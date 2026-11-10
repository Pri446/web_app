Version: V1.1.0

# Utility IMS

## Utility Dashboard

The Utility Dashboard uses the shared shell-first dashboard loading pattern documented in `03 - Dashboard.md`.

It uses:

- Chart.js for charts and trends.
- Bootstrap, HTML, and CSS for cards and count boxes.
- SVG assets and Font Awesome for icons.
- `UtilityDashboardController` and `UtilityDashboardService` for server-side data preparation.
- The shared asynchronous dashboard loader for content retrieval and error handling.

### Routes and Controller

| Purpose | Route | Controller method |
|---|---|---|
| Dashboard shell | `GET /utilityinfo/utilitydashboard` | `UtilityDashboardController@index` |
| Dashboard content | `GET /utilityinfo/utilitydashboard/content` | `UtilityDashboardController@content` |

Both routes require authentication.

`app/Http/Controllers/UtilityInfo/UtilityDashboardController.php` separates the fast page shell from the slower data preparation:

- `index()` returns `resources/views/dashboard/utilityDashboardShell.blade.php`.
- `content()` calls `buildDashboardData()`, renders `resources/views/dashboard/utilityDashboard.blade.php`, and returns the HTML inside JSON.
- Unexpected failures are logged and return a generic JSON error.
- The content response sets `Cache-Control: private, no-store`.

The successful content contract is:

```json
{
    "status": "ok",
    "html": "<rendered authorized dashboard content>"
}
```

### Data Retrieval

`buildDashboardData()` retrieves road, sewer, drain, and water-supply counts and chart values. It uses `app/Services/UtilityInfo/UtilityDashboardService.php` and the related Utility service classes. Queries use Eloquent and raw SQL where appropriate.

The Utility dashboard currently uses asynchronous loading but does not use the main dashboard's authorization-scoped HTML cache. Any future Utility cache must define and test every applicable authorization, organization, locale, and filter boundary.

### Views

| Purpose | File |
|---|---|
| Loading shell | `resources/views/dashboard/utilityDashboardShell.blade.php` |
| Dashboard content | `resources/views/dashboard/utilityDashboard.blade.php` |
| Shared loader | `resources/views/dashboard/_asyncDashboardLoader.blade.php` |

Charts are stored in `resources/views/dashboard/charts`. Chart scripts must support initialization after the content HTML is inserted by the loader.

### Error and Session Handling

The shared loader:

- Displays `Loading dashboard data...` during the content request.
- Redirects to login when the authenticated session has expired.
- Shows a retry action when the content request fails.
- Removes the loading state after success or failure.

### Tests

`tests/Feature/UtilityDashboardLoadingTest.php` verifies:

- The Utility shell is returned before the dashboard content.
- The content endpoint returns the expected JSON and HTML contract.
- The shell and content endpoints require authentication.
- Dashboard navigation uses the shared selected-item loading behavior.

## Road Network

### Tables

Road Network is under Utility IMS module and uses the following table:

roads: stores with information related to road infrastructure.

The corresponding tables have their respective models that are named in Pascal Case in singular form. Roadline model is located at app\\Models\\UtilityInfo\\.

### Views

All views used by this module is stored in resources\\views\\utility-info\\road-lines

road-lines.index: lists help desks records.

road-lines.create: opens form and calls partial-form for form contents

road-lines.partial-form: creates form content

road-lines.edit: opens form and calls partial-form for form contents

road-lines.history: lists all past edits of the record

### Models

The models contain the connection between the model and the table defined by \$table = ‘utility_info.roads’ as well as the primary key defined by primaryKey= ‘code’

Roadline Model

Location: app\\Models\\UtilityInfo\\Roadline.php

There are multiple relationships defined in the Roadline Model. They are:

buildings: belongsToMany relationship (1 to n relationship)

### Roadline Request

Location: app\\Http\\Requests\\UtilityInfo\\RoadLineRequest.php

RoadLineRequest handles all validation. It handles validation logic as well as error messages to be displayed.

Roadline follows CRUD operations, which usually have the same pattern. You can refer to the "Basic CRUD" Section **2 - Technical Information/ Basic CRUD** for more information.

However, New roads can be added directly to the map using the **Add Road** tool, and existing roads can be updated using the **Edit Road on Map** tool. The details are provided below:

##### Add Road

-   This tool allows the user to add road on map.

-   Path: views/utility-info/road-lines/index.blade.php

    \< -- CODE START -- \>

    \<a href="{{ action('MapsController@index') }}#add_road_control" class="btn btn-info">Add Road\</a\>

    \<--CODE End -- \>

-   on clicking the button,  navigates to the MapsController@index page with a hash (#add_road_control).

     \< -- CODE START -- \>

     if (window.location.hash === '#add_road_control') {
                    $('#add_edit_control').hide();
                    let code = '';
                        handleAddRoadControlClick(code);
      }\
    \<--CODE End -- \>

-  On the index page of MapsController, it hides the element with the ID add_edit_control.

- Initializes a variable code.

- Calls the function handleAddRoadControlClick(code) to handle the "Add Road" action, passing the code which is further explained in supporting function.


##### Edit Road on Map

-   This tool allows the user to edit road on map.

-   Path: views/utility-info/road-lines/edit.blade.php

    \< -- CODE START -- \>

    \<a href="{{ url('maps#edit_road_control') . '-' . $roadline->code }}" class="btn btn-info">Edit Road on Map\</a\>

    \<--CODE End -- \>

-   on clicking the button, navigates to the MapsController@index page with a hash (#edit_road_control-<code\>).

     \< -- CODE START -- \>

    if (window.location.hash.startsWith(\'#edit_road_control-\')) {

        const code = window.location.hash.split(\'-\')\[1\];

        \$(\'#add_start_control\').hide();

        \$(\'#add_undo_last_point_control\').hide();

        \$(\'#add_delete_control\').hide();

        \$(\'#add_edit_control\').hide();

        handleAddRoadControlClick(code);

        currentAddControl = \'Modify Road\';

        addModifyInteractions(currentAddControl, code, \'roadlines\');

        }

    \<--CODE End -- \>

- On page load, check if the hash is "#edit_road_control-".

- Splits the hash by - and takes the second part which is the ID of the road feature to be edited.

- Hides UI buttons related to adding or drawing new road. Since, these controls are not needed when editing an existing road.

- Calls a function to set up the map for editing a road passes the road ID code to it so the map knows which feature to focus on.

- Keeps track of the current tool/mode.

- Calls the function addModifyInteractions(currentAddControl, code, 'roadlines') to let the user modify the geometry of the selected road which is explained below in supporting function.


## Sewer Network

### Tables

Sewer Network is under Utility IMS module and uses the following table:

sewers: manages data associated with sewer systems.

The corresponding tables have their respective models that are named in Pascal Case in singular form. SewerLine model is located at app\\Models\\UtilityInfo\\.

### Views

All views used by this module is stored in resources\\views\\utility-info\\sewer-lines

sewer-lines.index: lists help desks records.

sewer-lines.create: opens form and calls partial-form for form contents

sewer-lines.partial-form: creates form content

sewer-lines.edit: opens form and calls partial-form for form contents

sewer-lines.history: lists all past edits of the record

### Models

The models contain the connection between the model and the table defined by \$table = ‘utility_info.sewers’ as well as the primary key defined by primaryKey= ‘code’

SewerLine Model

Location: app\\Models\\UtilityInfo\\SewerLine.php

There are multiple relationships defined in the SewerLine Model. They are:

buildings: belongsToMany relationship (1 to n relationship)

### SewerLine Request

Location: app\\Http\\Requests\\UtilityInfo\\SewerLineRequest.php

SewerLineRequest handles all validation. It handles validation logic as well as error messages to be displayed.

SewerLine follows CRUD operations, which usually have the same pattern. You can refer to the "Basic CRUD" section **2 - Technical Information/ Basic CRUD** for more information.

However, new sewer can be added to the map itself using **Add Sewer** Tool. The details are included below: 

##### Add Sewer

-   This tool allows the user to add sewer on map.

-   Path: views/utility-info/sewer-lines/index.blade.php

    \< -- CODE START -- \>

    \<a href="{{ action('MapsController@index') }}#add_sewer_control" class="btn btn-info">Add Sewer\</a\>

    \<--CODE End -- \>

-   on clicking the button,  navigates to the MapsController@index page with a hash (#add_sewer_control).

     \< -- CODE START -- \>

     if (window.location.hash === '#add_sewer_control') {
            $('#add_edit_control').hide();
                    let code = '';
                        handleAddSewerControlClick(code);
                }\

    \<--CODE End -- \>

-  On the index page of MapsController, it hides the element with the ID add_edit_control.

- Initializes a variable code.

- Calls the function handleAddSewerControlClick(code) to handle the "Add Sewer" action, passing the code which is further explained in supporting function.

##### Edit Sewer on Map

-   This tool allows the user to edit sewer on map.

-   Path: views/utility-info/sewer-lines/edit.blade.php

    \< -- CODE START -- \>

    \<a href="{{ url('maps#edit_sewer_control') . '-' . $sewerline->code }}" class="btn btn-info">Edit Sewer on Map\</a\>

    \<--CODE End -- \>

-   on clicking the button, navigates to the MapsController@index page with a hash (#edit_sewer_control-<code\>).

     \< -- CODE START -- \>

    if (window.location.hash.startsWith(\'#edit_sewer_control-\')) {

        const code = window.location.hash.split(\'-\')\[1\];

        \$(\'#add_start_control\').hide();

        \$(\'#add_undo_last_point_control\').hide();

        \$(\'#add_delete_control\').hide();

        \$(\'#add_edit_control\').hide();

        handleAddSewerControlClick(code);

        currentAddControl = \'Modify Sewer\';

        addModifyInteractions(currentAddControl, code, \'sewerlines\');

        }

    \<--CODE End -- \>

- On page load, check if the hash is "#edit_sewer_control-".

- Splits the hash by - and takes the second part which is the ID of the sewer feature to be edited.

- Hides UI buttons related to adding or drawing new sewer. Since, these controls are not needed when editing an existing sewer.

- Calls a function to set up the map for editing a sewer passes the sewer ID code to it so the map knows which feature to focus on.

- Keeps track of the current tool/mode.

- Calls the function addModifyInteractions(currentAddControl, code, 'sewerlines') to let the user modify the geometry of the selected sewer which is explained below in supporting function.

## Water Supply Network

### Tables

Water Supply Network is under Utility IMS module and uses the following table:

water_supplys: contains details about water supply networks

The corresponding tables have their respective models that are named in Pascal Case in singular form. WaterSupplys model is located at app\\Models\\UtilityInfo\\.

### Views

All views used by this module is stored in resources\\views\\utility-info\\water-supplys.

water-supplys.index: lists help desks records.

water-supplys.create: opens form and calls partial-form for form contents

water-supplys.partial-form: creates form content

water-supplys.edit: opens form and calls partial-form for form contents

water-supplys.history: lists all past edits of the record

### Models

The models contain the connection between the model and the table defined by \$table = ‘utility_info.water_supplys’ as well as the primary key defined by primaryKey= ‘code’

WaterSupplys Model

Location: \\app\\Models\\UtilityInfo\\WaterSupplys.php

There are multiple relationships defined in the WaterSupplys Model. They are:

buildings: belongsToMany relationship (1 to n relationship)

### WaterSupplys Request

Location: app\\Http\\Requests\\UtilityInfo\\WaterSupplysRequest.php

WaterSupplysRequest handles all validation. It handles validation logic as well as error messages to be displayed.

WaterSupplys follows CRUD operations, which usually have the same pattern. You can refer to the "Basic CRUD" Section **2 - Technical Information/ Basic CRUD** for more information.

However, new water supply can be added to the map itself using **Add Water Supply** Tool. The details are included below: 

##### Add Water Supply

-   This tool allows the user to add water supply on map.

-   Path: views/utility-info/water-supplys/index.blade.php

    \< -- CODE START -- \>

    \<a href="{{ action('MapsController@index') }}#add_watersupply_control" class="btn btn-info">Add Water Supply\</a\>

    \<--CODE End -- \>

-   on clicking the button,  navigates to the MapsController@index page with a hash (#add_watersupply_control).

     \< -- CODE START -- \>

     if (window.location.hash === '#add_watersupply_control') {      
           $('#add_edit_control').hide();
                    let code = '';
                        handleAddWatersupplyControlClick(code);
                }\

    \<--CODE End -- \>

-  On the index page of MapsController, it hides the element with the ID add_edit_control.

- Initializes a variable code.

- Calls the function handleAddWatersupplyControlClick(code) to handle the "Add Water Supply" action, passing the code which is further explained in supporting function.


##### Edit Water Supply on Map

-   This tool allows the user to edit watersupplys on map.

-   Path: views/utility-info/water-supplys/edit.blade.php

    \< -- CODE START -- \>

    \<a href="{{ url('maps#edit_watersupplys_control') . '-' . $watersupplys->code }}" class="btn btn-info">Edit Water Supply on Map\</a\>

    \<--CODE End -- \>

-   on clicking the button, navigates to the MapsController@index page with a hash (#edit_watersupplys_control-<code\>).

     \< -- CODE START -- \>

    if (window.location.hash.startsWith(\'#edit_watersupplys_control-\')) {

        const code = window.location.hash.split(\'-\')\[1\];

        \$(\'#add_start_control\').hide();

        \$(\'#add_undo_last_point_control\').hide();

        \$(\'#add_delete_control\').hide();

        \$(\'#add_edit_control\').hide();

        handleAddWatersupplyControlClick(code);

        currentAddControl = \'Modify watersupplys\';

        addModifyInteractions(currentAddControl, code, \'watersupplys\');

        }

    \<--CODE End -- \>

- On page load, check if the hash is "#edit_watersupplys_control-".

- Splits the hash by - and takes the second part which is the ID of the watersupplys feature to be edited.

- Hides UI buttons related to adding or drawing new watersupplys. Since, these controls are not needed when editing an existing watersupplys.

- Calls a function to set up the map for editing a watersupplys passes the watersupplys ID code to it so the map knows which feature to focus on.

- Keeps track of the current tool/mode.

- Calls the function addModifyInteractions(currentAddControl, code, 'watersupplys') to let the user modify the geometry of the selected watersupplys which is explained below in supporting function.


## Drain Network

### Tables

Sewer Network is under Utility IMS module and uses the following table:

drains: stores information regarding drainage systems

The corresponding tables have their respective models that are named in Pascal Case in singular form. SewerLine model is located at app\\Models\\UtilityInfo\\.

### Views

All views used by this module is stored in resources\\views\\utility-info\\drains

drains.index: lists help desks records.

drains.create: opens form and calls partial-form for form contents

drains.partial-form: creates form content

drains.edit: opens form and calls partial-form for form contents

drains.history: lists all past edits of the record

### Models

The models contain the connection between the model and the table defined by \$table = ‘utility_info.drains’ as well as the primary key defined by primaryKey= ‘code’

Drain Model

Location: app\\Models\\UtilityInfo\\Drain.php

There are multiple relationships defined in the Drain Model. They are:

buildings: belongsToMany relationship (1 to n relationship)

### Drain Request

Location: app\\Http\\Requests\\UtilityInfo\\DrainRequest.php

DrainRequest handles all validation. It handles validation logic as well as error messages to be displayed.

Drain follows CRUD operations, which usually have the same pattern. You can refer to the "Basic CRUD" section **2 - Technical Information/ Basic CRUD** for more information.

However, new drain can be added to the map itself using **Add Drain** Tool. The details are included below: 

##### Add Drain

-   This tool allows the user to add drain on map.

-   Path: views/utility-info/drains/index.blade.php

    \< -- CODE START -- \>

    \<a href="{{ action('MapsController@index') }}#add_drain_control" class="btn btn-info">Add Drain\</a\>

    \<--CODE End -- \>

-   on clicking the button,  navigates to the MapsController@index page with a hash (#add_drain_control).

     \< -- CODE START -- \>

     if (window.location.hash === '#add_drain_control') {
           $('#add_edit_control').hide();
                    let code = '';
                        handleAddDrainControlClick(code);

                }\

    \<--CODE End -- \>

-  On the index page of MapsController, it hides the element with the ID add_edit_control.

- Initializes a variable code.

- Calls the function handleAddDrainControlClick(code) to handle the "Add Drain" action, passing the code which is further explained in supporting function.


##### Edit Drain on Map

-   This tool allows the user to edit drain on map.

-   Path: views/utility-info/drains/edit.blade.php

    \< -- CODE START -- \>

    \<a href="{{ url('maps#edit_drain_control') . '-' . $drain->code }}" class="btn btn-info">Edit Water Supply on Map\</a\>

    \<--CODE End -- \>

-   on clicking the button, navigates to the MapsController@index page with a hash (#edit_drain_control-<code\>).

     \< -- CODE START -- \>

    if (window.location.hash.startsWith(\'#edit_drain_control-\')) {

        const code = window.location.hash.split(\'-\')\[1\];

        \$(\'#add_start_control\').hide();

        \$(\'#add_undo_last_point_control\').hide();

        \$(\'#add_delete_control\').hide();

        \$(\'#add_edit_control\').hide();

        handleAddWatersupplyControlClick(code);

        currentAddControl = \'Modify drain\';

        addModifyInteractions(currentAddControl, code, \'drain\');

        }

    \<--CODE End -- \>

- On page load, check if the hash is "#edit_drain_control-".

- Splits the hash by - and takes the second part which is the ID of the drain feature to be edited.

- Hides UI buttons related to adding or drawing new drain. Since, these controls are not needed when editing an existing drain.

- Calls a function to set up the map for editing a drain passes the drain ID code to it so the map knows which feature to focus on.

- Keeps track of the current tool/mode.

- Calls the function addModifyInteractions(currentAddControl, code, 'drains') to let the user modify the geometry of the selected drain which is explained below in supporting function.


### Supporting Tool

 **Add**

-   This tool allows the user to initialize a draw interaction of type MultiLineString. Add draw,snap & undo interactions.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="add_start_control" class="btn btn-default map-control" data-toggle="tooltip"data-placement="bottom" title="Add"\>\<i class="fa fa-circle-plus fa-fw"\>\</i\>\</a\>

    \<--CODE End -- \>

-   Here, id value (add_start_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#add_start_control').click(function (e) {

    e.preventDefault();

    hideAddForm();

    if (currentAddControl !== 'Add ' + currentLayerType){

    currentAddControl ='Add ' + currentLayerType;

    addDrawInteractions();

    }

    });

    \<--CODE End -- \>

-   Initial steps are explained below in Initialize having id value (add_start_control).

-   Selects an element with the id add_start_control using jQuery. It then attaches a click event handler to it. When this element is clicked, the function specified inside the click() method will be executed.

-   Calls a function named ‘**hideAddForm()**’ which is explained in supporting functions.

-   Checks whether the variable currentAddControl is not equal to current layer type passed. If this condition is true, it means that the current control for adding a the passed currecnt layer type is not already set to current layer passed.

-   Updates the value of the variable currentAddControl to current layer type passed. This variable likely keeps track of the current state or mode of the current layer type adding functionality.

-   Calls a function named ‘**addDrawInteractions()**’ explained in supporting function.


**Undo last point**

-   This tool allows the user to undo the last drawn point.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="add_undo_last_point_control" class="btn btn-default map-control ml-1" data-toggle="tooltip" data-placement="bottom" title="Undo last point"\>\<i class="fa fa-clock-rotate-left fa-fw"\>\</i\>\</a\>

    \<--CODE End -- \>

-   Here, id value (add_undo_last_point_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#add_undo_last_point_control').click(function (e) {

    e.preventDefault();

    hideAddForm();

    layerDrawInteraction?.removeLastPoint();

    });

    \<--CODE End -- \>

-   Initial steps are explained below in Initialize having id value (“add_undo_last_point_control'”).

-   Calls a function named ‘**hideAddForm()**’ which is explained in supporting functions.

-   It ensures that if layerDrawInteraction is null or undefined, the code will not throw an error and removeLastPoint() will not be called. If layerDrawInteraction is defined and has a removeLastPoint() method, it will be executed that will remove the last drawn point.

    **Undo**

-   This tool allows the user to undo the entire drawn line.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="add_undo_control" class="btn btn-default map-control ml-1" data-toggle="tooltip" data-placement="bottom" title="Undo"\>\<i class="fa fa-rotate-left fa-fw"\>\</i\>\</a\>\<--CODE End -- \>

-   Here, id value (add_undo_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#add_undo_control').click(function (e) {

    e.preventDefault();

    hideAddForm();

    undoInteraction?.undo();

    });

    \<--CODE End -- \>

-   Initial steps are explained below in Initialize having id value (add_undo_control'”).

-   Calls a function named ‘**hideAddForm()**’ which is explained in supporting functions.

-   It ensures that if undoInteraction is null or undefined, the code will not throw an error and undo() will not be called. If undoInteractionis defined and has a rundo() method, it will be executed that will undo all drawn point.

    **Redo**

-   This tool allows the user to redo the drawing that was undone.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="add_redo_control" class="btn btn-default map-control ml-1" data-toggle="tooltip" data-placement="bottom" title="Redo"\>\<i class="fa fa-rotate-right fa-fw"\>\</i\>\</a\>

    \<--CODE End -- \>

-   Here, id value (add_redo_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#add_redo_control').click(function (e) {

    e.preventDefault();

    hideAddRoadForm();

    undoInteraction?.redo();

    });

    \<--CODE End -- \>

-   Initial steps are explained below in Initialize having id value (add_redo_control'”).

-   Calls a function named ‘**hideAddForm()**’ which is explained in supporting functions.

-   It ensures that if undoInteraction is null or undefined, the code will not throw an error and redo() will not be called. If undoInteractionis defined and has a redo() method, it will be executed that will redo the drawn point.


    **Delete**

-   This tool allows the user to remove the drawn lines

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="add_delete_control" class="btn btn-default map-control ml-1" data-toggle="tooltip" data-placement="bottom" title="Remove all drawn lines"\>\<i class="fa fa-trash fa-fw"\>\</i\>\</a\>\<--CODE End -- \>

-   Here, id value (add_road_edit_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#add_delete_control').click(function (e) {

    e.preventDefault();

    hideAddRoadForm();

     if (hasModification && modifiedFeature && originalRoadFeature) {

     const source = vectorLayer.getSource();


     source.removeFeature(modifiedFeature);


     source.addFeature(originalRoadFeature.clone());

     selectInteraction.getFeatures().clear();

     originalRoadFeature = null;

     modifiedFeature = null;

     hasModification = false;

     } else {

     removeDrawnFeatures(); 

     }

     });

    \<--CODE End -- \>

-   Initial steps are explained below in Initialize having id value (add_delete_control).

-   This code binds a click event handler to the HTML element with the id add_delete_control. When this element is clicked, the function inside the click() method will be executed.

-   Calls a function named ‘**hideAddForm()**’ and ‘**removeDrawnRoads()**’ which is explained in supporting functions.

- Check if a feature has been modified (hasModification === true) and both modifiedFeature and originalRoadFeature exist:

     - Get the vector source (vectorLayer.getSource()).

     - Remove the modified feature from the map.

     - Re-add the original feature (restores the previous state).

     - Clear selection such as (selectInteraction.getFeatures().clear()).

     - Reset all modification-related flags and variables.


- If no modified feature is present, it simply removes any freshly drawn features using removeDrawnFeatures().

    **Submit**

-   There are two types of submission the user can perform. One is when an added layer submit and another is when layer updated is submit.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="add_submit_control" class="btn btn-default map-control ml-1" data-toggle="tooltip"
    data-placement="bottom" title="Save"\>\<i class="fa fa-floppy-disk fa-fw"\>\</i\>\</a\>\<--CODE End -- \>

-   Here, id value (add_submit_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#add_submit_control').click(function (e) {

    e.preventDefault();

    let code =  \$('\#add_submit_control').data('code');

     const layerTypeLower = currentLayerType.toLowerCase();

    if(currentAddControl === 'Add ' + currentLayerType){

    …………………………………..

    ………………………………… } else {

    hideAddRoadForm();

    Swal.fire({

    title: 'Nothing to save!',

    icon: "warning",

    });

    }

    });

    \<--CODE End -- \>

-   Initial steps are explained below in Initialize having id value (“add_submit_control”).

- Prevent default behavior using e.preventDefault().

- Retrieve the code from the data-code attribute.

- Convert current layer type (Road, Sewer, etc.) to lowercase for dynamic use.

-  If the current control is "Add Road" or "Add Sewer", etc.:

     - Check if features are drawn on the map (drawLayer.getSource().getFeatures()).

     - If no features, show warning using SweetAlert.

     - If features exist, toggle the corresponding add form using jQuery
     -  After clicking the save on the form, it prevents the default form submission and instead calls a custom function "**submitDetails()**" with the passed arguments. this function is explained in supporing function.

-   If a modification was made:
     
     - Hide any open form.

     - If a feature has been modified (hasModification is true and modifiedFeature exists): Ask for confirmation using SweetAlert then on confirm:

          - Clone and transform the geometry from EPSG:3857 to EPSG:4326.

          - Calculate the total length: for LineString: use getLength() and for MultiLineString: sum up each line segment's length

- Convert geometry to WKT (Well-Known Text).

- Prepare formData and updateUrl dynamically based on layer type (Road, Drain, etc.).

- Make an AJAX POST request to the corresponding URL with form data.

- On success: show success message and redirect and on error: show error message.
Show a SweetAlert confirmation dialog.

If there are no add mode nor modifications, it displays a warning message using Swal indicating that there is nothing to save.


### Supporting Functions

**handleAddRoadControlClick()**

 - The handleAddRoadControlClick(code) function sets up and triggers the "Add Road" or "Edit Road" on map control behavior.

- Sets a global variable to indicate the current layer is a road.

- Adds an attribute named "Road" (from currentLayerType) to various UI elements.

- Also sets a data-code attribute on #add_submit_control.

- Initializes the map control using the ‘**handleMapControl**’  function, configuring it for road drawing which is explained below in supporting functions.

- Auto-triggering the road control to begin adding roads having id value (add_road_control).

   
**handleAddSewerControlClick()**

- The handleAddSewerControlClick(code) function sets up and triggers the "Add Sewer" or "Edit Sewer" on map control behavior.

-  Sets a global variable to indicate the current layer is a sewer.

- Adds an attribute named "Sewer" (from currentLayerType) to various UI elements.

- Also sets a data-code attribute on #add_submit_control.

- Initializes the map control using the ‘**handleMapControl**’  function, configuring it for sewer drawing which is explained below in supporting functions.

- Auto-triggering the sewer control to begin adding sewer having id value (add_sewer_control).


**handleAddWatersupplyControlClick()**

- The handleAddWatersupplyControlClick(code) function sets up and triggers the "Add Water Supply" or "Edit Water Supply" on map control behavior.

-  Sets a global variable to indicate the current layer is a watersupply.

- Adds an attribute named "Watersupply" (from currentLayerType) to various UI elements.

- Also sets a data-code attribute on #add_submit_control.

- Initializes the map control using the ‘**handleMapControl**’  function, configuring it for watersupply drawing which is explained below in supporting functions.

- Auto-triggering the watersupply control to begin adding watersupply having id value (add_watersupply_control).


**handleAddDrainControlClick()**

- The handleAddDrainControlClick(code) function sets up and triggers the "Add Drain"  or "Edit Drain" on map control behavior.

- Sets a global variable to indicate the current layer is a drain.

- Adds an attribute named "Drain" (from currentLayerType) to various UI elements.

- Also sets a data-code attribute on #add_submit_control.

- Initializes the map control using the ‘**handleMapControl**’  function, configuring it for drain drawing which is explained below in supporting functions.

- Auto-triggering the drain control to begin adding drain having id value (add_drain_control).

**handleMapControl()**

- The function takes parameters as "controlId" , "layerName", "toolBoxId", "geoServerLayer".
    - controlId - The ID of the map control (e.g., '#add_road_control', '#add_sewer_control').
    - layerName - The name to assign to the feature display layer.
    - toolBoxId - The ID of the toolbox UI element to show/hide. ToolBox contain the button to add, edit, undo, redo and delete layer which is explained in Supporting tool.
    - geoServerLayer - The name of the layer in GeoServer to fetch via WFS.

- Calls a function called "disableAllControls explained in supporting function.

- Retrieves an array of all layers currently on the map.

- Removes a visual "active" state from all control buttons.

- Checks if the current control is already active, disable it and exit using function as "**resetAddTool()**", "**disableAllControls()**"explained in supporting function.

- If active, it activate the new control and show toolbox.

- Load existing features from GeoServer WFS into a vector layer and display existing features from GeoServer.

- Creates a drawLayer and drawSource for user-drawn geometries.

- Adds the vectorLayer and drawLayer if it’s not already on the map.

- Sets a listener to detect when features are fully loaded and then removes the loader.
 

**addModifyInteractions()**

- The function takes parameters as "currentAddControl" , "code", "utilityType".

    - currentAddControl - Keeps track of the current tool/mode.
    - code - The ID of current layer passed in url.
    - utilityType - The name of the layer.

- Removes existing interactions from the map to prevent conflicts.

- Clears old features from the source so only the one being edited appears.
- Sends a GET request through AJAX.
- The backend returns the WKT (Well-Known Text) geometry for the specific utility item.
- Converts the WKT string to an OpenLayers feature.

- Ensures correct projection and creates a collection.

- Applies custom styling for selection.

- Enables modifying geometry, and undo/redo functionality.

- Activates all the relevant tools on the map (modification, snapping, undo/redo).

- When a user finishes editing the geometry, this stores the modified feature and flags it as changed.

- Prevents the user from selecting or editing any other feature.

- Keeps only the intended feature selected.

- Uses a warning popup (SweetAlert2) if the user selects something else.

- Also prevents deselecting the feature by clicking outside.

- Resets any previous undo/redo state so it starts fresh for the selected feature.

**disableAllControls()**

-   disables all controls on an OpenLayers map. Overall, this function is used to reset the map to its original state and remove any previous actions performed on it.

-   The function first removes any interactions (draw, drag) and event listeners (pointermove, singleclick) that have been added to the map.

-   It then removes any overlay tooltips and hides a "layer-select-box" element.

-   Finally, it clears any features that may have been added to the map layers .

**resetAddTool()**

- Resets the current control used for adding features by setting currentAddControl to an empty string ('').

- Removes all interactions (like drawing or modifying) from the map that are related to adding new features (removeAllAddInteractions()).

- Clears temporary layers that were used while adding features (clearAddlayers()).

**hideAddForm()**

-   This line uses jQuery to select all elements with the class add-road-form.

-   The slideUp() function is then called on these selected elements that hides the selected elements by sliding them up.

**removeDrawnFeatures()**

-   When this function is called, it removes all drawn roads or features from the specified draw layer on the map. This is achieved by clearing the data source associated with the layer.

**addDrawInteractions()**

-   Remove any existing modify and select interactions from the map. These interactions might be present to modify or select existing features on the map.

-   Creates a new draw interaction for drawing layers on the map. It specifies the source where the drawn features will be added (drawSource) and sets the type of geometry that can be drawn, in this case, "MultiLineString" which means multiple lines can be drawn.

-   Creates a snap interaction to snap the vertices of the drawn layers to existing features on the map. It specifies the source from which to snap vertices (vectorSource).

-   Creates another snap interaction, but this time it's for snapping the vertices of the newly drawn layers to each other. It specifies the source from which to snap vertices (drawSource).

-   creates an Undo/Redo interaction, which allows users to undo and redo their drawing actions.

-   Add the draw, snap, and undo interactions to the map.

-   Attaches an event listener to the draw interaction's "drawstart" event. This function is triggered when the user starts drawing a new layer. Within this event listener, it hides the form used for adding layers ‘**hideAddForm**()’ and removes any previously drawn layers ‘**removeDrawnFeatures**()’. These two function: ‘**hideAddForm**()’ and ‘**removeDrawnFeatures**()’ are further explained in supporting functions.

**submitDetails()**

- This function handles form submission for different utility layers (e.g., road, sewer, drain, watersupply) by dynamically building form data, validating geometry, and sending an AJAX request to the appropriate endpoint.

- controlType – It determines which form and fields to process. It accepts:'road','sewer','drain','watersupply'.

- Based on controlType specific setup following are determined:
     - geom = Retrieves the geometry drawn on the map. This function "**getGeometryLayer()**" is explained separately in the supporting function section.

     - fieldNameMapping – Maps the form field names to user-friendly labels for error messages.
     
     - formData – Collects the values from the HTML input fields dynamically based on the selected control type. 

     - url – Defines the POST API endpoint where the form data and geometry should be submitted.

     - return_url – Specifies the URL to redirect to after a successful form submission.

**getGeometryLayer()**

- Returns the WKT (Well-Known Text) representation of the last drawn geometry from the OpenLayers drawing source, transformed into the correct coordinate system (EPSG:4326 for lat/lng).
