- Version: V1.1.0
- FSM scheduling and service-provider sections reviewed: 2026-10-13
- Source: `v2.0.0-desludging-schedule-clean` (`50927a82`)

# Fecal Sludge IMS

The scheduling and provider updates in this revision cover the clean v2.0.0 release. Later ANF, multi-trip, sludge-collection-log, Places and expanded API features belong to v2.0.1 and are not included here. The detailed reference is [FSM Service Delivery](21%20-%20FSM_SERVICE_DELIVERY_TECHNICAL_DOCUMENT.md). Older dashboard, containment and downstream-service sections below retain their existing baseline descriptions.

## UML Diagram

Service Delivery Chain DFD Level 1

![](media/5-fecal-sludge-service-delivery-dfd.png)

Service Delivery Chain

![](media/5-fecal-sludge-service-delivery-dfd-2.png)

## FSM Dashboard

The dashboard utilizes the following tools to deliver data and insights:

-   Charts: Created with Chart.js for visual representation of trends.
-   Cards: Built with Bootstrap, HTML, and CSS.
-   Icons: SVG and Font Awesome.

### Data Retrieval

-   Controller: The FsmDashboardController (located at app\\Http\\Controllers\\Fsm) initiates data fetching. Clear code comments within the controller aid in understanding this process.
-   Service Class: The FsmDashboardService (located at app\\Services\\Fsm) has been called by controller to perform necessary operations.

Count Boxes

-   Data Fetching: Laravel Eloquent retrieves data for count boxes (potentially using database queries.
-   Filtering: Specific count boxes can be filtered by year, enabling users to narrow down the displayed data.

Charts

-   Data Fetching: Laravel Eloquent & Raw SQL queries are used to fetch data for charts.
    1.  Views

Layout

The core dashboard structure is defined in the resources\\views\\dashboard\\fsmDashboard.blade.php file. This file acts as the overall layout and likely includes placeholders for the various components.

Components

Count boxes and charts are incorporated as separate components within the layout:

Count boxes are fetched from resources\\views\\dashboard\\countBox.

Charts are fetched from resources\\views\\dashboard\\charts.

## Containment IMS

The containment module encompasses one major sub modules:

-   Containment

### Tables

The Containment Sub Module uses the following tables:

-   containments: stores primary containment information. The internal identifier remains id, while the unique public_id UUID is used for browser URLs and route-model binding.
-   Build_contains: relational database that connects buildings and containments. Foreign Key: bin and containment_id.
-   Sanitation_system_types: stores sanitation system types attributes used as dropdowns

The corresponding tables have their respective models that are named in Pascal Case in singluar form. The containment info modules are located at app\\Http\\Models\\Fsm.

### Views

All views used by this module is stored in resources\\views\\building-info

-   containments.create: opens form and calls partial-form for form content
-   containments.edit: opens form and calls partial-form for form contents
-   containments.history: lists all past edits of the record
-   containments.index: lists containments records
-   containment.list-containments: list all containments connected to building
-   containments.listBuilding: lists all buildings connected to that containment.
-   containments.partial-form: creates form content for addition of new containment or edit containment of existing building
-   containments.show: displays all attributes of particular record
-   containments.type-change: displays the history of type attribute of containment

The models contain the connection between the model and the table defined by

*\$table = ‘schema_name.table_name’* as well as the primary key defined by

*primaryKey= ‘primary_key_field’*

For history functionality, the revisionCreationsEnabled is set true and we

-   use RevisionableTrait(use Venturecraft\\Revisionable\\RevisionableTrait;

For soft deletes functionality, we

-   use SoftDeletes;(use Illuminate\\Database\\Eloquent\\SoftDeletes;)

For Setting belongsTo and HasMany relationships, we:

-   use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
-   belongsToMany('model_relative_path', 'relational_table', primary_key, 'primary_key of relationship')
-   belongsTo('model_name', 'pk of first table', 'pk of Second table'
-   use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

### Models

Location: app\\Models\\Fsm\\Containment.php

There are multiple relationships defined in the Containment Model. They are:

buildings: belongsToMany relationship (n to n relationship)

emptyingService: hasMany relationship with applications

The Containment model uses app\\Models\\Concerns\\HasPublicId. This trait generates a UUID public_id for newly created records and makes Laravel resolve Containment route parameters by public_id instead of the internal containment id.

### ContainmentController

Controller: app\\Http\\Controllers\\Fsm\\ContainmentController.php

The controller’s main function is to provide the connection between the calling route and its subsequent function written in the Service Class.

The basic classes of the controller are:

| **Function**    | \__construct()                                                         |
|-----------------|------------------------------------------------------------------------|
| **Description** | Initializes authentication, permissions and the service class instance |
| **Parameters**  | Service class instance(ContainmentService)                             |
| **Return**      | null                                                                   |
| **Source**      | app\\Http\\Controllers\\Fsm\\ContainmentController.php                 |

| **Function**    | index()                                                                      |
|-----------------|------------------------------------------------------------------------------|
| **Description** | Returns the index.blade.php page with dropdown values fetched from database. |
| **Parameters**  | null                                                                         |
| **Return**      | fsm.containments.index compact('page_title')                                 |
| **Source**      | app\\Http\\Controllers\\Fsm\\ContainmentController.php                       |

| **Function**    | createContainment()                                                                                                                                                                              |
|-----------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns the form to create new containment with dropdown values fetched from the database.                                                                                                       |
| **Parameters**  | null                                                                                                                                                                                             |
| **Return**      | ('fsm.containments.create', compact('page_title','id','multiple_bin'))                                                                                                                           |
| **Source**      | app\\Http\\Controllers\\Fsm\\ContainmentController.php                                                                                                                                           |
| **Remarks**     | This function handles the situation where only containment is created by associating it with a building before hand. In situations where an existing building is connected to a new containment. |

| **Function**    | storeContainment()                                                           |
|-----------------|------------------------------------------------------------------------------|
| **Description** | Calls the service class that handles the process of storing containment data |
| **Parameters**  | ContainmentRequest \$request                                                 |
| **Return**      | Success or error message.                                                    |
| **Source**      | app\\Http\\Controllers\\Fsm\\ContainmentController.php                       |
| **Remarks**     | storeContainmentData(\$request) Service Class Function Name                  |

| **Function**    | show()                                                                          |
|-----------------|---------------------------------------------------------------------------------|
| **Description** | Returns the page displaying individual containment data                         |
| **Parameters**  | Containment \$containment resolved from the public_id route parameter           |
| **Return**      | fsm.containments.show compact('page_title', 'containment', 'building')           |
| **Source**      | app\\Http\\Controllers\\Fsm\\ContainmentController.php                         |

| **Function**    | edit()                                                                                  |
|-----------------|-----------------------------------------------------------------------------------------|
| **Description** | Returns the edit form page displaying pre-existing individual containment data          |
| **Parameters**  | Containment \$containment resolved from the public_id route parameter                    |
| **Return**      | fsm.containments.edit compact('page_title', 'containment')                               |
| **Source**      | app\\Http\\Controllers\\Fsm\\ContainmentController.php                                  |

| **Function**    | update()                                                                                         |
|-----------------|--------------------------------------------------------------------------------------------------|
| **Description** | Calls the service class that handles the process of updating containment data                    |
| **Parameters**  | ContainmentRequest \$request, Containment \$containment resolved from public_id                 |
| **Return**      | Success or error message                                                                         |
| **Source**      | app\\Http\\Controllers\\Fsm\\ContainmentController.php                                          |
| **Remarks**     | Uses the bound model's internal id only after public_id route resolution succeeds.                |

| **Function**    | destroy()                                                                          |
|-----------------|------------------------------------------------------------------------------------|
| **Description** | Soft deletes containment information if it is not connected to any buildings       |
| **Parameters**  | Containment \$containment resolved from the public_id route parameter               |
| **Return**      | Redirect with success or error message                                              |
| **Source**      | app\\Http\\Controllers\\Fsm\\ContainmentController.php                             |
| **Remarks**     | The delete form URL contains public_id and does not expose the internal id.          |

| **Function**    | export()                                                                       |
|-----------------|--------------------------------------------------------------------------------|
| **Description** | Calls the service class that handles the process of exporting containment data |
| **Parameters**  |                                                                                |
| **Return**      | CSV file containing containment data                                           |
| **Source**      | app\\Http\\Controllers\\Fsm\\ContainmentController.php                         |
| **Remarks**     | getExport() (service class function)                                           |


| **Function**    | exportBuildingContainment()                                                                       |
|-----------------|--------------------------------------------------------------------------------|
| **Description** | Calls the service class that handles the process of exporting building connected with containment data |
| **Parameters**  |                                                                                |
| **Return**      | CSV file containing containment data                                           |
| **Source**      | app\\Http\\Controllers\\Fsm\\ContainmentController.php                         |
| **Remarks**     | getExportBuildingContainment() (service class function)                                           |

| **Function**    | listBuildings()                                                                                                 |
|-----------------|-----------------------------------------------------------------------------------------------------------------|
| **Description** | Fetches all Buildings connected to the route-bound Containment                                                  |
| **Parameters**  | Containment \$containment resolved from the public_id route parameter                                          |
| **Return**      | fsm.containments.listBuilding compact('page_title', 'containment', 'buildings')                                 |
| **Source**      | app\\Http\\Controllers\\Fsm\\ContainmentController.php                                                        |
| **Remarks**     | The internal containment id can still be displayed as business data, but it is not used in the browser URL.     |

| **Function**    | deleteBuilding()                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
|-----------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Removes a Containment–Building connection while checking active applications and maintaining sewer/drain data integrity.                                                                                                                                                                                                                                                                                                                                                                                            |
| **Parameters**  | Containment \$containment and Building \$building, both resolved from their public_id route parameters                                                                                                                                                                                                                                                                                                                                                                                                              |
| **Return**      | Redirect with success or error message                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| **Source**      | app\\Http\\Controllers\\Fsm\\ContainmentController.php                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| **Remarks**     | Both internal identifiers are hidden from the relationship-delete URL. The action is available from containments.listBuilding and the Building edit form.                                                                                                                                                                                                                                                                                                                                                           |

### ContainmentService

Location: app\\Services\\Fsm\\ContainmentService.php

The Service Class contains all the business logic. It contains all the functions that are being called in the ContainmentController.

ContainmentService uses public_id when generating DataTable action URLs for details, edit, delete, history, type-change history, connected Buildings, maps, emptying history and nearest-road tools. Internal ids remain available for database relationships and displayed business data but are not used as URL identifiers.

| **Function**    | fetchData()                                                                                                                                                                                                                                   |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Handles the process of fetching containment data for data tables                                                                                                                                                                              |
| **Parameters**  | \$request                                                                                                                                                                                                                                     |
| **Return**      | Returns data of containment table for datatables                                                                                                                                                                                              |
| **Source**      | app\\Services\\Fsm\\ContainmentService.php                                                                                                                                                                                                    |
| **Logic**       | Join required tables Check for filter values if any. Filter accordingly.  Filter values are defined in the script of the index page Append actions column that contain buttons such as edit, delete, show, history and so on Return datatable |
| **Remarks**     | This function is called by the datatables displayed in the containment.index page                                                                                                                                                             |

| **Function**    | fetchBuildingContainmentData()                                                                                                                                                                                                                |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Handles the process of fetching containment data for data tables                                                                                                                                                                              |
| **Parameters**  | \$request                                                                                                                                                                                                                                     |
| **Return**      | Returns data of particular containments connected to selected building table for datatables                                                                                                                                                   |
| **Source**      | app\\Services\\Fsm\\ContainmentService.php                                                                                                                                                                                                    |
| **Logic**       | Join required tables Check for filter values if any. Filter accordingly.  Filter values are defined in the script of the index page Append actions column that contain buttons such as edit, delete, show, history and so on Return datatable |
| **Remarks**     | This function is called by the datatables displayed in the buildings.edit-form page                                                                                                                                                           |

| **Function**    | storeContainmentData()                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Handles the process of adding new containment to an existing building.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| **Parameters**  | \$request                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| **Return**      | Success or error message, stores data to containments.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| **Source**      | app\\Services\\Fsm\\ContainmentService.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| **Logic**       | Assings passed if to bin. Checks if multiple_bin field is not empty( this field will not be empty if the containment is already present in the system and is being used by some other building. If yes, creates new BuildContain, fetches containment_id of connected containment with the building Creates connection between existing containment and building If no, Finds the building to which new containment is being added and fetches its geom Creates new containment id and containment model Stores required information in said model If geom is present, calculates centroid from given geom and stores it in geom field of containment model Creates BuildContain connection between newly created containment and pre-existing building.  |

| **Function**    | updateContainmentData(\$request, \$id)                                                                                                                                                                                                                                      |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Handles the process of update containment information.                                                                                                                                                                                                                      |
| **Parameters**  | \$request, \$id                                                                                                                                                                                                                                                             |
| **Return**      | Success or error message, updates data to containments.                                                                                                                                                                                                                     |
| **Source**      | app\\Services\\Fsm\\ContainmentService.php                                                                                                                                                                                                                                  |
| **Logic**       | Similar to addition of containment. Only major difference is multiple_bin field is absent. All information previously present in the database are displayed in the fields.  Same information is restored into the database, according to logic of additing new containment  |

| **Function**    | getExport()                                                                                                                                                                                                                                                                                                                                                                                                                              |
|-----------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Handles the process of exporting containment information                                                                                                                                                                                                                                                                                                                                                                                 |
| **Parameters**  |                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| **Return**      | Returns CSV                                                                                                                                                                                                                                                                                                                                                                                                                              |
| **Source**      | app\\Services\\Fsm\\ContainmentService.php                                                                                                                                                                                                                                                                                                                                                                                               |
| **Logic**       | \$bin = isset(\$_GET['bin']) ? \$_GET['bin'] : null; Fetching value on the basis of which filter is to be carried out. This value needs to be declared in the script of the index page. Define columns, and the query fetching the respective columns Check if any filter values are present, if yes, filter accordingly Set styles using StyleBuilding provided by Box/Spout\^2 Run query, store values in the columns Export the data  |

| **Function**    | getExportBuildingContainment()                                                                                                                                                                                                                                                                                                                                                                                                                              |
|-----------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Handles the process of exporting building  connected with containment                                                                                                                                                                                                                                                                                                                                                                                |
| **Parameters**  |                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| **Return**      | Returns CSV                                                                                                                                                                                                                                                                                                                                                                                                                              |
| **Source**      | app\\Services\\Fsm\\ContainmentService.php                                                                                                                                                                                                                                                                                                                                                                                               |
| **Logic**       | \$bin = isset(\$_GET['bin']) ? \$_GET['bin'] : null; Fetching value on the basis of which filter is to be carried out. This value needs to be declared in the script of the index page. Define columns, and the query fetching the respective columns Check if any filter values are present, if yes, filter accordingly Set styles using StyleBuilding provided by Box/Spout\^2 Run query, store values in the columns Export the data  |


### Containment Public ID URL Binding

Containment browser URLs use a random UUID public_id instead of the internal containment id. The internal id remains the database primary key and may still be displayed as business data, but record-specific actions do not place it in the URL.

| Component | Location | Responsibility |
|-----------|----------|----------------|
| public_id migration | database\\migrations\\2026_08_12_000001_add_public_ids_to_buildings_and_containments.php | Adds UUID public_id columns, backfills existing Containments and Buildings, makes them required and creates unique indexes. |
| HasPublicId trait | app\\Models\\Concerns\\HasPublicId.php | Generates a UUID on model creation, returns public_id from getRouteKeyName() and validates incoming UUID route values. |
| Containment model | app\\Models\\Fsm\\Containment.php | Uses HasPublicId so implicit Containment route-model binding resolves by public_id. |
| Containment routes | routes\\web.php | Declares public_id binding for Containment actions and for Building–Containment relationship routes. |
| ContainmentService | app\\Services\\Fsm\\ContainmentService.php | Builds action buttons and forms with public_id rather than internal containment id or Building BIN. |

The following Containment actions use public_id in the URL:

| Action | Example route behavior |
|--------|------------------------|
| Show/Details | GET fsm/containments/{containment_public_id} |
| Edit | GET fsm/containments/{containment_public_id}/edit |
| Update | PUT/PATCH fsm/containments/{containment_public_id} |
| Delete | DELETE fsm/containments/{containment_public_id} |
| History | GET fsm/containments/{containment_public_id}/history |
| Type-change history | GET fsm/containments/{containment_public_id}/type-change-history |
| Connected Buildings | GET fsm/containments/{containment_public_id}/listBuildings |
| Map and nearest-road actions | Pass field=public_id and val={containment_public_id} instead of the internal id |
| Emptying-service history | Passes containment_public_id instead of the internal containment id |

Building–Containment routes protect both identifiers:

| Action | Route binding behavior |
|--------|------------------------|
| List Containments for a Building | GET fsm/containments/building/{building_public_id}/containmentData |
| Create Containment for a Building | GET fsm/containments/building/{building_public_id}/create |
| Store Containment for a Building | POST fsm/containments/building/{building_public_id}/store |
| Remove Building–Containment connection | DELETE fsm/containments/{containment_public_id}/buildings/{building_public_id} |

Controller methods type-hint the resolved models, including show(Containment \$containment), edit(Containment \$containment), update(ContainmentRequest \$request, Containment \$containment), history(Containment \$containment), typeChangeHistory(Containment \$containment), destroy(Containment \$containment), listBuildings(Containment \$containment) and deleteBuilding(Containment \$containment, Building \$building).

Laravel resolves each UUID to the correct model before the controller executes. After binding succeeds, controller and service code may use the internal id or BIN for database relationships, but those identifiers are not exposed in the action URL.

HasPublicId::resolveRouteBinding() rejects an internal numeric containment id, a Building BIN or a malformed UUID and returns a normal 404 response. It validates the value before PostgreSQL compares it with the native UUID column, preventing an invalid UUID from causing a database 500 error.

Public IDs reduce internal-identifier exposure and make sequential URL enumeration more difficult. They are not a replacement for authorization: authentication and the existing Containment permissions are still checked for show, edit, update, delete, history, relationship and map actions.

The URL-binding behavior is covered by tests\\Feature\\PublicIdUrlBindingTest.php. The tests verify UUID population and uniqueness, public_id usage in action URLs, absence of internal Containment IDs and Building BINs from those URLs, correct model resolution, automatic UUID generation and 404 responses for internal or malformed identifiers.

**ContainmentRequest**

Location: app\\Http\\Requests\\Fsm\\ContainmentRequest.php)

ContainmentRequest handles all validation login. It handles validation logic as well as error messages to be displayed.

| **Function**    | authorize()                                      |
|-----------------|--------------------------------------------------|
| **Description** | Determines if user is authenticated or not       |
| **Parameters**  |                                                  |
| **Return**      | Returns true                                     |
| **Source**      | app\\Http\\Requests\\Fsm\\ContainmentRequest.php |

| **Function**    | message()                                                                                                                         |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Message to be displayed in case of validation error                                                                               |
| **Parameters**  |                                                                                                                                   |
| **Return**      | Return validation message                                                                                                         |
| **Source**      | app\\Http\\Requests\\Fsm\\ContainmentRequest.php                                                                                  |
| **Remarks**     | Need to include errors.list.blade that displays the error message in dashboard Format: ‘Field.validation_rule’ =\>”error_message” |

| **Function**    | rules()                                                                           |
|-----------------|-----------------------------------------------------------------------------------|
| **Description** | Contains the validation rules                                                     |
| **Parameters**  |                                                                                   |
| **Return**      | Return validation logic to calling place                                          |
| **Source**      | app\\Http\\Requests\\Fsm\\ContainmentRequest.php                                  |
| **Remarks**     | Format for validation rule: ‘field_name’=\>’validation_rule1 \| validation_rule2’ |

## Service Provider IMS

Service Provider IMS encompasses three different sub-modules:

-   Service Providers
-   Employee Information
-   Desludging Vehicles

### Service Providers

Tables

The service providers module uses the following tables:

-   `fsm.service_providers`: provider identity, contact, office ward, operational status, `contract_document_pdf` path and `service_area` ward CSV.
-   `fsm.service_provider_wards`: normalized ward coverage with a unique `(service_provider_id, ward)` pair and timestamps. Several providers may cover the same ward; there is no provider foreign key in this migration.
-   `layer_info.wards`: municipality ward choices and the population used to calculate uncovered wards.

The provider model is `app/Models/Fsm/ServiceProvider.php`. Normalized ward coverage is synchronized with query-builder operations in `ServiceProviderService`, not a separate coverage model.

Views

All views used by this module is stored in resources\\views\\fsm\\service-providers

-   service-providers.create: opens form and calls partial-form for form content.
-   service-providers.edit: opens from and calls partial-form for form contents.
-   service-providers.history: lists all past edits of the record.
-   service-providers.index: lists service providers records.
-   service-providers.show: displays a single provider's details, contract link, coverage and readable status.
-   service-providers.partial-form: creates form content for addition of new service providers or edit service providers.

Models

Location: app\\Models\\Fsm\\ServiceProvider.php

There are multiple relationships defined in the Service Provider Model. They are:

-   sludgecollections: ServiceProvider model has many relationship with sludgecollection.
-   Applications: ServiceProvider model has many relationship with application.
-   Users: ServiceProvider model has many relationship with users.
-   VacutugTypes: ServiceProvider model has many relationship with vacutug types.

Service Provider Request

Location: app\\Http\\Requests\\Fsm\\ServiceProviderRequest.php

ServiceProviderRequest handles validation rules and error messages. Creation requires a PDF contract up to 5 MB; replacement on edit is optional with the same file rules. Both actions require a service-area array with at least one integer ward, company/contact details and boolean operational status. Company uniqueness is checked among non-deleted providers. Email uniqueness is checked against `auth.users`, with the current provider's users excluded on update.

#### Add logic

`ServiceProviderController::create()` loads ward/status choices and the uncovered-wards information for the add form. The popover calculates wards not covered by operational providers, using both CSV and normalized coverage; it does not restrict the dropdown or forbid overlapping coverage. Office ward and service-area wards are separate inputs.

The multipart `POST /fsm/service-providers` calls `store()` after request validation. The PDF is stored on the public disk under `contract_documents/`, using a sanitized company name and timestamp. `ServiceProviderService::storeOrUpdate(null, $data)` saves the provider, converts selected wards into positive unique sorted integers, writes their CSV to `service_area`, and replaces the provider's normalized ward rows. It returns the new provider ID.

When `create_user` is filled, the controller calls `UserService` to create a linked `Service Provider - Admin` account, with user type `Service Provider`, company name as the account name and the email local part as the username. Password validation is conditional on `create_user=on`. The action redirects to the provider list with the appropriate success message.

#### Edit logic

`edit($id)` loads the provider with `findOrFail()`, ward/status choices and selected wards parsed from the saved CSV. `edit.blade.php` binds the model and submits multipart `PATCH /fsm/service-providers/{id}`. Old service-area input is retained after validation failures.

`update()` keeps the existing contract when no new PDF is supplied. When replacing it, the controller stores the new PDF, deletes the old file if it exists, and then saves the new path through `storeOrUpdate($id, $data)`. Coverage is normalized and synchronized again. Updating with non-operational status (`0`) clears `service_provider_id` from this provider's applications where `emptying_status = false`; completed emptyings are not unassigned. The provider edit action does not edit the linked admin account or regenerate existing schedule rows.

#### Detail logic

`show($id)` renders `show.blade.php` with the provider and a description from `ServiceProviderStatus`; an absent provider returns 404. The page shows company name, email, office ward, address, contact name/gender/number, readable status, contract and service areas. The PDF opens from `storage/<saved path>` in a new tab. Missing contract paths or empty coverage display `Not available`. Coverage display formats the saved CSV; it does not query the normalized ward table.

#### Permissions, persistence and verification

The controller requires `Add Service Provider` for create/store, `Edit Service Provider` for edit/update and `View Service Provider` for details, in addition to authentication. List, delete and CSV export have their own permissions. Data/history actions are not in the action-specific permission lists.

Provider saving, coverage replacement, PDF handling and optional account creation are not one transaction. A file link does not prove that the stored PDF exists. Verify creation validation, keeping/replacing contracts, coverage synchronization, optional account creation, direct-route access, missing-provider 404 and non-operational reassignment. See the [detailed provider workflow and route tables](21%20-%20FSM_SERVICE_DELIVERY_TECHNICAL_DOCUMENT.md#service-provider-ims) for the exact rules, migrations and failure considerations.

### Employee Information

Tables

The employee information module uses the following table:

-   employees: stores primary information of employee.
-   Foreign Key: service_provider_id.

The corresponding tables have their respective models that are named in Pascal Case in singular form. The service provider modules are located at app\\Http\\Models\\Fsm\\ EmployeeInfo.

Views

All views used by this module is stored in resources\\views\\fsm\\employee-infos

-   employee-infos.create: opens form and calls partial-form for form content.
-   employee-infos.edit: opens from and calls partial-form for form contents.
-   employee-infos.history: lists all past edits of the record.
-   employee-infos.index: lists employee-infos records.
-   employee-infos.show lists employee-infos.
-   employee-infos.partial-form: creates form content for addition of new employee information or edit employee information.

Models

Location: app\\Models\\Fsm\\ EmployeeInfo.php

There is no multiple relationships defined in the Employee Information Model.

Employee Information Request

Location: app\\Http\\Requests\\Fsm\\EmployeeInfoRequest.php

EmployeeInfoRequest handles all validation login. It handles validation logic as well as error messages to be displayed.

Employee Information follow CRUD operations, which usually have the same pattern. You can refer to the "Basic CRUD" section above (2.2) for more information.

### Desludging Vehicles

Tables

The desludging vehicles module uses the following table:

-   desludging_vehicles: stores primary information of desludging vehicles.
-   Foreign Key: service_provider_id

The corresponding tables have their respective models that are named in Pascal Case in singular form. The desludging vehicles modules are located at app\\Http\\Models\\Fsm\\VacutugType.

Views

All views used by this module is stored in resources\\views\\fsm\\vacutug-types

-   vacutug-types.create: opens form and calls partial-form for form content.
-   vacutug-types.edit: opens from and calls partial-form for form contents.
-   vacutug-types.history: lists all past edits of the record.
-   vacutug-types.index: lists desludging vehicles records.
-   vacutug-types.show lists desludging vehicles types.
-   vacutug-types.partial-form: creates form content for addition of new desludging vehicles or edit desludging vehicles.

Models

Location: app\\Models\\Fsm\\VacutugType.php

There are multiple relationships defined in the Desludging vehicles Model. They are:

-   sludgecollections: Desludging vehicles model has many relationship with sludgecollection.
-   serviceProvider: Desludging vehicles model belongs to service provider.
-   vacutugType: Desludging vehicles model belongs to vacutug type

Desludging Vehicles Request

Location: app\\Http\\Requests\\Fsm\\VacuTugRequest.php

DesludgingVehiclesRequest handles all validation login. It handles validation logic as well as error messages to be displayed.

Desludging Vehicles follow CRUD operations, which usually have the same pattern. You can refer to the "Basic CRUD" section above (2.2) for more information.

## Treatment Plant IMS

Treatment Plant IMS encompasses two different sub-modules:

-   Treatment Plant
-   Performance Efficiency Test

### Treatment Plant

Tables

The treatment plant module uses the following table:

-   treatment_plants: stores primary information of treatment plants.

The corresponding tables have their respective models that are named in Pascal Case in singular form. The treatment plant modules are located at app\\Http\\Models\\Fsm\\ TreatmentPlant.

Views

All views used by this module is stored in resources\\views\\fsm\\treatment-plants

-   treatment-plants.create: opens form and calls partial-form for form content.
-   treatment-plants.edit: opens from and calls partial-form for form contents.
-   treatment-plants.history: lists all past edits of the record.
-   treatment-plants.index: lists of treatment plant records.
-   treatment-plants.show lists of treatment plant.
-   treatment-plants.partial-form: creates form content for addition of new treatmentplant or edit treatmentplant.

Models

Location: app\\Models\\Fsm\\TreatmentPlant.php

There are multiple relationships defined in the Treatment Plant Model. They are:

-   sludgecollections: Treatment Plant model has many relationship with sludgecollection.
-   compostSales: Treatment Plant model has many relationship with compostSales.
-   Users: Treatment Plant model has many relationship with users.
-   emptyings: TreatmentPlant model has many relationship with emptyings.
-   Treatmentplanttests: TreatmentPlant model has many relationship with treatmentplanttests.

Treatment Plant Request

Location: app\\Http\\Requests\\Fsm\\TreatmentPlantRequest.php

TreatmentPlantRequest handles all validation login. It handles validation logic as well as error messages to be displayed.

Treatment Plant follow CRUD operations, which usually have the same pattern. You can refer to the "Basic CRUD" section above (2.2) for more information.

## Performance Efficiency Standards

### Tables

Performance efficiency standards is under Settings module and uses the following table:

*public.treatment_plant_performance_efficiency_test_settings*

The corresponding tables have their respective models that are named in Pascal Case in singular form. TreatmentPlantPerformanceTest model is located at app\\Models\\Fsm\\.

### Views

All views used by this module is stored in resources\\views\\fsm\\treatment-plant-performance-test

-   treatment-plant-performance-test.index: opens treatment plant effecienct test form.

### TreatmentplantPerformanceTestController

app\\Http\\Controllers\\Fsm\\TreatmentplantPerformanceTestController.php

The controller’s main function is to provide the connection between the calling route and its subsequent function written in the Service Class.

The basic classes of the controller are:

|  **Function**   | \__construct()                                                             |
|-----------------|----------------------------------------------------------------------------|
| **Description** | Initializes authentication, permissions and the service class instance     |
| **Parameters**  | Service class instance(TreatmentplantPerformanceTestService)               |
| **Return**      | null                                                                       |
| **Source**      | app\\Http\\Controllers\\Fsm\\TreatmentplantPerformanceTestController.php   |

| **Function**    | index()                                                                      |
|-----------------|------------------------------------------------------------------------------|
| **Description** | Returns the index.blade.php page with dropdown values fetched from database. |
| **Parameters**  | null                                                                         |
| **Return**      | fsm/treatment-plant-performance-test.index compact('page_title', 'data')     |
| **Source**      | app\\Http\\Controllers\\Fsm\\TreatmentplantPerformanceTestController.php     |

| **Function**    | store()                                                                                    |
|-----------------|--------------------------------------------------------------------------------------------|
| **Description** | Store or update treatment plant performance test data                                      |
| **Parameters**  | TreatmentplantPerformanceTestRequest \$request                                             |
| **Return**      | Redirection with success/failure message                                                   |
| **Source**      | app\\Http\\Controllers\\Fsm\\TreatmentplantPerformanceTestController.php                   |
| **Remarks**     | treatmentplantPerformanceTestService-\>storeOrUpdate(\$data);  Service Class Function Name |

### TreatmentplantPerformanceTestService

Location: app\\Services\\Fsm\\TreatmentplantPerformanceTestService.php

The Service Class contains all the business logic. It contains all the functions that are being called in the TreatmentplantPerformanceTestController.php

|  **Function**   | storeOrUpdate()                                                                                   |
|-----------------|---------------------------------------------------------------------------------------------------|
| **Description** | Handles the process of edditing treatmentplant performance efficiency standards.                  |
| **Parameters**  | \$id,\$data                                                                                       |
| **Return**      | Success or error message, stores/updates data to treatment plant performance efficiency standards |
| **Source**      | app\\Services\\Fsm\\TreatmentplantPerformanceTestService.php                                      |

**TreatmentplantPerformanceTestRequest**

Location: app\\Http\\Requests\\Fsm\\TreatmentplantPerformanceTestRequest.php)

TreatmentplantPerformanceTestRequesthandles all validation logic. It handles validation logic as well as error messages to be displayed.

### TreatmentPlantPerformanceTest

**Location:** App\\Models\\Fsm**\\**TreatmentPlantPerformanceTest**.php**

The models contain the connection between the model and the table defined by

*\$table = ‘public.treatment_plant_performance_efficiency_test_settings’* as well as the primary key defined by

*primaryKey= ‘id’*

### Performance Efficiency Test

Tables

The performance efficiency test module uses the following table:

-   treatment_plant_tests: stores primary information of performance efficiency test.

The corresponding tables have their respective models that are named in Pascal Case in singular form. The treatment plant modules are located at app\\Http\\Models\\Fsm\\ TreatmentPlantTest.

Views

All views used by this module is stored in resources\\views\\fsm\\treatment-plant-test

-   treatment-plant-test.create: opens form and calls partial-form for form content.
-   treatment-plant-test.edit: opens from and calls partial-form for form contents.
-   treatment-plant-test.history: lists all past edits of the record.
-   treatment-plant-test.index: lists of performance efficiency test records.
-   treatment-plant-test.show lists of performance efficiency test.
-   treatment-plant-test.partial-form: creates form content for addition of new performance efficiency test or edit performance efficiency test.

Models

Location: app\\Models\\Fsm\\TreatmentPlantTest.php

There are multiple relationships defined in the Treatment Plant Model. They are:

treatmentplants:Performance efficiency test model has many relationship with treatment plants.

Perfomance Efficency Test

Location:app\\Http\\Requests\\Fsm\\TreatmentplantPerformanceTestRequest.php

PerformanceTestRequest handles all validation login. It handles validation logic as well as error messages to be displayed.

Performance Efficiency Test follow CRUD operations, which usually have the same pattern. You can refer to the "Basic CRUD" section above (Section 1.4) for more information.

## Emptying Service IMS

### Scheduled Desludging and Reintegration

Scheduled desludging adds a temporary queue before the existing application, emptying, sludge collection and feedback chain. The queue is generated working data, not a permanent application record.

| Component | Source |
| --- | --- |
| Schedule controller | `app/Http/Controllers/Fsm/DesludgingScheduleController.php` |
| Reintegration controller | `app/Http/Controllers/Fsm/DesludgingReintegrationController.php` |
| Generation and handoff service | `app/Services/Fsm/DesludgingScheduleService.php` |
| Queue model/table | `app/Models/Fsm/DesludgingSchedule.php`; `fsm.desludging_schedule_temp` |
| Views | `resources/views/fsm/desludging-schedule/index.blade.php`, `resources/views/fsm/desludging-reintegration/index.blade.php` |
| Supporting data | `fsm.containments`, building/owner context, provider coverage and `public.site_settings` |

`regenerate()` performs priority calculation, date allocation, queue refresh and provider assignment inside a database transaction with a PostgreSQL advisory lock. Concurrent regeneration returns HTTP 409. Priority uses valid last-emptying date, then construction date: no usable date or more than three years gives priority 1; between one and three years gives priority 2; within one year gives priority 3. Future dates are ignored.

Date allocation uses never-emptied active containments, configured working dates, holidays and remaining daily trip capacity. Queue refresh has different status and open-application eligibility criteria; it must not be described as the same population or order as date allocation. Uncovered wards may be distributed among operational providers without coverage; queue provider selection uses current building ward and selects the lowest operational provider ID where coverage overlaps. Unmatched rows remain unassigned.

Accepting a schedule row stores server-side context and opens application creation in confirm mode. Disagreeing sets containment status to 4 and removes its queue row transactionally. Reintegration lists status-4 containments; confirmation resolves provider coverage by current building ward and hands off to the application form. Scheduled application creation sets containment status to 1 and removes its temporary rows after a successful save.

The clean code still requires `supervisory_assessment_date` in confirm-mode request validation and reads it in the application service. This is an existing confirmation dependency requiring verification, not an additional workflow stage introduced by this documentation. See [implementation considerations](21%20-%20FSM_SERVICE_DELIVERY_TECHNICAL_DOCUMENT.md#implementation-considerations).

Schedule list/data, regenerate, accept and disagree use their respective `List`, `Regenerate`, `Confirm` and `Delete Schedule Desludging` permissions. Reintegration uses `List Schedule Reintegration` and `Confirm Schedule Reintegration`. Exact routes, capacity/settings rules and QA scenarios are in the [detailed scheduling reference](21%20-%20FSM_SERVICE_DELIVERY_TECHNICAL_DOCUMENT.md#scheduled-desludging).

### Existing Application and Emptying Architecture

A new architecture has been followed for the following sub-modules:

-   Application
-   Emptying

This new architecture introduces Dynamic Forms for every Model in the MVC architecture.

A template has been created for generating forms so that a common view can be utilized and individual views for forms are removed.

FormField Class

The model for a form field.

| **Function**    | \__construct()                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Initializes all the attributes required for the form field.                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| **Parameters**  |  String \$label = 'Label',  String \$labelFor = 'LabelFor',  String \$labelClass = 'col-sm-4 control-label',  String \$inputType = 'text',  String \$inputId = 'input-id',  String \$inputValue = null,  String \$inputClass = 'form-control',  array \$selectValues = [],  String \$selectedValue=null,  String \$placeholder = 'Enter value here',  String \$labelValue=null,  bool \$disabled = false,  bool \$hidden = false,  String \$fileUrl = '',  bool \$required = false,  String \$autoComplete = "on" |
| **Return**      | null                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| **Source**      | app\\Classes/FormField                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |

Parameters

**label**

| **Type**          | String                         |
|-------------------|--------------------------------|
| **Description**   | The label for the input field. |
| **Value**         | String                         |
| **Default value** | “Label”                        |

**labelFor**

| **Type**          | String                                            |
|-------------------|---------------------------------------------------|
| **Description**   | Specifies which form element a label is bound to. |
| **Value**         | ID of input                                       |
| **Default value** | “LabelFor”                                        |

**labelClass**

| **Type**          | String                   |
|-------------------|--------------------------|
| **Description**   | Classes for the label    |
| **Default Value** | “col-sm-4 control-label” |

**inputType**

| **Type**          | String                                                                                                       |
|-------------------|--------------------------------------------------------------------------------------------------------------|
| **Description**   | Specifies the type \<input\> element to display.                                                             |
| **Value**         | “text”,”number”,”select”,”label”,”radio”,”multiple-select”,”date”,”time”,”image”,”file_viewer”,”file_upload” |
| **Default value** | “text”                                                                                                       |

**inputId**

| **Type**          | String            |
|-------------------|-------------------|
| **Description**   | ID for the input. |
| **Default value** | “input-id”        |

**inputValue**

| **Type**          | String                                      |
|-------------------|---------------------------------------------|
| **Description**   | Specifies the value of an \<input\> element |
| **Default value** | null                                        |

**inputClass**

| **Type**          | String                 |
|-------------------|------------------------|
| **Description**   | Classes for the input. |
| **Default value** | “form-control”         |

**selectValues**

| **Type**          | array                 |
|-------------------|-----------------------|
| **Description**   | Options for selection |
| **Default value** | [ ]                   |

**selectedValue**

| **Type**          | String                        |
|-------------------|-------------------------------|
| **Description**   | Specifies the selected option |
| **Default value** | null                          |

**placeholder**

| **Type**          | String                                                                           |
|-------------------|----------------------------------------------------------------------------------|
| **Description**   | Specifies a short hint that describes the expected value of an \<input\> element |
| **Default value** | “Enter value here”                                                               |

**labelValue**

| **Type**          | String                                                                                                           |
|-------------------|------------------------------------------------------------------------------------------------------------------|
| **Description**   | This value is displayed instead of showing an input just like a label. This is used when viewing a model detail. |
| **Default value** | null                                                                                                             |

**disabled**

| **Type**          | bool                                                   |
|-------------------|--------------------------------------------------------|
| **Description**   | Specifies that an \<input\> element should be disabled |
| **Value**         | true,false                                             |
| **Default value** | false                                                  |

**hidden**

| **Type**          | bool                                                 |
|-------------------|------------------------------------------------------|
| **Description**   | Specifies that an \<input\> element should be hidden |
| **Value**         | true,false                                           |
| **Default value** | false                                                |

**fileUrl**

| **Type**          | String                                        |
|-------------------|-----------------------------------------------|
| **Description**   | The url of the file for the file viewer input |
| **Default value** | “ “                                           |

**required**

| **Type**          | bool                                                                                            |
|-------------------|-------------------------------------------------------------------------------------------------|
| **Description**   | Specifies whether the input field is required or not. Displays a red asterisk beside the label. |
| **Value**         | true,false                                                                                      |
| **Default value** | false                                                                                           |

**autoComplete**

| **Type**          | String                                           |
|-------------------|--------------------------------------------------|
| **Description**   | Specifies whether to provide suggestions or not. |
| **Value**         | “on”,”off”                                       |
| **Default value** | “off”                                            |

View Template

The array of FormField objects are rendered in partial-form.blade located in the resources/layouts folder. This view also accepts a variable name cardForm which if set to true displays forms grouped in cards with their respective title.

**Normal form**

For normal form, we send an array of FormField objects.

**Card form**

For card form we send FormField objects along with their card titles in the following way :

*[*

*[*

*“title” =\> “Card title”,*

*“fields” =\> [ FormField objects ]*

*],*

*[*

*“title” =\> “Card title”,*

*“fields” =\> [ FormField objects ]*

*],*

*]*

The FSM service delivery module consists of multiple smaller sub-modules that work in tandem to cover the entire service delivery chain. This module updates multiple tables in different scenarios which is depicted in below.

![](media/5-fecal-sludge-service-delivery-dfd-3.png)

The workflow for the entire service value chain is shown in below.

**![](media/5-fecal-sludge-service-delivery-dfd-4.png)**

### Application

Tables

The Application Module uses the following tables:

-   Fsm.applications: stores the primary information of the application. customer_name, customer_gender and customer_contact contain application-encrypted customer/owner PII. applicant_name, applicant_gender and applicant_contact remain a separate operational snapshot for downstream FSM workflows.
-   Building_info.buildings: retrieves informations of required building
-   Building_info.owners: relational database that connects buildings and their owner.
-   Utility_info.roads: retrieves road information of required building

The corresponding tables have their respective models that are named in Pascal Case in singular form. The application module is located at app\\Http\\Models\\Fsm\\Application.

Views

-   resources/views/fsm/applications/index.blade.php: lists applications.
-   resources/views/fsm/applications/create.blade.php: opens form and calls partial-form for form contents
-   resources/views/fsm/applications/edit.blade.php: opens form and calls partial-form for form contents
-   resources/views/fsm/applications/history.blade.php: lists all past edits of the record
-   resources/views/layouts/show.blade.php: view detailed information of the record
-   resources/views/layouts/partial-form.blade.php: the form template
-   resources/views/layouts/application_report.blade.php: shows applications for pdf download
-   resources/views/layouts/monthly_report.blade.php: shows detailed information month wise for pdf download

Application customer PII behavior in the views:

-   applications.index: displays fixed masks for customer name and contact while PII is locked. Authorized users can activate View Owner PII Information after re-entering their current password.
-   applications.index: shows the customer-name filter only while Application PII reveal mode is active.
-   applications.create: keeps Building owner lookup fields masked unless the current user has an unexpired Application owner_lookup scope and Add Application permission.
-   layouts.show and applications.edit: show fixed masks while locked. Customer PII edit fields remain disabled until an edit-scoped privileged session is active.
-   applications.index: provides a dedicated customer PII export for either a validated BIN list or, for approved administrative roles, all authorized Application rows.

Models

The models contain the connection between the model and the table defined by

*\$table = ‘schema_name.table_name’* as well as the primary key defined by

*primaryKey= ‘primary_key_field’*

For history functionality, the revisionCreationsEnabled is set true and we use *RevisionableTrait(use Venturecraft\\Revisionable\\RevisionableTrait);*

For soft deletes functionality, we

*use SoftDeletes;(use Illuminate\\Database\\Eloquent\\SoftDeletes;)*

For Setting belongsTo and HasMany relationships, we:

*use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;*

*belongsToMany('model_relative_path', 'relational_table', primary_key, 'primary_key of relationship')*

*belongsTo('model_name', 'pk of first table', 'pk of Second table'*

*use Illuminate\\Database\\Eloquent\\Relations\\HasMany;*

Application Model

Location: app\\Models\\Fsm\\Application.php

There are multiple relationships defined in the Application Model. They are:

-   service_provider: belongsTo
-   buildings: belongsTo
-   emptying: hasMany
-   feedback: hasOne
-   sludge_collection: hasOne

The Application model encrypts these attributes before writing to fsm.applications:

-   customer_name
-   customer_gender
-   customer_contact

The setCustomerNameAttribute(), setCustomerGenderAttribute() and setCustomerContactAttribute() mutators call PiiEncryptionService. Null remains null and an already encrypted value is not encrypted twice. These fields are excluded from revision history so plaintext or ciphertext is not copied into the revisions table.

The applicant_name, applicant_gender and applicant_contact fields are intentionally separate from the encrypted customer fields. Customer and applicant may be different people. Applicant fields are used as an operational snapshot by downstream workflows and are copied from customer data only when the user explicitly selects the Same as Owner option.

Application Controller

path: App\\Http\\Controllers\\Fsm\\ApplicationController.php

The controller’s main function is to provide the connection between the calling route and its subsequent function written in the Service Class.

The basic classes of the controller are:

| **Function**    | \__construct()                                                                                                     |
|-----------------|--------------------------------------------------------------------------------------------------------------------|
| **Description** | Initializes ApplicationService, ApplicationPiiAccessService, OwnerPiiAuditService and no-cache middleware          |
| **Parameters**  | ApplicationService, ApplicationPiiAccessService and OwnerPiiAuditService                                            |
| **Return**      | null                                                                                                               |
| **Source**      | app\\Http\\Controllers\\Fsm\\ApplicationController.php                                                             |

| **Function**    | index()                                                                                                                                                                                              |
|-----------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns the Application list with links, filters, customer PII permissions, reveal state, export permission and remaining privileged-session time.                                                   |
| **Parameters**  | null                                                                                                                                                                                                 |
| **Return**      | fsm.applications.index with the existing list data plus customerPiiListUnlocked, canUnlockCustomerPii, canExportCustomerPii and customerPiiUnlockSeconds                                               |
| **Source**      | App\\Http\\Controllers\\Fsm\\ApplicationController.php                                                                                                                                               |

| **Function**    | create()                                                                                                                                                                                                                                             |
|-----------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns the form to create a new application with values fetched from the database.                                                                                                                                                                  |
| **Parameters**  | null                                                                                                                                                                                                                                                 |
| **Return**      | view('fsm.applications.create',[  'formAction' =\> \$this-\>applicationService-\>getCreateFormAction(),  'formFields' =\> \$this-\>applicationService-\>getCreateFormFields(),  'indexAction' =\> \$this-\>applicationService-\>getIndexAction()  ]) |
| **Source**      | app\\Http\\Controllers\\Fsm\\ApplicationController.php                                                                                                                                                                                               |

| **Function**    | getData()                                                                                                                                                              |
|-----------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns the Application DataTable with customer name and contact fixed-masked while locked or explicitly decrypted during a valid list-scoped Application PII session. |
| **Parameters**  | Request \$request                                                                                                                                                      |
| **Return**      | DataTable response from getDatatable(\$request, \$revealed)                                                                                                           |
| **Source**      | App\\Http\\Controllers\\Fsm\\ApplicationController.php                                                                                                                |
| **Remarks**     | Privileged list responses and customer-name filter use are audited.                                                                                                    |

| **Function**    | store()                                                                      |
|-----------------|------------------------------------------------------------------------------|
| **Description** | Calls the service class that handles the process of creating new application |
| **Parameters**  | ApplicationRequest \$request                                                 |
| **Return**      | Redirection to index page with success or failure prompt                     |
| **Source**      | App\\Http\\Controllers\\Fsm\\ApplicationController.php                       |
| **Remarks**     | createApplication(\$request) Service Class Function Name                     |

| **Function**    | show()                                                                                                                                      |
|-----------------|---------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns Application details with fixed-masked customer PII unless a valid view-scoped Application PII session exists.                       |
| **Parameters**  | Application id                                                                                                                              |
| **Return**      | view('layouts.show', compact('page_title', 'formFields', 'application', 'indexAction'), ['cardForm'=\>true])                                |
| **Source**      | App\\Http\\Controllers\\Fsm\\ApplicationController.php                                                                                     |
| **Remarks**     | Successful plaintext viewing is audited.                                                                                                    |

| **Function**    | edit()                                                                                                                                                       |
|-----------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns the edit form. Customer PII is masked and disabled while locked and plaintext/editable only during a valid edit-scoped Application PII session.       |
| **Parameters**  | Application id                                                                                                                                               |
| **Return**      | view('fsm.applications.edit', compact('page_title', 'formFields', 'formAction', 'indexAction', 'application'), ['cardForm'=\>true])                            |
| **Source**      | App\\Http\\Controllers\\Fsm\\ApplicationController.php                                                                                                      |

| **Function**    | update()                                                                                                                                                 |
|-----------------|----------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Updates Application data and rejects customer_name, customer_gender or customer_contact changes unless an edit-scoped Application PII session is active. |
| **Parameters**  | ApplicationRequest \$request, \$id                                                                                                                       |
| **Return**      | Redirection to index page with success or failure prompt                                                                                                 |
| **Source**      | App\\Http\\Controllers\\Fsm\\ApplicationController.php                                                                                                   |
| **Remarks**     | Authorized changes are audited and encrypted by Application model mutators before database storage.                                                      |

| **Function**    | export()                                                                   |
|-----------------|----------------------------------------------------------------------------|
| **Description** | Calls the service class that handles the process of exporting applications |
| **Parameters**  | Request \$request                                                          |
| **Return**      | CSV file containing applications                                           |
| **Source**      | App\\Http\\Controllers\\Fsm\\ApplicationController.php                     |
| **Remarks**     | export(\$request) (service class function)                                 |

| **Function**    | buildingDetails()                                                                                                                                                                                       |
|-----------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Fetches Building details after BIN selection. Building owner PII is returned only when the user has Add Application permission and an unexpired owner_lookup scope from Application PII password re-entry. |
| **Parameters**  | Request containing bin                                                                                                                                                                                  |
| **Return**      | JSON containing non-PII Building information, lock flags and either authorized owner plaintext or null owner fields                                                                                     |
| **Source**      | App\\Http\\Controllers\\Fsm\\ApplicationController.php                                                                                                                                                  |
| **Remarks**     | The server validates access on every lookup; a browser flag cannot unlock PII. Authorized owner lookup is audited and protected by no-cache middleware.                                                   |

| **Function**    | history                                                                                                                        |
|-----------------|--------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Calls the service class that handles the process of fetching application history of specified application                      |
| **Parameters**  | \$id                                                                                                                           |
| **Return**      | Redirection to history page on successful retrieval or redirection to index page with failure prompt on unsuccessful retrieval |
| **Source**      | App\\Http\\Controllers\\Fsm\\ApplicationController.php                                                                         |

| **Function**    | Destroy()                                                |
|-----------------|----------------------------------------------------------|
| **Description** | Soft deletes the application                             |
| **Parameters**  | \$id                                                     |
| **Return**      | Redirection to index page with success or failure prompt |
| **Source**      | App\\Http\\Controllers\\Fsm\\ApplicationController.php   |

| **Function**    | monthlyApplicationsPdf()                                               |
|-----------------|------------------------------------------------------------------------|
| **Description** | Export application report month wise                                   |
| **Parameters**  | \$year, \$month                                                        |
| **Return**      | PDF file containing application report                                 |
| **Source**      | App\\Http\\Controllers\\Fsm\\ApplicationController.php                 |
| **Logic**       | All the filter values are retrieved if any. Export with filtered data. |
| **Remarks**     | fethMonthlyReport(\$year, \$month) (service class function)            |

| **Function**    | applicationReport()                                    |
|-----------------|--------------------------------------------------------|
| **Description** | Export single application report                       |
| **Parameters**  | \$id                                                   |
| **Return**      | PDF file containing application report                 |
| **Source**      | App\\Http\\Controllers\\Fsm\\ApplicationController.php |
| **Remarks**     | getApplicationReport (\$id) (service class function)   |

Application Service

path: App\\Services\\Fsm\\ApplicationService.php

The service class contains all the business logic and provides all the services related to application.

| **Function**    | \__construct()                                                                                                                                                                                                                                                                                                                                                                                       |
|-----------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Constructor that initializes following fields: createPartialForm : name of view for create partial form createFormFields : array of FormField objects for create form createFormAction : route for posting create form indexAction : route of the index page createRoute : route of create page exportRoute : route of the export page filterFormFields : array of FormField objects for filter form |
| **Parameters**  | null                                                                                                                                                                                                                                                                                                                                                                                                 |
| **Return**      | null                                                                                                                                                                                                                                                                                                                                                                                                 |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php                                                                                                                                                                                                                                                                                                                                                           |

| **Function**    | getCreateFormFields()                      |
|-----------------|--------------------------------------------|
| **Description** | Get form fields for creating applications. |
| **Parameters**  | null                                       |
| **Return**      | createFormFields()                         |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php |

| **Function**    | getShowFormFields(\$application, bool \$revealCustomerPii = false)                                        |
|-----------------|------------------------------------------------------------------------------------------------------------|
| **Description** | Builds Application detail fields with fixed-masked or explicitly decrypted customer PII.                  |
| **Parameters**  | Application and server-authorized reveal flag                                                             |
| **Return**      | showFormFields                                                                                             |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php                                                                  |

| **Function**    | getEditFormFields(\$application, bool \$revealCustomerPii = false)                                         |
|-----------------|------------------------------------------------------------------------------------------------------------|
| **Description** | Builds Application edit fields; customer PII fields remain masked and disabled unless edit scope is valid. |
| **Parameters**  | Application and server-authorized reveal flag                                                              |
| **Return**      | editFormFields                                                                                              |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php                                                                   |

| **Function**    | getCreateFormAction()                      |
|-----------------|--------------------------------------------|
| **Description** | Get action/route for create form.          |
| **Parameters**  | null                                       |
| **Return**      | createFormAction                           |
| **Source**      | app\\Services\\Fsm\\ApplicationService.php |

| **Function**    | getIndexAction()                                 |
|-----------------|--------------------------------------------------|
| **Description** | Get action/route for index page of Applications. |
| **Parameters**  | null                                             |
| **Return**      | indexAction                                      |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php       |

| **Function**    | getCreateRoute()                                  |
|-----------------|---------------------------------------------------|
| **Description** | Get action/route for create page of Applications. |
| **Parameters**  | null                                              |
| **Return**      | createRoute()                                     |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php        |

| **Function**    | getExportRoute()                             |
|-----------------|----------------------------------------------|
| **Description** | Get action/route for exporting Applications. |
| **Parameters**  | null                                         |
| **Return**      | exportRoute()                                |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php   |

| **Function**    | getEditFormAction()                        |
|-----------------|--------------------------------------------|
| **Description** | Get action/route for edit form.            |
| **Parameters**  | \$application                              |
| **Return**      | editFormAction                             |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php |

| **Function**    | getReportRoute()                           |
|-----------------|--------------------------------------------|
| **Description** | Get action/route for report.               |
| **Parameters**  | null                                       |
| **Return**      | reportRoute                                |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php |

| **Function**    | getFilterFormFields()                      |
|-----------------|--------------------------------------------|
| **Description** | Get form fields for filter.                |
| **Parameters**  | null                                       |
| **Return**      | filterFormFields                           |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php |

| **Function**    | getAllApplications()                       |
|-----------------|--------------------------------------------|
| **Description** | Get all the applications.                  |
| **Parameters**  | Request \$request                          |
| **Return**      | Collection of applications                 |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php |

| **Function**    | getDatatable(Request \$request, bool \$revealCustomerPii = false)                                         |
|-----------------|------------------------------------------------------------------------------------------------------------|
| **Description** | Returns the allowlisted Application DataTable with customer PII masked or decrypted according to server-authorized reveal state. |
| **Parameters**  | Request and reveal flag                                                                                   |
| **Return**      | DataTables                                                                                                |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php                                                                  |

| **Function**    | getBuildingDetails(Request \$request, bool \$revealOwnerPii = false)                                               |
|-----------------|--------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns selected Building data and includes owner plaintext only when the controller supplies an authorized owner_lookup flag. |
| **Parameters**  | Request containing BIN and server-authorized reveal flag                                                           |
| **Return**      | JSON response with lock flags, non-PII Building data and authorized owner fields or null                            |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php                                                                          |

| **Function**    | createApplication()                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
|-----------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Store a new application.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Parameters**  | ApplicationRequest \$request                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| **Return**      | Redirection to index page with success or failure prompt                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| **Logic**       | Initially ApplicationRequest class validates the request If the request is validated, a new Application is created with the request data Additional attributes are added to application containment_id : the containment code of the first containment of the building. Building is retrieved by the help of house_number. applicant_name,applicant_contact,applicant_gender,: These are stored from the request if available else they are set from the owner information of the building. The owner information is retrieved from the owners relation through the building verified_status:true when the house is already registered in the system and false when the house is not registered in the system If the owner info and building info is missing, it is updated if there is new data entered applilcation_date : The current date is set here user_id: the currently logged in user id is set here There’s an autofill function in the form for applicant information so that if the applicant is the owner themselves, they can autofill applicant information. If the autofill is selected, the owners information are set to the applicants information Since there are three status flags for the application i.e emptying_status,feedback_status and sludge_collection_status. The default values of these columns are set to false in the database itself so there’s no need of setting these flags here manually. If the system runs into any error during the process, the process is halted, redirected to the create form along with the old inputs with an appropriate message. The application created is force deleted. |

| **Function**    | updateApplication                                                                                   |
|-----------------|-----------------------------------------------------------------------------------------------------|
| **Description** | Update application.                                                                                 |
| **Parameters**  | ApplicationRequest \$request, \$id                                                                  |
| **Return**      | Redirection to index page with success or failure prompt                                            |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php                                                          |
| **Logic**       | The application with the specified id is updated similar to the process of creating an application. |

| **Function**    | getApplicationHistory()                                                                                                                                                                                                                       |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Retrieve application history.                                                                                                                                                                                                                 |
| **Parameters**  | \$id                                                                                                                                                                                                                                          |
| **Return**      | Redirection to history page on successful retrieval or redirection to index page with failure prompt on unsuccessful retrieval                                                                                                                |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php                                                                                                                                                                                                    |
| **Logic**       | Application with the specified id is retrieved. The Revisions table is queried with following conditions: revisionable_type is equal to get_class(\$application) revisionable_id is equal to id of the application Sorted by the created_date |

| **Function**    | export()                                                               |
|-----------------|------------------------------------------------------------------------|
| **Description** | Export applications.                                                   |
| **Parameters**  | Request \$request                                                      |
| **Return**      | CSV file containing applications                                       |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php                             |
| **Logic**       | All the filter values are retrieved if any. Export with filtered data. |

| **Function**    | fethMonthlyReport()                                 |
|-----------------|-----------------------------------------------------|
| **Description** | Export applications report month wise in pdf format |
| **Parameters**  | \$year, \$month                                     |
| **Return**      | PDF file containing applications report             |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php          |

| **Function**    | getApplicationReport()                     |
|-----------------|--------------------------------------------|
| **Description** | Export applications report in pdf format   |
| **Parameters**  | \$id                                       |
| **Return**      | PDF file containing applications report    |
| **Source**      | App\\Services\\Fsm\\ApplicationService.php |

### Application Customer PII Security

The Application module protects customer_name, customer_gender and customer_contact in fsm.applications. These columns are the Application's customer/owner snapshot and are encrypted before database storage. The Application module uses the same PiiEncryptionService, active key version and external key provider as the Building module; it does not require or generate a separate encryption key.

Applicant information is intentionally separate:

-   customer_name, customer_gender and customer_contact are encrypted PII and use the controlled Application PII workflow.
-   applicant_name, applicant_gender and applicant_contact are an independent operational snapshot used by Emptying, Feedback, reports and other downstream FSM workflows.
-   Customer and applicant are not assumed to be the same person.
-   Applicant values are copied from customer values only when the user explicitly selects Same as Owner.
-   Normal Application edits do not silently overwrite the Building owner from the Application customer snapshot or overwrite the customer snapshot from applicant data.

#### Application PII Data States

| State | Handling |
|-------|----------|
| At rest | Customer PII is stored as versioned AES-256-GCM ciphertext such as `enc:v1:payload` in fsm.applications. The encryption key remains outside the project/database. |
| In transit | Authorized plaintext must use HTTPS in production. Application DataTable, owner lookup, show, edit and dedicated PII export responses use no-store/no-cache protection. |
| In use | Plaintext exists temporarily in PHP request memory and in an authorized browser response. The session stores grant metadata only; it does not store customer plaintext or the encryption key. |
| Locked | Customer fields use the fixed `********` mask. Locked DataTable and BIN lookup responses do not send customer/owner plaintext or ciphertext. |

#### Application PII Components

| Component | Location | Responsibility |
|-----------|----------|----------------|
| Application mutators | app\\Models\\Fsm\\Application.php | Encrypt customer_name, customer_gender and customer_contact on assignment and exclude those fields from revision history. |
| PiiEncryptionService | app\\Services\\PiiEncryptionService.php | Provides shared versioned encryption/decryption, randomized ciphertext and double-encryption prevention. |
| ApplicationCustomerPiiPresenter | app\\Services\\ApplicationCustomerPiiPresenter.php | Returns fixed masks, controlled plaintext values and authorized customer-name matching. |
| ApplicationPiiAccessService | app\\Services\\ApplicationPiiAccessService.php | Stores and validates the Application-specific short-lived list, view, edit and owner_lookup scopes. |
| ApplicationPiiAccessController | app\\Http\\Controllers\\Fsm\\ApplicationPiiAccessController.php | Performs Application permissions and current-password verification before granting reveal mode. |
| ApplicationCustomerPiiExportService | app\\Services\\ApplicationCustomerPiiExportService.php | Validates selected BIN files, applies Application row-level visibility and builds allowlisted PII export rows. |
| ApplicationCustomerPiiExportController | app\\Http\\Controllers\\Fsm\\ApplicationCustomerPiiExportController.php | Enforces export permissions, administrative full-export rules, password re-entry, audit and secure streaming. |

#### Application PII Access Flow

ApplicationPiiAccessController::unlockList() requires List Applications, View Application Customer PII and Unlock Application Customer PII. It verifies the user's current login password and creates a five-minute Application-specific privileged session.

The Application grant is separate from the Building Owner PII grant. Revealing Building PII does not automatically reveal Application PII, and revealing Application PII does not unlock the Building module.

| Scope | Granted when | Allows |
|-------|--------------|--------|
| list | Password and Application PII permissions are valid | Plaintext customer values in the Application DataTable and customer-name filtering. |
| view | Password and Application PII permissions are valid | Plaintext customer values on Application details. |
| edit | User additionally has Edit Application | Plaintext customer values and editing on the Application edit page. |
| owner_lookup | User additionally has Add Application | Building owner lookup on Add Application after Street/House/BIN selection. |

ApplicationPiiAccessService::isUnlocked() checks the current user, expiry, requested scope and resource on the server. The grant is removed on manual lock or expiration. The list page automatically locks when the remaining timer expires.

Failure at login authentication, module permission, PII permission, password re-entry, user ownership, scope, resource or expiry checks blocks plaintext access.

#### Application DataTable and Customer Name Filter

ApplicationService::getAllApplications() selects only DataTable columns and disables automatic eager loading so unused relationships and PII fields cannot leak through JSON serialization.

ApplicationService::getDatatable(Request \$request, bool \$revealCustomerPii) controls presentation:

-   Locked mode returns `********` for customer_name and customer_contact.
-   Revealed mode decrypts only the customer fields required by the DataTable.
-   customer_gender and applicant fields are not added to the DataTable response when they are not required.
-   The customer-name filter is available only while Application PII reveal mode is active.
-   Randomized encryption cannot support SQL LIKE on customer_name. Authorized filtering reads names with a cursor, decrypts them temporarily in PHP, gathers matching Application IDs and then filters the main query.
-   Existing Service Provider and Treatment Plant row-level visibility continues to limit the Application query.
-   Each revealed DataTable request is audited without storing customer values.

#### Add Application Owner Lookup

When the user selects Street Name / Street Code and House Number / BIN, ApplicationController::buildingDetails() checks Add Application permission and the owner_lookup scope on every request.

-   If authorized, ApplicationService::getBuildingDetails() explicitly decrypts the related Building owner through OwnerPiiPresenter and returns owner name, gender and contact for form confirmation.
-   If locked, expired or unauthorized, customer_name, customer_gender and customer_contact are null and the owner_pii_locked/customer_pii_locked flags are true. Neither ciphertext nor partially masked owner values are sent.
-   The create page displays a fixed mask before authorized lookup.
-   The JavaScript unlock flag and disabled fields are presentation controls only; the controller remains the security boundary.
-   Authorized lookups audit the BIN and result, never the decrypted owner values.

When an Application is stored, missing customer values may be populated server-side from the current Building owner snapshot and then encrypted by Application model mutators. This server-side copy does not make applicant and customer permanently linked. Later edits treat the Application customer snapshot, applicant snapshot and Building owner as independent records.

#### Application Show, Edit and Update

ApplicationService::getShowFormFields() and getEditFormFields() use ApplicationCustomerPiiPresenter:

-   Show returns fixed masks unless a valid view scope exists.
-   Edit returns fixed masks and disables customer inputs unless a valid edit scope exists.
-   ApplicationController::update() rejects crafted customer PII fields with HTTP 403 when edit scope is missing, even if a caller bypasses disabled browser inputs.
-   Authorized plaintext views and customer PII changes are audited.
-   Application model mutators encrypt authorized changes before database storage.
-   customer_name, customer_gender and customer_contact are excluded from revision history.

#### Application Customer PII Export

The normal operational Application export remains separate from the dedicated customer PII export. Any export or report that requires plaintext customer PII must use the dedicated password-confirmed endpoint; operational exports and reports must not serialize either decrypted customer values or encrypted `enc:v...` payloads.

| **Function**    | ApplicationCustomerPiiExportController::export(Request \$request)                                                                                                                    |
|-----------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Streams an allowlisted CSV containing application_id, bin, customer_name, customer_gender, customer_contact and status after authorization and password re-entry.                     |
| **Parameters**  | application_export_mode, application_bin_csv for bin_list, confirm_application_export_all for all mode, and application_export_password                                              |
| **Return**      | UTF-8 CSV download with no-store headers                                                                                                                                               |
| **Remarks**     | Full export requires Super Admin, Municipality - Super Admin or Municipality - IT Admin. A selected BIN export is restricted by normal Application row-level visibility.              |

Export modes:

| Mode | Behavior |
|------|----------|
| bin_list | Requires a CSV with a bin header. BINs are normalized, validated and deduplicated. All authorized Applications matching each BIN are exported. |
| all | Requires an approved administrative role and a separate explicit confirmation. Streams all Applications inside the user's authorized row scope. |

The export service returns found for exported records and not_found_or_unauthorized when it cannot disclose whether a requested BIN exists outside the user's scope. A missing or invalid BIN file never falls back to a full export. Full exports use cursor() so the entire decrypted dataset is not retained in PHP memory. Spreadsheet formula prefixes are escaped before values are written.

#### Application PII Audit and Cache Protection

Application PII events use app\\Services\\OwnerPiiAuditService.php and auth.owner_pii_audit_logs. Events include password success/failure, reveal grants, list/detail/edit views, owner lookup, authorized/denied updates, lock, export success, validation failure and permission denial.

Audit context may include user, Application id, BIN, surface, export mode and counts. Application PII audit calls do not record applicant values. `OwnerPiiAuditService` automatically removes customer/owner values, passwords, encryption keys, ciphertext, payloads, sessions and tokens from audit context.

ApplicationController applies pii.no-cache middleware to getData, buildingDetails, show and edit. Dedicated PII export responses also include no-store, private, max-age=0, Pragma no-cache, expiry and nosniff headers.

#### Application PII Permissions and Routes

Permissions created by database\\seeders\\ApplicationPiiPermissionsSeeder.php:

-   View Application Customer PII
-   Unlock Application Customer PII
-   Export Application Customer PII

Routes under the authenticated fsm group:

| Method | Route | Name | Purpose |
|--------|-------|------|---------|
| POST | fsm/application/customer-pii/unlock-list | application-pii.unlock-list | Password-confirmed five-minute Application reveal; rate limited to five attempts per minute. |
| POST | fsm/application/customer-pii/lock | application-pii.lock | Immediately removes the Application privileged session. |
| POST | fsm/application/customer-pii/export | application-pii.export | Password-confirmed dedicated customer PII export; rate limited to five attempts per minute. |
| GET | fsm/application/getBuildingDetails | application.get-building-details | BIN lookup with server-side owner_lookup scope enforcement. |

#### Application PII Configuration and Commands

Application customer PII uses config\\pii.php and the same environment configuration as Building Owner PII:

-   PII_KEY_PROVIDER
-   PII_ACTIVE_KEY_VERSION
-   PII_KEY_V1_FILE
-   PII_ALLOW_LEGACY_PLAINTEXT
-   PII_AUDIT_ENABLED
-   PII_EXPORT_MAX_BINS
-   PII_EXPORT_MAX_FILE_KB

The external key must remain outside the project, database and source control. If the configured key is missing or incorrect, encrypted Application customer values cannot be decrypted. Key backup and recovery therefore protect Building, Application and other modules using the same active PII key.

| Command | Purpose |
|---------|---------|
| php artisan pii:check-configuration | Validates the active key and encryption round trip without displaying key material. |
| php artisan pii:encrypt-existing-applications --dry-run | Counts plaintext customer fields without changing fsm.applications. |
| php artisan pii:encrypt-existing-applications | Encrypts only non-null customer fields that are not already encrypted, in transactional chunks. |
| php artisan pii:verify-application-encryption | Verifies that all non-null Application customer fields are encrypted and decryptable without printing values. |

Recommended migration sequence:

1. Back up the database and external PII key securely.
2. Run pii:check-configuration.
3. Run pii:encrypt-existing-applications --dry-run and review the counts.
4. Run pii:encrypt-existing-applications.
5. Run pii:verify-application-encryption.
6. Keep PII_ALLOW_LEGACY_PLAINTEXT=false after all module backfills pass verification, then clear the configuration cache.

#### Application PII Tests

The main automated tests are located in:

-   tests\\Unit\\ApplicationCustomerPiiTest.php
-   tests\\Unit\\ApplicationOwnerPiiModeTest.php
-   tests\\Unit\\ApplicationCustomerPiiExportServiceTest.php
-   tests\\Unit\\ApplicationCustomerPiiExportControllerTest.php
-   tests\\Unit\\PiiEncryptionServiceTest.php
-   tests\\Unit\\OwnerPiiAuditServiceTest.php
-   tests\\Unit\\PreventPiiResponseCachingTest.php

### Emptying

Tables

The Emptying Module uses the following tables:

-   Fsm.emptyings: stores the primary information of the emptying
-   Fsm.service_providers:retrieves information of service provider
-   Building_info.buildings: retrieves informations of required building
-   Fsm.applications: retrieves information of application
-   Auth.users: retrieves information of user
-   Fsm.desludging_vechicles: retrieves information of desluding vechicle
-   Fsm.sludge_collections: retrieves information of sludge collection
-   Fsm.treatment_plants: retrieves information of treatment plant

The corresponding tables have their respective models that are named in Pascal Case in singular form. The emptying module is located at app\\Http\\Controllers\\Fsm\\EmptyingController.php.

Views

-   resources/views/fsm/emptying/index.blade.php: lists applications.
-   resources/views/fsm/emptying/create.blade.php: opens form and calls partial-form for form contents
-   resources/views/fsm/emptying/edit.blade.php: opens form and calls partial-form for form contents
-   resources/views/fsm/emptying/history.blade.php: lists all past edits of the record
-   resources/views/layouts/show.blade.php: view detailed information of the record
-   resources/views/layouts/partial-form.blade.php: the form template

Models

The models contain the connection between the model and the table defined by

*\$table = ‘schema_name.table_name’* as well as the primary key defined by

*primaryKey= ‘primary_key_field’*

For history functionality, the \$revisionCreationsEnabled is set true and we use

use Venturecraft\\Revisionable\\RevisionableTrait;

For soft deletes functionality, we use

*use Illuminate\\Database\\Eloquent\\SoftDeletes;*

For Setting belongsTo and HasMany relationships, we use:

\<-- CODE SNIPPET --\>

*use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;*

*belongsToMany('model_relative_path', 'relational_table', primary_key, 'primary_key of relationship')*

*belongsTo('model_name', 'pk of first table', 'pk of Second table'*

*use Illuminate\\Database\\Eloquent\\Relations\\HasMany;*

Emptying Model

Location: app\\Models\\Fsm\\Emptying.php

There are multiple relationships defined in the Application Model. They are:

-   service_provider : belongsTo
-   buildings : belongsTo
-   application : belongsTo
-   user : belongsTo
-   vacutug : hasOne
-   sludge_collection : hasOne
-   treatment_plant : belongsTo
-   getHouseNumberAttribute : application to building
-   employee_info_driver : belongsTo
-   employee_info_emptier : belongsTo

| **Function**    | getHouseNumberAttribute()                                      |
|-----------------|----------------------------------------------------------------|
| **Description** | Accessor function to access and append house_number attribute. |
| **Parameters**  | none                                                           |
| **Return**      | house_number:character varying                                 |
| **Source**      | app\\Http\\Controllers\\Fsm\\EmptyingController.php            |

Emptying Controller

path: app\\Http\\Controllers\\Fsm\\EmptyingController.php

The controller’s main function is to provide the connection between the calling route and its subsequent function written in the Service Class.

The basic classes of the controller are:

| **Function**    | \__construct()                                                         |
|-----------------|------------------------------------------------------------------------|
| **Description** | Initializes authentication, permissions and the service class instance |
| **Parameters**  | Service class instance(EmptyingService)                                |
| **Return**      | null                                                                   |
| **Source**      | app\\Http\\Controllers\\Fsm\\EmptyingController.php                    |

| **Function**    | index()                                                                                    |
|-----------------|--------------------------------------------------------------------------------------------|
| **Description** | Returns the index.blade.php page with links and filter form fields from the service class. |
| **Parameters**  | null                                                                                       |
| **Return**      | view('fsm.emptying.index',compact('filterFormFields','exportBtnLink'))                     |
| **Source**      | app\\Http\\Controllers\\Fsm\\EmptyingController.php                                        |

| **Function**    | create()                                                                                                                                                                                                                                        |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns the form to create a new emptying with values fetched from the database.                                                                                                                                                                |
| **Parameters**  | null                                                                                                                                                                                                                                            |
| **Return**      | view('fsm.emptying.create',[  'formAction' =\> \$this-\>emptyingService-\>getCreateFormAction(),  'formFields' =\> \$this-\>emptyingService-\>getCreateFormFields(\$id),  'indexAction' =\> url()-\>previous(),  'application_id' =\> \$id  ]); |
| **Source**      | app\\Http\\Controllers\\Fsm\\EmptyingController.php                                                                                                                                                                                             |

| **Function**    | getData                                                                                                                           |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Calls the getDatatable function of the service class and handles the process of fetching emptying data and respective information |
| **Parameters**  | Request \$request                                                                                                                 |
| **Return**      | Returns data of emptying data and respective information for datatable                                                            |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php                                                                                           |
| **Remarks**     | getDatatable(Request \$request) Service Class Function Name                                                                       |

| **Function**    | store()                                                                   |
|-----------------|---------------------------------------------------------------------------|
| **Description** | Calls the service class that handles the process of creating new emptying |
| **Parameters**  | EmptyingRequest \$request                                                 |
| **Return**      | Redirection to index page with success or failure prompt                  |
| **Source**      | app\\Http\\Controllers\\Fsm\\EmptyingController.php                       |
| **Remarks**     | createEmptying(\$request)  Service Class Function Name                    |

| **Function**    | show()                                                                            |
|-----------------|-----------------------------------------------------------------------------------|
| **Description** | Returns the page with detailed information of the emptying                        |
| **Parameters**  | \$id                                                                              |
| **Return**      | view('layouts.show',compact('page_title','formFields','emptying','indexAction')); |
| **Source**      | app\\Http\\Controllers\\Fsm\\EmptyingController.php                               |

| **Function**    | edit()                                                                                             |
|-----------------|----------------------------------------------------------------------------------------------------|
| **Description** | Returns the edit form page displaying pre-existing individual emptying data as well                |
| **Parameters**  | \$id                                                                                               |
| **Return**      | view('fsm.emptying.edit',compact('page_title','formFields','formAction','indexAction','emptying')) |
| **Source**      | app\\Http\\Controllers\\Fsm\\EmptyingController.php                                                |

| **Function**    | update()                                                                         |
|-----------------|----------------------------------------------------------------------------------|
| **Description** | Calls the service class that handles the process of updating application details |
| **Parameters**  | EmptyingRequest \$request , \$id                                                 |
| **Return**      | Redirection to index page with success or failure prompt                         |
| **Source**      | app\\Http\\Controllers\\Fsm\\EmptyingController.php                              |
| **Remarks**     | updateEmptying(\$request,\$id) (service class function)                          |

| **Function**    | export()                                                                |
|-----------------|-------------------------------------------------------------------------|
| **Description** | Calls the service class that handles the process of exporting emptyings |
| **Parameters**  | Request \$request                                                       |
| **Return**      | CSV file containing emptyings                                           |
| **Source**      | app\\Http\\Controllers\\Fsm\\EmptyingController.php                     |
| **Remarks**     | export(\$request) (service class function)                              |

| **Function**    | history()                                                                                                                      |
|-----------------|--------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Calls the service class that handles the process of fetching emptying history of specified emptying                            |
| **Parameters**  | \$id                                                                                                                           |
| **Return**      | Redirection to history page on successful retrieval or redirection to index page with failure prompt on unsuccessful retrieval |
| **Source**      | app\\Http\\Controllers\\Fsm\\EmptyingController.php                                                                            |

| **Function**    | destroy()                                                |
|-----------------|----------------------------------------------------------|
| **Description** | Soft deletes an emptying                                 |
| **Parameters**  | \$id                                                     |
| **Return**      | Redirection to index page with success or failure prompt |
| **Source**      | app\\Http\\Controllers\\Fsm\\EmptyingController.php      |

Emptying Service

path: app\\Services\\Fsm\\EmptyingService.php

The service class contains all the business logic and provides all the services related to emptying.

| **Function**    | \__construct()                                                                                                                                                                                                                                                                                                                             |
|-----------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Constructor that initializes following fields: createPartialForm : name of view for create partial form createFormAction : route for posting create form indexAction : route of the index page createRoute : route of the create page exportRoute : route of the export page filterFormFields : array of FormField objects for filter form |
| **Parameters**  | null                                                                                                                                                                                                                                                                                                                                       |
| **Return**      | null                                                                                                                                                                                                                                                                                                                                       |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php                                                                                                                                                                                                                                                                                                    |

| **Function**    | getCreateFormFields()                   |
|-----------------|-----------------------------------------|
| **Description** | Get form fields for creating emptyings. |
| **Parameters**  | null                                    |
| **Return**      | createFormFields                        |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php |

| **Function**    | getShowFormFields()                                                            |
|-----------------|--------------------------------------------------------------------------------|
| **Description** | Get form fields for showing emptyings after initializing the show form fields. |
| **Parameters**  | null                                                                           |
| **Return**      | showFormFields                                                                 |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php                                        |

| **Function**    | getEditFormFields()                                                            |
|-----------------|--------------------------------------------------------------------------------|
| **Description** | Get form fields for editing emptyings after initializing the edit form fields. |
| **Parameters**  | \$emptying                                                                     |
| **Return**      | editFormFields                                                                 |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php                                        |

| **Function**    | getCreateFormAction()                   |
|-----------------|-----------------------------------------|
| **Description** | Get action/route for create form.       |
| **Parameters**  | null                                    |
| **Return**      | createFormAction                        |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php |

| **Function**    | getIndexAction()                              |
|-----------------|-----------------------------------------------|
| **Description** | Get action/route for index page of Emptyings. |
| **Parameters**  | null                                          |
| **Return**      | indexAction                                   |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php       |

| **Function**    | getCreateRoute()                               |
|-----------------|------------------------------------------------|
| **Description** | Get action/route for create page of Emptyings. |
| **Parameters**  | null                                           |
| **Return**      | createRoute                                    |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php        |

| **Function**    | getExportRoute()                          |
|-----------------|-------------------------------------------|
| **Description** | Get action/route for exporting Emptyings. |
| **Parameters**  | null                                      |
| **Return**      | exportRoute                               |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php   |

| **Function**    | getEditFormAction                       |
|-----------------|-----------------------------------------|
| **Description** | Get action/route for edit form.         |
| **Parameters**  | \$emptying                              |
| **Return**      | editFormAction                          |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php |

| **Function**    | getFilterFormFields()                   |
|-----------------|-----------------------------------------|
| **Description** | Get form fields for filter.             |
| **Parameters**  | null                                    |
| **Return**      | filterFormFields                        |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php |

| **Function**    | getAllEmptyings                         |
|-----------------|-----------------------------------------|
| **Description** | Get all the emptyings.                  |
| **Parameters**  | null                                    |
| **Return**      | Collection of emptyings                 |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php |

| **Function**    | getDatatable()                          |
|-----------------|-----------------------------------------|
| **Description** | Get Datatables of Emptyings.            |
| **Parameters**  | Request \$request                       |
| **Return**      | DataTables                              |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php |

| **Function**    | createEmptying                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Store a new emptying.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| **Parameters**  | EmptyingRequest \$request                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| **Return**      | Redirection to index page with success or failure prompt                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Logic**       | Initially EmptyingRequest class validates the request If the request is validated, a new Emptying is created with the request data Application is retrieved from the application_id field. The flag emptying_status is checked for this application. If the flag is true, its redirected with error as emptying for that application is already performed. Additional attributes are added to emptying last_emptying_date & emptied_date: set to current date next_emptying_date : a year is added to current date service_provider_id : service provider id is retrieved from the application user_id : current user id is set All image uploads are validated with the help of Intervention package First, the extension of the uploaded images are checked If the extension is valid, the images are compressed to 50% quality in order to decrease file size. The images are then stored in the storage. receipt_image & house_image field are set with the uploaded images of respective filename If the system runs into any error during the process, the process is halted, redirected to the create form along with the old inputs with an appropriate message. The emptying created is force deleted. |

| **Function**    | updateEmptying()                                                                           |
|-----------------|--------------------------------------------------------------------------------------------|
| **Description** | Update emptying.                                                                           |
| **Parameters**  | EmptyingRequest \$request, \$id                                                            |
| **Return**      | Redirection to index page with success or failure prompt                                   |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php                                                    |
| **Logic**       | The emptying with the specified id is updated similar to the process of creating emptying. |

| **Function**    | getEmptyingHistory()                                                                                                                                                                                                                  |
|-----------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Retrieve emptying history.                                                                                                                                                                                                            |
| **Parameters**  | \$id                                                                                                                                                                                                                                  |
| **Return**      | Redirection to history page on successful retrieval or redirection to index page with failure prompt on unsuccessful retrieval                                                                                                        |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php                                                                                                                                                                                               |
| **Logic**       | Emptying with the specified id is retrieved. The Revisions table is queried with following conditions: revisionable_type is equal to get_class(\$emptying) revisionable_id is equal to id of the emptying Grouped by the created_date |

| **Function**    | export()                                                               |
|-----------------|------------------------------------------------------------------------|
| **Description** | Export emptyings.                                                      |
| **Parameters**  | Request \$request                                                      |
| **Return**      | CSV file containing emptyings                                          |
| **Source**      | app\\Services\\Fsm\\EmptyingService.php                                |
| **Logic**       | All the filter values are retrieved if any. Export with filtered data. |

### Sludge Collection

Tables

The Sludge Collection Module uses the following tables:

-   Fsm.sludge_collections: stores primary information of the sludge collection
-   Fsm.treatment_plants: retrieves the treatment plant information
-   Fsm.service_providers: retrieves the service provider information
-   Fsm.applications: retrieves application information
-   Fsm.emptyings: retrieves emptying information
-   Fsm.desludging_vehicles: retrieves desludging vechicle information

The corresponding tables have their respective models that are named in Pascal Case in singluar form. The feedback module is located at app\\Http\\Models\\Fsm\\Sludge Collection.

Views

All views used by this module is stored in resources\\views\\building-info

-   sludge-collection.index: lists sludge collection records.
-   sludge-collection.create: opens form and calls partial-form for form contents
-   sludge-collection.partial-form: creates form content for addition of new sludge collections
-   sludge-collection.edit: opens form and calls partial-form for form contents
-   sludge-collection.edit-partial-form: creates form content for editing of sludge collections
-   sludge-collection.history: lists all past edits of the record
-   sludge-collection.show: displays all attributes of particular record

Models

The models contain the connection between the model and the table defined by

*\$table = ‘schema_name.table_name’* as well as the primary key defined by

*primaryKey= ‘primary_key_field’*

For history functionality, the revisionCreationsEnabled is set true and we use *RevisionableTrait(use Venturecraft\\Revisionable\\RevisionableTrait);*

For soft deletes functionality, we

*use Illuminate\\Database\\Eloquent\\SoftDeletes;*

For Setting belongsTo and HasMany relationships, we use:

\<-- CODE SNIPPET --\>

*use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;*

*belongsToMany('model_relative_path', 'relational_table', primary_key, 'primary_key of relationship')*

*belongsTo('model_name', 'pk of first table', 'pk of Second table'*

*use Illuminate\\Database\\Eloquent\\Relations\\HasMany;*

Sludge Collection Model

Location: app\\Models\\Fsm\\SludgeCollection.php

There are multiple relationships defined in the Application Model. They are:

-   treatmentplants: belongsTo
-   serviceProvider: belongsTo
-   applications: belongsTo
-   emptyings: belongsTo
-   vacutug: belongsTo

SludgeCollectionController

Controller: app\\Http\\Controllers\\Fsm\\SludgeCollectionController.php

The basic classes of the controller are:

| **Function**    | \__construct()                                                         |
|-----------------|------------------------------------------------------------------------|
| **Description** | Initializes authentication, permissions and the service class instance |
| **Parameters**  | null                                                                   |
| **Return**      | null                                                                   |
| **Source**      | app\\Http\\Controllers\\Fsm\\SludgeCollectionController.php            |

| **Function**    | index()                                                                                  |
|-----------------|------------------------------------------------------------------------------------------|
| **Description** | Returns the index.blade.php page with dropdown values fetched from the database.         |
| **Parameters**  | null                                                                                     |
| **Return**      | view('fsm.sludge-collection.index', compact('page_title', 'treatmentPlants','servprov')) |
| **Source**      | app\\Http\\Controllers\\Fsm\\SludgeCollectionController.php                              |

| **Function**    | create()                                                                                                                                                                                                                                          |
|-----------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns the form to create a new sludge collection with values fetched from the database.                                                                                                                                                         |
| **Parameters**  | null                                                                                                                                                                                                                                              |
| **Return**      | view('fsm.sludge-collection.create', compact('page_title', 'treatmentPlants', 'serviceProviders', 'applications', 'VacutugTypes','sludgeCollection','application_id','service_provider_id','treatment_plant_id','vacutug_id','volume_of_sludge')) |
| **Source**      | app\\Http\\Controllers\\Fsm\\ApplicationController.php                                                                                                                                                                                            |

| **Function**    | getData()                                                                     |
|-----------------|-------------------------------------------------------------------------------|
| **Description** | Returns the datatable with sludge collection data and respective informations |
| **Parameters**  | Request \$request                                                             |
| **Return**      | Datatables                                                                    |
| **Source**      | app\\Http\\Controllers\\Fsm\\SludgeCollectionController.php                   |

| **Function**    | store()                                                      |
|-----------------|--------------------------------------------------------------|
| **Description** | Handles the process of storing sludge collection data        |
| **Parameters**  | SludgeCollectionRequest \$request                            |
| **Return**      | Success or error message.                                    |
| **Source**      | app\\Http\\Controllers\\Fsm\\SludgeCollectionController.php  |

| **Function**    | show()                                                                                                             |
|-----------------|--------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns the page displaying individual sludge collection data                                                      |
| **Parameters**  | \$id                                                                                                               |
| **Return**      | view('fsm.sludge-collection.show', compact('page_title', 'sludgeCollection', 'serviceProvider', 'treatmentPlant')) |
| **Source**      | app\\Http\\Controllers\\Fsm\\SludgeCollectionController.php                                                        |

| **Function**    | edit()                                                                                                                                                                                        |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns the edit form page displaying pre-existing individual sludge collection data as well                                                                                                  |
| **Parameters**  | \$id                                                                                                                                                                                          |
| **Return**      | view('fsm.sludge-collection.edit', compact('page_title', 'sludgeCollection', 'treatmentPlants', 'serviceProviders', 'applications', 'VacutugTypes','treatment_plant_id','volume_of_sludge'))  |
| **Source**      | app\\Http\\Controllers\\Fsm\\SludgeCollectionController.php                                                                                                                                   |

| **Function**    | update()                                                     |
|-----------------|--------------------------------------------------------------|
| **Description** | Handles the process of updating sludge collection data       |
| **Parameters**  | SludgeCollectionRequest \$request                            |
| **Return**      | Redirection with success/failure message                     |
| **Source**      | app\\Http\\Controllers\\Fsm\\SludgeCollectionController.php  |

| **Function**    | history()                                                    |
|-----------------|--------------------------------------------------------------|
| **Description** | Retrieves the sludge collection history                      |
| **Parameters**  | \$id                                                         |
| **Return**      | Redirection with success/failure message                     |
| **Source**      | app\\Http\\Controllers\\Fsm\\SludgeCollectionController.php  |

| **Function**    | export()                                                     |
|-----------------|--------------------------------------------------------------|
| **Description** | Handles the process of exporting sludge collection data      |
| **Parameters**  | null                                                         |
| **Return**      | CSV file containing sludge collection data                   |
| **Source**      | app\\Http\\Controllers\\Fsm\\SludgeCollectionController.php  |

| **Function**    | destroy()                                                    |
|-----------------|--------------------------------------------------------------|
| **Description** | Handles the process of deleting sludge collection data       |
| **Parameters**  | \$id                                                         |
| **Return**      | Redirection with success/failure message                     |
| **Source**      | app\\Http\\Controllers\\Fsm\\SludgeCollectionController.php  |

### Feedback

Tables

The Feedbacks Module uses the following tables:

-   feedbacks: stores primary information of the feedback
-   users: retrieves the user information
-   applications: retrieves the application information

The corresponding tables have their respective models that are named in Pascal Case in singluar form. The feedback module is located at app\\Http\\Models\\Fsm\\Feedback.

Views

All views used by this module is stored in resources\\views\\building-info

-   feedbacks.index: lists feedback records.
-   feedbacks.create: opens form contents
-   feedbacks.edit: opens form contents
-   feedbacks.show: displays all attributes of particular record

Models

The models contain the connection between the model and the table defined by

*\$table = ‘schema_name.table_name’* as well as the primary key defined by

*primaryKey= ‘primary_key_field’*

Feedback Model

Location: app\\Models\\Fsm\\Feedback.php

There is a relationship defined in the Feedback Model.

Application : belongsTo

Feedback Controller

Controller: app\\Http\\Controllers\\Fsm\\FeedbackController.php

The basic classes of the controller are:

| **Function**    | \__construct()                                                         |
|-----------------|------------------------------------------------------------------------|
| **Description** | Initializes authentication, permissions and the service class instance |
| **Parameters**  | null                                                                   |
| **Return**      | null                                                                   |
| **Source**      | app\\Http\\Controllers\\Fsm\\FeedbackController.php                    |

| **Function**    | index()                                                                          |
|-----------------|----------------------------------------------------------------------------------|
| **Description** | Returns the index.blade.php page with dropdown values fetched from the database. |
| **Parameters**  | null                                                                             |
| **Return**      | view('fsm.feedbacks.index', compact('page_title', 'wards', 'feedbackYears'))     |
| **Source**      | app\\Http\\Controllers\\Fsm\\FeedbackController.php                              |

| **Function**    | getData()                                                            |
|-----------------|----------------------------------------------------------------------|
| **Description** | Returns the datatable with feedback data and respective informations |
| **Parameters**  | Request \$request                                                    |
| **Return**      | Datatables                                                           |
| **Source**      | app\\Http\\Controllers\\Fsm\\FeedbackController.php                  |

| **Function**    | getAllFeedbackYearsDate()                                                              |
|-----------------|----------------------------------------------------------------------------------------|
| **Description** | Get the minimum and maximum years from the 'created_at' field of the ‘feedbacks' table |
| **Parameters**  | \$ward                                                                                 |
| **Return**      | The object containing the minimum and maximum years.                                   |
| **Source**      | app\\Http\\Controllers\\Fsm\\FeedbackController.php                                    |

| **Function**    | store()                                              |
|-----------------|------------------------------------------------------|
| **Description** | Handles the process of storing feedback data         |
| **Parameters**  | Request \$request                                    |
| **Return**      | Success or error message.                            |
| **Source**      | app\\Http\\Controllers\\Fsm\\FeedbackController.php  |

| **Function**    | show()                                                        |
|-----------------|---------------------------------------------------------------|
| **Description** | Returns the page displaying individual feedback data          |
| **Parameters**  | \$id                                                          |
| **Return**      | view('fsm.feedbacks.show', compact('page_title', 'feedback')) |
| **Source**      | app\\Http\\Controllers\\Fsm\\FeedbackController.php           |

| **Function**    | edit()                                                                                            |
|-----------------|---------------------------------------------------------------------------------------------------|
| **Description** | Returns the edit form page displaying pre-existing individual feedback data as well               |
| **Parameters**  | \$id                                                                                              |
| **Return**      | view('fsm.feedbacks.edit', compact('page_title', 'feedback', 'application', 'satisfactionScale')) |
| **Source**      | app\\Http\\Controllers\\Fsm\\FeedbackController.php                                               |

| **Function**    | update()                                             |
|-----------------|------------------------------------------------------|
| **Description** | Handles the process of updating feedback data        |
| **Parameters**  | Request \$request                                    |
| **Return**      | Redirection with success/failure message             |
| **Source**      | app\\Http\\Controllers\\Fsm\\FeedbackController.php  |

| **Function**    | export()                                             |
|-----------------|------------------------------------------------------|
| **Description** | Handles the process of exporting feedback data       |
| **Parameters**  | null                                                 |
| **Return**      | CSV file containing feedback data                    |
| **Source**      | app\\Http\\Controllers\\Fsm\\FeedbackController.php  |

| **Function**    | destroy()                                            |
|-----------------|------------------------------------------------------|
| **Description** | Handles the process of deleting feedback data        |
| **Parameters**  | \$id                                                 |
| **Return**      | Redirection with success/failure message             |
| **Source**      | app\\Http\\Controllers\\Fsm\\FeedbackController.php  |

### Help Desk

Tables

Help Desks is under FSM module and uses the following table:

help_desks: store the primary information of the help desks

service_providers: retrieves information of service providers

The corresponding tables have their respective models that are named in Pascal Case in singular form. HelpDesk model is located at app\\Models\\Fsm\\.

Views

All views used by this module is stored in resources\\views\\fsm\\help-desks

help-desks.index: lists help desks records.

help-desks.create: opens form and calls partial-form for form contents

help-desks.partial-form: creates form content

help-desks.edit: opens form and calls partial-form for form contents

help-desks.history: lists all past edits of the record

Models

The models contain the connection between the model and the table defined by *\$table = ‘fsm.help_desk’* as well as the primary key defined by primaryKey= ‘id’

HelpDesk Model

Location: app\\Models\\Fsm\\HelpDesk.php

There are multiple relationships defined in the HelpDesk Model. They are:

users: belongsToMany relationship (1 to n relationship)

boot: This is a cascade delete operation, ensuring that associated user records are also removed when a HelpDesk record is deleted.

HelpDesk follows CRUD operations, which usually have the same pattern. You can refer to the "Basic CRUD" **2 - Technical Information/ Basic CRUD** for more information.

HelpDeskRequest

Location: app\\Http\\Requests\\Fsm\\HelpDeskRequest.php)

HelpDeskRequest handles all validation login. It handles validation logic as well as error messages to be displayed.
