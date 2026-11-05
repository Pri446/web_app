# CWIS Live Deployment Guide With Docker Compose and Direct PHP

**Release branch:** `v2.0.6cwis-new-indicators`  
**Prepared:** 1 November 2026  
**Updated:** 4 November 2026  
**Command formats:** Linux Bash with Docker Compose or direct PHP Artisan  
**Scope:** Equity, revised Safety, Sustainability, Responsibility, Accountability, RPM, shared settings, dashboard, monitoring, Excel/PDF and Treatment Plant annual inputs.

## 1. Important Release Conditions

This is an upgrade to an existing Base IMIS installation, not a standalone fresh database installer. Existing operational schemas, PostGIS objects and legacy CWIS calculation helpers are prerequisites.

The original ten grouped CWIS commits end at `e185d67f`. The current code candidate is `917cc22a`, including the upstream merge, existing validation fixes and the follow-up evidence, Safety-count and listing changes. Lock and verify the approved documentation-inclusive SHA/tag before deployment. This guide does not publish or deploy that candidate.

Do not deploy a single intermediate outcome commit by itself. Shared controllers, routes and the combined generator depend on the full reviewed series.

Earlier checks passed for 96 changed PHP files and 2 migration-layout tests with 35 assertions. Latest focused checks passed on 4 November: 27 PHP tests with 265 assertions (Safety counts/helper, shared listing and evidence validation) and 10 JavaScript tests. The full database regression suite was stopped before completion; do not describe it as passed. Complete staging acceptance before live deployment. The inventory is 142 entries after the SF-0 migration, leaving 17 specifications unresolved against the latest supplied 159 target. The original D2.3 wording referred to 144; do not confuse that wording with verification of the newer catalogue.

**Never run these as part of this upgrade:** `migrate:fresh`, `migrate:refresh`, table truncation, deletion of annual results, or broad seeding through `DatabaseSeeder`.

### Choose One Deployment Method

Use **Docker Compose** if the application runs in containers, or **Direct PHP** if PHP runs directly on the server. Both command sets perform the same application operation: choose one for your installation and **do not run both**. This applies to migrations, seeders, maintenance, cache commands and storage links.

All multiline examples use Linux Bash continuations. For Windows PowerShell, put commands on one line or use PowerShell continuation syntax. Direct PHP commands use the production PHP binary; replace `php` with its configured absolute path when needed. Run them from the deployed application root under the approved deployment account.

### Docker Compose prerequisites

Run the Docker commands below on the live host from the directory containing its approved Compose file. The `artisan` service must mount the approved application code, load the live database configuration, and share persistent `storage` and `bootstrap/cache` with the running web/PHP services. A one-off container's private filesystem is not persistent application storage.

No Compose file is present in this repository, so the production image, working directory, volumes, runtime user and service names must be verified against your server configuration. `artisan` is the service name supplied for this deployment, not a newly created service. Keep any required `-f` or `--env-file` options consistent across all commands.

Read-only checks:

```bash
sudo docker compose config --services
sudo docker compose run --rm artisan --version
sudo docker compose run --rm --entrypoint sh artisan -lc 'id; pwd; ls -ld storage storage/app storage/app/public bootstrap/cache'
```

The shell check requires `sh` in the image and the application root as its working directory. If those assumptions do not hold, use the equivalent approved image/container command instead. Do not print the full resolved Compose configuration into a shared log because it may expose secrets.

### Direct PHP prerequisites

The host must have the supported production PHP runtime/extensions and Composer. Confirm its application directory, environment and database connection. Read-only checks from the application root:

```bash
php -v
php artisan --version
composer --version
id
pwd
ls -ld storage storage/app storage/app/public bootstrap/cache
```

Use the existing web/PHP service-account ownership policy. Do not run the PHP deployment as an arbitrary root user or copy container database hostnames into a host-based deployment.

## 2. Release Contents

The release includes:
- New outcome controllers, validation requests, services, configuration and source forms.
- Catalogue and form-definition seeders.
- Country-aware monitoring, dashboards, Excel and PDF integration.
- Treatment Plant annual measurement add/edit/show support.
- Migration files and the SQL files they load.
- Official technical chapter and data dictionary.

Equity database files now use:
- `database/migrations/cwis/equity/` - nine migrations.
- `database/sql/cwis/equity/` - four SQL files.
- `database/seeders/Cwis/Equity/` - two namespaced seeders.
- `app/Providers/AppServiceProvider.php` - registers the nested migration directory.

Preserve directory capitalization for Linux. Do not deploy duplicate copies at the old root paths. Migration basenames remain unchanged: an already-applied migration remains applied after the folder move.

Do not include internal `docs/` studies, `tmp/`, `output/`, local backups, uploaded evidence or report-generation scripts in the release artifact. Uploaded evidence and backups belong in persistent server storage, not Git.

## 3. Predeployment Checklist

Record:
- The live application directory and web-server/queue service names.
- Current Git SHA or release identifier, deployed PHP/Composer versions and database version.
- Database host/name/user, without putting passwords in the deployment log.
- Current active country and all CWIS calculation parameters.
- Existing annual result counts, source submissions and approval status.
- Existing migration status.
- The rollback owner and maintenance window.

Confirm:
- PHP satisfies the repository requirement (PHP 8.1-compatible runtime with required extensions).
- PostgreSQL/PostGIS and existing IMIS schema objects are available.
- The migration database user owns, or can modify, the required tables, routines and triggers.
- Storage and bootstrap/cache are writable by the application service account.
- The existing PDF renderer and PHP upload limits support the release's upload/PDF workflows.
- The reviewed evidence policy accepts PDF, DOC and DOCX only, up to 5 MB per new/replacement file. Include the latest upload-validation source changes in the approved artifact. Configure PHP/web-server total request limits for the aggregate of multiple evidence files and form overhead, not just one 5 MB file.
- Production `APP_DEBUG` is false.
- `Approve CWIS` exists and is assigned to the intended reviewer roles. The reviewed seeders do not explicitly register this permission; use the approved permission-registration process if it is missing. Super Admin testing alone is insufficient.
- No unreviewed live working-tree modifications will be overwritten.

Do not copy the local development `.env` to live, change `APP_KEY`, or point live at the developer database.

For either deployment method, the operational order is: backup and pause writers -> maintenance -> deploy/build the approved code/image -> create/verify persistent evidence directories (section 10) -> clear cache and check migration status -> scoped migration command (section 7) -> six seeders (section 8) -> country/settings/permissions -> installation checks and staging acceptance -> refresh services/caches -> reopen. Follow each step only after the previous one succeeds.

### Read-only database checks

Run through your normal PostgreSQL administration connection:

```sql
SELECT current_database(), current_user;

SELECT to_regclass('cwis.data_source') AS catalogue,
       to_regclass('cwis.data_cwis') AS results,
       to_regclass('public.site_settings') AS settings,
       to_regclass('building_info.buildings') AS buildings,
       to_regclass('fsm.treatment_plants') AS plants;

SELECT to_regprocedure('public.insert_data_into_cwis_table(integer)') AS legacy_insert,
       to_regprocedure('public.update_data_into_cwis_table_revised_2024(integer)') AS legacy_master;

SELECT year, count(*) AS saved_result_rows
FROM cwis.data_cwis
GROUP BY year
ORDER BY year;

SELECT name, value, category
FROM public.site_settings
WHERE category = 'cwis_setting'
ORDER BY name;
```

If prerequisite objects are absent or their schemas differ from the tested installation, stop and reconcile the database baseline. The new migrations do not install every historical operational helper.

## 4. Back Up and Rehearse

Before the maintenance window, restore a recent live database backup to a separate staging database and deploy this exact release there. Keep production evidence protected; use a controlled copy where necessary.

For the cutover, stop new application/background writes, then take a coordinated backup of:
- The full PostgreSQL database, including functions, triggers, migrations and all schemas.
- `storage/app/`, including private evidence, public evidence and CWIS result backups.
- Production configuration through the organization's secure backup mechanism.
- The previous deployed release.

Example database commands below use PostgreSQL client tools. Replace every placeholder; keep the backup outside the web root/repository. Use your approved password prompt, service configuration or secret store.

```bash
pg_dump --host=DB_HOST --port=5432 --username=BACKUP_USER --format=custom --file=/secure/backups/imis_before_cwis.dump LIVE_DATABASE
pg_restore --list /secure/backups/imis_before_cwis.dump
```

Listing a backup is not a restore test. A successful isolated restore and smoke test are required before relying on it for recovery.

## 5. Put the Application Into Maintenance

Choose the maintenance command for your deployment method:

**Docker Compose**

```bash
sudo docker compose run --rm artisan down
```

**Direct PHP**

```bash
php artisan down
```

Maintenance mode does not stop queue workers, scheduled tasks, external integrations or direct database users. Pause the relevant writers using your server's service-management procedure.

Leave maintenance enabled if a deployment step fails. Do not automatically reopen the site after an error.

## 6. Deploy the Reviewed Release

Use the organization's approved release process. For a Git-managed server, fetch the authorized remote only after the release has actually been published:

```bash
git status --short
git fetch origin
git show --no-patch --oneline APPROVED_RELEASE_SHA
```

Deploy the approved SHA into a clean release directory or use the existing atomic release mechanism. Do not use a blind `git pull` or destructive reset on a dirty live checkout. Record both old and new release identifiers.

The full local CWIS changeset is:
1. `9496647b` - shared country-aware infrastructure.
2. `add4e88b` - Equity sources, approval and calculations.
3. `e12789a4` - revised Safety.
4. `98191117` - Treatment Plant annual inputs.
5. `4540216a` - Sustainability.
6. `0a10e434` - Responsibility.
7. `c15f8618` - Accountability.
8. `ac9d2cb8` - RPM.
9. `cda49a9b` - integrated generation/dashboard/exports.
10. `e185d67f` - official documentation.
11. `cc0ecb6d` - existing validation fixes.
12. `02983b65` - evidence file-picker alignment and regression tests.
13. `b4ec0cbc` - SF-0 counts, helper compatibility and dashboard/PDF integration.
14. `917cc22a` - indicator generation listing title and displayed columns.

Build/deploy the production image with its locked Composer dependencies using the existing approved image pipeline. Do not assume there is a Compose service named `composer`; none is defined in this repository. If Composer is run during image build, use `composer install --no-dev --prefer-dist --optimize-autoloader`, not composer update.

For **Direct PHP**, install the same locked dependencies on the host using the approved deployment account:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
```

Clear obsolete cached configuration and inspect migration history:

**Docker Compose**

```bash
sudo docker compose run --rm artisan optimize:clear
sudo docker compose run --rm artisan migrate:status
```

**Direct PHP**

```bash
php artisan optimize:clear
php artisan migrate:status
```

Use `composer install`, not `composer update`. Do not run `key:generate` with either command method on the existing installation.

## 7. Install Tables and Database Functions

### Recommended route when every pending migration is approved

**Docker Compose**

```bash
sudo docker compose run --rm artisan migrate --force
sudo docker compose run --rm artisan migrate:status
```

**Direct PHP**

```bash
php artisan migrate --force
php artisan migrate:status
```

The nested Equity directory is registered by AppServiceProvider. Laravel combines its migrations with root migrations and orders them by filename timestamp.

**The command runs every pending migration, including non-CWIS files.** Review the status first. If unrelated pending migrations are not approved, use the scoped route below instead.

### Final scoped CWIS migration commands

Use only the command for your deployment method after backups, maintenance mode, code/image deployment and migration-status review. These are Linux Bash commands: each continuation backslash must be the final character on its line. Do not add comments or explanatory text inside them. This scoped route avoids executing unrelated pending migrations.

**Docker Compose**

```bash
sudo docker compose run --rm artisan migrate --force \

  --path=database/migrations/cwis/equity \
  --path=database/migrations/2026_10_27_000001_install_revised_cwis_safety_functions.php \
  --path=database/migrations/2026_10_27_000002_configure_cwis_safety_parameters.php \
  --path=database/migrations/2026_10_29_000001_align_sf_1e_document_formula.php \
  --path=database/migrations/2026_10_29_000002_create_cwis_sustainability_draft_tables.php \
  --path=database/migrations/2026_10_29_000003_add_sustainability_approval_audit.php \
  --path=database/migrations/2026_10_29_000004_create_treatment_plant_annual_sustainability.php \
  --path=database/migrations/2026_10_29_000005_install_cwis_sustainability_calculations.php \
  --path=database/migrations/2026_10_30_000001_create_cwis_responsibility_tables.php \
  --path=database/migrations/2026_10_30_000002_install_cwis_responsibility_calculations.php \
  --path=database/migrations/2026_10_31_000001_create_cwis_accountability_tables.php \
  --path=database/migrations/2026_10_31_000002_install_cwis_accountability_calculations.php \
  --path=database/migrations/2026_10_31_000003_create_cwis_rpm_tables.php \
  --path=database/migrations/2026_10_31_000004_install_cwis_rpm_calculations.php \
  --path=database/migrations/2026_11_04_000001_install_cwis_safety_count_indicators.php \
  --path=database/migrations/2026_11_04_000002_align_cwis_emptying_status_helper.php
```

**Direct PHP**

```bash
php artisan migrate --force \
  --path=database/migrations/cwis/equity \
  --path=database/migrations/2026_10_27_000001_install_revised_cwis_safety_functions.php \
  --path=database/migrations/2026_10_27_000002_configure_cwis_safety_parameters.php \
  --path=database/migrations/2026_10_29_000001_align_sf_1e_document_formula.php \
  --path=database/migrations/2026_10_29_000002_create_cwis_sustainability_draft_tables.php \
  --path=database/migrations/2026_10_29_000003_add_sustainability_approval_audit.php \
  --path=database/migrations/2026_10_29_000004_create_treatment_plant_annual_sustainability.php \
  --path=database/migrations/2026_10_29_000005_install_cwis_sustainability_calculations.php \
  --path=database/migrations/2026_10_30_000001_create_cwis_responsibility_tables.php \
  --path=database/migrations/2026_10_30_000002_install_cwis_responsibility_calculations.php \
  --path=database/migrations/2026_10_31_000001_create_cwis_accountability_tables.php \
  --path=database/migrations/2026_10_31_000002_install_cwis_accountability_calculations.php \
  --path=database/migrations/2026_10_31_000003_create_cwis_rpm_tables.php \
  --path=database/migrations/2026_10_31_000004_install_cwis_rpm_calculations.php \
  --path=database/migrations/2026_11_04_000001_install_cwis_safety_count_indicators.php \
  --path=database/migrations/2026_11_04_000002_align_cwis_emptying_status_helper.php
```

Each scoped command includes the same 24 migration files: nine nested Equity/shared-foundation files and fifteen root migrations. Already-applied files are skipped. Run neither the alternative method nor the unscoped route after a successful scoped migration merely for completeness.

### Adding SF-0a SF-0b and SF-0c to an already deployed installation

The new migration `2026_11_04_000001_install_cwis_safety_count_indicators.php` installs three individual count calculators/updaters, registers their catalogue entries, copies existing SF-1a country assignments, and adds a dispatch call to the classic CWIS master. Its SQL source is `database/sql/cwis_safety_counts.sql`. Deploy the new SQL, migration, seeder and dashboard/PDF changes together. The existing one-row-per-building Safety helper is a prerequisite.

If the earlier 22 migrations have already run, run only one of these equivalent commands for the two new migrations. Already-applied files are skipped:

**Docker Compose**

```bash
sudo docker compose run --rm artisan migrate --force --path=database/migrations/2026_11_04_000001_install_cwis_safety_count_indicators.php --path=database/migrations/2026_11_04_000002_align_cwis_emptying_status_helper.php
```

**Direct PHP**

```bash
php artisan migrate --force --path=database/migrations/2026_11_04_000001_install_cwis_safety_count_indicators.php --path=database/migrations/2026_11_04_000002_align_cwis_emptying_status_helper.php
```

No new manual input form or building columns are required. SF-0a counts safely managed buildings; SF-0b sums their private-toilet households; SF-0c sums their private-toilet population. These are counts, not percentages. Existing annual values are not regenerated by installation. Use the authorized generation/rebuild process for an intended year after source review. The expected combined catalogue becomes 142; 17 entries from the stated 159 target still require the missing specifications.


The second migration repairs the legacy classification helper when fsm.applications.emptying_status is an integer: it replaces the obsolete IS TRUE predicate with IN (1, 2) and preserves the helper's declared boolean output through an explicit expression. The source column is not converted to boolean and no application records are updated. Boolean-source installations retain their existing behaviour. The old function definition is saved in cwis.emptying_status_function_backup for rollback.

### Generation error: IS TRUE received an integer

The RPM generation wrapper also calls the classic Safety/Equity chain. Consequently, an error shown against `generate_cwis_with_rpm(year)` can originate in the older shared building/emptying classification helper rather than an RPM formula. The integer application status must not be tested with `IS TRUE`.

After deploying the release files, run the compatibility migration on the **same database connection used by the web application** (choose one method):

```bash
sudo docker compose run --rm artisan migrate --force --path=database/migrations/2026_11_04_000002_align_cwis_emptying_status_helper.php
# Or without Docker:
php artisan migrate --force --path=database/migrations/2026_11_04_000002_align_cwis_emptying_status_helper.php
```

Read-only verification in that database:

```sql
SELECT current_database();
SELECT migration FROM public.migrations
WHERE migration = '2026_11_04_000002_align_cwis_emptying_status_helper';
SELECT pg_get_functiondef('public.execute_select_build_sanisys_nd_criterias_part3()'::regprocedure);
SELECT * FROM public.execute_select_build_sanisys_nd_criterias_part3() LIMIT 1;
```

For an integer source, the definition must contain `a.emptying_status IN (1, 2)`; when its declared result is boolean, the projection must use `(bsc.emptying_status IN (1, 2)) AS latest_emptying_status`. Do not drop dependent functions or cast the source column to boolean. If the migration is recorded but the old definition remains, stop and check for a later database dump/manual replacement before rerunning or repairing it. These checks do not regenerate annual results.

### Evidence submission and review

Every applicable Yes answer requires its own PDF, DOC or DOCX evidence file (maximum 5 MB) on submission and approval. A saved, physically present attachment for that same submission is reused unless replaced. Drafts may remain incomplete. Equity review and edit screens offer protected open/download links, as do the other outcomes.

All outcome forms submit asynchronously and display Laravel validation errors without replacing the form. A 422 validation response preserves selected files for correction and retry. Successful saves navigate using the server-selected redirect and retain the existing success toast. A manual refresh or leaving the page still clears unsaved browser file selections.

### Equity document migration files

The `--path=database/migrations/cwis/equity` argument loads every PHP migration in that folder, including:

```text
database/migrations/cwis/equity/
  2026_10_06_000001_create_cwis_equity_indicator_tables.php
  2026_10_08_000001_create_cwis_equity_documents_table.php
  2026_10_08_000002_create_cwis_indicator_country_table.php
  2026_10_08_000003_add_cwis_country_setting_and_submission_context.php
  2026_10_11_000001_create_cwis_form_fields_table.php
  2026_10_13_000001_install_cwis_equity_calculation_functions.php
  2026_10_13_000002_share_country_checks_across_cwis_indicators.php
  2026_10_27_000003_limit_cwis_result_decimal_places.php
  2026_10_28_000001_install_equity_generation_dispatch.php
```

The documents migration creates **`cwis.equity_documents`** with disk, path, original filename, uploader and timestamp fields, and adds document foreign keys to the worker-policy table. No extra duplicate documents migration is required. Database migration does not create the physical upload directories; create/verify those in section 10.

### Treatment Plant annual-input migration is included

The final command explicitly includes:

```text
database/migrations/2026_10_29_000004_create_treatment_plant_annual_sustainability.php
```

It creates **`cwis.treatment_plant_annual_sustainability`**, linked by `treatment_plant_id` to the existing `fsm.treatment_plants.id`. It does not create a second Treatment Plant master table or add these annual values directly to its master row.

| Field | Purpose |
|---|---|
| treatment_plant_id | Existing plant reference |
| data_year | Explicit measurement year; CWIS reporting 2026 reads data year 2025 |
| treated_effluent_generated_volume | Annual-average treated effluent generated, KLD |
| treated_effluent_reused_volume | Annual-average treated effluent reused, KLD |
| treated_biosolids_generated_volume | Annual-average treated biosolids generated, KLD |
| treated_biosolids_reused_volume | Annual-average treated biosolids reused, KLD |
| id, created_by, updated_by, created_at, updated_at | Identity and audit metadata |

The table has one record per plant/data year, a plant foreign key and non-negative/reused-not-greater-than-generated checks. Deploy the Treatment Plant request, model, controller/service and add/edit/show partials too; the migration alone does not display or save the new fields in the form.

### What installs the functions

| Outcome | Canonical SQL loaded by migrations |
| --- | --- |
| Equity | `database/sql/cwis/equity/cwis_equity_calculations.sql`; `cwis_equity_generation.sql` |
| Shared checks | `database/sql/cwis/equity/cwis_indicator_country_checks.sql`; original guard retained for rollback |
| Revised Safety | `database/sql/cwis_safety_revised.sql` |
| Sustainability | `database/sql/cwis_sustainability_calculations.sql` |
| Responsibility | `database/sql/cwis_responsibility_calculations.sql` |
| Accountability | `database/sql/cwis_accountability_calculations.sql` |
| RPM | `database/sql/cwis_rpm_calculations.sql` |

Normally, there is no separate manual pgAdmin copy/paste step. Migrations install the function definitions. Refresh both the `cwis` and `public` Functions lists in pgAdmin afterward.

### If live already ran an earlier version

Changing the contents of an SQL file or moving a migration does not make an applied migration run again.

Compare live routine definitions and migration history with the approved release. Deliver any necessary replacements through a **new reviewed update migration**. For compatible signatures, use `CREATE OR REPLACE FUNCTION`. Signature/return-type changes require a dependency-aware plan.

Do not delete migration-history rows, drop the whole public schema, or use `DROP ... CASCADE` to force installation. Do not blindly replay a migration that creates existing tables/triggers. Some Safety definitions deliberately retain legacy argument signatures while reading their values from settings.

## 8. Seed Only the Required CWIS Data

Run only one of the equivalent seeder command sets below after the required tables exist:

**Docker Compose**

```bash
sudo docker compose run --rm artisan db:seed --class='Database\Seeders\Cwis\Equity\CwisDataSourceSeeder' --force
sudo docker compose run --rm artisan db:seed --class='Database\Seeders\Cwis\Equity\CwisFormFieldsSeeder' --force
sudo docker compose run --rm artisan db:seed --class=CwisSustainabilityDataSourceSeeder --force
sudo docker compose run --rm artisan db:seed --class=CwisResponsibilityDataSourceSeeder --force
sudo docker compose run --rm artisan db:seed --class=CwisAccountabilityDataSourceSeeder --force
sudo docker compose run --rm artisan db:seed --class=CwisRpmDataSourceSeeder --force
```

**Direct PHP**

```bash
php artisan db:seed --class='Database\Seeders\Cwis\Equity\CwisDataSourceSeeder' --force
php artisan db:seed --class='Database\Seeders\Cwis\Equity\CwisFormFieldsSeeder' --force
php artisan db:seed --class=CwisSustainabilityDataSourceSeeder --force
php artisan db:seed --class=CwisResponsibilityDataSourceSeeder --force
php artisan db:seed --class=CwisAccountabilityDataSourceSeeder --force
php artisan db:seed --class=CwisRpmDataSourceSeeder --force
```

The scoped seeders register catalogue entries and form metadata; they do not copy local submitted forms to live. The additional outcome seeders also insert their configured Bangladesh mappings. Accountability excludes AC-4a and AC-4b from its new Bangladesh assignments; it does not delete existing assignments left by a previous configuration.

Do not run the entire DatabaseSeeder on an existing live system. Do not assume every seeder is a neutral no-op: inspect existing catalogue ID collisions, old labels, country mappings and duplicate codes before seeding.

The Safety settings migration supplies missing revised parameters without overwriting existing city values. `CwisSettingsSeeder` is optional for missing baseline settings; review it before using it because it includes legacy defaults. Confirm the saved values through the settings screen after deployment.

## 9. Configure Country and Settings

### Country applicability

`public.site_settings` stores the active country. `cwis.indicator_country` links catalogue IDs to NP/BD; ALL uses the full catalogue.

**Do not blindly run CwisIndicatorCountryExampleSeeder on live.** It contains arbitrary development assignments. Deploy the nationally/municipally approved mappings through a reviewed migration or explicit mapping seeder.

Switching the country does not transfer a Bangladesh submission to Nepal or convert ALL submissions into country-specific sources. Existing saved results also retain their generation context.

### Calculation parameters

Review the actual saved parameters, rather than overwriting them with document defaults:
- Per-capita water consumption.
- Wastewater and greywater conversion percentages.
- Annual FS generation rates for the defined containment groups.
- BOD compliance threshold.

Revised Safety functions read these values from CWIS Settings. Existing city values may differ from the reference defaults. Annual source values such as subsidies, revenues, CAPEX and reuse volumes are not settings constants.

## 10. Create Evidence Directories and Verify Permissions

Preserve writable persistent storage for:
- `storage/app/public/cwis/equity-documents` where Equity's configured public disk is used.
- `storage/app/cwis/sustainability-documents`.
- `storage/app/cwis/responsibility-documents`.
- `storage/app/cwis/accountability-documents`.
- `storage/app/cwis/rpm-documents`.
- `storage/app/cwis/result-backups`.

Existing Equity document metadata records its disk/path; preserve those exact locations during migration. The private folders must not be made publicly accessible. Use protected evidence routes for review/download.

If the application's existing public disk requires a storage link, verify it under the normal server policy. Do not expose the whole `storage/app` directory. Do not use world-writable permissions to solve ownership problems.

### Docker Compose directory creation

First identify the **actual web/PHP runtime UID and GID** from the approved running application/container setup. The `id` output of the artisan service may differ from the web runtime user. Set `APP_UID` and `APP_GID` in the host shell to those verified numeric values; do not guess them or automatically use root.

The following fails if either variable is unset, the working directory is wrong, or permissions do not allow the runtime user to create/write the folders. It creates missing directories only and does not delete or replace existing uploads.

```bash
: "${APP_UID:?Set APP_UID to the verified web/PHP runtime UID first}"
: "${APP_GID:?Set APP_GID to the verified web/PHP runtime GID first}"

sudo docker compose run --rm \
  --user "$APP_UID:$APP_GID" \
  --entrypoint sh artisan -lc '
    set -eu
    test -d storage/app
    test -d bootstrap/cache
    mkdir -p \
      storage/app/public/cwis/equity-documents \
      storage/app/cwis/sustainability-documents \
      storage/app/cwis/responsibility-documents \
      storage/app/cwis/accountability-documents \
      storage/app/cwis/rpm-documents \
      storage/app/cwis/result-backups
    for directory in \
      storage/app/public/cwis/equity-documents \
      storage/app/cwis/sustainability-documents \
      storage/app/cwis/responsibility-documents \
      storage/app/cwis/accountability-documents \
      storage/app/cwis/rpm-documents \
      storage/app/cwis/result-backups \
      bootstrap/cache
    do
      test -w "$directory"
    done
  '
```

If this fails, have the server administrator correct the verified persistent volume's ownership/ACL using the existing deployment policy. Do not use chmod 777 or a broad recursive chown against an unverified mount. With user-namespace/rootless Docker, container IDs may map differently to host IDs.

### Direct PHP directory creation

For a host-based installation, set `APP_USER` in the host shell to the verified web/PHP runtime account. Run the following from the application root. It creates the same folders as the Docker option, using the host runtime user; it does not execute PHP or alter existing evidence files.

```bash
: "${APP_USER:?Set APP_USER to the verified web/PHP runtime account first}"

sudo -u "$APP_USER" sh -c '
  set -eu
  test -d storage/app
  test -d bootstrap/cache
  mkdir -p \
    storage/app/public/cwis/equity-documents \
    storage/app/cwis/sustainability-documents \
    storage/app/cwis/responsibility-documents \
    storage/app/cwis/accountability-documents \
    storage/app/cwis/rpm-documents \
    storage/app/cwis/result-backups
  for directory in \
    storage/app/public/cwis/equity-documents \
    storage/app/cwis/sustainability-documents \
    storage/app/cwis/responsibility-documents \
    storage/app/cwis/accountability-documents \
    storage/app/cwis/rpm-documents \
    storage/app/cwis/result-backups \
    bootstrap/cache
  do
    test -w "$directory"
  done
'
```

If permissions fail, stop and have the server administrator correct the verified application's ownership/ACL through the normal server policy. Do not substitute guessed users or world-writable permissions.

### Public storage link

Check any existing `public/storage` link. If missing and approved for this deployment, create the configured public-disk link with:

**Docker Compose**

```bash
sudo docker compose run --rm artisan storage:link
```

**Direct PHP**

```bash
php artisan storage:link
```

This links only the configured public disk; private Sustainability/Responsibility/Accountability/RPM folders must remain outside it. Verify existing Equity public-disk exposure against the site's evidence-access policy. Test an upload and reopening its saved evidence on **staging** before live acceptance. For Docker, confirm the running web container sees the same files after the one-off artisan container exits. For Direct PHP, confirm the web/PHP process can access the folders created under APP_USER.

## 11. Read-only Installation Verification

Run the following without generating annual results:

```sql
SELECT outcome, count(*) AS indicator_count
FROM cwis.data_source
GROUP BY outcome
ORDER BY outcome;

SELECT indicator_code, count(*)
FROM cwis.data_source
GROUP BY indicator_code
HAVING count(*) > 1;

SELECT ds.indicator_code, ds.outcome, m.country_code
FROM cwis.indicator_country m
JOIN cwis.data_source ds ON ds.id = m.data_source_id
ORDER BY m.country_code, ds.outcome, ds.indicator_code;

SELECT to_regprocedure('public.generate_cwis_with_rpm(integer)') AS combined_generator,
       to_regprocedure('public.generate_cwis_with_equity(integer)') AS equity_generator,
       to_regprocedure('public.rebuild_cwis_sustainability(integer,bigint)') AS sustainability_rebuild,
       to_regprocedure('public.rebuild_cwis_accountability(integer,bigint)') AS accountability_rebuild,
       to_regprocedure('public.rebuild_cwis_rpm(integer,bigint)') AS rpm_rebuild;

SELECT n.nspname AS schema_name,
       count(*) FILTER (WHERE p.proname ~ '^calculate_ac_[0-9]') AS ac_calculators,
       count(*) FILTER (WHERE p.proname ~ '^calculate_rpm_[0-9]') AS rpm_calculators,
       count(*) FILTER (WHERE p.proname ~ '^update_data_into_cwis_table_ac_[0-9].*_newsan$') AS ac_updaters,
       count(*) FILTER (WHERE p.proname ~ '^update_data_into_cwis_table_rpm_[0-9].*_newsan$') AS rpm_updaters
FROM pg_proc p
JOIN pg_namespace n ON n.oid = p.pronamespace
WHERE n.nspname IN ('cwis', 'public')
GROUP BY n.nspname
ORDER BY n.nspname;
```

Explicitly verify the two source tables highlighted in this deployment:

```sql
SELECT to_regclass('cwis.equity_documents') AS equity_document_metadata,
       to_regclass('cwis.treatment_plant_annual_sustainability') AS annual_plant_inputs;

SELECT table_schema, table_name, column_name, data_type
FROM information_schema.columns
WHERE table_schema = 'cwis'
  AND table_name IN ('equity_documents', 'treatment_plant_annual_sustainability')
ORDER BY table_name, ordinal_position;
```

Also run `migrate:status` through your chosen Docker or Direct PHP command after installation and confirm both named migrations are applied. If history says an earlier migration was applied but its table is absent, stop and investigate drift; do not delete history rows or force-create a duplicate migration during live cutover.

For this release, the cwis schema contains 29 individual AC calculators and 43 individual RPM calculators. The public schema contains 29 AC updaters and 43 RPM updaters. Extra overloads can increase raw routine counts; reconcile them instead of accepting counts alone.

The expected clean catalogue has Equity 15, Safety 25, Sustainability 9, Responsibility 21, Accountability 29 and RPM 43. Live custom indicators can legitimately increase these totals. Do not delete municipal entries to force the count to match.

## 12. Functional Acceptance

Perform substantive tests in staging, not against live production data. The repository's PHPUnit configuration can inherit the application's database connection. **Do not run the database test suite on live or assume APP_ENV=testing selects an isolated database.**

Verify on staging:
1. Country selection filters the available outcomes, fields, monitoring results and dashboard.
2. Draft permits incomplete source values but rejects invalid supplied values.
3. Another reporting year creates its own source instead of replacing an earlier year.
4. Saved evidence remains visible and does not need re-uploading during approval.
5. Submit is not the same as approve; approval validates all applicable fields and Yes evidence.
6. Approved records are locked and generation accepts the matching country/year source.
7. First generation requires all applicable manual-source outcomes to be approved.
8. Normal generation does not overwrite an existing annual set.
9. Explicit rebuild confirmation, page-contained loader and success redirect work.
10. Outcome-only rebuilds preserve other outcomes where supported.
11. Finite numerical results show three decimal places; missing data is not fabricated zero.
12. Dashboard, monitoring, Excel and PDF agree for the same country and year.
13. Sanitation Department and Super Admin see the intended shared records with the appropriate actions.
14. Wrong-country access, unauthorized approval and evidence access are blocked.

Sustainability checks:
- Reporting year 2026 reads Treatment Plant annual measurements for data year 2025.
- SS-1a/SS-1b include only eligible operational centralized/decentralized WWTPs.
- SS-2 and SS-3 are ratios, with no x100.
- Annual revenue inputs are not per-use toilet fee rates.

Safety checks:
- SF-1b uses previous-year distinct eligible containments.
- SF-1e follows the installed alignment equation: 100 x (365 x daily treatment capacity) / (365 x previous-year sludge-volume sum). Verify the document's daily-volume assumption against the actual source units before publication; do not silently reinterpret annual transaction totals as daily volumes.
- SF-1d/SF-1f use the saved settings and consistent annual/daily units.
- BOD equal to the threshold does not pass the strict SF-1g test.

## 13. Finish the Deployment

After acceptance checks:
- Refresh application configuration/cache according to the established deployment process.
- Restart PHP/opcache or the relevant application service when required.
- Restart queue workers if used; resume scheduler/integration writers at the approved point.
- Verify PDF renderer availability and evidence storage permissions.
- Check application logs for new errors.

Typical Artisan commands:

**Docker Compose**

```bash
sudo docker compose run --rm artisan config:cache
sudo docker compose run --rm artisan view:cache
sudo docker compose run --rm artisan queue:restart
sudo docker compose run --rm artisan up
```

**Direct PHP**

```bash
php artisan config:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

Use queue:restart only when queue workers are part of this installation. Route caching is optional; verify the complete legacy route set supports it before enabling it.

After reopening, smoke-test login, settings, listing, one existing approved source, monitoring, dashboard, Excel and PDF. Compare annual result counts with the recorded baseline. Installation alone must not delete or regenerate existing years.

## 14. Generating or Rebuilding Official Results

Do not generate test years or rebuild historical live results just to verify installation.

After deployment and approval of the actual source data:
1. Select the intended country.
2. Enter and approve applicable annual source forms.
3. Ensure operational source records and Treatment Plant data years are complete.
4. Generate the new reporting year through the application.
5. Review numerical and qualitative outputs before publication.
6. Use Rebuild only for an explicitly authorized recalculation.

Changes to code/settings do not retroactively alter saved values. The application backs up result context before rebuild, but that does not replace the full deployment backup.

## 15. Failure and Recovery

Stop on the first failed deployment step and keep maintenance enabled. Record the failing migration, error and last successful step. Inspect migration status before retrying; the whole deployment is not one global transaction.

Do not rely on a blanket `migrate:rollback` through either Docker Compose or Direct PHP:
- Several new calculation migrations intentionally retain functions/audit tables in their down methods.
- Earlier down methods can remove tables or functions and have cross-outcome dependencies.
- A failed deployment can involve both schema and source-data changes.

Recovery must use the tested plan: redeploy the previous code only if its database compatibility is established, or restore the coordinated predeployment database/storage backup and previous code with DBA approval. A database restore can lose writes made after the backup; reconcile them before reopening.

For a narrow function defect, prefer a reviewed forward-fix migration with compatible CREATE OR REPLACE definitions, validated in staging first.

## 16. Deployment Record

Record the approved SHA, backup locations, migration/seeder commands and exit status, country mappings, parameter changes, verification evidence, operator/reviewer and reopen time. Keep credentials and personal/evidence contents out of the log.

This guide was created locally for review. No live connection, migration, seeding, release push or deployment was performed while preparing it.

