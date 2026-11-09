Version: V1.1.0

# Dashboard

This document describes the current dashboard architecture for the main IMIS Dashboard, Building Dashboard, and Utility Dashboard. It covers server-side data preparation, asynchronous page loading, cache behavior, authorization boundaries, frontend loading states, and maintenance requirements.

## Dashboard Technologies

- Charts are rendered with Chart.js.
- Cards and count boxes use Bootstrap, HTML, and CSS.
- Icons use SVG assets and Font Awesome.
- Laravel controllers and service classes retrieve and prepare dashboard data.
- Blade templates render the dashboard shells and content fragments.
- A shared JavaScript loader requests and inserts dashboard content.

## Supported Dashboard Loading Flow

The main, Building, and Utility dashboards use a shell-first loading pattern:

1. The browser requests the dashboard page.
2. The controller `index()` method returns a lightweight shell containing the page title, loading message, and an empty dashboard content target.
3. The shared loader sends an authenticated request to the dashboard content endpoint.
4. The controller `content()` method prepares the authorized dashboard data and renders the corresponding content view.
5. The endpoint returns a JSON response containing `status` and `html`.
6. The loader inserts the HTML into the dashboard content target.
7. Scripts contained in the inserted content are initialized so that Chart.js charts render correctly.
8. The loading message is removed after successful insertion. A failed request displays an error and retry action.

This pattern improves the initial page response because the browser can display the page shell before all dashboard queries and chart views are complete.

## Routes and Controllers

| Dashboard | Shell route | Content route | Controller |
|---|---|---|---|
| Main IMIS | `GET /dashboard` | `GET /dashboard/content` | `app/Http/Controllers/HomeController.php` |
| Building | `GET /building-info/buildings/buildingdashboard` | `GET /building-info/buildings/buildingdashboard/content` | `app/Http/Controllers/BuildingInfo/BuildingDashboardController.php` |
| Utility | `GET /utilityinfo/utilitydashboard` | `GET /utilityinfo/utilitydashboard/content` | `app/Http/Controllers/UtilityInfo/UtilityDashboardController.php` |

The shell and content requests require an authenticated user. The main and Utility routes apply authentication middleware in `routes/web.php`. The Building controller applies authentication middleware in its constructor.

## Main Dashboard Data Retrieval

`HomeController@index` returns `resources/views/dashboard/indexAdmin.blade.php`, which is now the main dashboard shell rather than the complete dashboard body.

`HomeController@content` performs the following work:

- Reads and normalizes supported filters such as the selected year.
- Builds an authorization-scoped dashboard cache key.
- Retrieves cached authorized HTML when an eligible entry exists.
- Resolves dashboard data through `DashboardService` when the entry is missing or requires refresh.
- Renders `resources/views/dashboard/_content.blade.php`.
- Returns the rendered HTML through a private, non-browser-cacheable JSON response.
- Logs unexpected failures without exposing exception details to the browser.

`HomeController@buildDashboardData` assembles count boxes and chart data. `app/Services/DashboardService.php` contains the shared query and cache logic used by the main dashboard.

## Main Dashboard Cache Design

The authorization-scoped data and HTML cache currently applies to the main IMIS Dashboard. Building and Utility use asynchronous content loading but do not currently use this dashboard HTML cache.

### Cache Key Isolation

`DashboardService::dashboardCacheKey` builds a SHA-256 key from the following values:

- Dashboard namespace version.
- User ID.
- Service provider ID.
- Treatment plant ID.
- Ordered role names.
- Ordered direct and role-derived permission names.
- Active application locale.
- Normalized dashboard filters.

The user ID remains a final isolation boundary even when two users have the same role and organization scope. Filter keys are sorted before hashing so that equivalent filters do not create duplicate entries merely because their input order differs.

Do not remove an authorization or data-scope value from the key without a security review and corresponding cache-isolation tests.

### Fresh and Stale Periods

Cache durations are configured in `config/dashboard.php`:

| Configuration | Default | Purpose |
|---|---:|---|
| `dashboard.cache_ttl_seconds` | 180 seconds | Period during which cached dashboard data and HTML are fresh |
| `dashboard.cache_stale_ttl_seconds` | 1,800 seconds | Maximum period during which previously authorized HTML can remain available while it is refreshed |

When authorized HTML is fresh, it is returned immediately. When it is stale but still retained, it is returned to the same scoped key and Laravel schedules a refresh after sending the response. A 60-second cache lock prevents multiple requests from refreshing the same entry at the same time.

The stale response is not a shared public response. It remains isolated by the same user, role, permission, provider, plant, locale, filter, and namespace values used for the fresh response.

### Cache Invalidation

`AppServiceProvider::registerDashboardCacheInvalidation` listens for saved, deleted, and restored events from models that feed dashboard widgets. When one of those models changes, `DashboardService::invalidateDashboardCache` increments the dashboard namespace version.

Old entries become unreachable and expire naturally. This method works with file, database, and Redis cache stores without requiring wildcard key deletion or cache tags. It also avoids clearing unrelated application cache entries.

Relevant data-changing workflows must continue to use Eloquent model events. A bulk SQL update that bypasses Eloquent will not trigger this invalidation automatically and must explicitly call the dashboard invalidation service.

## Building Dashboard

`BuildingDashboardController@index` returns `resources/views/dashboard/buildingDashboardShell.blade.php`.

The shell includes the shared loader and requests the named `buildingdashboard.content` route. `BuildingDashboardController@content` calls `buildDashboardData`, renders `resources/views/dashboard/buildingDashboard.blade.php`, and returns the HTML in JSON.

The controller checks widget permissions before executing the related queries. The content response uses `Cache-Control: private, no-store` because this endpoint does not use the main dashboard's authorization-scoped server cache.

## Utility Dashboard

`UtilityDashboardController@index` returns `resources/views/dashboard/utilityDashboardShell.blade.php`.

The shell includes the shared loader and requests the named `utilitydashboard.content` route. `UtilityDashboardController@content` calls `buildDashboardData`, renders `resources/views/dashboard/utilityDashboard.blade.php`, and returns the HTML in JSON.

The content response uses `Cache-Control: private, no-store`. Utility dashboard caching must not be added by reusing a broad cache key; it must define and test every relevant authorization and data-scope dimension first.

## Shared Asynchronous Loader

The shared loader is located at `resources/views/dashboard/_asyncDashboardLoader.blade.php`.

It provides the following behavior:

- Displays `Loading dashboard data...` while the content request is active.
- Requests the configured content URL with same-origin credentials.
- Expects a JSON object with `status: "ok"` and an `html` string.
- Redirects to the login page when the content request shows that the session has expired.
- Inserts the returned HTML into `#dashboard-content`.
- Recreates executable script elements after insertion so chart initialization runs.
- Displays a recoverable error message and retry button when the request fails.
- Removes `aria-busy` when loading succeeds or fails.

Dashboard content views must not assume their scripts ran during the initial shell response. Chart code must support initialization after the content HTML has been inserted.

## Sidebar Navigation Loader

Dashboard navigation links in `resources/views/includes/sidebar.blade.php` use the `data-dashboard-navigation` attribute.

When a marked link is activated:

- Only the selected link changes to a loading state.
- Its icon changes to a spinner and its label changes to `Loading...`.
- The link receives `aria-busy="true"` and `aria-disabled="true"`.
- Repeated click, double-click, Enter, and Space activation are blocked at the capture phase.
- Other sidebar entries are not given loading indicators.
- The original state is restored when the page is restored through browser Back or Forward navigation.

Do not use `pointer-events: none` as the only repeat-click protection. Pointer-event suppression can allow the event target to fall through to an underlying or parent element. The capture-phase guard is the control that prevents duplicate navigation.

## Views and Components

| Purpose | View |
|---|---|
| Main dashboard shell | `resources/views/dashboard/indexAdmin.blade.php` |
| Main dashboard content | `resources/views/dashboard/_content.blade.php` |
| Building dashboard shell | `resources/views/dashboard/buildingDashboardShell.blade.php` |
| Building dashboard content | `resources/views/dashboard/buildingDashboard.blade.php` |
| Utility dashboard shell | `resources/views/dashboard/utilityDashboardShell.blade.php` |
| Utility dashboard content | `resources/views/dashboard/utilityDashboard.blade.php` |
| Shared content loader | `resources/views/dashboard/_asyncDashboardLoader.blade.php` |
| Shared dashboard navigation | `resources/views/includes/sidebar.blade.php` |

Count-box partials are stored in `resources/views/dashboard/countBox`. Chart partials are stored under `resources/views/dashboard/charts` and the corresponding module-specific dashboard directories.

## Cache Driver and Deployment

The cache implementation uses Laravel's configured cache driver.

- A single application instance can use a supported local cache driver.
- Multiple application instances must use a shared cache such as Redis so dashboard entries, refresh locks, namespace versions, and authentication rate limits are consistent across servers.
- Confirm cache connectivity and lock support before deploying the optimization.
- Rebuild configuration, route, and view caches through the approved deployment procedure.
- No database migration is required for the dashboard shell, loader, or cache implementation.

The following optional environment values control dashboard freshness:

```dotenv
DASHBOARD_CACHE_TTL_SECONDS=180
DASHBOARD_CACHE_STALE_TTL_SECONDS=1800
```

## Testing

The primary automated tests are:

- `tests/Unit/DashboardOptimizationTest.php`
- `tests/Unit/DashboardServiceCacheTest.php`
- `tests/Feature/BuildingDashboardOptimizationTest.php`
- `tests/Feature/UtilityDashboardLoadingTest.php`

The tests cover cache duration, normalized filters, authorization boundaries, stale refresh, namespace invalidation, shell-first responses, JSON HTML contracts, authentication, and dashboard navigation controls.

QA must also measure the deployed system under fixed conditions:

- User role and permissions.
- Service provider and treatment plant scope.
- Selected year and other filters.
- Database size.
- Cold or warm application cache.
- Dashboard response time and time to first byte.
- SQL query count and total database time.
- Slowest queries.
- Time until all permitted dashboard cards and charts appear.

Run at least three equivalent cold-cache attempts and three equivalent warm-cache attempts. Record the test environment and measurement source with the result.

## Current Scope Boundary

The shell-first content pattern is implemented for the main, Building, and Utility dashboards. The authorization-scoped `DashboardService` data and HTML cache is implemented for the main dashboard only.

FSM, KPI, CWIS, and other dashboards must not be described as fully optimized until their controller, content contract, authorization scope, query behavior, and tests have been reviewed and implemented.
