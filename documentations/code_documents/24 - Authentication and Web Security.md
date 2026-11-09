Version: V1.0.0

# Authentication and Web Security

## Scope and Release Status

This document describes three Base IMIS security remediations:

- **BASEIMIS-41 — Clickjacking:** prevent another website from displaying Base IMIS inside an embedded frame.
- **BASEIMIS-44 — Login rate limiting:** temporarily restrict repeated website and API login requests.
- **BASEIMIS-47 — Password-reset enumeration:** prevent the reset response from revealing whether an email address has an account.

The application files, configuration, tests, and fixtures for these controls have been recovered into the working tree. The release owner must commit, review, and deploy the corresponding changes before presenting this behavior as deployed. Local and historical test results do not establish production acceptance.

Credential seeding, CAPTCHA, MFA, open-redirect protection, and unrelated session-security changes are outside this document's implementation scope. Each finding retains its own Jira acceptance and security-retest result even when the three fixes ship together.

## Entry Points and Application Files

| Purpose | Endpoint | Responsible application code |
|---|---|---|
| Website credential submission | `POST /login` | `routes/web.php`; `Auth/LoginController`; `LoginRequest` |
| API credential submission | `POST /api/login` | `routes/api.php`; `Api/AuthController` |
| Password-reset request form | `GET /password/reset` | Existing `Auth/ForgotPasswordController` flow |
| Password-reset email request | `POST /password/email` | Application override in `Auth/ForgotPasswordController` |
| HTML anti-framing protection | Applicable website pages and rendered HTML errors | `PreventClickjacking`; HTTP kernel; exception handler |

Class paths are relative to `app/Http/Controllers`, `app/Http/Requests`, or `app/Http/Middleware` as applicable. The existing login form is `resources/views/auth/login.blade.php`.

## 1. Clickjacking Protection

### Expected Behavior

The application supplies these browser instructions on applicable HTML responses:

```http
Content-Security-Policy: frame-ancestors 'none'
X-Frame-Options: DENY
```

This prevents other websites from embedding Base IMIS in a frame. No framing exception is approved.

### Implementation Locations

| File | Responsibility |
|---|---|
| `config/security_headers.php` | Central values for the anti-framing policy |
| `app/Http/Middleware/PreventClickjacking.php` | Applies headers to HTML responses and preserves unrelated CSP directives |
| `app/Http/Kernel.php` | Registers the middleware in the web group |
| `app/Exceptions/Handler.php` | Applies the policy to rendered HTML exception responses |

The middleware replaces conflicting `frame-ancestors` directives and `X-Frame-Options` values, while preserving other existing application CSP directives. It does not add document-framing headers to non-HTML responses. Redirects receive the policy when their response content type is HTML.

### DevOps Responsibilities

Laravel is the source of truth for these two headers. Nginx, ingress, CDN, and load-balancer settings must pass them through without replacing, removing, duplicating, or conflicting with them. If infrastructure already supplies a broader CSP, coordinate its consolidation with the application team and preserve the other approved protections.

Verify the public endpoint, not only the internal application address. Include normal HTML, authenticated HTML, and HTML errors such as 403/404 responses. Separately check errors generated directly by infrastructure, which Laravel cannot control. An approved external test page must be unable to embed the application.

## 2. Login Rate Limiting

### Named Limiters and Keys

`app/Providers/RouteServiceProvider.php` defines:

- `web-login`, applied through `throttle:web-login` to `POST /login`.
- `api-login`, applied through `throttle:api-login` to `POST /api/login`.

The API endpoint retains the general `throttle:api` control. Credential validation, role restrictions, and token creation remain with the existing controllers.

The web identifier is the `username` form field; the API identifier is `email`. For limiter keys, each identifier is trimmed, lowercased, and SHA-256 hashed. Keys combine the endpoint namespace with the identifier hash, client IP, or both. Passwords are excluded. This is normalization of the supplied identifier, not resolution of different username/email aliases to one user ID.

### Default Controls

| Counter | Requests allowed | Window |
|---|---:|---:|
| Identifier and IP | 5 | 1 minute |
| IP across identifiers | 20 | 1 minute |
| Identifier across IPs | 15 | 15 minutes |

The IP-only counter prevents bypass by rotating usernames or emails. The identifier-only counter reduces distributed guessing against the same supplied identifier. Web and API namespaces have separate counters.

All admitted login requests count, including successful requests. Unknown identifiers are counted without checking account existence. Once any applicable counter is exhausted, further requests are temporarily blocked. Each counter expires automatically from its first admitted attempt in that window. Repeated blocked attempts do not create a permanent database account lockout.

### Responses

| Endpoint | Response when blocked |
|---|---|
| Website login | Redirect back with a translated generic validation error under `username`; preserve only the username, exclude the password, and include `Retry-After` |
| API login | HTTP 429 JSON with `status: false`, a translated generic `message`, and `Retry-After` |

The web response is a validation-style redirect rather than an HTTP 429 error page. Neither message identifies whether an account exists or which counter triggered the block. Clients must use the returned waiting time; the longer identifier window can remain active after a one-minute counter expires.

See [15 - API documentation](15%20-%20API%20documentation.md#login-rate-limiting-and-retry-behavior-baseimis-44) for the API response example and retry guidance.

### Configuration

`config/security.php` supplies the environment-backed values:

```dotenv
LOGIN_RATE_LIMIT_IDENTITY_IP_ATTEMPTS=5
LOGIN_RATE_LIMIT_IDENTITY_IP_DECAY_MINUTES=1
LOGIN_RATE_LIMIT_IP_ATTEMPTS=20
LOGIN_RATE_LIMIT_IP_DECAY_MINUTES=1
LOGIN_RATE_LIMIT_IDENTITY_ATTEMPTS=15
LOGIN_RATE_LIMIT_IDENTITY_DECAY_MINUTES=15
TRUSTED_PROXIES=
```

Keep these names in `.env.example` with approved defaults and no secrets. Adjust deployment values consistently across application nodes. Shared public internet addresses, such as offices, can cause legitimate users to share the IP counter; review traffic and support reports when tuning thresholds.

### Trusted Proxies and Shared Cache

`app/Http/Middleware/TrustProxies.php` reads the explicit proxy IPs/CIDRs in `TRUSTED_PROXIES`. The prepared implementation filters string wildcard entries `*` and `**`. Leave the setting empty when PHP receives requests directly. DevOps must verify that the application sees the real visitor's IP through the actual proxy chain and does not accept arbitrary client-supplied forwarding headers.

Laravel stores the counters in its configured cache. For multiple application servers, use a shared store such as Redis:

```dotenv
CACHE_DRIVER=redis
```

Every application node must use consistent cache connection/database settings and the same application cache prefix. Provision Redis and the required PHP client before selecting it. Separate local file caches cannot enforce a common counter across nodes. Routine cache clearing also clears the applicable counters.

## 3. Password-Reset Account Privacy

The application override of `sendResetLinkEmail()` in `app/Http/Controllers/Auth/ForgotPasswordController.php` validates the email, trims and lowercases it, and calls the existing Laravel password broker. It returns a generic acknowledgement regardless of the broker's account-existence outcome.

The public message is defined by `passwords.request_received` in `resources/lang/en/passwords.php`:

> If an account exists for this email address, a password reset link will be sent.

For registered and unregistered valid addresses, web requests must have matching status, redirect destination, session message, and absence of account-specific validation errors. JSON requests must return HTTP 200 with the same `message` body. Missing or invalid emails still receive normal validation errors. Eligible registered accounts receive reset notifications; unknown and soft-deleted accounts do not.

This remediation addresses public wording and response structure. It does not claim constant response timing, change reset-token formats, or replace the existing password broker's eligibility rules. The reviewer-requested form throttling described below is an additional abuse control; it applies equally to registered and unknown email addresses.

DevOps must verify mail delivery and the public reset-link address. If notifications use a background queue, confirm its workers run. Keep `APP_DEBUG=false` in production to avoid exposing development error details. Supply real mail/Redis secrets through the existing secure configuration system, not in documentation or shareable templates.

## 4. Forgot Password and Contact Us Rate Limiting

Reviewer follow-up: apply the login limiter's three-layer approach to Forgot Password and the Contact Us form at the bottom of the landing page.

| Form | Protected route | Named limiter | Configuration |
|---|---|---|---|
| Forgot Password | `POST /password/email` | `password-reset` | `security.password_reset_rate_limit` |
| Contact Us | `POST /contact/send` | `contact` | `security.contact_rate_limit` |

Each form independently defaults to 5 requests per minute for the same normalized email/IP, 20 requests per minute per IP across emails, and 15 requests per 15 minutes per normalized email across IPs. Email values are trimmed, lowercased, and hashed in keys. The counters are independent of each other and the web/API login counters. All submissions admitted by the limiter count, including requests that subsequently fail validation. Invalid or non-string email input still consumes the IP control.

Limiter checks happen before controller processing. Blocked requests do not call the password broker or Contact Us mail service. Restrictions expire automatically. The password broker's existing notification cooldown remains separate; receiving the generic acknowledgement does not promise that a new email is sent on every request.

For browsers, blocked reset requests redirect to the reset form with an `email` validation error. Blocked Contact Us requests redirect to `/#contact` with a visible `contact_rate_limit` error. Only allowlisted form inputs are flashed, and the contact form escapes restored values. Both responses include `Retry-After`. Requests expecting JSON receive HTTP 429 with `status: false`, a generic translated `message`, and `Retry-After`.

Add these environment names to deployment templates, with approved values:

```dotenv
PASSWORD_RESET_RATE_LIMIT_IDENTITY_IP_ATTEMPTS=5
PASSWORD_RESET_RATE_LIMIT_IDENTITY_IP_DECAY_MINUTES=1
PASSWORD_RESET_RATE_LIMIT_IP_ATTEMPTS=20
PASSWORD_RESET_RATE_LIMIT_IP_DECAY_MINUTES=1
PASSWORD_RESET_RATE_LIMIT_IDENTITY_ATTEMPTS=15
PASSWORD_RESET_RATE_LIMIT_IDENTITY_DECAY_MINUTES=15
CONTACT_RATE_LIMIT_IDENTITY_IP_ATTEMPTS=5
CONTACT_RATE_LIMIT_IDENTITY_IP_DECAY_MINUTES=1
CONTACT_RATE_LIMIT_IP_ATTEMPTS=20
CONTACT_RATE_LIMIT_IP_DECAY_MINUTES=1
CONTACT_RATE_LIMIT_IDENTITY_ATTEMPTS=15
CONTACT_RATE_LIMIT_IDENTITY_DECAY_MINUTES=15
```

These settings use the same explicit trusted-proxy and shared-cache requirements as login. DevOps must rebuild the configuration cache after deployment. QA should verify the sixth same-email/IP request is blocked at the defaults, rotating emails cannot bypass the IP limit, rotating IPs cannot bypass the email limit, counters remain separate between forms/login, blocks expire, and registered/unknown reset requests remain publicly equivalent. Test email delivery only in an approved test environment.

## Testing and Acceptance

The prepared suites and fixtures are:

| Test file | Primary coverage |
|---|---|
| `tests/Feature/LoginRateLimitingTest.php` | Allowed/blocked attempts, keys, expiry, endpoint separation, and trusted proxies |
| `tests/Feature/PublicFormRateLimitingTest.php` | Reset/contact controls, blocked service calls, JSON 429, web messages, key separation, validation, and expiry |
| `tests/Feature/Http/Controllers/LoginControllerTest.php` | Credential/input contracts, page controls, role rules, and API response contracts using controlled test doubles |
| `tests/Feature/LoginSecurityHardeningTest.php` | Login throttle and header regression checks |
| `tests/Feature/Http/Middleware/PreventClickjackingTest.php` | HTML/error responses, existing CSP preservation, conflicts, and non-HTML behavior |
| `tests/Feature/Http/Controllers/ForgotPasswordControllerTest.php` | Registered/unknown response equivalence, validation, normalization, eligible notifications, and soft-deleted users |

Fixtures are under `tests/Fixtures/Login`, `tests/Fixtures/Clickjacking`, and `tests/Fixtures/ForgotPassword`. The controller suites use shared helpers under `tests/Feature/Http/Concerns`; retain other modules' helper methods when integrating the saved changes.

After integrating the application fixes, run:

```text
php artisan test --filter="LoginControllerTest|LoginRateLimitingTest|LoginSecurityHardeningTest|PreventClickjackingTest|ForgotPasswordControllerTest|PublicFormRateLimitingTest"
```

Use a dedicated test database for database-backed tests; transactions alone do not isolate an existing development database. `ForgotPasswordControllerTest` creates its own SQLite in-memory user and password-reset tables before beginning its test transactions. It requires PDO SQLite and does not exercise the full PostgreSQL/PostGIS schema. Run the combined suite on the actual release branch. Mocked token/authentication assertions do not prove real database credential lookup or persisted token creation. Local tests do not establish production proxy, Redis, browser, or mail behavior.

## Deployment Responsibilities

1. The release owner includes all required application files, configuration, tests, and fixtures for the three findings.
2. DevOps sets approved login limits, exact proxy addresses, shared-cache settings where required, and working mail/worker settings.
3. Rebuild Laravel's configuration cache through the normal deployment process. Follow the secure deployment sequence in [02 - Technical Information](02%20-%20Technical%20Information.md), including its handling of deployment-only secrets.
4. QA verifies real-account web/API login below the limits, temporary blocking and expiry, and API `Retry-After` handling.
5. DevOps verifies public anti-framing headers, real client IPs, cross-node counters, and reset-email delivery.
6. QA compares registered and unregistered reset responses and verifies an external frame is blocked.
7. Security records independent retest acceptance for each finding. Record the tested commit, environment, and results without passwords, tokens, or reset links.

Deployment dates and production acceptance are not established by this document. If a release is rolled back, coordinate the application and server configuration and retain effective server-side protection while correcting the problem. Tune thresholds only after checking proxy/cache behavior and legitimate usage.

## Database and Documentation Impact

These three fixes add no application database table, column, relationship, migration, or data backfill. The existing data dictionary and ERD therefore require no structural update. Cache counters are temporary operational data, not new user-account fields.

This technical document supports the three individual impact analyses, developer guides/baselines, and VAPT remediation reports. Keep the per-finding evidence separate from the shared release and DevOps instructions.
