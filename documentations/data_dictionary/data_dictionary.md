Version: V1.1.0

Last Updated: 2026-10-12

Contents

[1 Data Type Standard for IMIS](#data-type-standard-for-imis)

[2 Postgres Naming Convention](#postgres-naming-convention)

[2.1 Conventions](#conventions)

[2.2 Demonstration](#demonstration)

[3 Building IMS](#building-ims)

[3.1 Buildings](#buildings)

[3.2 Building Survey](#building-survey)

[3.3 Low Income Communities](#low-income-communities)

[4 Fecal Sludge IMS](#fecal-sludge-ims)

[4.1 Containment IMS](#containment-ims)

[4.1.1 Containments](#containments)

[4.2 Service Provider IMS](#service-provider-ims)

[4.2.1 Service Providers](#service-providers)

[Service Provider Wards](#service-provider-wards)

[4.2.2 Employee Information](#employee-information)

[4.2.3 Desludging Vehicles Information](#desludging-vehicles-information)

[4.3 Treatment Plant IMS](#treatment-plant-ims)

[4.3.1 Treatment Plants](#treatment-plants)

[4.3.2 Performance Efficiency Standards](#performance-efficiency-standards)

[4.3.3 Performance Efficiency Test](#performance-efficiency-test)

[4.4 Emptying Service IMS](#emptying-service-ims)

[Scheduled Desludging Schedule](#scheduled-desludging-schedule)

[4.4.1 Application](#application)

[4.4.2 Emptying](#emptying)

[4.4.3 Sludge Collection](#sludge-collection)

[4.4.4 Feedback](#feedback)

[4.4.5 Help Desk](#help-desk)

[5 Sewer Connection IMS](#Sewer-Connection-ims)

[6 PT/CT IMS](#ptct-ims)

[7.1 Public / Community Toilets](#public--community-toilets)

[7.2 PT Users Log](#pt-users-log)

[8 CWIS IMS](#cwis-ims)

[8.1 CWIS Generator](#cwis-generator)

[8.2 CWIS Setting](#cwis-setting)

[8.3 NSD Setting](#nsd-setting)

[8.4 KPI Target](#kpi-target)

[9 Utility IMS](#utility-ims)

[9.1 Road Network Information](#road-network-information)

[9.2 Sewer Network Information](#sewer-network-information)

[9.3 Water Supply Network Information](#water-supply-network-information)

[9.4 Drain Network Information](#drain-network-information)

[10 Solid Waste ISS](#solid-waste-iss)

[11 Property Tax Collection IMS](#property-tax-collection-ims)

[12 Water Supply ISS](#water-supply-iss)

[13 Urban Management DSS](#urban-management-dss)

[14.1 Map Feature Layers](#map-feature-layers)

[14.1.1 Wards](#wards)

[14.1.2 Grids](#grids)

[14.1.3 City Polygon ](#citypolys)

[14.1.4 Ward Boundary ](#ward-boundary)

[14.1.5 Landuse ](#landuses)

[14.1.6 Places ](#places)

[14.1.7 Sanitation System ](#sanitation-systems)

[14.1.8 Ward Overlay ](#ward-overlay)

[14.1.9 Waterbodies ](#waterbodys)

[15 Public Health ISS](#public-health-iss)

[15.1 Water Sample Information](#water-sample-information)

[15.2 Waterborne Hotspot](#waterborne-hotspot)

[15.3 Waterborne Cases Information](#waterborne-cases-information)

[16 Settings](#settings)

[16.1 User Information Management](#user-information-management)

[16.1.1 User](#user)

[16.1.2 Owner PII Audit Logs](#owner-pii-audit-logs)

[16.1.3 PII Key Backup Downloads](#pii-key-backup-downloads)

[16.1.4 Roles](#roles)

[16.1.5 Permissions](#permissions)

[17 Miscellaneous Tables](#miscellaneous-tables)

[17.1 Migrations ](#migrations)

[17.2 Soft Delete ](#soft-delete)

[17.3 Spatial refrencing ](#spatial-ref-sys)

[17.4 Authentication Log ](#authentication-log)

# Database Change Register (PII, URL Binding, and Scheduling)

This register summarizes the final database schema introduced by the PII protection, public URL binding, key-backup audit, and scheduled-desludging work. It records final schema state; transient columns that were later removed are identified separately.

Schema types in this section were checked against the local PostgreSQL `information_schema` on 2026-10-12. The repository contains explicit type-change migrations for Application and Property Tax PII. The local `building_info.owners` columns are also `TEXT`; before deployment, confirm that the production release includes the corresponding owner-column type migration or an approved equivalent database change.

| Schema.Table | Change | Column / Table | Previous Type | Final Type / State | Purpose |
| --- | --- | --- | --- | --- | --- |
| `building_info.buildings` | Added | `public_id` | — | `uuid NOT NULL UNIQUE` | Non-sequential public identifier used for show, edit, delete, history, and other URL model binding. Internal `bin` remains unchanged. |
| `building_info.owners` | Modified | `owner_name` | `character varying` | `text NULL` | Stores versioned AES-256-GCM ciphertext. |
| `building_info.owners` | Modified | `owner_gender` | `character varying` | `text NULL` | Stores versioned AES-256-GCM ciphertext. |
| `building_info.owners` | Modified | `owner_contact` | `bigint` | `text NULL` | Numeric storage was replaced because encrypted envelopes are text. |
| `building_info.owners` | Modified | `nid` | `bigint` | `text NULL` | Numeric storage was replaced because encrypted envelopes are text. |
| `fsm.containments` | Added | `public_id` | — | `uuid NOT NULL UNIQUE` | Public URL-binding identifier; internal containment `id` remains unchanged. |
| `fsm.containments` | Added | `status` | — | `smallint NULL DEFAULT 0` | Scheduled-desludging workflow status. |
| `fsm.containments` | Added | `priority` | — | `smallint NULL` | Calculated scheduling priority. |
| `fsm.containments` | Added | `fstp_distance` | — | `numeric(12,2) NULL` | Distance used for queue ordering. |
| `fsm.containments` | Added | `closest_fstp_id` | — | `bigint NULL` | Logical identifier of the closest treatment plant. |
| `fsm.service_providers` | Added | `contract_document_pdf` | — | `character varying NULL` | Optional contract-document reference. |
| `fsm.service_providers` | Added | `service_area` | — | `character varying NULL` | Optional comma-separated service-area wards. |
| `fsm.service_provider_wards` | Added table | all columns | — | See table definition below | Normalized service-provider-to-ward coverage. |
| `fsm.desludging_schedule_temp` | Added table | all operational columns | — | See table definition below | Regenerated, non-PII scheduling queue. |
| `fsm.desludging_schedule_temp` | Modified | `service_provider_id` | `bigint NOT NULL` | `bigint NULL` | Allows queue generation before provider assignment. |
| `fsm.desludging_schedule_temp` | Added | `next_emptying_date` | — | `date NULL` | Scheduled emptying date snapshot. |
| `fsm.desludging_schedule_temp` | Removed | `owner_name`, `owner_gender`, `owner_contact`, `respondent_name`, `respondent_contact` | PII snapshot columns | Not present | Prevents owner/respondent PII from being copied into the operational queue. |
| `fsm.applications` | Modified | `customer_name`, `customer_gender`, `customer_contact` | `character varying` / `bigint` | `text NULL` | Stores encrypted customer/owner PII envelopes. |
| `fsm.applications` | Added | `supervisory_assessment_date` | — | `date NULL` | Records supervisory assessment date. |
| `taxpayment_info.tax_payments` | Modified | `owner_name`, `owner_contact` | `character varying` / `bigint` | `text NULL` | Stores encrypted property-tax owner PII envelopes. |
| `taxpayment_info.tax_payment_status` | Removed | `owner_name`, `owner_contact` | Derived plaintext columns | Not present | Prevents owner PII duplication in the derived map/status table. |
| `auth.owner_pii_audit_logs` | Added table | all columns | — | See table definition below | Audits reveal, export, unlock, and other PII-access events without storing decrypted values. |
| `auth.pii_key_backup_downloads` | Added table | all columns | — | See table definition below | Records backup-download metadata only; the encryption key is never stored in the database. |
| `auth.users` | Removed | `pii_pin_hash` | `character varying NULL` | Not present | The temporary PIN design was removed in favor of password re-entry and authorization. |

# Data Type Standard for IMIS

The following table outlines the data types used for IMIS and the standard conventions set that should be followed during future additional developments and maintenance of IMIS to ensure best practices. (reference: [PostgreSQL: Documentation: 15: Chapter 8.1 Numeric Types](https://www.postgresql.org/docs/14/datatype-numeric.html)):

| Name               | Storage Size | Description                     | Range                                                                                    |
| ------------------ | ------------ | ------------------------------- | ---------------------------------------------------------------------------------------- |
| `smallint`         | 2 bytes      | small-range integer             | -32768 to +32767                                                                         |
| `integer`          | 4 bytes      | typical choice for integer      | -2147483648 to +2147483647                                                               |
| `bigint`           | 8 bytes      | large-range integer             | -9223372036854775808 to +9223372036854775807                                             |
| `decimal`          | variable     | user-specified precision, exact | up to 131072 digits before the decimal point; up to 16383 digits after the decimal point |
| `numeric`          | variable     | user-specified precision, exact | up to 131072 digits before the decimal point; up to 16383 digits after the decimal point |
| `real`             | 4 bytes      | variable-precision, inexact     | 6 decimal digits precision                                                               |
| `double precision` | 8 bytes      | variable-precision, inexact     | 15 decimal digits precision                                                              |
| `smallserial`      | 2 bytes      | small autoincrementing integer  | 1 to 32767                                                                               |
| `serial`           | 4 bytes      | autoincrementing integer        | 1 to 2147483647                                                                          |
| `bigserial`        | 8 bytes      | large autoincrementing integer  | 1 to 9223372036854775807                                                                 |

Additionally, PostGIS extension is used as it greatly extends PostgreSQL's spatial capabilities and is essential for GIS (Geographic Information System) applications with advanced spatial capabilities, though it is not required for basic geometric operations. (reference: [PostGIS: Introduction to PostGIS: 9: Geometries 9.1 Introduction](https://postgis.net/workshops/postgis-intro/geometries.html)):

Geometry: The base type for all geometric types which can store any geometry type.

| Geometry Type      | Description                                                                                 |            |         |                                |
| :----------------- | :------------------------------------------------------------------------------------------ | ---------- | ------- | ------------------------------ |
| Point              | Represents a single point.                                                                  |            |         |                                |
| LineString         | Represents a line defined by a collection of two or more points.                            |            |         |                                |
| Polygon            | Represents a polygonal area defined by one outer ring and zero or more inner rings (holes). |            |         |                                |
| MultiPoint         | A collection of multiple Point geometries.                                                  |            |         |                                |
| MultiLineString    | A collection of multiple LineString geometries.                                             |            |         |                                |
| MultiPolygon       | A collection of multiple Polygon geometries.                                                |            |         |                                |
| GeometryCollection | A collection that can contain a mix of Point                                                | LineString | Polygon | or other geometry collections. |

# Postgres Naming Convention

## Conventions

Schema Name:

SQL identifiers and keywords must begin with a letter (a-z, but also letters with diacritical marks and non-Latin letters) or an underscore (\_). Subsequent characters in an identifier or keyword can be letters, underscores, digits (0-9), or dollar signs (\$).

Indexes:

The standard names for indexes in PostgreSQL are:

{tablename}_{columnname(s)}_{suffix}

where the suffix is one of the following:

-   pkey for a Primary Key constraint
-   key for a Unique constraint
-   excl for an Exclusion constraint
-   idx for any other kind of index
-   fkey for a foreign key
-   check for a Check constraint

Standard suffix for sequences is

-   seq for all sequences

## Demonstration

Create an index

create index my_table_column_a_idx on my_table(column_a);

Create a multicolumn index

create index my_table_column_a_column_b_idx on my_table(column_a, column_b);

Create a unique index

create unique index my_table_column_a_key on my_table(column_a);

Create a multicolumn unique index

create unique index my_table_column_a_column_b_key on my_table(column_a, column_b);

|                  | **Description**                       | **Good**           | **Bad**         | **Remarks**        |
| ---------------- | ------------------------------------- | ------------------ | --------------- | ------------------ |
| **TABLE Naming** |                                       |                    |                 |                    |
| **Schema name**  |                                       | building_info, fsm | buildingInfos   | singular           |
| **Table name**   |                                       | treatment_plants   |                 | plural             |
| **Pivot Table**  |                                       | build_contains     |                 | plural             |
| **Column Name**  | snake_case long name with 3 words max | applicant_name     | applicants_name | should be singular |
| **ID**           | integer                               | 35, 777777         |                 |                    |
| **Code**         | code                                  | B000035, C77777    |                 |                    |
| **Primary key**  | id                                    | id                 | containcd       |                    |
| **Foreign key**  | tablename_id                          | containment_id     | containcd       |                    |

# Building

**Schema Name: building_info**

The Building Information Management System Module uses the following tables:

**Data Tables**

-   building_surveys: stores building footprint file information received from the Building Information Collection Mobile Application.
-   buildings: stores information of the buildings
-   owners: relational database that connects buildings and and the corresponding owner information. Foreign_key: bin

**Lookup Tables**

-   structure_types: stores structure types values that is displayed in the Structure Type dropdown.
-   functional_uses: stores functional use values that is displayed in the Functional Use dropdown.
-   use_categorys: stores use categorys values that is displayed in the Use Category dropdown.
-   water_sources: stores water sources values that is displayed in the Main Drinking Water Source dropdown.
-   sanitation_systems: stores sanitation system type values that is displayed in the Toilet Connection dropdown and Defecation Area dropdown.
-   wms_links: stores the WMS links of geoserver that is used by the mobile app's map interface to display the different layers and attribute information.

**Relational Tables**

-   build_contains: relational database that connects buildings and containments. Foreign Key: bin and containment_id.

## Building Survey

Table Name: **building_surveys**

| **Field Name** | **Label**               | **Description**                                                                                                | **Data Type**         |
| -------------------- | ----------------------------- | -------------------------------------------------------------------------------------------------------------------- | --------------------------- |
| id                   |                               | Unique identifier for the data                                                                                       | Integer pk                  |
| temp_building_code   | Temporary  Building Code      | Temporary building identification number assigned by user                                                            | character varying           |
| tax_code             | Tax Code                      | Identifier for the building tax record                                                                               | character varying           |
| kml                  | Building Footprint (KML File) | File name of the KML file that is generated by the mobile app and stored in the server                               | character varying           |
| collected_date       | Surveyed Date                 | Date when the KML file was collected                                                                                 | date                        |
| is_enabled           |                               | Boolean indicating whether the record is visible or not, the record is disabled after the building has been approved | boolean (Default True)      |
| user_id              |                               | Identifier for the user who created the record (Hidden)                                                              | integer fk:auth.users(id)   |
| created_at           |                               | Timestamp when the record was created (Auto Fill, Hidden)                                                            | timestamp without time zone |
| updated_at           |                               | Timestamp when the record was last updated (Auto Fill, Hidden)                                                       | timestamp without time zone |
| deleted_at           |                               | Timestamp when the record was deleted (Auto Fill, Hidden)                                                            | timestamp without time zone |

## Buildings

**Data Tables**

Table Name: **buildings**

| **Field Name**                 | **Label**                                     | **Description**                                                                                                          | **Data Type**                                         |
| ------------------------------ | --------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ | ----------------------------------------------------- |
| bin                            |                                               | Unique building identification number (auto generated)                                                                   | character varying pk                                  |
| public_id                      | Public ID                                     | Non-sequential identifier exposed in URLs; unique and required. Internal relationships continue to use `bin`.             | uuid NOT NULL UNIQUE                                  |
| building_associated_to         | BIN of Main Building                          | If the building is not a main building, BIN of the main building for that associated building                            | character varying                                     |
| ward                           | Ward No                                       | The ward number where the building is situated                                                                           | integer fk:layer_info.wards(ward)                     |
| road_code                      | Road Code                                     | Road Code of the road to which the building is connected                                                                 | character varying fk:utility_info.roads(code)         |
| house_number                   | House Number                                  | Unique address code of the building assigned by the city (e.g., house number, holding number)                            | character varying                                     |
| house_locality                 | House Locality / Address                      | House address or name of the locality where the building is situated                                                     | character varying                                     |
| tax_code                       | Tax Code / Holding ID                         | Building’s tax code/ holding ID that is assigned by the city                                                             | character varying                                     |
| structure_type_id              | Structure Type                                | Type of the building structure                                                                                           | integer fk:building_info.structure_types(id)          |
| surveyed_date                  | Surveyed Date                                 | Date when the building was surveyed, auto generated if building is added from building surveys approve form.             | date                                                  |
| floor_count                    | Number of Floors                              | Number of floors of the building (including the ground floor)                                                            | numeric                                               |
| construction_year              | Construction Date                             | Date when the building was constructed                                                                                   | date                                                  |
| functional_use_id              | Functional Use of Building                    | Functional use of the building                                                                                           | integer fk:building_info.functional_uses(id)          |
| use_category_id                | Use Category of Buildings                     | Category of the building use, which depends on functional use                                                            | integer fk:building_info.use_categorys(id)            |
| office_business_name           | Office or Business Name                       | Name of the business or office in the building, if not residential                                                       | character varying                                     |
| household_served               | Number of Households                          | Number of households served by the building                                                                              | integer                                               |
| population_served              | Population of Building                        | Number of people served by the building                                                                                  | integer                                               |
| male_population                | Male Population                               | Number of male population                                                                                                | integer                                               |
| female_population              | Female Population                             | Number of female population                                                                                              | integer                                               |
| other_population               | Other Population                              | Number of other population                                                                                               | integer                                               |
| diff_abled_male_pop            | Differently Abled Male Population             | Number of differently abled male population                                                                              | integer                                               |
| diff_abled_female_pop          | Differently Abled Female Population           | Number of differently abled female population                                                                            | integer                                               |
| diff_abled_others_pop          | Differently Abled Other Population            | Number of differently abled other population                                                                             | integer                                               |
| low_income_hh                  | Is Low Income House                           | Boolean indicating if building is low income house                                                                       | boolean                                               |
| lic_id                         | LIC Name                                      | Low income community identifier if the building is part of LIC                                                           | integer fk:layer_info.low_income_communities(id)      |
| water_source_id                | Main Drinking Water Source                    | Source of drinking water supply for the building                                                                         | integer fk:building_info.water_sources(id)            |
| watersupply_pipe_code          | Water Supply Pipe Line Code                   | Water supply pipeline code (if water source is Municipal/Public Water Supply)                                            | character varying fk:utility_info.water_supplys(code) |
| water_customer_id              | Water Supply Customer ID                      | Unique identifier for the water supply customer record, if available (if water source is Municipal/Public Water Supply)  | character varying                                     |
| well_presence_status           | Well in Premises                              | Boolean indicating whether a well is present in the building premises                                                    | character varying                                     |
| distance_from_well             | Distance of Well from Closest Containment (m) | Distance from the well to the closest containment in meter                                                               | numeric                                               |
| swm_customer_id                | SWM Customer ID                               | Unique identifier for the solid waste management customer record, if available                                           | character varying                                     |
| toilet_status                  | Presence of Toilet                            | Boolean indicating whether the toilets are present in the building premises                                              | boolean                                               |
| toilet_count                   | Number of Toilets                             | Number of toilet facilities in the building if toilet_status (Presence of Toilet) is true                                | integer                                               |
| household_with_private_toilet  | Households with Private Toilet                | No of households with access to private toilets                                                                          | integer                                               |
| population_with_private_toilet | Population with Private Toilet                | Population with access to private toilets                                                                                | interger                                              |
| sanitation_system_id           | Toilet Connection / Defecation Place          | Sanitation system of the building where toilet is connected/ Defecation Place if there Presence of Toilet is false.      | integer fk:building_info.sanitation_systems(id)       |
| sewer_code                     | Sewer Code                                    | Identifier for the sewer network that the building is connected to, if applicable                                        | character varying fk:utility_info.sewers(code)        |
| drain_code                     | Drain Code                                    | Identifier for the drain network that the building is connected to, if applicable                                        | character varying fk:utility_info.drains(code)        |
| desludging_vehicle_accessible  | Building Accessible to Desludging Vehicle     | Boolean indicating whether the building is accessible to the desludging vehicle                                          | boolean                                               |
| geom                           | Building Footprint (KML File)                 | Geospatial coordinates of the building's footprint initially uploaded as a KML file and stored in the table as a polygon | geometry(MultiPolygon,4326)                           |
| verification_status            |                                               | Status that indicates the need of verification of the buildings footprint.                                               | boolean                                               |
| estimated_area                 | Estimated Area of the Building ( ㎡ )         | Estimated area of the building excluding the compound in square meters (unit m²) which is auto calculated from the geom  | numeric                                               |
| user_id                        |                                               | Identifier for the user who created the record (Hidden)                                                                  | integer fk:auth.users(id)                             |
| created_at                     |                                               | Timestamp when the record was created (Auto Fill, Hidden)                                                                | timestamp without time zone                           |
| updated_at                     |                                               | Timestamp when the record was last updated (Auto Fill, Hidden)                                                           | timestamp without time zone                           |
| deleted_at                     |                                               | Timestamp when the record was deleted (Auto Fill, Hidden)                                                                | timestamp without time zone                           |

Table Name: **Owners**

| **Field Name** | **Label**            | **Description**                                                | **Data Type**                                     |
| -------------- | -------------------- | -------------------------------------------------------------- | ------------------------------------------------- |
| id             |                      | Unique identifier for the record (auto generated)              | integer pk                                        |
| bin            |                      | Building identification number                                 | character varying fk:building_info.buildings(bin) |
| owner_name     | Owner Name           | Versioned AES-256-GCM encrypted owner name (`enc:<version>:<payload>`); decrypted only in authorized application flows | text (nullable) |
| owner_gender   | Owner Gender         | Versioned AES-256-GCM encrypted owner gender; decrypted only in authorized application flows | text (nullable) |
| owner_contact  | Owner Contact Number | Versioned AES-256-GCM encrypted owner contact number; stored as text because ciphertext is not numeric | text (nullable) |
| nid            | Owner NID            | Versioned AES-256-GCM encrypted national identification number; stored as text because ciphertext is not numeric | text (nullable) |
| created_at     |                      | Timestamp when the record was created (Auto Fill, Hidden)      | timestamp without time zone                       |
| updated_at     |                      | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone                       |
| deleted_at     |                      | Timestamp when the record was deleted (Auto Fill, Hidden)      | timestamp without time zone                       |

**Lookup Tables**

Table Name: **structure_types**

| **Field Name** | **Label**      | **Description**                                        | **Data Type**     |
| -------------- | -------------- | ------------------------------------------------------ | ----------------- |
| id             |                | Unique identifier for the record                       | integer pk        |
| type           | Structure Type | The name of the construction technique of the building | character varying |

**Lookup Table values for structure_types**

| **ID** | **Type**     |
| ------ | ------------ |
| 1      | RCC framed   |
| 2      | Load bearing |
| 3      | CGI Sheet    |
| 4      | Wooden/Mud   |

Table Name: **functional_uses**

| **Field Name** | **Description**                  | **Data Type**     |
| -------------- | -------------------------------- | ----------------- |
| id             | Unique identifier for the record | integer pk        |
| name           | Name of the Functional Use       | character varying |

**Lookup Table Values for functional_uses**

| **id** | **name**                                     |
| ------ | -------------------------------------------- |
| 1      | Residential                                  |
| 2      | Mixed (Residential, Commercial, Office uses) |
| 3      | Educational                                  |
| 4      | Health Institution                           |
| 5      | Commercial                                   |
| 6      | Industrial                                   |
| 7      | Agriculture and Livestock                    |
| 8      | Public Institution                           |
| 9      | Government Institution                       |
| 10     | Recreational Institution                     |
| 11     | Social Institution                           |
| 12     | Cultural and Religious                       |
| 13     | Financial Institution                        |
| 14     | Vacant/Under Construction                    |

Table Name: **use_categorys**

| **Field Name**    | **Label**                  | **Description**                                                | **Data Type**                                |
| ----------------- | -------------------------- | -------------------------------------------------------------- | -------------------------------------------- |
| id                |                            | Unique identifier for the record                               | integer pk                                   |
| name              | Use Categories of Building | The name of the use category                                   | character varying                            |
| functional_use_id | Functional Use of Building | Identifier for the functional use this use category belongs to | integer fk:building_info.functional_uses(id) |

**Lookup Table values for use_categorys**

| **ID** | **Name**                       | **functional_use_id** |
| ------ | ------------------------------ | --------------------- |
| 1      | Residential                    | 1                     |
| 2      | Housing                        | 1                     |
| 3      | Apartment                      | 1                     |
| 4      | Orphanage                      | 1                     |
| 5      | Old-aged Home                  | 1                     |
| 6      | Hostel                         | 1                     |
| 7      | Mixed                          | 2                     |
| 8      | School                         | 3                     |
| 9      | College                        | 3                     |
| 10     | University                     | 3                     |
| 11     | Training Center                | 3                     |
| 12     | Hospital                       | 4                     |
| 13     | Clinic/Health Post             | 4                     |
| 14     | Shop                           | 5                     |
| 15     | Restaurant                     | 5                     |
| 16     | Hotel / Resort                 | 5                     |
| 17     | Offices (Private)              | 5                     |
| 18     | Shopping mall / Super Market   | 5                     |
| 19     | Party Palace/Banquets          | 5                     |
| 20     | Business Complex               | 5                     |
| 21     | Industry                       | 6                     |
| 22     | Factory                        | 6                     |
| 23     | Warehouse                      | 6                     |
| 24     | Workshop                       | 6                     |
| 25     | Printing Press                 | 6                     |
| 26     | Agriculture Farm               | 7                     |
| 27     | Livestocks                     | 7                     |
| 28     | City hall                      | 8                     |
| 29     | Museum                         | 8                     |
| 30     | Public Library and archive     | 8                     |
| 31     | Public transportation terminal | 8                     |
| 32     | Parking                        | 8                     |
| 33     | Post office                    | 8                     |
| 34     | Community Toilet               | 8                     |
| 35     | Public Toilet                  | 8                     |
| 36     | Municipal Office               | 9                     |
| 37     | Ward Office                    | 9                     |
| 38     | Government Office              | 9                     |
| 39     | Police Office                  | 9                     |
| 40     | Fire Station                   | 9                     |
| 41     | Army barrack                   | 9                     |
| 42     | Jail                           | 9                     |
| 43     | Club                           | 10                    |
| 44     | Stadium                        | 10                    |
| 45     | Cinema/theatre                 | 10                    |
| 46     | Sports complex                 | 10                    |
| 47     | Fitness center                 | 10                    |
| 48     | Recreational center            | 10                    |
| 49     | NGO                            | 11                    |
| 50     | INGO                           | 11                    |
| 51     | Political Party                | 11                    |
| 52     | Guthi house                    | 11                    |
| 53     | Media                          | 11                    |
| 54     | Social Group /Samiti Bhawan    | 11                    |
| 55     | Temple                         | 12                    |
| 56     | Church                         | 12                    |
| 57     | Mosque                         | 12                    |
| 58     | Stupa                          | 12                    |
| 59     | Hermitage (kuti)               | 12                    |
| 60     | Mourning house                 | 12                    |
| 61     | Bihar/Gumba                    | 12                    |
| 62     | Bhajan Mandal                  | 12                    |
| 63     | Cultural Centers               | 12                    |
| 64     | Bank                           | 13                    |
| 65     | Cooperative / Finance          | 13                    |
| 66     | Vacant building                | 14                    |
| 67     | Building under construction    | 14                    |

Table Name:**water_sources**

| **Field Name** | **Label**                  | **Description**                  | **Data Type**     |
| -------------- | -------------------------- | -------------------------------- | ----------------- |
| id             |                            | Unique identifier for the record | integer pk        |
| source         | Main Drinking Water Source | The source of water resource     | character varying |

**Lookup table values for water_sources**

| **ID** | **Source**                    |
| ------ | ----------------------------- |
| 1      | Municipal/Public water supply |
| 2      | Deep boring                   |
| 3      | Tube well                     |
| 4      | Dug well                      |
| 5      | Private Tanker water          |
| 6      | Jar Water                     |
| 7      | Spring/River/Canal            |
| 8      | Stone spout/Pond              |
| 9      | Rainwater                     |
| 10     | Others                        |

Table Name: **sanitaiton_systems**

| **Field Name**    | **Description**                                                                                             | **Data Type**          |
| ----------------- | ----------------------------------------------------------------------------------------------------------- | ---------------------- |
| id                | Unique identifier for the record                                                                            | integer pk             |
| sanitation_system | Type of sanitation system of the building if toilet is present and Defecation area if toilet is not present | integer                |
| dashboard_display | Boolean value indicating if sanitation systems is to be displayed in dashboard or not                       | boolean                |
| map_display       | Boolean value indicating if sanitation systems is to be displayed in map or not                             | boolean                |
| icon_name         | Name of the icon that is supposed to be displayed for sanitation systems                                    | character varying(255) |

**Lookup table values for sanitaiton_systems**

| **id** | **sanitation_system**                                       | **dashboard_display** | **map_display** | **icon_name**   |
| ------ | ----------------------------------------------------------- | --------------------- | --------------- | --------------- |
| 1      | Sewer Network                                               | True                  | True            | sewers.svg      |
| 2      | Drain Network                                               | False                 | True            | others.svg      |
| 3      | Septic Tank                                                 | True                  | True            | septic-tank.svg |
| 4      | Pit/ Holding Tank                                           | True                  | True            | pit.svg         |
| 5      | Onsite Treatment (e.g., Anaerobic Digestor/ Biogas, DEWATS) | True                  | True            | no_icon         |
| 6      | Composting Toilet (e.g., Ecosan, UDDT, etc.)                | True                  | True            | no_icon         |
| 7      | Water Body                                                  | False                 | True            | others.svg      |
| 8      | Open Ground                                                 | False                 | True            | others.svg      |
| 9      | Community Toilet                                            | False                 | True            | others.svg      |
| 10     | Open Defecation                                             | False                 | True            | others.svg      |
| 11     | Shared Containment                                          | False                 | False           | NULL            |
| 12     | Shared Toilet                                               | False                 | True            | NULL            |

Table Name: **wms_links**

| **Field Name** | **Description**               | **Data Type**     |
| -------------- | ----------------------------- | ----------------- |
| name           | The name of the wms link used | character varying |
| link           | The link of the wms           | character varying |

**Relational Tables**

Table Name: **build_contains**

| **Field Name** | **Label** | **Description**                                                  | **Data Type**                                     |
| -------------- | --------- | ---------------------------------------------------------------- | ------------------------------------------------- |
| id             |           | Unique identifier for the record (auto generated)                | integer pk                                        |
| bin            |           | Building identification number                                   | character varying fk:building_info.buildings(bin) |
| containment_id |           | Identifier for the containment that the building is connected to | character varying fk:fsm.containments(id)         |
| created_at     |           | Timestamp when the record was created (Auto Fill, Hidden)        | timestamp without time zone                       |
| updated_at     |           | Timestamp when the record was last updated (Auto Fill, Hidden)   | timestamp without time zone                       |
| deleted_at     |           | Timestamp when the record was deleted (Auto Fill, Hidden)        | timestamp without time zone                       |

## Low Income Communities

Schema Name: **layer_info**

Table Name: **low_income_communities**

This table stores information about low-income communities in the area.

| **Field Name**          | **Label**                | **Description**                                                                               | **Data Type**               |
| ----------------------- | ------------------------ | --------------------------------------------------------------------------------------------- | --------------------------- |
| id                      |                          | Unique identifier for the record (auto generated)                                             | integer pk                  |
| community_name          | Community Name           | Name of Community                                                                             | Character varying           |
| geom                    | Area                     | Geospatial coordinates of the boundary of the low income community (represented as a polygon) | geometry(MultiPolygon,4326) |
| no_of_buildings         | No. of Buildings         | Total buildings present in the low income community                                           | integer                     |
| number_of_households    | No. of Households        | Number of Households present in the low income community                                      | integer                     |
| population_total        | Population               | Total population in the low income community                                                  | integer                     |
| population_male         | Male Population          | Total male population                                                                         | integer                     |
| population_female       | Female Population        | Total female population                                                                       | integer                     |
| population_others       | Other Population         | Total other population                                                                        | integer                     |
| no_of_septic_tank       | No. of Septic Tanks      | Total number of septic tanks                                                                  | integer                     |
| no_of_holding_tank      | No. of Holding Tanks     | Total number of holding tanks                                                                 | integer                     |
| no_of_pit               | No. of Pits              | Total number of pits                                                                          | integer                     |
| no_of_sewer_connection  | No. of Sewer Connections | Total number of sewer connections                                                             | integer                     |
| no_of_community_toilets | No. of Community Toilets | Total number of community toilets                                                             | integer                     |
| user_id                 |                          | Identifier for the user who created the record (Hidden)                                       | integer fk:auth.users(id)   |
| created_at              |                          | Timestamp when the record was created (Auto Fill, Hidden)                                     | timestamp without time zone |
| updated_at              |                          | Timestamp when the record was last updated (Auto Fill, Hidden)                                | timestamp without time zone |
| deleted_at              |                          | Timestamp when the record was deleted (Auto Fill, Hidden)                                     | timestamp without time zone |

# Fecal Sludge IMS

Schema Name: **fsm**

The Fecal Sludge Information Management System Module uses the following tables:

**Data Tables**

-   containments: stores the containment information.
-   service_providers: stores information of the service providers that provide emptying services.
-   service_provider_wards: stores the wards covered by each service provider for scheduled desludging.
-   employees: stores employee information associated with the registered service providers.
-   desludging_vehicles: stores details about desludging vehicles used for desludging operations of the service providers.
-   treatment_plants: stores information about the treatment plants.
-   treatmentplant_tests: stores records of water effluent tests conducted at treatment plants.
-   applications: stores information regarding applications submitted.
-   desludging_schedule_temp: stores the currently generated scheduled-desludging queue.
-   emptyings: stores information related to emptying operations carried out.
-   sludge_collections: stores details about collections of sludge.
-   feedbacks: stores feedback provided by the users.
-   help_desks: stores information related to help desks.

**Lookup Tables**

-   containment_types: stores values of the list of containment types used in the Contaiment Type field.
-   treatment_plant_performance_efficiency_test_settings: stores data for treatment plant performance efficiency tests.

## Containment IMS

### Containments

**Data Tables**

Table Name: **containments**

| **Field Name**        | **Label**                       | **Description**                                                                                                                                      | **Data Type**                                     |
| --------------------- | ------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------- |
| id                    |                                 | Unique identifier for the containment                                                                                                                | character varying pk                              |
| public_id             | Public ID                       | Non-sequential identifier exposed in containment URLs; unique and required. Internal relationships continue to use `id`.                            | uuid NOT NULL UNIQUE                              |
| type_id               | Containment Type                | Type of the containment with outlet connnection information                                                                                          | integer fk:fsm.containment_types(id)              |
| location              | Containment Location            | Location of the containment (Inside the house\|Outside the house)                                                                                    | character varying                                 |
| size                  | Containment Volume (m³)         | Volume of the containment in cubic meters (m³)                                                                                                       | numeric                                           |
| pit_diameter          | Pit Diameter (m)                | Diameter of the pit in meter                                                                                                                         | numeric                                           |
| tank_length           | Tank Length (m)                 | Length of the tank in meter                                                                                                                          | numeric                                           |
| tank_width            | Tank Width (m)                  | Width of the tank in meter                                                                                                                           | numeric                                           |
| depth                 | Tank Depth (m)                  | Depth of the containment in meter                                                                                                                    | numeric                                           |
| septic_criteria       | Septic Tank Standard Compliance | Boolean value indicating the compliance status of septic tank                                                                                        | boolean                                           |
| construction_date     | Construction Date               | Containment construction date                                                                                                                        | date                                              |
| emptied_status        | Emptied Status                  | Boolean value indicating the emptying status of the septic system, if emptied or not                                                                 | boolean                                           |
| last_emptied_date     | Last Emptied Date               | Date of the last time the septic system was emptied                                                                                                  | date                                              |
| next_emptying_date    | Next Emptying Date              | Date of the next scheduled emptying of the septic system                                                                                             | date                                              |
| status                | Scheduled Desludging Status     | Scheduling workflow status; the application treats 0 as active, 1 as confirmed, 3 as queue-eligible, and 4 as opted out (eligible for reintegration). | smallint (nullable, DEFAULT 0)                    |
| priority              | Scheduled Desludging Priority   | Calculated scheduling priority (1–3, with 1 highest).                                                                                                 | smallint (nullable)                                |
| fstp_distance         | FSTP Distance                   | Distance to the treatment plant used in scheduling order; the migration does not calculate or backfill it.                                           | numeric(12,2) (nullable)                          |
| closest_fstp_id       | Closest FSTP                    | Identifier of the closest treatment plant; a logical reference, not a database-enforced foreign key.                                                 | bigint (nullable)                                 |
| no_of_times_emptied   | Number of Times Emptied         | Number of times the septic system has been emptied                                                                                                   | integer                                           |
| surveyed_at           |                                 | Date when the septic system was surveyed                                                                                                             | date                                              |
| toilet count          |                                 | Number of toilet served by the containment                                                                                                           | integer                                           |
| distance_closest_well |                                 | Distance to the closest well, if applicable                                                                                                          | numeric                                           |
| geom                  |                                 | Geospatial coordinates of the septic system geometry, auto calculated as center point of the building it is associated with (represented as a point) | geometry(Point,4326)                              |
| user_id               |                                 | Identifier for the user who created the record (Hidden)                                                                                              | integer fk:auth.users(id)                         |
| verification_required |                                 | Indicates whether the containment requires further verification after addition into the system                                                       | boolean                                           |
| responsible_bin       |                                 | The building identification number of the main/responsible building of the containment.                                                              | character varying fk:building_info.buildings(bin) |
| created_at            |                                 | Timestamp when the record was created (Auto Fill, Hidden)                                                                                            | timestamp without time zone                       |
| updated_at            |                                 | Timestamp when the record was last updated (Auto Fill, Hidden)                                                                                       | timestamp without time zone                       |
| deleted_at            |                                 | Timestamp when the record was deleted (Auto Fill, Hidden)                                                                                            | timestamp without time zone                       |

## Service Provider IMS

### Service Providers

Table Name: serivce_providers

| **Field Name**   | **Label**             | **Description**                                                                         | **Data Type**               |
| ---------------- | --------------------- | --------------------------------------------------------------------------------------- | --------------------------- |
| id               |                       | Unique identifier for the Service Provider                                              | Integer pk                  |
| company_name     | Company Name          | Company Name of the Service Provider                                                    | character varying           |
| email            | Email                 | Email address of the service provider                                                   | character varying           |
| ward             | Ward Number           | Ward number where service provider office is present                                    | Integer                     |
| company_location | Address               | House address of the service provider office                                            | character varying           |
| contact_person   | Contact Person Name   | Name of the company head/ proprietor of the service provider                            | character varying           |
| contact_gender   | Contact Person Gender | Gender of contact person                                                                | character varying           |
| contact_number   | Contact Person Number | Contact number of the contact person/ service provider office                           | big integer                 |
| status           | Status                | Boolean value that defined the operational status of Service Provider                   | boolean (DEFAULT true)      |
| service_area     | Service Area (Wards)  | Optional comma-separated wards served by this provider; scheduling also uses the normalized service_provider_wards table. | character varying (nullable) |
| contract_document_pdf | Contract Document | Optional stored path or name of the provider contract PDF; not used by scheduling. | character varying (nullable) |
| geom             |                       | Geospatial coordinates of the location of the service provider (represented as a point) | geometry(Point,4326)        |
| user_id          |                       | Identifier for the user who created the record (Hidden)                                 | integer fk:auth.users(id)   |
| created_at       |                       | timestamp when the record was created (Auto Fill, Hidden)                               | timestamp without time zone |
| updated_at       |                       | Timestamp when the record was last updated (Auto Fill, Hidden)                          | timestamp without time zone |
| deleted_at       |                       | Timestamp when the record was deleted (Auto Fill, Hidden)                               | timestamp without time zone |

### Service Provider Wards

Table Name: **service_provider_wards**

One row records a ward served by a provider. The relationship to `fsm.service_providers` is used by the application but is not enforced by a database foreign key.

| **Field Name** | **Label** | **Description** | **Data Type** |
| -------------- | --------- | --------------- | ------------- |
| id | | Unique identifier for the provider-to-ward mapping | bigint pk |
| service_provider_id | Service Provider | Identifier of the provider covering the ward (logical reference to `fsm.service_providers.id`) | bigint |
| ward | Service Area Ward | Ward covered by the provider | integer |
| created_at | | Timestamp when the record was created (Auto Fill, Hidden) | timestamp without time zone |
| updated_at | | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone |

Unique constraint: (`service_provider_id`, `ward`). Indexes: `service_provider_id`, `ward`.

### Employee Information

Table Name: **employees**

| **Field Name**      | **Label**                  | **Description**                                                             | **Data Type**                        |
| ------------------- | -------------------------- | --------------------------------------------------------------------------- | ------------------------------------ |
| id                  |                            | Unique identifier for each employee                                         | integer pk                           |
| service_provider_id | Service Provider Name      | Unique identifier for the service provider the employee works for           | integer fk:fsm.service_providers(id) |
| name                | Employee Name              | Name of the employee                                                        | character varying                    |
| gender              | Employee Gender            | Gender of the employee                                                      | character varying                    |
| contact_number      | Employee Contact Number    | Contact Number of the employee                                              | big integer                          |
| dob                 | Date of Birth              | Date of birth of the employee                                               | date                                 |
| address             | Address                    | Address of the employee                                                     | character varying                    |
| employee_type       | Designation                | Type of employee (Management\|Driver\|Cleaner/Emptier)                      | character varying                    |
| year_of_experience  | Working Experience (years) | Working experience of the employee in years                                 | integer                              |
| wage                | Monthly Remuneration       | Monthly Remuneration/Wage of the employee                                   | integer                              |
| license_number      | Driving License Number     | License number of employee (if employee_type is driver)                     | character varying                    |
| license_issue_date  | License Issue Date         | License issued date of employee (if employee_type is driver)                | date                                 |
| training_status     | Training Received (if any) | Indicates what training the employee has completed                          | character varying                    |
| status              | Status                     | Boolean value that indicates if the employee is currently active or not     | boolean (DEFAULT true)               |
| employment_start    | Job Start Date             | Start date of the employee's employment                                     | date                                 |
| employment_end      |                            | End date of the employee's employment (if status is set as not operational) | date                                 |
| user_id             |                            | Identifier for the user who created the record (Hidden)                     | Integer fk:auth.users(id)            |
| created_at          |                            | Timestamp when the record was created (Auto Fill, Hidden)                   | timestamp without time zone          |
| updated_at          |                            | Timestamp when the record was last updated (Auto Fill, Hidden)              | timestamp without time zone          |
| deleted_at          |                            | Timestamp when the record was deleted (Auto Fill, Hidden)                   | timestamp without time zone          |

### Desludging Vehicles Information

Table Name: **desludging_vehicles**

| **Field Name**                     | **Label**                         | **Description**                                                                                             | **Data Type**                        |
| ---------------------------------- | --------------------------------- | ----------------------------------------------------------------------------------------------------------- | ------------------------------------ |
| id                                 |                                   | A unique identifier for each desludging vehicle                                                             | integer pk                           |
| service_provider_id                | Service Provider                  | Unique identifier for the service provider the desludging vehicle is owned by                               | integer fk:fsm.service_providers(id) |
| license_plate_number               | Vehicle License Plate Number      | The license plate number of the desludging vehicle                                                          | character varying                    |
| capacity                           | Capacity (m³)                     | The size of the desludging vehicle in m3                                                                    | numeric                              |
| width                              | Width (m)                         | The width of the desludging vehicle in meters                                                               | numeric                              |
| comply_with_maintainance_standards | Comply with Maintenance Standards | Boolean value that maintains Status whether desludging vehicle complies with maintenance standards or not   | boolean                              |
| status                             | Status                            | Boolean value indicating the status of the desludging vehicle, whether it is operational or non operational | boolean (DEFAULT true)               |
| description                        | Description                       | A description of the desludging vehicle                                                                     | character varying                    |
| created_at                         |                                   | Timestamp when the record was created (Auto Fill, Hidden)                                                   | timestamp without time zone          |
| updated_at                         |                                   | Timestamp when the record was last updated (Auto Fill, Hidden)                                              | timestamp without time zone          |
| deleted_at                         |                                   | Timestamp when the record was deleted (Auto Fill, Hidden)                                                   | timestamp without time zone          |

## Treatment Plant IMS

### Treatment Plants

Table Name: treatment_plants

Table Structure

| **Field Name**       | **Label**                           | **Description**                                                                                              | **Data Type**                                                    |
| -------------------- | ----------------------------------- | ------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------- |
| id                   |                                     | A unique identifier for each treatment plant                                                                 | integer pk                                                       |
| name                 | Name                                | The name of the treatment plant                                                                              | character varying                                                |
| ward                 | Ward                                | Ward number where treatment plant is located                                                                 | integer                                                          |
| location             | Location                            | The address/ location of the treatment plant                                                                 | character varying                                                |
| type                 | Treatment Plant Type                | The type of Treatment Plant (enum useD) ( Centralized WWTP\| Decentralized WWTP \|FSTP\|Co-Treatment Plant ) | integer with Check Constraint (only accepts values 1, 2, 3 or 4) |
| treatment_system     |                                     | The system of treatment plant in use                                                                         | character varying                                                |
| treatment_technology |                                     | The technology of treatment plant in use                                                                     | character varying                                                |
| capacity_per_day     | Capacity Per Day (m³)               | The capacity of the treatment plant (m3 per day)                                                             | numeric                                                          |
| caretaker_name       | Caretaker Name                      | The name of the caretaker of the facility                                                                    | character varying                                                |
| caretaker_gender     | Caretaker Gender                    | The gender of the caretaker for the facility                                                                 | character varying                                                |
| caretaker_number     | Caretaker Number                    | The phone number of the caretaker for the facility                                                           | big integer                                                      |
| status               | Status                              | The operational facility of the treatment plant                                                              | boolean                                                          |
| geom                 | Click To Set Latitude And Longitude | The geographic coordinates of the facility (represented as a point)                                          | geometry(Point,4326)                                             |
| created_at           |                                     | Timestamp when the record was created (Auto Fill, Hidden)                                                    | timestamp without time zone                                      |
| updated_at           |                                     | Timestamp when the record was last updated (Auto Fill, Hidden)                                               | timestamp without time zone                                      |
| deleted_at           |                                     | Timestamp when the record was deleted (Auto Fill, Hidden)                                                    | timestamp without time zone                                      |

**Enum values for Treatment Plant Type**
CentralizedWWTP = 1
DecentralizedWWTP = 2
FSTP = 3
CoTreatmentPlant = 4

### Treatment Plant Performance Efficiency Test

Table Name: treatmentplant_tests

| **Field Name**     | **Label**       | **Description**                                                    | **Data Type**                       |
| ------------------ | --------------- | ------------------------------------------------------------------ | ----------------------------------- |
| id                 |                 | Unique identifier for each test record                             | integer pk                          |
| treatment_plant_id | Treatment Plant | Identifier for the treatment plant                                 | integer fk:fsm.treatment_plants(id) |
| date               | Sample Date     | Date on which the sample was taken                                 | date                                |
| temperature        | Temperature °C  | Temperature of the sample in degrees Celsius                       | double precision                    |
| ph                 | pH              | pH level of the sample                                             | double precision                    |
| cod                | COD (mg/l)      | Concentration of Chemical Oxygen Demand in milligrams per liter    | double precision                    |
| bod                | BOD (mg/l)      | Concentration of Biochemical Oxygen Demand in milligrams per liter | double precision                    |
| tss                | TSS (mg/l)      | Concentration of Total Suspended Solids in milligrams per liter    | double precision                    |
| ecoli              | Ecoli           | Quantity of Escherichia coli bacteria in the sample                | integer                             |
| remarks            | Remark          | Remarks                                                            | character varying                   |
| user_id            |                 | Identifier for the user who created the record (Hidden)            | integer fk:auth.users(id)           |
| created_at         |                 | Timestamp when the record was created (Auto Fill, Hidden)          | timestamp without time zone         |
| updated_at         |                 | Timestamp when the record was last updated (Auto Fill, Hidden)     | timestamp without time zone         |
| deleted_at         |                 | Timestamp when the record was deleted (Auto Fill, Hidden)          | timestamp without time zone         |

## Emptying Service IMS

### Scheduled Desludging Schedule

Table Name: **desludging_schedule_temp**

This table stores a regenerated operational queue. Its building, containment and provider details are snapshots, not a replacement for the application, emptying, sludge-collection or feedback records. Owner and respondent PII is deliberately not stored in this table. The provider may be unassigned; the `next_emptying_date` column was added by a later migration. References below are logical, not database-enforced foreign keys.

| **Field Name** | **Label** | **Description** | **Data Type** |
| -------------- | --------- | --------------- | ------------- |
| id | | Unique identifier for the schedule row | bigint pk |
| service_provider_id | Service Provider | Assigned provider, if available (logical reference to `fsm.service_providers.id`) | bigint (nullable) |
| service_provider_name | Service Provider Name | Snapshot of the assigned provider name | character varying(255) (nullable) |
| bin | BIN | Building identification number associated with the containment | character varying(255) |
| ward | Ward Number | Building ward used for provider matching | integer (nullable) |
| house_number | House Number | Building house-number snapshot | character varying(255) (nullable) |
| house_locality | House Locality | Building locality snapshot | character varying(255) (nullable) |
| road_code | Road Code | Building road-code snapshot | character varying(255) (nullable) |
| containment_id | Containment ID | Scheduled containment (logical reference to `fsm.containments.id`) | character varying(255) |
| next_emptying_date | Next Emptying Date | Date copied from the containment after schedule allocation | date (nullable) |
| fstp_distance | FSTP Distance | Containment distance snapshot used in queue ordering | numeric(12,2) (nullable) |
| priority | Priority | Containment priority snapshot | smallint (nullable) |
| sequence | Sequence | Generated queue sequence | integer (nullable) |
| status | Status | Scheduling-status snapshot | smallint (nullable, DEFAULT 0) |
| generated_by | Generated By | Identifier of the user who generated the queue (logical reference to `auth.users.id`) | bigint (nullable) |
| generated_at | Generated At | Time at which the queue row was generated | timestamp without time zone (nullable) |
| created_at | | Timestamp when the row was created (Auto Fill, Hidden) | timestamp without time zone (nullable) |
| updated_at | | Timestamp when the row was last updated (Auto Fill, Hidden) | timestamp without time zone (nullable) |

Unique constraint: (`service_provider_id`, `containment_id`). Indexes: (`service_provider_id`, `status`, `sequence`), (`priority`, `fstp_distance`), and individual indexes on `containment_id`, `bin`, `ward`, `generated_at` and `next_emptying_date`. Because `service_provider_id` is nullable, PostgreSQL's unique constraint does not itself prevent multiple unassigned rows for the same containment.

### Application

Table Name: applications

| Field name                  | Label                     | description                                                                  | data_type                                         |
| --------------------------- | ------------------------- | ---------------------------------------------------------------------------- | ------------------------------------------------- |
| id                          |                           | Unique identifier for each application lodged                                | integer pk                                        |
| road_code                   | Street Name / Street Code | Identifier for the road that the building is connected                       | character varying fk:utility_info.roads(code)     |
| house_number                | House Numbers             | Unique identifier of the building with which the septic tank is connected    | character varying                                 |
| ward                        | Ward Number               | Ward number of the building                                                  | integer                                           |
| address                     |                           | Address of the building                                                      | character varying                                 |
| containment_id              |                           | Identifier for the containment connected to the building                     | character varying fk:fsm.containments(id)         |
| application_date            | Application Date          | Date when the application was lodged                                         | date                                              |
| customer_name               | Owner Name                | Versioned AES-256-GCM encrypted owner/customer name; decrypted only through authorized PII flows | text (nullable)                                   |
| customer_gender             | Owner Gender              | Versioned AES-256-GCM encrypted owner/customer gender                        | text (nullable)                                   |
| customer_contact            | Owner Contact (Phone)     | Versioned AES-256-GCM encrypted owner/customer contact; text accommodates the encryption envelope | text (nullable)                         |
| applicant_name              | Applicant Name            | Name of the applicant, can be same as owner                                  | character varying                                 |
| applicant_gender            | Applicant Gender          | Gender of the applicant                                                      | character varying                                 |
| applicant_contact           | Applicant Contact (Phone) | Contact number of the applicant                                              | big integer                                       |
| proposed_emptying_date      | Proposed Emptying Date    | Date when the septic tank is proposed to be emptied                          | date                                              |
| supervisory_assessment_date | Supervisory Assessment Date | Date recorded for the supervisory assessment                               | date (nullable)                                   |
| service_provider_id         | Service Provider Name     | Identifier for the service provider assigned to provide the emptying service | integer fk:fsm.service_providers(id)              |
| emergency_desludging_status | Emergency Desludging      | Boolean indicating whether the desludging is of high priority                | boolean                                           |
| user_id                     |                           | Identifier for the user who created the record (Hidden)                      | integer fk:auth.users(id)                         |
| approved_status             |                           | Boolean indicating application approval status                               | boolean (DEFAULT false)                           |
| emptying_status             | Emptying Status           | Boolean indicating the emptying status                                       | boolean (DEFAULT false)                           |
| feedback_status             | Feedback Status           | Boolean indicating feedback collection status                                | boolean (DEFAULT false)                           |
| sludge_collection_status    | Sludge Collection Status  | Boolean indicating sludge collection status                                  | boolean (DEFAULT false)                           |
| bin                         | BIN                       | Unique building identification number                                        | character varying fk:building_info.buildings(bin) |
| created_at                  |                           | Timestamp when the record was created (Auto Fill, Hidden)                    | timestamp without time zone                       |
| updated_at                  |                           | Timestamp when the record was last updated (Auto Fill, Hidden)               | timestamp without time zone                       |
| deleted_at                  |                           | Timestamp when the record was deleted (Auto Fill, Hidden)                    | timestamp without time zone                       |

### Emptying

Table Name: emptyings

| **Field Name**           | **Label**                       | **Description**                                                              | **Data Type**                          |
| ------------------------ | ------------------------------- | ---------------------------------------------------------------------------- | -------------------------------------- |
| id                       |                                 | Unique identifier for each emptied application                               | integer pk                             |
| application_id           | Application ID                  | Identifier for the application                                               | integer fk:fsm.applications(id)        |
| emptied_date             | Date                            | The date when the emptying service was provided                              | date                                   |
| service_receiver_name    | Service Receiver Name           | Name of person who was present at time of emptying                           | character varying                      |
| service_receiver_gender  | Service Receiver Gender         | Gender of service receiver                                                   | character varying                      |
| service_receiver_contact | Service Receiver Contact Number | Contact Number of service receiver                                           | big integer                            |
| emptying_reason          | Reason for Emptying             | The reason for which the containment was emptied                             | character varying                      |
| volume_of_sludge         | Sludge Volume (m³)              | The volume of sludge emptied in cubic meters (m³)                            | numeric                                |
| desludging_vehicle_id    | Desludging Vehicle Number Plate | Identifier for the desludging vehicle used for emptying                      | integer fk:fsm.desludging_vehicles(id) |
| treatment_plant_id       | Disposal Place                  | The treatment plant where the sludge was disposed                            | integer fk:fsm.treatment_plants(id)    |
| driver                   | Driver Name                     | Identifier for the driver of the desludging vehicle                          | integer fk:fsm.employees(id)           |
| emptier1                 | Emptier 1 Name                  | Identifier for the first person involved in the emptying process             | integer fk:fsm.employees(id)           |
| emptier2                 | Emptier 2 Name                  | Identifier for the second person involved in the emptying process            | integer fk:fsm.employees(id)           |
| start_time               | Start Time                      | The start time of the emptying process                                       | time                                   |
| end_time                 | End Time                        | The end time of the emptying process                                         | time                                   |
| no_of_trips              | No. of Trips                    | The number of trips required to empty the sludge                             | integer                                |
| receipt_number           | Receipt Number                  | Identifier for the receipt generated after the emptying process              | character varying                      |
| total_cost               | Total Cost                      | The total cost of the emptying process                                       | numeric                                |
| house_image              | House Image                     | Identifier for the image of the building from where the sludge was emptied   | character varying                      |
| receipt_image            | Receipt Image                   | Identifier for the image of the receipt generated after the emptying process | character varying                      |
| comments                 | Comments (if any)               | Any additional comments that were noted during service delivery              | text                                   |
| user_id                  |                                 | Identifier for the user who created the record (Hidden)                      | integer fk:auth.users(id)              |
| service_provider_id      |                                 | Identifier for the service provider who provided the emptying service        | integer fk:fsm.service_providers(id)   |
| created_at               |                                 | Timestamp when the record was created (Auto Fill, Hidden)                    | timestamp without time zone            |
| updated_at               |                                 | Timestamp when the record was last updated (Auto Fill, Hidden)               | timestamp without time zone            |
| deleted_at               |                                 | Timestamp when the record was deleted (Auto Fill, Hidden)                    | timestamp without time zone            |

### Sludge Collection

Table Name: **sludge_collections**

| Field Name            | Label                | Description                                                                      | Data type                              |
| --------------------- | -------------------- | -------------------------------------------------------------------------------- | -------------------------------------- |
| id                    |                      | Unique identifier for each sludge collection                                     | integer pk                             |
| application_id        | Application ID       | ID of the application                                                            | integer fk:fsm.applications(id)        |
| treatment_plant_id    | Treatment Plant Name | ID of the treatment plant where the sludge was disposed                          | integer fk:fsm.treatment_plants(id)    |
| volume_of_sludge      | Sludge Volume (m³)   | Volume of sludge disposed in cubic meters (m³)                                   | numeric                                |
| date                  | Date                 | Date of the sludge disposal                                                      | date                                   |
| no_of_trips           | No. of Trips         | Total number of trips                                                            | integer                                |
| entry_time            | Entry Time           | Entry time of vehicle into treatment plant                                       | time                                   |
| exit_time             | Exit Time            | Exit time of vehicle into treatment plant                                        | time                                   |
| desludging_vehicle_id |                      | ID of the desludging vehicle used for the sludge disposal                        | integer fk:fsm.desludging_vehicles(id) |
| user_id               |                      | Identifier for the user who created the record (Hidden)                          | integer fk:auth.users(id)              |
| service_provider_id   |                      | ID of the service provider who provided the emptying service (Auto Fill, Hidden) | integer fk:fsm.service_providers(id)   |
| created_at            |                      | Timestamp when the record was created (Auto Fill, Hidden)                        | timestamp without time zone            |
| updated_at            |                      | Timestamp when the record was last updated (Auto Fill, Hidden)                   | timestamp without time zone            |
| deleted_at            |                      | Timestamp when the record was deleted (Auto Fill, Hidden)                        | timestamp without time zone            |

### Feedback

Table Name: **feedbacks**

| **Field Name**      | **Label**                                              | **Description**                                                                                        | **Data Type**                        |
| ------------------- | ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------ | ------------------------------------ |
| id                  |                                                        | A unique identifier for each feedback                                                                  | integer pk                           |
| application_id      | Application ID                                         | A unique identifier for the application submitted by the customer                                      | integer fk:fsm.applications(id)      |
| customer_name       | Applicant Name                                         | Server-side snapshot of `fsm.applications.applicant_name`; encrypted application customer/owner fields are not copied here | character varying(255) (nullable) |
| customer_gender     | Applicant Gender                                       | Server-side snapshot of `fsm.applications.applicant_gender`                                            | character varying (nullable)         |
| customer_number     | Applicant Contact Number                               | Server-side snapshot of `fsm.applications.applicant_contact`                                           | bigint (nullable)                    |
| fsm_service_quality | Are you satisfied with the Service Quality?            | A boolean value indicating whether the customer is satisfied with the Service Quality                  | boolean                              |
| wear_ppe            | Did the sanitation workers wear PPE during desludging? | A boolean value indicating whether the service provider is wearing Personal Protective Equipment (PPE) | boolean                              |
| comments            | Comments                                               | Comments provided by the customer                                                                      | character varying                    |
| user_id             |                                                        | Identifier for the user who collected the feedback (Hidden)                                            | integer fk:auth.users(id)            |
| service_provider_id |                                                        | ID of the service provider who provided the emptying service (Hidden)                                  | integer fk:fsm.service_providers(id) |
| created_at          |                                                        | Timestamp when the record was created (Auto Fill, Hidden)                                              | timestamp without time zone          |
| updated_at          |                                                        | Timestamp when the record was last updated (Auto Fill, Hidden)                                         | timestamp without time zone          |
| deleted_at          |                                                        | Timestamp when the record was deleted (Auto Fill, Hidden)                                              | timestamp without time zone          |

### Help Desk

Table Name: **help_desks**

| Field Name          | Label                            | Description                                                    | Data type                            |
| ------------------- | -------------------------------- | -------------------------------------------------------------- | ------------------------------------ |
| id                  |                                  | Unique identifier for each help desk                           | integer pk                           |
| name                | Help Desk Name                   | Name of the help desk                                          | character varying                    |
| service_provider_id | Service provider (if associated) | Service provider id associated with the help desk if any       | integer fk:fsm.service_providers(id) |
| email               | Email Address                    | Email address of the help desk                                 | character varying                    |
| contact_number      | Contact Number                   | Contact number of the help desk                                | big integer                          |
| description         | Description                      | Additional information about the help desk                     | character varying                    |
| created_at          |                                  | Timestamp when the record was created (Auto Fill, Hidden)      | timestamp without time zone          |
| updated_at          |                                  | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone          |
| deleted_at          |                                  | Timestamp when the record was deleted (Auto Fill, Hidden)      | timestamp without time zone          |

**Lookup Tables**

Table Name: **containment_types**

| **Field Name**       | **Description**                                                                     | **Data Type**                                   |
| -------------------- | ----------------------------------------------------------------------------------- | ----------------------------------------------- |
| id                   | Unique identifier for the record                                                    | integer pk                                      |
| type                 | Type of containment with outlet connection                                          | character varying                               |
| sanitation_system_id | Sanitation system (technology) of the building                                      | integer fk:building_info.sanitation_systems(id) |
| dashboard_display    | Boolean value indicating if containment type is to be displayed in dashboard or not | boolean                                         |
| map_display          | Name to be displayed for containment type in map interface                          | character varying                               |

**Lookup table values for containment_types**

| **ID** | **Type**                                   | **sanitation_system_id** | **dashboard_display** | **map_display**  |
| ------------ | ------------------------------------------------ | ------------------------------ | --------------------------- | ---------------------- |
| 1            | Septic Tank connected to Sewer Network           | 3                              | true                        | Septic Tank            |
| 2            | Septic Tank connected to Drain Network           | 3                              | true                        | Septic Tank            |
| 3            | Septic Tank connected to Soak Pit                | 3                              | true                        | Septic Tank            |
| 4            | Septic Tank connected to Water Body              | 3                              | true                        | Septic Tank            |
| 5            | Septic Tank connected to Open Ground             | 3                              | true                        | Septic Tank            |
| 6            | Septic Tank without Outlet Connection            | 3                              | true                        | Septic Tank            |
| 7            | Septic Tank with Unknown Outlet Connection       | 3                              | true                        | Septic Tank            |
| 8            | Double Pit                                       | 4                              | true                        | Double Pit             |
| 9            | Permeable/ Unlined Pit                           | 4                              | true                        | Permeable/ Unlined Pit |
| 10           | Lined Pit connected to a Soak Pit                | 4                              | true                        | Lined Pit              |
| 11           | Lined Pit connected to Water Body                | 4                              | true                        | Lined Pit              |
| 12           | Lined Pit connected to Open Ground               | 4                              | true                        | Lined Pit              |
| 13           | Lined Pit connected to Sewer Network             | 4                              | true                        | Lined Pit              |
| 14           | Lined Pit connected to Drain Network             | 4                              | true                        | Lined Pit              |
| 15           | Lined Pit without Outlet                         | 4                              | true                        | Lined Pit              |
| 16           | Lined Pit with Unknown Outlet Connection         | 4                              | true                        | Lined Pit              |
| 17           | Lined Pit with Impermeable Walls and Open Bottom | 4                              | true                        | Lined Pit              |

## Performance Efficiency Standards

Schema Name: **public**

Table name: treatment_plant_performance_efficiency_test_settings

| **Field Name** | **Label**                   | **Description**                                                | **Data Type**               |
| -------------- | --------------------------- | -------------------------------------------------------------- | --------------------------- |
| id             |                             | Unique identifier                                              | integer pk                  |
| tss_standard   | TSS Standard (mg/I)         | Total suspended solids (TSS) Standard Value (mg/l)             | integer                     |
| ecoli_standard | ECOLI Standard (CFU/100 mL) | Ecoli Standard (CFU/100 mL)                                    | integer                     |
| ph_min         | PH Minimum                  | Minimum Ph value                                               | integer                     |
| ph_max         | PH Maximum                  | Maximum Ph value                                               | integer                     |
| bod_standard   | BOD Standard (mg/l)         | Biochemical oxygen demand (BOD) standard (mg/l)                | integer                     |
| created_at     |                             | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone |
| updated_at     |                             | Timestamp when the record was deleted (Auto Fill, Hidden)      | timestamp without time zone |
| deleted_at     |                             | Timestamp when the record was created (Auto Fill, Hidden)      | timestamp without time zone |

# Sewer Connection IMS

Schema Name: **sewer_connection**

Table name: sewer_connections

This table is used to store connection of sewers to other building.

| **Field Name** | **Label**  | **Description**                                                           | **Data Type**                                     |
| -------------- | ---------- | ------------------------------------------------------------------------- | ------------------------------------------------- |
| id             |            | Unique identifier for the record                                          | integer pk                                        |
| bin            | BIN        | Unique building identification number                                     | character varying fk:building_info.buildings(bin) |
| sewer_code     | Sewer Code | Unique code of the sewer network to which the building has connected with | character varying fk:utility_info.sewers(code)    |
| user_id        |            | Identifier for the user who created the record (Hidden)                   | integer fk:auth.users(id)                         |
| created_at     |            | Timestamp when the record was last updated (Auto Fill, Hidden)            | timestamp without time zone                       |
| updated_at     |            | Timestamp when the record was deleted (Auto Fill, Hidden)                 | timestamp without time zone                       |
| deleted_at     |            | Timestamp when the record was created (Auto Fill, Hidden)                 | timestamp without time zone                       |

# PT/CT IMS

Schema Name: fsm

The PT/CT Information Management System Module uses the following tables:

Data Tables:

-   toilets: stores the community toilets and public toilets information.
-   ctpt_users: stores the daily log of the users of the public toilet.

Relational Tables:

-   build_toilets: relational database that connects buildings that use community toilets when they do not have their own toilet. Foreign Key: bin and toilet_id.

## Public / Community Toilets

Table name: toilets

| Field Name                              | Label                                        | Description                                                                                                                                                  | Data type                   |
| --------------------------------------- | -------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------- |
| id                                      |                                              | Unique identifier for the toilet                                                                                                                             | integer pk                  |
| type                                    | Toilet Type                                  | Type of the toilet, such as "Public Toilet" or "Community Toilet"                                                                                            | character varying           |
| name                                    | Toilet Name                                  | Name of the toilet                                                                                                                                           | character varying           |
| ward                                    | Ward Number                                  | Ward number where the toilet is located                                                                                                                      | integer                     |
| location_name                           | Location                                     | The location of the the toilet                                                                                                                               | character varying           |
| bin                                     | House Number\| BIN                           | Building identification number(BIN) where the toilet is located. Only those buildings with use category Public Toilet or Community Toilet are displayed here | character varying           |
| access_frm_nearest_road                 | Distance From Nearest Road (m)               | Distance from the nearest road                                                                                                                               | integer                     |
| status                                  | Status                                       | Boolean indicating operational status of toilet                                                                                                              | boolean                     |
| caretaker_name                          | Caretaker Name                               | Caretaker of the toilet                                                                                                                                      | character varying           |
| caretaker_gender                        | Caretaker Gender                             | Gender of Caretaker                                                                                                                                          | character varying           |
| caretaker_contact_number                | Caretaker Contact                            | Contact number of the Caretaker of the toilet                                                                                                                | big integer                 |
| owner                                   | Owning Institution                           | Owning institution of the toilet                                                                                                                             | character varying           |
| owning_institution_name                 | Name of Owning Institution                   | Name of Owning Institution                                                                                                                                   | character varying           |
| operator_or_maintainer                  | Operate And Maintained By                    | Operator or maintainer of the toilet                                                                                                                         | character varying           |
| operator_or_maintainer_name             | Name of Operate and Maintained by            | Name of Operate and Maintained by                                                                                                                            | character varying           |
| total_no_of_toilets                     | Total Number of Seats                        | Total number of seats                                                                                                                                        | integer                     |
| total_no_of_urinals                     | Total Number of Urinals                      | Total Number of Urinals                                                                                                                                      | integer                     |
| male_or_female_facility                 | Separate Facility for Male and Female        | Indicates whether the toilet has separate facilities for males and females or not                                                                            | boolean                     |
| male_seats                              | No. of Seats For Male Users                  | Number of male seats available in the toilet                                                                                                                 | integer                     |
| female_seats                            | No. of Seats For Female Users                | Number of female seats available in the toilet                                                                                                               | integer                     |
| handicap_facility                       | Separate Facility for People with Disability | Indicates whether the toilet has facilities for the disabled or not                                                                                          | boolean                     |
| pwd_seats                               | No. of Seats for People with Disability      | Number of Seats for People with Disability                                                                                                                   | integer                     |
| children_facility                       | Separate Facility for Children               | Indicates whether the toilet has facilities for children or not                                                                                              | boolean                     |
| separate_facility_with_universal_design | Adherence with Universal Design Principles   | Adherence with Universal Design Principles                                                                                                                   | boolean                     |
| indicative_sign                         | Presence of Indicative Sign                  | Indicates whether the toilet has an indicative sign or not                                                                                                   | boolean                     |
| sanitary_supplies_disposal_facility     | Sanitary Supplies And Disposal Facilities    | Indicates whether the toilet has sanitary supplies and disposal facilities or not                                                                            | boolean                     |
| fee_collected                           | Uses Fee Collection                          | Indicates whether the fee is collected for using the toilet                                                                                                  | boolean                     |
| amount_of_fee_collected                 | Uses Fee Rate                                | Uses Fee Rate                                                                                                                                                | numeric                     |
| frequency_of_fee_collected              | Frequency of Fee Collection                  | Frequency of Fee Collection                                                                                                                                  | character varying           |
| geom                                    |                                              | Geometric location of the toilet (represented as a point)                                                                                                    | geometry(Point,4326)        |
| created_at                              |                                              | Timestamp when the record was created (Auto Fill, Hidden)                                                                                                    | timestamp without time zone |
| updated_at                              |                                              | Timestamp when the record was last updated (Auto Fill, Hidden)                                                                                               | timestamp without time zone |
| deleted_at                              |                                              | Timestamp when the record was deleted (Auto Fill, Hidden)                                                                                                    | timestamp without time zone |

## PT Users Log

Table name: **ctpt_users**

| **Field Name** | **Label**                   | **Description**                                                         | **Data type**               |
| -------------- | --------------------------- | ----------------------------------------------------------------------- | --------------------------- |
| id             |                             | Unique identifier for each data                                         | Integer pk                  |
| toilet_id      | Toilet Name                 | Unique identifier for the public toilet of which the data was collected | Integer fk:fsm.toilets(id)  |
| date           | Date                        | The date when the data was collected                                    | Date                        |
| no_male_user   | No. of Male Users (daily)   | The total number of male users that used the public toilet              | Integer                     |
| no_female_user | No. of Female Users (daily) | The total number of female users that used the public toilet            | Integer                     |
| created_at     |                             | Timestamp when the record was created (Auto Fill, Hidden)               | timestamp without time zone |
| updated_at     |                             | Timestamp when the record was last updated (Auto Fill, Hidden)          | timestamp without time zone |
| deleted_at     |                             | Timestamp when the record was deleted (Auto Fill, Hidden)               | timestamp without time zone |

**Relational Tables**

Table Name: **build_toilets**

| **Field Name** | **Description**                                                | **Data Type**                                     |
| -------------- | -------------------------------------------------------------- | ------------------------------------------------- |
| id             | Unique identifier for the record (auto generated)              | integer pk                                        |
| bin            | Unique building identification number                          | character varying fk:building_info.buildings(bin) |
| toilet_id      | Identifier for the toilet that the building is connected to    | integer fk:fsm.toilets(id)                        |
| created_at     | Timestamp when the record was created (Auto Fill, Hidden)      | timestamp without time zone                       |
| updated_at     | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone                       |
| deleted_at     | Timestamp when the record was deleted (Auto Fill, Hidden)      | timestamp without time zone                       |

# CWIS IMS

The CWIS Information Management System Module uses the following tables:

## CWIS Generator

Schema Name: **cwis**

**Data Table:**

-   data_cwis: stores yearly information related indicators and its value generated from the system or user input.

**Lookup Table:**

-   data_source: stores with information related indicators, its data types and more.
-   site_settings: stores site settings related to CWIS IMS.

Table name: data_cwis

| **Field Name** | **Description**                                                | **Data type**                               |
| -------------- | -------------------------------------------------------------- | ------------------------------------------- |
| id             | Unique identifier for the record (auto generated)              | Integer pk                                  |
| outcome        | CWIS outcome                                                   | character varying                           |
| indicator_code | A unique identifier for the indicator generated                | Integer fk:cwis.data_source(indicator_code) |
| label          | A label to be displayed for the indicator                      | character varying                           |
| year           | Year of data CWIS data generated                               | integer                                     |
| data_value     | Actual value of the cwis Indicator of each year                | text                                        |
| created_at     | Timestamp when the record was created (Auto Fill, Hidden)      | timestamp without time zone                 |
| updated_at     | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone                 |
| deleted_at     | Timestamp when the record was deleted (Auto Fill, Hidden)      | timestamp without time zone                 |

Table name: data_source

| **Field Name** | **Description**                                   | **Data type**     |
| -------------- | ------------------------------------------------- | ----------------- |
| id             | Unique identifier for the record (auto generated) | Integer pk        |
| outcome        | CWIS outcome                                      | character varying |
| indicator_code | A unique identifier for the indicator generated   | character varying |
| label          | A label to be displayed for the indicator         | character varying |

**Lookup table values for data_source**

| id | outcome | indicator_code | label                                                                                                                                                     |
| -- | ------- | -------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1  | equity  | EQ-1           | Ratio of LIC access to total population access                                                                                                            |
| 2  | safety  | SF-1a          | Percentage of population with access to safe, private, individual toilets/latrines                                                                        |
| 3  | safety  | SF-1b          | Percentage of on-site sanitation that have been desludged                                                                                                 |
| 4  | safety  | SF-1c          | Percentage of collected FS disposed at a treatment plant or at designated disposal site                                                                   |
| 5  | safety  | SF-1d          | FS treatment capacity as a percentage of total FS generated from NSS connections (excluding safely disposed in situ)                                      |
| 6  | safety  | SF-1e          | FS treatment capacity as a percentage of total FS collected from NSS connections                                                                          |
| 7  | safety  | SF-1f          | Wastewater treatment capacity as a percentage of total wastewater generated from sewered connections and greywater generated from non-sewered connections |
| 8  | safety  | SF-1g          | Effectiveness of FS/WW treatment in meeting prescribed standards for effluent discharge and biosolids disposal                                            |
| 9  | safety  | SF-2a          | Percentage LIC population with access to safe individual toilets                                                                                          |
| 10 | safety  | SF-2b          | Percentage of LIC, NSS, IHHLs that have been desludged                                                                                                    |
| 11 | safety  | SF-2c          | Percentage of collected FS (collected from LIC) disposed at treatment plant or designated disposal sites                                                  |
| 12 | safety  | SF-3           | Percentage of dependent population (those without access to a private toilet/latrine) with access to safe shared facilities (CT/PT)                       |
| 13 | safety  | SF-3b          | Percentage of CTs that adhere to principles of universal design                                                                                           |
| 14 | safety  | SF-3c          | Percentage of users of CTs that are women                                                                                                                 |
| 15 | safety  | SF-3e          | Average distance from the house to the closest CT (in meters)                                                                                             |
| 16 | safety  | SF-4a          | Percentage of PTs where FS and WW generated is safely transported to TP or safely disposed in situ                                                        |
| 17 | safety  | SF-4b          | Percentage of PTs that adhere to principles of universal design                                                                                           |
| 18 | safety  | SF-4d          | Percentage of users of PTs that are women                                                                                                                 |
| 19 | safety  | SF-5           | Percentage of educational institutions where FS/WW generated is safely transported to TP or safely disposed in situ                                       |
| 20 | safety  | SF-6           | Percentage of healthcare facilities where FS/WW generated is safely transported to TP or safely disposed in situ                                          |
| 21 | safety  | SF-7           | Percentage of desludging services completed mechanically or semi-mechanically (by a gulper)                                                               |
| 22 | safety  | SF-9           | Percentage of tests which are in compliance with water quality standards for fecal coliform                                                               |

## CWIS Setting

Schema Name: **public**

Table name: **site_settings**

This table stores the value that is input from the editable form and is used as lookup table for CWIS models and dashboard.

| **Field Name** | **Description**                                                | **Data Type**               |
| -------------- | -------------------------------------------------------------- | --------------------------- |
| id             | Unique identifier                                              | integer pk                  |
| name           | Variable name                                                  | character varying           |
| value          | Value input from the editable form                             | character varying           |
| category       | Category of site settings (cwis_setting)                       | character varying           |
| created_at     | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone |
| updated_at     | Timestamp when the record was deleted (Auto Fill, Hidden)      | timestamp without time zone |
| deleted_at     | Timestamp when the record was created (Auto Fill, Hidden)      | timestamp without time zone |

**Lookup table values for data_source**

| id | name                                                       | value | category     |
| -- | ---------------------------------------------------------- | ----- | ------------ |
| 1  | average_water_consumption_lpcd                             | 150   | cwis_setting |
| 2  | waste_water_conversion_factor                              | 80    | cwis_setting |
| 3  | greywater_conversion_factor_connected_to_sewer             | 80    | cwis_setting |
| 4  | greywater_conversion_factor_not_connected_to_sewer         | 80    | cwis_setting |
| 5  | fs_generation_from_containment_not_connected_to_sewer_lpcd | 270   | cwis_setting |
| 6  | fs_generation_from_permeable_or_unlined_pit_lpcd           | 280   | cwis_setting |

**Scheduled desludging settings in the existing `public.site_settings` table**

The scheduling migration adds rows with category `desludging_schedule`; it does not create a new table or columns. IDs are allocated from the current maximum, so fixed IDs are not listed here. Values are stored as strings.

| name | Default value | Description |
| ---- | ------------- | ----------- |
| Schedule Desludging Start Date | empty string | Optional first allocation date (`YYYY-MM-DD`). |
| Schedule Regeneration Period | 6 | Number of days added to today when a saved start date is already past. |
| Trip Capacity Per Day | 20 | Configured daily trip capacity used by allocation. |
| Weekend | Friday,Saturday | Comma-separated weekdays excluded from allocation. |
| Holiday Dates | empty string | Comma-separated `YYYY-MM-DD` dates excluded from allocation. |

## NSD Setting

Schema Name: **cwis**

Table name: **nsd-setting**

This table stores the credentials and API endpoint URLs required for integration with the National Sanitation Dashboard (NSD).

| **Field Name** | **Description**                                                | **Data type**               |
| -------------- | -------------------------------------------------------------- | --------------------------- |
| id             | Unique identifier for the record (auto generated)              | bigint pk                   |
| nsd_username   | Username for NSD API authentication                            | character varying (256)     |
| city           | Name of the municipality / city                                 | character varying (256)     |
| api_post_url   | API endpoint URL to post CWIS indicator data to NSD            | text                        |
| api_login_url  | API endpoint URL to authenticate with NSD                      | text                        |
| nsd_password   | Password for NSD API authentication                            | character varying (256)     |
| created_at     | Timestamp when the record was created (Auto Fill, Hidden)      | timestamp without time zone |
| updated_at     | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone |
| deleted_at     | Timestamp when the record was deleted (Auto Fill, Hidden)      | timestamp without time zone |

## KPI Target

Schema Name: **fsm**

**Data Tables**

-   kpi_targets: stores with information related to KPI targets.

**Lookup Tables**

-   key_performance_indicators: stores indicators attributes used as dropdowns.
-   quarters : stores information of quarters for each year.

Table name: **kpi_targets**

| **Field Name** | **Label** | **Description**                                                | **Data type**               |
| -------------- | --------- | -------------------------------------------------------------- | --------------------------- |
| id             |           | A unique identifier for each record                            | integer pk                  |
| indicator_id   | Indicator | A unique identifier for each indicator                         | integer                     |
| year           | Year      | Year for which KPI target is fixed                             | integer                     |
| target         | Target(%) | Target value of KPI for the year                               | integer                     |
| created_at     |           | Timestamp when the record was created (Auto Fill, Hidden)      | timestamp without time zone |
| updated_at     |           | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone |
| deleted_at     |           | Timestamp when the record was deleted (Auto Fill, Hidden)      | timestamp without time zone |

**Lookup Tables**

Schema Name: **fsm**

Table Name: **key_performance_indicators**

| **Field Name** | **Description**                  | **Data Type**     |
| -------------- | -------------------------------- | ----------------- |
| id             | Unique identifier for the record | integer pk        |
| indicator      | Name of each indicator           | character varying |

**Lookup table values for key_performance_indicators**

| **id** | **indicator**                         |
| ------ | ------------------------------------- |
| 1      | Application Response Efficiency       |
| 2      | Customer Satisfaction                 |
| 3      | PPE Compliance                        |
| 4      | Safe Desludging                       |
| 5      | Faecal Sludge Collection Ratio (FSCR) |
| 6      | Response Time                         |
| 7      | Inclusion                             |

Table Name: **quarters**

| **Field Name** | **Description**                                                | **Data Type**               |
| -------------- | -------------------------------------------------------------- | --------------------------- |
| quarterid      | Unique identifier for the record                               | integer pk                  |
| quartername    | Type of sanitation system of the building                      | character varying           |
| start_time     | Timestamp when the record was created (Auto Fill, Hidden)      | timestamp without time zone |
| end_time       | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone |
| year           | Timestamp when the record was deleted (Auto Fill, Hidden)      | integer                     |

**The Lookup table values for quarters are auto filled yearly. But it might need attention during initial setup.**
**During initial setup, the command `php artisan kpi:cron` can be used for the upcoming year OR directly import the data from csv for the required year. Below is the sample data:**

| **quarterid** | **quartername** | **starttime**  | **endtime**      | **year** |
| ------------- | --------------- | -------------- | ---------------- | -------- |
| 1             | Q1              | 1/1/2024 0:00  | 3/31/2024 23:59  | 2024     |
| 2             | Q2              | 4/1/2024 0:00  | 6/30/2024 23:59  | 2024     |
| 3             | Q3              | 7/1/2024 0:00  | 9/30/2024 23:59  | 2024     |
| 4             | Q4              | 10/1/2024 0:00 | 12/31/2024 23:59 | 2024     |
| 5             | Q1              | 1/1/2023 0:00  | 3/31/2023 23:59  | 2023     |
| 6             | Q2              | 4/1/2023 0:00  | 6/30/2023 23:59  | 2023     |
| 7             | Q3              | 7/1/2023 0:00  | 9/30/2023 23:59  | 2023     |
| 8             | Q4              | 10/1/2023 0:00 | 12/31/2023 23:59 | 2023     |

# Utility IMS

The Utility IMS Module uses the following tables:

Schema Name: **utility_info**

Data Tables

-   roads: stores information of the road infrastructure.
-   sewers: stores information of the sewer network of the city.
-   water_supplys: stores information of the water supply networks of the city.
-   drains: stores information of the drainage networks of the city.

## Road Network Information

Table name: **roads**

| **Field Name** | **Label**          | **Description**                                                                                | **Data Type**                  |
| -------------- | ------------------ | ---------------------------------------------------------------------------------------------- | ------------------------------ |
| name           | Road Name          | The name of the road                                                                           | character varying              |
| code           | Code               | A unique identifier for each road (City endorsed road code, if available)                      | character varying pk           |
| hierarchy      | Hierarchy          | The hierarchy of road based on its network (Strategic Urban Road\| Feeder Road \| Other Road ) | character varying              |
| right_of_way   | Right of Way (m)   | The width of the road (right of way) in meters                                                 | numeric                        |
| carrying_width | Carrying Width (m) | The carriageway width of the road that can be used for traffic in meters                       | numeric                        |
| surface_type   | Surface Type       | The type of surface on the road (Earthen\| Gravelled \| Metalled \| Brick Paved)               | character varying              |
| length         | Road Length (m)    | The length of the road in meters                                                               | numeric                        |
| geom           |                    | The geometric shape of the road (represented as a linestring)                                  | geometry(MultiLineString,4326) |
| user_id        |                    | Identifier for the user who created the record (Hidden)                                        | integer fk:auth.users(id)      |
| created_at     |                    | Timestamp when the record was created (Auto Fill, Hidden)                                      | timestamp without time zone    |
| updated_at     |                    | Timestamp when the record was last updated (Auto Fill, Hidden)                                 | timestamp without time zone    |
| deleted_at     |                    | Timestamp when the record was deleted (Auto Fill, Hidden)                                      | timestamp without time zone    |

## Sewer Network Information

Table name: **sewers**

| **Field Name**     | **Label**       | **Description**                                                                  | **Type**                                      |
| ------------------ | --------------- | -------------------------------------------------------------------------------- | --------------------------------------------- |
| code               | Code            | Unique identifier for the sewer section (City endorsed sewer code, if available) | Character varying pk                          |
| road_code          | Road Code       | Corresponding road code                                                          | character varying fk:utility_info.roads(code) |
| location           | Location        | Location of the sewer section (Middle\| Left \| Right ) side of the road         | character varying                             |
| length             | Length (m)      | Length of the sewer section in meters                                            | numeric                                       |
| diameter           | Diameter (mm)   | Diameter of the sewer section in mm                                              | numeric                                       |
| treatment_plant_id | Treatment Plant | Corresponding treatment plant ID, if network is treated                          | integer fk:fsm.treatment_plants(id)           |
| geom               |                 | Geometric information for the sewer network (represented as a linestring)        | geometry(MultiLineString,4326)                |
| user_id            |                 | Identifier for the user who created the record (Hidden)                          | integer fk:auth.users(id)                     |
| created_at         |                 | Timestamp when the record was created (Auto Fill, Hidden)                        | timestamp without time zone                   |
| updated_at         |                 | Timestamp when the record was last updated (Auto Fill, Hidden)                   | timestamp without time zone                   |
| deleted_at         |                 | Timestamp when the record was deleted (Auto Fill, Hidden)                        | timestamp without time zone                   |

## Water Supply Network Information

Table name: **water_supplys**

| **Field Name** | **Label**     | **Description**                                                                             | **Data Type**                                 |
| -------------- | ------------- | ------------------------------------------------------------------------------------------- | --------------------------------------------- |
| code           | Code          | A unique identifier for each water supply pipeline (City endorsed sewer code, if available) | character varying pk                          |
| road_code      | Road Code     | Corresponding road code                                                                     | character varying fk:utility_info.roads(code) |
| project_name   | Project Name  | Name of the Project                                                                         | character varying                             |
| type           | Type          | Type of the pipeline (Main\| Secondary \| Distribution)                                     | character varying                             |
| material_type  | Material Type | Type of the pipe material ( HDPE\| GI \| Others)                                            | character varying                             |
| diameter       | Diameter (mm) | The diameter of the water supply pipe in mm                                                 | numeric                                       |
| length         | Length (m)    | The length of the water supply pipe in meter                                                | numeric                                       |
| geom           |               | The geometry of the water supply pipe (represented as a linestring)                         | geometry(MultiLineString,4326)                |
| user_id        |               | Identifier for the user who created the record (Hidden)                                     | integer fk:auth.users(id)                     |
| created_at     |               | Timestamp when the record was created (Auto Fill, Hidden)                                   | timestamp without time zone                   |
| updated_at     |               | Timestamp when the record was last updated (Auto Fill, Hidden)                              | timestamp without time zone                   |
| deleted_at     |               | Timestamp when the record was deleted (Auto Fill, Hidden)                                   | timestamp without time zone                   |

## Drain Network Information

Table name: **drains**

| **Field Name**     | **Label**       | **Description**                                                | **Data Type**                                 |
| ------------------ | --------------- | -------------------------------------------------------------- | --------------------------------------------- |
| code               | Code            | A unique identifier for each drain                             | character varying pk                          |
| road_code          | Road Code       | Corresponding road code                                        | character varying fk:utility_info.roads(code) |
| cover_type         | Cover Type      | The type of the drain cover (Open\| Closed \| Unknown)         | character varying                             |
| surface_type       | Surface Type    | The type of the surface lining (Lined\| Unlined \| Unknown)    | character varying                             |
| size               | Width (mm)      | The size of the drain in mm                                    | numeric                                       |
| length             | Length (m)      | The length of the drain in meter                               | numeric                                       |
| treatment_plant_id | Treatment Plant | Corresponding treatment plant id                               | integer fk:fsm.treatment_plants(id)           |
| geom               |                 | The geometry of the drain (represented as a linestring)        | geometry(MultiLineString,4326)                |
| user_id            |                 | Identifier for the user who created the record (Hidden)        | integer fk:auth.users(id)                     |
| created_at         |                 | Timestamp when the record was created (Auto Fill, Hidden)      | timestamp without time zone                   |
| updated_at         |                 | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone                   |
| deleted_at         |                 | Timestamp when the record was deleted (Auto Fill, Hidden)      | timestamp without time zone                   |

# Solid Waste ISS

The SWM Waste Information Support System Module uses the following tables:

Schema Name: **swm_info**

Data Tables

-   swmservice_payment_status: Stores swm service payment records after the data has been analyzed and matched with the swm_customer_id field of the Building IMS data.
-   swmservice_payments: Stores swm service payment records temporarily during import.

Lookup Tables

-   due_years: Reference table for due year classification.

Table name: **swmservice_payment_status**

| **Field Name**         | **Label**         | **Description**                                                | **Data Type**               |
| ---------------------- | ----------------- | -------------------------------------------------------------- | --------------------------- |
| swm_customer_id        |                   | Unique identifier for solid waste management customer record   | Integer                     |
| bin                    |                   | Corresponding Building identification number for customer ID   | Character varying           |
| tax_code               | Tax Code          | Tax code of the building SWM service is provided to            | Character varying           |
| ward                   | Ward              | Ward number where the building is located                      | Integer                     |
| building_associated_to |                   | Associated Building number or ID associated to the SWM service | Character varying           |
| customer_name          | Customer Name     | Name of the SWM service customer                               | Character varying           |
| customer_contact       | Customer Contact  | Phone number of the SWM service customer                       | Big Integer                 |
| last_payment_date      | Last Payment Date | Date when the last payment was made (Format:YYYY-MM-DD)        | Date                        |
| due_year               | Due Year          | Year for which the payment is due                              | Integer                     |
| geom                   |                   | Geospatial data of the building (represented as a polygon)     | geometry(MultiPolygon,4326) |
| created_at             |                   | Timestamp when the record was created (Auto Fill, Hidden)      | timestamp without time zone |
| updated_at             |                   | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone |
| deleted_at             |                   | Timestamp when the record was deleted (Auto Fill, Hidden)      | timestamp without time zone |

**swmservice_payments**

| **Field Name**    | **Description**                                           | **Data Type**               |
| ----------------- | --------------------------------------------------------- | --------------------------- |
| id                | Unique identifier for each record                         | Integer pk                  |
| bin               | Building identification number                            | character varying           |
| customer_name     | Name of the SWM servicecustomer                           | character varying           |
| customer_contact  | Phone number of the SWM service customer                  | bigint                      |
| last_payment_date | Date when the last payment was made (Format:YYYY-MM-DD)   | date                        |
| created_at        | Timestamp when the record was created (Auto Fill, Hidden) | timestamp without time zone |
| updated_at        | Timestamp when the record was created (Auto Fill, Hidden) | timestamp without time zone |

**due_years**

| **Field Name** | **Description**                   | **Data Type**     |
| -------------- | --------------------------------- | ----------------- |
| id             | Unique identifier for each record | Integer pk        |
| name           | Name for the due years category   | character varying |
| value          | Value for the due years category  | integer           |

**lookup table for due_years**

| **Id** | **Name** | **Value** |
| ------ | -------- | --------- |
| 1      | No Due   | 0         |
| 2      | 1 Year   | 1         |
| 3      | 2 Years  | 2         |
| 4      | 3 Years  | 3         |
| 5      | 4 Years  | 4         |
| 6      | 5 Years+ | 5         |
| 7      | No Data  | 99        |

# Property Tax Collection IMS

The Property Tax Collection Information Support System Module uses the following tables:

Schema Name: **taxpayment_info**

Data Tables

-   tax_payment_status: Stores Tax Payment records after the data has been analyzed and matched with tax_code field of the Building IMS data.
-   tax_payments: Stores Tax Payment records temporarily during import.

Lookup Tables

-   due_years: Reference table for due year classification.

### Data Tables

Table name: **tax_payment_status**

| **Field Name**         | **Label**         | **Description**                                                | **Data Type**               |
| ---------------------- | ----------------- | -------------------------------------------------------------- | --------------------------- |
| tax_code               | Tax Code          | Building’s tax code/ holding ID that is assigned by the city   | Character varying           |
| bin                    |                   | Corresponding building identification number for the tax code  | Character varying           |
| ward                   | Ward              | Ward number where the property is located                      | Integer                     |
| building_associated_to |                   | Auxiliary Building number or ID associated to the property     | Character varying           |
| last_payment_date      | Last Payment Date | Date when the last payment was made (Format:YYYY-MM-DD)        | Date                        |
| due_year               | Due Year          | Year for which the payment is due                              | Integer                     |
| geom                   |                   | Geospatial data of the building (represented as a polygon)     | geometry(MultiPolygon,4326) |
| created_at             |                   | Timestamp when the record was created (Auto Fill, Hidden)      | timestamp without time zone |
| updated_at             |                   | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone |
| deleted_at             |                   | Timestamp when the record was deleted (Auto Fill, Hidden)      | timestamp without time zone |

**tax_payments**

| **Field Name** | **Description**                                     | **Data Type**         |
| -------------------- | --------------------------------------------------------- | --------------------------- |
| id                   | Unique identifier for each record                         | Integer pk                  |
| tax_code             | Identifier for the building’s tax code/ holding ID       | character varying           |
| owner_name           | Versioned AES-256-GCM encrypted property-owner name       | text (nullable)             |
| owner_contact        | Versioned AES-256-GCM encrypted property-owner contact    | text (nullable)             |
| last_payment_date    | Date when the last payment was made (Format:YYYY-MM-DD)   | date                        |
| created_at           | Timestamp when the record was created (Auto Fill, Hidden) | timestamp without time zone |
| updated_at           | Timestamp when the record was created (Auto Fill, Hidden) | timestamp without time zone |

### Lookup Tables

**due_years**

| **Field Name** | **Description**                   | **Data Type**     |
| -------------- | --------------------------------- | ----------------- |
| id             | Unique identifier for each record | Integer pk        |
| name           | Name for the due years category   | character varying |
| value          | Value for the due years category  | integer           |

**lookup table for due_years**

| **Id** | **Name** | **Value** |
| ------ | -------- | --------- |
| 1      | No Due   | 0         |
| 2      | 1 Year   | 1         |
| 3      | 2 Years  | 2         |
| 4      | 3 Years  | 3         |
| 5      | 4 Years  | 4         |
| 6      | 5 Years+ | 5         |
| 7      | No Data  | 99        |

# Water Supply ISS

The Water Supply Information Support System Module uses the following tables:

Schema Name: watersupply_info

Data Tables

-   watersupply_payment_status: Stores Watersupply Payment records after the data has been analyzed and matched with water_customer_id field of the Building IMS data.
-   watersupply_payments: Stores Watersupply Payment records temporarily during import.

Lookup Tables

-   due_years: Reference table for due year classification.

Table name: **watersupply_payment_status**

| **Field Name**   | **Label**   | **Description**                                                                   | **Data Type**         |
| ---------------------- | ----------------- | --------------------------------------------------------------------------------------- | --------------------------- |
| water_customer_id      |                   | Unique identifier for the water supply customer record                                  | Integer                     |
| bin                    |                   | Corresponding building identification number for the water customer id                  | Character varying           |
| tax_code               | Tax Code          | Unique identifier for each tax record                                                   | Character varying           |
| ward                   | Ward              | Ward number of the building                                                             | Integer                     |
| building_associated_to |                   | Description of the building                                                             | Character varying           |
| customer_name          | Customer Name     | Name of the water supply customer                                                       | Character varying           |
| customer_contact       | Customer Contact  | Contact number of the water supply customer                                             | Big Integer                 |
| last_payment_date      | Last Payment Date | Date of last payment made by the customer for taxes or water supply (Format:YYYY-MM-DD) | Date                        |
| due_year               | Years Due         | Year for which the tax or water supply payment is due                                   | Integer                     |
| geom                   |                   | Geospatial data of the building (represented as a polygon)                              | geometry(MultiPolygon,4326) |
| created_at             |                   | Timestamp when the record was created (Auto Fill, Hidden)                               | Timestamp                   |
| updated_at             |                   | Timestamp when the record was last updated (Auto Fill, Hidden)                          | Timestamp                   |
| deleted_at             |                   | Timestamp when the record was deleted (Auto Fill, Hidden)                               | Timestamp                   |

**watersupply_payments**

| **Field Name** | **Description**                                     | **Data Type**         |
| -------------------- | --------------------------------------------------------- | --------------------------- |
| id                   | Unique identifier for each record                         | Integer pk                  |
| water_customer_id    | Identifier for the building’s tax code/ holding ID       | character varying           |
| customer_name        | Name of the customer                                      | character varying           |
| customer_contact     | Phone number of the customer                              | bigint                      |
| last_payment_date    | Date when the last payment was made (Format:YYYY-MM-DD)   | date                        |
| created_at           | Timestamp when the record was created (Auto Fill, Hidden) | timestamp without time zone |
| updated_at           | Timestamp when the record was created (Auto Fill, Hidden) | timestamp without time zone |

**due_years**

| **Field Name** | **Description**                   | **Data Type**     |
| -------------- | --------------------------------- | ----------------- |
| id             | Unique identifier for each record | Integer pk        |
| name           | Name for the due years category   | character varying |
| value          | Value for the due years category  | integer           |

**lookup table for due_years**

| **Id** | **Name** | **Value** |
| ------ | -------- | --------- |
| 1      | No Due   | 0         |
| 2      | 1 Year   | 1         |
| 3      | 2 Years  | 2         |
| 4      | 3 Years  | 3         |
| 5      | 4 Years  | 4         |
| 6      | 5 Years+ | 5         |
| 7      | No Data  | 99        |

# Urban Management DSS

# Map Feature Layers

## Wards

This table is used as summary table that includes wardwise summary count data, which is displayed through the Ward Wise Info Layer in the map interface.

| **Field Name**                        | **Description**                                                                      | **Data Type**               |
| ------------------------------------- | ------------------------------------------------------------------------------------ | --------------------------- |
| ward                                  | Unique identifier for each Ward                                                      | integer pk                  |
| geom                                  | The geometry of the ward (represented as a polygon)                                  | geometry(MultiPolygon,4326) |
| area                                  | Area of the ward polygon in square kilometer (km²)                                   | double precision            |
| total_rdlen                           | Total length of roads within ward geom                                               | double precision            |
| no_build                              | Summary count of no of buildings within ward geom                                    | integer                     |
| no_popsrv                             | Total no of population served within ward geom                                       | integer                     |
| no_hhsrv                              | Total no of household served within ward geom                                        | integer                     |
| no_rcc_framed                         | Summary count of no of rcc framed buildings within ward geom                         | integer                     |
| no_load_bearing                       | Summary count of no of load bearing buildings within ward geom                       | integer                     |
| no_wooden_mud                         | Summary count of no of wooden buildings within ward geom                             | integer                     |
| no_cgi_sheet                          | Summary count of no of cgi sheet buildings within ward geom                          | integer                     |
| no_build_directly_to_sewerage_network | Summary count of no of building directly connected to sewer network within ward geom | integer                     |
| no_contain                            | Summary count of no of containments within ward geom                                 | integer                     |
| no_septic_tank                        | Summary count of no of septic tanks within ward geom                                 | integer                     |
| no_pit_holding_tank                   | Summary count of no of pit/holding tanks within ward geom                            | integer                     |
| no_emptying                           | Total count of no of emptying of containments within ward geom                       | integer                     |
| bldgtaxpdprprtn                       | Building to tax payment proportion within ward geom                                  | double precision            |
| wtrpmntprprtn                         | Building to water supply payment proportion within ward geom                         | double precision            |
| swmsrvpmntprprtn                      | Building to SWM service payment proportion within ward geom                          | double precision            |
| created_at                            | Timestamp when the record was last created (Auto Fill, Hidden)                       | timestamp without time zone |
| updated_at                            | Timestamp when the record was last updated (Auto Fill, Hidden)                       | timestamp without time zone |
| deleted_at                            | Timestamp when the record was deleted (Auto Fill, Hidden)                            | timestamp without time zone |

## Grids

This table is used as summary table that includes gridwise summary count data, which is displayed through the Summarized Grids (0.5km) Layer in the map interface.

| **Field Name**                        | **Description**                                                                      | **Data Type**               |
| ------------------------------------- | ------------------------------------------------------------------------------------ | --------------------------- |
| id                                    | Unique identifier for each Grid                                                      | integer pk                  |
| geom                                  | The geometry of the grid (represented as a polygon)                                  | geometry(MultiPolygon,4326) |
| total_rdlen                           | Total length of roads within grid geom                                               | double precision            |
| no_build                              | Summary count of no of buildings within grid geom                                    | integer                     |
| no_popsrv                             | Total no of population served within grid geom                                       | integer                     |
| no_hhsrv                              | Total no of household served within grid geom                                        | integer                     |
| no_rcc_framed                         | Summary count of no of rcc framed buildings within grid geom                         | integer                     |
| no_load_bearing                       | Summary count of no of load bearing buildings within grid geom                       | integer                     |
| no_wooden_mud                         | Summary count of no of wooden buildings within grid geom                             | integer                     |
| no_cgi_sheet                          | Summary count of no of cgi sheet buildings within grid geom                          | integer                     |
| no_build_directly_to_sewerage_network | Summary count of no of building directly connected to sewer network within grid geom | integer                     |
| no_contain                            | Summary count of no of containments within grid geom                                 | integer                     |
| no_septic_tank                        | Summary count of no of septic tanks within grid geom                                 | integer                     |
| no_pit_holding_tank                   | Summary count of no of pit/holding tanks within grid geom                            | integer                     |
| no_emptying                           | Total count of no of emptying of containments within grid geom                       | integer                     |
| bldgtaxpdprprtn                       | Building to tax payment proportion within grid geom                                  | double precision            |
| wtrpmntprprtn                         | Building to water supply payment proportion within grid geom                         | double precision            |
| swmsrvpmntprprtn                      | Building to SWM service payment proportion within grid geom                          | double precision            |
| created_at                            | Timestamp when the record was last created (Auto Fill, Hidden)                       | timestamp without time zone |
| updated_at                            | Timestamp when the record was last updated (Auto Fill, Hidden)                       | timestamp without time zone |
| deleted_at                            | Timestamp when the record was deleted (Auto Fill, Hidden)                            | timestamp without time zone |

# Citypolys

This table stores city polygon geometry data, which is displayed through the Municipality Layer in the map interface.

| **Field Name** | **Description**                                                | **Data Type**               |
| -------------- | -------------------------------------------------------------- | --------------------------- |
| id             | Unique identifier for each City Polygon                        | integer pk                  |
| name           | Name of the City                                               | character varying           |
| area           | Area of the City polygon in square kilometer (km²)             | double precision            |
| geom           | The geometry of the citypolygon (represented as a polygon)     | geometry(MultiPolygon,4326) |
| created_at     | Timestamp when the record was last created (Auto Fill, Hidden) | timestamp without time zone |
| updated_at     | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone |
| deleted_at     | Timestamp when the record was deleted (Auto Fill, Hidden)      | timestamp without time zone |

# Ward Boundary

This table stores Ward Boundary Polygon geometry data, which is displayed through the WArd Boundary Layer in the map interface.

| **Field Name** | **Description**                                                 | **Data Type**               |
| -------------- | --------------------------------------------------------------- | --------------------------- |
| ward           | Ward Number of the Ward Boundary Polygon                        | integer pk                  |
| area           | Area of the Ward Boundary in square kilometer (km²)             | double precision            |
| geom           | The geometry of the Ward Boundary (represented by multipolygon) | geometry(MultiPolygon,4326) |
| created_at     | Timestamp when the record was last created (Auto Fill, Hidden)  | timestamp without time zone |
| updated_at     | Timestamp when the record was last updated (Auto Fill, Hidden)  | timestamp without time zone |
| deleted_at     | Timestamp when the record was deleted (Auto Fill, Hidden)       | timestamp without time zone |

# Landuses

This table stores Landuse Polygon geometry data, which is displayed through the Land Use Layer in the map interface.

| **Field Name** | **Description**                                                | **Data Type**               |
| -------------- | -------------------------------------------------------------- | --------------------------- |
| id             | Unique identifier for each Landuse Polygon                     | integer pk                  |
| class          | Class of the landuse polygon shape ( Builtup\| Road \| etc)    | character varying           |
| area           | Area of the polygon shape in square meter (m²)                 | numeric                     |
| geom           | The geometry of the landuse (represented as a polygon)         | geometry(MultiPolygon,4326) |
| created_at     | Timestamp when the record was last created (Auto Fill, Hidden) | timestamp without time zone |
| updated_at     | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone |
| deleted_at     | Timestamp when the record was deleted (Auto Fill, Hidden)      | timestamp without time zone |

# Places

This table stores important Place or point-of-interest geometry data, which is displayed through the Places Layer in the map interface.

| **Field Name** | **Description**                                                | **Data Type**               |
| -------------- | -------------------------------------------------------------- | --------------------------- |
| id             | Unique identifier for each Place                               | integer pk                  |
| name           | Name of the Place                                              | character varying           |
| ward           | Ward Number where the place geospatially lies                  | integer                     |
| geom           | The geometry of the place (represented as a point)             | geometry(Point,4326)        |
| created_at     | Timestamp when the record was last created (Auto Fill, Hidden) | timestamp without time zone |
| updated_at     | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone |
| deleted_at     | Timestamp when the record was deleted (Auto Fill, Hidden)      | timestamp without time zone |

# Sanitation Systems

This table stores Sanitation System Polygons geometry data, which is displayed through the Sanitation System Layer in the map interface.

| **Field Name** | **Description**                                                    | **Data Type**               |
| -------------- | ------------------------------------------------------------------ | --------------------------- |
| id             | Unique identifier for each Sanitation System Polygons              | integer pk                  |
| area           | Area of the sanitation system polygon in square kilometer (km²)    | numeric                     |
| type           | Type of the Sanitation System ( sewered (SS)\| non-sewered (NSS) ) | character varying           |
| geom           | The geometry of the sanitation system (represented as a polygon)   | geometry(MultiPolygon,4326) |
| created_at     | Timestamp when the record was last created (Auto Fill, Hidden)     | timestamp without time zone |
| updated_at     | Timestamp when the record was last updated (Auto Fill, Hidden)     | timestamp without time zone |
| deleted_at     | Timestamp when the record was deleted (Auto Fill, Hidden)          | timestamp without time zone |

# Ward Overlay

This table stores Ward Overlay Polygons geometry data, which is displayed through the Info Tools Ward Boundary Layer option in the map interface.

| **Field Name** | **Description**                                                | **Data Type**               |
| -------------- | -------------------------------------------------------------- | --------------------------- |
| id             | Unique identifier for each Ward Overlay Polygons               | integer pk                  |
| ward           | Ward Number of the ward overlay Polygon                        | numeric                     |
| geom           | The geometry of the ward overlay (represented by multipolygon) | geometry(MultiPolygon,4326) |
| created_at     | Timestamp when the record was last created (Auto Fill, Hidden) | timestamp without time zone |
| updated_at     | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone |
| deleted_at     | Timestamp when the record was deleted (Auto Fill, Hidden)      | timestamp without time zone |

# Waterbodys

This table stores Waterbody Polygons geometry data, which is displayed through the Water Bodies Layer in the map interface.

| **Field Name** | **Description**                                                | **Data Type**               |
| -------------- | -------------------------------------------------------------- | --------------------------- |
| id             | Unique identifier for each Waterbody Polygons                  | integer pk                  |
| name           | Name of the Waterbody                                          | character varying           |
| type           | Type of the Waterbody ( Pond\| River \| etc )                  | character varying           |
| geom           | The geometry of the waterbody (represented as a polygon)       | geometry(MultiPolygon,4326) |
| created_at     | Timestamp when the record was last created (Auto Fill, Hidden) | timestamp without time zone |
| updated_at     | Timestamp when the record was last updated (Auto Fill, Hidden) | timestamp without time zone |
| deleted_at     | Timestamp when the record was deleted (Auto Fill, Hidden)      | timestamp without time zone |

# Public Health ISS

The Public Health Information Support System Module uses the following tables:

Schema Name: **public_health**

Data Tables

-   water_samples: Stores information about Coliform Test performed in water samples.
-   waterborne_hotspots: Stores information about waterborne hotspots.
-   yearly_waterborne_cases: Stores information about yearly waterborne cases. (No of cases on a yearly basis)

## Water Sample Information

Table name: **water_samples**

| Field Name                 | Label                      | Description                                                         | Data Type                                                                                  |
| -------------------------- | -------------------------- | ------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| id                         |                            | Unique identifier                                                   | integer pk                                                                                 |
| sample_date                | Sample Date                | Date on which the water sample was taken                            | Date                                                                                       |
| sample_location            | Sample Location            | Water Sample Location                                               | character varying                                                                          |
| water_coliform_test_result | Water Coliform Test Result | The result received after the Coliform Test                         | character varying (8) with Check Constraint (only accepts values 'positive' or 'negative') |
| geom                       |                            | The geographic coordinates of the facility (represented as a point) | geometry(Point,4326)                                                                       |
| user_id                    |                            | Identifier for the user who created the record (Hidden)             | integer fk:auth.users(id)                                                                  |
| created_at                 |                            | Timestamp when the record was created (Auto Fill, Hidden)           | timestamp without time zone                                                                |
| updated_at                 |                            | Timestamp when the record was last updated (Auto Fill, Hidden)      | timestamp without time zone                                                                |
| deleted_at                 |                            | Timestamp when the record was deleted (Auto Fill, Hidden)           | timestamp without time zone                                                                |

## Waterborne Hotspot

Table name: **waterborne_hotspots**

| Field Name        | Label            | Description                                                                                | Data Type                   |
| ----------------- | ---------------- | ------------------------------------------------------------------------------------------ | --------------------------- |
| id                |                  | Unique identifier for each waterborne hotspot                                              | integer pk                  |
| disease           | Infected Disease | Hotspot disease name (enum used) Cholera, Diarrhea, Dysentery, Hepatitis A, Typhoid, Polio | integer                     |
| hotspot_location  | Hotspot Location | Hotspot location of the disease                                                            | character varying           |
| date              | Date             | Date when the hotspot information was collected                                            | date                        |
| ward              |                  | Ward number where the hotspot information was collected                                    | integer                     |
| no_of_cases       | No of Cases      | Number of cases reported                                                                   | integer                     |
| male_cases        | Male             | Number of male cases reported                                                              | integer                     |
| female_cases      | Female           | Number of female cases reported                                                            | integer                     |
| other_cases       | Other            | Number of other cases reported                                                             | integer                     |
| no_of_fatalities  | No of Fatalities | Number of fatalities reported                                                              | integer                     |
| female_fatalities | Female           | Number of female fatalities reported                                                       | integer                     |
| male_fatalities   | Male             | Number of male fatalities reported                                                         | integer                     |
| other_fatalities  | Other            | Number of other fatalities reported                                                        | integer                     |
| notes             | Notes            | Additional notes or information                                                            | character varying           |
| geom              | Hotspot Area     | Geometric information of the location of the cases (represented as a polygon)              | geometry(MultiPolygon,4326) |
| user_id           |                  | Identifier for the user who created the record (Hidden)                                    | integer fk:auth.users(id)   |
| created_at        |                  | Timestamp when the record was created (Auto Fill, Hidden)                                  | timestamp without time zone |
| updated_at        |                  | Timestamp when the record was last updated (Auto Fill, Hidden)                             | timestamp without time zone |
| deleted_at        |                  | Timestamp when the record was deleted (Auto Fill, Hidden)                                  | timestamp without time zone |

**Enum values for Infected Disease**
const Cholera = 1
const Diarrhea = 2
const Dysentery = 3
const HepatitisA = 4
const Polio = 5
const Typhoid = 6

## Waterborne Cases Information

Table name: **yearly_waterborne_cases**

| Field Name             | Label            | Description                                                                           | Data Type                   |
| ---------------------- | ---------------- | ------------------------------------------------------------------------------------- | --------------------------- |
| id                     |                  | Unique identifier for each yearly waterborne case                                     | integer pk                  |
| infected_disease       | Infected Disease | Hotspot disease name (enum used) Cholera Diarrhea Dysentery Hepatitis A Typhoid Polio | integer                     |
| year                   | Year             | The year when the disease cases were identified                                       | integer                     |
| ward                   |                  | Ward number where the disease cases were identified                                   | integer                     |
| total_no_of_cases      | No of Cases      | Number of cases reported in that year                                                 | integer                     |
| male_cases             | Male             | Number of male cases reported in that year                                            | integer                     |
| female_cases           | Female           | Number of female cases reported in that year                                          | integer                     |
| other_cases            | Other            | Number of other cases reported in that year                                           | integer                     |
| total_no_of_fatalities | No of Fatalities | Number of fatalities reported in that year                                            | integer                     |
| male_fatalities        | Male             | Number of male fatalities reported in that year                                       | integer                     |
| female_fatalities      | Female           | Number of female fatalities reported in that year                                     | integer                     |
| other_fatalities       | Other            | Number of other fatalities reported in that year                                      | integer                     |
| notes                  | Notes            | Additional notes or information                                                       | character varying           |
| user_id                |                  | Identifier for the user who created the record (Hidden)                               | integer fk:auth.users(id)   |
| created_at             |                  | Timestamp when the record was created (Auto Fill, Hidden)                             | timestamp without time zone |
| updated_at             |                  | Timestamp when the record was last updated (Auto Fill, Hidden)                        | timestamp without time zone |
| deleted_at             |                  | Timestamp when the record was deleted (Auto Fill, Hidden)                             | timestamp without time zone |

# Settings

The Settings Module uses the following tables:

## User Information Management

Schema Name: **auth**

Data Tables

-   failed_jobs: Stores information about background job failures.
-   password_resets: Stores data for password reset tokens, facilitating the process of resetting user passwords.
-   personal_access_tokens: Stores personal access tokens used for authentication in API requests.
-   users: Stores information about users, including usernames, passwords, and other relevant details.
-   owner_pii_audit_logs: Stores metadata for owner/customer PII access events without storing decrypted values.
-   pii_key_backup_downloads: Stores PII-key backup download metadata without storing the key or backup file.

Lookup Tables

-   roles: Lookup table, storing different roles that can be assigned to users.
-   permissions: Lookup table, storing different permissions that can be assigned to users or roles.

Relational Tables

-   model_has_permissions: Represents the association between models (e.g., users) and permissions in a many-to-many relationship.
-   model_has_roles: Represents the association between models (e.g., users) and roles in a many-to-many relationship.
-   role_has_permissions: Represents the association between roles and permissions in a many-to-many relationship.

Data Tables

Table name: failed_jobs

| **Field Name** | **Description**                         | **Data Type**               |
| -------------- | --------------------------------------- | --------------------------- |
| id             | Unique identifier                       | Integer pk                  |
| uuid           | label used to uniquely identify objects | character varying           |
| connection     | Connection name                         | text                        |
| queue          | Queue name                              | text                        |
| payload        | Serialized job payload                  | text                        |
| exception      | Serialized exception information        | text                        |
| failed_at      | Timestamp of failure                    | timestamp without time zone |

Table name: **password_resets**

| **Field Name** | **Description**       | **Data Type**               |
| -------------- | --------------------- | --------------------------- |
| email          | User's email address  | character varying           |
| token          | Password reset token  | character varying           |
| created_at     | Timestamp of creation | timestamp without time zone |

Table name: **personal_access_tokens**
Schema name: **public**

| **Field Name** | **Description**                     | **Data Type**               |
| -------------- | ----------------------------------- | --------------------------- |
| id             | Unique identifier                   | Integer pk                  |
| tokenable_type | Polymorphic type of tokenable       | character varying           |
| tokenable_id   | Polymorphic identifier of tokenable | Big Integer                 |
| name           | Name of the personal access token   | character varying           |
| token          | Personal access token               | character varying           |
| abilities      | Serialized token abilities          | text                        |
| last_used_at   | Timestamp of last usage             | timestamp without time zone |
| created_at     | Timestamp of creation               | timestamp without time zone |
| updated_at     | Timestamp of last update            | timestamp without time zone |

### User

Table name: **users**

| **Field Name**      | **Label**        | **Description**                                                                      | **Data Type**                        |
| ------------------- | ---------------- | ------------------------------------------------------------------------------------ | ------------------------------------ |
| id                  |                  | Unique identifier for each user                                                      | Integer pk                           |
| name                | Full Name        | Name of the user                                                                     | Character varying                    |
| gender              | Gender           | Gender of the user                                                                   | Character varying                    |
| username            | Username         | Username of the user                                                                 | Character varying                    |
| email               | Email            | Email address of the user                                                            | Character varying                    |
| password            | Password         | Encrypted Password of the user                                                       | Character varying                    |
| remember_token      |                  | Token used to remember the user (optional)                                           | Character varying                    |
| treatment_plant_id  | Treatment Plant  | Identifier for the user's treatment plant (optional)                                 | Integer fk:fsm.treatment_plants(id)  |
| help_desk_id        | Help Desk        | Identifier for the user's help desk (optional)                                       | Integer fk:fsm.help_desks(id)        |
| service_provider_id | Service Provider | Identifier for the user's service provider (optional)                                | Integer fk:fsm.service_providers(id) |
| user_type           | User Type        | Type of user (Municipality\| Service Provider\| Treatment Plant\| Help Desk\| Guest) | character varying                    |
| status              | Status           | Status of the user ( Active\| Inactive ), if set Inactive, system access is revoked. | Integer (DEFAULT 1)                  |
| created_at          |                  | Timestamp when the record was last updated (Auto Fill, Hidden)                       | Timestamp                            |
| updated_at          |                  | Timestamp when the record was deleted (Auto Fill, Hidden)                            | Timestamp                            |
| deleted_at          |                  | Timestamp when the record was created (Auto Fill, Hidden)                            | Timestamp                            |

### Owner PII Audit Logs

Table name: **owner_pii_audit_logs**

Schema name: **auth**

This append-only audit table records PII access activity. It stores identifiers and request metadata only; it must never store decrypted owner values or encryption-key material.

| **Field Name** | **Description** | **Data Type** |
| --- | --- | --- |
| id | Unique audit-event identifier | bigint pk |
| user_id | User who attempted the PII operation; logical reference to `auth.users.id` | bigint (nullable) |
| building_public_id | Public building identifier associated with the event, when applicable | uuid (nullable) |
| event | PII event name such as reveal, lock, unlock, or export | character varying(64) |
| successful | Whether the attempted action succeeded | boolean |
| authentication_method | Re-authentication method used, when applicable | character varying(32) (nullable) |
| ip_address | Request IP address | character varying(45) (nullable) |
| user_agent | Request user-agent string | text (nullable) |
| context | Non-PII structured event metadata | json (nullable) |
| created_at | Event timestamp | timestamp with time zone (DEFAULT current timestamp) |

Indexes: (`user_id`, `created_at`), (`building_public_id`, `created_at`), and (`event`, `created_at`).

### PII Key Backup Downloads

Table name: **pii_key_backup_downloads**

Schema name: **auth**

This table records when an authorized administrator downloads a key backup. It stores metadata only. The PII encryption key and downloaded backup file are not stored in this table.

| **Field Name** | **Description** | **Data Type** |
| --- | --- | --- |
| id | Unique backup-download audit identifier | bigint pk |
| user_id | Administrator who downloaded the backup; logical reference to `auth.users.id` | bigint |
| key_version | Version of the PII key that was downloaded | character varying(32) |
| downloaded_at | Time the backup was downloaded | timestamp with time zone (DEFAULT current timestamp) |

Index: (`key_version`, `downloaded_at`).

### Roles

Table name: **roles**

| **Field Name** | **Label** | **Description**                                                                | **Data Type**               |
| -------------- | --------- | ------------------------------------------------------------------------------ | --------------------------- |
| id             |           | Unique identifier for each role                                                | integer pk                  |
| name           | Name      | Descriptive name for the role                                                  | character varying           |
| guard_name     |           | Represents the authentication guard ( "web" or "api") associated with the role | character varying           |
| created_at     |           | Timestamp when the record was last updated (Auto Fill, Hidden)                 | timestamp without time zone |
| updated_at     |           | Timestamp when the record was deleted (Auto Fill, Hidden)                      | timestamp without time zone |

**Lookup Table values for roles**

| **ID** | **Name**                                         | **guard_name** |
| ------ | ------------------------------------------------ | -------------- |
| 1      | Super Admin                                      | web            |
| 2      | Municipality - Executive                         | web            |
| 3      | Municipality - Building Permit Department        | web            |
| 4      | Municipality - Building Surveyor (Ward)          | web            |
| 5      | Municipality - Infrastructure Department         | web            |
| 6      | Municipality - Tax Department                    | web            |
| 7      | Municipality - Water Billing Unit                | web            |
| 8      | Municipality - Solid Waste Management Department | web            |
| 9      | Municipality - Sanitation Department             | web            |
| 10     | Municipality - IT Admin                          | web            |
| 11     | Municipality - Public Health Department          | web            |
| 12     | Service Provider - Admin                         | web            |
| 13     | Service Provider - Emptying Operator             | web            |
| 14     | Service Provider - Help Desk                     | web            |
| 15     | Municipality - Help Desk                         | web            |
| 16     | Treatment Plant - Admin                          | web            |
| 17     | Guest                                            | web            |

### Permissions

Table name: **permissions**

| **Field Name** | **Description**                                                           | **Data Type**               |
| -------------- | ------------------------------------------------------------------------- | --------------------------- |
| id             | Unique identifier for each role                                           | integer pk                  |
| name           | Descriptive name for the role                                             | character varying           |
| group          | Group of Permissions based on Modules of IMIS                             | character varying           |
| type           | Type of permission ( Add\| View \| Edit \| Delete \| etc)                 | character varying           |
| guard_name     | Represents the authentication guard associated with the role ( web\| api) | character varying           |
| created_at     | Timestamp when the record was last updated (Auto Fill, Hidden)            | timestamp without time zone |
| updated_at     | Timestamp when the record was deleted (Auto Fill, Hidden)                 | timestamp without time zone |

Relational Tables

Table name: model_has_permissions

| **Field Name** | **Description**                           | **Data Type**                      |
| -------------- | ----------------------------------------- | ---------------------------------- |
| permission_id  | Foreign key referencing permissions table | Integer pk fk:auth.permissions(id) |
| model_type     | Type of model / users                     | character varying pk               |
| model_id       | Foreign key referencing users table       | Integer pk fk:auth.users(id)       |

**Composite Primary Key:** Composite Primary Key: (permission_id, model_type, model_id)

Table name: **model_has_roles**

| **Field Name** | **Description**                     | **Data Type**                |
| -------------- | ----------------------------------- | ---------------------------- |
| role_id        | Foreign key referencing roles table | Integer pk fk:auth.roles(id) |
| model_type     | Type of model / users               | character varying pk         |
| model_id       | Foreign key referencing users table | Integer pk fk:auth.users(id) |

**Composite Primary Key:** (role_id, model_type, model_id)

Table name: **role_has_permissions**

| **Field Name** | **Description**                           | **Data Type**                      |
| -------------- | ----------------------------------------- | ---------------------------------- |
| permission_id  | Foreign key referencing permissions table | Integer pk fk:auth.permissions(id) |
| role_id        | Foreign key referencing roles table       | Integer pk fk:auth.roles(id)       |

**Composite Primary Key:** Composite Primary Key: (permission_id, role_id)

## Language

Schema Name: language

Data Tables

Table name:language

| Field Name | Label             | Description                                                        | Data Type                |
| ---------- | ----------------- | ------------------------------------------------------------------ | ------------------------ |
| Id         |                   | A unique identifier for each language                              | integer                  |
| name       | Name              | The name of the language                                           | character varying(255)   |
| code       | Code              | Unique identifier also used in translates table as  a foreign key | character varying(255)   |
| status     | Status            | Boolean indicating status of language                              | boolean                  |
| created_at |                   | Timestamp when the record was created(Auto Fill , Hidden)          | timestamp with time zone |
| updated_at | Code              | Timestamp when the record was last updated(Auto Fill , Hidden)     | timestamp with time zone |
| deleted_at | character varying | Timestamp when the record was deleted(Auto Fill , Hidden)          | timestamp with time zone |


Data Tables

Table name:translates


| Field Name  | Description                                                    | Data Type                |
| ----------- | -------------------------------------------------------------- | ------------------------ |
| Id          | A unique identifier for each translation                       | integer                  |
| key         | Collective sum of text , pages, and group column               | character varying(255)   |
| name        | Code from language table                                       | character varying(255)   |
| text        | The translated text                                            | text                     |
| pages       | Page where the text will be used                               | character varying(255)   |
| group       |                                                                | character varying(255)   |
| platform_at | Indicates which platform the text will be used (mobile/web)    | character varying(255)   |
| load        |                                                                | boolean                  |
| created_at  | Timestamp when the record was created(Auto Fill , Hidden)      | timestamp with time zone |
| updated_at  | Timestamp when the record was last updated(Auto Fill , Hidden) | timestamp with time zone |

# Miscellaneous Tables

# Migrations

Schema name: **Public**

Table name: **migrations**

The migrations table is used to keep track of which migrations have been applied to your database. This table is automatically created when you run the php artisan migrate command for the first time.

| **Field Name** | **Description**                                                                                                     | **Data Type**     |
| -------------- | ------------------------------------------------------------------------------------------------------------------- | ----------------- |
| id             | A unique identifier for each record in the table                                                                    | integer pk        |
| migration      | The name of the migration file that has been applied                                                                | character varying |
| batch          | The batch number that the migration was run in (Multiple migrations run at once are assigned the same batch number) | integer           |

# Soft Delete

Schema name: **Public**

Table name: **revisions**
This table stores the main revision records.

| **Field Name**    | **Description**                                                       | **Data Type**               |
| ----------------- | --------------------------------------------------------------------- | --------------------------- |
| id                | The unique identifier for each revision                               | bigint pk                   |
| revisionable_type | The type of the model that was revised (often the model’s class name) | character varying           |
| revisionable_id   | The ID of the model that was revised                                  | character varying           |
| user_id           | The ID of the user who made the revision (if applicable)              | integer fk:auth.users(id)   |
| key               | The column of the table that was revised                              | character varying           |
| old_value         | The old values of the model’s attributes before the revision          | text                        |
| new_value         | The new values of the model’s attributes after the revision           | text                        |
| created_at        | The timestamp when the revision was created                           | timestamp without time zone |
| updated_at        | The timestamp when the revision was updated                           | timestamp without time zone |

# Spatial Refrencing Systems

Schema name: **Public**

Table name: **spatial_ref_sys**

This table is used in spatial databases, particularly in PostGIS, an extension for PostgreSQL that adds support for geographic objects. This table stores information about coordinate systems and spatial reference systems.

| **Field Name** | **Description**                                                                                                                | **Data Type**           |
| -------------- | ------------------------------------------------------------------------------------------------------------------------------ | ----------------------- |
| srid           | The Spatial Reference System Identifier (SRID) is a unique identifier for the coordinate system                                | integer pk              |
| auth_name      | The name of the authority that defined the SRID (e.g., EPSG)                                                                   | character varying       |
| auth_srid      | The SRID assigned by the authority                                                                                             | integer                 |
| srtext         | The Well-Known Text (WKT) representation ( human-readable format ) of the spatial reference system                             | character varying(2048) |
| proj4text      | The PROJ.4 representation is used for defining projections and coordinate systems in a format compatible with the PROJ library | character varying(2048) |

# Authentication Log

Schema name: **Public**

Table name: **authentication_log**

This table is used to log information about user authentication activities. It is not default authentication setup but is additional package used to track authentication-related events.

| **Field Name** | **Description**                                                                               | **Data Type**         |
| -------------------- | --------------------------------------------------------------------------------------------------- | --------------------------- |
| id                   | A unique identifier for each log entry                                                              | bigint pk                   |
| authenticatable_type | The type of the model that was involved in the authentication event (often the model’s class name) | character varying           |
| authenticatable_id   | The ID of the specific instance of the model that was involved in the authentication event          | bigint                      |
| ip_address           | The IP address from which the authentication attempt was made                                       | character varying           |
| user_agent           | The user agent string from the client making the authentication request                             | text                        |
| login_at             | A timestamp indicating when the authentication event occurred                                       | timestamp without time zone |
| logout_at            | A timestamp indicating when the log entry was last updated (optional)                               | timestamp without time zone |

# Session Log

Schema name: **Public**

Table name: **sessions**

This table is used to log information about user's session activities. It is the laravels default session tracking mechanism, with the addition of user_id to track each user's session.
In addition to this, the session for the particular user is cleared when their password is updated to ensure all other browsers running with the old password is logged out.

| **Field Name** | **Description**                                                                                        | **Data Type**                  |
| -------------------- | ------------------------------------------------------------------------------------------------------------ | ------------------------------------ |
| id                   | Primary key. A unique identifier for the session. Typically the session ID.                                  | string                               |
| user_id              | Foreign key referencing the id column in the users table. Represents the authenticated user for the session. | unsignedBigInteger fk:auth.users(id) |
| ip_address           | Stores the user's IP address associated with the session. Can store IPv4 or IPv6 addresses.                  | string(45)                           |
| user_agent           | Contains information about the browser or client used to access the application.                             | text                                 |
| payload              | Serialized session data, including all variables stored in the session.                                      | text                                 |
| last_activity        | Unix timestamp indicating the last time the session was active. Used to track session expiration.            | integer                              |
