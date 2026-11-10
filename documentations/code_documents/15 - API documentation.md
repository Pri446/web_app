Version: V1.0.0

# API Documentation

APIs are logically grouped and stored within multiple controllers to ensure a clear separation of concerns and maintainability. Each controller is responsible for managing a specific set of related endpoints, providing a structured approach to API development.

## AuthController

### App Login

| URL          | {Base_URL}/api/login              |          |                   |
| ------------ | --------------------------------- | -------- | ----------------- |
| Method       | POST                              |          |                   |
| Headers      | Header                            | Type     | Required/Optional |
| Content-Type | application/x-www-form-urlencoded | required |                   |
| Accept       | application/json                  |          |                   |

POST Params

| Parameter | Type   | Required/Optional |
| --------- | ------ | ----------------- |
| email     | string | required          |
| password  | string | required          |

Success Response

{

"status": true,

"message": "User Logged In Successfully.",

"token": "",

"data": {

"name": "",

"gender": "",

"treatment_plant": "",

"help_desk": "",

"service_provider": "",

"permissions": {

"building-survey": ,

"save-emptying-service":

}

}

}

Error Response

{

"status": false,

"message": "Email & Password does not match with our records."

}

#### Login Rate Limiting and Retry Behavior (BASEIMIS-44)

The following contract applies to releases containing the login-rate-limiting remediation. The release owner must confirm that `throttle:api-login` and its named limiter are included; this documentation does not itself activate the protection.

`POST /api/login` uses the named `api-login` limiter in addition to the existing general API limiter. Its configurable default limits are:

| Requests counted | Default maximum | Window |
|---|---:|---:|
| Same normalized email and client IP | 5 | 1 minute |
| Same client IP across email addresses | 20 | 1 minute |
| Same normalized email across client IPs | 15 | 15 minutes |

The submitted email is trimmed and converted to lowercase for the limiter key, then SHA-256 hashed. Web login uses a separate counter namespace. These controls count admitted login requests, including requests with correct credentials; they are not failed-password-only counters.

When a login-specific limit is exceeded, the response is **HTTP `429 Too Many Requests`**, with a `Retry-After` header indicating the remaining wait in seconds. Example:

```http
HTTP/1.1 429 Too Many Requests
Content-Type: application/json
Retry-After: 60
```

```json
{
  "status": false,
  "message": "Too many login attempts. Please try again in 60 seconds."
}
```

The message is translated using `auth.throttle`; wording and waiting time may vary. It must not reveal whether the email exists. The general API limiter can also return HTTP 429; clients must handle that status without depending on an exact error-message string or assuming every 429 body has the login-specific structure.

Clients should stop automatic login retries after HTTP 429, show a temporary waiting message, and wait at least the duration in `Retry-After` before allowing another attempt. If the header is absent, avoid a tight retry loop and use a bounded retry delay. A later request can still be blocked by another active limit, so handle each response's waiting time. Restrictions expire automatically and do not permanently disable the account.

See [24 - Authentication and Web Security](24%20-%20Authentication%20and%20Web%20Security.md) for configuration names, trusted-proxy and shared-cache requirements, web-login behavior, and verification steps.

### App Logout

| URL           | {Base_URL}/api/logout             |          |                   |
| ------------- | --------------------------------- | -------- | ----------------- |
| Method        | POST                              |          |                   |
| Headers       | Header                            | Type     | Required/Optional |
| Authorization | Bearer {access_token}             | required |                   |
| Content-Type  | application/x-www-form-urlencoded | required |                   |
| Accept        | application/json                  | required |                   |

POST Params

Success Response

{

"success": true,

"message": "Logged out Successfully."

}

## ApiServiceController

### GET application info from application ID

| URL           | {Base_URL}/api/application/{application_id} |          |                   |
| ------------- | ------------------------------------------- | -------- | ----------------- |
| Method        | GET                                         |          |                   |
| Headers       | Header                                      | Type     | Required/Optional |
| Authorization | Bearer {access_token}                       | required |                   |
| Content-Type  | application/x-www-form-urlencoded           | required |                   |
| Accept        | application/json                            | required |                   |

GET Params

{application_id}

Success Response

{

"success": true,

"data": {

"id": ,

"application_date": "",

"road_code": "",

"containment_id": "",

"approved_status": ,

"emptying_status": ,

"feedback_status": ,

"customer_name": "",

"ward": ,

"address": ,

"customer_contact": ,

"applicant_name": "",

"user_id": ,

"service_provider_id": ,

"customer_gender": "",

"applicant_gender": "",

"created_at": "",

"updated_at": ,

"deleted_at": ,

"proposed_emptying_date": "",

"house_number": "",

"applicant_contact": ,

"sludge_collection_status": ,

"emergency_desludging_status": ,

"buildings": {

"bin": "",

"ward": ,

"road_code": "",

"address": ,

"structure_type_id": ,

"estimated_area": "",

"floor_count": ,

"household_served": ,

"population_served": ,

"functional_use_id": ,

"use_category_id": ,

"office_business_name": ,

"building_associated_to": ,

"water_source_id": ,

"well_presence_status": "",

"toilet_status": "",

"toilet_count": ,

"sewer_code": ,

"drain_code": ,

"surveyed_status": ,

"surveyed_date": ,

"verification_required": ,

"geom": "",

"tax_code": "",

"user_id": ,

"created_at": "",

"updated_at": "",

"deleted_at": ,

"house_number": "",

"desludging_vehicle_accessible": ,

"water_customer_id": ,

"swm_customer_id": ,

"sanitation_system_type_id": ,

"sanitation_system_technology_id": ,

"construction_year": ,

"distance_from_well": ,

"no_of_ihhl_yes": ,

"no_of_ihhl_no": ,

"mapped_functional_use_id": ,

"lic_id": ,

"structure_type": {

"id": ,

"type": "",

"created_at": "",

"updated_at": "",

"deleted_at":

},

"functional_use": {

"id": ,

"name": "",

"created_at": "",

"updated_at": "",

"deleted_at":

},

"sanitation_system_types": {

"id": ,

"type": "",

"created_at": ,

"updated_at": ,

"deleted_at":

},

"owners": {

"id": ,

"bin": "",

"owner_name": "",

"owner_gender": "",

"owner_contact": ,

"created_at": ,

"updated_at": ,

"deleted_at": ,

"tax_code": ""

}

},

"service_provider": {

"id": ,

"company_name": "",

"ward": ,

"company_location": "",

"email": "",

"contact_person": "",

"contact_number": ,

"geom": ,

"created_at": "",

"updated_at": "",

"deleted_at": ,

"total_trips": ,

"contact_gender": "",

"status":

},

"feedback": {

"id": ,

"application_id": ,

"comments": "",

"fsm_service_quality": ,

"customer_name": "",

"customer_number": ,

"customer_gender": "",

"wear_ppe": ,

"user_id": ,

"created_at": ,

"updated_at": ,

"deleted_at": ,

"service_provider_id":

}

},

"message": "Application Details."

}

Error Response

{

"status": false,

"message": "No Application found for ID 9999."

}

### GET containment(s) info from application ID

| URL           | {Base_URL}/api/containment/{application_id} |          |                   |
| ------------- | ------------------------------------------- | -------- | ----------------- |
| Method        | GET                                         |          |                   |
| Headers       | Header                                      | Type     | Required/Optional |
| Authorization | Bearer {access_token}                       | required |                   |
| Content-Type  | application/x-www-form-urlencoded           | required |                   |
| Accept        | application/json                            | required |                   |

GET Params

{application_id}

Success Response

{

"success": true,

"data": [

{

"id": "",

"location": "",

"size": "",

"pit_number": ,

"pit_diameter": ,

"tank_length": ,

"tank_width": ,

"depth": ,

"septic_criteria": ,

"construction_date": "",

"buildings_served": ,

"emptied_status": ,

"last_emptied_date": "",

"next_emptying_date": "",

"no_of_times_emptied": ,

"geom": "",

"surveyed_at": ,

"verification_required": ,

"user_id": ,

"created_at": ,

"updated_at": ,

"deleted_at": ,

"type": "",

"population_served": ,

"household_served": ,

"toilet_count": ,

"distance_closest_well":

}

],

"message": "Containment details for application 1"

}

Error Response

{

"status": false,

"message": "No containment found or application with ID 9999 doesn't exist."

}

### GET service providers

| URL           | {Base_URL}api/service-providers   |          |                   |
| ------------- | --------------------------------- | -------- | ----------------- |
| Method        | GET                               |          |                   |
| Headers       | Header                            | Type     | Required/Optional |
| Authorization | Bearer {access_token}             | required |                   |
| Content-Type  | application/x-www-form-urlencoded | required |                   |
| Accept        | application/json                  | required |                   |

Success Response

{

"success": true,

"data": {

"1": "Cleaning Services PVT.LTD"

},

"message": "Service Providers."

}

Error Response

## BuildingSurveyController

### GET WMS layer of buildings

| URL           | {Base_URL}/api/wms/buildings      |          |                   |
| ------------- | --------------------------------- | -------- | ----------------- |
| Method        | GET                               |          |                   |
| Headers       | Header                            | Type     | Required/Optional |
| Authorization | Bearer {access_token}             | required |                   |
| Content-Type  | application/x-www-form-urlencoded | required |                   |
| Accept        | application/json                  | required |                   |

Success Response

{

"success": true,

"baseUrl": "{base_url:8080}/geoserver/{workspace}/",

"data": {

"buildings": "wms?SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&FORMAT=image/png&TRANSPARENT=true&LAYERS={workspace}:{layer_name}&TILED=true&STYLES={workspace}:{style_name}&FORMAT_OPTIONS=dpi:113&WIDTH={width}&HEIGHT={height}&srs=EPSG:900913&BBOX={minX},{minY},{maxX},{maxY}"

},

"message": "WMS layer for buildings."

}

### GET WMS layer of containments

| URL           | {Base_URL}/api/wms/containments   |          |                   |
| ------------- | --------------------------------- | -------- | ----------------- |
| Method        | GET                               |          |                   |
| Headers       | Header                            | Type     | Required/Optional |
| Authorization | Bearer {access_token}             | required |                   |
| Content-Type  | application/x-www-form-urlencoded | required |                   |
| Accept        | application/json                  | required |                   |

Success Response

{

"success": true,

"baseUrl": "{base_url:8080}/geoserver/{workspace}/",

"data": {

"containments": "wms?SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&FORMAT=image/png&TRANSPARENT=true&LAYERS={workspace}:{layer_name}&TILED=true&STYLES={workspace}:{style_name}&FORMAT_OPTIONS=dpi:113&WIDTH={width}&HEIGHT={height}&srs=EPSG:900913&BBOX={minX},{minY},{maxX},{maxY}"

},

"message": "WMS layer for containments."

}

### GET WMS layer of wards

| URL           | {Base_URL}/api/wms/wards          |          |                   |
| ------------- | --------------------------------- | -------- | ----------------- |
| Method        | GET                               |          |                   |
| Headers       | Header                            | Type     | Required/Optional |
| Authorization | Bearer {access_token}             | required |                   |
| Content-Type  | application/x-www-form-urlencoded | required |                   |
| Accept        | application/json                  | required |                   |

Success Response

{

"success": true,

"baseUrl": "{base_url:8080}/geoserver/{workspace}/",

"data": {

"wards": "wms?SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&FORMAT=image/png&TRANSPARENT=true&LAYERS={workspace}:{layer_name}&TILED=true&STYLES={workspace}:{style_name}&FORMAT_OPTIONS=dpi:113&WIDTH={width}&HEIGHT={height}&srs=EPSG:900913&BBOX={minX},{minY},{maxX},{maxY}"

},

"message": "WMS layer for wards."

}

### GET WMS layer of roads

| **URL**     | {Base_URL}/api/wms/roads          |                |                             |
| ----------------- | --------------------------------- | -------------- | --------------------------- |
| **Method**  | GET                               |                |                             |
| **Headers** | **Header**                  | **Type** | **Required/Optional** |
| Authorization     | Bearer {access_token}             | required       |                             |
| Content-Type      | application/x-www-form-urlencoded | required       |                             |
| Accept            | application/json                  | required       |                             |

**Success Response**

{

"success": true,

"baseUrl": "{base_url:8080}/geoserver/{workspace}/",

"data": {

"roads": "wms?SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&FORMAT=image/png&TRANSPARENT=true&LAYERS={workspace}:{layer_name}&TILED=true&STYLES={workspace}:{style_name}&FORMAT_OPTIONS=dpi:113&WIDTH={width}&HEIGHT={height}&srs=EPSG:900913&BBOX={minX},{minY},{maxX},{maxY}"

},

"message": "WMS layer for roads."

}

### POST Save building survey

| URL           | {Base_URL}/api/save-building      |          |                   |
| ------------- | --------------------------------- | -------- | ----------------- |
| Method        | POST                              |          |                   |
| Headers       | Header                            | Type     | Required/Optional |
| Authorization | Bearer {access_token}             | required |                   |
| Content-Type  | application/x-www-form-urlencoded | required |                   |
| Accept        | application/json                  | required |                   |

POST Params

| Parameter      | Type        | Required? |
| -------------- | ----------- | --------- |
| bin            | varchar     | Yes       |
| tax_code       | varchar     | Yes       |
| collected_date | Date(Y-m-d) | Yes       |
| kml            | .kml File   | Yes       |

Success Response

{

"status": true,

"message": "Building Survey is uploaded successfully."

}

## EmptyingServiceController

### GET assessed applications (for emptying service)

| URL           | {Base_URL}/api/assessed-applications |          |                   |
| ------------- | ------------------------------------ | -------- | ----------------- |
| Method        | GET                                  |          |                   |
| Headers       | Header                               | Type     | Required/Optional |
| Authorization | Bearer {access_token}                | required |                   |
| Content-Type  | application/x-www-form-urlencoded    | required |                   |
| Accept        | application/json                     | required |                   |

Success Response

{

"success": true,

"data": {

"applications": []

},

"message": "Applications"

}

### GET treatment plants (place of disposal)

| URL           | {Base_URL}api/treatment-plants    |          |                   |
| ------------- | --------------------------------- | -------- | ----------------- |
| Method        | GET                               |          |                   |
| Headers       | Header                            | Type     | Required/Optional |
| Authorization | Bearer {access_token}             | required |                   |
| Content-Type  | application/x-www-form-urlencoded | required |                   |
| Accept        | application/json                  | required |                   |

Success Response

{

"success": true,

"data": {

"treatment-plants": [

{

"id": ,

"name": ""

}

]

},

"message": "Treatment Plants"

}

### GET vacutugs

| URL           | {Base_URL}/api/vacutugs           |          |                   |
| ------------- | --------------------------------- | -------- | ----------------- |
| Method        | GET                               |          |                   |
| Headers       | Header                            | Type     | Required/Optional |
| Authorization | Bearer {access_token}             | required |                   |
| Content-Type  | application/x-www-form-urlencoded | required |                   |
| Accept        | application/json                  | required |                   |

Success Response

{

"success": true,

"data": [],

"message": "Vacutugs"

}

### GET drivers

| URL           | {Base_URL}/api/drivers            |          |                   |
| ------------- | --------------------------------- | -------- | ----------------- |
| Method        | GET                               |          |                   |
| Headers       | Header                            | Type     | Required/Optional |
| Authorization | Bearer {access_token}             | required |                   |
| Content-Type  | application/x-www-form-urlencoded | required |                   |
| Accept        | application/json                  | required |                   |

Success Response

{

"success": true,

"data": [],

"message": "Drivers."

}

### GET emptiers

| URL           | {Base_URL}/api/emptiers           |          |                   |
| ------------- | --------------------------------- | -------- | ----------------- |
| Method        | GET                               |          |                   |
| Headers       | Header                            | Type     | Required/Optional |
| Authorization | Bearer {access_token}             | required |                   |
| Content-Type  | application/x-www-form-urlencoded | required |                   |
| Accept        | application/json                  | required |                   |

Success Response

{

"success": true,

"data": [],

"message": "Emptiers."

}

### POST Save emptying service

| URL           | {Base_URL}/api/save-emptying      |          |                   |
| ------------- | --------------------------------- | -------- | ----------------- |
| Method        | POST                              |          |                   |
| Headers       | Header                            | Type     | Required/Optional |
| Authorization | Bearer {access_token}             | required |                   |
| Content-Type  | application/x-www-form-urlencoded | required |                   |
| Accept        | application/json                  | required |                   |

POST Params

| Parameter         | Type             | Required? |
| ----------------- | ---------------- | --------- |
| volume_of_sludge  | Double precision | Yes       |
| vacutug_id        | integer          | Yes       |
| place_of_disposal | integer          | Yes       |
| driver            | varchar          | Yes       |
| emptier1          | varchar          | Yes       |
| emptier1          | varchar          | No        |
| start_time        | Time (HⓂ️s)    | Yes       |
| end_time          | Time (HⓂ️s)    | Yes       |
| no_of_trips       | Double precision | Yes       |
| receipt_number    | varchar          | Yes       |
| total_cost        | numeric          | Yes       |
| application_id    | integer          | Yes       |
| house_image       | Image file       | Yes       |
| receipt_image     | Image file       | Yes       |

Success Response

{

"success": true,

"message": "Emptying service saved successfully."

}
