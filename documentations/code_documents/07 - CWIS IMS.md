Version: V1.0.0

# CWIS IMS

## CWIS Dashboard

The CWIS dashboard incorporates charts (using Chart.js) and cards (using HTML and CSS) to visualize data. This section delves into the implementation of the doughnut chart.

### Data Retrieval

- Data is fetched from the CwisNewDashboardController (located at app\\Http\\Controllers\\Cwis\\CwisNewDashboardController.php).
- The getall() function retrieves data using the following steps:

1. Queries the data_cwis table (using the Eloquent ORM).
2. Filters records based on year and indicator_code.
3. Selects specific columns, applying formatting to heading.
4. Stores the retrieved records in the variable.

### Views

- View code resides at resources\\views\\cwis\\cwis-dashboard\\chart-layout\\cwis-dash-layout.blade.php.
- It performs the following actions:

1. Fetches data passed from the controller.
2. Uses a \<div\> with a canvas element to display the chart.
3. Calls the createDoughnutChart() function upon DOM content loading.

### JavaScript Function Breakdown

- The createDoughnutChart() function generates the doughnut chart with these parameters:
- percent: The percentage value to display.
- color: The color of the chart.
- canvasId: The ID of the canvas element.
- containerId: The ID of the container element.
- It performs the following actions:

1. Retrieves canvas and container elements from the DOM.
2. Caps percentValue at 100 if it exceeds 100.
3. Creates a div to display the percentage value.
4. Constructs a Chart.js doughnut chart instance:

- Sets chart type to doughnut.
- Specifies data, background color, 0 border width, 78% cutout, and disabled tooltips.

1. Sets animation duration to 1400 milliseconds.
2. Appends the percentage value div to the container.

Further generation of the formula has been mentioned in “CWIS Generator” below.

## CWIS Generator

### Tables

The CWIS module uses the following table:

- data_source: stores with information related indicators, its data types and more.
- data_cwis: stores yearly information related indicators and its value generated from the system or user input.

The corresponding tables have their respective models that are named in Pascal Case in singular form. The desludging vehicles modules are located at app\\Models\\Cwis\\cwis_mne.php.

### Views

All views used by this module is stored in resources\\views\\cwis\\cwis-df-mne

- cwis-df-mne.create: opens form and calls partial-form for form content.
- cwis-df-mne.index: lists existing records for cwis indicators of each year.
- cwis-df-mne.partial-form: creates form content for generation of new cwis indicator data for new year or edit existing cwis indicator value.

### Models

Location: app\\Models\\Cwis\\cwis_mne.php

### Data Retrieval

- Controller: The CwisMneController (located at app\\Http\\Controllers\\Cwis\\CwisMneController.php) initiates contains all the functions that are being called.
- Exports: App\\Exports\\MneCsvExport handles all the exports process of CWIS data.

CwisMneController follow CRUD operations, which usually have the same pattern. You can refer to the "Basic CRUD" section above (2.2) for more information. Exceptional classes are as follows:

| **Function**    | cwis()                                                                                                                                                                  |
| --------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the postgresql function insert_data_into_cwis_table(\$year) that handles the insert or generation process of new cwis data                                        |
| **Parameters**  | \$year                                                                                                                                                                  |
| **Return**      | json(\$result)                                                                                                                                                          |
| **Source**      | app\\Http\\Controllers\\Cwis\\CwisMneController.php                                                                                                                     |
| **Remarks**     | The new data is generated for new selected year with the reference from data_source table and the data value are calculated from the indicators definition and formula. |

| **Function**    | exportMneCsv()                                                                                         |
| --------------------- | ------------------------------------------------------------------------------------------------------ |
| **Description** | Filters data for selected year and calls the export class that handles the export process of cwis data |
| **Parameters**  | Request\$request                                                                                       |
| **Return**      | Downloaded File.                                                                                       |
| **Source**      | app\\Http\\Controllers\\Cwis\\CwisMneController.php                                                    |
| **Remarks**     | App\\Exports\\MneCsvExport                                                                             |

### Data Calculation
The classic CWIS baseline contained 22 indicators, with individual database functions calculating annual values from operational IMIS data. Shared helper functions classify sanitation systems for the indicator calculations. The indicator catalogue is stored in cwis.data_source, and calculated results are stored in cwis.data_cwis. The upgraded generation chain retains public.insert_data_into_cwis_table(year) within the outcome-aware wrapper functions and adds the new indicator calculations described below.


### Equity Indicator Data

The Equity extension retains EQ-1 and adds EQ-2 to EQ-6.5. EQ-1 and EQ-2 use operational IMIS data. The separate Indicator Data form supplies annual subsidy, workforce, leadership, salary and worker-policy inputs for EQ-3 onward. These are source values, not calculated results in `cwis.data_cwis`.

#### Tables

- cwis.equity_submissions: reporting year, country, draft/submitted/approved status and user/date audit.
- cwis.equity_subsidies: annual NSS and SS subsidy totals for a submission.
- cwis.sanitation_personnel_snapshot: employee/leadership counts and annual average salaries.
- cwis.sanitation_worker_policy: nullable policy answers and evidence-document references.
- cwis.equity_documents: uploaded-file disk, path, original filename and uploader metadata.
- cwis.form_fields: database-driven field definitions linked to cwis.data_source; not submitted answers.
- cwis.indicator_country: indicator-to-country applicability, linked through data_source_id.
- cwis.generation_runs: saved annual country context and approved Equity source reference.

#### Data Entry and Approval

Controller: app\\Http\\Controllers\\Cwis\\CwisGeneratorDataController.php. Request validation: app\\Http\\Requests\\Cwis\\CwisIndicatorDataRequest.php. CwisFormService resolves enabled field definitions for the selected country. CwisEquityWorkflowService checks the approved source before calculation. Views are stored in resources\\views\\cwis\\generator-data.

Save as Draft permits incomplete source data while validating supplied values. Submit for Approval validates applicable required fields but does not approve or calculate results. Review allows corrections before approval. Counts are non-negative whole values and cannot exceed their corresponding totals; monetary inputs follow their field precision and bounds. Evidence inputs are enabled for applicable Yes answers, and saved documents are reused when no replacement is supplied. Parent No answers make the defined dependent Equity questions not applicable. Approved sources are read-only.

#### Calculation Rules

| **Indicator** | **Input fields or source** | **Calculation** |
| ------------- | -------------------------- | --------------- |
| EQ-2 | building_info.buildings.population_served, building_info.buildings.lic_id and the derived sanitation classification; cwis.eq_2_population_totals(year) aggregates safe LIC, total LIC, safe city and total city population | (a/b)/(c/d); the safe-system result is derived, not a stored building flag |
| EQ-3 | cwis.equity_subsidies.total_subsidies_amount_nss; cwis.equity_subsidies.total_subsidies_amount_ss | NSS total / SS total |
| EQ-4 | cwis.sanitation_personnel_snapshot.women_employee_count; cwis.sanitation_personnel_snapshot.total_employee_count | women / total employees * 100 |
| EQ-4a | cwis.sanitation_personnel_snapshot.women_leadership_count; cwis.sanitation_personnel_snapshot.total_leadership_count | women leaders / total leadership positions * 100 |
| EQ-5 | cwis.sanitation_personnel_snapshot.average_salary_of_women_in_sanitation; cwis.sanitation_personnel_snapshot.average_salary_of_men_in_sanitation | (men's average - women's average) / men's average |
| EQ-6.x | cwis.sanitation_worker_policy policy answers and corresponding document IDs referencing cwis.equity_documents.id | Evidence-backed Yes, explicit No, No data, or Not applicable for defined dependent questions |

The schema-level calculators are named cwis.calculate_eq_*; public.update_data_into_cwis_table_eq_*_newsan(year) writes their results. public.generate_cwis_with_equity(year) records the country/source context, calls the existing insert/master flow and dispatches the new Equity functions. The integrated application uses the outer RPM, Accountability, Responsibility and Sustainability wrappers before this Equity generator; a database-only commit is not the complete application release.

Approval does not generate annual results. Generation checks the approved source for the same year/country. Ordinary generation does not overwrite an existing year. The explicit combined Equity rebuild validates applicable manual-source outcomes, backs up saved results/context and regenerates transactionally. The dashboard and exports read saved results, not input previews. Missing or zero denominators return No data rather than a fabricated zero. Finite numerical values are displayed to three decimals; the result trigger also rounds EQ/SF numeric text on insert/update.

#### Country Context

The active site-wide country is stored in public.site_settings.value where name = active_country and category = cwis_setting. Values are ALL, NP and BD. cwis.indicator_country stores NP/BD assignments; ALL does not require mapping rows. Source submissions retain their own country_code and reporting_year. cwis.data_cwis has no country column: cwis.generation_runs records the saved context for each result year. Switching country changes applicability and visibility, not the identity of previously saved sources or results.

#### Database File Organization and Installation

The nine Equity/shared-foundation migrations are under database/migrations/cwis/equity. AppServiceProvider registers this directory with loadMigrationsFrom, so ordinary php artisan migrate and migrate:status discover it together with the root migrations in timestamp order. Migration basenames are unchanged; moving an already-applied migration does not make it pending again. Other outcomes retain their own migrations.

Canonical SQL is under database/sql/cwis/equity: cwis_equity_calculations.sql, cwis_equity_generation.sql, cwis_indicator_country_checks.sql and cwis_original_equity_submission_guard.sql. The last file supports rollback of the shared-country guard. Migration SQL paths use the new directory.

The seeders use the PSR-4 namespace Database\\Seeders\\Cwis\\Equity and are stored in database/seeders/Cwis/Equity. DatabaseSeeder calls the relocated catalogue seeder. After the required migrations, the explicit seeding commands are:

```powershell
php artisan migrate:status
php artisan migrate
php artisan db:seed --class="Database\Seeders\Cwis\Equity\CwisDataSourceSeeder"
php artisan db:seed --class="Database\Seeders\Cwis\Equity\CwisFormFieldsSeeder"
```

Review every pending migration before using the default migrate command. To run only this directory, use --path=database/migrations/cwis/equity; that explicit path excludes other outcome migrations. Existing IMIS schemas and legacy calculation helpers are prerequisites. This reorganization does not modify migration-history rows, delete submissions or regenerate results. Editing an SQL file later does not rerun an already-applied migration; subsequent function changes require an update migration. Namespaced seeder class names must be used in deployment scripts.

### Indicator Generation Data Listing

The shared listing is titled CWIS Indicator Generation Data. Its displayed columns are Year, Service Outcome, Status, Updated At and Actions. Submission IDs remain internal identifiers used for source relationships and View/Approve routes; they are not shown as a shared sequence because separate outcome tables may contain the same ID. The default DataTables order is year descending, followed by updated_at descending.

### Evidence Upload Validation

All five evidence-upload forms accept PDF, DOC and DOCX only, up to 5 MB per new or replacement file (Laravel max:5120 KB). App\\Support\\CwisEvidence provides the shared upload rules and file-picker accept types. Request validation enforces the restriction for draft, submitted and approval-review uploads; file-picker filtering alone is not a security check. Existing saved attachments are retained and reused when no replacement is supplied. Configure PHP and web-server limits for the complete multipart request, including multiple files.

### Safely Managed Sanitation Counts

SF-0a, SF-0b and SF-0c are operational Safety counts, not additional manual form inputs. They use cwis.safety_buildings(year), which filters eligible non-deleted buildings by construction year and returns one row per building after aggregating the existing service-chain classification.

| **Indicator** | **Source fields** | **Calculation** |
| ------------- | ----------------- | --------------- |
| SF-0a | building_info.buildings.bin and the derived safe classification | Count distinct safely managed buildings |
| SF-0b | building_info.buildings.household_with_private_toilet | Sum private-toilet households in safely managed buildings |
| SF-0c | building_info.buildings.population_with_private_toilet | Sum private-toilet population in safely managed buildings |

Each has a cwis.calculate_sf_0* function and public.update_data_into_cwis_table_sf_0*_newsan(year) updater. The new 2026_11_04_000001_install_cwis_safety_count_indicators.php migration loads database/sql/cwis_safety_counts.sql, saves the old master definition, and adds the three updater calls through cwis.dispatch_safety_counts(year). It registers catalogue entries and inherits current SF-1a country mappings. It does not regenerate saved results.

The All and Safety dashboard views show the mapped counts as three green number cards, not doughnuts. PDF includes saved count cards; catalogue-driven monitoring and Excel include the new entries after generation. Unknown/negative private totals return No data; an empty eligible set returns zero. The catalogue becomes 142 entries, including 25 Safety indicators. The full stated 159 target still needs 17 additional specifications.

The subsequent 2026_11_04_000002_align_cwis_emptying_status_helper.php migration backs up and repairs the legacy classification helper when fsm.applications.emptying_status is an integer. It uses IN (1, 2) rather than IS TRUE and preserves the helper's boolean result column without altering the source table or application records.

## KPI Dashboard

The KPI dashboard incorporates charts (using Chart.js) and cards (using HTML and CSS) to visualize key performance indicator data. This section delves into the implementation of the bar chart.

The generation of these elements occurs only upon setting the respective targets for the indicators within the kpi_targets table, as detailed in Section 7.4.

### Tables

KPI Dashboard is under FSM module and uses the following table:

- kpi_target: retrieves the information of kpi targets.
- service_providers: retrieves information of service providers.
- quarters: retrieves information of quarters for each year.

### Data Retrieval

- Data is fetched from the KpiDashboardController (located at app\\Http\\Controllers\\Fsm\\KpiDashboardController.php).
- A dedicated service class, located at app\\Services\\Fsm\\KpiDashboardService.php, exclusively contains the logic designed specifically for generating charts.
- Laravel Eloquent and raw SQL queries retrieves data for cards.
- Here, each indicator is calculated based on a derived formula, which is further explained as

  - Application Response Efficiency: A ratio of total containments emptied/total application received in the given time period.
  - Customer Satisfaction: A ratio of feedback with the “FSM Service Quality” field marked as “Yes”/total number of submitted feedback.
  - PPE compliance: A ratio of feedback with the “Sanitation workers wore PPE during desludging” field marked as “Yes”/total number of submitted feedback.
- Safe Desludging: A ratio of the total number of desludging recorded in treatment plant/ total number of containments emptied. (Assumption: 1 desludge is 1 containment)
- Inclusion: delivered services in LICs and Slums / total delivered services.
- Response Time: Average time between application and delivered service.
- Faecal Sludge Collection Ratio: Total amount of faecal sludge emptied from containments/ Total estimated sludge to be emptied in accessible areas.
- The filtering system allows for selecting a specific year and service provider. Upon choosing a year, cards are generated based on the selected year, and charts are generated by further breaking it down into four quarters: Q1, Q2, Q3, and Q4. Detailed information for these quarters can be obtained from the quarters table.
- Clear code comments within the controller aid in understanding this process.

### Views

**Layout:** The core dashboard structure is defined in the resources\\views\\fsm\\kpi-dashboard\\index.blade.php file. This file acts as the overall layout and includes placeholders for the various components. It fetches data passed from the controller.

**Components:** Cards and charts are incorporated as separate components within the layout:

- Cards are located in resources\\views\\layouts\\dashborad\\key-performance-indicator-card.
- Charts are located in resources\\views\\fsm\\kpi-dashboard\\KpiCharts.

## KPI Target

### Tables

KPI Target is under CWIS IMS module and uses the following table:

kpi_targets: store the primary information of the KPI targets

key_performance_indicators: retrieves information of key performance indicators

The corresponding tables have their respective models that are named in Pascal Case in singular form. KpiTarget model is located at app\\Models\\Fsm\\.

### Views

All views used by this module is stored in resources\\views\\fsm\\kpi-target

kpi-target.index: lists KPI targets records.

kpi-target.create: opens form and calls partial-form for form contents

kpi-target.partial-form: creates form content

kpi-target.edit: opens form and calls partial-form for form contents

kpi-target.history: lists all past edits of the record

kpi-target.show: displays all attributes of particular record

### Models

The models contain the connection between the model and the table defined by

*\$table = ‘fsm.kpi_targets’* as well as the primary key defined by primaryKey= ‘id’

### KpiTarget Model

Location: app\\Models\\Fsm\\kpiTarget.php

KpiTarget follows CRUD operations, which usually have the same pattern. You can refer to the "Basic CRUD" Section **2 - Technical Information/ Basic CRUD** for more information.

### KpiTargetRequest

Location: app\\Http\\Requests\\Fsm\\KpiTargetRequest.php)

KpiTargetRequest handles all validation login. It handles validation logic as well as error messages to be displayed.

## CWIS Setting

### Tables

CWIS Setting is under Settings module and uses the following table:

public.site_settings

The corresponding tables have their respective models that are named in Pascal Case in singular form. CwisSetting model is located at app\\Models\\Fsm\\.

The CWIS setting sets the values for variables that are required for the CWIS Dashboard.

### Views

All views used by this module is stored in resources\\views\\fsm\\cwis-setting

cwis-setting.index: opens cwis setting form.

### CwisSettingController

app\\Http\\Controllers\\Fsm\\CwisSettingController.php

The controller’s main function is to provide the connection between the calling route and its subsequent function written in the Service Class.

The basic classes of the controller are:

|  **Function**   | \__construct()                                                         |
|-----------------|------------------------------------------------------------------------|
| **Description** | Initializes authentication, permissions and the service class instance |
| **Parameters**  | Service class instance(CwisSettingService)                             |
| **Return**      | null                                                                   |
| **Source**      | app\\Http\\Controllers\\Fsm\\CwisSettingController.php                 |

| **Function**    | index()                                                 |
|-----------------|---------------------------------------------------------|
| **Description** | Returns the index.blade.php page                        |
| **Parameters**  | null                                                    |
| **Return**      | fsm/cwis-setting.index compact('page_title', 'data')    |
| **Source**      | app\\Http\\Controllers\\Fsm\\CwisSettingController.php  |

| **Function**    | store()                                                         |
|-----------------|-----------------------------------------------------------------|
| **Description** | Store or update cwis setting data                               |
| **Parameters**  | Request \$request                                               |
| **Return**      | Redirection with success/failure message                        |
| **Source**      | app\\Http\\Controllers\\Fsm\\CwisSettingController.php          |
| **Remarks**     | cwissetting-\>storeOrUpdate(\$data) Service Class Function Name |

### CwisSettingService

Location: app\\Services\\Fsm\\CwisSettingService.php

The Service Class contains all the business logic. It contains all the functions that are being called in the CwisSettingController.php

|  **Function**   | storeOrUpdate()                                                |
|-----------------|----------------------------------------------------------------|
| **Description** | Handles the process of edditing cwis settings.                 |
| **Parameters**  | \$data                                                         |
| **Return**      | Success or error message, stores/updates data to cwis settings |
| **Source**      | app\\Services\\Fsm\\CwisSettingService.php                     |
