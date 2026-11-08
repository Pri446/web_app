Version: V1.1.0

# Building IMS

## Buildings

### Tables

The Building Module uses the following tables

-   buildings: stores primary information of the building. The internal/business identifier remains bin, while the unique public_id UUID is used for browser URLs and route-model binding.
-   building_surveys: stores survey information received from the mobile application
-   build_contains: relational database that connects buildings and containments. Foreign Key: bin and containment_id.
-   owners: relational database that connects buildings and their owner. Foreign_key: bin. The owner_name, owner_gender, owner_contact and nid columns contain application-encrypted PII values.
-   auth.owner_pii_audit_logs: stores append-only security audit metadata for owner PII reveal, view, edit and export actions. PII values, passwords, encryption keys and ciphertext are not stored in the audit context.
-   functional_uses: stores functional use attributes used as dropdowns
-   sanitation_systems: stores sanitation system types attributes used as dropdowns
-   use_categorys: stores Use categorys attributes used as dropdowns
-   water_sources: stores Water sources attributes used as dropdowns
-   wms_links: stores links for WMS (geoserver)

The corresponding tables have their respective models that are named in Pascal Case in singular form. The containment info modules are located at app\\Http\\Models.

### Views

All views used by this module is stored in resources\\views\\building-info\\building

-   building. create: opens form and calls partial-form for form content
-   building.edit: opens form and calls partial-form for form contents
-   building.history: lists all past edits of the record
-   building.index: lists containments records
-   building.partial-form: creates form content for addition of new containment or edit containment of existing building
-   building.show: displays all attributes of particular record

Owner PII behavior in the views:

-   building.index: displays a fixed mask while PII is locked. Authorized users can activate View PII Information after re-entering their current password. The owner-name filter is available only while PII reveal mode is active.
-   building.show: displays fixed masks for owner name, gender, contact number and NID while PII is locked, and displays plaintext only during a valid privileged session.
-   building.edit and building.partial-form: keep owner PII fields locked and masked unless the current user has a valid edit-scoped privileged session.
-   building.index: provides a dedicated Owner PII CSV export for either a validated BIN list or, for approved administrative roles, all owner records.

### Models

Location: app\\Models\\BuildingInfo\\Building.php

There are multiple relationships defined in the Building Model. They are:

-   containments: belongsToMany relationship (n to n relationship)
-   sharedToilets: belongsToMany relationship with PTCT ((n to n relationship)
-   Owners: belongsTo relationship (1 to 1 relationship)
-   StructureType: belongsTo relationship (1 to 1 relationship)
-   Applications: hasMany relationship (1 to n relationship)
-   Roadlines: belongsTo relationship (1 to 1 relationship)
-   functionalUse: belongsTo relationship with PTCT ((n to n relationship)
-   useCategory: belongsTo relationship (1 to 1 relationship)
-   sanitationSystemTypes: belongsTo relationship (1 to 1 relationship)
-   sanitationSystemTechnology: belongsTo relationship (1 to 1 relationship)
-   ctpt: belongsTo relationship (1 to 1 relationship)

The Building model uses app\\Models\\Concerns\\HasPublicId. This trait generates a UUID public_id for newly created records and makes Laravel resolve Building route parameters by public_id instead of bin.

Location: app\\Models\\BuildingInfo\\Owner.php

There are multiple relationships defined in the Owner Model. They are:

-   Buildings: hasMany relationship (1 to n relationship)

The Owner model encrypts the following attributes before they are written to building_info.owners:

-   owner_name
-   owner_gender
-   owner_contact
-   nid

The setOwnerNameAttribute(), setOwnerGenderAttribute(), setOwnerContactAttribute() and setNidAttribute() mutators call PiiEncryptionService. Null values remain null, and a value that already has a valid encrypted envelope is not encrypted a second time. Plaintext is not returned automatically by model serialization; authorized presentation services must explicitly decrypt it.

### BuildingController

Controller: app\\Http\\Controllers\\BuildingInfo\\BuildingController.php

The controller’s main function is to provide the connection between the calling route and its subsequent function written in the Service Class.

The basic classes of the controller are:

| Function    | \__construct()                                                                                                         |
|-------------|------------------------------------------------------------------------------------------------------------------------|
| Description | Initializes authentication, building permissions, no-cache middleware and the Building and Owner PII service instances |
| Parameters  | BuildingStructureService, OwnerPiiPresenter, PiiAccessService and OwnerPiiAuditService                                  |
| Return      | null                                                                                                                   |
| Source      | app\\Http\\Controllers\\BuildingInfo\\BuildingController.php                                                           |

| Function    | getData ()                                                                                                                                                                    |
|-------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Description | Returns the server-side Building DataTable. Owner names are fixed masks while PII is locked and plaintext only while the authenticated user has a valid list-scoped PII session. |
| Parameters  | Request $request                                                                                                                                                              |
| Return      | JSON DataTable response from fetchData($request, $piiUnlocked)                                                                                                                |
| Source      | app\\Http\\Controllers\\BuildingInfo\\BuildingController.php                                                                                                                  |
| Remarks     | Uses an explicit response-column allowlist and audits each privileged DataTable view. The owner-name filter is processed only while reveal mode is active.                     |

| **Function**    | index()                                                                                                                                          |
|-----------------|--------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns the Building list and filter values together with Owner PII reveal permission, unlock state and remaining privileged-session time.        |
| **Parameters**  | null                                                                                                                                             |
| **Return**      | building-info.buildings.index with building filters, piiListUnlocked, canUnlockOwnerPiiList and piiListUnlockSeconds                              |
| **Source**      | app\\Http\\Controllers\\BuildingInfo\\BuildingController.php                                                                                     |

| Function    | create()                                                                                                                                                                                                                                                                                                        |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Description | Returns the form to create new building with dropdown values fetched from the database.                                                                                                                                                                                                                         |
| Parameters  | null                                                                                                                                                                                                                                                                                                            |
| Return      |  return view('building-info.buildings.create', compact(  'page_title',  'bin',  'containment_id',  'water_source',  'structure_type',  'functional_use',  'usecatgsJson',  'sanitation_system_technology',  'containment',  'road_code',  'ward',  'buildingSurvey',  'sewer_code',  'ctpt',  'drain_code'  ))  |
| Source      | app\\Http\\Controllers\\BuildingInfo\\BuildingController.php                                                                                                                                                                                                                                                    |
| Remarks     | function handles the situation where building is created by associating it with a containment .                                                                                                                                                                                                                 |

| **Function**    | store()                                                                   |
|-----------------|---------------------------------------------------------------------------|
| **Description** | Calls the service class that handles the process of storing Building data |
| **Parameters**  | BuildingRequest \$request                                                 |
| **Return**      | Success or error message.                                                 |
| **Source**      | app\\Http\\Controllers\\BuildingInfo\\BuildingController.php              |
| **Remarks**     | storeBuildingData(\$request)                                              |

| **Function**    | show()                                                                                                                                                                  |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns an individual Building page. Owner PII is fully masked by default and decrypted only when the user has a valid view-scoped privileged session for that building. |
| **Parameters**  | Building $building resolved by public_id                                                                                                                               |
| **Return**      | building-info.buildings.show with ownerPii and piiUnlocked                                                                                                              |
| **Source**      | app\\Http\\Controllers\\BuildingInfo\\BuildingController.php                                                                                                            |
| **Remarks**     | A successful plaintext view is recorded by OwnerPiiAuditService.                                                                                                        |

| **Function**    | edit()                                                                                                                                                                         |
|-----------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns the Building edit form. Owner PII inputs are not populated while locked; plaintext is supplied only for a valid edit-scoped privileged session.                         |
| **Parameters**  | Building $building resolved by public_id                                                                                                                                      |
| **Return**      | building-info.buildings.edit with ownerPii, piiUnlocked and piiUnlockSeconds together with the existing Building form data                                                     |
| **Source**      | app\\Http\\Controllers\\BuildingInfo\\BuildingController.php                                                                                                                   |
| **Remarks**     | The edit page automatically relocks after the configured unlock period. Plaintext viewing is audited.                                                                          |

| **Function**    | update()                                                                                                                                                        |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Updates Building data and rejects any owner PII change unless the current user has an active edit-scoped PII session.                                            |
| **Parameters**  | BuildingRequest $request, Building $building                                                                                                                   |
| **Return**      | Success or error message from updateBuildingData                                                                                                                 |
| **Source**      | app\\Http\\Controllers\\BuildingInfo\\BuildingController.php                                                                                                    |
| Remarks         | Owner PII changes are audited and are encrypted by Owner model mutators before storage.                                                                          |

| **Function**    | history()                                                            |
|-----------------|----------------------------------------------------------------------|
| **Description** | Shows all Building records that have been edited                     |
| **Parameters**  | Building \$building resolved from the public_id route parameter      |
| **Return**      | Building history view                                                |
| **Source**      | app\\Http\\Controllers\\BuildingInfo\\BuildingController.php         |

| **Function**    | destroy()                                                           |
|-----------------|---------------------------------------------------------------------|
| **Description** | Soft deletes Building information                                   |
| **Parameters**  | Building \$building resolved from the public_id route parameter     |
| **Return**      | Redirect with success or error message                              |
| **Source**      | app\\Http\\Controllers\\BuildingInfo\\BuildingController.php        |
| **Remarks**     | The delete form URL contains public_id and does not expose BIN.      |

| **Function**    | export()                                                                    |
|-----------------|-----------------------------------------------------------------------------|
| **Description** | Calls the service class that handles the process of exporting Building data |
| **Parameters**  |                                                                             |
| **Return**      | CSV file containing data of connected community toilet    if used                                  |
| **Source**      | app\\Http\\Controllers\\BuildingInfo\\BuildingController.php                |
| **Remarks**     | fetchExport  (service class function)                                       |

| **Function**    | getHouseNumbers()                                                                                           |
|-----------------|-------------------------------------------------------------------------------------------------------------|
| **Description** | Calls the service class that handles building based on search criteria such as road code and search string  |
| **Parameters**  |                                                                                                             |
| **Return**      | fetchHouseNumber(service class function)                                                                             |
| **Source**      | app\\Http\\Controllers\\BuildingInfo\\BuildingController.php                                                |

| **Function**    | listContainments()                                                     |
|-----------------|------------------------------------------------------------------------|
| **Description** | Returns the containments connected to the route-bound Building.        |
| **Parameters**  | Building \$building resolved from the public_id route parameter        |
| **Return**      | title and popContentsHtml                                               |
| **Source**      | app\\Http\\Controllers\\BuildingInfo\\BuildingController.php          |
| **Remarks**     | The URL uses public_id; BIN may still be displayed inside the response. |

| **Function**    | getHouseNumbersAll()                                                                 |
|-----------------|--------------------------------------------------------------------------------------|
| **Description** | Calls the service class that handles returns json with building id and house numebr  |
| **Parameters**  |                                                                                      |
| **Return**      | Returns a JSON response with building id and house number                            |
| **Source**      | app\\Http\\Controllers\\BuildingInfo\\BuildingController.php                         |

| **Function**    | popUpContentHtml                                                                                                                                                                       |
|-----------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Creates a HTML table displaying containment records , builds the table header and body .It iterates through the containment array adding rows and containment id type and view button. |
| **Parameters**  | \$containments                                                                                                                                                                         |
| **Return**      |  Returns HTML table                                                                                                                                                                    |
| **Source**      | app\\Http\\Controllers\\BuildingInfo\\BuildingController.php                                                                                                                           |
| **Remarks**     |                                                                                                                                                                                        |

| **Function**    | getContainmentTypes                                            |
|-----------------|----------------------------------------------------------------|
| **Description** | Disconnects the relation between the containment and building. |
| **Parameters**  | \$id, \$bin                                                    |
| **Return**      |  \$title,\$popContentsHtml                                     |
| **Source**      | app\\Http\\Controllers\\BuildingInfo\\BuildingController.php   |
| **Remarks**     |                                                                |

### BuildingStructureService

Location: app\\Services\\BuildingInfo\\BuildingStructureService.php

The Service Class contains all the business logic. It contains all the functions that are being called in the BuildingController.

| **Function**    | storeBuildingData(Request \$request)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
|-----------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | This function creates a new building record in the database, populating it with data from the request. It handles various attributes such as ownership, sanitation systems, and population details, and ensures proper validation and keyword matching for specific fields. It also manages transactional integrity, rolling back in case of errors.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| **Parameters**  | \$request                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| **Return**      | Returns data of containment table for datatables                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| **Source**      | app\\Services\\BuildingInfo\\BuildingStructureService.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| **Logic**       | Begins transaction, to mark the start. Used to rollback if save fails Fetches max bin from building table and removes B  Creates new model for building Calculates new bin number by adding 1 and concatenates B to the beginning  Stores information that has been submitted to respective building fields Dynamic Conditions: Check if the building is not a main building and set the building_associated_to attribute accordingly. Check if the building is not residential and set the office_business_name attribute accordingly. Check if the building is a low-income household and, if so, check the LIC (Low Income Community) status to set the lic_id attribute. Check if the water source is non-municipal and set the water_customer_id and watersupply_pipe_code attributes accordingly. Check if there is a well present and set the distance_from_well attribute accordingly. Check if the toilet status is false and set the sanitation_system_id to the defecation place. If the toilet status is true, set attributes like toilet_count, no_hh_shared_toilet, population_shared_toilet, and sanitation_system_id. Check for keywords in the sanitation system to set the sewer_code or drain_code. Check if the sanitation system contains the keyword "shared" and call storeContainmentInfo with the appropriate flag. Check if the sanitation system contains the keywords "septic" or "pit" without "shared" and call storeContainmentInfo accordingly. Check if the sanitation system contains the keywords "drain", "sewer", "onsite", "water", "ground", or "composting" to determine if no containment info needs to be stored. Check if the sanitation system contains the keyword "community" and call storeContainmentInfo with the appropriate flag. Also checks for houuse_image if present the image needs to be less than 5mb and extension must be jpeg or jpg |
| **Remarks**     |                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |

| **Function**    | storeContainmentInfo                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Handles the storage and updating of containment information for buildings, including communal, shared, and specific containment types like septic tanks and holding tanks. It updates or creates records in the Containment and BuildToilet models and ensures the correct relationships and attributes are set based on the provided request data.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| **Parameters**  |  flag (string): Indicates the type of containment ('communal', 'shared', or 'containment').  type (string): Specifies the operation type ('create', 'createContainOnly', or 'update').  request (Request object): Contains the request data with various building and containment attributes.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| **Return**      |  For 'shared' containment: Returns the sanitation system ID of the related building.  For other containment types: No explicit return value, but the function performs database updates and commits.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| **Source**      | app\\Services\\ BuildingInfo\\BuildingStructureService.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| **Logic**       |  **Communal or Shared Containment:** **Communal:** If type is 'update', removes the previous BuildToilet connection if it exists. Creates a new BuildToilet record to store the connection between the building and the communal toilet. **Shared:** Finds the related building and its containment ID. Calls storeBuildContainInfo to store the building-containment relationship. Returns the sanitation system ID of the related building.  **Specific Containment Types (e.g., Septic Tanks, Holding Tanks):** **Create or CreateContainOnly:** Generates a new containment ID. **Update:** Finds the existing containment record using the provided ID. Sets various attributes for the containment based on the request data, including type, dimensions, and criteria. **Septic Tanks:** Updates connected buildings with sewer and drain codes. **Holding Tanks:** Sets dimensions based on the shape (rectangular or cylindrical). Sets additional containment attributes such as construction date and location. Handles geometric data (geom) for the containment: For 'createContainOnly', sets geom from the building's centroid. For other types, calls storeGeomInfo to set geom. Saves the containment record. Calls storeBuildContainInfo to store the building-containment relationship. Commits the database transaction.  |
| **Remarks**     | This                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |

| **Function**    | storeOwnerInfo(\$request, \$type)                                                                                                                                                                                                     |
|-----------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Stores (bin , tax_code) or Updates (name , gender , contact , nid )building owner detail                                                                                                                                                     |
| **Parameters**  | \$request , \$type                                                                                                                                                                                                                    |
| **Return**      | Success or error message, stores data to containments.                                                                                                                                                                                |
| **Source**      | app\\Services\\ BuildingInfo\\BuildingStructureService.php                                                                                                                                                                            |
| **Logic**       | \$type determines the operation is create or update If type is create, the function creates a new Owner Instance. if type is update, the function updates the existing owner instance based on bin (Building Identification Number)   |

| **Function**    | storeBuildContainInfo(\$bin, \$containment_id)                                                                                                                                                                                                                                                                                                |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Stores a relation between building and containment                                                                                                                                                                                                                                                                                            |
| **Parameters**  | \$bin, \$containment_id                                                                                                                                                                                                                                                                                                                       |
| **Return**      |                                                                                                                                                                                                                                                                                                                                               |
| **Source**      | app\\Services\\BuildingInfo\\BuildingStructureService.php                                                                                                                                                                                                                                                                                     |
| **Logic**       | Creates a new instance of the 'BuildContain' model representing relationship between building and containment. It sets the bin attribute of the BuildContain instance to the provided \$bin. It sets the containment_id attribute of the BuildContain . Save the instance to establish the relationship between the building and containment. |

| **Function**    | storeGeomInfo(\$request , \$flag , \$type)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Handles the process of storing geom information by converting kml into geom                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| **Parameters**  | \$request: contains the form fields \$flag: defines the type of store, either create or update \$type: defines type of calculations to be carried out; Values: building, containment or area                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| **Return**      | Return geom of building if flag is set as building, centroid of geom if flag is set as containment and area of geom is flag is set as area                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| **Source**      | app\\Services\\Fsm\\ContainmentService.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| **Logic**       | Checks if type is create or update: If create; If geom (KML File) is present, it converts it into a new DOM Document and load the kml file into xml form. If \$request-\>kml exits, that means the building is being added via building survey, thus the kml file is fetched from the server and converted into xml. If update; Checks if request-\>geom is present, and loads it into xml if it is present, else throws error The xml file is converted into coordinate values and stored in points Checks if flag is building, area or containment: If flag is building; The points are converted into geom If flag is area; The area is calculated via the points generated If flag is containment; The centroid of the geom is calculated |

| **Function**    | updateBuildingData(\$request, \$id)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Updates building information based on the request data, including handling various sanitation systems, containment relationships, and geometric information. It performs validation checks and ensures consistency of the data. If there is a mismatch in the containment and sanitation system, it rolls back the transaction and returns an error message.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| **Parameters**  | \$request, \$id                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| **Return**      | Redirects to the building information page with a success message if the update is successful. Redirects back with an error message if there is a containment mismatch or if the update fails.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| **Source**      | app\\Services\\BuildingInfo\\BuildingStructureService.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| **Logic**       |  **Begin Transaction:** Starts a database transaction.  **Find Building:** Finds the building by the given ID.  **Set Initial Error Flag:** Sets the error flag to "no_error".  **Update Building Attributes:** Updates various building attributes from the request data (e.g., ward, road_code, house_number, tax_code, etc.). Handles conditional updates based on specific criteria (e.g., main_building, functional_use_id, toilet_status).  **Update Population Information:** Updates population information (e.g., male, female, and other populations, including differently-abled populations).  **Update Income and Water Source Information:** Updates low-income household information and water source details.  **Handle Geometry and Area:** Updates the building's geometry and estimated area if a new geometry file is provided.  **Update User Information:** Sets the user ID of the user making the update.  **Store Owner Information:** Calls storeOwnerInfo to store owner-related information.  **Handle Sanitation Systems:** **Toilet Present:** Sets the sanitation system ID. Validates and updates containment relationships based on the sanitation system type. Handles different sanitation systems (e.g., shared containments, septic or pit systems, sewer, drain, or other systems). **No Toilet Present:** Sets the sanitation system ID based on the defecation place. Handles containment relationships and updates based on the defecation place.  **Validation and Error Handling:** Rolls back the transaction and returns an error message if there is a containment mismatch. Proceeds if there are no errors.  **Save Building:** Saves the updated building information.  **Store Owner Information Again:** Calls storeOwnerInfo again to ensure owner information is stored.  **Commit Transaction:** Commits the transaction if everything is successful.  **Return Response:** Redirects to the building information page with a success message. Catches exceptions, rolls back the transaction, and returns an error message if the update fails. |

| **Function**    | updateBuildingFromContainment                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Handles the process of update building, owner information and containment,drain code or sewer code if any.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| **Parameters**  | \$request (mixed): This can be either a request object or a containment ID. It contains the data needed to find the containment and update the building's sanitation details.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| **Return**      | void: This function does not return a value. It performs updates directly on the Building records in the database.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| **Source**      | app\\Services\\BuildingInfo\\BuildingStructureService.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| **Logic**       |  **Fetch Containment:** Retrieve the containment record using the containment_id from the \$request or directly from the \$request.  **Update Buildings:** If the containment has associated buildings: Loop through each connected building. Fetch the building details. Assign a new sanitation system ID based on the containment's type. Set the toilet status to true. Update drain or sewer codes if the sanitation system or containment type includes specific keywords ("drain" or "sewer").  **Check Existing Containments:** For each building: Check if any associated containment has a drain or sewer connection. If no containment has a drain or sewer connection, set the respective codes to null.  **Save Changes:** Save the updated building details to the database. If err is set as no_containment, this means no containment connections are required to be set, so checks for existing containment connection. If count is not zero, it throws error and update is blocked. If err is set as shared, the function storeContainmentInfo is called that handles the containment addition process with respective flags If err is set as communal, the function storeContainmentInfo is called that handles the containment addition process with respective flags Tables Affected: buildings, build_contain, owners, containments, build_toilets. |

| **Function**    | fetchExport ()                                                                                                                                                                                                                                                                                                                                                                                                                          |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Handles the process of exporting building and owner information  with Community toilet detail if used                                                                                                                                                                                                                                                                                                                                                                      |
| **Parameters**  |                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Return**      | Returns CSV                                                                                                                                                                                                                                                                                                                                                                                                                             |
| **Logic**       | \$bin = isset(\$_GET['bin']) ? \$_GET['bin'] : null; Fetching value on the basis of which filter is to be carried out. This value needs to be declared in the script of the index page. Define columns, and the query fetching the respective columns Check if any filter values are present, if yes, filter accordingly Set styles using StyleBuilding provided by Box/Spout\^2 Run query, store values in the columns Export the data |

| **Function**    | fetchHouseNumber()                                                                                                                                               |
|-----------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Handles the process of fetching house number for selection of address according to road code                                                                     |
| **Parameters**  |                                                                                                                                                                  |
| **Return**      | Returns data of building house number                                                                                                                            |
| **Logic**       | Runs query to fetch all buildings having containments Runs filter query to filter according to house number or road code Returns json response of house numbers  |

| **Function**    | fetchData(Request \$request, bool \$revealOwnerPii = false)                                                                                                                                                                                     |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Fetches the allowlisted Building DataTable columns and controls whether owner_name is fully masked or explicitly decrypted.                                                                                                                     |
| **Parameters**  | Request and server-authorized reveal flag                                                                                                                                                                                                        |
| **Return**      | Server-side DataTable JSON containing Building columns, one protected owner_name column and action buttons                                                                                                                                       |
| **Logic**       | Applies the existing Building filters. Owner-name matching is allowed only in reveal mode and is performed by authorized application-layer decryption followed by BIN filtering. Locked responses return a fixed mask and never serialize Owner relationships. |

| **Function**    | fetchHouseNumberAll ()                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Fetches house number information from the Building model, with optional search and filtering functionality. It supports pagination and returns the results in JSON format, suitable for usage in APIs or web applications.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| **Parameters**  |                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Return**      | A JSON response containing the house number results and pagination information.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Logic**       |  **Initialize Query:** Starts a query on the Building model, selecting all columns (\*) and filtering out deleted buildings and those associated with another building.  **Apply Search Filter:** Checks if a search term is provided in the request. If so, adds a filter to search within the bin and house_number columns using a case-insensitive ilike query.  **Apply Road Code Filter:** Checks if a road code is provided in the request. If so, filters the buildings by the specified road_code.  **Pagination Setup:** Counts the total number of results. Sets a limit of 10 results per page. Determines the current page from the request or defaults to page 1. Calculates the starting point for the query based on the current page. Calculates the total number of pages and determines if there are more results to paginate through.  **Fetch Paginated Results:** Offsets the query to start from the calculated starting point. Limits the number of results to the specified limit (10). Executes the query and fetches the results.  **Format Results:** Initializes an empty array for JSON results. Iterates over the fetched results, formatting each result as an associative array with id (set to bin) and text (set to house_number or bin if house_number is null).  **Return JSON Response:** Returns a JSON response containing the formatted results and pagination information, indicating if there are more results to fetch. Dynamic Conditions  **Search Condition:** If request()-\>search is present, it searches within the bin and house_number columns.  **Road Code Condition:** If request()-\>road_code is present, it filters results by the specified road_code.  **Pagination Condition:** Calculates the starting point and the number of pages based on the total number of results and the current page  |

### Public ID URL Binding

Building browser URLs use a random UUID public_id instead of the BIN. The BIN remains the Building business key inside the database and application, but it is not placed in record-specific show, edit, update, delete, history, containment-list or map-action URLs.

| Component | Location | Responsibility |
|-----------|----------|----------------|
| public_id migration | database\\migrations\\2026_08_12_000001_add_public_ids_to_buildings_and_containments.php | Adds UUID public_id columns, backfills existing Buildings and Containments, makes them required and adds unique indexes. |
| HasPublicId trait | app\\Models\\Concerns\\HasPublicId.php | Generates a UUID when a new model is created, returns public_id from getRouteKeyName() and validates route UUID format. |
| Building model | app\\Models\\BuildingInfo\\Building.php | Uses HasPublicId so implicit Building route-model binding resolves by public_id. |
| Building routes | routes\\web.php | Uses Building model parameters and explicit `{building:public_id}` parameters for history and connected-containment routes. |
| BuildingStructureService | app\\Services\\BuildingInfo\\BuildingStructureService.php | Builds show, edit, delete, history, map and nearest-road action URLs with public_id rather than bin. |

The following Building actions use public_id in the URL:

| Action | Example route behavior |
|--------|------------------------|
| Show/Details | GET building-info/buildings/{public_id} |
| Edit | GET building-info/buildings/{public_id}/edit |
| Update | PUT/PATCH building-info/buildings/{public_id} |
| Delete | DELETE building-info/buildings/{public_id} |
| History | GET building-info/buildings/{public_id}/history |
| Connected containments | GET building-info/buildings/{public_id}/listContainments |
| Map and nearest-road actions | Pass field=public_id and val={public_id} instead of BIN |

Controller methods type-hint Building, for example show(Building \$building), edit(Building \$building), update(BuildingRequest \$request, Building \$building), history(Building \$building) and destroy(Building \$building). Laravel resolves the UUID to the correct database record before the controller executes.

HasPublicId::resolveRouteBinding() rejects a BIN, an internal numeric identifier or malformed UUID and returns the normal 404 response. This also prevents PostgreSQL UUID comparison errors from becoming a 500 response.

Public IDs reduce direct identifier exposure and make sequential record enumeration more difficult, but they are not an authorization control. Authentication, Building permissions and Owner PII privileged-session checks remain mandatory for every protected action.

The URL-binding behavior is covered by tests\\Feature\\PublicIdUrlBindingTest.php. The tests verify UUID population and uniqueness, correct record resolution, public_id usage across action URLs, absence of BIN/internal IDs from those URLs, automatic UUID generation and 404 responses for invalid or internal identifiers.

### Owner PII Security

The Building module protects the owner_name, owner_gender, owner_contact and nid fields in building_info.owners. Encryption is applied in the application before a database write. Decryption is explicit and is allowed only after authentication, permission checks and a short-lived password-confirmed privileged session.

#### Data States

| State | Handling |
|-------|----------|
| At rest | Owner PII is stored as a versioned encrypted value such as `enc:v1:payload`. AES-256-GCM is configured through config/pii.php, and the key is loaded from the configured external key provider. |
| In transit | Authorized plaintext responses must use HTTPS in production. PII pages, DataTable responses and PII downloads receive no-store/no-cache headers. |
| In use | Plaintext exists temporarily in PHP request memory and, for an authorized response, in the current browser page. The privileged session stores user, scope, resource, authentication method and expiry metadata; it does not store owner plaintext or the encryption key. |
| Locked | The DataTable and details page return a fixed `********` mask. The locked DataTable response does not serialize the Owner model or send owner gender, contact number or NID. |

#### Encryption Components

| Component | Location | Responsibility |
|-----------|----------|----------------|
| PiiEncryptionService | app\\Services\\PiiEncryptionService.php | Encrypts new values, decrypts authorized values, recognizes versioned ciphertext and prevents double encryption. |
| PiiKeyProvider | app\\Contracts\\PiiKeyProvider.php | Defines retrieval of raw key bytes for a requested key version. |
| FilePiiKeyProvider | app\\Services\\PiiKeys\\FilePiiKeyProvider.php | Loads the Base64 key from the external file configured for the ciphertext version and validates that it decodes to exactly 32 bytes. |
| PiiServiceProvider | app\\Providers\\PiiServiceProvider.php | Binds PiiKeyProvider to the configured provider implementation. |
| Owner mutators | app\\Models\\BuildingInfo\\Owner.php | Encrypt owner_name, owner_gender, owner_contact and nid whenever those attributes are assigned. |
| OwnerPiiPresenter | app\\Services\\OwnerPiiPresenter.php | Provides fixed masking, controlled plaintext presentation and owner-name matching after authorized decryption. |

PiiEncryptionService functions:

| **Function** | encrypt(?string $value) |
|--------------|--------------------------|
| **Description** | Returns null for null, preserves an already encrypted value, or encrypts plaintext using the active key version. Randomized encryption means equal plaintext values normally produce different ciphertext. |
| **Return** | Versioned ciphertext in the configured enc:version:payload format |

| **Function** | decrypt(?string $value) |
|--------------|--------------------------|
| **Description** | Identifies the key version from the encrypted envelope and decrypts the payload. Invalid or modified ciphertext raises a generic DecryptException without exposing the value or key. |
| **Return** | Plaintext for an authorized caller, or null when the input is null |

| **Function** | isEncrypted(?string $value) |
|--------------|----------------------------|
| **Description** | Checks whether the value uses a valid versioned PII envelope. It is used by mutators and migration commands to avoid double encryption. |
| **Return** | boolean |

#### PII Access Controller

Controller: app\\Http\\Controllers\\BuildingInfo\\OwnerPiiAccessController.php

| **Function**    | unlockList(Request $request)                                                                                                                                                                  |
|-----------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Requires List Building Structures, View Owner PII and Unlock Owner PII permissions, then verifies the current login password. Creates a short-lived all-owner session for list and view scopes. |
| **Parameters**  | Current password                                                                                                                                                                               |
| **Return**      | Redirects to the Building list with success or validation error                                                                                                                                |
| **Remarks**     | Adds edit scope only if the user also has Edit Building Structure permission. The default duration is five minutes and both successful and failed attempts are audited.                         |

| **Function**    | lock(Request $request)                                                                            |
|-----------------|-----------------------------------------------------------------------------------------------------|
| **Description** | Removes the privileged Owner PII session immediately and regenerates the session identifier.        |
| **Parameters**  | Request $request                                                                                   |
| **Return**      | Redirects back with confirmation                                                                    |
| **Remarks**     | Locking is also triggered automatically by the Building list/edit page when the unlock timer expires. |

#### PII Access Service

Location: app\\Services\\PiiAccessService.php

| **Function**    | unlock(User $user, string $authenticationMethod, array $scopes, ?string $resourceId) |
|-----------------|------------------------------------------------------------------------------------------|
| **Description** | Stores a time-limited privileged-access grant for the current user.                       |
| **Parameters**  | User, authentication method, allowed scopes and building/all-owner resource identifier    |
| **Return**      | void                                                                                     |

| **Function**    | isUnlocked(User $user, string $scope, ?string $resourceId) |
|-----------------|----------------------------------------------------------------|
| **Description** | Verifies permissions, user ownership, expiry, required scope and resource binding. Expired state is removed automatically. |
| **Return**      | boolean |

| **Function**    | lock() |
|-----------------|--------|
| **Description** | Removes current and legacy Owner PII session keys. |
| **Return**      | void |

The all-owner resource identifier allows one authorized list reveal to apply to Building list, details and, when edit permission is present, edit pages during the same short-lived period. Failure at authentication, permission, password re-entry, scope, resource or expiry checks blocks plaintext access.

#### DataTable and Owner Name Filter

BuildingStructureService::fetchData(Request $request, bool $revealOwnerPii) uses an explicit query-builder column list. This prevents automatically loaded Building relationships from being serialized into the JSON response.

-   Locked mode returns `********` for owner_name and does not expose ciphertext or additional Owner PII fields in the network response.
-   Revealed mode decrypts only the owner_name column required by the Building DataTable.
-   The owner-name filter is shown and processed only in revealed mode.
-   Because encryption is randomized, the database cannot use a normal plaintext equality or ILIKE filter on owner_name. Authorized filtering reads owner names in a cursor, decrypts them in application memory, collects matching BIN values and applies those BIN values to the Building query.
-   Every privileged DataTable response is recorded as pii_bulk_viewed without writing owner plaintext to the audit log.

#### Owner PII Export Controller

Controller: app\\Http\\Controllers\\BuildingInfo\\OwnerPiiExportController.php

| **Function**    | export(Request $request)                                                                                                                                                              |
|-----------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Exports owner_name, owner_contact, owner_gender and nid in a dedicated CSV after permission checks and current-password verification. Supports all and bin_list modes.                |
| **Parameters**  | export_mode, optional bin_csv for bin_list mode, confirm_export_all for all mode, and export_password                                                                                  |
| **Return**      | Streamed UTF-8 CSV download with no-store headers                                                                                                                                      |
| **Remarks**     | Selected-BIN export preserves requested order and adds found or not_found status. Full export requires Super Admin, Municipality - Super Admin or Municipality - IT Admin role.        |

Location: app\\Services\\OwnerPiiExportService.php

| **Function**    | parseCsvText(string $csv)                                                                                                         |
|-----------------|------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Validates the CSV size, requires a bin header, normalizes and deduplicates BIN values, rejects invalid rows and enforces max_bins. |
| **Return**      | Array of validated BIN values                                                                                                     |

| **Function**    | rowsForBins(array $bins)                                                                                               |
|-----------------|------------------------------------------------------------------------------------------------------------------------|
| **Description** | Fetches only the requested owners, explicitly decrypts the approved fields and creates found/not_found export rows.    |
| **Return**      | Rows and requested, exported and missing counts                                                                         |

| **Function**    | rowsForAllOwners()                                                                                                      |
|-----------------|------------------------------------------------------------------------------------------------------------------------|
| **Description** | Streams active owner rows using cursor() so the full decrypted dataset is not retained in PHP memory.                  |
| **Return**      | Lazy rows and export counts                                                                                             |

Spreadsheet formula prefixes are escaped before writing CSV values. A missing or invalid BIN file cannot silently fall back to a full export. Export permission, mode, counts and failures are audited, but exported PII values and passwords are excluded from audit context.

#### PII Audit and Cache Protection

| Component | Location | Responsibility |
|-----------|----------|----------------|
| OwnerPiiAuditService | app\\Services\\OwnerPiiAuditService.php | Writes security-event metadata to auth.owner_pii_audit_logs and removes forbidden sensitive context keys. |
| OwnerPiiAuditLog | app\\Models\\OwnerPiiAuditLog.php | Represents append-only PII audit records. |
| PreventPiiResponseCaching | app\\Http\\Middleware\\PreventPiiResponseCaching.php | Adds no-store, no-cache, private and expiry headers to protected responses. |

Audit records can include the user, Building public_id, event, result, authentication method, IP address, user agent and non-sensitive context. They must not contain owner values, customer values, passwords, PINs, encryption keys, ciphertext, payloads, sessions or tokens.

#### Permissions and Routes

Permissions created by database\\seeders\\OwnerPiiPermissionsSeeder.php:

-   View Owner PII
-   Unlock Owner PII
-   Export Owner PII

Routes under the authenticated building-info group:

| Method | Route | Name | Purpose |
|--------|-------|------|---------|
| POST | building-info/owner-pii/unlock-list | owner-pii.unlock-list | Password-confirmed list reveal; rate limited to five attempts per minute. |
| POST | building-info/owner-pii/lock | owner-pii.lock | Immediately ends the privileged PII session. |
| POST | building-info/owner-pii/export | owner-pii.export | Password-confirmed dedicated owner PII export; rate limited to five attempts per minute. |

Building routes use public_id route-model binding instead of exposing sequential database IDs in URLs. Public IDs do not replace authorization; every protected controller action still performs server-side permission and PII-session checks.

#### Configuration and Operational Commands

Configuration file: config\\pii.php

Important environment settings:

-   PII_KEY_PROVIDER: selects the key provider.
-   PII_ACTIVE_KEY_VERSION: version used for new ciphertext.
-   PII_KEY_V1_FILE: external file containing the Base64 v1 key.
-   PII_ALLOW_LEGACY_PLAINTEXT: temporary migration compatibility. It should be false after successful backfill verification.
-   PII_AUDIT_ENABLED: enables security audit logging.
-   PII_EXPORT_MAX_BINS and PII_EXPORT_MAX_FILE_KB: limit the selected-BIN export.

The key file must remain outside the project and source control. If the key is missing or incorrect, encrypted owner values cannot be decrypted. The key-backup function is shared security infrastructure and allows only Super Admin and Municipality - IT Admin to download the active raw Base64 key after password re-entry; the downloaded file must be moved to approved encrypted offline storage.

| Command | Purpose |
|---------|---------|
| php artisan pii:check-configuration | Performs an encryption round-trip and validates active PII configuration without displaying key material. |
| php artisan pii:encrypt-existing-owners --dry-run | Counts legacy plaintext Owner fields without changing the database. |
| php artisan pii:encrypt-existing-owners | Encrypts only non-null Owner fields that are not already encrypted, in transactional chunks. |
| php artisan pii:verify-owner-encryption | Verifies that every non-null Owner PII field is encrypted and decryptable without printing values. |

Recommended migration sequence:

1. Back up the database and the external PII key securely.
2. Run pii:check-configuration.
3. Run pii:encrypt-existing-owners --dry-run and review the counts.
4. Run pii:encrypt-existing-owners.
5. Run pii:verify-owner-encryption.
6. Set PII_ALLOW_LEGACY_PLAINTEXT=false after verification and clear the configuration cache.

#### Tests

The main automated tests for Building Owner PII are located in:

-   tests\\Unit\\PiiEncryptionServiceTest.php
-   tests\\Unit\\OwnerPiiEncryptionTest.php
-   tests\\Unit\\OwnerPiiPresenterTest.php
-   tests\\Unit\\PiiAccessServiceTest.php
-   tests\\Unit\\OwnerPiiAuditServiceTest.php
-   tests\\Unit\\OwnerPiiPasswordReentryTest.php
-   tests\\Unit\\OwnerPiiExportServiceTest.php
-   tests\\Unit\\OwnerPiiExportControllerTest.php
-   tests\\Unit\\PreventPiiResponseCachingTest.php

### BuildingRequest

Location: app\\Http\\Requests\\BuildingInfo\\BuildingRequest.php)

BuildingRequest handles all validation login. It handles validation logic as well as error messages to be displayed.

| **Function**    | authorize()                                            |
|-----------------|--------------------------------------------------------|
| **Description** | Determines if user is authenticated or not             |
| **Parameters**  |                                                        |
| **Return**      | Returns true                                           |
| **Source**      | app\\Http\\Requests\\BuildingInfo\\BuildingRequest.php |

| **Function**    | message()                                                                                                                         |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Message to be displayed in case of validation error                                                                               |
| **Parameters**  |                                                                                                                                   |
| **Return**      | Return validation message                                                                                                         |
| **Source**      | app\\Http\\Requests\\BuildingInfo\\BuildingRequest.php                                                                            |
| **Remarks**     | Need to include errors.list.blade that displays the error message in dashboard Format: ‘Field.validation_rule’ =\>”error_message” |

| **Function**    | store()                                                                           |
|-----------------|-----------------------------------------------------------------------------------|
| **Description** | Contains validation rules for storing new building data                           |
| **Parameters**  |                                                                                   |
| **Return**      | Returns error if validation is not fulfilled, else returns true                   |
| **Remarks**     | Format for validation rule: ‘field_name’=\>’validation_rule1 \| validation_rule2’ |

| **Function**    | update()                                                                          |
|-----------------|-----------------------------------------------------------------------------------|
| **Description** | Contains validation rules for updaing building data                               |
| **Parameters**  |                                                                                   |
| **Return**      | Returns error if validation is not fulfilled, else returns true                   |
| **Remarks**     | Format for validation rule: ‘field_name’=\>’validation_rule1 \| validation_rule2’ |

building-surveys.index: lists building survey records(db: building_surveys).

### Building Surveys

### Tables

The Building Info Module uses the following tables:

-   building_surveys: stores survey information received from the mobile application

The corresponding tables have their respective models that are named in Pascal Case in singluar form. The building info modules are located at app\\Http\\Models\\BuildingInfo.

### Views

All views used by this module is stored in resources\\views\\building-info\\building-surveys

-   index: lists building records (db: buildings)
-   kmlPreviewModal: opens form and calls partial-form for form contents

### BuildingSurveyController

app\\Http\\Controllers\\BuildingInfo\\BuildingSurveyController.php

The controller’s main function is to provide the connection between the calling route and its subsequent function written in the Service Class.

The basic classes of the controller are:

| **Function**    | \__construct()                                                         |
|-----------------|------------------------------------------------------------------------|
| **Description** | Initializes authentication, permissions and the service class instance |
| **Parameters**  |                                                                        |
| **Return**      | null                                                                   |

| **Function**    | index()                                                                                                                                                                |
|-----------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns the index.blade.php page  Called when Building Survey is selected from sidebar.blade.php via  \<a href="{{action('BuildingInfo\\BuildingController@index') }}" |
| **Parameters**  | null                                                                                                                                                                   |
| **Return**      | building-info.building-surveys.index compact('page_title')                                                                                                             |

| **Function**    | getData()                                                                                                                                                     |
|-----------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns the data stored in building survey page which has is_enabled as true  Called when Building Survey’s index page is loaded via ajax call for datatable. |
| **Parameters**  | null                                                                                                                                                          |
| **Return**      | building-info.building-surveys.index compact('page_title')                                                                                                    |

| **Function**    | destroy ()                                   |
|-----------------|----------------------------------------------|
| **Description** | Soft deletes the building survey information |
| **Parameters**  | \$id                                         |
| **Return**      | Success or error message                     |
| **Remarks**     |                                              |

| **Function**    | download()                                                                 |
|-----------------|----------------------------------------------------------------------------|
| **Description** | Downloads the km file of the building survey that is stored in the server. |
| **Parameters**  |                                                                            |
| **Return**      | KML file containing building data                                          |

| **Function**    | approve()                                                                                                                                                                                                                                                   |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Description** | Returns the add building page with the geom and building-survey field prefilled.                                                                                                                                                                            |
| **Parameters**  |                                                                                                                                                                                                                                                             |
| **Return**      | compact('page_title','bin','containment_id','sanitation_system_types', 'water_source','structure_type','functional_use','usecatgsJson','sub_typeJson','sanitation_system_technology','containment','road_code','ward','buildingSurvey','sewer_code','ctpt') |
| **Remarks**     | The building data added through approve button fetches the kml file from the database and stores the geom accordingly.                                                                                                                                      |
