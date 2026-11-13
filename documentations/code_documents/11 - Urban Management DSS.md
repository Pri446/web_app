Version: V1.0.0

# Urban Management DSS

## Export Data

This section describes the functionality for exporting data as shapefiles or KML files. Users can select specific layers, wards, and the desired format (shapefile or KML) for download.

Normally, GeoServer operation only allows a filter to be applied on each layer in isolation, based on its attribute and external information (geometry, values) provided by the user. The querylayer, a geoserver extension, is used that provides three new filter functions namely: querySingle, queryCollection, and collectGeometries, which allows Cross layer filtering. These filter functions can be used directly in CQL filters, OGC filters and SLD, meaning they are available both from WMS and WFS.

In IMIS, the querylayer filter functions are used in Map Export Tools. The export tools requires Cross layer filtering, which is the the ability to select features from one layer that bear some relationship with features coming from another layer.

### Backend

The current shared export endpoint is handled by `GISExportController` located at `app\\Http\\Controllers\\GISExportController.php`. The authenticated route is `GET /gis/export/{format}`, where `format` can be `csv`, `kml`, or `shp`. Shape exports are returned as a ZIP archive.

The controller receives the configured GIS layer and an optional CQL filter, verifies that the layer is enabled for export in `config/gis.php`, checks the user's layer permission, and requests the export from GeoServer through WFS. Download filenames are also configured per layer in `config/gis.php`.

The shared GIS infrastructure also includes:

-   `GISProxyController`: Provides authenticated `/gis/wms` and `/gis/wfs` routes for approved GeoServer map and feature requests. It validates services, operations, workspace, layers, and permissions.
-   `GISPrintController`: Provides `POST /gis/print`, validates the print specification and layer permissions, sends the request to MapFish, and returns a PDF map.
-   `GET /api/gis/wms`: Provides the Sanctum-protected mobile WMS route.

The former `ExportShpKmlController` and `/export-shp-kml` page describe the legacy layer/ward selection workflow. They should be retained in this section only as legacy documentation if that page remains available; new module export buttons use the shared `GISExportController` route.

### Views

**Export Page:** The view for the export functionality resides at resources\\views\\export-shp-kml\\index.blade.php.

-   Form Elements: The view primarily consists of dropdown menus:

-   Layers: Users have the option to choose the data layers they want to export, with support for multi-select functionality.These layers are manually specified in the view file.

-   Wards: Users have the option to select specific wards for filtering the exported data. The "wards" parameter is passed from the ExportShpKmlController to the index function, where data is fetched from the ward model.

-   Format: Users can choose between exporting as a shapefile or KML file in the legacy export page. The shared map export controls additionally support CSV.

-   Multi-Select: The ward selection dropdown utilizes a multi-select functionality.

-   Download Button: A button initiates the export process when clicked.

### JavaScript Functionality Breakdown

-   Toggle Visibility: A JavaScript function (likely triggered by clicking the \#checkboxforwards checkbox) controls the visibility of certain elements. It also clears the selections in the ward multi-select dropdown (\#ward) when the checkbox is unchecked.

-   Form Submission: Another JavaScript function handles form submission (\#export_form). Here's a breakdown of its actions:

-   Gathers selected values from the dropdowns (layers, wards, format).

-   Performs validation using the checkValidation function (described below).

-   If validation passes, constructs an export link based on the selected options. This likely involves using PHP variables for configuration on the backend.

-   Opens the generated link in a new browser window, triggeriz
-   Validation: The checkValidation function ensures essential variables (selectedWards, selectedLayers, and selectedFormat) have valid values. It displays a warning message using SweetAlert if any are missing or empty. This function returns false if validation fails, preventing the export process, and true otherwise.

### Overall Flow

-   User selects desired layers, wards and format(shape/kml).

-   Clicking the download button triggers the JavaScript form submission function.

-   If validation passes, an export link is constructed based on selections.

The link is opened in a new window, initiating the download of the shapefile or KML file.

## Map Feature

-   Views stored in \\resources\\views\\maps\\index

-   Maps has layouts maps stored in layouts (source: resources\\views\\layouts\\maps.blade.php)

-   Map is incorporated in web page with open layers (version v4.6.5) as client and styled with geoserver(version 2.20).

-   Open layer source files are stored in:

    -   public\\js\\ol.js

    -   public\\css\\ol.css

-   The styling for each map component becomes visible only after reaching a certain scale.

### Map Display

The points mentioned below for displaying map adheres to the comment in the code. Here, GEOSERVER_WORKSPACE, GEOSERVER_URL, and AUTH_KEYare further elaborated in the .env file, as described in Section **2 - Technical Information/ Basic CRUD**.

Geoserver workspace name from constants

-   URL of GeoServer

-   URL of WMS

-   URL of WFS

-   Authentication Keys

-   URL of GeoServer Legends.

-   Declare Openlayers map variable as

\< -- CODE START --\>

// Coordinates of the city

var coord = [centerX, centerY]; // [latitude, longitude]
//Modifying these values will affect functions like zoomToCity(), setInitialZoom(), and tools like Hard to Reach Building,Buildings Close to Water Bodies.

// OpenLayers Map

var map = new ol.Map({

controls: ol.control.defaults().extend([new ol.control.ScaleLine()]),

interactions: ol.interaction.defaults({

altShiftDragRotate: false,

dragPan: false,

rotate: false,

doubleClickZoom: false

}).extend([new ol.interaction.DragPan({kinetic: null}), dragAndDropInteraction]),

target: 'olmap',

view: new ol.View({

minZoom: 12,

maxZoom: 19,

extent: ol.proj.transformExtent([xmin, ymin, xmax, ymax], 'EPSG:4326', 'EPSG:3857')

})

});

\< -- CODE END --\>

GetMap example request

gurl+//wms?SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&FORMAT=image/png&TRANSPARENT=true&LAYERS={workspace}:{layer_name}&TILED=true&STYLES={workspace}:{style_name}&FORMAT_OPTIONS=dpi:113&WIDTH={width}&HEIGHT={height}&srs=EPSG:900913&BBOX={minX},{minY},{maxX},{maxY}

GetLegendGraphic example request

gurl+//wms?REQUEST=GetLegendGraphic&VERSION=1.0.0&FORMAT=image/png&WIDTH={width}&HEIGHT={height}&BBOX={minX},{minY},{maxX},{maxY}&LAYER={workspace}:{layer_name}&LEGEND_OPTIONS=countMatched:TRUE;fontName:Lucida%20Bright;fontAntiAliasing:true;&STYLE={workspace}:{style_name}

Initially certain layers are checked and loaded in map.

Municipality, Buildings, Containments, Places and Ward wise info

### Layers Tab

1.  **Base Layer**

BaseLayer consist of many layers such as stamen Toner, OpenstreetMap, Bing and so on. It sets the foundation for the rest of the map and provides context for the other layers that are added on top of it.

\< -- CODE START --\>

\<div\>\<select id="base_layer_select"\>

\<option value=""\>None\</option\>\</select\>\</div\>

\< -- CODE END --\>

-   The select box is identified by the id "base_layer_select".

\< -- CODE START --\>

\$('\#base_layer_select').append(html);

\< -- CODE END --\>

-   The code appends the HTML to the select box and sets up a change handler function for the select box.

\< -- CODE START --\>

\$('\#base_layer_select').change(function () {

…. .

…. …}

\< -- CODE END --\>

-   The change function checks the value of the selected option in the select box and performs different actions based on the selected option:

    -   If the selected option is not "None" or exists in the "bLayer" object, then the code performs different actions based on the type of the selected layer:

    -   If the selected layer is of type "google", it hides all base layers, sets the type of the Google Map to the selected layer, sets the center and zoom of the Google Map to those of the OpenLayers Map, adds handlers to the OpenLayers view center change and zoom change events, and makes the Google Map visible.

    -   If the selected layer is not of type "google", it makes the Google Map invisible, shows the selected base layer and hides other base layers, removes handlers from the OpenLayers view center change and zoom change events, and removes the handler from the window resize event.

    -   If the selected option is "None" or does not exist in the "bLayer" object, it makes the Google Map invisible, hides all base layers, removes handlers from the OpenLayers view center change and zoom change events, and removes the handler from the window resize event.

2.  **Overlays**

\< -- CODE START -- \>

// Looping through Overlays Object and creating layer object

\$.each(mLayer, function (key, value) {

// creating layer object

var layer = new ol.layer.Image({

visible: false,

source: new ol.source.ImageWMS({

url: gurl_wms,

params: {

'LAYERS': workspace + ':' + key,

'TILED': true,

'CQL_FILTER': null

},

serverType: 'geoserver',

crossOrigin: 'anonymous'

})});

// Setting name to layer

layer.set('name', key);

// Assigning layer to layer property of the current Overlay

mLayer[key].layer = layer;

// Adding layer to OpenLayers Map

map.addLayer(layer);

\<-- CODE END -- \>

-   This code is adding image layers to an OpenLayers map.

-   The \$each function iterates through the mLayer object, where key represents the layer name and value is the object for that layer.

-   For each iteration, a new layer object is created using the ol.layer.Image constructor. The source of the layer is set using ol.source.ImageWMS with the specified URL, parameters (layer name, tiled option and CQL filter), server type and cross-origin property.

-   The name of the layer is set using the set method. The layer object is then assigned to the layer property of the current iteration's mLayer object.

-   Finally, the layer is added to the OpenLayers map using the map.addLayer method.

-   Here, layers such as buildings and containments are style will be applied only when the scale denominator is less than 2000 and places style will be applied only when the scale denominator is less than 10,000.

**Checkbox**

To visualize styles on the map, users are required to click on the checkbox, triggering the rendering process. When an input checkbox is changed (either checked or unchecked), the following actions will be taken:

-   The name attribute of the changed checkbox will be retrieved and stored in the "key" variable. If the checkbox is checked:

    -   The visibility of the layer associated with the "key" in the "mLayer" object will be set to "true". If the "key" exists in the "conditionalLayers" array, the visibility will only be set to "true" if the map's current zoom level is greater than 14 for layers such as building Structures, Containments and Point of Interest.

    -   The element with an id of " ' + key + '\_overlay_style_select_container" will be shown.

    -   If there are styles associated with the layer in the "mLayer" object, the change event on the select element with the same name as the "key" will be triggered.

    -   If there are no styles, the legend image HTML for the layer will be set and set to the element with an id of "' + key + '\_overlay_legend_container".

-   If the checkbox is unchecked:

-   The visibility of the layer associated with the "key" in the "mLayer" object will be set to "false".

-   The element with an id of "' + key + '\_overlay_style_select_container" will be hidden.

-   The HTML of the element with an id of "' + key + '\_overlay_legend_container" will be set to an empty string.

-   The options for the "feature_info_overlay" select dropdown will be updated with the visible layers in the "mLayer" object. The value of "feature_info_overlay" will be set to an empty string.

**Dropdown**

-   The code is setting up two arrays: alllayers and allstyles.

-   The alllayers array is being populated by looping through an object mLayer, checking each layer's visibility, and adding the key (layer name) to the array if the layer is visible.

-   The allstyles array is being populated by looping through the same mLayer object, checking the visibility of each layer, and adding the selected style for each visible layer to the array.

-   The style for each layer is determined differently based on whether or not the layer name is found in the multistylelayers array.

    -   If the layer name is in the multistylelayers array, then the selected style is retrieved from a dropdown control with a specific id (e.g. containments_layer_overlay_style_select_container).

    -   If the layer name is not in the multistylelayers array, then the style is set to be the same as the key (layer name).

**Municipality**

-   The ‘layer_info.citypolys’ table is utilized for retrieving municipality data present in the ‘citypolys \_layer’ layer.

-   The styling for this visualization is sourced from the 'citypolys_layer' available within Geoserver.

**Ward Boundary**

-   The ‘layer_info. wardboundary’ table is utilized for retrieving ward boundary data present in the ‘wardboundary_layer’ layer.

-   The styling for this visualization is sourced from the ‘wardboundary_layer’ available within Geoserver.

**Containments**

-   A SQL query is executed to obtain containments data using table such as ‘fsm.containments’ present in the ‘containments_layer’ layer.

-   There is no styling visualized initially as none is selected.

-   However, the containments can be visualized based on the following options selected from the dropdown menu:

None

-   The styling for this visualization is sourced from the ‘containments_layer_none’ available within Geoserver.

Type

-   The styling for this visualization is sourced from the ‘containments_layer_type’ available within Geoserver.

    Emptied Status

-   The styling for this visualization is sourced from the ‘containments_layer_emptied_status’ available within Geoserver.

Times Emptied

-   The styling for this visualization is sourced from the ‘containments_layer_no_of_times_emptied’ available within Geoserver.

Year of Construction

-   The styling for this visualization is sourced from the ‘containments_period_from_construction’ available within Geoserver.

Last Emptied Year

-   The styling for this visualization is sourced from the ‘containments_last_emptied_year’ available within Geoserver.

Location

-   The styling for this visualization is sourced from the ‘containments_layer_location’ available within Geoserver.

**Building**

-   A SQL query is executed to obtain building data using tables such as: ’building_info.buildings’, ‘fsm.containments’ , ‘building_info.build_contains’ and ‘building_info.owners’ present in the ‘buildings_layer’ layer.

-   However, the buildings can be visualized based on the following options selected from the dropdown menu:

None

-   The styling for this visualization is sourced from the ‘buildings_layer_none’ available within Geoserver.

Structure Type

-   The styling for this visualization is sourced from the ‘buildings_layer_structure_type’ available within Geoserver.

Floor Count

-   The styling for this visualization is sourced from the ‘buildings_layer_flrcount’ available within Geoserver.

Building Use

-   The styling for this visualization is sourced from the ‘buildings_layer_functional_use’ available within Geoserver.

Associated Building

-   The styling for this visualization is sourced from the ‘buildings_layer_building_associated_to’ available within Geoserver.

Toilet

-   The styling for this visualization is sourced from the ‘buildings_layer_toilet’ available within Geoserver.

Toilet Connection

-   The styling for this visualization is sourced from the ‘buildings_layer_toilet_connection’ available within Geoserver.

Water Source

-   The styling for this visualization is sourced from the ‘buildings_layer_water_source’ available within Geoserver.

Well Presence

-   The styling for this visualization is sourced from the ‘buildings_layer_well_presence’ available within Geoserver.

Low Income Houses

-   The styling for this visualization is sourced from the ‘low_income_houses’ available within Geoserver.

Year of Construction

-   The styling for this visualization is sourced from the ‘building_construction_year’ available within Geoserver.

SWM Service

-   The styling for this visualization is sourced from the ‘solid_waste_management_service’ available within Geoserver.

**Property Tax Collection ISS**

-   A SQL query is executed to obtain tax payment status data using tables such as: ’building_info.buildings’ and ‘taxpayment_info.tax_payment_status’ present in the ‘buildings_tax_status_layer’ layer.

-   The styling for this visualization is sourced from the ‘buildings_tax_status_layer’ available within Geoserver.

**Water Supply ISS**

-   A SQL query is executed to obtain water supply payment status data using tables such as: ’building_info.buildings’ and ‘watersupply_payment_status’ present in the ‘buildings_water_payment_status_layer’ layer.

-   The styling for this visualization is sourced from the ‘buildings_water_payment_status_layer’ available within Geoserver.

**Treatment Plants**

-   SQL query is executed to obtain treatment plants data using tables such as: ‘fsm.treatment_plants’ present in the ‘treatmentplants_layer’ layer.

-   The treatment plants data can be visualized based on the following options selected from the dropdown menu:

None

-   The styling for this visualization is sourced from the ‘treatmentplants_layer_none’ available within Geoserver.

Status

-   The styling for this visualization is sourced from the ‘treatmentplants_layer_status’ available within Geoserver.

Type

-   The styling for this visualization is sourced from the ‘treatmentplants_layer_type’ available within Geoserver.

**Road Network**

-   The 'utility_info.roads' table is utilized for retrieving road data present in the 'roadlines_layer' layer.

-   Within this context, layers grouped from the 'roads_width_zoom_layer' within the Layer Group of GeoServer are categorized into 'roadlines_layer_width' and 'roadlines_layer_zoom', 'roadlines_layer_surface_type' and 'roadlines_layer_zoom', as well as 'roadlines_layer_hierarchy' and 'roadlines_layer_zoom'.
    None

-   The styling for this visualization is sourced from the ‘roadlines_layer_none’ available within Geoserver.

Carrying Width

-   The styling for this visualization is sourced from the ‘roadlines_layer_width’ available within Geoserver.

Surface Type

-   The styling for this visualization is sourced from the ‘roadlines_layer_surface_type’ available within Geoserver.

Hierarchy

-   The styling for this visualization is sourced from the ‘roadlines_layer_hierarchy’ available within Geoserver.

**Sewer Network**

-   The 'utility_info.sewers’ table is utilized for retrieving sewers data present in the 'sewerlines_layer' layer.

-   The sewer network can be visualized based on the following options selected from the dropdown menu:
    None

-   The styling for this visualization is sourced from the ‘sewer_none’ available within Geoserver.

Sewer Width(cm)

-   The styling for this visualization is sourced from the ‘sewerlines_layer_size’ available within Geoserver.

Sewer Length (m)

-   The styling for this visualization is sourced from the ‘sewerlines_layer_length’ available within Geoserver.

**Water Supply Network**

-   The ‘utility_info.water_supplys’ table table is utilized for retrieving water supply network data present in the watersupply_network_layer' layer.

-   The styling for this visualization is sourced from the ‘watersupply_network_layer' available within Geoserver.
    None

-   The styling for this visualization is sourced from the ‘watersupply_none’ available within Geoserver.

**Drain Network**

-   The ‘utility_info.drains’ table is utilized for retrieving drain network data present in the ‘drains_layer' layer.

-   The styling for this visualization is sourced from the ‘drains_layer' available within Geoserver.
    None

-   The styling for this visualization is sourced from the ‘drain_none’ available within Geoserver.

**Places**

-   The ‘utility_info.places’ table is utilized for retrieving places data present in the ‘places_layer' layer.

-   The styling for this visualization is sourced from the ‘places_layer' available within Geoserver.

**Water Bodies**

-   The ‘layer_info.waterbodys’ table is utilized for retrieving waterbodies data present in the ‘waterbodys_layer' layer.

-   The styling for this visualization is sourced from the ‘waterbodys_layer' available within Geoserver.

**Water Samples**

-   The ‘public_health.water_samples’ table is utilized for retrieving water samples data present in the ‘water_samples_layer' layer.

-   The styling for this visualization is sourced from the ‘water_samples_layer' available within Geoserver.

**Solid Waste ISS**

-   A SQL query is executed to obtain solid waste payment status data using tables such as: ’building_info.buildings’ and ‘swm_info.swmservice_payment_status’ present in the ‘buildings_swm_payment_status_layer’ layer.

-   The styling for this visualization is sourced from the ‘buildings_swm_payment_status_layer’ available within Geoserver.

**Land Use**

-   The ‘layer_info.landuses’ table is utilized for retrieving land use data present in the ‘landuses_layer' layer.

-   The styling for this visualization is sourced from the ‘landuses_layer' available within Geoserver.

**Ward wise info**

-   The ‘layer_info.wards’ table is utilized for retrieving ward wise data present in the ‘wards_layer' layer.

-   The ward wise info can be visualized based on the following options selected from the dropdown menu:

None

-   The styling for this visualization is sourced from the ‘wards_layer_none’ available within Geoserver.

No. of Buildings

-   The styling for this visualization is sourced from the ‘wards_layer_no_build’ available within Geoserver.

No. of RCC framed

-   The styling for this visualization is sourced from the ‘wards_layer_no_rcc_framed’ available within Geoserver.

No. of Wooden/Mud

-   The styling for this visualization is sourced from the ‘wards_layer_no_wooden_mud’ available within Geoserver.

No. of Load bearing

-   The styling for this visualization is sourced from the ‘wards_layer_no_load_bearing’ available within Geoserver.

No. of CGI Sheet

-   The styling for this visualization is sourced from the ‘wards_layer_no_cgi_sheet’ available within Geoserver.

No. of Building Connected to Sewerage Network

-   The styling for this visualization is sourced from the ‘wards_layer_no_build_directly_to_sewerage_network’ available within Geoserver.

No. of Containments

-   The styling for this visualization is sourced from the ‘wards_layer_no_contain’ available within Geoserver.

No. of Pit/Holding Tank

-   The styling for this visualization is sourced from the ‘wards_layer_no_pit_holding_tank’ available within Geoserver.

No. of Septic Tank

-   The styling for this visualization is sourced from the ‘wards_layer_no_septic_tank’ available within Geoserver.

Total Length of Roads(km)

-   The styling for this visualization is sourced from the ‘wards_layer_total_rdlen’ available within Geoserver.

Tax Paid %

-   The styling for this visualization is sourced from the ‘wards_layer_bldgtaxpdprprtn’ available within Geoserver.

Watersupply payment paid %

-   The styling for this visualization is sourced from the ‘wards_layer_wtrpmntprprtn’ available within Geoserver.

Population Served

-   The styling for this visualization is sourced from the ‘wards_layer_population_served’ available within Geoserver.

Household Served

-   The styling for this visualization is sourced from the ‘wards_layer_household_served’ available within Geoserver.

No. of Emptying Requests

-   The styling for this visualization is sourced from the ‘wards_layer_no_emptying’ available within Geoserver.

**Summerized Grids(0.5km)**

-   The ‘layer_info.grids’ table is utilized for retrieving summarized grids data present in the ‘grids_layer' layer.

-   The summarized grid data can be visualized based on the following options selected from the dropdown menu:

No. of Buildings

-   The styling for this visualization is sourced from the ‘grids_layer_no_build’ available within Geoserver.

No. of Containments

-   The styling for this visualization is sourced from the ‘grids_layer_no_contain’ available within Geoserver.

No. of RCC framed

-   The styling for this visualization is sourced from the ‘grids_layer_no_rcc_framed’ available within Geoserver.

No. of Wooden/Mud

-   The styling for this visualization is sourced from the ‘grids_layer_no_wooden_mud’ available within Geoserver.

No. of Load bearing

-   The styling for this visualization is sourced from the ‘grids_layer_no_load_bearing’ available within Geoserver.

No. of CGI Sheet

-   The styling for this visualization is sourced from the ‘grids_layer_no_cgi_sheet’ available within Geoserver.

No. of Building Connected Sewerage Network

-   The styling for this visualization is sourced from the ‘grids_layer_no_build_directly_to_sewerage_network’ available within Geoserver.

No. of Pit/Holding Tank

-   The styling for this visualization is sourced from the ‘grids_layer_no_pit_holding_tank’ available within Geoserver.

No. of Septic Tank

-   The styling for this visualization is sourced from the ‘grids_layer_no_septic_tank’ available within Geoserver.

Total Length of Roads(km)

-   The styling for this visualization is sourced from the ‘grids_layer_total_rdlen’ available within Geoserver.

Tax Paid %

-   The styling for this visualization is sourced from the ‘grids_layer_bldgtaxpdprprtn’ available within Geoserver.

Watersupply payment paid %

-   The styling for this visualization is sourced from the ‘grids_layer_wtrpmntprprtn’ available within Geoserver.

Population Served

-   The styling for this visualization is sourced from the ‘grids_layer_population_served’ available within Geoserver.

Household Served

-   The styling for this visualization is sourced from the ‘grids_layer_household_served’ available within Geoserver.

No. of Emptying Requests

-   The styling for this visualization is sourced from the ‘grids_layer_no_emptying’ available within Geoserver.

**Sanitation System**

-   The ‘layer_info.sanitation_system’ table is utilized for retrieving sanitation system data present in the ‘sanitation_system_layer' layer.

-   The styling for this visualization is sourced from the ‘sanitation_system_layer' available within Geoserver.

**Toilets PT/CT**

-   The ‘fsm.toilets’ table is utilized for retrieving toilets data present in the ‘toilets_layer' layer.

-   The toilets data can be visualized based on the following options selected from the dropdown menu:

None

-   The styling for this visualization is sourced from the ‘toilets_layer_none’ available within Geoserver.

Toilet Type

-   The styling for this visualization is sourced from the ‘toilets_layer_type’ available within Geoserver.

**Waterborne** **Hotspots**

-   The ‘public_health.waterborne_hotspots_layer’ table is utilized for retrieving waterborne hotspots data present in the ‘waterborne_hotspots_layer ' layer.

-   The waterborne hotspots data can be visualized based on the following options selected from the dropdown menu:

None

-   The styling for this visualization is sourced from the ‘waterborne_hotspots_layer_none’ available within Geoserver.

Infected Disease

-   The styling for this visualization is sourced from the ‘infected_disease’ available within Geoserver.

**Low Income Community**

-   The ‘layer_info.low_income_communities’ table is utilized for retrieving low income community data present in the ‘low_income_communities_layer' layer.

-   The styling for this visualization is sourced from the ‘low_income_communities_layer’ available within Geoserver.

### Tools Tab

#### Service Delivery Tools

##### Application

-   This tool aids in searching for applications that have been submitted according to certain filters that the user can select from. There are different year, month and date wise filters that the user can combine to get to the point data as well.

-   Path: views/maps/index.blade.php

\< -- CODE START --\>

\<a id="applicationcontainments_control" class="btn btn-default collapse-control collapsed" role="button" data-toggle="collapse" href="\#collapse_find_appications" aria-expanded="false" aria-controls="collapse_find_appications"\>

\<i class="fa fa-file-text"\>\</i\>Applications\</a\>

\< -- CODE END -- \>

-   Here, id value (**applicationcontainments_control**) trigger the jQuery as

    \< -- CODE START --\>

    \$('\#applicationcontainments_control').click(function(e) {

    ……

    … } \< -- CODE END -- \>

-   It checks if the element with the ID "applicationcontainments_control" has a class that includes "collapsed". If it does, it:

    -   Initial steps are explained below in **Initialize** having id value (**“applicationcontainmnets_control”).**

    -   Clears the value of a variable called "currentControl".

    -   Calls a function called "displayApplicationContainments" which is explained below.

    -   It also contains form that filters the application based on year, month and date.

    -   To filter based on year and month, it triggers the jQuery of id value (**find_application_yearmonth**) as

\< -- CODE START --\>

\$('\# find_application_yearmonth’).click(function(e) {

……

… } \< -- CODE END -- \>

-   It retrieves the values of two input fields with IDs applicaion_year and application_month.

    -   It checks if the applicaion_year field is empty. If it is, it displays a warning message using the Swal (SweetAlert) library.

        -   It then checks if both applicaion_year and application_month fields are empty. If they are, it displays another warning message.

        -   If both fields have values, it constructs a message indicating the number of applications for the selected year and month.

        -   It calls a function displaySelectedYearMonthApplications() passing the year, month, and the constructed message which is explained below.

        -   Finally, it returns false to prevent the form from submitting in case of warnings or successful submission.

    -   To filter based on date, it triggers the jQuery of id value (**application_date_form**) as

        \< -- CODE START --\>

        \$('\# application_date_form’).click(function(e) {

        ……

        … } \< -- CODE END -- \>

        -   It retrieves the value of an input field with the ID application_date_field, presumably containing a selected date.

        -   It checks if the date field is empty. If it is, it displays a warning message using the Swal (SweetAlert) library.

        -   If the date field has a value, it constructs a message indicating the number of applications for the selected date.

        -   It then calls a function displaySelectedDateApplications() passing the selected date and the constructed message which is explained below.

        -   Finally, it returns false to prevent the form from submitting in case of warnings or successful submission.

-   If the element with the ID "applicationcontainments_control" does not have a class that includes "collapsed", it:

    -   Prevents the default action for the event (e.preventDefault()).

    -   Calls a function called "disableAllControls explained in supporting function

    -   It checks if the eLayer object has a property called "application_containment_markers", if it does, it calls the clear method of the source of that layer.

displayApplicationContainments()

-   It displays applications.

-   It checks if the eLayer object has a property called "application_containment_markers". If it does, it calls the clear method of the source of that layer.

-   If the eLayer object does not have a property called "application_containment_markers", it creates a new layer. Then it adds this layer to the eLayer object as a property called "application_containment_markers" and gives it a title of "Building Markers".

-   It makes an AJAX GET request to a URL specified in the "url" variable, which appears to be a route on the server that returns data for "application containments". It calls the ‘**getApplicationContainments’** function of ‘**MapsController**’. The request includes a CSRF token.

-   If the request is successful, the function iterates through the data of "application containments". For each item in the list, it checks if the item has "lat" and "long" properties. If it does, it creates a new feature and sets its geometry to a point using the "lat" and "long" properties, and sets its properties like "application_id", "service_provider", "application_date".

-   It assigns an icon to the feature based on the status of some properties like "sludge_collection_status", "feedback_status", "emptying_status".

-   It adds the feature to the source of the "application_containment_markers" layer.It removes ajax modal and add layers by calling "showLayer".

-   Then the code calls **zoomToCity()** further explained below in supporting function.

-   If the request is not successful, it shows error message.

displaySelectedYearMonthApplications()

-   It displays applications of filtered data based on year or month or both.

-   It checks if the eLayer object has a property called "application_containment_markers". If it does, it calls the clear method of the source of that layer.

-   If the eLayer object does not have a property called "application_containment_markers", it creates a new layer. Then it adds this layer to the eLayer object as a property called "application_containment_markers" and gives it a title of "Building Markers".

-   It makes an AJAX GET request to a URL specified in the "url" variable, which appears to be a route on the server that returns data for "application containments". It calls the ‘**getApplicationContainmentsYearMonth’** function of ‘**MapsController**’. The request includes a CSRF token.

-   Upon successful retrieval of data, it iterates over the response data and creates OpenLayers features for each data point.

-   Each feature represents a marker on the map with properties such as coordinates, application ID, house number, application date, service provider, and possibly emptying date.It assigns an icon to the feature based on the status of some properties like "sludge_collection_status", "feedback_status", "emptying_status".

-   It adds the feature to the source of the "application_containment_markers" layer.It removes ajax modal and add layers by calling "showLayer".

-   Then the code calls **zoomToCity()** further explained below in supporting function.

-   If the request is not successful, it shows error message.

displaySelectedDateApplications()

-   It displays applications of filtered data based on date.

-   It checks if the eLayer object has a property called "application_containment_markers". If it does, it calls the clear method of the source of that layer.

-   If the eLayer object does not have a property called "application_containment_markers", it creates a new layer. Then it adds this layer to the eLayer object as a property called "application_containment_markers" and gives it a title of "Building Markers".

-   It makes an AJAX GET request to a URL specified in the "url" variable, which appears to be a route on the server that returns data for "application containments". It calls the ‘**getApplicationOnDate’** function of ‘**MapsController**’. The request includes a CSRF token.

-   Upon successful retrieval of data, it iterates over the response data and creates OpenLayers features for each data point.

-   Each feature represents a marker on the map with properties such as coordinates, application ID, house number, application date, service provider, and possibly emptying date.It assigns an icon to the feature based on the status of some properties like "sludge_collection_status", "feedback_status", "emptying_status".

-   It adds the feature to the source of the "application_containment_markers" layer.It removes ajax modal and add layers by calling "showLayer".

-   Then the code calls **zoomToCity()** further explained below in supporting function.

-   If the request is not successful, it shows error message.

##### Emptied Applications that have not reached to Treatment Plant:

-   This tool aids in searching for applications that have been emptied but have not reached the treatment plant according to certain filters that the user can select from.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a id="applications_not_tp" class="btn btn-default collapse-control collapsed" role="button" data-toggle="collapse" href="\#collapse_applications_not_tp" aria-expanded="false"aria-controls="collapse_find_tax_due_buildings"\>\<i class="fa-solid fa-calendar-xmark"\>\</i\>Emptied Applications that have not reached to Treatment Plant\</a\>

    \< -- CODE END -- \>

-   Here, id value (**applications_not_tp**) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#applications_not_tp’).click(function(e) {

    ……

    … }

    \< -- CODE END -- \>

-   When the button is clicked, it checks if the button has the class "collapsed". If the button has the class "collapsed", the function calls the "displayApplicationNotTP()" function explained below.

-   It also contains form that filters the application based on year, month and date.

-   To filter based on year and month, it triggers the jQuery of id value (find_application_not_tp_yearmonth) as

    \< -- CODE START --\>

    \$('\#find_application_not_tp_yearmonth’).click(function(e) {

……… } \< -- CODE END -- \>

-   The code starts with a jQuery event listener attached to the form with the ID find_application_not_tp_yearmonth. This function listens for form submission.

-   When the form is submitted, it prevents the default form submission (return false;), and retrieves the values of the selected year and month from form input fields with IDs applicaion_not_tp_year and application_not_tp_month, respectively.

-   It then checks if a year is selected. If not, it displays a warning using SweetAlert (Swal) plugin and stops further execution by returning false.

-   If both year and month are not selected, another warning is displayed.

-   If both year and month are selected, it constructs a message string indicating the selected year and month for application processing.

-   It calls a function displaySelectedYearMonthApplicationsNotTP(year, month, message) passing the year, month, and the constructed message which is explained below.

-   To filter based on date, it triggers the jQuery of id value (application_not_tp_date_form) as

    \< -- CODE START --\>

    \$('\# application_not_tp_date_form’).click(function(e) {

    ……

    … } \< -- CODE END -- \>

    -   The code begins with a jQuery event listener attached to the form with the ID application_not_tp_date_form. This function listens for form submission.

        -   When the form is submitted, it prevents the default form submission (return false;).

        -   It retrieves the value of the selected date from the input field with the ID application_not_tp_date_field.

        -   If no date is selected, it displays a warning using the Swal (SweetAlert) plugin.

        -   If a date is selected, it constructs a message string indicating the selected date for application processing.

        -   It then calls a function displaySelectedDateApplicationsNotTP(date, message) passing the selected date and the constructed message which is explained below.

-   If the button does not have the class "collapsed" then it clears all the source of the different layers eLayer.application_notTP_markers, eLayer.application_NotTPDate_containment_markers, eLayer.application_NotTP_containment_markers and also clear the date, month and year fields.

displayApplicationNotTp()

-   displays applications that are not reached to treatment plants

-   This function is used to display markers on a map for a specific type of application, labeled as "notTP".

-   It first clears any existing markers in the layers "application_notTP_markers", "application_NotTPDate_containment_markers", and "application_NotTP_containment_markers". Then, it creates a new vector layer called "application_notTP_markers" and adds it as an extra layer to the map.

-   The function then makes an AJAX call to a specific URL ( '{{ url("maps/application-not-tp") }}' ) to retrieve data for the markers. It calls ‘**getApplicationNotTP()**’ of **MapsController**.

-   It passes a CSRF token as a data parameter in the AJAX call.

-   Once the data is returned, the function populates a table with the service providers' names and corresponding marker colors.

-   Then, it loops through the data, creates new features for each data point with information such as the application's id, date, service provider, and emptying date, assigns a specific marker image for each feature based on the service provider, and adds the features to the source of the "application_notTP_markers" layer.

-   Finally, the function removes the loading spinner and shows the "containments_layer".

displaySelectedYearMonthApplicationsNotTP(year, month, message)

-   Inside this function, it clears existing layers if they exist.

-   It creates a new vector layer layer using OpenLayers library.

-   Then it adds this layer as an extra layer using the function addExtraLayer('application_NotTP_containment_markers', 'Building Markers', layer).

-   It constructs a URL for AJAX request based on the Laravel route maps/application-not-tp-containments-year-month. It calls ‘**getApplicationNotTPContainmentsYearMonth()**’ of **MapsController**.

-   Then it sends an AJAX POST request to the server with selected year, month, and CSRF token.

-   Upon successful response, it processes the data received:

    -   It constructs legends for service providers based on marker colors.

    -   It iterates through the data received and creates OpenLayers features for each data point.

    -   It assigns a custom marker icon for each feature based on the service provider.

    -   It adds each feature to the vector source of the layer.

-   After processing the data, it removes the AJAX loader, shows the layer containments_layer, and zooms to the city extent on the map.

-   In case of an error during the AJAX request, it displays an AJAX error message.

displaySelectedDateApplicationsNotTP(date, message)

-   It clears existing layers related to application markers if they exist. This ensures that previously displayed markers are removed before adding new ones.

-   It creates a new vector layer using OpenLayers library, with an empty source initially.

-   It adds this newly created layer as an extra layer using the addExtraLayer() function. The layer is named 'application_NotTPDate_containment_markers', indicating its purpose.

-   It constructs a URL for AJAX request based on the Laravel route maps/application-not-tp-on-date, appending the selected startDate. It calls ‘**getApplicationNotTPOnDate ()**’ of **MapsController**.

-   Upon successful response, it processes the received data.

-   It constructs legends for service providers based on marker colors.

-   It iterates through the received data and creates OpenLayers features for each data point.

-   For each feature, it creates a point geometry and assigns attributes such as application_id, house_number, application_date, service_provider, and emptying_date.

-   It adds each feature to the vector source of the application_NotTPDate_containment_markers layer.

-   After processing the data, it removes the AJAX loader.

-   It shows the layer named 'containments_layer', which presumably contains the map features related to containment areas.

-   Finally, it zooms to the city extent on the map.

-   In case of an error during the AJAX request, it calls the displayAjaxError() function, which likely displays an error message to the user.

##### Containments proposed to be emptied

-   This tool displays the containments that have been proposed to be emptied according to certain filters that the user can select from.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

\<a id="containments_proposed_to_be_emptied" class="btn btn-default collapse-control" role="button" data-toggle="collapse" href="\#collapse_proposed_emptying_containments" aria-expanded="false" aria-controls="collapse_proposed_emptying_containments"\>\<i class="fa fa-square" aria-hidden="true"\>\</i\>Find Containments proposed to be emptied\</a\>

\< -- CODE END -- \>

-   Here, id value (containments_proposed_to_be_emptied) trigger the jQuery as

\< -- CODE START -- \>

\$('\#containments_proposed_to_be_emptied’).click(function(e) {

……

… } \< -- CODE END --\>

-   When the button is clicked, it checks if the element with the ID " containments_proposed_to_be_emptied " does not have a class that includes "collapsed". it:

    -   It checks if the eLayer object has a property called " proposed_emptying_containments ", if it does, it calls the clear method of the source of that layer.

-   It also contains form that filters the containments propsed to be emptied based on days, next week and date

-   To filter based on days, it triggers the jQuery of id value (proposed_emptying_days_form) as

    -   The code begins with a jQuery event listener attached to the form with the ID proposed_emptying_days_form. This function listens for form submission.

    -   It retrieves the value of the entered number of days from the input field with the ID proposed_emptying_days.

    -   If a valid number of days is entered, it proceeds to calculate the start date and end date based on the current date and the entered number of days.

    -   It constructs a message string indicating the number of containments proposed to be emptied in the next 'n' days for display purposes.

    -   After validation, it calls the function **displayProposedEmptyingContainments(startDate, endDate, message**) which is explained below.

-   To filter based on days, it triggers the jQuery of id value (proposed_emptying_days_form) as

    -   The code begins with a jQuery event listener attached to the form with the ID proposed_emptying_week_form. This function listens for form submission.

    -   It calculates the start date as the current date and formats it as 'YYYY-MM-DD'.

    -   It calculates the end date by adding 7 days to the current date using Moment.js (moment().add(7, 'days')) and formats it as 'YYYY-MM-DD'.

    -   It constructs a message string indicating the number of containments proposed to be emptied next week. This message will likely be displayed to the user for information.

    -   After calculating the start date, end date, and message, it calls the function **displayProposedEmptyingContainments(startDate, endDate, message)** whish is explained below.

-   To filter based on days, it triggers the jQuery of id value (proposed_emptying_days_form) as

    -   It starts with a jQuery event listener attached to the form with the ID proposed_emptying_days_form. This function listens for form submission.

    -   It retrieves the value entered for the number of days from the input field with the ID proposed_emptying_days.

    -   If no value is entered (!days), it displays a warning using SweetAlert (Swal) indicating that the user needs to enter the number of days.

    -   If a value is entered but it's not a valid positive integer (!Number.isInteger(days) \|\| days \< 0), it displays another warning indicating that the input for the number of days is invalid.

    -   If the entered value is a valid positive integer, it proceeds to calculate the start and end dates.

    -   The start date is set to the current date formatted as 'YYYY-MM-DD'.

    -   The end date is calculated by adding the entered number of days to the current date and then formatting it as 'YYYY-MM-DD'.

    -   It constructs a message indicating the number of containments proposed to be emptied within the next 'n' days.

    -   After validating the input and calculating the dates, it calls the **displayProposedEmptyingContainments(startDate, endDate, message)** function whish is explained below.

        **displayProposedEmptyingContainments(startDate, endDate, message**)

-   It checks if the layer proposed_emptying_containments exists in the eLayer object.

-   If the layer already exists, it clears its source to remove any existing features.

-   If the layer doesn't exist, it creates a new vector layer with an empty source and adds it to the map using the addExtraLayer() function.

-   It constructs a URL for the AJAX request based on the Laravel route maps/proposed-emptying-containments, appending the start date and end date as parameters. It calls **getProposedEmptyingContainments() of MapsController.**

-   It sends an AJAX GET request to the constructed URL.

-   It includes the CSRF token in the request data for security.

-   Upon successful response, it iterates through the received data.

-   For each data point, if latitude and longitude coordinates are present, it creates a new feature with a point geometry at those coordinates.

-   It adds each feature to the source of the proposed_emptying_containments layer.

-   It constructs HTML content to display the message along with the count of proposed emptying containments.

-   It updates the modal body with this HTML content and displays the modal (\#proposed_emptying_modal).

-   It removes the AJAX loader after processing the data.

-   It shows the layer named containments_layer.

-   It zooms to the city extent on the map.

-   In case of an error during the AJAX request, it calls the displayAjaxError() function to handle the error.

##### Service Feedback

-   This tool displays the feedback chart of custom boundary entered by the user. If the user requires the feedback of a certain area, the user can utilize this tool to generate the feedback chart that aggregates all the records of feedback from that particular area.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="feedback_control" class="btn btn-default map-control"\>\<i class="fa fa-list-alt"\>\</i\>Feedback Chart (FSM Service Quality) \</a\>

    \< -- CODE END -- \>

-   Here, id value (feedback_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#feedback_control').click(function (e) {

    …. .. …. … … } \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“feedback_control”).

-   It then checks if the current control is "feedback_control" and if so, sets the current control to an empty string.

-   If not, it sets the current control to "feedback_control" and adds the class "map-control-active" to the element with the ID "feedback_control".

-   The function then creates a new vector layer and adds it to the map as a layer. The function also creates an interaction to allow the user to draw a polygon on the map and sends the data to the server via an AJAX call to the specified URL. It calls getFeedbackReport() of MapsController.

-   The success callback of the AJAX call updates the UI with charts and statistics based on the data returned from the server.

#### General Tools

##### Building by Structure Type

-   This tool filters buildings by specific building structure types.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<div id="building_structype_checkbox_container"\>

    @foreach(\$pickStructureResults as \$structype)

    \<div class="checkbox"\>

    \<label\>

    \<input type="checkbox" name="{{ \$structype-\>id }}" value= "{{ \$structype-\>id }}" /\> {{ \$structype-\>type }}

    \</label\>\</div\>

    @endforeach

    \</div\>

    \< -- CODE END -- \>

-   Here, id value (**building_structype_checkbox_container**) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#building_structype_checkbox_container').on('change', 'input[type=checkbox]', function () {

    … …. ..

    … ….. } \< -- CODE END -- \>

-   When the event is triggered, the code runs a function that does the following:

    -   Initializes an empty array called "checkedList".

    -   Using jQuery, it finds all of the child input checkboxes that are currently checked within the container element, and for each one, it pushes a string into the "checkedList" array. The string is in the format "structure_type = '" + the name attribute of the checkbox + "'".

    -   If there are any items in the "checkedList" array, it sets a property on an object called "mFilter" to a string in the format "(checkedList[0] OR checkedList[1] OR ...)"

    -   If the "checkedList" array is empty, it sets the same property on "mFilter" to an empty string.

    -   It calls a function called "updateAllCQLFiltersParams" explained in supporting function below.

    -   It calls a function called "showLayer" with the argument of 'buildings_layer'.

##### Building by Tax Payment status

-   This tool filters buildings by specific tax payment status. This tool helps reflect the tax payment status in the map.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<div id="building_tax_payment_checkbox_container"\>

    @foreach(\$dueYears as \$key =\> \$val)

    \<div class="checkbox"\>

    \<label\>\<input type="checkbox" name="{{\$key}}" value="{{\$val}}"/\> {{\$val}} \</label\>\</div\>

    @endforeach \</div\> \< --CODE END -- \>

-   Here, id value (building_tax_payment_checkbox_container) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#building_tax_payment_checkbox_container').on('change', 'input[type=checkbox]', function () {

    …..

    .....} \< -- CODE END -- \>

-   When the event is triggered, the code runs a function that does the following:

    -   Initializes an empty array called "checkedList".

    -   Using jQuery, it finds all of the child input checkboxes that are currently checked within the container element, and for each one, it pushes a string into the "checkedList" array. The string is in the format "due_year = '" + the name attribute of the checkbox + "'".

    -   If there are any items in the "checkedList" array, it sets a property on an object called "mFilter" to a string in the format "(checkedList[0] OR checkedList[1] OR ...)"

    -   If the "checkedList" array is empty, it sets the same property on "mFilter" to an empty string.

    -   It calls a function called "updateAllCQLFiltersParams" explained in supporting function below.

    -   It calls a function called "showLayer" with the argument of ‘buildings_tax_status_layer’.

##### Building by Water Supply Payment status

-   This tool filters buildings by water supply payment status.

-   Path: views/maps/index.blade.php

    \<-- CODE START-- \>

    \<div id="water_supply_payment_checkbox_container"\>

@foreach(\$dueYears as \$key =\> \$val)

\<div class="checkbox"\>

\<label\>\<input type="checkbox" name="{{\$key}}" value="{{\$val}}"/\>

{{\$val}}

\</label\>\</div\>

@endforeach

\</div\> \< -- CODE END -- \>

-   Here, id value (building_tax_payment_checkbox_container) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#water_supply_payment_checkbox_container').on('change', 'input[type=checkbox]', function () {

    …..

    .....} \< -- CODE END -- \>

-   When the event is triggered, the code runs a function that does the following:

    -   Initializes an empty array called "checkedList".

    -   Using jQuery, it finds all of the child input checkboxes that are currently checked within the container element, and for each one, it pushes a string into the "checkedList" array. The string is in the format "due_year = '" + the name attribute of the checkbox + "'".

    -   If there are any items in the "checkedList" array, it sets a property on an object called "mFilter" to a string in the format "(checkedList[0] OR checkedList[1] OR ...)"

    -   If the "checkedList" array is empty, it sets the same property on "mFilter" to an empty string.

    -   It calls a function called "updateAllCQLFiltersParams" explained in supporting function below.

    -   It calls a function called "showLayer" with the argument of ‘buildings_water_payment_status_layer’.

    -   Here, the data visualized can be exported in different forms such as : CSV, KML and Shape File.

        -   CSV :

            \< -- CODE START -- \>

            \$('\#export_building_tax_filter_csv').click(function (e) {

            e.preventDefault();

            exportBuildingTaxFilter('csv');

            }); \< -- CODE END -- \>

            -   When clicked event listener on an element with the ID export_building_tax_filter_csv. When clicked, it triggers the export of building tax filter data in CSV format.

            -   For this, it calls **exportBuildingTaxFilter()** functions passing parameter csv which is explained below.

        -   KML:

            \< -- CODE START -- \>

            \$('\#export_building_tax_filter_kml').click(function (e) {

            e.preventDefault();

            exportBuildingTaxFilter('kml');

            }); \< -- CODE END -- \>

            -   When clicked event listener on an element with the ID export_building_tax_filter_kml. When clicked, it triggers the export of building tax filter data in KML format.

            -   For this, it calls **exportBuildingTaxFilter()** functions passing parameter kml which is explained below.

        -   Shape File:

            \< -- CODE START -- \>

            \$('\#export_building_tax_filter_shp').click(function (e) {

            e.preventDefault();

            exportBuildingTaxFilter('shp');

            }); \< -- CODE END -- \>

            -   When clicked event listener on an element with the ID export_building_tax_filter_shp. When clicked, it triggers the export of building tax filter data in shape file format.

            -   For this, it calls **exportBuildingTaxFilter()** functions passing parameter shp which is explained below.

exportBuildingTaxFilter(format)

-   The function first checks if the provided export format is one of the accepted formats: 'csv', 'kml', or 'shp'.If the format provided is not one of these, the function returns, indicating an invalid export format.

-   If the format is valid, the function constructs a CQL (Common Query Language) filter based on the checkboxes checked in the building_tax_payment_checkbox_container.

-   It iterates over the checked checkboxes and constructs a list of CQL filter conditions, ensuring the 'due_year' matches the checked checkbox's name attribute.

-   If no checkboxes are checked, it displays a warning using SweetAlert and returns from the function.

-   The function determines the output format based on the provided export format. It maps the provided format to the corresponding output format used in the export link ('CSV', 'KML', or 'SHAPE-ZIP').

-   The function constructs an export link based on the provided format and the constructed CQL filter.

-   It includes necessary parameters such as the Geoserver URL (gurl_wfs), authentication key (authkey), workspace (workspace), and the property names to be included in the export.

-   If the output format is 'SHAPE-ZIP', it also includes additional format options for the filename based on the current date and time.

-   Finally, the function opens the constructed export link in a new browser window, triggering the download of the exported file.

#### Data Export Tools

##### Filter By Wards

-   This tool filter specific data layers by within selected wards. It allows the user to drill-down the records on the basis of wards.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<form role="form" name="ward_form" id="ward_form"\>

    \<div class="form-group"\>

    label for="ward"\>Wards\</label\>

    {!! Form::select('ward', \$wards,null,

    ['id' =\> 'ward', 'multiple' =\> true, 'style' =\> 'width: 100%'])!!}

    \</div\>

    \<div class="form-group"\>

    \<label for="ward_overlay"\>Overlay\</label\>

    \<select id="ward_overlay" style="width: 100%"\>

    option value=""\>Select a layer\</option\>

    \</select\>

    \</div\>

    . . . .. .. . .. .

    \<a class="dropdown-item" href="\#" id="export_ward_filter_shp"\>Shape File\</a\>

    \</div\>\</div\>

    \</form\>

    \< -- CODE END -- \>

-   Retrieves the selected values of the form inputs with the IDs ward and ward_overlay.

-   If both values are present, the code sets up a filter by creating an array of filters based on the selected values of ward, and joining the filters with the OR operator.

-   The code then sets a ward property on an object mFilter to the resulting filter expression, using the INTERSECTS function.

-   The code iterates through each layer in an object mLayer, adding the ward filter to the selected layer using the addFilterToLayer function and removing the filter from the other layers using the removeFilterFromLayer function.

-   The code calls the showLayer function with the argument wards_layer to show the filtered wards_layer.

-   If either of the values selectedWards or selectedLayer is not present, the code sets the ward property of mFilter to an empty string and removes the filter from all layers.

-   The code updates updateAllCQLFiltersParams function.

-   If selectedLayer is present, the code calls the showLayer function with selectedLayer as the argument to show the selected layer.

-   The function returns false to prevent the form from being submitted and reloading the page.

-   Here, the data visualized can be exported in different forms such as : CSV, KML and Shape File.

    -   CSV :

        \< -- CODE START -- \>

        \$('\#export_ward_filter_csv').click(function (e) {

        e.preventDefault();

        exportWardFilter ('csv');

        }); \< -- CODE END -- \>

        -   When clicked event listener on an element with the ID export_ward_filter_csv. When clicked, it triggers the export of ward filter data in CSV format.

            -   For this, it calls **exportWardFilter ()** functions passing parameter csv which is explained below.

    -   KML :

        \< -- CODE START -- \>

        \$('\#export_ward_filter_kml').click(function (e) {

        e.preventDefault();

        exportWardFilter ('kml');

        }); \< -- CODE END -- \>

        -   When clicked event listener on an element with the ID export_ward_filter_kml. When clicked, it triggers the export of ward filter data in KML format.

            -   For this, it calls **exportWardFilter ()** functions passing parameter kml which is explained below.

    -   Shape file:

        \< -- CODE START -- \>

        \$('\#export_ward_filter_shp').click(function (e) {

        e.preventDefault();

        exportWardFilter ('shp');

        }); \< -- CODE END -- \>

        -   When clicked event listener on an element with the ID export_ward_filter_shp. When clicked, it triggers the export of ward filter data in shape file format.

            -   For this, it calls **exportWardFilter ()** functions passing parameter shp which is explained below.

exportWardFilter(outputFormat)

-   This checks if the outputFormat is one of the allowed values (csv, kml, shp). If outputFormat is not in this list, the function exits early.

-   The function first checks if the provided export format is one of the accepted formats: 'csv', 'kml', or 'shp'.

-   If the format provided is not one of these, the function returns, indicating an invalid export format.

-   The function retrieves the selected ward values from the element with the ID ward and the selected layer value from the element with the ID ward_overlay.

-   If either the selected wards or the selected layer is not specified, it displays a warning using SweetAlert and returns from the function.

-   The function constructs a CQL (Common Query Language) filter based on the selected wards.

-   It iterates over the selected ward values and constructs a list of filter conditions, ensuring that each ward matches the value of the 'ward' attribute.

-   The function determines the output format based on the provided export format. It maps the provided format to the corresponding output format used in the export link ('CSV', 'KML', or 'SHAPE-ZIP').

-   The function constructs an export link based on the provided format, selected layer, and the constructed CQL filter.

-   It includes necessary parameters such as the Geoserver URL (gurl_wfs), authentication key (authkey), workspace (workspace), and the property names to be included in the export.

-   Calls a function named csvdata (outputFormat, selectedLayer, exportLink) to handle the actual export process. It passes the outputFormat, selectedLayer, and exportLink as arguments. Explained below in supporting function.

##### Export Data set (of custom boundary)

-   This tool lets user export data set according to the different overlay’s options.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<span data-toggle="tooltip" data-placement="bottom"

    title="Export Data Set of Custom Boundary"\>

    \<a href="\#" id="export_control" class="btn btn-default map-control"\>\<i class="fa-solid fa-file-export"\>\</i\>Export Data Set\</a\>

    \</span\> \< -- CODE END -- \>

-   Here, id value (export_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#export_control').click(function (e) {

    …..

    .....} \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“export_control”).

-   If the "currentControl" variable is equal to "export_control", then "currentControl" is set to an empty string, and the "map-control-active" class is not added to the element with the id of "export_control".

-   If "currentControl" is not equal to "export_control", then the "currentControl" variable is set to "export_control" and the "map-control-active" class is added to the element with the id of "export_control".

-   A new Vector layer called "exportPolygonLayer" is created if the "eLayer.export_polygon" property is false.

-   The new layer is added to the map as an extra layer with the id "export_polygon", a name of "Export Polygon", and the new layer "exportPolygonLayer".

-   The "draw" interaction is created as a "Polygon" Draw interaction with its source set to the source of "exportPolygonLayer".

-   Two event listeners are added to the "draw" interaction. One for the "drawstart" event that clears the source of "exportPolygonLayer" and sets the position of the "exportPopupOverlay" to undefined. The other for the "drawend" event that calls **displayExportPopup()** using the geometry of the drawn feature which is explained below , removes the "map-control-active" class from the element with id "export_control", sets the "currentControl" to an empty string, removes the "draw" interaction from the map, and adds a "drag" interaction to the map.

displayExportPopup()

-   The function first clears the value of an element with the ID export_overlay. This element is presumably used to hold information about the selected export overlay.

-   It removes any previously attached click event handlers from elements with the IDs export-csv-btn, export-kml-btn, and export-shape-btn. This ensures that the click event handlers are not duplicated.

-   The function attaches click event handlers to elements with IDs export-csv-btn, export-kml-btn, and export-shape-btn.

-   When any of these buttons are clicked, they will call the **openExportLink()** function with the appropriate geometry and export format which is explained below.

-   It sets the position of the export popup overlay to the interior point of the provided geometry. This presumably ensures that the popup appears at a suitable location related to the geometry.

openExportLink()

-   The function retrieves the value of the selected overlay from an element with the ID export_overlay. If no overlay is selected, it displays a warning using SweetAlert and returns from the function.

-   It uses the OpenLayers format library (ol.format.WKT()) to convert the provided geometry to Well-Known Text (WKT) format. This geometry is then transformed from EPSG:3857 to EPSG:4326.

-   Based on the selected layer, geometry in WKT format, and output format, the function constructs an export link.

-   It includes necessary parameters such as the Geoserver URL (gurl_wfs), authentication key (authkey), workspace (workspace), and the constructed WKT geometry.

-   Calls a function named csvdata (outputFormat, selectedLayer, exportLink) to handle the actual export process. It passes the outputFormat, selectedLayer, and exportLink as arguments.Explained below in supporting function.

##### Building Owner Information

-   This tool can be used to extract the information of the building owner of each building through the map interface itself by drawing a polygon that covers the area of the required buildings.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="update_tax_zone" class="btn btn-default map-control"\>\<i class="fa fa-database"\>\</i\>Building Owner Information\</a\> \< -- CODE END -- \>

-   Here, id value (update_tax_zone) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#update_tax_zone').click(function (e) {

    . . . . .

    . . . .

    . .

    } \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“update_tax_zone”).

-   If the "currentControl" variable is equal to " update_tax_zone", then "currentControl" is set to an empty string.

-   If "currentControl" is not equal to " update_tax_zone ", then the "currentControl" variable is set to " update_tax_zone " and the "map-control-active" class is added to the element with the id of " update_tax_zone ".

-   A new Vector layer called " export_tax_polygon " is created if the "eLayer. export_tax_polygon " property is false.

-   The new layer is added to the map as an extra layer with the id " export_tax_polygon ", a name of "Export Polygon", and the new layer "exportPolygonLayer".

-   The "draw" interaction is created as a "Polygon" Draw interaction with its source set to the source of " export_tax_polygon ".

-   Two event listeners are added to the "draw" interaction. One for the "drawstart" event that clears the source of " export_tax_polygon " and sets the position of the "exportPopupOverlay" to undefined. The other for the "drawend" event that displays the export popup using the geometry of the drawn feature, removes the "map-control-active" class from the element with id " export_tax_polygon ", sets the "currentControl" to an empty string, removes the "draw" interaction from the map, and adds a "drag" interaction to the map.

#### Decision Tools

##### Find Tax Due Buildings

-   This tool filter shows tax due buildings.

-   Path: views/maps/index.blade.php

    \< -- CODE START-- \>

    \<form role="form" name="tax_due_buildings_form" id="tax_due_buildings_form"\>

    \<div class="form-group"\>

    \<label for="ward_tax_due"\>Wards\</label\>

    {!! Form::select('ward', \$wards, null,

    ['id' =\> 'ward_tax_due', 'multiple' =\> true, 'style' =\> 'width: 100%'])!!}

    \</div\>

    {{-- \<div class="form-group"\>

    ="tax_zone_tax_due"\>Tax Zones\</label\>

    {!! Form::select('tax_zone', \$taxZones, null,

    ['id' =\> 'tax_zone_tax_due', 'multiple' =\> true, 'style' =\> 'width: 100%'])!!} \</div\> --}}

    \<button type="submit" class="btn btn-default"\>Filter\</button\>

    \<button type="button" class="btn btn-default" id="wardtaxzone_clear_button"\>Clear\</button\>

    \</form\> \< -- CODE END -- \>

-   Here, id value (tax_due_buildings_form) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#tax_due_buildings_form').submit(function () {

    ….

    …..

    } \< -- CODE END -- \>

-   The function starts by retrieving the selected values of the dropdown lists with IDs "ward_tax_due" and "tax_zone_tax_due".

-   It checks if the "building_markers" layer already exists in the eLayer object. If it does, it clears the source of the layer. If not, it creates a new vector layer with an empty source and adds it to the eLayer object as the "building_markers" layer.

-   It displays an AJAX loading spinner by calling the displayAjaxLoader() function.

-   It sets up an AJAX request to the URL "{{ url("maps/due-buildings-ward-taxzone") }}", with the selected values of the dropdown lists, and a token "{{ csrf_token() }}". It calls **getDueBuildingsWardTaxzone()** of **MapsController**.

-   If the AJAX request is successful, the function loops through the returned data, and for each item with a latitude and longitude:

    -   creates a new point feature using the longitude and latitude values and transforms the coordinates from EPSG:4326 to EPSG:3857.

    -   It creates a new style for the feature, using a green building icon.

    -   It sets the style for the feature. It adds the feature to the source of the "building_markers" layer.

    -   If the AJAX request fails, it display error message.

    -   The function shows the "buildings_layer" and calls the zoomToCity() function to zoom the map to the city level. The ZoomToCity() is explained below in supporting function.

    -   The function returns false to prevent the form from being submitted and reloading the page.

##### Sewers Potential Buildings

-   This tool can be used to determine which non-sewered buildings can be connected to the existing sewer line by using a buffer distance.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="drainpotential_control" class="btn btn-default map-control"\>\<i class="fa-solid fa-building"\>\</i\>Sewers Potential Buildings\</a\> \< --CODE END --\>

-   Here, id value (drainpotential_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#drainpotential_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'drainpotential_control') {

    currentControl = '';

    } else {

    currentControl = 'drainpotential_control';

    \$('\#drainpotential_control').addClass('map-control-active');

    map.on('pointermove', hoverOnDrainHandler);

    map.on('singleclick', displayDrainPotentialBuildings);

    }

    }); \< --CODE END --\>

-   Initial steps are explained below in Initialize having id value (“drainpotential_control”).

-   The code checks if currentControl is equal to drainpotential_control, if the condition is true than the variable "currentControl" is set to an empty string. Else variable "currentControl" is set to drainpotential_control.

-   Adds the "map-control-active" class to the element with the ID “drainpotential_control”

-   Finally, the function attaches two event handlers to the map object: 'pointermove' event is handled by the " hoverOnDrainHandler " function, and 'singleclick' event is handled by the " displayDrainPotentialBuildings " function. The functions " hoverOnDrainHandler "is explained further in supporting function and " “displayDrainPotentialBuildings " are further explained in the below.

displayDrainPotentialBuildings()

-   Clears the previous selected sewer line and adds a new vector layer for the selected sewer line with the blue color and a width of 3 pixels.

-   Clears the previous selected buildings and adds a new vector layer for the buildings with the blue color and a width of 3 pixels.

-   Queries a WMS source for the sewer lines layer and generates a get feature info URL.

-   Makes an AJAX call to the generated URL and retrieves the sewer line information in the form of GeoJSON.

-   Adds the sewer line to the "selected_drains" vector layer and populates the drain code, longitude, latitude, and buffer distance fields.

-   Displays a modal with the populated data and also allow to export this data to excel.

-   Removes the AJAX loading icon and disables the "pointermove" and "singleclick" event listeners on the map.

-   If the AJAX call is unsuccessful, it displays an error message.

##### Buildings to Sewer

-   This tool shows Buildings connected to sewer.

-   Path: views/maps/index.blade.php

    \< --CODE START --\>

    \<a href="\#" id="drainbuildings_control" class="btn btn-default map-control"\>\<i class="fa fa-building"\>\</i\>Find Buildings to Sewer\</a\> \< --CODE END --\>

-   Here, id value (drainbuildings_control) trigger the jQuery as

    \< --CODE START --\>

    \$('\#drainbuildings_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'drainbuildings_control') {

    currentControl = '';

    } else {

    currentControl = 'drainbuildings_control';

    \$('\#drainbuildings_control').addClass('map-control-active');

    map.on('pointermove', hoverOnDrainHandler);

    map.on('singleclick', displayDrainBuildings);

    }

    }); \< --CODE END --\>

-   Initial steps are explained below in Initialize having id value (“drainbuildings_control”).

-   The code checks if currentControl is equal to drainbuildings_control, if the condition is true than the variable "currentControl" is set to an empty string. Else variable "currentControl" is set to drainbuildings_control.

-   Adds the "map-control-active" class to the element with the ID “drainbuildings_control”

-   Finally, the function attaches two event handlers to the map object: 'pointermove' event is handled by the " hoverOnDrainHandler " function, and 'singleclick' event is handled by the " displayDrainBuildings " function. The functions " hoverOnDrainHandler "is explained further in supporting function and " “displayDrainBuildings " are further explained in the below.

displayDrainBuildings()

-   If a layer named "selected_drains" exists, clear its source, otherwise create a new vector layer with a red stroke.

-   If a layer named "drain_buildings" exists, clear its source, otherwise create a new vector layer with a blue stroke.

-   Get the current view resolution of the map.

-   Create an ImageWMS source using the URL "gurl_wms".

-   Get the "GetFeatureInfo" URL using the WMS source, view resolution, and the coordinate of the event.

-   Make an AJAX call to the URL to get data about the sewer line at the event's coordinate.

-   If data is returned, extract the drain codes and make another AJAX call to get the drain buildings data.

-   If the drain buildings data is returned, add the sewer line features to the "selected_drains" layer and drain building features to the "drain_buildings" layer.

-   Remove the AJAX loader and turn off the map control.

##### Buildings to road

-   This tool shows Buildings connected to road.

-   Path: views/maps/index.blade.php

    \< --CODE START --\>

    \<a href="\#" id="roadbuildings_control" class="btn btn-default map-control"\>\<i class="fa fa-building"\>\</i\>Find Buildings to Road\</a\>

    \< --CODE END --\>

-   Here, id value (roadbuildings_control) trigger the jQuery as

    \< --CODE START --\>

    \$('\#roadbuildings_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'roadbuildings_control') {

    currentControl = '';

    } else {

    currentControl = 'roadbuildings_control';

    \$('\#roadbuildings_control').addClass('map-control-active');

    map.on('pointermove', hoverOnRoadBuildingHandler);

    map.on('singleclick', displayRoadBuildings);

    }

    }); \< --CODE END --\>

-   Initial steps are explained below in Initialize having id value (“roadbuildings_control”).

-   The code checks if currentControl is equal to roadbuildings_control, if the condition is true than the variable "currentControl" is set to an empty string. Else variable "currentControl" is set to roadbuildings_control.

-   Adds the "map-control-active" class to the element with the ID “roadbuildings_control”

-   Finally, the function attaches two event handlers to the map object: 'pointermove' event is handled by the " hoverOnRoadBuildingHandler " function, and 'singleclick' event is handled by the " displayRoadBuildings " function. The functions " hoverOnRoadBuildingHandler " and " “displayRoadBuildings " are further explained in the below.

displayRoadBuildings()

-   If there are existing layers for selected road buildings (eLayer.selected_road_buildings) and road-to-building relationships (eLayer.road_to_buildings), they are cleared to ensure that only the new data is displayed.

-   New vector layers are created for selected road buildings and road-to-building relationships if they don't already exist. These layers are styled with a yellow stroke.

-   The function retrieves information about the roadlines layer at the clicked coordinate using a WMS GetFeatureInfo request. This request fetches data in JSON format.

-   If the request is successful and features are found, the function extracts the road codes from the response and updates the hidden input field with these codes.

-   It then makes an AJAX request to retrieve building information related to the selected roads. It calls **getBuildingsToRoad()** of **MapsController**.

-   Upon successful retrieval of building information, the selected road buildings are added to the map, and a popup is displayed with details about buildings related to the selected roads.

-   If there are errors during any of the AJAX requests, appropriate error handling is performed.

-   The function removes active states from map controls and unbinds event listeners for pointer move and single click events.

hoverOnRoadBuildingHandler()

-   The function first checks if the event is currently being dragged, and if so, it exits the function.

-   Next, the function gets the pixel coordinates of the mouse event and uses the "forEachLayerAtPixel" method of the map object to check if the mouse is currently hovering over a layer with the name " roadlines_layer".

-   If it is, the function sets the cursor style to "pointer" to indicate that the feature is clickable. If the mouse is not hovering over a " roadlines_layer" feature, the cursor is set to an empty string, which defaults it back to its default style.

-   If the mouse is not being dragged, the function gets the pixel coordinates of the mouse pointer using the "getEventPixel" method, and then checks if the mouse pointer is over a layer on the map using the "forEachLayerAtPixel" method.

-   If the mouse is over a layer, the cursor is changed to a pointer, otherwise the cursor is set to an empty string, which makes it disappear.

##### Hard to reach building

-   This tool allows the user to find the roads that are inaccessible to reach building on map.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="road_inaccessible_control" class="btn btn-default map-control" data-toggle="tooltip" data-placement="bottom" title="Hard to reach building"\>\<i class="fa-brands fa-buffer"\>\</i\>\</a\>\<--CODE End -- \>

-   Here, id value (road_inaccessible_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#road_inaccessible_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    displayAjaxLoader();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'road_inaccessible_control') {

    currentControl = '';

    removeAjaxLoader();

    \$('\#add-road-inaccessible-box').hide();

    } else {

    currentControl = 'road_inaccessible_control';

    \$('\#road_inaccessible_control').addClass('map-control-active');

    map.on('pointermove', hoverOnRoadsHandler);

    removeAjaxLoader();

    \$('\#add-road-inaccessible-box').show();

    }

    }); \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“road\_ inaccessible_control”).

-   This code binds a click event handler to the HTML element with the id road\_ inaccessible \_control.

-   Removes the class map-control-active from all elements with the class map-control.

-   Check if the current control is already set to 'road_inaccessible_control'. If it is, reset the currentControl variable, remove the AJAX loader, and hide the 'add-road-inaccessible-box'

-   Else attach a pointermove event listener to the map, executing hoverOnRoadsHandler function when the pointer moves. hoverOnRoadsHandler() explained below in supporting functions.

-   Remove the AJAX loader

-   Show the 'add-road-inaccessible-box' which allows the user to enter a road width and a hose length to find the hard to reach buildings.

-   The submit button of form binds a click event handler to the HTML element with the id add_road\_ inaccessible \_submit_button.

    -   Hide the road inaccessible popup overlay.

    -   Clear existing data in the summary_road_inaccessible layer if it exists, or create a new layer if it doesn't. Create a new vector layer for summary road inaccessible.

    -   Clear existing data in the road_inaccessible_buildings layer if it exists, or create a new layer if it doesn't.

    -   Setup AJAX headers including CSRF token and content type. It calls ‘**roadInaccessibleBuildings’** function of ‘**RoadlineController**’.

    -   Make an AJAX request to fetch road inaccessible buildings data.

    -   On success, populate form fields with retrieved data, road inaccessible popup content, show road inaccessible popup container and set its position. Retrieved buildings data and add them to the map. Retrive data call also be exported in csv.

##### Building close to water bodies

-   This tool allows the user to find building close to water bodies on map.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="waterbody_inaccessible_control" class="btn btn-default map-control"

    data-toggle="tooltip" data-placement="bottom" title="Building close to water bodies"\>\<i class="fa-solid fa-water"\>\</i\>\</a\>

    \<--CODE End -- \>

-   Here, id value (waterbody_inaccessible_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#waterbody_inaccessible_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    displayAjaxLoader();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'waterbody_inaccessible_control') {

    currentControl = '';

    removeAjaxLoader();

    \$('\#add-waterbody-inaccessible-box').hide();

    } else {

    currentControl = 'waterbody_inaccessible_control';

    \$('\#waterbody_inaccessible_control').addClass('map-control-active');

    map.on('pointermove', hoverOnRoadsHandler);

    removeAjaxLoader();

    \$('\#add-waterbody-inaccessible-box').show();

    }

    }); \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“waterbody\_ inaccessible_control”).

-   This code binds a click event handler to the HTML element with the id waterbody\_ inaccessible \_control.

-   Removes the class map-control-active from all elements with the class map-control.

-   Check if the current control is already set to waterbody_inaccessible_control'. If it is, reset the currentControl variable, remove the AJAX loader, and hide the 'add-waterbody-inaccessible-box'

-   Else attach a pointermove event listener to the map, executing hoverOnRoadsHandler function when the pointer moves. hoverOnRoadsHandler() explained below in supporting functions.

-   Remove the AJAX loader

-   Show the 'add-waterbody-inaccessible-box' which allows the user to enter a buffer distance.

-   The submit button of form binds a click event handler to the HTML element with the id add_waterbody\_ inaccessible_submit_btn.

    -   Hide the waterbody inaccessible popup overlay.

    -   Clear existing data in the summary\_ waterbody_inaccessible layer if it exists, or create a new layer if it doesn't. Create a new vector layer for summary waterbody inaccessible.

    -   Clear existing data in the waterbody \_inaccessible_buildings layer if it exists, or create a new layer if it doesn't.

    -   Setup AJAX headers including CSRF token and content type. It calls ‘**waterbodyInaccessibleBuildings’** function of ‘**MapsController**’.

    -   Make an AJAX request to fetch road inaccessible buildings data.

    -   On success, show the waterbody layers, set the map view center and zoom level, Clear any previous error messages, set the content of waterbodyInaccessiblePopupContent, set the position of waterbodyInaccessiblePopupOverlay, show the waterbodyInaccessiblePopupContainer, add features to layers.

    -   Populate form fields with retrieved data. Retrive data call also be exported in csv.

##### Building connected to Community Toilets

-   This tool allows the user to find building connected to community toilets on map.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="ptct_network" class="btn btn-default map-control"

    data-toggle="tooltip" data-placement="bottom" title="Building Connected to Public /Community Toilets"\>\<i class="fa-solid fa-bezier-curve"\>\</i\>\</a\>\<--CODE End -- \>

-   Here, id value (ptct_network) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#ptct_network').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'ptct_network') {

    currentControl = '';

    } else {

    currentControl = 'ptct_network';

    \$('\#ptct_network').addClass('map-control-active');

    map.on('pointermove', hoverOnPTCTHandler);

    map.on('singleclick', displayBuildingsToPTCT);

    }

    }); \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“ptct_network”).

-   This code binds a click event handler to the HTML element with the id ptct_network.

-   Removes the class map-control-active from all elements with the class map-control.

-   The code checks if currentControl is equal to ptct_network, if the condition is true than the variable "currentControl" is set to an empty string. Else variable "currentControl" is set to ptct_network.

-   Adds the "map-control-active" class to the element with the ID “ptct_network”

-   Finally, the function attaches two event handlers to the map object: 'pointermove' event is handled by the " hoverOnPTCTHandler " function, and 'singleclick' event is handled by the " displayBuildingsToPTCT " function.

-   The functions " hoverOnPTCTHandler " and " “displayBuildingsToPTCT " are explained below.

hoverOnPTCTHandler()

-   Inside the function, it checks if the map is being dragged (evt.dragging). If the map is being dragged, the function returns early and does nothing. This prevents unnecessary processing when the map is being interactively moved by the user.

-   It retrieves the pixel coordinates of the event (evt.originalEvent) relative to the map using map.getEventPixel(evt.originalEvent).

-   It then checks if there are any layers at the pixel coordinates using map.forEachLayerAtPixel(pixel, function (layer) { ... }). It iterates through all layers at that pixel, and if it finds a layer with the name 'toilets_layer', it returns true.

-   Based on whether there is a hit (a layer with the name 'toilets_layer'), it changes the cursor style of the map's target element (map.getTargetElement().style.cursor). If there is a hit, it sets the cursor to 'pointer', indicating to the user that there is something clickable under the cursor. Otherwise, it sets the cursor to the default style.

displayBuildingsToPTCT()

-   It first checks if a layer named selected_ptct exists. If it does, it clears its source. Otherwise, it creates a new vector layer for selected_ptct.

-   It then checks for the existence of another layer named buildings_ptct. If it exists, it clears its source. If not, it creates a new vector layer for buildings_ptct.

-   It sets up a Web Map Service (WMS) source using the OpenLayers ol.source.ImageWMS class. This source is used to retrieve information from a WMS server.

-   It generates a GetFeatureInfo URL using the getGetFeatureInfoUrl method of the WMS source. This URL is used to request information about a specific point on the map.

-   It sends an AJAX request to the generated URL to retrieve feature information. It calls ‘**getBuildingsToiletNetwork’** function of ‘**RoadlineController**’.

-   Upon successful response, it checks if features are returned.If features are found, it extracts relevant data, such as the bin property.

-   It then sends another AJAX request to a custom URL (maps/buildings-toilet-network) to retrieve building information related to the received features.

-   It adds features related to the selected PTCT (Public Toilets) to the selected_ptct layer. It adds features related to buildings linked to the PTCT to the buildings_ptct layer.

-   It handles various error scenarios, such as when no features or buildings are found, and displays appropriate error messages using SweetAlert modals.

-   It displays and removes AJAX loaders to indicate loading states during AJAX requests.

##### Area Population

-   The tool calculates the total population of people residing within the custom drawn boundary.

-   Path: views/maps/index.blade.php

    \< --CODE START --\>

    \<a href="\#" id="areapopulation_control" class="btn btn-default map control" data-toggle="tooltip" data-placement="bottom" title="Area Population"\>\<i class="fa fa-bars" aria-hidden="true"\>\</i\>Area Population\</a\> \< --CODE END --\>

-   Here, id value (areapopulation_control) trigger the jQuery as

    \< --CODE START --\>

    \$('\#areapopulation_control').click(function (e) {

    . . . .

    . . . . }\< --CODE END --\>

-   Initial steps are explained below in Initialize having id value (**“areapopulation_control”).**

-   If the current control is "areapopulation_control", the current control is set to an empty string.

-   If the current control is not "areapopulation_control", the current control is set to "areapopulation_control" and the element with the ID "areapopulation_control" gets the "map-control-active" class.

-   It creates a vector layer named "populationPolygonLayer" with a source of empty vector, and adds it to the map as a new layer named "areapopulation_polygon".

-   It creates a new interaction "draw" of type "Polygon" with source set to the source of the "areapopulation_polygon" layer.

-   It adds two listeners to the "draw" interaction, one for the "drawstart" event, which clears the source of the "areapopulation_polygon" layer and sets the position of "populationPopupOverlay" to undefined, and one for the "drawend" event, which does the following:

-   Writes the polygon geometry to a WKT format and sets it to a value of an element with the ID "population-export-geom".

-   Sends an AJAX request to the URL specified in the "url" variable with the polygon geometry and CSRF token as data. It calls **getAreaPopulationPolygonSum()** of **MapsController**.

-   On success, it sets the content of the "populationPopupContent" element to the data received from the AJAX request and sets the position of "populationPopupOverlay" to the interior point of the drawn feature.

-   Display error message.

-   It removes the "map-control-active" class from the element with the ID "areapopulation_control", sets the current control to an empty string, and removes the "draw" interaction from the map.

##### Summary Information Buffer Filter

-   This tool displays summary information of buildings and containments within the custom boundary set by the user in the range of the buffer value.

    \< --CODE START --\>

    \<a href="\#" id="report_control_summary_buffer" class="btn btn-default map-control"\>\<i class="fa fa-list-alt"\>\</i\>Summary Information Buffer Filter With Landuse\</a\>

    \< --CODE END --\>

-   Here, id value **(report_control_summary_buffer**) trigger the jQuery as

    \< --CODE START --\>

    \$('\#report_control_summary_buffer').click(function (e) {

    …

    .. ..} \< --CODE END --\>

-   Initial steps are explained below in Initialize having id value (**“report_control_summary_buffer”).**

-   The currentControl variable is checked and set.

-   If it was already set to "report_control_summary_buffer", it is reset to an empty string.

-   If it was not set to "report_control_summary_buffer", it is set to "report_control_summary_buffer" and the clicked element is given the "map-control-active" class.

-   If a report polygon buffer layer (eLayer.report_polygon_buffer) does not exist, it is created as a new vector layer.

-   The draw interaction is created as a polygon draw interaction using the report polygon buffer layer's source.

-   The draw interaction has two event handlers defined.

-   On "drawstart", the report polygon buffer layer's source is cleared and a report popup overlay's position is set to undefined.

-   On "drawend", a Well-Known Text (WKT) format is used to write the geometry of the drawn polygon, the polygon's WKT, coordinates, and interior point are saved to corresponding HTML input elements, the draw interaction is removed, and a buffer polygon popup modal and overlay are shown.

-   Buffer polygon popup modal shows the form to enter the buffer distance and based on the entered value data is populated. It also gives the features to export the populated data to excel.

-   For export, it calls **getBufferPolygonReportCSV()** of MapsController.

-   The draw interaction is added to the map.

##### Water Bodies Buffer Summary Information

-   This tool displays summary information of buildings and containments by water boundary buffer.

    \< -- CODE START -- \>

    \<a href="\#" id="buildingswaterbodies_control" class="btn btn-default map-control"\>\<i class="fa-solid fa-building"\>\</i\>Water Bodies Buffer Summary Information\</a\>

    \< -- CODE END -- \>

-   Here, id value (buildingswaterbodies_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#buildingswaterbodies_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'buildingswaterbodies_control') {

    currentControl = '';

    } else {

    currentControl = 'buildingswaterbodies_control';

    \$('\#buildingswaterbodies_control').addClass('map-control-active');

    map.on('pointermove', hoverOnWaterBodiesHandler);

    map.on('singleclick', displayWaterBodiesBuildings);

    }

    });

    \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“buildingswaterbodies_control”).

-   The code checks if currentControl is equal to buildingswaterbodies_control, if the condition is true than the variable "currentControl" is set to an empty string. Else variable "currentControl" is set to buildingswaterbodies_control.

-   Adds the "map-control-active" class to the element with the ID “buildingswaterbodies_control”

-   Finally, the function attaches two event handlers to the map object: 'pointermove' event is handled by the " hoverOnWaterBodiesHandler " function, and 'singleclick' event is handled by the " displayWaterBodiesBuildings " function. The functions " hoverOnWaterBodiesHandler " and " “displayWaterBodiesBuildings " are further explained in the below.

displayWaterBodiesBuildings()

-   displays buildings on selected water body and buffer distance

-   Check if the selected_waterbodies layer exists, if yes, clear its source, else create a new vector layer with blue stroke style.

-   Check if the waterbodies_buildings layer exists, if yes, clear its source, else create a new vector layer with blue stroke style.

-   Get the current view resolution of the map.

-   Create an ImageWMS source to get the waterbodies data from a WMS service.

-   Get the feature info URL of the WMS source and make an AJAX request to the URL.

-   On AJAX request success, check if the response has data and data.features is an array with at least one element.

-   If there is one feature, add it to the source of the selected_waterbodies layer, set the water body code, longitude, and latitude in the corresponding fields, and show the popup-waterbodies-buildings modal.

-   Here, modal shows the form to enter the buffer distance and based on the entered value data is populated. It also gives the features to export the populated data to excel.

-   For export, it calls **getWaterBodyReportCsv ()** of MapsController.

-   If there are more than one features, show an error modal indicating that more than one water body is found.

-   If no features are found, show an error modal indicating that the water body is not found.

-   Remove the buildingswaterbodies_control class and unregister the hover and click handlers for the map.

hoverOnWaterBodiesHandler()

-   The function first checks if the event is currently being dragged, and if so, it exits the function.

-   Next, the function gets the pixel coordinates of the mouse event and uses the "forEachLayerAtPixel" method of the map object to check if the mouse is currently hovering over a layer with the name " waterbodys_layer".

-   If it is, the function sets the cursor style to "pointer" to indicate that the feature is clickable. If the mouse is not hovering over a " waterbodys_layer" feature, the cursor is set to an empty string, which defaults it back to its default style.

-   If the mouse is not being dragged, the function gets the pixel coordinates of the mouse pointer using the "getEventPixel" method, and then checks if the mouse pointer is over a layer on the map using the "forEachLayerAtPixel" method.

-   If the mouse is over a layer, the cursor is changed to a pointer, otherwise the cursor is set to an empty string, which makes it disappear.

##### Ward Summary Information

-   This tool displays summary information of buildings and containments by wards.

    \< -- CODE START -- \>

    \<a href="\#" id="buildingswards_control" class="btn btn-default map-control"\>\<i class="fa-solid fa-building"\>\</i\>Wards Summary Information\</a\>

    \< -- CODE END -- \>

-   Here, id value **(buildingswards_control**) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#buildingswards_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'buildingswards_control') {

    currentControl = '';

    } else {

    currentControl = 'buildingswards_control';

    \$('\#buildingswards_control').addClass('map-control-active');

    map.on('pointermove', hoverOnWardsBuildingHandler);

    map.on('singleclick', displayWardsBuildings);

    }

    });

    \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (**“buildingswards_control”).**

-   The code checks if currentControl is equal to **buildingswards_control**, if the condition is true than the variable "currentControl" is set to an empty string. Else variable "currentControl" is set to **buildingswards_control**.

-   Adds the "map-control-active" class to the element with the ID “**buildingswards_control**”

-   Finally, the function attaches two event handlers to the map object: 'pointermove' event is handled by the " **hoverOnWardsBuildingHandler** " function, and 'singleclick' event is handled by the " **displayWardsBuildings** " function. The functions " **hoverOnWardsBuildingHandler** " and " “**displayWardsBuildings** " are further explained in the below.

hoverOnWardsBuildingHandler()

-   The function first checks if the event is currently being dragged, and if so, it exits the function.

-   Next, the function gets the pixel coordinates of the mouse event and uses the "forEachLayerAtPixel" method of the map object to check if the mouse is currently hovering over a layer with the name " wards_layer".

-   If it is, the function sets the cursor style to "pointer" to indicate that the feature is clickable. If the mouse is not hovering over a " wards_layer" feature, the cursor is set to an empty string, which defaults it back to its default style.

-   If the mouse is not being dragged, the function gets the pixel coordinates of the mouse pointer using the "getEventPixel" method, and then checks if the mouse pointer is over a layer on the map using the "forEachLayerAtPixel" method.

-   If the mouse is over a layer, the cursor is changed to a pointer, otherwise the cursor is set to an empty string, which makes it disappear.

displayWardsBuildings()

-   displays buildings on selected ward

-   It checks if a ward has already been selected and if so, it clears the previous selection. If not, it creates a new vector layer for the selected ward.

-   It creates an ImageWMS source using the URL of the WMS server and sets the layer and feature count as parameters.

-   It gets the URL for getting feature information by calling the getGetFeatureInfoUrl method of the WMS source with the clicked coordinates and the view resolution. If the URL is not generated, it shows an error message.

-   It makes an AJAX request to the URL to get the feature information and retrieves the properties of the selected ward. It calls **getWardBuildings()** of MapsController.

-   If the response contains a single ward, it adds the ward's features to the selected ward layer, sets the value of the ward_building_no element, and makes another AJAX request to retrieve the buildings in the ward.

-   If the response contains more than one ward, it shows an error message. If the response does not contain any ward, it also shows an error message.

-   Finally, it removes the class map-control-active from the \#buildingswards_control element, unbinds the pointermove and singleclick events from the map, and sets the value of the currentControl variable to an empty string.

-   For export, it calls **getWardBuildings ()** of MapsController.

##### Road Buffer Summary Information

-   This tool displays summary information of buildings and containments by road buffer boundary.

    \< -- CODE START -- \>

    \<a href="\#" id="buildingsroads_control" class="btn btn-default map-control"\>\<i class="fa-solid fa-building"\>\</i\>Road Buffer Summary Information\</a\>

    \< -- CODE END -- \>

-   Here, id value (buildingsroads_control) trigger the jQuery as

    \< -- CODE START -- \>

\$('\#buildingsroads_control').click(function (e) {

e.preventDefault();

disableAllControls();

\$('.map-control').removeClass('map-control-active');

if (currentControl == 'buildingsroads_control') {

currentControl = '';

} else {

currentControl = 'buildingsroads_control';

\$('\#buildingsroads_control').addClass('map-control-active');

map.on('pointermove', hoverOnRoadsHandler);

map.on('singleclick', displayRoadsBuildings);

}

}); \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“buildingsroads_control”).

-   The code checks if currentControl is equal to buildingsroads_control, if the condition is true than the variable "currentControl" is set to an empty string. Else variable "currentControl" is set to buildingsroads_control.

-   Adds the "map-control-active" class to the element with the ID “buildingsroads_control”

-   Finally, the function attaches two event handlers to the map object: 'pointermove' event is handled by the " hoverOnRoadsHandler " function, and 'singleclick' event is handled by the " displayRoadsBuildings " function. The functions " hoverOnRoadsHandler " and " “displayRoadsBuildings " are further explained in the below.

hoverOnRoadsHandler()

-   The function first checks if the event is currently being dragged, and if so, it exits the function.

-   Next, the function gets the pixel coordinates of the mouse event and uses the "forEachLayerAtPixel" method of the map object to check if the mouse is currently hovering over a layer with the name " roadlines_layer".

-   If it is, the function sets the cursor style to "pointer" to indicate that the feature is clickable. If the mouse is not hovering over a " roadlines_layer" feature, the cursor is set to an empty string, which defaults it back to its default style.

-   If the mouse is not being dragged, the function gets the pixel coordinates of the mouse pointer using the "getEventPixel" method, and then checks if the mouse pointer is over a layer on the map using the "forEachLayerAtPixel" method.

-   If the mouse is over a layer, the cursor is changed to a pointer, otherwise the cursor is set to an empty string, which makes it disappear.

displayRoadsBuildings()

-   displays buildings on selected road and buffer distance

-   It first checks if there are two vector layers named "selected_roads" and "roads_buildings", and if they exist, it clears the source of each layer.

-   If the layers don't exist, it creates them, sets their sources to be new empty vector sources, and styles the layers with blue lines of width 3.

-   It then retrieves the current resolution of the map view and sets up a WMS source with a URL and a specified layer.

-   It generates a GetFeatureInfo URL from the WMS source using the given coordinate, view resolution, and EPSG code.

-   It then makes an AJAX call to the URL and retrieves data in JSON format.

-   Here, modal shows the form to enter the buffer distance and based on the entered value data is populated. It also gives the features to export the populated data to excel.

-   For export, it calls **getRoadBuildings ()** of MapsController.

-   If the data returned is valid, it checks if there are any features in the data. If there is only one feature, it adds it to the "selected_roads" layer, sets the road code, longitude, latitude and clears the buffer distance value. It then opens a modal popup.

-   If there are more than one feature, it displays an error modal saying "More than One Roads Found, Please Zoom In or Select another Road".

-   If no features are found, it displays an error modal saying "Road Not Found".

-   It then removes the active state of a control, removes pointermove and singleclick event listeners, and sets the current control to an empty string.

##### Point Buffer Summary infromation

-   This tool displays summary information of buildings and containments by point buffer boundary.

    \< -- CODE START -- \>

    \<a href="\#" id="pointbuffer_control" class="btn btn-default map-control"\>\<i

    class="fa fa-building"\>\</i\>Point Buffer Summary Information\</a\>

    \< -- CODE END -- \>

-   Here, id value (pointbuffer_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#pointbuffer_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'pointbuffer_control') {

    currentControl = '';

    } else {

    currentControl = 'pointbuffer_control';

    \$('\#pointbuffer_control').addClass('map-control-active');

    map.on('singleclick', displayPopupPointBuffer);

    }

    }); \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“pointbuffer_control”).

-   The code checks if currentControl is equal to pointbuffer_control, if the condition is true than the variable "currentControl" is set to an empty string. Else variable "currentControl" is set to pointbuffer_control.

-   Adds the "map-control-active" class to the element with the ID “pointbuffer_control”

-   Finally, the function attache event handlers to the map object: 'singleclick' event is handled by the " displayPopupPointBuffer " function. The functions “displayPopupPointBuffer " are further explained in the below.

displayPopupPointBuffer()

-   Displays summary information of clicked point and given buffer distance.

-   The function starts by transforming the event's coordinate from EPSG:3857 to EPSG:4326 using the ol.proj.transform method.

-   The transformed longitude and latitude values are set as the values of the inputs with ids point-buffer-long and point-buffer-lat, respectively.

-   The original longitude and latitude values in EPSG:3857 are set as the values of the inputs with ids point-buffer-long-pos and point-buffer-lat-pos, respectively.

-   The pointbuffer_control class is removed from the control and the singleclick event is unregistered from the map.

-   The value of currentControl is set to an empty string.

-   The modal with id popup-point-buffer is shown.

-   Here, modal shows the form to enter the buffer distance and based on the entered value data is populated. It also gives the features to export the populated data to excel.

-   For export, it calls **getPointBufferBuildings()** of MapsController.

Initialize

-   Click event is invoked with passed id. When the element is clicked, the function is executed.

-   The function starts by calling e.preventDefault() to prevent the default behavior of the clicked element.

-   Then it calls disableAllControls(), this function disable all the controls in the page. The function is further explained in the supporting functions below.

-   Then it uses jQuery to remove the "map-control-active" class from all elements with the class "map-control".

##### Containments Emptied Information

-   This tool displays the monthly emptied containment chart of custom boundary entered by the user. If the user requires the information of monthly emptied containment of a certain area, the user can utilize this tool to generate the chart that aggregates all the records of containment from that particular area.

    \< -- CODE START -- \>

    \<a href="\#" id="containments_emptied_monthly" class="btn btn-default map-control"\>\<i

    class="fa fa-building"\>\</i\>Containments Emptied Information\</a\>

    \< -- CODE END -- \>

-   Here, id value (containments_emptied_monthly) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#containments_emptied_monthly').click(function (e) {

    e.preventDefault();

    disableAllControls();

    }); \< -- CODE END -- \>

-   Initial steps are explained below in initialize having id value (containments_emptied_monthly).

-   The code checks if currentControl is equal to containments_emptied_monthly, if the condition is true than the variable "currentControl" is set to an empty string. Else variable "currentControl" is set to containments_emptied_monthly.

-   Adds the "map-control-active" class to the element with the ID “containments_emptied_monthly”.

-   If the layer doesn't exist, it creates a new vector layer with an empty source and adds it to the map using the addExtraLayer() function.

-   Allows drawing of a polygon that will be stored in the containments_report_polygon vector layer.

-   Clears old features when a new drawing begins and removes popup location from the map.

-   Converts drawn polygon to WKT (Well-Known Text) format and transforms it to EPSG:4326.

-   Stores the polygon geometry in a hidden input for form access and shows a loading spinner.

-   Sends the polygon to the server via AJAX POST request along with CSRF token for security.

-   Builds a dynamic HTML structure including dropdown to choose a year.

-   Displays a bar chart with containment data.
-   Allows users to download the chart as a PNG image.
-   Updates chart data dynamically when the user selects a year from the dropdown.
-   Displays a message if no containment data is returned.
-   Removes loading spinner.
-   Shows popup at drawn polygon’s center.
-   Cleans up map interaction and resets control states.

##### Toilet Isochrone Map

-   This tool displays the concave polygon boundary that represents the area which is reachable within a given distance (speed \* time) (in meters) from a specific operational public toilet on a road network.

    \< -- CODE START -- \>

    \<a href="\#" id="toilet_isochrone_control" title="Generate isochrone information of CTPT by distance traversed (m)"> class="btn btn-default map-control"\>\<i

    class="fa fa-building"\>\</i\>Public Toilet Accessibility Tool\</a\>

    \< -- CODE END -- \>

-   Here, id value (toilet_isochrone_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#toilet_isochrone_control').click(function (e) { e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'toilet_isochrone_control') {

    currentControl = '';

    } else {

    currentControl = 'toilet_isochrone_control';

    \$('\#toilet_isochrone_control').addClass('map-control-active');
    \$('\#popup-toilet-isochrone').modal('show');

    }

    });

    \< --CODE END -- \>

-   Initial steps are explained below in initialize having id value (“toilet_isochrone_control”).

    -   The code checks if currentControl is equal to toilet_isochrone_control, if the condition is true than the variable "currentControl" is set to an empty string. Else variable "currentControl" is set to toilet_isochrone_control.

    -   Adds the "map-control-active" class to the element with the ID “toilet_isochrone_control”.

    -   Show the modal having ID "popup-toilet-isochrone" which allows the user to enter a Estimated Travel Time Distance (meter).

-   The submit button of form binds a click event handler to the HTML element with the id form-toilet-isochrone-map.

    -   Prevents the default form submission behavior .

    -   Clear existing data in the toilet_isochrone_polygon if it exists, or create a new layer if it doesn't. Create a new vector layer for toilet_isochrone_polygon and adds it using a custom function addExtraLayer().

-   Handles the buildings within the isochrone zone, just like the polygon layer.
-   Setup AJAX headers including CSRF token and passes distance as a query parameter. It calls ‘**getToiletIsochroneAreaLayers** function of ‘**MapsController**’.

-   Make an AJAX request to fetch isochrone polygons from nearby toilets.
-   On success, extracts the returned polygon(s) from the response and loops through array of geometries (in WKT format) and converted to the correct projection.

-   Applies dynamic styling via styleFunction(i) and adds the feature to the map layer.

-   Ensures relevant layers (e.g., toilets and roadlines) are made visible after drawing the isochrone.

-   Hides the modal form and removes the loading spinner.

-   If request fails, shows a generic error UI.

### Tools functions

##### Zoom In

-   The zoom in functionality allows the user to zoom in to a certain portion of the map.

-   Path: views/maps/index.blade.php

    \< -- CODE START --\>

    \<a href="\#" id="zoomin_control" class="btn btn-default map-control" datatoggle="tooltip"data-placement="bottom" title="Zoom In"\>\<i class="fa fa-search-plus fa-fw"\>\</i\>\</a\>

    \< -- CODE END --\>

-   Here, id value (zoomin_control) trigger the jQuery as

    \< -- CODE START --\>

    \$('\#zoomin_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    currentControl = '';

    if (map.getView().getZoom() \< map.getView().getMaxZoom()) {

    map.getView().setZoom(map.getView().getZoom() + 1);

    }});

    \< -- CODE END --\>

-   Initial steps are explained below in Initialize having id value (“zoomin_control”). The variable "currentControl" is set to an empty string.

-   Then the code checks if the current zoom level of the map is less than the maximum zoom level that is allowed for the map, if this condition is true, the code increases the zoom level of the map by one on each click event fired.

##### Zoom out

-   The zoom out functionality allows the user to zoom out to a certain portion of the map.

-   Path: views/maps/index.blade.php

\< -- CODE START --\>

\<a href="\#" id="zoomout_control" class="btn btn-default map-control" data-toggle="tooltip" data-placement="bottom" title="Zoom Out"\>\<i class="fa fa-search-minus fa-fw"\>\</i\>\</a\>

\< -- CODE END --\>

-   Here, id value (zoomout_control) trigger the jQuery as

\< -- CODE START --\>

\$('\#zoomout_control').click(function (e) {

e.preventDefault();

disableAllControls();

\$('.map-control').removeClass('map-control-active');

currentControl = '';

if (map.getView().getZoom() \> map.getView().getMinZoom()) {

map.getView().setZoom(map.getView().getZoom() - 1);

}

});

\< -- CODE END --\>

-   Initial steps are explained below in Initialize having id value (“zoomout_control”). The variable "currentControl" is set to an empty string.

    -   Then the code checks if the current zoom level of the map is greater than the minimum zoom level that is allowed for the map, if this condition is true, the code decreases the zoom level of the map by one on each click event fired.

##### Municipality

-   The municipality functionality allows the user to zoom out to a specified area

-   Path: views/maps/index.blade.php

    \< -- CODE START --\>

    \<a href="\#" id="zoomfull_control" class="btn btn-default map-control" data-toggle="tooltip" data-placement="bottom" title="Municipality"\>\<i class="fa fa-globe fa-fw"\>\</i\>\</a\>

    \< -- CODE END --\>

-   Here, id value (zoomfull_control) trigger the jQuery as

    \< -- CODE START --\>

    \$('\#zoomfull_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    currentControl = '';

    zoomToCity();

    });

    \< -- CODE END --\>

-   Initial steps are explained below in Initialize having id value (“zoomfull_control”). The variable "currentControl" is set to an empty string.

-   Then the code calls zoomToCity() explained in the supporting functions below

##### Info

-   Allows the user to select a layer and get information of the features of the selected layer such as: Containments, Building Structures and Point of Interest by hovering or clicking on the map.

-   Path: views/maps/index.blade.php

    \<-- CODE START -- \>

    \<a href="\#" id="identify_control" class="btn btn-default map-control" data-toggle="tooltip" data-placement="bottom" title="Info"\>\<i class="fa fa-info-circle fa-fw"\>\</i\>\</a\>

    \<-- CODE END -- \>

-   Here, id value (identify_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#identify_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    var html = '\<option value=""\>Select a layer\</option\>';

    \$.each(mLayer, function (key, value) {

    if (value.layer.getVisible()) {

    html += '\<option value="' + key + '"\>' + value.name + '\</option\>';

    } });

    \$('\#feature_info_overlay').html(html);

    \$('\#feature_info_overlay').val('');

    if (currentControl == 'identify_control') {

    \$('\#layer-select-box').hide();

    currentControl = '';

    } else {

    \$('\#layer-select-box').show();

    currentControl = 'identify_control';

    \$('\#identify_control').addClass('map-control-active');

    map.on('pointermove', hoverOnLayerHandler);

    map.on('singleclick', displayFeatureInformation);

    }});

    \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“identify_control”). The variable "currentControl" is set to an empty string.

-   The function then creates an empty variable 'html' and a loop that iterates over an object "mLayer". For each iterate on, if the "layer" property's "getVisible()" method returns true, the key and value's "name" property is added to the variable "html" as an option element in a select element.

-   The content of the HTML element with the ID "feature_info_overlay" is then set as the content of the variable "html". Then, the value of the element with the ID "feature_info_overlay" is set to an empty string.

-   The function then checks if the currentControl variable is equal to "identify_control". If it is, it hides the element with the ID "layer-select-box" and sets the currentControl variable to an empty string. If the currentControl variable is not equal to "identify_control", it shows the element with the ID "layer-select-box", sets the currentControl variable to "identify_control" and adds the "map-control-active" class to the element with the ID "identify_control".

-   Finally, the function attaches two event handlers to the map object: 'pointermove' event is handled by the "hoverOnLayerHandler" function, and 'singleclick' event is handled by the "displayFeatureInformation" function. The functions "hoverOnLayerHandler" and " “displayFeatureInformation " are further explained below:

hoverOnLayerHandler ()

-   When "hoverOnLayerHandler" triggered, the function checks if the mouse is currently being dragged and if so, the function exits and does nothing. If the mouse is not being dragged, the function gets the pixel coordinates of the mouse pointer using the "getEventPixel" method, and then checks if the mouse pointer is over a layer on the map using the "forEachLayerAtPixel" method. If the mouse is over a layer, the cursor is changed to a pointer, otherwise the cursor is set to an empty string, which makes it disappear.

displayFeatureInformation()

-   When displayFeatureInfromation is triggered, the function display the information about the selected features.

-   When the function is called, it retrieves the selected layer from a dropdown menu, gets the coordinates of the event, and sets up an ImageWMS source with the appropriate parameters.

-   The 'PROPERTYNAME' parameter is set based on the selected layer, which will determine the specific information that is returned.

-   The code then generates a URL for the getGetFeatureInfoUrl request and uses the Sweet Alert library to display an error message if the URL cannot be generated.

-   The code also uses JQuery to update the HTML content of an element with the id 'feature_info_content' with the information returned from the getGetFeatureInfoUrl request.

##### Coordinate Information

-   Displays the latitude and longitude of the clicked point on Map

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="coordinate_control" class="btn btn-default map-control" style="padding:6px 14px!important;" data-toggle="tooltip" data-placement="bottom" title="Coordinate Information"\>\<i class="fa fa-map-pin fa-fw"\>\</i\>\</a\>

    \< --CODE END -- \>

-   Here, id value (coordinate_control) trigger the jQuery as

    \< --CODE START -- \>

    \$('\#coordinate_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'coordinate_control') {

    currentControl = '';

    } else {

    currentControl = 'coordinate_control';

    \$('\#coordinate_control').addClass('map-control active');

    map.on('singleclick', displayCoordinateInformation);

    }});

    CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“coordinate_control”).

-   Then the code checks if the currentControl is equal to the coordinate_control, if this condition is true, the variable "currentControl" is set to an empty string else currentControl is set to value of coordinate_control, adds the "map-control-active" class to the element with the ID "coordinate_control" and 'singleclick' event is handled by the "displayFeatureInformation" function.

-   When displayCoordinateInformation is triggered, it displays the information about the coordinates and further information is explained below:

displayCoordinateInformation()

-   This code is displaying the coordinates of the event passed to the function in two different coordinate reference systems EPSG:3857 and EPSG:4326.

-   First, the code uses the ol.proj.transform method to convert the coordinates of the event from EPSG:3857 to EPSG:4326. The first argument passed to this method is the coordinate to be transformed.

-   Then the code creates a variable called html, which will store the HTML to be displayed in a popup. The code uses template literals to construct the HTML which contains table structure with rows and columns.

-   Finally, the code assigns the value of the html variable to the innerHTML property of an element called popupContent.

-   The function then uses the setPosition method of the popupOverlay object to set the position of the popup to the coordinates of the event.

##### Locate Point by Coordinate

-   Locate point on map by coordinates

-   Path: views/maps/index.blade.php

    \< -- CODE START --\>

    \<a href="\#" id="getpointbycoordinates_control" class="btn btn-default map-control" data-toggle="tooltip" data-placement="bottom" title="Locate Point by Coordinate"\>\<I class="fa fa-location-arrow" aria-hidden="true"\>\</i\>\</a\>

    \< --CODE End -- \>

-   Here, id value (getpointbycoordinates_control) trigger the jQuery as

    \< -- CODE START --\>

    \$('\#getpointbycoordinates_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    currentControl = '';

    \$('\#coordinate_search_modal').modal('show');

    });

    \< -- CODE END --\>

-   Initial steps are explained below in Initialize having id value (“getpointbycoordinates_control”). The variable "currentControl" is set to an empty string.

-   Activate the tool by selecting it, a modal popup fills in necessary longitude and latitude information a point that is of interest. The point is located on the map.

##### Measure Distance

-   This tool is useful if the user wants to measure distances of drawn polygon on the map.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="linemeasure_control" class="btn btn-default map-control" data-toggle="tooltip" data-placement="bottom" title="Measure Distance"\>\<i class="fa-solid fa-ruler"\>\</i\>\</a\>

    \< --CODE END -- \>

-   Here, id value (linemeasure_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#linemeasure_control').click(function (e) { e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'linemeasure_control') {

    currentControl = '';

    } else {

    currentControl = 'linemeasure_control';

    addMeasureControl('length');

    \$('\#linemeasure_control').addClass('map-control-active');

    }

    });

    \< --CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“linemeasure_control”).

    -   The code checks if currentControl is equal to linemeasure_control, if the condition is true than the variable "currentControl" is set to an empty string. Else variable "currentControl" is set to linemeasure_control.

    -   Then it sends parameter length to addMeasureControl and further information is explained in the supporting functions below.

    -   At last, adds the "map-control-active" class to the element with the ID " linemeasure_control".

##### Measure Area

-   This tool is useful if the user wants to measure areas of drawn polygon on the map.

-   Path: views/maps/index.blade.php

    \< --CODE START -- \>

    \<a href="\#" id="polymeasure_control" class="btn btn-default map-control" data-toggle="tooltip" data-placement="bottom" title="Measure Area"\>\<i class="fas fa-draw-polygon"\>\</i\>\</a\>

    \< -- CODE END --\>

-   Here, id value (polymeasure_control) trigger the jQuery as

    \< -- CODE START --\>

    \$('\#polymeasure_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'polymeasure_control') {

    currentControl = '';

    } else {

    currentControl = 'polymeasure_control';

    addMeasureControl('area');

    \$('\#polymeasure_control').addClass('map-control-active');

    }});

    \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“polymeasure_control”). The code checks if currentControl is equal to polymeasure_control, if the condition is true than the variable "currentControl" is set to an empty string.

-   Then it sends parameter area to addMeasureControl and further information is explained in the supporting functions below.

-   At last, adds the "map-control-active" class to the element with the ID " polymeasure_control".

##### Print

-   Prints the visible area of the map

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="print_control" class="btn btn-default map-control" data-toggle="tooltip"data-placement="bottom" title="Print"\>\<I class="fa fa-print fa-fw"\>\</i\>\</a

    \< -- CODE END -- \>

-   Here, id value (print_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#print_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    .. . ..

    .... .

    ..... .

    }

    \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“print_control”) and sets the currentControl variable to an empty string. It then retrieves the values of several input fields, including "print_map_title", "print_map_description", "print_paper_size", "print_dpi", and "box_orientation" and stores them in variables.

-   Then, it shows a modal window with id 'print_modal' when the button is clicked. This modal window allows the user to enter a title and a description for the print map. The code also includes a line that makes the modal window non-draggable.

##### Help

-   Displays the Help page.

-   Path: views/maps/index.blade.php

    \<-- CODE START -- \>

    \<a target="\_blank" href="{{ asset('pdf/tools-help.pdf') }}" class="btn btn-default map-control" data-toggle="tooltip" data-placement="bottom" title="Help"\>\<i class="fa-solid fa-file"\>\</i\>\</a\>

    \< -- CODE END -- \>

-   This code is an anchor tag that creates a link to a pdf file called "tools-help.pdf" that is located in the "asset" directory.

##### Find Nearest Road

-   Displays nearest road to the clicked point

-   Path: views/maps/index.blade.php

    \< -- CODE START --\>

    \<a href="\#" id="nearestroad_control" class="btn btn-default map-control" data-toggle="tooltip"data-placement="bottom" title="Find Nearest Road"\>\<i class="fa fa-road fa-fw"\>\</i\>\</a\>

    CODE END -- \>

-   Here, id value (nearestroad_control) trigger the jQuery as

    \< --CODE START -- \>

    \$('\#nearestroad_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'nearestroad_control') {

    currentControl = '';

    } else {

    currentControl = 'nearestroad_control';

    \$('\#nearestroad_control').addClass('map-control-active');

    map.on('singleclick', findNearestRoad);

    }

    });

    \< -- CODE END --\>

-   Initial steps are explained below in Initialize having id value (“nearestroad_control”).

-   The code checks if currentControl is equal to nearestroad_control, if the condition is true than the variable "currentControl" is set to an empty string. Else variable "currentControl" is set to nearestroad_control.

-   Adds the "map-control-active" class to the element with the ID “nearestroad control”

-   'singleclick' event is handled by the " findNearestRoad " function.

-   When findNearestRoad is triggered, it displays the marker to the nearest road and further information is explained below:

findNearestRoad()

-   When "findNearestRoad" is triggered an event (evt) occurs on a map. The event's coordinates are transformed from EPSG:3857 to EPSG:4326, and the longitude and latitude values are stored in the variables "long" and "lat" respectively.

-   The function then checks if there are any existing layers on the map called "nearest_road_markers" and "nearest_road_line", and if so, it clears their sources. If these layers do not exist, it creates new vector layers and adds them to the map.

-   Next, it creates a marker feature at the event's coordinates and adds it to the "nearest_road_markers" layer, along with a blue marker icon.

-   The function then makes an AJAX call to a specified URL ({{ url("maps/nearest-road") }} + '/' + long + '/' + lat), passing the event coordinates. Upon receiving a successful response, it adds a marker and a line to the "nearest_road_markers" and "nearest_road_line" layers, respectively, showing the nearest road to the clicked location. The given URL belongs to ‘**getNearestRoad’** function of ‘**MapsController’**.

-   It also displays an ajax loader until the response is received and removes it after that. Finally, it calls the function showLayer() which makes the layers visible on the map.

##### Find buildings connected to containments

-   Displays Buildings connected to Containments

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="containmentbuilding_control" class="btn btn-default map-control" data-toggle="tooltip" data-placement="bottom" title="Find Buildings to Containment"\>\<i class="fa fa-archive fa-fw" aria-hidden="true"\>\</i\>\</a\>

    \< -- CODE END --\>

-   Here, id value (containmentbuilding_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#containmentbuilding_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'containmentbuilding_control') {

    currentControl = '';

    } else {

    currentControl = 'containmentbuilding_control';

    \$('\#containmentbuilding_control').addClass('map-control-active');

    map.on('pointermove', hoverOnContainmentHandler);

    map.on('singleclick', displayContainmentToBuildings);

    }

    });

    \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“containmentbuilding_control”).

-   The code checks if currentControl is equal to containmentbuilding_control, if the condition is true than the variable "currentControl" is set to an empty string. Else variable "currentControl" is set to containmentbuilding_control.

-   Adds the "map-control-active" class to the element with the ID “containmentbuilding_control”

-   Finally, the function attaches two event handlers to the map object: 'pointermove' event is handled by the " hoverOnContainmentHandler " function, and 'singleclick' event is handled by the " displayContainmentToBuildings " function. The functions " hoverOnContainmentHandler " and " “displayContainmentToBuildings " are further explained below:

hoverOnContainmentHandler()

-   The function first checks if the event is currently being dragged, and if so, it exits the function.

-   Next, the function gets the pixel coordinates of the mouse event and uses the "forEachLayerAtPixel" method of the map object to check if the mouse is currently hovering over a layer with the name "containments_layer".

-   If it is, the function sets the cursor style to "pointer" to indicate that the feature is clickable. If the mouse is not hovering over a "containments_layer" feature, the cursor is set to an empty string, which defaults it back to its default style.

-   If the mouse is not being dragged, the function gets the pixel coordinates of the mouse pointer using the "getEventPixel" method, and then checks if the mouse pointer is over a layer on the map using the "forEachLayerAtPixel" method.

-   If the mouse is over a layer, the cursor is changed to a pointer, otherwise the cursor is set to an empty string, which makes it disappear.

displayContainmentToBuildings()

-   This code is creating a function that is displaying the buildings within a selected containment area.

-   It first checks if a layer for the selected containment area (eLayer.selected_containment) already exists and if it does, it clears it. If it does not exist, it creates a new layer for the selected containment area.

-   The layer is a vector layer with a specific style that includes an icon. It also adds this new layer to a collection of extra layers for the map.

-   Then it checks if a layer for the buildings within the selected containment area (eLayer.containment_buildings) already exists. If it does, it will proceed to display the buildings within the selected containment area. If not, it will not proceed with displaying the buildings. It calls the ‘**getBuildingToContainment**’ function of ‘**MapsController**’.

##### Find Containments connected to Buildings

-   Finds Containments connected to Buildings

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="buildingcontainment_control" class="btn btn-default map-control" data-toggle="tooltip" data-placement="bottom" title="Find Containments to Building"\>\<i class="fa fa-building fa-fw" aria-hidden="true"\>\</i\>\</a\>

    \< -- CODE END --\>

-   Here, id value (buildingcontainment_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#buildingcontainment_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'buildingcontainment_control') {

    currentControl = '';

    } else {

    currentControl = 'buildingcontainment_control';

    \$('\#buildingcontainment_control').addClass('map

    control-active');

    map.on('pointermove', hoverOnBuildingContainmentHandler);

    map.on('singleclick', displayBuildingToContainment);

    }});

    \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“buildingcontainment_control”).

-   The code checks if currentControl is equal to buildingcontainment_control, if the condition is true than the variable "currentControl" is set to an empty string. Else variable "currentControl" is set to buildingcontainment_control.

-   Adds the "map-control-active" class to the element with the ID “buildingcontainment_control”

-   Finally, the function attaches two event handlers to the map object: 'pointermove' event is handled by the " hoverOnBuildingContainmentHandler " function, and 'singleclick' event is handled by the " displayBuildingToContainment " function. The functions " hoverOnBuildingContainmentHandler " is further explained in the supporting functions below and " “displayBuildingToContainment " is explained below

displayBuildingToContainment()

-   This code display building containment information on a map.

-   The code first checks if a building has been selected, and if so, it clears the existing building from the map. It then creates a new Vector layer to display the selected building and adds this layer to the map.

-   Next, it does the same for building containments and creates a new Vector layer for this purpose. The code then uses an ImageWMS source to retrieve data for the selected building from a WMS service using the specified URL.

-   If the data is successfully retrieved, it makes an additional AJAX call to retrieve containment information for the building and adds this information to the map. It calls the ‘**getContainmentToBuilding**’ function of ‘**MapsController**’.

-   Markers are also added to the map for each containment point.

##### Find Associated buildings

-   This tool allows the user to identify all associated such buildings of the main building.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="associatedtomain_control" class="btn btn-default map-control"data-toggle="tooltip" data-placement="bottom" title="Find Associated Buildings"\>\<i class="fa-solid fa-building-circle-arrow-right"\>\</i\>\</a\>

    \<--CODE End -- \>

-   Here, id value (associatedtomain_control) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#associatedtomain_control').click(function (e) {

    e.preventDefault();

    disableAllControls();

    \$('.map-control').removeClass('map-control-active');

    if (currentControl == 'associatedtomain_control') {

    currentControl = '';

    } else {

    currentControl = 'associatedtomain_control';

    \$('\#associatedtomain_control').addClass('map-control-active');

    map.on('pointermove', hoverOnBuildingContainmentHandler);

    map.on('singleclick', displayAssociatedToMainBuilding)}});

    \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“associatedtomain_control”).

-   The code checks if currentControl is equal to associatedtomain_control, if the condition is true than the variable "currentControl" is set to an empty string. Else variable "currentControl" is set to associatedtomain_control.

-   Adds the "map-control-active" class to the element with the ID “associatedtomain_control”

-   Finally, the function attaches two event handlers to the map object: 'pointermove' event is handled by the " hoverOnBuildingContainmentHandler " function, and 'singleclick' event is handled by the " displayAssociatedToMainBuilding " function.

-   The functions " hoverOnBuildingContainmentHandler " is further explained in the supporting functions below and " “displayAssociatedToMainBuilding" are explained below.

displayAssociatedToMainBuilding()

-   This code display containment associated to building information on a map.

-   The code first checks if a building has been selected, and if so, it clears the existing building from the map. It then creates a new Vector layer to display the selected building and adds this layer to the map.

-   Next, it does the same for containments associated tpo main building and creates a new Vector layer for this purpose. The code then uses an ImageWMS source to retrieve data for the selected building from a WMS service using the specified URL.

-   If the data is successfully retrieved, it makes an additional AJAX call to retrieve containment information for the building and adds this information to the map. It calls the ‘**getAssociatedToMainbuilding**’ function of ‘**MapsController**’.

-   Markers are also added to the map for each containment point.

##### Import From WMS

-   This tool is used for importing layer from WMS URL and displaying the selected layer on map.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="wms_layer" class="btn btn-default map-control" data-toggle="tooltip"data-placement="bottom" title="Import from WMS"\>\<i class="as fa-layer-group"\>\</i\>\</a\> \< -- CODE END -- \>

-   Here, id value (wms_layer) trigger the jQuery as

\< -- CODE START -- \>

\$('\#wms_layer').click(function (e) {

e.preventDefault();

disableAllControls();

\$('.map-control').removeClass('map-control-active');

currentControl = '';

\$("#wmsModal").modal('show'); }); \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (wms_layer).

-   The variable "currentControl" is set to an empty string.

-   Opens the WMS modal where the user can configure and add WMS layers.

-   Show the 'wmsModal' which allows the user to enter a WMS URL.

-   After submitting the input, it retrieves all available layers from the URL.

-   When the retrieve WMS layers button is clicked, a loading effect is displayed.

-   The code takes the WMS URL entered by the user and sends a request to get its GetCapabilities XML.

-   After getting the response, it closes the input modal.

-   The capabilities XML is parsed and names of all available layers are obtained.

-   These layer names are stored in an array and displayed in a dropdown inside another modal.

-   The WMS and related WFS base URLs are created by modifying the user-entered URL.

-   If the user selects a layer from the dropdown, the selected layer name and WMS URL are retrieved.

-   The code extracts the workspace name from the WMS URL.

-   Request is made to the WFS endpoint to get the municipality boundary polygon (city polygon layer).

-   City boundary geometry is parsed and stored.

-   Request is made to the WFS service to import features of the target layer.

-   All features of the targeted layer are queried for whether any of them spatially overlap the city boundary or not.

-   In case of no overlap, an error alert is displayed.

-   If an intersection is correct, a new WMS layer is constructed and overlaid on the map, showing the chosen data.

##### Locate Me

-   This tool is used for importing layer from WMS URL and displaying the selected layer on map.

-   Path: views/maps/index.blade.php

```sh
    \< -- CODE START -- \>

    \<a href="\#" id="get_location" class="btn btn-default map-control" data-toggle="tooltip"data-placement="bottom" title="Locate Me"\>\<img src="{{ asset('img/locate_me.png')}}" style="height:20px;"alt="Location Icon">\</a\> \< -- CODE END -- \>
```

-   Here, id value (get_location) trigger the jQuery as

```sh
\< -- CODE START -- \>

\$('\#get_location').click(function (e) {

e.preventDefault();

disableAllControls();

\$('.map-control').removeClass('map-control-active');

currentControl = '';


if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(showLoc, errHand);
                } \< -- CODE END -- \>
```

-   Initial steps are explained below in Initialize having id value (get_location).

-   The variable "currentControl" is set to an empty string.

-   Checks if the browser supports geolocation.

    -   If supported, it calls getCurrentPosition():

    -   If successful, it runs showLoc() which is explained below .

    -   If it fails, it runs errHand() which is explained below.

**_showLoc()_**

-   Gets the latitude and longitude of the user's current position.

-   Setup AJAX headers including CSRF token and content type. It calls ‘**checkLocationWithinBoundary**’ function of ‘**MapsController**’.

    -   Make an AJAX request to check whether a given point (latitude & longitude) lies within a municipality boundary.

    -   On success, it returns the intersected feature and display it on the map.

-   If not, it shows an error message modal.

**_errHand()_**

-   The errHand function is defined to handle geolocation errors.

-   It uses a switch statement to handle different error codes returned by the Geolocation API, and displays corresponding messages based on each case:

    -   PERMISSION_DENIED : Displays a message when the user denies location access permission.

    -   POSITION_UNAVAILABLE: Displays a message if the device’s location cannot be determined.

    -   TIMEOUT: Displays a message when the request to get the location takes too long and times out.

    -   UNKNOWN_ERROR: Displays a message for any other unknown location error.

##### KML Viewer

The KML Viewer is a map-index tool available from `resources\\views\\maps\\index.blade.php`. It allows a user to select a KML file for visualization and municipality-based analysis.

-   The **KML Viewer** control opens the file picker for selecting a KML file.
-   OpenLayers parses the file with `ol.format.KML` and converts the features for display on the map.
-   Files with no features, invalid KML, or more than 1,000 features are rejected with a warning.
-   The feature geometries are sent to `POST /maps/check-geometry` for municipality-intersection validation.
-   Features that intersect the municipality are rendered in the KML layer and the map zooms to their extent. Non-intersecting features are reported to the user.
-   Polygon features are sent to `POST /maps/get-kml-summary-info`; matching buildings are displayed in a separate layer and summary information is shown in a popup.
-   The KML geometry can be submitted through `POST /maps/get-kml-info-report-csv` for the related report/export workflow.
-   Map service requests use the authenticated `/gis/wms` and `/gis/wfs` proxy routes; GeoServer URLs and authentication keys are not exposed in the browser.

###### KML Summary Report

The KML Viewer can generate a multi-sheet Excel summary report through `MapsController@getKmlInfoReportCsv()` at `POST /maps/get-kml-info-report-csv`. The report is generated by `SummaryInfoMultiSheetKmlExport`, which includes `BuildContainKmlExport`.

The `BuildContainKmlExport` sheet is titled **Build-Contain List** and contains the following columns:

-   Polygon Name
-   BIN
-   Containment ID

The sheet links buildings found within the uploaded polygon areas to their associated containment records. The report also includes the other KML summary sheets generated by `SummaryInfoMultiSheetKmlExport`.

##### Remove Markers

-   This tool is used for removing such Markers.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<a href="\#" id="removemarkers_control" class="btn btn-default map-control" data-toggle="tooltip"data-placement="bottom" title="Remove Markers"\>\<i class="fa fa-trash fa-fw"\>\</i\>\</a\> \< -- CODE END -- \>

-   Here, id value (removemarkers_control) trigger the jQuery as

\< -- CODE START -- \>

\$('\#removemarkers_control').click(function (e) {

e.preventDefault();

disableAllControls();

\$('.map-control').removeClass('map-control-active');

currentControl = '';

\$.each(eLayer, function (key, value) {

value.layer.getSource().clear();

});

map.removeOverlay(staticMeasureTooltip); }); \< -- CODE END -- \>

-   Initial steps are explained below in Initialize having id value (“removemarkers_control”).

-   The variable "currentControl" is set to an empty string and loop through each element in the eLayer array.

-   For each iteration of the loop, it accesses the layer property of the value object and calls the clear method of its source. This effectively removes all features from the layer's source.

-   Finally, it calls the removeOverlay method of the map object and passes the staticMeasureTooltip object as an argument. This removes the overlay from the map.

##### Ward

-   This tool allows the user to add search result markers on the map, and the code is executed when a user interacts with the filterward_select dropdown.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<select class="form-control" id="filterward_select" name="ward" style="min-width:50px;"\>

    \<option value=""\>All Wards\</option\>

    @foreach(\$pickWardResults as \$unique)

    \<option value= "{{ \$unique-\>ward }}" \> {{ \$unique-\>ward }}\</option\>

    @endforeach \</select\> \< -- CODE END -- \>

-   In above mentioned code, it creates a drop-down select element with the ID "filterward_select" and name "ward" and includes a default "All Wards" option. The code then uses a foreach loop to iterate through a variable called \$pickWardResults. For each iteration, the code creates an option element with the value of the "ward" property of the current object in the iteration, and the inner text of the option element is set to the value of "ward" property of the current object in the iteration.

-   Here, id value (filterward_select) trigger the jQuery as

    \< -- CODE START -- \>

    \$('\#filterward_select').on('change', function(e) {

    ……

    … } \< -- CODE END -- \>

-   Click event is invoked with the id “filterward_select ". When the element is clicked, the function is executed.

-   The function starts by calling e.preventDefault() to prevent the default behavior of the clicked element and disableAllControls(), this function disable all the controls in the page. The function is further explained in the supporting functions below.

-   Then, retrieves the current value of the "filterward_select" element from a drop-down select element.

-   It sends an AJAX request to a URL (maps/clipward/ward-center-coordinates) to retrieve center coordinates for the selected ward. It calls ‘**getClipWardCenterCoordinates’** function of ‘**MapsController**’.

-   Upon success, it parses the received data, creates an OpenLayers feature from it, and sets a custom style for the feature.

-   It adds the feature to the searchResultMarkers layer.

-   It calls a function named handleZoomToExtent to handle zooming to the extent of the ward overlay. Upon completion of this function, it removes any AJAX loader.

-   If the AJAX request fails, it calls a function named displayAjaxError() to handle and display the error.

##### Search

-   This tool allows the user to search a layer and mark the selected layer on map such as: House Number, Places and Road.

-   Path: views/maps/index.blade.php

    \< -- CODE START -- \>

    \<form class="form-inline" name="building_search_form" id="building_search_form"\>

    \<div class="form-row"\>

    \<input type="text" class="form-control" id="building_value_text" style="min-width:30% !important;" /\>\</div\>

    \<div class="form-row"\>

    \<select class="form-control" id="building_field_select"\>

    \<option value="bin"\>House Number\</option\>

    \<option value="places_layer"\>Places\</option\>

    \<option value="roadlines_layer"\>Roads\</option\>\</select\>\</div\>

    \<button class="btn btn-default" type="submit"\>\<i class="fa fa-search fa-fw"\>\</i\>\</button\>\</form\>

    • Here, id value (building_search_form) trigger the jQuery as

    \$('\#building_search_form').submit(function () {

    .. … .

    .. .. … .

    } \< -- CODE END -- \>

-   It retrieves the values of the form input fields, building_value_text and building_field_select, and stores them in the variables val and field, respectively. It also retrieves the text of the selected option in building_field_select and stores it in the variable field_text.

-   It checks if the value of val is empty. If it is, it displays a warning message which instructs the user to enter the value of field_text.

-   If val is not empty, it checks if field is equal to 'places_layer' or 'roadlines_layer'. If it is, it calls the findPlacesRoads function and passes val and field as arguments.

-   If field is not equal to 'places_layer' or 'roadlines_layer', it performs the following actions:

    -   It checks if the searchResultBuilding property of the eLayer object exists. If it does, it calls the clear method of its source to remove all features.

    -   If searchResultBuilding does not exist, it creates a new vector layer and adds it to the map using the addExtraLayer function.

    -   It displays an AJAX loader to indicate that a search is in progress.

    -   It makes an AJAX request to the URL '{{ url("maps/search-building") }}' + '/' + field + '/' + val using the \$.ajax method. The request is a GET request and it passes a CSRF token in the data. It calls ‘**searchBuilding’** function of ‘**MapsController**’.

    -   If the request is successful, it checks if the data returned is an array. If it is, it loops through the array and adds each feature to the searchResultBuilding layer. It also sets the style of each feature based on a value in the data.

    -   If the data returned is not an array, it displays an error message.

    -   If the request is not successful, it displays an error message.

-   The function returns false to prevent the form from being submitted.

### Supporting Functions

**csvdata()**

-   The function csvdata takes three parameters:

    -   outputFormat: The format in which to export the data (csv, kml, or shp).

    -   selectedLayer: The specific layer for which data is being exported.

    -   exportLink: The URL for the WFS service request to fetch the data.

-   An object layerFilenames maps specific layer names to more user-friendly filenames.

-   Another object propertyNames maps layer names to a string of property names (fields) relevant for each layer.

-   Determines the filename to use for the export. It uses layerFilenames if available; otherwise, it uses selectedLayer directly.

-   A helper function setExportLink updates the exportLink to include the desired output format and optionally the file extension. It then opens this link in a new window.

-   The switch statement is used to execute different code blocks based on the value of outputFormat.

-   outputFormat.toLowerCase() ensures that the outputFormat is compared in lowercase, making the check case-insensitive.

-   Retrieves the property names for the selected layer from propertyNames.

-   Updates the exportLink to specify CSV as the format and includes the propertyName if available.

-   Uses the fetch API to retrieve the CSV data from the exportLink.

-   Processes the CSV data with transformCSVAfterDownload (csvData) which is explained below.

-   Creates a Blob from the transformed CSV data and triggers a download using a temporary anchor element.

**transformCSVAfterDownload()**

-   The function transformCSVAfterDownload takes a single argument csvData, which is a string containing the CSV data to be transformed.

-   Replaces original CSV column headers with more readable names using the nameMapping object.

-   Splits the CSV data into lines, processes the header line for mapping, and reassembles the data lines.

-   Joins the transformed lines into a single CSV string and returns it.

**disableAllControls()**

-   disables all controls on an OpenLayers map. Overall, this function is used to reset the map to its original state and remove any previous actions performed on it.

-   The function first removes any interactions (draw, drag) and event listeners (pointermove, singleclick) that have been added to the map.

-   It then removes any overlay tooltips (measure, report_polygon, export_polygon, export_tax_polygon) and hides a "layer-select-box" element.

-   Finally, it clears any features that may have been added to the map layers (measure, report_polygon, export_polygon, export_tax_polygon).

**addMeasureControl()**

-   The function takes one parameter, "measureType", which determines whether the measure control is for measuring length or area.

-   It first sets the type of geometry based on measureType, it then listens to pointermove and mouseout events and if the helpTooltipElement is present it removes the hidden class. It then checks if a measure layer is already present, if not it creates a new measure layer and adds it to the map.

-   It then creates a new "Draw" interaction and adds it to the map, this allows the user to draw on the map.

-   It also creates two tooltips, one for measuring and one for help. When the user starts drawing, the measure layer is cleared and the measure tooltip is removed. When the user is drawing, the measure tooltip is updated with the current measurement, and when the user stops drawing, the measure tooltip is set to be static and the measure layer is made visible.

**hoverOnBuildingContainmentHandler()**

-   The function first checks if the event is currently being dragged, and if so, it exits the function.

-   Next, the function gets the pixel coordinates of the mouse event and uses the "forEachLayerAtPixel" method of the map object to check if the mouse is currently hovering over a layer with the name " buildings_layer".

-   If it is, the function sets the cursor style to "pointer" to indicate that the feature is clickable. If the mouse is not hovering over a " buildings_layer" feature, the cursor is set to an empty string, which defaults it back to its default style.

-   If the mouse is not being dragged, the function gets the pixel coordinates of the mouse pointer using the "getEventPixel" method, and then checks if the mouse pointer is over a layer on the map using the "forEachLayerAtPixel" method.

-   If the mouse is over a layer, the cursor is changed to a pointer, otherwise the cursor is set to an empty string, which makes it disappear.

**zoomToCity()**

-   The map's view is set to a specific longitude and latitude (85.373130, 27.636114) using the ol.proj.transform method. The map's zoom level is then set to 12. When this function is called, the map will zoom to the specified city.

**hoverOnDrainHandler()**

-   The function first checks if the event is currently being dragged, and if so, it exits the function.

-   Next, the function gets the pixel coordinates of the mouse event and uses the "forEachLayerAtPixel" method of the map object to check if the mouse is currently hovering over a layer with the name " sewerlines_layer ".

-   If it is, the function sets the cursor style to "pointer" to indicate that the feature is clickable. If the mouse is not hovering over a " sewerlines_layer " feature, the cursor is set to an empty string, which defaults it back to its default style.

-   If the mouse is not being dragged, the function gets the pixel coordinates of the mouse pointer using the "getEventPixel" method, and then checks if the mouse pointer is over a layer on the map using the "forEachLayerAtPixel" method.

-   if the mouse is over a layer, the cursor is changed to a pointer, otherwise the cursor is set to an empty string, which makes it disappear.

**updateAllCQLFiltersParams()**

-   The function "updateAllCQLFiltersParams" updates the CQL filters for all the map layers stored in the "mLayer" object.

-   The function loops through each layer in the "mLayer" object using the "\$.each" method and calls the "updateCQLFilterParams" function for each layer with the "key" as the argument.

-   The "updateCQLFilterParams" function updates the CQL filter for the layer based on the given key.

**updateCQLFilterParams()**

-   The updateCQLFilterParams function updates the CQL (Common Query Language) filter parameters for a specified map layer.

-   It takes the layer name as an input parameter. The function retrieves the mLayer object for the specified layer and retrieves the filters property.

-   For each filter in the filters property, the function checks if a corresponding filter object exists in the mFilter object. If it does, the filter is added to an array of cqlFilters.

-   Once all filters have been processed, the function creates a cql_filter string by joining all filters in the cqlFilters array using the AND operator. If there are no filters, the cql_filter string is set to 'deleted_at is null'.

-   Finally, the function updates the source parameters of the layer with the CQL_FILTER parameter set to the cql_filter string.

**hoverOnRoadsHandler()**

-   Inside the function, it checks if the map is being dragged (evt.dragging). If the map is being dragged, the function returns early and does nothing. This prevents unnecessary processing when the map is being interactively moved by the user.

-   It retrieves the pixel coordinates of the event (evt.originalEvent) relative to the map using map.getEventPixel(evt.originalEvent).

-   It then checks if there are any layers at the pixel coordinates using map.forEachLayerAtPixel(pixel, function (layer) { ... }). It iterates through all layers at that pixel, and if it finds a layer with the name 'roads_width_zoom_layer', it returns true.

-   Based on whether there is a hit (a layer with the name 'roads_width_zoom_layer'), it changes the cursor style of the map's target element (map.getTargetElement().style.cursor). If there is a hit, it sets the cursor to 'pointer', indicating to the user that there is something clickable under the cursor. Otherwise, it sets the cursor to the default style.

**MapController**

Controllers stored in app\\Http\\Controllers\\MapsController.php

| **Function**    | \_\_construct()                                                        |
| --------------- | ---------------------------------------------------------------------- |
| **Description** | Initializes authentication, permissions and the service class instance |
| **Parameters**  | Service class instance(Excel, MapsService)                             |
| **Return**      | null                                                                   |
| **Source**      | app\\Http\\Controllers\\MapsController.php                             |

| **Function**    | index ()                                                                     |
| --------------- | ---------------------------------------------------------------------------- |
| **Description** | Returns the index.blade.php page with dropdown values fetched from database. |
| **Parameters**  | null                                                                         |
| **Return**      | maps.index                                                                   |
| **Source**      | app\\Http\\Controllers\\MapsController.php                                   |

| **Function**    | getBufferPolygonReportCSV ()                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the class that handles the data about buffer polygon that is stored in the server.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| **Parameters**  | Null                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| **Return**      | CSV files containing data obtainerd from: new SummaryInfoMultiSheetExport(request()-\>buffer_polygon_geom, request()-\>buffer_polygon_distance)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| **Source**      | app\\Http\\Controllers\\MapsController.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| **Remarks**     | SummaryInfoMultiSheetExport: The Excel file is generated using this class, passing the buffer polygon geometry and distance obtained from the request parameters. In this case, it returns an array containing instances of other export classes (BuildingsExport, BuildingsListExport, and ContainmentsListExport), passing the buffer polygon geometry and distance to each of them. BuildingsExport: This class encapsulates the logic to export building data from SQL query using the provided buffer polygon geometry data and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. BuildingsListExport: This class encapsulates the logic to export a list of buildings data from SQL query using the provided buffer polygon geometry data and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. ContainmentsListExport: This class encapsulates the logic to export a list of containments data from SQL query using the provided buffer polygon geometry data and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. |

| **Function**    | getWaterBodyReportCsv ()                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Description** | Calls the class that handles the data about buffer water body polygon that is stored in the server.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| **Parameters**  | Null                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| **Return**      | CSV files containing data obtained from: new SummaryInfoMultiSheetExport(\$waterbody[0]-\>geom, \$bufferDisancePolygon)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| **Source**      | app\\Http\\Controllers\\MapsController.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| **Remarks**     | A SQL query is constructed to retrieve the geometry of the water body from the database table layer_info.waterbodys based on the provided wb_code. Execute the constructed SQL query using Laravel's DB facade to fetch the geometry of the waterbody. Check if a buffer distance is provided in the request (wb_distance). If a valid distance is provided, set bufferDisancePolygon to that value. Otherwise, set it to 0. Instantiate a SummaryInfoMultiSheetExport export class with the water body geometry and buffer distance parameters. In this case, it returns an array containing instances of other export classes (BuildingsExport, BuildingsListExport, and ContainmentsListExport), passing the buffer polygon geometry and distance to each of them. BuildingsExport: This class encapsulates the logic to export building data from SQL query using the provided waterbody geometry data and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. BuildingsListExport: This class encapsulates the logic to export a list of buildings data from SQL query using the provided waterbody geometry data and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. ContainmentsListExport: This class encapsulates the logic to export a list of containments data from SQL query using the provided waterbody geometry data and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. |

| **Function**    | getWardBuildingsReportCsv()                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the class that handles the data about ward building that is stored in the server.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| **Parameters**  | Null                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| **Return**      | CSV files containing data from: new SummaryInfoMultiSheetExport(\$rowGeom, 0)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| **Source**      | app\\Http\\Controllers\\MapsController.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| **Remarks**     | A SQL query is constructed to retrieve the geometry data of ward from the database table layer_info.wards based on a specific ward number obtained from the request parameter ward_building_no. The geometry data (in WKT format) from the first row of the result set is retrieved and stored in the variable \$rowGeom. Instantiate a SummaryInfoMultiSheetExport export class with the ward geometry and 0 parameters. In this case, it returns an array containing instances of other export classes (BuildingsExport, BuildingsListExport, and ContainmentsListExport), passing the polygon geometry. BuildingsExport: This class encapsulates the logic to export building data from SQL query using the provided ward geometry data to an Excel sheet, apply some styling, and provide a title for the sheet. BuildingsListExport: This class encapsulates the logic to export a list of buildings data from SQL query using the provided ward geometry data to an Excel sheet, apply some styling, and provide a title for the sheet.ContainmentsListExport: This class encapsulates the logic to export a list of containments data from SQL query using the provided ward geometry data to an Excel sheet, apply some styling, and provide a title for the sheet. |

| **Function**    | getRoadBuildingsReportCsv()                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the class that handles the data about road to building that is stored in the server.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| **Parameters**  | Null                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| **Return**      | CSV files containing data from: new SummaryInfoMultiSheetExport(\$rowGeom, \$bufferDisancePolygon)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| **Source**      | app\\Http\\Controllers\\MapsController.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| **Remarks**     | A SQL query is constructed to retrieve the geometry data of road from the database table utility_info.roads based on a specific road code obtained from the request parameter road_code. The geometry data (in WKT format) from the first row of the result set is retrieved and stored in the variable \$row. Check if a buffer distance is provided in the request (rb_distance). If a valid distance is provided, set bufferDisancePolygon to that value. Otherwise, set it to 0. Instantiate a SummaryInfoMultiSheetExport export class with the road geometry and buffer distance parameters. In this case, it returns an array containing instances of other export classes (BuildingsExport, BuildingsListExport, and ContainmentsListExport), passing the buffer polygon geometry and distance to each of them. BuildingsExport: This class encapsulates the logic to export building data from SQL query using the provided road geometry data, and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. BuildingsListExport: This class encapsulates the logic to export a list of buildings data from SQL query using the provided road geometry data, and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. ContainmentsListExport: This class encapsulates the logic to export a list of containments data from SQL query using the provided road geometry data, and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. |

| **Function**    | getPointBuildingReportCSV ()                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Description** | Calls the class that handles the data about buffer point that is stored in the server.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| **Parameters**  | Null                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| **Return**      | CSV files containing data from: new PointBuildingsSummaryInfoMultiSheetExport(request()-\>PTB_long, request()-\>PTB_lat, request()-\>PTB_distance)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| **Source**      | app\\Http\\Controllers\\MapsController.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Remarks**     | PointBuildingsSummaryInfoMultiSheetExport: The Excel file is generated using this class, passing the latitude, longitude and buffer distance obtained from the request parameters. In this case, it returns an array containing instances of other export classes (PointBuildingsExport, PointBuildingsListExport, and PointContainmentsListExport. PointBuildingsExport: This class encapsulates the logic to export building data from SQL query using the provided longitude, latitude, and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. PointBuildingsListExport: This class encapsulates the logic to export a list of buildings data from SQL query using the provided longitude, latitude, and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. PointContainmentsListExport: This class encapsulates the logic to export a list of containments data from SQL query using the provided longitude, latitude, and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. |

| **Function**    | getBuildingsRoadReportCsv ()                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the class that handles the data about building to road that is stored in the server.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| **Parameters**  | Null                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| **Return**      | CSV files containing data from: (new BuildingsRoadSummaryInfoMultiSheetExport(request()-\>road_codes)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| **Source**      | app\\Http\\Controllers\\MapsController.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| **Remarks**     | BuildingsRoadSummaryInfoMultiSheetExport: The Excel file is generated using this class, passing the road code from the request parameters. In this case, it returns an array containing instances of other export classes (BuildingsRoadExport, BuildingsRoadListExport, and ContainmentsRoadListExport. BuildingsRoadExport: This class encapsulates the logic to export buildings and their sanitation systems data based on various criteria, such as structype and sanitation_system_technology_id. It also considers road codes and ensures that only active buildings and containments are included in the results display it to an Excel sheet, apply some styling, and provide a title for the sheet. BuildingsRoadListExport: This class encapsulates the logic to export a list of buildings data from SQL query using the provided longitude, latitude, and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. ContainmentsRoadListExport: This class encapsulates the logic to export a list of containments data from SQL query using the provided longitude, latitude, and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. |

| **Function**    | getDrainPotentialReportCSV ()                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| --------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the class that handles the data about sewer to building that is stored in the server.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| **Parameters**  | Null                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| **Return**      | CSV files containing data from: (new DrainPotentialSummaryInfoMultiSheetExport(\$rowGeom, \$bufferDisancePolygon)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| **Source**      | app\\Http\\Controllers\\MapsController.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| **Remarks**     | A SQL query is constructed to retrieve the geometry data of sewer from the database table utility_info.sewers based on a specific sewer code obtained from the request parameter sewer_code. The geometry data (in WKT format) from the first row of the result set is retrieved and stored in the variable \$rowGeom. Check if a buffer distance is provided in the request (db_distance). If a valid distance is provided, set bufferDisancePolygon to that value. Otherwise, set it to 0. Instantiate a DrainPotentialSummaryInfoMultiSheetExport export class with the sewer geometry and buffer distance parameters. In this case, it returns an array containing instances of other export classes (BuildingsExport, BuildingsListExport, and ContainmentsListExport), passing the buffer polygon geometry and distance to each of them. BuildingsExport: This class encapsulates the logic to export building data from SQL query using the provided sewer geometry data, and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. BuildingsListExport: This class encapsulates the logic to export a list of buildings data from SQL query using the provided sewer geometry data, and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. ContainmentsListExport: This class encapsulates the logic to export a list of containments data from SQL query using the provided sewer geometry data, and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. |

| **Function**    | getBuildingsTaxzoneReportCSV ()                                                                                                                                  |
| --------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the class that handles the data about buildings within a tax zone that is stored in the server.                                                            |
| **Parameters**  | Null                                                                                                                                                             |
| **Return**      | CSV file containing data from: (new BuildingsOwnerExport(\$geom)                                                                                                 |
| **Source**      | app\\Http\\Controllers\\MapsController.php                                                                                                                       |
| **Remarks**     | Instantiate a BuildingsOwnerExport export class with the geometry of building. A SQL query is constructed to fetch building information along with owner details |

| **Function**    | getBuildingToContainment()                                                                        |
| --------------- | ------------------------------------------------------------------------------------------------- |
| **Description** | Calls the service class that handles the data                                                     |
| **Parameters**  | null                                                                                              |
| **Return**      | Returns the result obtained from the getBuildingToContainment() method of the \$mapsService,      |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                       |
| **Remarks**     | getBuildingToContainment () MapsService explains the Service Class Function Name mentioned above. |

| **Function**    | getContainmentToBuildings ()                                                                       |
| --------------- | -------------------------------------------------------------------------------------------------- |
| **Description** | Calls the service class that handles the data                                                      |
| **Parameters**  | null                                                                                               |
| **Return**      | Returns the result obtained from the getContainmentToBuildings() method of the \$mapsService,      |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                        |
| **Remarks**     | getContainmentToBuildings () MapsService explains the Service Class Function Name mentioned above. |

| **Function**    | getAssociatedToMainbuilding ()                                                                       |
| --------------- | ---------------------------------------------------------------------------------------------------- |
| **Description** | Calls the service class that handles the data                                                        |
| **Parameters**  | null                                                                                                 |
| **Return**      | Returns the result obtained from the getAssociatedToMainbuilding () method of the \$mapsService,     |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                          |
| **Remarks**     | getAssociatedToMainbuilding () MapsService explains the Service Class Function Name mentioned above. |

| **Function**    | getExtent ()                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the service class that handles the data                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| **Parameters**  | null                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| **Return**      | the spatial extent of the requested layer such as: Returns the result obtained from the containmentExtent (\$layer, \$atrribute, \$value) method of the \$mapsService, Returns the result obtained from the buildingExtent (\$layer, \$atrribute, \$value) method of the \$mapsService, Returns the result obtained from the lineStringExtent(\$layer, \$atrribute, \$value) method of the \$mapsService, Returns the result obtained from the containmentSurveyExtent(\$atrribute, \$value) method of the \$mapsService, Returns the result obtained from the pointsExtent(\$layer, \$atrribute, \$value) method of the \$mapsService, |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| **Remarks**     | lineStringExtent(\$layer, \$atrribute, \$value), containmentSurveyExtent(\$atrribute, \$value) and pointsExtent(\$layer, \$atrribute, \$value) MapsService explains the Service Class Function Name mentioned above.                                                                                                                                                                                                                                                                                                                                                                                                                    |

| **Function**    | getContainmentBuildings ()                                                                                                         |
| --------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the service class that handles the data                                                                                      |
| **Parameters**  | null                                                                                                                               |
| **Return**      | Returns the result obtained from the getContainmentBuildings (request()-\>field, request()-\>val) method of the \$mapsService,     |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                        |
| **Remarks**     | getContainmentBuildings (request()-\>field, request()-\>val) MapsService explains the Service Class Function Name mentioned above. |

| **Function**    | getContainmentRoad ()                                                                                                             |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the service class that handles the data                                                                                     |
| **Parameters**  | null                                                                                                                              |
| **Return**      | Returns the result obtained from the getContainmentRoadInfo (request()-\>field, request()-\>val) method of the \$mapsService,     |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                       |
| **Remarks**     | getContainmentRoadInfo (request()-\>field, request()-\>val) MapsService explains the Service Class Function Name mentioned above. |

| **Function**    | getBuildingRoad ()                                                                                                             |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| **Description** | Calls the service class that handles the data                                                                                  |
| **Parameters**  | null                                                                                                                           |
| **Return**      | Returns the result obtained from the getBuildingRoadInfo (request()-\>field, request()-\>val) method of the \$mapsService,     |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                    |
| **Remarks**     | getBuildingRoadInfo (request()-\>field, request()-\>val) MapsService explains the Service Class Function Name mentioned above. |

| **Function**    | getNearestRoad ()                                                                                                        |
| --------------- | ------------------------------------------------------------------------------------------------------------------------ |
| **Description** | Calls the service class that handles the data                                                                            |
| **Parameters**  | null                                                                                                                     |
| **Return**      | Returns the result obtained from the getNearestRoad (request()-\>lat, request()-\>long) method of the \$mapsService,     |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                              |
| **Remarks**     | getNearestRoad (request()-\>lat, request()-\>long) MapsService explains the Service Class Function Name mentioned above. |

| **Function**    | getProposedEmptyingContainments ()                                                                                                                      |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the service class that handles the data                                                                                                           |
| **Parameters**  | null                                                                                                                                                    |
| **Return**      | Returns the result obtained from the getProposedEmptyingContainmentsInfo(request()-\>start_date, request()-\>end_date) method of the \$mapsService,     |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                             |
| **Remarks**     | getProposedEmptyingContainmentsInfo(request()-\>start_date, request()-\>end_date) MapsService explains the Service Class Function Name mentioned above. |

| **Function**    | getDueBuildings ()                                                                          |
| --------------- | ------------------------------------------------------------------------------------------- |
| **Description** | Calls the service class that handles the data                                               |
| **Parameters**  | null                                                                                        |
| **Return**      | Returns the result obtained from the getDueBuildingsInfo() method of the \$mapsService,     |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                 |
| **Remarks**     | getDueBuildingsInfo() MapsService explains the Service Class Function Name mentioned above. |

| **Function**    | getDueBuildingsWardTaxzone(Request \$request)                                                                 |
| --------------- | ------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the service class that handles the data                                                                 |
| **Parameters**  | \$request                                                                                                     |
| **Return**      | Returns the result obtained from the getDueBuildingsWardTaxzoneInfo(\$where) method of the \$mapsService,     |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                   |
| **Remarks**     | getDueBuildingsWardTaxzoneInfo(\$where) MapsService explains the Service Class Function Name mentioned above. |

| **Function**    | getApplicationContainments ()                                                                                                       |
| --------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the service class that handles the data                                                                                       |
| **Parameters**  | null                                                                                                                                |
| **Return**      | Returns the result obtained from the getApplicationContainments(request()-\>lat, request()-\>long) method of the \$mapsService,     |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                         |
| **Remarks**     | getApplicationContainments(request()-\>lat, request()-\>long) MapsService explains the Service Class Function Name mentioned above. |

| **Function**    | getApplicationContainmentsYearMonth(Request \$request)                                                                                         |
| --------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the service class that handles the data                                                                                                  |
| **Parameters**  | \$request                                                                                                                                      |
| **Return**      | Returns the result obtained from the getApplicationContainmentsYearMonth(\$request-\>year, \$request-\>month) method of the \$mapsService,     |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                    |
| **Remarks**     | getApplicationContainmentsYearMonth(\$request-\>year, \$request-\>month) MapsService explains the Service Class Function Name mentioned above. |

| **Function**    | getApplicationNotTPOnDate ()                                                                                            |
| --------------- | ----------------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the service class that handles the data                                                                           |
| **Parameters**  | null                                                                                                                    |
| **Return**      | Returns the result obtained from the getApplicationNotTPOnDate(request()-\>start_date) method of the \$mapsService,     |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                             |
| **Remarks**     | getApplicationNotTPOnDate(request()-\>start_date) MapsService explains the Service Class Function Name mentioned above. |

| **Function**    | getApplicationOnDate ()                                                                                            |
| --------------- | ------------------------------------------------------------------------------------------------------------------ |
| **Description** | Calls the service class that handles the data                                                                      |
| **Parameters**  | null                                                                                                               |
| **Return**      | Returns the result obtained from the getApplicationOnDate(request()-\>start_date) method of the \$mapsService,     |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                        |
| **Remarks**     | getApplicationOnDate(request()-\>start_date) MapsService explains the Service Class Function Name mentioned above. |

| **Function**    | getApplicationNotTP ()                                                                                       |
| --------------- | ------------------------------------------------------------------------------------------------------------ |
| **Description** | Calls the service class that handles the data                                                                |
| **Parameters**  | null                                                                                                         |
| **Return**      | Returns the result obtained from the getApplicationNotTP() method of the \$mapsService,                      |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                  |
| **Remarks**     | getApplicationNotTP() MapsService explains the Service Class Function Name mentioned above. mentioned above. |

| **Function**    | getApplicationNotTPContainmentsYearMonth(Request \$request)                                                                                        |
| --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the service class that handles the data                                                                                                      |
| **Parameters**  | \$request                                                                                                                                          |
| **Return**      | Returns the result obtained from the getApplicationNotTPContainmentsYearMonth(\$request-\>year,\$request-\>month) method of the \$mapsService,     |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                        |
| **Remarks**     | getApplicationNotTPContainmentsYearMonth(\$request-\>year,\$request-\>month) MapsService explains the Service Class Function Name mentioned above. |

| **Function**    | getFeedbackReport(Request \$request)                                                                                                                                                                                                                                                    |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Calls the service class that handles the data                                                                                                                                                                                                                                           |
| **Parameters**  | \$request                                                                                                                                                                                                                                                                               |
| **Return**      | Returns the result obtained from the getUniqueContainmentEmptiedCount(\$request-\>geom, \$whereUser), getFeedbacksCount(\$request-\>geom, \$whereUser), getFeedbackFsmServiceQuality(\$request-\>geom, \$whereUser), getFeedbackSanitationWorkersPpe()method of the \$mapsService,      |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                                                                                                                                                             |
| **Remarks**     | getUniqueContainmentEmptiedCount(\$request-\>geom, \$whereUser), getFeedbacksCount(\$request-\>geom, \$whereUser), getFeedbackFsmServiceQuality(\$request-\>geom, \$whereUser), getFeedbackSanitationWorkersPpe() MapsService explains the Service Class Function Name mentioned above. |

| **Function**    | getDrainBuildings (Request \$request)                                        |
| --------------- | ---------------------------------------------------------------------------- |
| **Description** | Retrieves information about buildings associated with specified drain codes. |
| **Parameters**  | \$request                                                                    |
| **Return**      | Building information associated with the specified drain codes.              |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                  |

| **Function**    | getBuildingsToRoad (Request \$request)                                                                                                                          |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves buildings associated with the specified road codes and their summary information. Calls the service class that handles the data                       |
| **Parameters**  | \$request                                                                                                                                                       |
| **Return**      | Associative array containing buildings and their summary information.                                                                                           |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                                     |
| **Remarks**     | getBuildingsToRoadSummary(\$roadCodes) MapsService explains the Service Class Function Name mentioned above to get buildings related to the provided road codes |

| **Function**    | getDrainPotentialBuildings (Request \$request)                                                                                                                                          |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves the potential buildings within the drainage area. Calls the service class that handles the data                                                                               |
| **Parameters**  | \$request                                                                                                                                                                               |
| **Return**      | Returns an array containing buildings, popup content HTML, and polygon data.                                                                                                            |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                                                             |
| **Remarks**     | buildingsPopContentPolygon(\$bufferDisancePolygon, \$sewer[0]-\>geom) MapsService explains the Service Class Function Name mentioned above to get buildings, pop contents, and polygon. |

| **Function**    | getWaterBodiesBuildings (Request \$request)                                                                                                                                    |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Description** | Retrieves buildings and related information within a specified distance from a water body. Calls the service class that handles the data                                       |
| **Parameters**  | \$request                                                                                                                                                                      |
| **Return**      | Array containing buildings, pop contents HTML, and polygon information.                                                                                                        |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                                                    |
| **Remarks**     | buildingsPopContentPolygon(\$distance,\$waterbody[0]-\>geom) MapsService explains the Service Class Function Name mentioned above to get buildings, pop contents, and polygon. |

| **Function**    | getPointBufferBuildings(Request \$request)                                                                                                                                     |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Description** | Retrieves buildings and related information within a specified distance from a water body. Calls the service class that handles the data                                       |
| **Parameters**  | \$request                                                                                                                                                                      |
| **Return**      | Array containing buildings, pop contents HTML, and polygon information.                                                                                                        |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                                                    |
| **Remarks**     | buildingsPopContentPolygon(\$distance,\$waterbody[0]-\>geom) MapsService explains the Service Class Function Name mentioned above to get buildings, pop contents, and polygon. |

| **Function**    | getRoadBuildings (Request \$request)                                                                                                                                                   |
| --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves information about buildings along a road and returns relevant data. Calls the service class that handles the data                                                            |
| **Parameters**  | \$request                                                                                                                                                                              |
| **Return**      | Array containing buildings, pop contents HTML, and polygon information.                                                                                                                |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                                                            |
| **Remarks**     | buildingsPopContentPolygon(\$bufferDisancePolygon, \$road[0]-\>geom) MapsService explains the Service Class Function Name mentioned above to get buildings, pop contents, and polygon. |

| **Function**    | searchByKeywords()                                                                                                                                                                                                                                                                               |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Description** | Searches for keywords within specified layers and retrieves associated data.                                                                                                                                                                                                                     |
| **Parameters**  | null                                                                                                                                                                                                                                                                                             |
| **Return**      | An array containing the following keys: 'gid': The ID associated with the matched keyword (or null if not found). 'point': The point geometry associated with the matched keyword (or null if not found). 'geom': The geometric data associated with the matched keyword (or null if not found). |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                                                                                                                                                                      |

| **Function**    | searchBuilding()                                                                                |
| --------------- | ----------------------------------------------------------------------------------------------- |
| **Description** | Searches for buildings based on a specified field and value.                                    |
| **Parameters**  | null                                                                                            |
| **Return**      | An array of buildings matching the search criteria, or null if no matching buildings are found. |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                     |

| **Function**    | searchAutoComplete ()                                              |
| --------------- | ------------------------------------------------------------------ |
| **Description** | Performs autocomplete search based on provided layer and keywords. |
| **Parameters**  | null                                                               |
| **Return**      | Results of autocomplete search.                                    |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                        |

| **Function**    | getBufferPolygonBuildings (Request \$request)                                                                                                                                              |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Description** | Retrieves buildings within a buffered polygon and their corresponding population content HTML. Calls the service class that handles the data                                               |
| **Parameters**  | \$request                                                                                                                                                                                  |
| **Return**      | Array containing buildings, population content HTML, and polygon information.                                                                                                              |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                                                                |
| **Remarks**     | buildingsPopContentPolygon(\$bufferDistancePolygon, \$bufferPolygonGeom) MapsService explains the Service Class Function Name mentioned above to get buildings, pop contents, and polygon. |

| **Function**    | getWardBuildings (Request \$request)                                                                                                                                      |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves buildings within a specific ward and their corresponding population content HTML. Calls the service class that handles the data                                 |
| **Parameters**  | \$request                                                                                                                                                                 |
| **Return**      | Array containing buildings information.                                                                                                                                   |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                                               |
| **Remarks**     | buildingsPopContentPolygon(\$bufferDisancePolygon, \$ward[0]-\>geom) MapsService explains the Service Class Function Name mentioned above to get buildings, pop contents. |

| **Function**    | getAreaPopulationPolygonSum(Request \$request)                                                                                                         |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Description** | Retrieves the sum of population within a specified polygon area. Calls the service class that handles the data                                         |
| **Parameters**  | \$request                                                                                                                                              |
| **Return**      | The sum of population within the polygon area or an error message if the 'geom' field is missing.                                                      |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                            |
| **Remarks**     | getAreaPopulationPolygonSumInfo(\$request-\>geom) MapsService explains the Service Class Function Name mentioned above to get buildings, pop contents. |

| **Function**    | getWardCenterCoordinates (Request \$request)                        |
| --------------- | ------------------------------------------------------------------- |
| **Description** | Retrieves the center coordinates of a specified ward.               |
| **Parameters**  | \$request                                                           |
| **Return**      | Array containing the geometry and identifier of the specified ward. |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                         |

| **Function**    | getClipWardCenterCoordinates (Request \$request)               |
| --------------- | -------------------------------------------------------------- |
| **Description** | Retrieves the center coordinates of the clipped ward geometry. |
| **Parameters**  | \$request                                                      |
| **Return**      | Array containing the geometry and ward identifier.             |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                    |

| **Function**    | getOwnerOfBuilding (Request \$request)                         |
| --------------- | -------------------------------------------------------------- |
| **Description** | Retrieves the owner information of a building based on its BIN |
| **Parameters**  | \$request                                                      |
| **Return**      | The owner information of the building, or null if not found    |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                    |

| **Function**    | checkGeometryType (Request \$request)                |
| --------------- | ---------------------------------------------------- |
| **Description** | Checks the type of geometry provided in the request. |
| **Parameters**  | \$request                                            |
| **Return**      | The type of geometry                                 |
| **Source**      | app\\Http\\Controllers\\ MapsController.php          |

| **Function**    | roadInaccesibleISummaryInfo (Request \$request)                                                                                                       |
| --------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves summary information about road inaccessibility based on provided road width and vacuum range. Calls the service class that handles the data |
| **Parameters**  | \$request                                                                                                                                             |
| **Return**      | Array containing buildings, population content HTML, and polygon information.                                                                         |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                           |
| **Remarks**     | getRoadInaccesibleISummaryInfo(\$roadWidth, \$vacutugRange) MapsService explains the Service Class Function Name mentioned above.                     |

| **Function**    | exportWfsRequest (Request \$request)                                                                    |
| --------------- | ------------------------------------------------------------------------------------------------------- |
| **Description** | Exports data from a WFS request.                                                                        |
| **Parameters**  | \$request                                                                                               |
| **Return**      | The HTTP request containing the export URL, file format, and layer information.                         |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                             |
| **Remarks**     | This function retrieves data from a specified WFS endpoint and exports it in the requested file format. |

| **Function**    | getPolygonWaterbodyInaccessibleReport (Request \$request)                       |
| --------------- | ------------------------------------------------------------------------------- |
| **Description** | Retrieves a report of buildings close to water bodies within a specified range. |
| **Parameters**  | \$request                                                                       |
| **Return**      | Excel file containing summary information of buildings close to water bodies.   |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                     |

| **Function**    | waterbodyInaccessibleBuildings (Request \$request)                                                                                                                |
| --------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves buildings within a water body's inaccessible zone along with their corresponding population content HTML. Calls the service class that handles the data |
| **Parameters**  | \$request                                                                                                                                                         |
| **Return**      | Array containing buildings, population content HTML, and polygon information.                                                                                     |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                                       |
| **Remarks**     | getWaterbodyInaccesibleISummaryInfo(\$hose_length) MapsService explains the Service Class Function Name mentioned above.                                          |

| **Function**    | roadInaccessibleBuildings (Request \$request)                                                                                              |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| **Description** | Retrieves inaccessible buildings along a road based on provided road width and house length. Calls the service class that handles the data |
| **Parameters**  | \$request                                                                                                                                  |
| **Return**      | Array containing buildings, population content HTML, and polygon information.                                                              |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                                                                |
| **Remarks**     | getRoadInaccesibleISummaryInfo(\$road_width, \$hose_length) MapsService explains the Service Class Function Name mentioned above.          |

| **Function**    | getPolygonRoadInaccessibleReport (Request \$request)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves a report of inaccessible road areas within a specified range and width. Calls the service class that handles the data                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| **Parameters**  | \$request                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| **Return**      | CSV files containing data obtainerd from: new SummaryInfoMultiSheetExport(\$remainingPolygonGeom, 0)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| **Source**      | app\\Http\\Controllers\\MapsController.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| **Remarks**     | SummaryInfoMultiSheetExport: The Excel file is generated using this class, passing the buffer polygon geometry and distance obtained from the request parameters. In this case, it returns an array containing instances of other export classes (BuildingsExport, BuildingsListExport, and ContainmentsListExport), passing the buffer polygon geometry and distance to each of them. BuildingsExport: This class encapsulates the logic to export building data from SQL query using the provided buffer polygon geometry data and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. BuildingsListExport: This class encapsulates the logic to export a list of buildings data from SQL query using the provided buffer polygon geometry data and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. ContainmentsListExport: This class encapsulates the logic to export a list of containments data from SQL query using the provided buffer polygon geometry data and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. |

| **Function**    | getBuildingsToiletNetwork (Request \$request)                                                   |
| --------------- | ----------------------------------------------------------------------------------------------- |
| **Description** | Retrieves buildings with toilet network information based on the provided bin.                  |
| **Parameters**  | \$request                                                                                       |
| **Return**      | Returns an array of buildings with toilet network information if found, otherwise returns null. |
| **Source**      | app\\Http\\Controllers\\ MapsController.php                                                     |

| **Function**    | checkLocationWithinBoundary(Request $request)                                                                                              |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| **Description** | Checks whether the provided coordinates fall within the municipality boundary                                                              |
| **Parameters**  | $request                                                                                                                                   |
| **Return**      | an array of polygon(s) from the 'citypolys' table that intersect with the given point. If no polygon is found, an empty array is returned. |

| **Function**    | getKmlInfoReportCsv(Request $request)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| --------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Generates and downloads a summary report in Excel format based on multiple KML geometries.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| **Parameters**  | $request                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| **Return**      | CSV files containing data obtainerd from: new SummaryInfoMultiSheetExport($geometries, 0)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| **Source**      | app\Http\Controllers\MapsController.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Remarks**     | SummaryInfoMultiSheetExport: The Excel file is generated using this class, passing the geometry and distance obtained from the request parameters. In this case, it returns an array containing instances of other export classes (BuildingsExport, BuildingsListExport, and ContainmentsListExport), passing the buffer polygon geometry and distance to each of them. BuildingsExport: This class encapsulates the logic to export building data from SQL query using the provided buffer polygon geometry data and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. BuildingsListExport: This class encapsulates the logic to export a list of buildings data from SQL query using the provided buffer polygon geometry data and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. ContainmentsListExport: This class encapsulates the logic to export a list of containments data from SQL query using the provided buffer polygon geometry data and its buffer distance to an Excel sheet, apply some styling, and provide a title for the sheet. |

| **Function**    | checkGeometry(Request $request)                                                |
| --------------- | ------------------------------------------------------------------------------ |
| **Description** | Checks whether the provided geometries intersect with municipality boundaries. |
| **Parameters**  | $request                                                                       |
| **Return**      | JSON response indicating intersection results for each geometry.               |
| **Source**      | app\Http\Controllers\MapsController.php                                        |
| **Remarks**     | checkGeometryType($geometry) is explained below                                |

| **Function**    | getKmlSummaryInfo(Request $request)                                                                                                                                                    |
| --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Processes an array of geometries from the request and filters only valid polygons (ST_POLYGON). \* If valid polygons exist, it forwards them to another method for further processing. |
| **Parameters**  | $request                                                                                                                                                                               |
| **Return**      | Result of building lookup \* if valid polygons exist; otherwise, returns an error response indicating no valid polygons found.                                                         |
| **Source**      | app\Http\Controllers\MapsController.php                                                                                                                                                |
| **Remarks**     | checkGeometryType($geometry) is explained below                                                                                                                                        |

| **Function**    | checkGeometryType(string $geometry)                  |
| --------------- | ---------------------------------------------------- |
| **Description** | Checks the type of geometry provided in the request. |
| **Parameters**  | $geometry                                            |
| **Return**      | The type of geometry                                 |
| **Source**      | app\Http\Controllers\MapsController.php              |

| **Function**    | getToiletIsochroneAreaLayers(Request $request)                                                                                              |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves buildings and polygon geometry within a specified isochrone distance for toilet network analysis                                  |
| **Parameters**  | $request                                                                                                                                    |
| **Return**      | Returns an associative array with: - 'buildings': List of buildings within the isochrone area. - 'polygon': The isochrone polygon geometry. |
| **Source**      | app\Http\Controllers\MapsController.php                                                                                                     |
| **Remarks**     | getToiletIsochroneAreaLayers($distance) MapsService explains the Service Class Function Name mentioned above.                               |

| **Function**    | getContainmentReport(Request $request)                                                                                                      |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Generates a containment emptying report for a specified geometry over the past 5 years.                                                     |
| **Parameters**  | $request                                                                                                                                    |
| **Return**      | Returns an array of monthly data and styling information for the chart. - If 'geom' is missing, a string error message is returned instead. |
| **Source**      | app\Http\Controllers\MapsController.php                                                                                                     |

| **Function**    | getContainmentReportCsv(Request $request)                                                                                                            |
| --------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves a report containing the monthly containment emptying summary, based on the selected geometry and year from the request.                    |
| **Parameters**  | $request                                                                                                                                             |
| **Return**      | CSV files containing data obtainerd from: new SummaryInfoMultiSheetExport(request()->containment_report_polygon, request()->containment_report_year) |
| **Source**      | app\Http\Controllers\MapsController.php                                                                                                              |

MapsService

Service class: app\\Services\\Maps\\MapsService.php

-   All functions and queries used by maps are written here.

| **Function**    | mapsIndex ()                                                                                                                                                                                                              |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves necessary data for rendering the map index page                                                                                                                                                                 |
| **Parameters**  | null                                                                                                                                                                                                                      |
| **Return**      | view('maps.index', compact('page_title', 'wards', 'taxZones', 'dueYears', 'maxDate', 'minDate', 'bldguse', 'usecatg', 'pickWardResults', 'pickDateResults', 'pickStructureResults', 'roadHierarchy', 'roadSurfaceTypes')) |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                                                                                                  |

| **Function**    | getBuildingToContainment ()                                                                                                                       |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves the latitude and longitude coordinates of containment areas associated with a given building.                                           |
| **Parameters**  | null                                                                                                                                              |
| **Return**      | Returns an array of associative arrays containing latitude and longitude coordinates for each containment associated with the specified building. |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                          |

| **Function**    | getContainmentToBuildings ()                                                     |
| --------------- | -------------------------------------------------------------------------------- |
| **Description** | Retrieves containment information for buildings based on a given containment ID. |
| **Parameters**  | null                                                                             |
| **Return**      | Returns an array containing building information including BIN and geometry.     |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                         |

| **Function**    | getAssociatedToMainbuilding ()                                                               |
| --------------- | -------------------------------------------------------------------------------------------- |
| **Description** | Retrieves the coordinates of buildings associated with a main building identified by its BIN |
| **Parameters**  | null                                                                                         |
| **Return**      | Returns an array containing the latitude and longitude coordinates of associated buildings.  |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                     |

| **Function**    | buildingExtent (\$bin, \$value)                                                               |
| --------------- | --------------------------------------------------------------------------------------------- |
| **Description** | Retrieves the extent and centroid coordinates of buildings based on a given attribute value.  |
| **Parameters**  | \$bin, \$value                                                                                |
| **Return**      | An array containing the extent (xmin, ymin, xmax, ymax) and centroid (lat, long) coordinates. |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                      |

| **Function**    | containmentExtent (\$id, \$value)                                                               |
| --------------- | ----------------------------------------------------------------------------------------------- |
| **Description** | Retrieves the extent and centroid coordinates of containments based on a given attribute value. |
| **Parameters**  | \$id, \$value                                                                                   |
| **Return**      | An array containing the extent (xmin, ymin, xmax, ymax) and centroid (lat, long) coordinates.   |

| **Function**    | lineStringExtent(\$layer, \$code, \$value)                                                                        |
| --------------- | ----------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves the extent (bounding box) and geometry of a linestring feature based on a given layer, code, and value. |
| **Parameters**  | \$layer, \$code, \$value                                                                                          |
| **Return**      | An array containing the xmin, ymin, xmax, ymax values of the extent, and the geometry of the linestring feature.  |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                          |

| **Function**    | containmentSurveyExtent(\$param)                                                      |
| --------------- | ------------------------------------------------------------------------------------- |
| **Description** | Retrives the extent of containment survey based on given parameters.                  |
| **Parameters**  | \$param                                                                               |
| **Return**      | Containment survey extent containing xmin, ymin, xmax, ymax, latitude, and longitude. |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                              |

| **Function**    | pointsExtent(\$layer, \$id, \$value)                                                               |
| --------------- | -------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves the extent (bounding box) of points for a given layer, value and identifier.             |
| **Parameters**  | \$layer, \$id, \$value                                                                             |
| **Return**      | The extent of points represented as an associative array with keys 'xmin', 'ymin', 'xmax', 'ymax'. |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                           |

| **Function**    | getContainmentBuildings(\$field, \$value)                            |
| --------------- | -------------------------------------------------------------------- |
| **Description** | Retrieves containment buildings based on a specific field and value. |
| **Parameters**  | \$field, \$value                                                     |
| **Return**      | An array of containment connected to building coordinates.           |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                             |

| **Function**    | getContainmentRoadInfo (\$field, \$value)                                                                                                                                                                                                                   |
| --------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves containment and nearest road information based on a specified field and value.                                                                                                                                                                    |
| **Parameters**  | \$field, \$value                                                                                                                                                                                                                                            |
| **Return**      | An associative array containing the latitude and longitude of the containment (c_lat, c_long) and the nearest road (r_lat, r_long) found within a 1000-meter radius of the containment. If no results are found, empty strings are returned for all values. |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                                                                                                                                    |

| **Function**    | getBuildingRoadInfo (\$field, \$value)                                                                                                                                                                                                                                                       |
| --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves building centroid and nearest road information based on a specified field and value.                                                                                                                                                                                               |
| **Parameters**  | \$field, \$value                                                                                                                                                                                                                                                                             |
| **Return**      | An associative array containing the latitude and longitude of the building centroid (c_lat, c_long) and the latitude and longitude of the nearest road (r_lat, r_long) found within a 1000-meter radius of the building. If no results are found, empty strings are returned for all values. |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                                                                                                                                                                     |

| **Function**    | getNearestRoad (\$lat,\$long)                                                                                                                                        |
| --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves information about the nearest road to a specified latitude and longitude.                                                                                  |
| **Parameters**  | \$lat,\$long                                                                                                                                                         |
| **Return**      | An associative array containing the latitude and longitude of the nearest road. If no results are found, empty strings are returned for both latitude and longitude. |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                                             |

| **Function**    | getProposedEmptyingContainmentsInfo(request()-\>start_date, request()-\>end_date)                                                                                                                                                                            |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Description** | Retrieves information about proposed emptying containments within a specified date range.                                                                                                                                                                    |
| **Parameters**  | request()-\>start_date, request()-\>end_date                                                                                                                                                                                                                 |
| **Return**      | An array of associative arrays containing the latitude and longitude coordinates of containment locations with proposed emptying dates within the specified date range. If no results are found, empty strings are returned for both latitude and longitude. |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                                                                                                                                     |

| **Function**    | getDueBuildingsInfo()                                                                                                                                |
| --------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves information about buildings with due taxes, including their bin number, latitude, and longitude.                                           |
| **Parameters**  | null                                                                                                                                                 |
| **Return**      | An array of associative arrays, each containing the latitude ('lat') and longitude ('long') of a building with due taxes, along with its bin number. |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                             |

| **Function**    | getDueBuildingsWardTaxzoneInfo(\$where)                                                                                                                         |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves information about buildings within a ward or tax zone that have tax dues.                                                                             |
| **Parameters**  | \$where                                                                                                                                                         |
| **Return**      | An array of associative arrays, each containing the latitude (lat) and longitude (long) of a building within the specified ward or tax zone that has due taxes. |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                                        |

| **Function**    | getApplicationContainments()                                                                                                                                                                                    |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves application containment information based on the user's role.                                                                                                                                         |
| **Parameters**  | null                                                                                                                                                                                                            |
| **Return**      | An array containing application containment information including application ID, house number, service provider, application date, emptying status, feedback status, sludge collection status , and longitude. |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                                                                                        |

| **Function**    | getApplicationContainmentsYearMonth(\$year, \$month)                                     |
| --------------- | ---------------------------------------------------------------------------------------- |
| **Description** | Retrieves containment information for applications filtered by year and month.           |
| **Parameters**  | \$year, \$month                                                                          |
| **Return**      | An array containing information about applications and their corresponding containments. |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                 |

| **Function**    | getApplicationNotTPOnDate(\$start_date)                                                                                                     |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves applications that are not yet marked as sludge collection and have been emptied by service providers on the specified start date. |
| **Parameters**  | \$start_date                                                                                                                                |
| **Return**      | JSON response containing data about applications and service providers.                                                                     |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                    |

| **Function**    | getApplicationOnDate (\$start_date)                                                                                                                                                         |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves application information for a specified date.                                                                                                                                     |
| **Parameters**  | \$start_date                                                                                                                                                                                |
| **Return**      | An array containing application information, including application ID, house number, service provider, emptying status, feedback status, sludge collection status, latitude, and longitude. |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                                                                    |

| **Function**    | getApplicationNotTP ()                                                                                                                                                                      |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves application information for a specified date.                                                                                                                                     |
| **Parameters**  | \$start_date                                                                                                                                                                                |
| **Return**      | An array containing application information, including application ID, house number, service provider, emptying status, feedback status, sludge collection status, latitude, and longitude. |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                                                                    |

| **Function**    | getApplicationNotTP ()                                                                                        |
| --------------- | ------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves application information for applications that are not yet marked as TP (TreatmentPlant) collection. |
| **Parameters**  | null                                                                                                          |
| **Return**      | JSON response containing application data and service provider information.                                   |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                      |

| **Function**    | getApplicationNotTPContainmentsYearMonth (\$year, \$month)                                                    |
| --------------- | ------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves application information for applications that are not yet marked as TP (TreatmentPlant) collection. |
| **Parameters**  | null                                                                                                          |
| **Return**      | JSON response containing application data and service provider information.                                   |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                      |

| **Function**    | getUniqueContainmentEmptiedCount(\$geom, \$whereUser)                   |
| --------------- | ----------------------------------------------------------------------- |
| **Description** | Retrieves the count of unique containment units that have been emptied. |
| **Parameters**  | \$geom, \$whereUser                                                     |
| **Return**      | The count of unique containment units that have been emptied.           |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                |

| **Function**    | getFeedbacksCount (\$geom, \$whereUser)                                                       |
| --------------- | --------------------------------------------------------------------------------------------- |
| **Description** | Retrieves the count of feedbacks based on the provided geometry and user-specific conditions. |
| **Parameters**  | \$geom, \$whereUser                                                                           |
| **Return**      | The total count of feedbacks satisfying the given criteria.                                   |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                      |

| **Function**    | getFeedbackFsmServiceQuality(\$geom, \$whereUser)                                                               |
| --------------- | --------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves feedback data related to FSM service quality based on provided geometry and optional user conditions. |
| **Parameters**  | \$geom, \$whereUser                                                                                             |
| **Return**      | Returns an array of feedback data including quality indicators and their counts.                                |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                        |

| **Function**    | getFeedbackSanitationWorkersPpe ()                                              |
| --------------- | ------------------------------------------------------------------------------- |
| **Description** | Retrieves the count of sanitation workers wearing (PPE) based on feedback data. |
| **Parameters**  | null                                                                            |
| **Return**      | An array containing the count of workers wearing PPE and not wearing PPE.       |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                        |

| **Function**    | getBuildingsToRoadSummary(\$roadCodes)                                                                                                     |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| **Description** | Retrieves summary information about buildings along specified road codes.                                                                  |
| **Parameters**  | \$roadCodes                                                                                                                                |
| **Return**      | An array containing buildings information and summary HTML                                                                                 |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                   |
| **Remarks**     | popUpContentHtml(\$buildingResults): Generates HTML content for displaying based on summary data of building information in a table format |

| **Function**    | buildingsPopContentPolygon(\$bufferDistancePolygon, \$bufferPolygonGeom)                                                                                                                                                                                                                                                                                                           |
| --------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Generates building information and popup content HTML within a buffered polygon.                                                                                                                                                                                                                                                                                                   |
| **Parameters**  | \$bufferDistancePolygon, \$bufferPolygonGeom                                                                                                                                                                                                                                                                                                                                       |
| **Return**      | Associative array containing building information, popup content HTML, and polygon geometry.                                                                                                                                                                                                                                                                                       |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                                                                                                                                                                                                                                                           |
| **Remarks**     | Use a ufunction to get building data as fnc_getBufferPolygonBuildings( ST_GeomFromText(" . "'" . "\$bufferPolygonGeom" . "'" . ",4326), \$bufferDistancePolygon) further explained in Section **2 - Technical Information/ Basic CRUD** popUpContentHtml(\$buildingResults): Generates HTML content for displaying based on summary data of building information in a table format |

| **Function**    | getPointBufferBuildingsSummary(\$distance, \$long, \$lat)                                                                                                                                                                                                                                     |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves information about buildings within a specified buffer zone around a given point. use a function to get point buffer buildings “fnc_getPointBufferBuildings”                                                                                                                         |
| **Parameters**  | \$distance, \$long, \$lat                                                                                                                                                                                                                                                                     |
| **Return**      | An array containing information about buildings within the buffer zone, along with other related data.                                                                                                                                                                                        |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                                                                                                                                                                      |
| **Remarks**     | use a function to get point buffer buildings “fnc_getPointBufferBuildings” which is mentioned in Section **2 - Technical Information/ Basic CRUD** popUpContentHtml(\$buildingResults): Generates HTML content for displaying based on summary data of building information in a table format |

| **Function**    | getAreaPopulationPolygonSumInfo (\$geom)                                                               |
| --------------- | ------------------------------------------------------------------------------------------------------ |
| **Description** | Retrieves the sum of population served within a given polygon area.                                    |
| **Parameters**  | \$geom                                                                                                 |
| **Return**      | An array containing information about buildings within the buffer zone, along with other related data. |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                               |
| **Remarks**     | HTML table containing the total population served within the polygon area.                             |

| **Function**    | getRoadInaccesibleISummaryInfo(\$width, \$range)                                                                                                                                                                                                                                                                                                                  |
| --------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves summary information about inaccessible roads within a specified width range.                                                                                                                                                                                                                                                                            |
| **Parameters**  | \$width, \$range                                                                                                                                                                                                                                                                                                                                                  |
| **Return**      | Array containing information about buildings, population content HTML, and the buffered polygon.                                                                                                                                                                                                                                                                  |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                                                                                                                                                                                                                                          |
| **Remarks**     | Use a ufunction to get building data as fnc_getBufferPolygonBuildings ( ST_GeomFromText(" . "'" . "\$remainingPolygonGeom" . "'" . ",4326), 0) further explained in Section **2 - Technical Information/ Basic CRUD** popUpContentHtml(\$buildingResults) : Generates HTML content for displaying based on summary data of building information in a table format |

| **Function**    | getWaterbodyInaccesibleISummaryInfo(\$range)                                                                                                                                                                                                                                                                          |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Description** | Retrieves summary information about inaccessible water bodies within a specified range.                                                                                                                                                                                                                               |
| **Parameters**  | \$range                                                                                                                                                                                                                                                                                                               |
| **Return**      | An array containing information about buildings, population content HTML, and the polygon.                                                                                                                                                                                                                            |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                                                                                                                                                                                                                                                              |
| **Remarks**     | Use a ufunction to get building data as fnc_getBufferPolygonBuildings( ST_GeomFromText(" . "'" . "\$polygon" . "'" . ",4326), \$range) further explained in Section 10.2.2 popUpContentHtml(\$buildingResults): Generates HTML content for displaying based on summary data of building information in a table format |

| **Function**    | popUpContentHtml(\$buildingResults)                                           |
| --------------- | ----------------------------------------------------------------------------- |
| **Description** | Generates HTML content for displaying building information in a table format. |
| **Parameters**  | \$buildingResults                                                             |
| **Return**      | HTML content for displaying building information.                             |
| **Source**      | app\\Http\\Controllers\\ MapsService.php                                      |

| **Function**    | getToiletIsochroneAreaLayers($distance)              |
| --------------- | ---------------------------------------------------- |
| **Description** | Calculates isochrone polygons from nearby toilets.   |
| **Parameters**  | $distance                                            |
| **Return**      | an array of isochrone polygons (WKT) for each toilet |
| **Source**      | app\Services\Maps\MapsService.php                    |
