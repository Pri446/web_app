Version: V1.1.0  
Application: Base IMIS 1.3.0 NSD  
Document type: Code and workflow reference  
Source reviewed: 2026-09-17

# Fecal Sludge IMS — Service Delivery

This document follows the submodule and code-component layout used by `05 - Fecal Sludge IMS.md`. It covers the service chain from provider setup and scheduled desludging through application, emptying, sludge collection, and feedback. Field definitions belong in the [existing data dictionary](../data_dictionary/data_dictionary.md).

## Service Delivery Chain

The intended business flow is:

```text
Provider and ward setup
        ↓
Generate priority, dates, and temporary schedule
        ↓
Customer agrees ───────────────→ Application
        │                              ↓
        └─ Disagrees → Reintegration → Application
                                       ↓
                              Emptying service
                                       ↓
                              Sludge collection
                                       ↓
                              Customer feedback
```

Manual applications also enter at **Application**. The schedule is a regenerated working queue, not an application or a permanent service record. There is no supervisory-assessment stage in the intended workflow for this application. A current code dependency that can block scheduled confirmation is documented under [Implementation considerations](#implementation-considerations).

## Shared Code Pattern

The code-document convention is **Tables → Views → Models → Controller → Service → Request → principal functions** for each submodule. Web routes use the `/fsm` prefix and `auth` middleware. Controllers typically coordinate requests and responses; services or models perform persistence and business operations. Several older modules still contain business logic in controllers.

| Component | Location or role |
| --- | --- |
| Web routes | `routes/web.php` |
| Mobile routes | `routes/api.php`, protected by `auth:sanctum` |
| Controllers | `app/Http/Controllers/Fsm` and `app/Http/Controllers/Api` |
| Services | `app/Services/Fsm` |
| Models | `app/Models/Fsm` |
| Form requests | `app/Http/Requests/Fsm` |
| Views | `resources/views/fsm` |
| Database | PostgreSQL `fsm` schema; supporting `building_info`, `layer_info`, `auth`, and `public` data |

## Service Provider IMS

### Service Providers

**Tables:** `fsm.service_providers` stores provider identity, operational status, contract path and legacy `service_area` CSV. `fsm.service_provider_wards` stores one provider/ward pair per row with a unique pair constraint. The latter's provider reference is logical; its migration does not declare a database foreign key.

**Views:** `resources/views/fsm/service-providers/{index,create,edit,show,history,partial-form}.blade.php`.

**Model:** `app/Models/Fsm/ServiceProvider.php`.

**Controller:** `app/Http/Controllers/Fsm/ServiceProviderController.php` handles the resource routes, DataTable data, history and export.

**Service:** `app/Services/Fsm/ServiceProviderService.php`; `storeOrUpdate()` normalizes and synchronizes selected wards to both coverage stores. Optional provider-user creation is initiated by `ServiceProviderController` through `UserService`.

**Request:** `app/Http/Requests/Fsm/ServiceProviderRequest.php` validates identity, contact, service-area wards and the contract PDF. The contract is required on creation, optional on update, PDF-only and limited to 5 MB.

| Function or action | Current behavior |
| --- | --- |
| Create/update provider | Saves provider and synchronized ward coverage. |
| Change to non-operational | Unassigns incomplete applications previously allocated to that provider. |
| Read provider coverage | Scheduling and reintegration accept either normalized ward rows or the compatibility CSV. |

### Employee Information

**Table/model:** `fsm.employees`; `app/Models/Fsm/EmployeeInfo.php`.

**Controller/service/request:** `EmployeeInfoController.php`, `EmployeeInfoService.php`, `EmployeeInfoRequest.php` under their respective `Fsm` directories. They manage provider staff, including drivers and emptiers used by emptying records. Provider-specific mobile queries use the authenticated user's `service_provider_id`.

### Desludging Vehicles

**Table/model:** `fsm.desludging_vehicles`; `app/Models/Fsm/VacutugType.php`.

**Controller/service:** `VacutugTypeController.php` and `VacutugTypeService.php`. Vehicle selection in emptying is linked to the provider and vehicle's operational status. The mobile vacutug endpoint filters by the authenticated provider.

## Treatment Plant IMS

### Treatment Plants

**Table/model:** `fsm.treatment_plants`; `app/Models/Fsm/TreatmentPlant.php`.

**Controller/service/request:** `TreatmentPlantController.php`, `TreatmentPlantService.php`, `TreatmentPlantRequest.php`. Treatment plants are master data for emptying destinations and sludge receipt. The mobile emptying list exposes operational FSTP and co-treatment plants (types 3 and 4). Treatment Plant Admin visibility is filtered by the user's associated plant where the controller applies that rule.

## Emptying Service IMS

The existing application, emptying, sludge-collection and feedback modules remain the permanent service chain. The two scheduled-desludging submodules feed into application creation; they do not replace downstream records.

### Scheduled Desludging

**Tables:** `fsm.containments` supplies status, priority, FSTP distance and dates; `fsm.desludging_schedule_temp` is the regenerated non-PII queue snapshot; `fsm.service_provider_wards` and `fsm.service_providers.service_area` provide ward coverage; `public.site_settings` supplies scheduling settings. Only building BIN, ward and address data is read from `building_info` for this queue. Owner PII is not joined, copied, searched or returned.

**View/model:** `resources/views/fsm/desludging-schedule/index.blade.php`; `app/Models/Fsm/DesludgingSchedule.php`.

**Controller/service:** `app/Http/Controllers/Fsm/DesludgingScheduleController.php` and `app/Services/Fsm/DesludgingScheduleService.php`. The controller exposes index, DataTable, regenerate, accept and disagree actions. The service owns generation, assignment and handoff to the application form.

**Request:** These actions validate their `Request` inputs in the service; there is no separate schedule FormRequest in the repository.

| Function | Current behavior |
| --- | --- |
| `regenerate()` | Runs all generation steps inside one database transaction with a PostgreSQL advisory transaction lock; concurrent generation returns HTTP 409. |
| `setPriority()` | Recalculates priority from a valid last-emptying date or construction date; no usable date has priority 1. |
| `setEmptyingDate()` | Allocates eligible never-emptied, active containments to working dates under remaining daily trip capacity. |
| `refreshTemporarySchedule()` | Deletes and rebuilds the queue from eligible containments; its membership criteria differ from date allocation. |
| Provider assignment | Assigns uncovered wards to operational providers without coverage, then assigns queue rows by current building ward; unmatched rows remain unassigned. |
| `redirectToApplication()` | Validates selected row values, stores them in the session and opens the application form in confirm mode. |
| `disagree()` | Sets containment status to 4 and removes its queue row in a transaction. |

**Eligibility and ordering:** Priority recalculation considers non-deleted containments with null, 0 or 4 status. Date allocation considers non-deleted containments with `emptied_status = false` and null/0 status; it orders by priority, then FSTP distance, then containment ID. The temporary queue considers null/0/3 status, excludes containments with a non-deleted open application, and sequences rows by FSTP distance, then priority, then containment ID. Thus queue membership and queue sequence must not be described as identical to date allocation.

**Priority bands:** 1 = no usable reference date or older than three years; 2 = older than one year but not older than three; 3 = within one year. Future reference dates are unusable. Exact one- and three-year boundaries follow the service's strict comparisons.

**Capacity:** The service skips configured weekend and holiday dates and subtracts existing confirmed application bookings and automatic bookings from `Trip Capacity Per Day`. It advances until all eligible containments have dates or a safety horizon is exceeded. A non-positive capacity is rejected.

**Provider assignment:** The service preserves existing coverage. It distributes uncovered municipality wards among active providers with no service area; the selection is shuffled and round-robin, so an initial assignment can vary. If multiple active providers cover a queue row's ward, the lowest provider ID wins. An unmatched row remains unassigned.

| `public.site_settings` name (`desludging_schedule` category) | Seeded value |
| --- | --- |
| `Schedule Desludging Start Date` | Empty |
| `Schedule Regeneration Period` | `6` |
| `Trip Capacity Per Day` | `20` |
| `Weekend` | `Friday,Saturday` |
| `Holiday Dates` | Empty |

### Scheduled Desludging Reintegration

**Tables:** `fsm.containments` with status 4, building/owner context, and provider coverage. Reintegration is a view over these records, not a separate reintegration table.

**View:** `resources/views/fsm/desludging-reintegration/index.blade.php`.

**Controller/service:** `DesludgingReintegrationController.php` lists eligible records and calls `DesludgingScheduleService::redirectReintegrationToApplication()` on confirmation. Provider resolution checks the BIN's current building ward against both coverage stores, selects the lowest-ID operational provider and uses a separate advisory lock.

**Handoff:** Confirmation returns to application creation with server-side schedule context. It does not directly restore a deleted temporary queue row. Municipality administrative/help-desk users can view across providers; provider users are filtered to configured wards.

### Application

**Table/model:** `fsm.applications`; `app/Models/Fsm/Application.php`. Each application links BIN, containment, provider and proposed emptying date to later service records.

**Views:** `resources/views/fsm/applications/{index,create,edit,history,application_report,monthly_report}.blade.php`.

**Controller/service/request:** `ApplicationController.php`, `ApplicationService.php`, `ApplicationRequest.php` under their respective `Fsm` directories.

| Function or action | Current behavior |
| --- | --- |
| Manual create | Creates an application without a schedule-row handoff. |
| Scheduled confirm | `ApplicationRequest::prepareForValidation()` locks BIN, containment, road, ward and provider to session values when available. |
| `ApplicationService::createApplication()` | Checks for an existing non-deleted, non-emptied application for the containment before insert. Within a transaction, creates the application, updates building/owner context and, in confirm mode, sets containment status to 1 and deletes queue rows for the containment. |
| Reports | Per-application and monthly PDF routes remain part of the existing module. |

The open-application check is application logic, not a database partial unique index. Scheduled confirmation is **not currently end-to-end clean** because the request and service still reference an out-of-scope assessment-date field; see [Implementation considerations](#implementation-considerations). This document does not add an assessment stage.

### Emptying

**Table/model:** `fsm.emptyings`; `app/Models/Fsm/Emptying.php`.

**Views:** `resources/views/fsm/emptying/{index,create,edit,history,partial-form}.blade.php`.

**Controller/service/request:** `EmptyingController.php`, `EmptyingService.php`, `EmptyingRequest.php`. The web service records the vehicle, crew, destination, sludge volume, trips, time, receipt/cost and evidence. It sets the application's `emptying_status`, updates containment last/next emptying dates (next date = emptied date plus three years), emptied status and emptying count. The web request checks volume against containment size and requires end time after start time.

**Mobile API:** `app/Http/Controllers/Api/EmptyingServiceController.php` uses `EmptyingApiRequest.php` under Sanctum. The API accepts multipart emptying data and JPEG receipt/optional house evidence. Provider-specific vehicle and employee lists are filtered by the authenticated provider. API and web validation are not identical; test both paths.

### Sludge Collection

**Table/model:** `fsm.sludge_collections`; `app/Models/Fsm/SludgeCollection.php`.

**Views:** `resources/views/fsm/sludge-collection/{index,create,edit,show,history,partial-form}.blade.php`.

**Controller/request:** `SludgeCollectionController.php` and `SludgeCollectionRequest.php`. The record links an application to the receiving plant and records delivery volume, date, trips, vehicle and entry/exit times. On store, the controller sets `applications.sludge_collection_status = true`. Date/trip/time validation and plant-specific visibility are applied in this module.

### Feedback

**Table/model:** `fsm.feedbacks`; `app/Models/Fsm/Feedback.php`.

**Views:** `resources/views/fsm/feedbacks/{index,create,edit,show}.blade.php`.

**Controller/request:** `FeedbackController.php` and `FeedbackRequest.php`. Feedback records service quality, PPE use and comments against an application/provider; store sets `applications.feedback_status = true`. Provider rating display uses recent feedback. There is no separate feedback service class in this repository.

### Help Desk

**Table/model:** `fsm.help_desks`; `app/Models/Fsm/HelpDesk.php`.

**Controller/service/request:** `HelpDeskController.php`, `HelpDeskService.php`, `HelpDeskRequest.php`. This is supporting master data for the service workflow, not an additional service stage.

## Routes and Permissions

| Action | Route | Enforced schedule permission |
| --- | --- | --- |
| List schedule / data | `GET /fsm/desludging-schedule`, `/data` | `List Schedule Desludging` |
| Regenerate | `POST /fsm/desludging-schedule/regenerate` | `Regenerate Schedule Desludging` |
| Begin confirmation | `POST /fsm/desludging-schedule/accept` | `Confirm Schedule Desludging` |
| Disagree | `POST /fsm/desludging-schedule/{bin}/disagree` | `Delete Schedule Desludging` |
| List reintegration / data | `GET /fsm/desludging-reintegration`, `/data` | `List Schedule Reintegration` |
| Confirm reintegration | `POST /fsm/desludging-reintegration/confirm` | `Confirm Schedule Reintegration` |

Other resource routes remain in `routes/web.php`: `/fsm/service-providers`, `/fsm/employee-infos`, `/fsm/desludging-vehicles`, `/fsm/treatment-plants`, `/fsm/application`, `/fsm/emptying`, `/fsm/sludge-collection`, `/fsm/feedback`, and `/fsm/help-desks`. Some permissions exist in the seeder without a corresponding action in these controllers; the table above lists enforcement observed in the code, not every seeded permission. Direct-request authorization should be tested, not inferred from hidden UI buttons.

## Mobile API

The following endpoints are declared in `routes/api.php` under `/api` and `auth:sanctum`. Their current names are retained here for client compatibility; endpoint naming does not add a supervisory-assessment stage to this workflow.

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/application/{id}` | Application details and building context. |
| GET | `/api/containment/{application_id}` | Containment linked to an application. |
| GET | `/api/service-providers` | Operational provider list. |
| GET | `/api/assessed-applications` | Legacy-named list used by the emptying client for work awaiting emptying. |
| GET | `/api/treatment-plants` | Operational FSTP and co-treatment facilities. |
| GET | `/api/vacutugs`, `/api/drivers`, `/api/emptiers` | Provider-filtered vehicle and staff choices. |
| POST | `/api/save-emptying` | Validate and save emptying and image evidence. |
| POST | `/api/logout` | End the authenticated API session. |

The save-emptying request includes application ID, sludge volume, vehicle, plant, driver, at least one emptier, trips, receipt/cost, start/end times, service-receiver details, a required JPEG receipt and an optional JPEG house image. The endpoint's response bodies use both `success` and `status` conventions in current code; callers should treat HTTP status as authoritative.

## Validation and Business Rules

| Area | Implemented rule |
| --- | --- |
| Provider | Company name and relevant user email uniqueness; at least one service-area ward; PDF contract at most 5 MB. |
| Application | Proposed date is required and checked against the current date; application service rejects another open application for the containment. |
| Web emptying | At least one trip, non-negative total cost, end time after start time, and volume no greater than containment size. |
| Mobile emptying | Separate `EmptyingApiRequest` rules, including a required JPEG receipt and positive total cost. |
| Sludge collection | Date at or after today, at least one trip, and exit time after entry time. |
| Feedback | Service-quality and PPE responses are required. |

Laravel FormRequest validation failures follow normal web/API response behavior. Schedule generation reports concurrent execution with HTTP 409 and unexpected errors with HTTP 500. Errors are reported through Laravel logging; production configuration should keep `APP_DEBUG=false`.

## Storage, Transactions and Reporting

- Provider contracts use the Laravel `public` disk under `contract_documents/`; on update, the controller stores the new file and deletes the old file before the provider database update completes.
- Emptying evidence uses `public/emptyings/receipts/` and `public/emptyings/houses/`; Intervention Image handles saving and mobile-path resizing/compression.
- Schedule regeneration is one transaction with an advisory transaction lock. Disagreement and application creation also use transactions. Database rollback does not automatically remove previously written files.
- Supported models use soft deletion and revision history where their model declares those traits. DataTables and export/report endpoints are part of existing controllers; do not infer identical behavior for every submodule.

## Technology and Deployment

The repository specifies PHP `^8.1` and Laravel `^8.75` in `composer.json`. The implementation uses PostgreSQL/PostGIS, Laravel web sessions and Sanctum, Spatie permissions, Blade, Yajra DataTables and Laravel storage. Source code also uses Intervention Image for emptying evidence and PDF/CSV libraries for reports and exports.

Before deployment, back up the database and uploaded files; then deploy dependencies/assets, run the repository migrations, confirm the five scheduling settings in `public.site_settings`, rebuild application caches as appropriate, and verify the public storage link and write permissions. Do not run migrations against a target environment without its backup and rollback procedure. This document records the source-code state and does not itself execute deployment commands.

## Verification Checklist

1. Create a provider with contract and wards; verify CSV and normalized coverage and non-operational reassignment.
2. Test priority boundaries, future dates, daily capacity, weekends/holidays, concurrent regeneration, unassigned wards and overlapping provider coverage.
3. Verify disagreement removes a queue row, status 4 appears in reintegration, and reintegration resolves a provider by current building ward.
4. Test manual application and scheduled confirmation separately; verify the known confirmation dependency below before claiming completion.
5. Record web and mobile emptying, then sludge collection and feedback; verify application flags and containment dates.
6. Test direct-route permission enforcement for municipality, provider and treatment-plant users.

## Implementation Considerations

1. **Scheduled-confirmation blocker:** Current `ApplicationRequest.php` requires `supervisory_assessment_date` when `action_type=confirm`, and `ApplicationService.php` reads that value. This is an out-of-scope code dependency, not a supported supervisory-assessment stage for this application. Remove or otherwise resolve the dependency in code and tests before describing scheduled confirmation as fully working; this document does not add a field, role, permission or workflow step for it.
2. **Different scheduling populations:** Date allocation only updates never-emptied active containments, while the queue also includes status 3 and can include previously emptied containments. Keep this distinction visible in UI/QA expectations.
3. **Temporary IDs:** Regeneration deletes and recreates queue rows. Their IDs are not stable references.
4. **Dual provider coverage:** The CSV and normalized ward table must stay synchronized while both are read by schedule and reintegration queries.
5. **Concurrency:** The open-application uniqueness check has no database-level partial unique index. Concurrent submissions warrant testing.
6. **API response shape and storage:** Mobile responses use more than one boolean-key convention; file writes are not rolled back with database transactions.

## Source Traceability

This revision was compared with `documentations/code_documents/05 - Fecal Sludge IMS.md`, the prior FSM technical draft, `routes/web.php`, `routes/api.php`, the FSM controllers/services/requests/models named above, scheduling migrations from August 2026, and `database/seeders/PermissionsSeeder.php`. It is a source-code review, not a claim that deployment migrations or end-to-end tests were run.
