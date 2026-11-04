# **Release Notes IMIS v2.0.6 CWIS Upgrade**

Full Release Note

#### **1. Release Information**

| **Field** | **Information** |
|---|---|
| Version | v2.0.6 release candidate; final tag pending approval |
| Release name | IMIS CWIS Indicators and Country Localization |
| Release date | Pending production approval; notes updated 4 November 2026 |
| Release type | Feature upgrade and calculation corrections |
| Source branch | `v2.0.6cwis-new-indicators` |
| Base branch | Branch-creation base requires confirmation; current candidate includes a merge from `base-imis:master` |
| Release tag | Not assigned at the current candidate commit |
| Reference commit | `6e057cc1586e2921b27ce1ea938ceca9f3dcca37` before the CWIS implementation series |
| CWIS implementation commit | `e185d67f` ends the ten grouped CWIS commits |
| Current code candidate | `917cc22a` including the upstream merge, validation fixes and three follow-up code commits |
| Final release commit | Lock the final documentation-inclusive SHA/tag after review; no production tag is assigned here |
| Pull request | Not supplied |
| Status | Implementation committed; focused follow-up checks passed; full acceptance, release/tag and production sign-off pending |
| Audience | Technical lead, QA, deployment, PM and implementation teams |

#### **2. Release Summary**

This release extends the classic CWIS generation process to six outcome groups: Equity, Safety, Sustainability, Responsibility, Accountability, and Resource Planning and Management (RPM). It adds annual source forms, retained evidence, draft/submission/review/approval, country applicability and individual database functions while retaining official calculated values in `cwis.data_cwis`.

The upgrade also revises the specified Safety formulas, introduces annual Treatment Plant reuse measurements, and integrates applicable outcomes into monitoring, dashboard, Excel and PDF views. Source approval remains separate from result generation. Explicit rebuilding refreshes saved results after authorization.

| **Outcome** | **Registered entries** | **Release change** |
|---|---:|---|
| Equity | 15 | EQ-1 retained; EQ-2 to EQ-6.5 added |
| Safety | 25 | SF-0a/SF-0b/SF-0c and SF-1 added; SF-1a to SF-1g revised; remaining classic indicators retained |
| Sustainability | 9 | Reuse, O&M cost recovery, CAPEX coverage and campaign assessments |
| Responsibility | 21 | Completed source workflow, assessments, calculators/updaters and presentation |
| Accountability | 29 | Institutional accountability assessments and individual functions |
| RPM | 43 | Financing, budgets, resource allocation, appraisal and strategy assessments |
| **Total** | **142** | 35 quantitative and 107 qualitative entries; the latest supplied target is 159 |

#### **3. Purpose and Scope**

- Extends IMIS to capture CWIS inputs that are absent from existing operational modules.
- Reuses buildings, containments, emptyings, treatment plants and sample information where the calculation already has an operational source.
- Provides one country applicability mechanism for forms, calculation dispatch, monitoring, dashboard and exports.
- Separates entered values, approved source records, generation snapshots and final results.
- Preserves existing-year generation protection and provides an explicit controlled rebuild action.
- Includes Treatment Plant annual source entry and the official technical/data-dictionary changes.

This release does not migrate local test submissions to live, automatically recalculate historical results, or establish an approved national indicator catalogue. The original D2.3 wording referred to 144; the latest supplied indicator-count target is 159. The current 142-entry catalogue remains 17 specifications short of that latest target. The supplied Safety file covers SF-1 to SF-1g rather than the full updated Safety catalogue, so the remaining codes must not be guessed.

#### **4. User-Facing Changes**

- CWIS Settings includes All, Nepal and Bangladesh selection and editable calculation parameters.
- Add Indicator Data uses outcome radio options, followed by the available years for that outcome.
- Forms show applicable fields for the selected country, including quantitative and qualitative sections.
- Users can save incomplete drafts, submit completed sources and review/correct values before approval.
- Saved evidence can be reviewed/downloaded and reused during approval without re-uploading.
- New/replacement evidence accepts PDF, DOC and DOCX only, up to 5 MB per file; all five file pickers use the same policy as backend validation.
- The shared page is titled CWIS Indicator Generation Data. Submission ID is removed from its displayed columns; internal IDs and action routes are unchanged. Default sorting is year descending, then Updated At descending.
- SF-0a/SF-0b/SF-0c appear as green number cards in the All and Safety views and are included in PDF, monitoring and Excel after result generation. They are counts, not percentages.
- Laravel field-specific errors remain on the source/review page; successful saves use Toastr and return to the listing.
- Approved records are read-only. Approval does not silently generate annual results.
- Generate/Rebuild actions use confirmation, a page-contained loader and duplicate-click protection.
- Treatment Plant forms include annual effluent and biosolids generated/reused measurements.
- Dashboard tabs/cards, monitoring and exports show applicable saved year/country results. Added outcome cards use three columns on desktop and their configured colours.
- Finite numerical outputs use three decimal places; missing information is not converted into zero or No.

#### **5. Technical Change Summary**

| **Area** | **Change** | **Repository Path / Reference** |
|---|---|---|
| Shared applicability | Catalogue selection, active country, saved-result context and source filtering | `app/Services/Cwis/CwisIndicatorService.php`; `cwis.indicator_country`; `public.site_settings` |
| Equity workflow | Database-driven field metadata, numerical inputs, worker-policy evidence and approval | `CwisGeneratorDataController.php`; `CwisIndicatorDataRequest.php`; `CwisFormService.php`; `CwisEquityWorkflowService.php` |
| Safety | Revised SF-1/SF-1a-g formulas, settings-backed parameters, period and unit corrections | `database/sql/cwis_safety_revised.sql`; `config/cwis_safety_settings.php` |
| Safety counts | Distinct safe-building count and private-toilet household/population sums; individual calculators/updaters and master dispatch | `database/sql/cwis_safety_counts.sql`; 2026_11_04_000001 migration; count-card partials |
| Helper compatibility | Integer emptying-status predicate and boolean result compatibility; source column type and records remain unchanged | `2026_11_04_000002_align_cwis_emptying_status_helper.php` |
| Treatment Plant | Explicit annual measurement year and generated/reused volume records | `TreatmentPlantAnnualSustainability.php`; `TreatmentPlantService.php`; Treatment Plant views/request |
| New outcome forms | Dedicated submission/answer or financial/campaign storage and shared workflow conventions | `app/Http/Controllers/Cwis/`; `app/Http/Requests/Cwis/`; `app/Services/Cwis/`; `config/cwis_*.php` |
| Calculations | Individual per-indicator calculators/updaters and combined generation chain | `database/sql/cwis/equity/`; outcome SQL files under `database/sql/` |
| Generation context | Approved source references/snapshots and country validation | `cwis.generation_runs`; outcome-specific generation-run tables |
| Evidence | Retained file references, physical-file checks and protected review/download | `app/Support/CwisEvidence.php`; outcome controllers |
| Dashboard/export | Applicable saved results, outcome cards and existing PDF/Excel integration | `CwisNewDashboardController.php`; `ChartController.php`; `MneCsvExport.php`; `resources/views/cwis/` |
| Migration discovery | Registers nested Equity migrations without changing their history names | `app/Providers/AppServiceProvider.php` |
| Documentation | Equity technical chapter, schema-qualified dictionary and deployment guidance | `documentations/code_documents/07 - CWIS IMS.md`; `documentations/data_dictionary/data_dictionary.md` |

**Calculation behaviour**

Equity includes ratios, workforce/leadership percentages and evidence-based policy outcomes. Responsibility, Accountability and RPM use approved qualitative assessments, not invented numerical scores. All 29 Accountability and all 43 RPM indicators have individual calculators and public updater functions, backed by shared validation/evaluation logic.

Sustainability SS-1a/SS-1b are percentages. SS-2 and SS-3 follow the confirmed written equations as ratios, without multiplying by 100. Plant measurement data year is reporting year minus one; reporting 2026 reads plant measurements for 2025.

Safety SF-1b uses previous-year distinct eligible containments. The installed SF-1e equation annualizes both daily capacity and the previous-year sludge-volume sum by 365, following the aligned document assumption; source-volume units must be verified during acceptance. Revised formulas read their configured parameters from CWIS Settings.

SF-0a counts distinct safely managed buildings; SF-0b sums building_info.buildings.household_with_private_toilet; SF-0c sums building_info.buildings.population_with_private_toilet. The existing one-row-per-building safety helper prevents join multiplication. Missing/negative private totals return No data rather than a partial-city total; an empty eligible set returns zero. Country assignments inherit existing SF-1a mappings; no mapping means availability under All only until approved country assignments are added.

The combined entry point is `public.generate_cwis_with_rpm(year)`, including Accountability, Responsibility, Sustainability, Equity and the retained classic Safety pathway. Official results are written to `cwis.data_cwis`. The result table remains year-based; generation metadata records country context rather than introducing an independent country column in every result row.

#### **6. Files Added or Changed**

The grouped source changes are recorded in these commits:

| **Commit** | **Group** |
|---|---|
| `9496647b` | Shared country-aware settings, validation and formatting |
| `add4e88b` | Equity sources, approval, form metadata and calculation dispatch |
| `e12789a4` | Revised Safety calculations |
| `98191117` | Treatment Plant annual reuse measurements |
| `4540216a` | Sustainability |
| `0a10e434` | Responsibility |
| `c15f8618` | Accountability |
| `ac9d2cb8` | RPM |
| `cda49a9b` | Shared generation, dashboard and export integration |
| `e185d67f` | Technical documentation and data dictionary |
| `748d87ad` | Subsequent upstream merge; full release acceptance still required |
| `cc0ecb6d` | Existing validation fixes preceding this follow-up series |
| `02983b65` | Evidence picker alignment and upload-validation regression tests |
| `b4ec0cbc` | SF-0 counts, migration/helper compatibility, catalogue, dashboard/PDF and tests |
| `917cc22a` | Indicator generation listing title, columns and regression coverage |

Main source locations:
- `app/Http/Controllers/Cwis/`, CWIS request classes and `app/Services/Cwis/`.
- `app/Http/Controllers/Fsm/CwisSettingController.php` and Treatment Plant controller/request/model/service.
- `app/Support/CwisEvidence.php` and `CwisValueFormatter.php`.
- `config/cwis_country_examples.php`, `cwis_equity.php`, `cwis_safety_settings.php`, `cwis_sustainability.php`, `cwis_responsibility.php`, `cwis_accountability.php` and `cwis_rpm.php`.
- `database/migrations/cwis/equity/`, the thirteen root CWIS upgrade migrations and outcome SQL files.
- `database/seeders/Cwis/Equity/` and named outcome catalogue seeders.
- `resources/views/cwis/`, `resources/views/fsm/cwis-setting/`, Treatment Plant annual partials, export template and sidebar.
- `routes/web.php`, `tests/Feature/Cwis/`, migration-layout unit test and CWIS JavaScript workflow tests.

Internal studies, generated reports/screenshots, `tmp/`, `output/`, evidence uploads and database/result backups are excluded from the source changeset.

#### **7. Routes APIs or Interfaces Added or Changed**

These are authenticated web interfaces, not new external Bearer-token APIs.

| **Method** | **Endpoint / Interface** | **Purpose** | **Access / Permission** |
|---|---|---|---|
| GET | `/cwis/generator-data`; `/cwis/generator-data/data` | Shared source listing and datatable data | Authentication and List CWIS |
| GET / POST / PUT | `/cwis/generator-data/create`; `/cwis/generator-data`; `/cwis/generator-data/{submission}` | Equity source creation and updates | List CWIS plus operation-specific Add/Edit checks |
| GET / POST | `/cwis/generator-data/{submission}/review`; `/approve`; `/calculate` | Review/approve and explicit result calculation | Approve CWIS for review/approval; Add CWIS for calculation; source/country checks |
| GET / POST / PUT | `/cwis/sustainability-data/*`; `/responsibility-data/*`; `/accountability-data/*`; `/rpm-data/*` | Outcome creation, viewing, review, approval, updates and calculation | Authentication, List CWIS and operation-specific Add/Edit/Approve checks |
| GET | Outcome `/{submission}/documents/{document}` routes | Protected saved-evidence review/download | Authenticated permitted source access and country checks |
| GET / POST | Existing CWIS monitoring/generation interfaces | Country-aware generation, monitoring and export | Existing CWIS permissions and new source guards |
| Existing web actions | CWIS Settings, dashboard, Treatment Plant add/edit/show, Excel/PDF | Updated country, source and result presentation | Existing module permissions; verify role assignments |

The wildcard rows summarize route families, not literal routable URLs. Exact definitions are in `routes/web.php` and the controller permission checks.

#### **8. Permission and Seeder Changes**

Existing permission names used include **List CWIS**, **Add CWIS**, **Edit CWIS**, and **Approve CWIS**. Export/dashboard paths retain their existing permission handling. No blanket role escalation is part of this release.

**Approval permission deployment check:** the reviewed seeders do not contain an explicit `Approve CWIS` registration. Confirm that live has this permission and that approved reviewer roles receive it. If absent, add it through the project's reviewed permission-registration/role-assignment process before enabling approval. Testing only with Super Admin is insufficient.

| **Seeder File** | **Purpose / Role** |
|---|---|
| `database/seeders/Cwis/Equity/CwisDataSourceSeeder.php` | Retains baseline catalogue and registers Equity additions, SF-1 and SF-0a/SF-0b/SF-0c |
| `database/seeders/Cwis/Equity/CwisFormFieldsSeeder.php` | Database-driven Equity input definitions |
| `database/seeders/CwisSustainabilityDataSourceSeeder.php` | Sustainability catalogue and configured Bangladesh assignments |
| `database/seeders/CwisResponsibilityDataSourceSeeder.php` | Responsibility catalogue and configured Bangladesh assignments |
| `database/seeders/CwisAccountabilityDataSourceSeeder.php` | Accountability catalogue and configured Bangladesh assignments |
| `database/seeders/CwisRpmDataSourceSeeder.php` | RPM catalogue and configured Bangladesh assignments |
| `database/seeders/CwisSettingsSeeder.php` | Missing baseline/settings values; optional and subject to review |
| `database/seeders/CwisIndicatorCountryExampleSeeder.php` | Development/example mappings only; not an approved production national catalogue |

**Seeder commands after migration**

```bash
php artisan db:seed --class='Database\Seeders\Cwis\Equity\CwisDataSourceSeeder' --force
php artisan db:seed --class='Database\Seeders\Cwis\Equity\CwisFormFieldsSeeder' --force
php artisan db:seed --class=CwisSustainabilityDataSourceSeeder --force
php artisan db:seed --class=CwisResponsibilityDataSourceSeeder --force
php artisan db:seed --class=CwisAccountabilityDataSourceSeeder --force
php artisan db:seed --class=CwisRpmDataSourceSeeder --force
```

Do not run the entire DatabaseSeeder on an existing live database. Review country mapping side effects first. Seeders adding Bangladesh assignments do not automatically remove old assignments or migrate existing source country codes.

#### **9. Configuration Requirements**

- Confirm active_country and the approved NP/BD mapping in CWIS Settings/catalogue relationships. All uses the full catalogue.
- Verify saved Safety water consumption, conversion percentages, annual FS-generation rates and BOD threshold.
- Preserve the live environment configuration, APP_KEY, database connection and existing persistent storage.
- Keep private evidence/result-backup folders protected and writable by the application service account.
- Verify upload size/type settings, PHP/web-server limits and PDF renderer availability.
- No additional numerical settings constants are required for the qualitative Responsibility/Accountability/RPM assessments or the annual Sustainability source values.
- No credentials, production endpoints or confidential evidence contents are included in these notes.

#### **10. Dependencies and Compatibility**

- PHP and Composer dependencies are defined by the release's composer.json/composer.lock; the application requires PHP ^8.1.
- PostgreSQL/PostGIS and the existing IMIS operational schemas/helpers remain prerequisites.
- Nested migrations require the updated AppServiceProvider; namespaced seeders require correct directory capitalization on Linux.
- Current operational attributes do not form a complete historical snapshot merely because construction/event dates are filtered.
- Existing migration history is retained; moving an applied migration does not rerun it.
- Editing an SQL file does not replace an installed database routine when its migration has already run. Later changes require a reviewed update migration.
- Ordinary generation does not overwrite saved annual results. Recalculation requires the explicit authorized rebuild workflow.
- Cross-outcome wrapper dependencies require the full reviewed deployment series rather than isolated intermediate commits.

#### **11. Deployment Instructions**

1. Lock the reviewed candidate SHA/tag and complete post-merge staging verification.
2. Back up the full database, function definitions, persistent evidence/storage, live configuration and previous code release.
3. Enter maintenance mode and pause queue/scheduler/integration writes as required.
4. Deploy the approved complete artifact; do not overwrite a dirty live checkout.
5. Install locked production dependencies and clear stale caches.
6. Review migration status and run the 24 included CWIS migrations. Default migrate runs every pending migration, including unrelated ones. Existing installations with the original 22 applied need only the two new 2026_11_04 migrations.
7. Run only the scoped catalogue/form seeders listed above.
8. Confirm approval permission registration, role assignments, country mappings, saved parameters and storage.
9. Verify installed routines, source tables, catalogue uniqueness and generation context.
10. Refresh caches/services, perform smoke checks, obtain approval and reopen.
11. Generate/rebuild official annual results only as a separate authorized operational action.

**Migration inventory**

| **Group** | **Directory** | **Included migration basenames** |
|---|---|---|
| Equity and shared foundation | `database/migrations/cwis/equity/` | 2026_10_06_000001_create_cwis_equity_indicator_tables.php; 2026_10_08_000001_create_cwis_equity_documents_table.php; 2026_10_08_000002_create_cwis_indicator_country_table.php; 2026_10_08_000003_add_cwis_country_setting_and_submission_context.php; 2026_10_11_000001_create_cwis_form_fields_table.php; 2026_10_13_000001_install_cwis_equity_calculation_functions.php; 2026_10_13_000002_share_country_checks_across_cwis_indicators.php; 2026_10_27_000003_limit_cwis_result_decimal_places.php; 2026_10_28_000001_install_equity_generation_dispatch.php |
| Revised Safety | `database/migrations/` | 2026_10_27_000001_install_revised_cwis_safety_functions.php; 2026_10_27_000002_configure_cwis_safety_parameters.php; 2026_10_29_000001_align_sf_1e_document_formula.php |
| Sustainability and plant inputs | `database/migrations/` | 2026_10_29_000002_create_cwis_sustainability_draft_tables.php; 2026_10_29_000003_add_sustainability_approval_audit.php; 2026_10_29_000004_create_treatment_plant_annual_sustainability.php; 2026_10_29_000005_install_cwis_sustainability_calculations.php |
| Responsibility | `database/migrations/` | 2026_10_30_000001_create_cwis_responsibility_tables.php; 2026_10_30_000002_install_cwis_responsibility_calculations.php |
| Accountability | `database/migrations/` | 2026_10_31_000001_create_cwis_accountability_tables.php; 2026_10_31_000002_install_cwis_accountability_calculations.php |
| RPM | `database/migrations/` | 2026_10_31_000003_create_cwis_rpm_tables.php; 2026_10_31_000004_install_cwis_rpm_calculations.php |
| Safety counts and helper fix | `database/migrations/` | 2026_11_04_000001_install_cwis_safety_count_indicators.php; 2026_11_04_000002_align_cwis_emptying_status_helper.php |

**Example commands when every pending migration is approved**

```bash
php artisan down
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan optimize:clear
php artisan migrate:status
php artisan migrate --force
# Run the reviewed CWIS seeders from section 8.
php artisan config:cache
php artisan view:cache
# Restart relevant PHP/opcache and queue services using the server procedure.
# Reopen only after successful verification and approval.
php artisan up
```

The scoped Docker Compose and direct PHP commands, evidence-folder creation, read-only SQL checks and recovery procedure are provided in **documentations/CWIS_Live_Deployment_Guide.md**. That guide is included in this follow-up documentation changeset.

**QA and acceptance**

| **Check** | **Recorded result / requirement** |
|---|---|
| PHP syntax | 96 changed PHP files passed before the subsequent merge |
| JavaScript workflow tests | 10 passed on 4 November: outcome/year selection and rebuild loader behaviour |
| Nested migration-layout test | 2 tests, 35 assertions passed |
| Whitespace diff check | Passed for the grouped CWIS commit series |
| Full database regression | Started but stopped before completion; not a full pass |
| Focused follow-up PHP tests | 27 tests, 265 assertions passed: Safety counts/helper compatibility, shared listing and evidence validation |
| Breakdown | Safety 9/37; shared listing 2/42; evidence validation 16/186 (tests/assertions) |
| Full post-merge acceptance | Not completed; focused checks are not a substitute for the full release suite |
| Staging acceptance | Required before production approval |
| Production smoke checks | Pending actual deployment |

Acceptance includes draft/edit/submit/approve transitions, missing evidence, retained uploads, wrong-country rejection, role permissions, cross-year preservation, new-year generation, existing-year no-overwrite, rebuild isolation, three-decimal outputs and dashboard/Excel/PDF consistency. Database tests must run against an isolated staging/test database, not live; the repository test configuration can inherit the application's DB connection.

**Rollback and recovery**

Keep the site in maintenance on failure and record the last completed step. Do not assume a blanket migrate:rollback fully reverses this release: some calculation migrations intentionally retain routines and audit tables, and others remove dependent objects. Recover through a tested, coordinated restore of the predeployment database/storage and prior code, or an approved compatible forward-fix migration. Restoring a backup can discard later writes; reconcile them before reopening.

#### **12. Known Issues Limitations and Risks**

- Registered coverage is 142 against the latest supplied 159 target. Identify the remaining 17 specifications before claiming complete global coverage.
- SF-0 country assignments depend on the existing SF-1a mappings. Empty inherited mappings must be resolved through an approved assignment, not an invented national requirement.
- Missing private-toilet household/population values can produce No data for SF-0b/SF-0c even when SF-0a has a valid building count.
- Current country examples are development assignments, not approved national requirements. Validate mappings before production use.
- The approval permission requires the deployment check in section 8, particularly for non-Super-Admin roles.
- RS-1ba.8 in the supplied Responsibility specification is normalized as RS-1b.8. Accountability's duplicated reporting code is normalized as AC-1b.2. Retain an approved source-to-code mapping.
- Responsibility, Accountability and RPM answers depend on evidence review; file existence does not prove evidence substance.
- Switching country does not transform existing submissions/results or create a separate independent result dataset for every country in the same year.
- Existing saved results are not updated automatically by code/settings changes.
- Several reverse migrations are intentionally incomplete; a tested restore/forward-fix plan is required.
- The full regression suite and subsequent upstream merge still require QA acceptance.
- Complete all-outcome user-manual/technical/data-dictionary verification before final release sign-off; the documentation commit specifically records the Equity updates and relocated paths.

#### **13. Documentation and Release References**

| **Field** | **Reference / Status** |
|---|---|
| User manual / workflow | Final all-outcome UM revision reference to be confirmed |
| Technical documentation | `documentations/code_documents/07 - CWIS IMS.md` |
| Data dictionary | `documentations/data_dictionary/data_dictionary.md` |
| Deployment guide | `documentations/CWIS_Live_Deployment_Guide.md` - separately prepared local document |
| D2.3 implementation report | Separate handover document; update its older coverage snapshot before final submission; not part of this source release |
| One-page information sheet | `One_Page_Information_v2.0.6_CWIS.md` beside this file |
| External API / Swagger | Not applicable to this web-workflow release |
| Pull request | Not supplied |
| Release comparison | Review reference commit 6e057cc1 through the approved final candidate |
| Release tag | Pending approval; use an immutable tag for publication |
| Current code candidate | 917cc22a; lock the final release SHA/tag after the documentation commit and approval |

#### **14. Final Sign-Off**

| **Role** | **Name** | **Approval / Signature** | **Date** |
|---|---|---|---|
| Prepared by | To be entered | | |
| Technical Lead | To be entered | | |
| QA Lead | To be entered | | |
| Project Manager / Product Owner | To be entered | | |

Implementation completion is recorded separately from release approval. Signatures, release date, PR and tag must not be inferred from the existence of local commits.

## **Final Review Checklist**

- [x] CWIS outcome scope and current candidate commit recorded.
- [x] User-facing changes and principal repository paths recorded.
- [x] Migration/seeder paths and country/settings requirements described.
- [x] QA evidence distinguished from incomplete verification.
- [x] Rollback limitations and known risks disclosed.
- [ ] Approve final country/code mappings and permission registration.
- [ ] Complete post-merge regression and isolated staging acceptance.
- [ ] Confirm full documentation revisions, PR, immutable tag and release date.
- [ ] Record technical, QA and PM sign-off.
- [ ] Approve and execute production deployment.

These notes follow the supplied original full-release-note and information-sheet structure. Production deployment, tagging and publication require separate approval.

