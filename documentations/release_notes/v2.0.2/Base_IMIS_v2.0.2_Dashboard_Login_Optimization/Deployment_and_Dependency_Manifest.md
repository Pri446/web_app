# Base IMIS v2.0.2 - Deployment and Dependency Manifest

## Dependency result

No migration, seeder, permission, Composer package, NPM package, or GeoServer change is required.

## Runtime scope

The release contains the three dashboard controllers, `DashboardService`, request-local permission-group reuse in `User`, dashboard invalidation registration in `AppServiceProvider`, `config/dashboard.php`, the shared dashboard loader, Main/Building/Utility shell and content views, selected chart initialization corrections, login submission-state handling, three dashboard content routes, and selected-item sidebar loading protection.

## Mixed-file controls

Only the three dashboard-content route additions belong in `routes/web.php`. The VAPT `throttle:web-login`, feedback public-ID binding, and CWIS generator-data routes are excluded.

Only dashboard navigation attributes and the selected-item loader belong in `resources/views/includes/sidebar.blade.php`. Desludging schedule navigation and CWIS Generator Data are excluded.

## Explicit release exclusions

- VAPT rate limiting, clickjacking, and password-reset enumeration changes.
- CWIS generator, URL binding, notification, localization, seed-user, Dependabot, and unrelated FSM changes.
- Current unrelated `composer.lock`, `package.json`, and `package-lock.json` working-tree changes.

## Focused tests

```bash
php artisan test tests/Unit/DashboardOptimizationTest.php
php artisan test tests/Unit/DashboardServiceCacheTest.php
php artisan test tests/Feature/BuildingDashboardOptimizationTest.php
php artisan test tests/Feature/UtilityDashboardLoadingTest.php
```

Verified result: 18 dashboard tests passed. Login loading remains a manual UI scenario for this optimization-only release.
