# Authorization Route Audit and Change Impact

**Audit scope:** Laravel route registration, effective route middleware, controller middleware, and the three vulnerability-assessment endpoints  
**Audit type:** Static/read-only review of the current checkout  
**Important limitation:** This documents the code currently in this workspace. The deployed test environment must be compared with this commit and its route cache before the vulnerability can be considered fixed.

## 1. Main Finding

In the current checkout, all three reported endpoints have Laravel authentication middleware:

| Endpoint | Effective middleware | Controller/action |
|---|---|---|
| `GET /auth/users/{user}` | `web, auth` | `Auth\UserController@show` |
| `GET /fsm/emptying/{emptying}` | `web, auth` | `Fsm\EmptyingController@show` |
| `GET /tax-payment/data` | `web, auth` | `TaxPaymentInfo\TaxPaymentController@getData` |

Therefore, a genuinely logged-out request should not receive the protected response from this version of the code. If the assessment environment still returns data, investigate these deployment causes first:

1. The test server is running an older commit.
2. The server has stale route, configuration, or opcode cache.
3. The tester used a valid session belonging to a low-privilege role described as "Guest"; that is an authorization failure, not absence of authentication.
4. A reverse proxy or web-server rule is serving a cached protected response.
5. The test host is routed to a different application instance or release directory.

## 2. Routes Without Authentication Middleware

The following routes have no effective `auth` or `auth:sanctum` middleware in the current application route table.

### 2.1 Expected public framework/authentication routes

These normally must remain public, but require validation, throttling, and abuse tests where applicable:

- `GET /`
- `GET /login`
- `POST /login`
- `GET /register`
- `POST /register`
- `GET /password/reset`
- `POST /password/email`
- `GET /password/reset/{token}`
- `POST /password/reset`
- `POST /api/login`
- `GET /sanctum/csrf-cookie`
- `GET /cas/login`
- `GET /cas/callback`
- `POST /cas/logout`
- `GET /cas/user`
- `POST /cas/auth/validate`
- `POST /auth/validate`

The CAS routes come from a dependency and need separate verification against that package's internal checks. In particular, confirm that `/cas/user` returns no identity information without a valid CAS session and apply throttling to credential-validation endpoints.

### 2.2 Public application route requiring abuse protection

| Route | Current protection | Impact/recommendation |
|---|---|---|
| `POST /contact/send` | `web` only | Public by design, but add strict validation, rate limiting, bot protection, safe sender handling, and tests against mail abuse. |

### 2.3 Confirmed route-protection defects or unsafe development routes

| Route | Current state | Risk | Required action |
|---|---|---|---|
| `GET /api/test-xss` | `api, security.headers`; deliberately returns a script payload | Test/debug endpoint exposed in the application | Remove it outside automated test code, or restrict it to a local/testing environment. |
| `GET /redirect-to-map/{ebps_id}` | Effective route table contains only `web` | May disclose or generate map access for an arbitrary EBPS identifier | Register and test a real authentication mechanism; the source references `fixed_token_auth`, but that alias is not registered in `Http\Kernel`. |
| `GET /files` | `web` only; referenced `FileController` does not exist | Public route and broken controller resolution | Remove the obsolete route or implement the controller behind authentication and permission checks. |
| `POST /files/upload` | `web` only; referenced `FileController` does not exist | Potential unauthenticated file upload if the controller is restored | Remove it or protect it with authentication, upload permission, MIME/content validation, size limits, private storage, randomized names, malware scanning, and throttling. |

The missing `FileController` also prevents the normal `php artisan route:list` command from completing. This should be corrected because it blocks reliable operational inspection.

## 3. Reported Endpoint Change Impact

### 3.1 `/auth/users/{id}`

**Primary files/functions affected**

- `routes/web.php`: the `auth` users route group and `Route::resource('users', ...)`.
- `app/Http/Controllers/Auth/UserController.php`
  - `__construct()` for action permissions.
  - `show()` for record retrieval.
  - `authorizeUserRecord()` for same-user/organizational scope.
  - `edit()`, `update()`, and `destroy()` must reuse the same record-scope rule; action permission alone is not sufficient.
  - `getLoginActivity()` and `getHelpDeskData()` require explicit record and organization scoping.
- `app/Services/Auth/UserService.php`
  - `getAllData()` controls which users appear in lists.
  - `getUserRelatedData()` retrieves associated treatment plant, help desk, and service-provider information.
  - update/delete helper methods must not accept an out-of-scope user ID.
- `app/Models/User.php`: policy subject and organization relationships.
- `app/Policies/UserPolicy.php`: recommended centralized policy to replace private, controller-specific checks.
- User views and AJAX calls: must handle `403` and `404` without displaying partial sensitive data.
- Tests: unauthenticated, missing permission, self, same organization, cross organization, and privileged municipal roles.

**Current residual gap:** `show()` calls `authorizeUserRecord()`, but `edit()`, `update()`, and `destroy()` do not visibly reuse that record-level check. Those mutation paths require the same policy.

### 3.2 `/fsm/emptying/{id}`

**Primary files/functions affected**

- `routes/web.php`: the authenticated `fsm` group and Emptying resource routes.
- `app/Http/Controllers/Fsm/EmptyingController.php`
  - `__construct()` maps list/view/add/edit/delete/history/export permissions.
  - `show()` calls the current record-scope check.
  - `authorizeEmptyingRecord()` defines service-provider, treatment-plant, creator, and municipal access.
  - `edit()`, `update()`, `history()`, and `destroy()` must call the same policy/check before reading or changing the record.
  - `create($applicationId)` must validate access to the parent Application.
- `app/Services/Fsm/EmptyingService.php`
  - `getDatatable()` and its base query must be scoped before filters are applied.
  - `updateEmptying()`, `getEmptyingHistory()`, and `export()` must apply the same organization scope.
  - Supporting employee, vehicle, application, and containment lookups must not expose cross-provider data.
- `app/Models/Fsm/Emptying.php` and related Application/Containment models.
- `app/Policies/EmptyingPolicy.php`: recommended reusable location for `view`, `update`, `delete`, `history`, and export scope.
- Role/permission seeders and migrations controlling Guest Emptying permissions.
- Tests for every resource action and list/export path, not only `show()`.

**Current residual gap:** `show()` applies `authorizeEmptyingRecord()`, but direct `edit`, `update`, `history`, and `destroy` paths do not visibly invoke it. The service query used by list/export must also be tested for cross-provider and cross-treatment-plant leakage.

### 3.3 `/tax-payment/data`

**Primary files/functions affected**

- `routes/web.php`: authenticated Tax Payment route group.
- `app/Http/Controllers/TaxPaymentInfo/TaxPaymentController.php`
  - `__construct()` currently assigns `List Property Tax Collection` only to `index`; add `getData` to the same permission or create a dedicated permission.
  - `getData()` returns tax code, BIN, ward, owner name, owner contact, and due-year data and therefore requires explicit permission and jurisdiction scope.
  - `export()` and `exportunmatched()` require the identical scope used by `getData()`.
- Tax-payment query/service layer: recommended extraction of a single authorized base query reused by list and export.
- Ward/municipality relationships: use these to constrain data to the caller's jurisdiction.
- DataTables frontend: handle `401/403`, and do not retry or display cached data.
- Tests: logged out, logged-in without permission, permitted ward/municipality, cross-jurisdiction filters, export, pagination, search, and malformed filters.

**Confirmed permission gap:** authentication exists, but `getData()` is not included in the controller's `List Property Tax Collection` permission middleware. Any authenticated role may therefore reach the action unless another control outside this controller blocks it. The query is also not visibly restricted by municipality or ward ownership.

## 4. Shared Modules Affected by a Correct Fix

| Module | Change impact |
|---|---|
| `routes/web.php` | Group protected resources consistently; remove/debug-restrict unsafe routes; correct obsolete file routes. |
| `routes/api.php` | Remove the XSS test route and keep protected APIs inside `auth:sanctum`; avoid duplicate authentication declarations. |
| `app/Http/Kernel.php` | Register any real custom middleware alias used by routes, or replace it with a supported authentication middleware. |
| `app/Providers/AuthServiceProvider.php` | Register User, Emptying, and Tax Payment policies. |
| Controllers | Call `authorize()`/policies for each object action and apply permission middleware to list/data/export actions. |
| Services/query builders | Start every list, search, export, and mutation from an actor-scoped query. Never fetch globally and filter only in the UI. |
| Role/permission seeders and migrations | Remove unintended Guest permissions and deploy corrections to existing databases. |
| Frontend views/DataTables | Correctly handle `401`, `403`, and `404`; prevent stale sensitive data from remaining visible. |
| Cache/deployment | Clear route/config/application cache and PHP opcode cache; verify the deployed commit and effective route list. |
| Audit logging | Record denied cross-scope access without logging sensitive response data. |
| Automated tests | Add route-level and object-level authorization matrices for all affected actions. |

## 5. Recommended Implementation Order

1. Confirm the exact commit and effective route table on the assessment server.
2. Reproduce with a fresh browser/no cookies, then with an authenticated Guest account; record which case actually succeeds.
3. Remove `/api/test-xss` and remove or secure the two `/files` routes.
4. Replace/fix `fixed_token_auth` for `/redirect-to-map/{ebps_id}` and add a test proving anonymous denial.
5. Add the missing tax `getData` permission and jurisdiction-scoped query.
6. Create reusable User and Emptying policies; apply them to show, edit, update, delete, history, parent-child actions, lists, and exports.
7. Update permission seeders and add a migration for existing role assignments.
8. Add automated negative tests before deployment.
9. Deploy code and migration, clear caches, restart long-running PHP workers where applicable, and inspect the effective route list.
10. Have QA execute the role/object matrix and attach response evidence showing that denied responses contain no protected fields.

## 6. QA Test Design Summary

For every protected object endpoint, use two different records and at least these actors:

- No session/cookies.
- Expired or invalid session.
- Guest role with a valid login.
- User lacking the action permission.
- Authorized user whose organization owns record A.
- Same-role user whose organization owns record B.
- Approved municipality administrator.

Run each action against own-scope and other-scope IDs: show, edit form, update, delete, history, list/data, search/filter, and export. A test passes only when the status is correct **and** the body, headers, redirect, downloaded file, and subsequent UI state contain no protected data.

