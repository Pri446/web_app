<?php
// Last Modified Date: 12-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Services\Maps;

use App\BuildOwner;
use App\Exports\SummaryInfoMultiSheetExport;
use App\Exports\WaterbodySummaryInfoMultiSheetExport;
use App\Exports\WardBuildingsSummaryInfoMultiSheetExport;
use App\Exports\RoadBuildingsSummaryInfoMultiSheetExport;
use App\Exports\PointBuildingsSummaryInfoMultiSheetExport;
use App\Exports\BuildingsRoadSummaryInfoMultiSheetExport;
use App\Exports\DrainPotentialSummaryInfoMultiSheetExport;
use App\ServiceProvider;
use Auth;
use App\Exports\BuildingsOwnerExport;
use Maatwebsite\Excel\Excel;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\LayerInfo\Ward;
use App\Models\LayerInfo\Taxzone;
//use App\Models\WaterSupplyInfo\DueYear;
use App\Models\TaxPaymentInfo\DueYear;
use DB;
use App\Models\BuildingInfo\FunctionalUse;
use App\Models\BuildingInfo\UseCategory;
use App\ContainmentSurvey;
use App\Models\Fsm\Emptying;
use App\Models\Fsm\Feedback;
use Schema;
use App\Models\UtilityInfo\Roadline;
use App\Models\UtilityInfo\SewerLine;
use App\Models\UtilityInfo\Drain;
use App\Models\Fsm\TreatmentPlant;



class MapsService {

    protected $session;
    protected $instance;

    /**
     * Constructs a new maps object.
     *
     *
     */
    public function __construct()
    {
        /*Session code
        ....
         here*/


    }

   /**
     * Retrieves necessary data for rendering the map index page.
     * @return \Illuminate\View\View
     */

    public function mapsIndex(array $approvedWmsServers = [])
    {

        $bldguse = FunctionalUse::orderBy('id', 'asc')->get(['name']);
        $usecatg = UseCategory::orderBy('id', 'asc')->get(['name']);
        $wards = Ward::orderBy('ward', 'asc')->pluck('ward', 'ward')->all();
        $dueYears = DueYear::orderBy('id', 'asc')->pluck('name', 'value')->all();
        // Determining max and min years
        $maxDate = date('Y');
        $minDate = date('Y') - 4;
        // Query to select distinct wards
        $pickWard ="select distinct ward from layer_info.wards order by ward asc";
        $pickWardResults = DB::select($pickWard);
        // Query to select distinct application dates
        $pickDate ="select distinct extract(year from application_date) as date1 from fsm.applications where deleted_at is null order by date1 DESC";
        $pickDateResults = DB::select($pickDate);
        // Query to select distinct structure types
        $pickStructureResults = DB::table('building_info.buildings')
            ->join('building_info.structure_types', 'building_info.structure_types.id', '=', 'building_info.buildings.structure_type_id')
            ->select('building_info.structure_types.*')
            ->groupBy('building_info.buildings.structure_type_id', 'building_info.structure_types.id')
            ->get();


        $page_title = __("Map");
        
        // Fetching road hierarchy data
        $roadHierarchy = Roadline::whereNotNull('hierarchy')->groupBy('hierarchy')->pluck('hierarchy','hierarchy');
         // Fetching road surface types data
        $roadSurfaceTypes = Roadline::whereNotNull('surface_type')->groupBy('surface_type')->pluck('surface_type','surface_type');
        $road_code = Roadline::get(['code', 'name'])->mapWithKeys(function ($item) {
            return [$item->code => ($item->name ? $item->code . ' - ' . $item->name : $item->code)];
        })->toArray();
        $bboxValues = DB::select("SELECT 
            (ST_XMin(bbox) || ',' || ST_YMin(bbox) || ',' || ST_XMax(bbox) || ',' || ST_YMax(bbox)) AS bbox_values 
            FROM (
                SELECT ST_Extent(geom) AS bbox FROM layer_info.citypolys
            ) AS extent_subquery
        ");
        $bboxstring = $bboxValues[0]->bbox_values;
        $cover_type = Drain::whereNotNull('cover_type')->groupBy('cover_type')->pluck('cover_type','cover_type');

        // $roadCode = Roadline::orderBy('code')->pluck('code', 'code');
        $treatmentPlants = TreatmentPlant::Operational()->orderBy('id')->pluck('name', 'id');
        $surface_type = Drain::whereNotNull('surface_type')->groupBy('surface_type')->pluck('surface_type','surface_type');


        $location = SewerLine::whereNotNull('location')->distinct('location')->pluck('location','location')->all();
        
        return view('maps.index', compact('page_title', 'wards', 'location','dueYears', 'maxDate','treatmentPlants', 
        'minDate', 'bldguse', 'usecatg', 'pickWardResults', 'pickDateResults', 'pickStructureResults', 'cover_type','roadHierarchy', 'roadSurfaceTypes','surface_type',
        'bboxstring','road_code', 'approvedWmsServers'
    ));
    }

    /**
     * Retrieves the latitude and longitude coordinates of containment areas associated with a given building.
     * 
     * @return array An array containing latitude and longitude coordinates of containment areas.
     */

    public function getBuildingToContainment() 
    {

        $bin = request()->bin;
        // query to retrieve latitude and longitude from the database
        $query = "SELECT ST_Y (ST_Transform (c.geom, 4326)) AS lat, ST_X (ST_Transform (c.geom, 4326)) AS long
                FROM fsm.containments c
                WHERE c.deleted_at IS NULL 
                    AND c.id IN (
                        SELECT bc.containment_id
                        FROM building_info.build_contains bc
                        JOIN building_info.buildings b ON bc.bin = b.bin::CHARACTER VARYING AND bc.deleted_at IS NULL
                        WHERE b.deleted_at IS NULL"
                    . " AND b.bin = ? "
                    . ")";
        // Execute the SQL query with the 'bin' value as a parameter
        $results = DB::select($query, [$bin]);

        $data = array();

        foreach ($results as $row) {
            $coord = array();
            $coord['lat'] = $row->lat;
            $coord['long'] = $row->long;
            $data[] = $coord;
        }
        return $data;

    }

    /**
     * Retrieves containment information for buildings based on a given containment ID.
     * 
     * @return array Array containing building information including BIN and geometry.
     */

    public function getContainmentToBuildings()
    {
        $containmentId = request()->containmentId;
        // Constructing the SQL query to retrieve building information based on containment ID
        $query = "SELECT b.bin, ST_AsText(b.geom) AS geom
            FROM building_info.buildings b
            WHERE b.deleted_at IS NULL
            AND b.bin IN (
                SELECT bc.bin
                FROM building_info.build_contains bc
                JOIN fsm.containments c ON bc.containment_id = c.id::CHARACTER VARYING AND c.deleted_at IS NULL
                WHERE bc.deleted_at IS NULL"
                . " AND c.id = ?"
                . ")";
        // Executing the SQL query with the containmentId as a parameter
        $results = DB::select($query, [$containmentId]);

        $data = array();

        foreach ($results as $row) {
            $coord = array();
            $coord['bin'] = $row->bin;
            $coord['geom'] = $row->geom;
            $data[] = $coord;
        }

        return $data;
    }

    /**
     * Retrieves the coordinates of buildings associated with a main building identified by its BIN (Building Identification Number).
     *
     * @return array An array containing the latitude and longitude coordinates of associated buildings.
     */

    public function getAssociatedToMainbuilding()
    {
        $bin = request()->bin;
        // SQL query to retrieve the latitude and longitude coordinates of buildings associated with the given bin
        $query = "SELECT ST_Y(ST_Centroid(ST_Transform (geom, 4326))) as lat, ST_X(ST_Centroid(ST_Transform (geom, 4326))) as long"
                    . " FROM building_info.buildings"
                    . " WHERE bin IN"
                    . " ("
                    . " SELECT building_associated_to"
                    . " FROM building_info.buildings"
                    . " WHERE bin = ?"
                    . " )";
        // Execute the SQL query with the bin value as a parameter
        $results = DB::select($query, [$bin]);
        $data = array();
        foreach ($results as $row) {
            $coord = array();
            $coord['lat'] = $row->lat;
            $coord['long'] = $row->long;
            $data[] = $coord;
        }
        return $data;
    }

    /**
     * Retrieves the extent and centroid coordinates of a containment specified by its ID and value.
     *
     * @param mixed $id The column name or identifier for the containment.
     * @param mixed $value The value corresponding to the containment.
     * @return array An array containing the extent (xmin, ymin, xmax, ymax) and centroid coordinates (lat, long) of the containment.
     */
    public function containmentExtent($id, $value)
    {
            // Get the minimum X coordinate of the containment extent
            $xmin = array_pluck(DB::select(DB::raw("select st_xmin(ST_Extent(geom)) from fsm.containments where " . $id . " = '" . $value . "'")), 'st_xmin')[0];
            // Get the minimum Y coordinate of the containment extent
            $ymin = array_pluck(DB::select(DB::raw("select st_ymin(ST_Extent(geom)) from fsm.containments where " . $id . " = '" . $value . "'")), 'st_ymin')[0];
            // Get the maximum X coordinate of the containment extent
            $xmax = array_pluck(DB::select(DB::raw("select st_xmax(ST_Extent(geom)) from fsm.containments where " . $id . " = '" . $value . "'")), 'st_xmax')[0];
            // Get the maximum Y coordinate of the containment extent
            $ymax = array_pluck(DB::select(DB::raw("select st_ymax(ST_Extent(geom)) from fsm.containments where " . $id . " = '" . $value . "'")), 'st_ymax')[0];

             // Get the latitude and longitude of the centroid of the containment
            $lat = array_pluck(DB::select(DB::raw("select ST_Y (ST_Transform (geom, 4326)) as lat from fsm.containments where " . $id . " = '" . $value . "'")), 'lat')[0];
            $long = array_pluck(DB::select(DB::raw("select ST_X (ST_Transform (geom, 4326)) as long from fsm.containments where " . $id . " = '" . $value . "'")), 'long')[0];

               // Return an array containing the extent coordinates and centroid coordinates
            return array(
                'xmin' => $xmin,
                'ymin' => $ymin,
                'xmax' => $xmax,
                'ymax' => $ymax,
                'lat' => $lat,
                'long' => $long,
            );
    }

    /**
     * Retrieves the extent and centroid coordinates of buildings based on a given attribute value.
     *
     * @param string $bin The attribute column name.
     * @param mixed $value The attribute value.
     * @return array An array containing the extent (xmin, ymin, xmax, ymax) and centroid (lat, long) coordinates.
     */
    public function buildingExtent($bin, $value) {


            $val1 = "building_info.buildings";
             // Get the minimum X coordinate of the building extent
            $xmin = array_pluck(DB::select(DB::raw("select st_xmin(ST_Extent(geom)) from building_info.buildings where " . $bin . " = '" . $value . "'")), 'st_xmin')[0];
             // Get the minimum Y coordinate of the building extent
            $ymin = array_pluck(DB::select(DB::raw("select st_ymin(ST_Extent(geom)) from building_info.buildings where " . $bin . " = '" . $value . "'")), 'st_ymin')[0];
             // Get the maximum X coordinate of the building extent
            $xmax = array_pluck(DB::select(DB::raw("select st_xmax(ST_Extent(geom)) from building_info.buildings where " . $bin . " = '" . $value . "'")), 'st_xmax')[0];
             // Get the maximum Y coordinate of the building extent
            $ymax = array_pluck(DB::select(DB::raw("select st_ymax(ST_Extent(geom)) from building_info.buildings where " . $bin . " = '" . $value . "'")), 'st_ymax')[0];

            // Get the latitude and longitude of the centroid of the building
            $lat = array_pluck(DB::select(DB::raw("select ST_Y (ST_Transform (ST_Centroid(geom), 4326)) as lat from building_info.buildings where " . $bin . " = '" . $value . "'")), 'lat')[0];
            $long = array_pluck(DB::select(DB::raw("select ST_X (ST_Transform (ST_Centroid(geom), 4326)) as long from building_info.buildings where " . $bin . " = '" . $value . "'")), 'long')[0];

            // Return an array containing the extent coordinates and centroid coordinates
            return array(
                'xmin' => $xmin,
                'ymin' => $ymin,
                'xmax' => $xmax,
                'ymax' => $ymax,
                'lat' => $lat,
                'long' => $long,
            );

    }

    /**
     * Retrieves the extent (bounding box) and geometry of a linestring feature based on a given layer, code, and value.
     *
     * @param string $layer The name of the layer containing the linestring features.
     * @param string $code The attribute code used for filtering the linestring features.
     * @param string $value The value of the attribute used for filtering the linestring features.
     * @return array An array containing the xmin, ymin, xmax, ymax values of the extent, and the geometry of the linestring feature.
     */

    public function lineStringExtent($layer, $code, $value) 
    {
           // Retrieve the minimum x-coordinate of the bounding box of the geometry
            $xmin = array_pluck(DB::select(DB::raw("select st_xmin(ST_Extent(geom)) from " . $layer . " where " . $code . " = '" . $value . "'")), 'st_xmin')[0];

            // Retrieve the minimum y-coordinate of the bounding box of the geometry
            $ymin = array_pluck(DB::select(DB::raw("select st_ymin(ST_Extent(geom)) from " . $layer . " where " . $code . " = '" . $value . "'")), 'st_ymin')[0];

            // Retrieve the maximum x-coordinate of the bounding box of the geometry
            $xmax = array_pluck(DB::select(DB::raw("select st_xmax(ST_Extent(geom)) from " . $layer . " where " . $code . " = '" . $value . "'")), 'st_xmax')[0];

            // Retrieve the maximum y-coordinate of the bounding box of the geometry
            $ymax = array_pluck(DB::select(DB::raw("select st_ymax(ST_Extent(geom)) from " . $layer . " where " . $code . " = '" . $value . "'")), 'st_ymax')[0];

            // Retrieve the geometry itself as a WKT (Well-Known Text) string
            $geom = array_pluck(DB::select(DB::raw("select ST_AsText(geom) AS geom from " . $layer . " where " . $code . " = '" . $value . "'")), 'geom')[0];

            // Return an array containing the bounding box coordinates and the geometry
            return array(
                'xmin' => $xmin, 
                'ymin' => $ymin, 
                'xmax' => $xmax, 
                'ymax' => $ymax, 
                'geom' => $geom, 
            );


    }
    /**
     * Retrieves the extent and centroid coordinates of layer based on a given attribute value.
     *
     * @param string $bin The attribute column name.
     * @param mixed $value The attribute value.
     * @return array An array containing the extent (xmin, ymin, xmax, ymax) and centroid (lat, long) coordinates.
     */
    public function polygonExtent($layer, $id, $value) {

            if($layer == 'low_income_communities_layer' ){
            $val1 = "layer_info.low_income_communities";}
             // Get the minimum X coordinate of the building extent
            $xmin = array_pluck(DB::select(DB::raw("select st_xmin(ST_Extent(geom)) from $val1 where " . $id . " = '" . $value . "'")), 'st_xmin')[0];
             // Get the minimum Y coordinate of the building extent
            $ymin = array_pluck(DB::select(DB::raw("select st_ymin(ST_Extent(geom)) from $val1 where " . $id . " = '" . $value . "'")), 'st_ymin')[0];
             // Get the maximum X coordinate of the building extent
            $xmax = array_pluck(DB::select(DB::raw("select st_xmax(ST_Extent(geom)) from $val1 where " . $id . " = '" . $value . "'")), 'st_xmax')[0];
             // Get the maximum Y coordinate of the building extent
            $ymax = array_pluck(DB::select(DB::raw("select st_ymax(ST_Extent(geom)) from $val1 where " . $id . " = '" . $value . "'")), 'st_ymax')[0];

            // Get the latitude and longitude of the centroid of the building
            $lat = array_pluck(DB::select(DB::raw("select ST_Y (ST_Transform (ST_Centroid(geom), 4326)) as lat from $val1 where " . $id . " = '" . $value . "'")), 'lat')[0];
            $long = array_pluck(DB::select(DB::raw("select ST_X (ST_Transform (ST_Centroid(geom), 4326)) as long from $val1 where " . $id . " = '" . $value . "'")), 'long')[0];

            // Return an array containing the extent coordinates and centroid coordinates
            return array(
                'xmin' => $xmin,
                'ymin' => $ymin,
                'xmax' => $xmax,
                'ymax' => $ymax,
                'lat' => $lat,
                'long' => $long,
            );

    }
    /**
     * Returns the extent of containment survey based on given parameters.
     *
     * @param mixed $param The parameter used for querying containment survey.
     * @return array Containment survey extent containing xmin, ymin, xmax, ymax, latitude, and longitude.
     */

    public function containmentSurveyExtent($param) 
    {
        $xmin = $ymin = $xmax = $ymax = $lat = $long = '';
        // Retrieve containment survey based on provided criteria ($val2 and $val3)
            $containmentSurvey = ContainmentSurvey::where($val2, $val3)->first();
            if ($containmentSurvey) {
        // Assign latitude and longitude from the survey to respective variables
                $lat = $containmentSurvey->latitude;
                $long = $containmentSurvey->longitude;
            }

            return array(
                'xmin' => $xmin,
                'ymin' => $ymin,
                'xmax' => $xmax,
                'ymax' => $ymax,
                'lat' => $lat,
                'long' => $long,
            );

    }

    /**
     * Retrieves the extent (bounding box) of points for a given layer and identifier.
     *
     * @param string $layer The name of the database table containing the points.
     * @param string $id The column name used for filtering points.
     * @param mixed $value The value to filter points by.
     * @return array The extent of points represented as an associative array with keys 'xmin', 'ymin', 'xmax', 'ymax'.
     */
    public function pointsExtent($layer, $id, $value) {

           // Extracting the minimum x-coordinate of the bounding box of a geometry
            $xmin = array_pluck(DB::select(DB::raw("select st_xmin(ST_Extent(geom)) from " . $layer . " where " . $id . " = '" . $value . "'")), 'st_xmin')[0];

            // Extracting the minimum y-coordinate of the bounding box of a geometry
            $ymin = array_pluck(DB::select(DB::raw("select st_ymin(ST_Extent(geom)) from " . $layer . " where " . $id . " = '" . $value . "'")), 'st_ymin')[0];

            // Extracting the maximum x-coordinate of the bounding box of a geometry
            $xmax = array_pluck(DB::select(DB::raw("select st_xmax(ST_Extent(geom)) from " . $layer . " where " . $id . " = '" . $value . "'")), 'st_xmax')[0];

            // Extracting the maximum y-coordinate of the bounding box of a geometry
            $ymax = array_pluck(DB::select(DB::raw("select st_ymax(ST_Extent(geom)) from " . $layer . " where " . $id . " = '" . $value . "'")), 'st_ymax')[0];

            // Returning the extracted coordinates as an associative array
            return array(
                'xmin' => $xmin,
                'ymin' => $ymin,
                'xmax' => $xmax,
                'ymax' => $ymax,
            );

    }

    /**
     * Retrieves containment buildings based on a specific field-value pair.
     *
     * @param string $field The field name to filter by.
     * @param string $value The value to filter by.
     * @return array An array of containment connected to building coordinates.
     */

    public function getContainmentBuildings($field, $value) 
    {
        // SQL query to select the bin, latitude, and longitude of buildings
        $query = "SELECT bin, ST_Y (ST_Transform (ST_Centroid(b.geom), 4326)) as lat, ST_X (ST_Transform (ST_Centroid(b.geom), 4326)) as long
            FROM building_info.buildings b
            WHERE b.deleted_at IS NULL
            AND b.bin IN (
                SELECT bc.bin
                FROM building_info.build_contains bc
                JOIN fsm.containments c ON bc.containment_id = c.id::CHARACTER VARYING AND c.deleted_at IS NULL
                WHERE bc.deleted_at IS NULL"
            . " AND c." . $field . " = '" . $value . "'"
            . ")";

        $results = DB::select($query);

        $data = array();

        foreach ($results as $row) {
            $coord = array();
            $coord['lat'] = $row->lat;
            $coord['long'] = $row->long;
            $data[] = $coord;
        }

        return $data;
    }

    /**
     * Retrieves information about the nearest road to a specified latitude and longitude.
     *
     * @param float $lat The latitude of the point to search from.
     * @param float $long The longitude of the point to search from.
     * @return array An associative array containing the latitude and longitude of the nearest road.
     *               If no results are found, empty strings are returned for both latitude and longitude.
     */

    public function getNearestRoad($lat,$long)
    {
        $r_lat = $r_long = '';
        // SQL query to select road information and closest point   
        $r_query = "SELECT r.code, ST_AsEWKT(ref_geom),r.name, ST_Y(ST_ClosestPoint(ST_Transform(r.geom,4326), ref_geom)) As r_lat, ST_X(ST_ClosestPoint(ST_Transform(r.geom,4326), ref_geom)) As r_long"
            . " FROM utility_info.roads As r, ST_Transform(ST_SetSRID(ST_Point(?,?),4326),4326) AS ref_geom"
            . " WHERE ST_DWithin(ST_Transform(r.geom,4326), ref_geom, 1000)"
            . " ORDER BY ST_Distance(ST_Transform(r.geom,4326),ref_geom) LIMIT 1";

        $r_results = DB::select($r_query, [$long,$lat]);

        if (count($r_results) > 0) {
            $r_lat = $r_results[0]->r_lat;
            $r_long = $r_results[0]->r_long;
        }

        return array(
            'lat' => $r_lat,
            'long' => $r_long
        );

    }

    /**
     * Retrieves application containment information based on the user's role.
     * @return array An array containing application containment information including
     *               application ID, house number, service provider, application date,
     *               emptying status, feedback status, sludge collection status,
     *               latitude, and longitude.
     */

    public function getApplicationContainments()
    {
        // Check if the user has either 'Service Provider - Admin' or 'Service Provider - Help Desk' role
        if (Auth::user()->hasRole('Service Provider - Admin') || Auth::user()->hasRole('Service Provider - Help Desk') ) {
             // If the user has one of the specified roles, construct WHERE clause to filter by service_provider_id
            $whereUser = "AND a.service_provider_id = " . Auth::user()->service_provider_id;
        } else {
            // If the user doesn't have the specified roles, no additional filtering is needed
            $whereUser = "";
        }
        // Construct the SQL query to retrieve application data.
        $query = "SELECT 
                s.company_name, a.id AS application_id,a.bin,a.application_date, a.emptying_status, a.feedback_status, a.sludge_collection_status, c.id AS containment_id, ST_X(c.geom) AS long, ST_Y(c.geom) AS lat
            FROM fsm.applications a
            LEFT JOIN building_info.buildings b ON a.bin = b.bin AND b.deleted_at IS NULL
            LEFT JOIN building_info.build_contains bc ON bc.bin = b.bin AND bc.deleted_at IS NULL
            LEFT JOIN fsm.containments c ON bc.containment_id = c.id AND c.deleted_at IS NULL
            LEFT JOIN auth.users u ON u.id = a.user_id AND u.deleted_at IS NULL
            LEFT JOIN fsm.service_providers s ON s.id = a.service_provider_id AND s.deleted_at IS NULL
            WHERE a.deleted_at IS NULL"
            . " $whereUser";

        $results = DB::select($query);

        $data = array();
        // Iterate over the query results to format each row into a structured array.
        foreach ($results as $row) {
            $coord = array();
            $coord['application_id'] = $row->application_id;
            $coord['bin'] = $row->bin;
            $coord['service_provider'] = $row->company_name;
            $coord['application_date'] = $row->application_date;
            $coord['emptying_status'] = $row->emptying_status;
            $coord['feedback_status'] = $row->feedback_status;
            $coord['sludge_collection_status'] = $row->sludge_collection_status;
            $coord['lat'] = $row->lat;
            $coord['long'] = $row->long;
            $data[] = $coord;
        }

        return $data;
    }

    /**
     * Retrieves containment information for applications filtered by year and month.
     *
     * @param int|null $year The year to filter the applications by.
     * @param int|null $month The month to filter the applications by.
     * @return array An array containing information about applications and their corresponding containments.
     */

    public function getApplicationContainmentsYearMonth($year, $month)
    {
        // initialize the variable 
        $whereCondition = "";

        // Add conditions based on the $year variable
        if ($year) {
            $whereCondition .= " AND extract(year from application_date) = '$year'";
        }
        // Add conditions based on the $month variable
        if ($month) {
            $whereCondition .= " AND extract(month from application_date) = '$month'";
        }
        // Check if the user has specific roles, and adjust the query accordingly
        if (Auth::user()->hasRole('Service Provider - Admin') || Auth::user()->hasRole('Service Provider - Help Desk') ) {
              // If user has specific roles, add conditions related to the user's service provider ID
            $whereUser = " AND a.service_provider_id = " . Auth::user()->service_provider_id;
        } else {
            $whereUser = "";
        }
        // Constructing the SQL query to retrieve application data with certain conditions
        $query = "SELECT 
                a.id AS application_id,a.bin, s.company_name, a.emptying_status, a.feedback_status, a.sludge_collection_status, c.id AS containment_id, ST_X(c.geom) AS long, ST_Y(c.geom) AS lat
            FROM fsm.applications a
            LEFT JOIN building_info.buildings b ON a.bin = b.bin AND b.deleted_at IS NULL
            LEFT JOIN building_info.build_contains bc ON bc.bin = b.bin AND bc.deleted_at IS NULL
            LEFT JOIN fsm.containments c ON bc.containment_id = c.id AND c.deleted_at IS NULL
            LEFT JOIN auth.users u ON u.id = a.user_id AND u.deleted_at IS NULL
            LEFT JOIN fsm.service_providers s ON s.id = a.service_provider_id AND s.deleted_at IS NULL
            WHERE a.deleted_at IS NULL"
            . " $whereCondition "
            . " $whereUser ";
        $results = DB::select($query);
        $data = array();
        // Iterate over the query results and format the data
        foreach ($results as $row) {
            $coord = array();
            $coord['application_id'] = $row->application_id;
            $coord['bin'] = $row->bin;
            $coord['service_provider'] = $row->company_name;
            $coord['emptying_status'] = $row->emptying_status;
            $coord['feedback_status'] = $row->feedback_status;
            $coord['sludge_collection_status'] = $row->sludge_collection_status;
            $coord['lat'] = $row->lat;
            $coord['long'] = $row->long;
            $data[] = $coord;
        }

        return $data;
    }

    /**
     * Retrieves applications that are not yet marked as sludge collection and have been emptied by service providers
     * on the specified start date.
     *
     * @param string $start_date The start date to filter applications by.
     * @return \Illuminate\Http\JsonResponse JSON response containing data about applications and service providers.
     */

    public function getApplicationNotTPOnDate($start_date)
    {
        // Construct the SQL query to retrieve application data along with relevant details
        $query = "SELECT 
                a.id AS application_id,a.application_date,a.bin,e.emptied_date,s.id,s.company_name, a.emptying_status, a.feedback_status, c.id AS containment_id, ST_X(c.geom) AS long, ST_Y(c.geom) AS lat
            FROM fsm.applications a
            LEFT JOIN building_info.buildings b ON a.bin = b.bin AND b.deleted_at IS NULL
            LEFT JOIN building_info.build_contains bc ON bc.bin = b.bin AND bc.deleted_at IS NULL
            LEFT JOIN fsm.containments c ON bc.containment_id = c.id AND c.deleted_at IS NULL
            LEFT JOIN auth.users u ON u.id = a.user_id AND u.deleted_at IS NULL
            LEFT JOIN fsm.service_providers s ON s.id = a.service_provider_id AND s.deleted_at IS NULL
            LEFT JOIN fsm.emptyings e ON e.application_id = a.id AND e.deleted_at IS NULL
            WHERE a.deleted_at IS NULL"
            . " AND a.application_date = '$start_date'"
            . " AND a.emptying_status > 0 AND (a.sludge_collection_status = 0 OR a.sludge_collection_status IS NULL)";

        $results = DB::select($query);
        // Construct another query to retrieve service provider details
        $service_providers = DB::select("SELECT DISTINCT s.company_name
                FROM fsm.applications a
                LEFT JOIN building_info.buildings b ON a.bin = b.bin AND b.deleted_at IS NULL
                LEFT JOIN building_info.build_contains bc ON bc.bin = b.bin AND bc.deleted_at IS NULL
                LEFT JOIN fsm.containments c ON bc.containment_id = c.id AND c.deleted_at IS NULL
                LEFT JOIN auth.users u ON u.id = a.user_id AND u.deleted_at IS NULL
                LEFT JOIN fsm.service_providers s ON s.id = a.service_provider_id AND s.deleted_at IS NULL
                LEFT JOIN fsm.emptyings e ON e.application_id = a.id AND e.deleted_at IS NULL
                WHERE a.deleted_at IS NULL"
            . " AND a.application_date = '$start_date'"
            . " AND a.emptying_status > 0 AND (a.sludge_collection_status = 0 OR a.sludge_collection_status IS NULL)"
            . " GROUP BY s.company_name");

        $data = array();
        // Iterate through each result to structure the data
        foreach ($results as $row) {
            $coord = array();
            $coord['application_id'] = $row->application_id;
            $coord['bin'] = $row->bin;
            $coord['emptying_status'] = $row->emptying_status;
            $coord['feedback_status'] = $row->feedback_status;
            $coord['application_date'] = $row->application_date;
            $coord['emptying_date'] = $row->emptied_date;
            $coord['service_provider'] = $row->company_name;
            $coord['service_provider_id'] = $row->id;
            $coord['lat'] = $row->lat;
            $coord['long'] = $row->long;
            $data[] = $coord;
        }
        // Return the structured data and service providers as JSON response
        return response()->json(
            [
                "data"=>$data,
                "service_providers"=>$service_providers
            ]);
    }

    /**
     * Retrieves application information for a specified date.
     *
     * @param string $start_date The start date to search for applications.
     * @return array An array containing application information, including application ID, house number, service provider,
     *               emptying status, feedback status, sludge collection status, latitude, and longitude.
     */
    public function getApplicationOnDate($start_date)
    {
        // Check if the user has either 'Service Provider - Admin' or 'Service Provider - Help Desk' role
        // If so, set $whereUser to restrict results to the user's service provider ID, else set it to an empty string
        if (Auth::user()->hasRole('Service Provider - Admin') || Auth::user()->hasRole('Service Provider - Help Desk') ) {
            $whereUser = " AND a.service_provider_id = " . Auth::user()->service_provider_id;
        } else {
            $whereUser = "";
        }
        // Construct the SQL query to retrieve application data along with related information:
        $query = "SELECT 
                    a.id AS application_id,a.bin, s.company_name, a.emptying_status, a.feedback_status, a.sludge_collection_status, c.id AS containment_id, ST_X(c.geom) AS long, ST_Y(c.geom) AS lat
                FROM fsm.applications a
                LEFT JOIN building_info.buildings b ON a.bin = b.bin AND b.deleted_at IS NULL
                LEFT JOIN building_info.build_contains bc ON bc.bin = b.bin AND bc.deleted_at IS NULL
                LEFT JOIN fsm.containments c ON bc.containment_id = c.id AND c.deleted_at IS NULL
                LEFT JOIN auth.users u ON u.id = a.user_id AND u.deleted_at IS NULL
                LEFT JOIN fsm.service_providers s ON s.id = a.service_provider_id AND s.deleted_at IS NULL
                WHERE a.deleted_at IS NULL"
            . " AND application_date = '$start_date' "
            . " $whereUser ";

        $results = DB::select($query);

        $data = array();
        // Iterate through the query results and structure the data
        foreach ($results as $row) {
            $coord = array();
            $coord['application_id'] = $row->application_id;
            $coord['bin'] = $row->bin;
            $coord['service_provider'] = $row->company_name;
            $coord['emptying_status'] = $row->emptying_status;
            $coord['feedback_status'] = $row->feedback_status;
            $coord['sludge_collection_status'] = $row->sludge_collection_status;
            $coord['lat'] = $row->lat;
            $coord['long'] = $row->long;
            $data[] = $coord;
        }

        return $data;
    }

    /**
     * Retrieves application information for applications that are not yet marked as TP (Treatment Plant) collection.
     * @return \Illuminate\Http\JsonResponse JSON response containing application data and service provider information.
     */

    public function getApplicationNotTP()
    {
         // Check if the user has either 'Service Provider - Admin' or 'Service Provider - Help Desk' role
        // If so, set $whereUser to restrict results to the user's service provider ID, else set it to an empty string
        if (Auth::user()->hasRole('Service Provider - Admin') || Auth::user()->hasRole('Service Provider - Help Desk') ) {
            $whereUser = " AND a.service_provider_id = " . Auth::user()->service_provider_id;
        } else {
            $whereUser = "";
        }
         // Construct the SQL query to retrieve application data along with related information:
        $query = "SELECT 
                a.id AS application_id,a.bin,a.application_date,s.id,s.company_name,e.emptied_date, c.id AS containment_id, ST_X(c.geom) AS long, ST_Y(c.geom) AS lat
            FROM fsm.applications a
            LEFT JOIN building_info.buildings b ON a.bin = b.bin AND b.deleted_at IS NULL
            LEFT JOIN building_info.build_contains bc ON bc.bin = b.bin AND bc.deleted_at IS NULL
            LEFT JOIN fsm.containments c ON bc.containment_id = c.id AND c.deleted_at IS NULL
            LEFT JOIN auth.users u ON u.id = a.user_id AND u.deleted_at IS NULL
            LEFT JOIN fsm.service_providers s ON s.id = a.service_provider_id AND s.deleted_at IS NULL
            LEFT JOIN fsm.emptyings e ON e.application_id = a.id AND e.deleted_at IS NULL
            WHERE a.deleted_at IS NULL"
            . " $whereUser"
            . " AND a.emptying_status=true AND a.sludge_collection_status = false";

        $results = DB::select($query);
        // Construct another query to retrieve service provider details
        $service_providers = DB::select("SELECT DISTINCT s.company_name
                FROM fsm.applications a
                LEFT JOIN building_info.buildings b ON a.bin = b.bin AND b.deleted_at IS NULL
                LEFT JOIN building_info.build_contains bc ON bc.bin = b.bin AND bc.deleted_at IS NULL
                LEFT JOIN fsm.containments c ON bc.containment_id = c.id AND c.deleted_at IS NULL
                LEFT JOIN auth.users u ON u.id = a.user_id AND u.deleted_at IS NULL
                LEFT JOIN fsm.service_providers s ON s.id = a.service_provider_id AND s.deleted_at IS NULL
                LEFT JOIN fsm.emptyings e ON e.application_id = a.id AND e.deleted_at IS NULL
                WHERE a.deleted_at IS NULL"
            . " AND a.emptying_status > 0 AND (a.sludge_collection_status = 0 OR a.sludge_collection_status IS NULL)"
            . " $whereUser "
            . " GROUP BY s.company_name");
        
        $data = array();
        // Iterate over the results and format them into an array
        foreach ($results as $row) {
            $coord = array();
            $coord['application_id'] = $row->application_id;
            $coord['bin'] = $row->bin;
            $coord['application_date'] = $row->application_date;
            $coord['service_provider'] = $row->company_name;
            $coord['service_provider_id'] = $row->id;
            $coord['emptying_date'] = $row->emptied_date;
            $coord['lat'] = $row->lat;
            $coord['long'] = $row->long;
            $data[] = $coord;
        }
        // Return the formatted data and the list of service providers as JSON response
        return response()->json(
            [
                "data"=>$data,
                "service_providers"=>$service_providers
            ]);
    }

    /**
     * Retrieves application data for containments not yet emptied in a specific year and month.
     *
     * @param int $year The year to filter the applications by.
     * @param int $month The month to filter the applications by.
     * @return \Illuminate\Http\JsonResponse A JSON response containing application data and service providers.
     */

    public function getApplicationNotTPContainmentsYearMonth($year, $month)
    {
        // Define the initial WHERE clause based on certain conditions
        $whereCondition = " AND a.emptying_status > 0 AND (a.sludge_collection_status = 0 OR a.sludge_collection_status IS NULL)";
        // Append condition for filtering by year if provided
        if ($year) {
            $whereCondition .= " AND extract(year from application_date) = '$year'";
        }
        // Append condition for filtering by month if provided
        if ($month) {
            $whereCondition .= " AND extract(month from application_date) = '$month'";
        }
         // Construct the SQL query to retrieve application data along with related information:
        $query = "SELECT 
                a.id AS application_id,a.bin,a.application_date,e.emptied_date,s.id,s.company_name, a.emptying_status, a.feedback_status, c.id AS containment_id, ST_X(c.geom) AS long, ST_Y(c.geom) AS lat
            FROM fsm.applications a
            LEFT JOIN building_info.buildings b ON a.bin = b.bin AND b.deleted_at IS NULL
            LEFT JOIN building_info.build_contains bc ON bc.bin = b.bin AND bc.deleted_at IS NULL
            LEFT JOIN fsm.containments c ON bc.containment_id = c.id AND c.deleted_at IS NULL
            LEFT JOIN auth.users u ON u.id = a.user_id AND u.deleted_at IS NULL
            LEFT JOIN fsm.service_providers s ON s.id = a.service_provider_id AND s.deleted_at IS NULL
            LEFT JOIN fsm.emptyings e ON e.application_id = a.id AND e.deleted_at IS NULL
            WHERE a.deleted_at IS NULL"
            . " $whereCondition";

        $results = DB::select($query);
        // Construct another query to retrieve service provider details
        $service_providers = DB::select("SELECT DISTINCT s.company_name
                FROM fsm.applications a
                LEFT JOIN building_info.buildings b ON a.bin = b.bin AND b.deleted_at IS NULL
                LEFT JOIN building_info.build_contains bc ON bc.bin = b.bin AND bc.deleted_at IS NULL
                LEFT JOIN fsm.containments c ON bc.containment_id = c.id AND c.deleted_at IS NULL
                LEFT JOIN auth.users u ON u.id = a.user_id AND u.deleted_at IS NULL
                LEFT JOIN fsm.service_providers s ON s.id = a.service_provider_id AND s.deleted_at IS NULL
                LEFT JOIN fsm.emptyings e ON e.application_id = a.id AND e.deleted_at IS NULL
                WHERE a.deleted_at IS NULL"
                . " $whereCondition"
                . " GROUP BY s.company_name");

        $data = array();
        // Iterate over the results and format them into an array
        foreach ($results as $row) {
            $coord = array();
            $coord['application_id'] = $row->application_id;
            $coord['bin'] = $row->bin;
            $coord['emptying_status'] = $row->emptying_status;
            $coord['feedback_status'] = $row->feedback_status;
            $coord['application_date'] = $row->application_date;
            $coord['emptying_date'] = $row->emptied_date;
            $coord['service_provider'] = $row->company_name;
            $coord['service_provider_id'] = $row->id;
            $coord['lat'] = $row->lat;
            $coord['long'] = $row->long;
            $data[] = $coord;
        }
        // Return the formatted data and the list of service providers as JSON response

        return response()->json(
            [
                "data"=>$data,
                "service_providers"=>$service_providers
            ]);
    }

    /**
     * Retrieves the count of unique containment units that have been emptied.
     *
     * @param string $geom The geometry representing the area of interest.
     * @param string $whereUser Additional SQL conditions based on user.
     * @return int The count of unique containment units that have been emptied.
     */

    public function getUniqueContainmentEmptiedCount($geom, $whereUser)
    {
          // Construct the SQL query to retrieve the count of unique containment units that have been emptied
           
           $query = "SELECT COUNT(DISTINCT c.id) AS total_count
                FROM fsm.containments c 
                LEFT JOIN building_info.build_contains bc ON bc.containment_id = c.id AND bc.deleted_at IS NULL
                LEFT JOIN building_info.buildings b ON bc.bin = b.bin AND b.deleted_at IS NULL
                LEFT JOIN fsm.applications ap ON ap.bin = b.bin AND ap.deleted_at IS NULL
                LEFT JOIN fsm.feedbacks fb ON fb.application_id = ap.id AND fb.deleted_at IS NULL
                LEFT JOIN auth.users u ON u.id = fb.user_id AND u.deleted_at IS NULL
                LEFT JOIN fsm.service_providers s ON s.id = ap.service_provider_id AND s.deleted_at IS NULL
                WHERE c.deleted_at IS NULL"
                . " AND (ST_Intersects(c.geom, ST_GeomFromText('" . $geom . "', 4326)))"
                . " $whereUser"
                . " AND c.emptied_status is true";
           
            $feedbacks = DB::select($query);
            if(!empty($feedbacks)) {
            return $feedbacks[0]->total_count;
            } else {
                return 0;
            }
    }
    
    /**
     * Retrieves the count of feedbacks based on the provided geometry and user-specific conditions.
     *
     * @param string $geom The geometry in Well-Known Text (WKT) format for spatial operations.
     * @param string $whereUser Additional conditions specific to users for filtering feedbacks.
     * @return int The total count of feedbacks satisfying the given criteria.
     */
    public function getFeedbacksCount($geom, $whereUser)
    {
        // query to count feedbacks based on the provided geometry and user condition
        $query = "SELECT COUNT(fb.id) AS total_count"
                . " FROM fsm.feedbacks fb "
                . " LEFT JOIN fsm.applications ap ON fb.application_id = ap.id"
                . " LEFT JOIN building_info.buildings b"
                . " ON ap.bin = b.bin"
                . " LEFT JOIN auth.users u"
                . " ON u.id = fb.user_id"
                . " LEFT JOIN fsm.service_providers s"
                . " ON s.id = ap.service_provider_id"
                . " WHERE (ST_Intersects(b.geom, ST_GeomFromText('" . $geom . "', 4326)))"
                . " $whereUser";
             
           $feedbacks = DB::select($query);
           return $feedbacks[0]->total_count;
           
    }
    
    /**
     * Retrieves feedback data related to FSM (Fecal Sludge Management) service quality based on provided geometry and optional user conditions.
     *
     * @param string $geom The geometry in Well-Known Text (WKT) format to determine spatial intersection.
     * @param string $whereUser Additional conditions for user filtering (optional).
     * @return array Returns an array of feedback data including quality indicators and their counts.
     */

    public function getFeedbackFsmServiceQuality($geom, $whereUser)
    {
        // Constructing SQL query to retrieve feedback FSM service quality
        $query = "SELECT count(CASE WHEN fb.fsm_service_quality THEN 1 END) as yes,count(CASE WHEN NOT fb.fsm_service_quality THEN 1 END) as no"
                . " FROM fsm.feedbacks fb"
                . " LEFT JOIN fsm.applications ap ON fb.application_id = ap.id"
                . " LEFT JOIN building_info.buildings b"
                . " ON ap.bin = b.bin"
                . " LEFT JOIN auth.users u"
                . " ON u.id = fb.user_id"
                . " LEFT JOIN fsm.service_providers s"
                . " ON s.id = ap.service_provider_id"
                . " WHERE (ST_Intersects(b.geom, ST_GeomFromText('" . $geom . "', 4326)))"
                . " $whereUser";

            return DB::select($query);
    }
    
    public function getFeedbackServiceQualityItoPrice($geom, $whereUser)
    {
        $query2 = "SELECT  COUNT(fb.id)"
                . " FROM fsm.feedbacks fb ON fb.srvc_qlty_ito_prc"
                . " LEFT JOIN fsm.applications ap ON fb.application_id = ap.id"
                . " LEFT JOIN building_info.buildings b"
                . " ON ap.bin = b.bin"
                . " LEFT JOIN auth.users u"
                . " ON u.id = fb.user_id"
                . " LEFT JOIN fsm.service_providers s"
                . " ON s.id = ap.service_provider_id"
                . " WHERE (ST_Intersects(b.geom, ST_GeomFromText('" . $geom . "', 4326)))"
                . " $whereUser";
                

            return DB::select($query2);
    }


    /**
     * Retrieves the count of sanitation workers wearing personal protective equipment (PPE) based on feedback data.
     *
     * @return array An array containing the count of workers wearing PPE and not wearing PPE.
     */

    public function getFeedbackSanitationWorkersPpe($geom, $whereUser){

        $query = "SELECT count(CASE WHEN fb.wear_ppe THEN 1 END) as yes,count(CASE WHEN NOT fb.wear_ppe THEN 1 END) as no"
            . " FROM fsm.feedbacks fb"
            . " LEFT JOIN fsm.applications ap ON fb.application_id = ap.id"
            . " LEFT JOIN building_info.buildings b"
            . " ON ap.bin = b.bin"
            . " LEFT JOIN auth.users u"
            . " ON u.id = fb.user_id"
            . " LEFT JOIN fsm.service_providers s"
            . " ON s.id = ap.service_provider_id"
            . " WHERE (ST_Intersects(b.geom, ST_GeomFromText('" . $geom . "', 4326)))"
            . " $whereUser";
        return DB::select($query);
    }

    /**
     * Retrieves summary information about buildings along specified road codes.
     * 
     * @param array $roadCodes An array of road codes for which to retrieve building information.
     * @return array An array containing buildings information and summary HTML
     */
    public function getBuildingsToRoadSummary($roadCodes)
    {
        // query to get detailed information about buildings
            $building_query = "SELECT b.bin, ST_AsText(b.geom) AS geom, st.type AS structype, COUNT(*)::integer AS count,
                         COUNT(b.bin) filter (where b.sanitation_system_id = '1')::integer  AS sewer_network,
            COUNT(b.bin) filter (where b.sanitation_system_id = '2')::integer  AS drain_network,
            COUNT(b.bin) filter (where b.sanitation_system_id = '3')::integer AS septic_tank,
            COUNT(b.bin) filter (where b.sanitation_system_id = '4')::integer AS pit_holding_tank,
            COUNT(b.bin) filter (where b.sanitation_system_id = '5')::integer AS onsite_treatment,
            COUNT(b.bin) filter (where b.sanitation_system_id = '6')::integer AS composting_toilet,
            COUNT(b.bin) filter (where b.sanitation_system_id = '7')::integer AS water_body,
            COUNT(b.bin) filter (where b.sanitation_system_id = '8')::integer AS open_ground,
            COUNT(b.bin) filter (where b.sanitation_system_id = '9')::integer AS community_toilet,
            COUNT(b.bin) filter (where b.sanitation_system_id = '10')::integer AS open_defacation
                    FROM building_info.buildings b 
                    LEFT JOIN building_info.structure_types st ON b.structure_type_id = st.id"
                . " WHERE b.road_code IN (" . implode(',', $roadCodes) . ")"
                . "  AND b.deleted_at is null"
                . " GROUP BY b.structure_type_id, st.id, b.bin ORDER BY st.id ASC";

            $results = DB::select($building_query);
            $buildings = array();
            foreach ($results as $row) {
                $building = array();
                $building['bin'] = $row->bin;
                $building['geom'] = $row->geom;
                $buildings[] = $building;
            }
            // query to get summary information about buildings
            $buildingQuery = "SELECT st.type AS structype, COUNT(*)::integer AS count,
                          COUNT(b.bin) filter (where b.sanitation_system_id = '1')::integer  AS sewer_network,
            COUNT(b.bin) filter (where b.sanitation_system_id = '2')::integer  AS drain_network,
            COUNT(b.bin) filter (where b.sanitation_system_id = '3')::integer AS septic_tank,
            COUNT(b.bin) filter (where b.sanitation_system_id = '4')::integer AS pit_holding_tank,
            COUNT(b.bin) filter (where b.sanitation_system_id = '5')::integer AS onsite_treatment,
            COUNT(b.bin) filter (where b.sanitation_system_id = '6')::integer AS composting_toilet,
            COUNT(b.bin) filter (where b.sanitation_system_id = '7')::integer AS water_body,
            COUNT(b.bin) filter (where b.sanitation_system_id = '8')::integer AS open_ground,
            COUNT(b.bin) filter (where b.sanitation_system_id = '9')::integer AS community_toilet,
            COUNT(b.bin) filter (where b.sanitation_system_id = '10')::integer AS open_defacation
                    FROM building_info.buildings b 
                    LEFT JOIN building_info.structure_types st ON b.structure_type_id = st.id"
                . " WHERE b.road_code IN (" . implode(',', $roadCodes) . ")"
                . "  AND b.deleted_at is null"
                . " GROUP BY b.structure_type_id, st.id ORDER BY st.id ASC";

            $buildingResults = DB::select($buildingQuery);
             // Generate HTML content for popup based on summary data
            $popContentsHtml = $this->popUpContentHtml($buildingResults);
            
            // Return the result as an array containing buildings information and summary HTML
            return ['buildings' => $buildings, 'summary' => $popContentsHtml];

    }

    public function getMultiplePointBufferBuildingsSummary($distance, $long, $lat)
    {
        // dd($distance, $lat, $long);
       // Query to create a buffer around the specified point
        $polygon_query = "SELECT  ST_AsText(ST_Buffer(ST_SetSRID(ST_Point(" . $long . "," . $lat . "),4326)::GEOGRAPHY, " . $distance . ")) AS circle_geog";
        $polygon_result = DB::select($polygon_query);
        $polygon = $polygon_result[0]->circle_geog;
        $buildings = array();
        $popContentsHtml = '';
          // Query to select buildings within the buffered area
        $building_query = "SELECT b.bin, ST_AsText(b.geom) AS geom"
            . " FROM building_info.buildings b, ST_Buffer(ST_SetSRID(ST_Point(" . $long . "," . $lat . "),4326)::GEOGRAPHY, " . $distance . ") AS circle_geog"
            . " WHERE ST_Intersects(circle_geog::GEOMETRY, b.geom)"
            . " AND b.drain_code IS NULL";
        $results1 = DB::select($building_query);
        foreach ($results1 as $row1) {
            $building = array();
            $building['bin'] = $row1->bin;
            $building['geom'] = $row1->geom;
            $buildings[] = $building;
        }
        // Query to use a function to get point buffer buildings 
        $buildingQuery = "Select * from fnc_getPointBufferBuildings($long::float, $lat::float, $distance);";
        $buildingResults = DB::select($buildingQuery);
        $popContentsHtml = $this->popUpContentHtml($buildingResults);

        return [
            'buildings' => $buildings,
            'popContentsHtml' => $popContentsHtml,
            'polygon' => $polygon
        ];
    }



    /**
     * Retrieves information about buildings within a specified buffer zone around a given point.
     *
     * @param float $distance The radius of the buffer zone in meters.
     * @param float $long The longitude coordinate of the center point.
     * @param float $lat The latitude coordinate of the center point.
     * @return array An array containing information about buildings within the buffer zone, along with other related data.
     */
    public function getPointBufferBuildingsSummary($distance, $long, $lat)
    {
        // dd($distance, $lat, $long);
       // Query to create a buffer around the specified point
        $polygon_query = "SELECT  ST_AsText(ST_Buffer(ST_SetSRID(ST_Point(" . $long . "," . $lat . "),4326)::GEOGRAPHY, " . $distance . ")) AS circle_geog";
        $polygon_result = DB::select($polygon_query);
        $polygon = $polygon_result[0]->circle_geog;
        $buildings = array();
        $popContentsHtml = '';
          // Query to select buildings within the buffered area
        $building_query = "SELECT b.bin, ST_AsText(b.geom) AS geom"
            . " FROM building_info.buildings b, ST_Buffer(ST_SetSRID(ST_Point(" . $long . "," . $lat . "),4326)::GEOGRAPHY, " . $distance . ") AS circle_geog"
            . " WHERE ST_Intersects(circle_geog::GEOMETRY, b.geom)"
            . " AND b.drain_code IS NULL";
        $results1 = DB::select($building_query);
        foreach ($results1 as $row1) {
            $building = array();
            $building['bin'] = $row1->bin;
            $building['geom'] = $row1->geom;
            $buildings[] = $building;
        }
        // Query to use a function to get point buffer buildings 
        $buildingQuery = "Select * from fnc_getPointBufferBuildings($long::float, $lat::float, $distance);";
        $buildingResults = DB::select($buildingQuery);
        $popContentsHtml = $this->popUpContentHtml($buildingResults);

        return [
            'buildings' => $buildings,
            'popContentsHtml' => $popContentsHtml,
            'polygon' => $polygon
        ];
    }


    public function buildingsKmlPopContentPolygon($bufferDistancePolygon, $bufferPolygonGeoms)
    {

        // Initialize variables to store polygon, buildings, and HTML content
        $polygons = [];
        $buildings = [];
        $popContentsHtml = '';
        $allBuildingResults = []; // This will store all the building results from the function calls
        $allBuildings = [];
        // Loop through each buffer polygon geometry
        foreach ($bufferPolygonGeoms as $bufferPolygonGeom) {
            // Query to create a buffer polygon around the input polygon geometry
            $polygon_query = "SELECT ST_AsText(ST_Buffer(ST_GeomFromText('" . $bufferPolygonGeom . "', 4326)::GEOGRAPHY, " . $bufferDistancePolygon . ")) AS circle_geog";
            $polygon_result = DB::select($polygon_query);
            $polygon = $polygon_result[0]->circle_geog;
    
            // Use array_push to add the polygon to the polygons array
            array_push($polygons, $polygon);
    
            // Query to retrieve buildings that intersect with the buffer polygon
            $building_query = "SELECT b.bin, ST_AsText(b.geom) AS geom"
                . " FROM building_info.buildings b"
                . " LEFT JOIN building_info.structure_types s ON b.structure_type_id = s.id"
                . " LEFT JOIN building_info.sanitation_systems ss ON b.sanitation_system_id = ss.id"
                . " WHERE (ST_Intersects(ST_Buffer(ST_GeomFromText('" . $bufferPolygonGeom . "', 4326)::GEOGRAPHY, " . $bufferDistancePolygon . ")::GEOMETRY, b.geom))"
              . " AND ss.map_display IS TRUE"
                . " GROUP BY b.bin, b.structure_type_id, s.id ORDER BY s.id ASC";
            $results1 = DB::select($building_query);
            // Process building results and collect them
           
            $allBuildings = array_merge($allBuildings, $results1);
            // Query to retrieve buildings using the stored function (this replicates the previous logic)
            $buildingQuery = "SELECT * FROM fnc_getBufferPolygonBuildings(ST_GeomFromText('" . $bufferPolygonGeom . "', 4326), $bufferDistancePolygon);";
            $buildingResults = DB::select($buildingQuery);
    
            // Accumulate the results of the building function for all buffer polygons
             $allBuildingResults = array_merge($allBuildingResults, $buildingResults);
        }
   

        foreach ($allBuildings as $row1) {
            
            $building = [
                'gid' => $row1->bin,
                'geom' => $row1->geom
            ];
            $buildings[] = $building;
        }
      
        // dd($building);
         // Aggregate results by structype
        $aggregatedResults = [];

        foreach ($allBuildingResults as $building) {
            $type = $building->structype;

            if (!isset($aggregatedResults[$type])) {
                // Store the first occurrence as an object
                $aggregatedResults[$type] = clone $building; 
            } else {
                // Aggregate numeric fields
                foreach ($building as $key => $value) {
                    if ($key !== 'structype' && is_numeric($value)) {
                        $aggregatedResults[$type]->$key += $value;
                    }
                }
            }
        }

        $aggregatedResults = array_values($aggregatedResults);

        // Convert associative array back to indexed array

        $popContentsHtml .= $this->popUpContentHtml($aggregatedResults); 

        return [
            'buildings' => $buildings,
            'popContentsHtml' => $popContentsHtml,
            'polygon' => $polygons, 
        ];
    }
    




    /**
     * Generates building information and popup content HTML within a buffered polygon.
     *
     * @param float $bufferDistancePolygon The buffer distance for the polygon.
     * @param string $bufferPolygonGeom The geometry of the buffered polygon.
     * @return array Associative array containing building information, popup content HTML, and polygon geometry.
     */
    public function buildingsPopContentPolygon($bufferDistancePolygon, $bufferPolygonGeom){
        $buildings = array();
        $popContentsHtml = '';
        // Query to create a buffer polygon around the input polygon geometry
        $polygon_query = "SELECT  ST_AsText(ST_Buffer(ST_GeomFromText('" . $bufferPolygonGeom . "', 4326)::GEOGRAPHY, " . $bufferDistancePolygon . ")) AS circle_geog";
        $polygon_result = DB::select($polygon_query);
        $polygon = $polygon_result[0]->circle_geog;
        // Query to retrieve buildings that intersect with the buffer polygon
        $building_query = "SELECT b.bin, ST_AsText(b.geom) AS geom"
                . " FROM building_info.buildings b"
                . " LEFT JOIN building_info.structure_types s ON b.structure_type_id = s.id"
                . " LEFT JOIN building_info.sanitation_systems ss ON b.sanitation_system_id = ss.id"
                . " WHERE (ST_Intersects(ST_Buffer(ST_GeomFromText('" . $bufferPolygonGeom . "', 4326)::GEOGRAPHY, " . $bufferDistancePolygon . ")::GEOMETRY, b.geom))"
              . " AND ss.map_display IS TRUE"
                . " GROUP BY b.bin, b.structure_type_id, s.id ORDER BY s.id ASC";
        $results1 = DB::select($building_query);

        foreach ($results1 as $row1) {
            $building = array();
            $building['gid'] = $row1->bin;
            $building['geom'] = $row1->geom;
            $buildings[] = $building;
        }
        // Query to retrieve buildings using a stored function
        $buildingQuery = "Select * from fnc_getBufferPolygonBuildings( ST_GeomFromText(" . "'" . "$bufferPolygonGeom" . "'" . ",4326), $bufferDistancePolygon) ;";
        $buildingResults = DB::select($buildingQuery);
        
      // Generate HTML content for pop-up using the building query results
        $popContentsHtml = $this->popUpContentHtml($buildingResults);
        // Return the buildings array, pop-up HTML content, and polygon
        return [
            'buildings' => $buildings,
            'popContentsHtml' => $popContentsHtml,
            'polygon' => $polygon
        ];
    
    }
    
    /**
     * Generates HTML content for displaying building information in a table format.
     *
     * @param array $buildingResults An array containing building information.
     * @return string HTML content for displaying building information.
     */
    public function popUpContentHtml($buildingResults){
     
        $total = 0;
        $total_sewer_network = 0;
        $total_drain_network = 0;
        $total_septic_tank = 0;
        $total_pit_holding_tank = 0;
        $total_onsite_treatment = 0;
        $total_composting_toilet = 0;
        $total_water_body = 0;
        $total_open_ground = 0;
        $total_community_toilet = 0;
        $total_open_defacation = 0;
        
        foreach ($buildingResults as $row1) {

            $total += $row1->count;
            $total_sewer_network += $row1->sewer_network;
            $total_drain_network += $row1->drain_network;
            $total_septic_tank += $row1->septic_tank;
            $total_pit_holding_tank += $row1->pit_holding_tank;
            $total_onsite_treatment += $row1->onsite_treatment;
            $total_composting_toilet += $row1->composting_toilet;
            $total_water_body += $row1->water_body;
            $total_open_ground += $row1->open_ground;
            $total_community_toilet += $row1->community_toilet;
            $total_open_defacation += $row1->open_defacation;
        }
        
        $tbody = '<tbody>';
        foreach ($buildingResults as $row1) {
            $tbody .= '<tr>';
            $tbody .= '<td>' . $row1->structype . '</td>';
            $tbody .= '<td>' . $row1->count . '</td>';
            if($total_sewer_network > 0) { $tbody .= '<td>' . $row1->sewer_network . '</td>'; }
            if($total_drain_network > 0) { $tbody .= '<td>' . $row1->drain_network . '</td>'; }
            if($total_septic_tank > 0) { $tbody .= '<td>' . $row1->septic_tank . '</td>'; }
            if($total_pit_holding_tank > 0) { $tbody .= '<td>' . $row1->pit_holding_tank . '</td>'; }
            if($total_onsite_treatment > 0) { $tbody .= '<td>' . $row1->onsite_treatment . '</td>'; }
            if($total_composting_toilet > 0) { $tbody .= '<td>' . $row1->composting_toilet . '</td>'; }
            if($total_water_body > 0) { $tbody .= '<td>' . $row1->water_body . '</td>'; }
            if($total_open_ground > 0) { $tbody .= '<td>' . $row1->open_ground . '</td>'; }
            if($total_community_toilet > 0) { $tbody .= '<td>' . $row1->community_toilet . '</td>'; }
            if($total_open_defacation > 0) { $tbody .= '<td>' . $row1->open_defacation . '</td>'; }
            $tbody .= '</tr>'; 
            }
            $tbody .= '</tbody>';
        
        $tfoot = '<tfoot>';
        $tfoot .= '<th>Total</th>';
        $tfoot .= '<th>' . $total . '</th>';
        if($total_sewer_network > 0){
        $tfoot .= '<th>' . $total_sewer_network . '</th>';
        }
        if($total_drain_network > 0){
        $tfoot .= '<th>' . $total_drain_network . '</th>';
        }
        if($total_septic_tank > 0){
        $tfoot .= '<th>' . $total_septic_tank . '</th>'; }
        if($total_pit_holding_tank > 0){
        $tfoot .= '<th>' . $total_pit_holding_tank . '</th>'; }
        if($total_onsite_treatment > 0){
        $tfoot .= '<th>' . $total_onsite_treatment . '</th>'; }
        if($total_composting_toilet > 0){
            $tfoot .= '<th>' . $total_composting_toilet . '</th>';
            }
            if($total_water_body > 0){
            $tfoot .= '<th>' . $total_water_body . '</th>';
            }
            if($total_open_ground > 0){
            $tfoot .= '<th>' . $total_open_ground . '</th>'; }
            if($total_community_toilet > 0){
            $tfoot .= '<th>' . $total_community_toilet . '</th>'; }
            if($total_open_defacation > 0){
            $tfoot .= '<th>' . $total_open_defacation . '</th>'; }
        $tfoot .= '</tfoot>';
        $thead = '<thead>';
        $thead .= '<tr>';
        $thead .= '<th>Structure Type</th>';
        $thead .= '<th>Buildings</th>';
        if($total_sewer_network > 0){
        $thead .= '<th>Sewer Network</th>';
        }
        if($total_drain_network > 0){
        $thead .= '<th>Drain Network</th>';
        }
        if($total_septic_tank > 0){
        $thead .= '<th>Septic Tank </th>';
        }
        if($total_pit_holding_tank > 0){
        $thead .= '<th>Pit / Holding Tank</th>'; }
        if($total_onsite_treatment > 0){
        $thead .= '<th>Onsite Treatment</th>'; }
        if($total_composting_toilet > 0){
            $thead .= '<th>Composting Toilet</th>';
            }
            if($total_water_body > 0){
            $thead .= '<th>Water Body</th>';
            }
            if($total_open_ground > 0){
            $thead .= '<th>Open Ground</th>';
            }
            if($total_community_toilet > 0){
            $thead .= '<th>Community Toilet</th>'; }
            if($total_open_defacation > 0){
            $thead .= '<th>Open Defecation</th>'; }
        $thead .= '</tr>';
        $thead .= '</thead>';
        $html = '<table class="table table-bordered">';
        $html .= $thead;
        $html .= $tbody;
        $html .= $tfoot;
        $html .= '</table>';
        return $html;
    }

    public function getAreaPopulationPolygonSum(Request $request)
    {
        if ($request->geom) {

            /*$Query = "SELECT (ST_SummaryStats(St_Union(ST_Clip(rast,ST_GeomFromText('" . $request->geom . "', 4326),true)))).sum
             FROM public.populations
             WHERE ST_Intersects
              (rast,ST_GeomFromText
              ('" . $request->geom . "', 4326))";*/

            $Query = "SELECT SUM(population_served) AS sum
             FROM building_info.buildings
             WHERE ST_Intersects
              (geom,ST_GeomFromText
              ('" . $request->geom . "', 4326))";

            $results = DB::select($Query);
            $html = '<table class="table table-bordered" style="text-align: center;">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="text-align: center;">Total Population</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
            $html .= '<tr>';
            $html .= '<td>' . intval($results[0]->sum) . '</td>';
            $html .= '</tr>';
            $html .= '</tbody>';
            $html .= '</table>';

            return $html;
        } else {
            return "The 'geom' field is required";
        }
    }

    /**
     * Retrieves the sum of population served within a given polygon area.
     *
     * @param string $geom The geometry of the polygon in Well-Known Text format.
     * @return string HTML table containing the total population served within the polygon area.
     */

     public function getAreaPopulationPolygonSumInfo($geom)
    {
            /*$Query = "SELECT (ST_SummaryStats(St_Union(ST_Clip(rast,ST_GeomFromText('" . $geom . "', 4326),true)))).sum
             FROM public.populations
             WHERE ST_Intersects
              (rast,ST_GeomFromText
              ('" . $geom . "', 4326))";*/

         // Construct the SQL query to calculate the sum of population served within the provided geometry
            $query = "SELECT SUM(population_served) AS sum
             FROM building_info.buildings
             WHERE ST_Intersects
              (geom,ST_GeomFromText
              ('" . $geom . "', 4326))";

            $results = DB::select($query);
            $html = '<table class="table table-bordered" style="text-align: center;">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="text-align: center;">Total Population</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
            $html .= '<tr>';
            $html .= '<td>' . intval($results[0]->sum) . '</td>';
            $html .= '</tr>';
            $html .= '</tbody>';
            $html .= '</table>';

            return $html;

    }

    /**
         * Retrieves containment and nearest road information based on a specified field and value.
         *
         * @param string $field The field to search in the containment database table.
         * @param string $val The value to search for in the specified field.
         * @return array An associative array containing the latitude and longitude of the containment (c_lat, c_long)
         *               and the nearest road (r_lat, r_long) found within a 1000-meter radius of the containment.
         *               If no results are found, empty strings are returned for all values.
         */

    public function getContainmentRoadInfo($field, $val)
    {
        $c_lat = $c_long = $r_lat = $r_long = '';

        $c_query = "SELECT ST_Y (ST_Transform (geom, 4326)) as c_lat, ST_X (ST_Transform (geom, 4326)) as c_long"
            . " FROM fsm.containments"
            . " WHERE " . $field . " = '" . $val . "'";

        $c_results = DB::select($c_query);

        if (count($c_results) > 0) {
            $c_lat = $c_results[0]->c_lat;
            $c_long = $c_results[0]->c_long;

            $r_query = "SELECT ST_AsEWKT(ref_geom),r.name, ST_Y(ST_ClosestPoint(ST_Transform(r.geom,4326), ref_geom)) As r_lat, ST_X(ST_ClosestPoint(ST_Transform(r.geom,4326), ref_geom)) As r_long"
                . " FROM utility_info.roads As r, ST_Transform(ST_SetSRID(ST_Point(" . $c_long . "," . $c_lat . "),4326),4326) AS ref_geom"
                . " WHERE ST_DWithin(ST_Transform(r.geom,4326), ref_geom, 1000)"
                . " ORDER BY ST_Distance(ST_Transform(r.geom,4326),ref_geom) LIMIT 1";

            $r_results = DB::select($r_query);

            if (count($r_results) > 0) {
                $r_lat = $r_results[0]->r_lat;
                $r_long = $r_results[0]->r_long;
            }
        }

        $data = array(
            'c_lat' => $c_lat,
            'c_long' => $c_long,
            'r_lat' => $r_lat,
            'r_long' => $r_long
        );

        return $data;
    }

    /**
     * Retrieves building centroid and nearest road information based on a specified field and value.
     *
     * @param string $field The field to search in the buildings database table.
     * @param string $val The value to search for in the specified field.
     * @return array An associative array containing the latitude and longitude of the building centroid (c_lat, c_long)
     *               and the latitude and longitude of the nearest road (r_lat, r_long) found within a 1000-meter radius of the building.
     *               If no results are found, empty strings are returned for all values.
     */

    public function getBuildingRoadInfo($field, $val)
    {
        $c_lat = $c_long = $r_lat = $r_long = '';

        // Query to get centroid coordinates of buildings
        $c_query = "SELECT ST_Y (ST_Transform ((SELECT st_centroid(st_union(geom)) as geom
        FROM building_info.buildings WHERE $field = '$val'), 4326)) as c_lat, ST_X (ST_Transform ((SELECT st_centroid(st_union(geom)) as geom
        FROM building_info.buildings WHERE $field = '$val'), 4326)) as c_long
        FROM building_info.buildings
        WHERE $field = '$val'";

        $c_results = DB::select($c_query);

        if (count($c_results) > 0) {
            $c_lat = $c_results[0]->c_lat;
            $c_long = $c_results[0]->c_long;

            // Query to find the nearest road to the centroid
            $r_query = "SELECT ST_AsEWKT(ref_geom),r.name, ST_Y(ST_ClosestPoint(ST_Transform(r.geom,4326), ref_geom)) As r_lat, ST_X(ST_ClosestPoint(ST_Transform(r.geom,4326), ref_geom)) As r_long"
                . " FROM utility_info.roads As r, ST_Transform(ST_SetSRID(ST_Point(" . $c_long . "," . $c_lat . "),4326),4326) AS ref_geom"
                . " WHERE ST_DWithin(ST_Transform(r.geom,4326), ref_geom, 1000)"
                . " ORDER BY ST_Distance(ST_Transform(r.geom,4326),ref_geom) LIMIT 1";

            $r_results = DB::select($r_query);

            if (count($r_results) > 0) {
                $r_lat = $r_results[0]->r_lat;
                $r_long = $r_results[0]->r_long;
            }
        }

        $data = array(
            'c_lat' => $c_lat,
            'c_long' => $c_long,
            'r_lat' => $r_lat,
            'r_long' => $r_long
        );

        return $data;
    }

    /**
     * Retrieves information about buildings with due taxes, including their bin number, latitude, and longitude.
     *
     * @return array An array of associative arrays, each containing the latitude ('lat') and longitude ('long')
     *               of a building with due taxes, along with its bin number.
     */

    public function getDueBuildingsInfo()
    {
        $query = "SELECT b.bin, ST_Y (ST_Transform (ST_Centroid(b.geom), 4326)) as lat, ST_X (ST_Transform (ST_Centroid(b.geom), 4326)) as long"
            . " FROM building_info.buildings b"
            . " INNER JOIN taxpayment_info.tax_payment_status tax"
            . " ON tax.tax_code = b.tax_code"
            . " WHERE tax.due_year > 0 AND tax.due_year < 99"
            . " ORDER BY RANDOM()";

        $results = DB::select($query);

        $data = array();

        foreach ($results as $row) {
            $coord = array();
            $coord['lat'] = $row->lat;
            $coord['long'] = $row->long;
            $data[] = $coord;
        }

        return $data;
    }

    /**
     * Retrieves information about buildings within a ward or tax zone that have tax dues.
     *
     * @param string $where Additional SQL conditions to filter the query further (optional).
     * @return array An array of associative arrays, each containing the latitude (lat) and longitude (long) 
     *               of a building within the specified ward or tax zone that has due taxes.
     */

    public function getDueBuildingsWardTaxzoneInfo($where){

        $query = "SELECT b.bin, ST_Y (ST_Transform (ST_Centroid(b.geom), 4326)) as lat, ST_X (ST_Transform (ST_Centroid(b.geom), 4326)) as long"
            . " FROM building_info.buildings b"
            . " INNER JOIN taxpayment_info.tax_payment_status tax"
            . " ON tax.tax_code = b.tax_code"
            . " WHERE tax.due_year > 0 AND tax.due_year < 99 AND b.deleted_at is null $where"
            . " ORDER BY RANDOM()";
        $results = DB::select($query);

        $data = array();

        foreach ($results as $row) {
            $coord = array();
            $coord['lat'] = $row->lat;
            $coord['long'] = $row->long;
            $data[] = $coord;
        }

        return $data;
    }

    /**
     * Retrieves information about proposed emptying containments within a specified date range.
     * @param string $start_date The start date of the date range.
     * @param string $end_date The end date of the date range.
     * @return array An array of associative arrays containing the latitude and longitude coordinates of
     *               containment locations with proposed emptying dates within the specified date range.
     */

    public function getProposedEmptyingContainmentsInfo($start_date, $end_date)
    {
        if (Auth::user()->hasRole('Service Provider - Admin') || Auth::user()->hasRole('Service Provider - Help Desk') ) {
            $whereUser = " AND a.service_provider_id = " . Auth::user()->service_provider_id;
        } else {
            $whereUser = "";
        }
        $query = "SELECT 
                c.id, ST_X(c.geom) AS long, ST_Y(c.geom) AS lat
            FROM fsm.containments c
            JOIN fsm.applications a ON a.containment_id = c.id AND a.deleted_at IS NULL
            WHERE c.deleted_at IS NULL
            AND a.deleted_at is null AND (a.emptying_status = 0 OR a.emptying_status IS NULL)"
            . "$whereUser"
            . " AND a.proposed_emptying_date BETWEEN ? AND ?";

        $results = DB::select($query, [$start_date, $end_date]);
        $data = array();

        foreach ($results as $row) {
            $coord = array();
            $coord['lat'] = $row->lat;
            $coord['long'] = $row->long;
            $data[] = $coord;
        }

        return $data;
    }

    /**
     * Retrieves summary information about inaccessible roads within a specified width range.
     *
     * @param int $width The minimum width of roads to consider.
     * @param int $range The range for buffering roads.
     * @return array Array containing information about buildings, population content HTML, and the buffered polygon.
     */
     public function getRoadInaccesibleISummaryInfo($width, $range)
    {
       
        // Query to get the union of road geometries with specified width
        $query = "SELECT ST_AsText(ST_Union(geom)) AS geom FROM utility_info.roads WHERE carrying_width >= $width";
        $bufferQuery = DB::select($query);
        
        $row = $bufferQuery[0];
        $polygon_query = "SELECT ST_AsText(ST_Buffer(ST_GeomFromText('" . $row->geom . "', 4326)::GEOGRAPHY, " . $range . ")) AS circle_geog";
        $polygon_result = DB::select($polygon_query);
        $polygon = $polygon_result[0]->circle_geog;

        // Query to retrieve city polygon
        $cityPoly = "SELECT ST_AsText(geom) AS geom FROM layer_info.citypolys";
        $cityPolyResult = DB::select($cityPoly);
        $cityPolygon = $cityPolyResult[0]->geom;

        // Query to get remaining area after subtracting road buffer polygon from city polygon
        $remainingPolygonQuery = "SELECT ST_AsText(ST_Difference(ST_GeomFromText('$cityPolygon'), ST_GeomFromText('$polygon'))) AS geom";
        $remainingPolygon = DB::select($remainingPolygonQuery);
        $remainingPolygonGeom = $remainingPolygon[0]->geom;

        // Query to retrieve buildings within the remaining polygon
        $building_query = "SELECT b.bin, ST_AsText(b.geom) AS geom"
            . " FROM building_info.buildings b"
            . " WHERE ST_Intersects(ST_GeomFromText('$remainingPolygonGeom',4326), b.geom)"
            . " AND b.deleted_at IS NULL";
        $results1 = DB::select($building_query);
        
            foreach ($results1 as $row1) {
                $building = array();
                $building['bin'] = $row1->bin;
                $building['geom'] = $row1->geom;
                $buildings[] = $building;
            }
           // Call the function to get buildings within the remaining polygon
        $buildingQuery = "Select * from fnc_getBufferPolygonBuildings( ST_GeomFromText(" . "'" . "$remainingPolygonGeom" . "'" . ",4326), 0) ;";
        $buildingResults = DB::select($buildingQuery);

        // Generate HTML for pop-up contents
        $popContentsHtml = $this->popUpContentHtml($buildingResults);

        return [
            'buildings' => $buildings,
            'popContentsHtml' => $popContentsHtml,
            'polygon' => $polygon
        ];


    }
    
    /**
     * Retrieves summary information about inaccessible water bodies within a specified range.
     *
     * @param int $range The range parameter used to determine the buffer distance around water bodies.
     * @return array An array containing information about buildings, population content HTML, and the polygon.
     */
     public function getWaterbodyInaccesibleISummaryInfo($range)
    {
        // Query to retrieve the union of waterbody geometries
        $query = "SELECT ST_AsText(ST_Union(geom)) AS geom FROM layer_info.waterbodys";
        $bufferQuery = DB::select($query);
        $row = $bufferQuery[0];
        // Create a polygon by buffering the unioned waterbody geometry
        $polygon_query = "SELECT ST_AsText(ST_Buffer(ST_GeomFromText('" . $row->geom . "', 4326)::GEOGRAPHY, " . $range . ")) AS circle_geog";
        $polygon_result = DB::select($polygon_query);
        $polygon = $polygon_result[0]->circle_geog;

         // Query to retrieve buildings intersecting with the created polygon
        $building_query = "SELECT b.bin, ST_AsText(b.geom) AS geom"
            . " FROM building_info.buildings b"
            . " WHERE ST_Intersects(ST_GeomFromText('$polygon',4326), b.geom)"
            . " AND b.deleted_at IS NULL";
        
        $results1 = DB::select($building_query);
        
            foreach ($results1 as $row1) {
                $building = array();
                $building['bin'] = $row1->bin;
                $building['geom'] = $row1->geom;
                $buildings[] = $building;
            }
           
         // Query to retrieve buffer polygon buildings using a stored function   
        $buildingQuery = "Select * from fnc_getBufferPolygonBuildings( ST_GeomFromText(" . "'" . "$polygon" . "'" . ",4326), $range) ;";
        $buildingResults = DB::select($buildingQuery);
        
         // Generate population contents HTML for the buildings
        $popContentsHtml = $this->popUpContentHtml($buildingResults);

        return [
            'buildings' => $buildings,
            'popContentsHtml' => $popContentsHtml,
            'polygon' => $polygon
        ];

    }

 /**
 * Calculates isochrone polygons from nearby toilets.
 *
 * For each toilet in the database (excluding "Community Toilets"), this function:
 * - Finds the nearest node in the road network.
 * - Calculates a concave hull polygon around the reachable area (based on driving distance).
 * - Identifies buildings that lie within this isochrone area and are not linked to a drain.
 *
 * @param float $distance The travel distance in meters .
 * @return array Returns an associative array containing:
 *               - 'polygon': an array of isochrone polygons (WKT) for each toilet.
 *               
 */
    public function getToiletIsochroneAreaLayers($distance)
    {
        
        $toilet_geom = DB::SELECT("SELECT geom, name
                FROM fsm.toilets 
                WHERE deleted_at IS NULL 
                AND type <> 'Community Toilet';");

                $polygons = array(); 
                $buildings = array();
        $i = 0;
        foreach($toilet_geom as $toilet)
        {
        $long = DB::SELECT("SELECT ST_X('$toilet->geom')")[0]->st_x;
        $lat = DB::SELECT("SELECT ST_Y('$toilet->geom')")[0]->st_y;

        $node_id_query = "WITH parameters AS (
                SELECT
                    ".$distance." AS max_cost, 
                    ST_SetSRID(ST_Point(". $long .", ". $lat."),4326)::geometry AS origin, 
                    4326 AS srid 
                ), 
                nearest_node AS (
                SELECT
                    id,
                    the_geom <-> origin AS distance
                FROM
                    utility_info.roads_network_noded_vertices_pgr,
                    parameters
                ORDER BY
                    the_geom <-> origin
                LIMIT 1
                )
                SELECT * from nearest_node; ";

        $node_id = DB::SELECT($node_id_query)[0]->id;

        $polygon_query = "
            SELECT ST_asTEXT(ST_setSRID(ST_ConcaveHull(ST_Collect(the_geom), 0.9),4326)) as isochrone
            FROM (
                SELECT ST_Transform(ST_setSRID(the_geom, 4326),4326) as the_geom
                FROM  pgr_drivingdistance('SELECT
                id::integer AS id, 
                source::integer AS source, 
                target::integer AS target,                                    
                distance::double precision AS cost 
                FROM utility_info.roads_network_noded'::text, ".$node_id."::bigint, 
                ".$distance."::double precision, false)
                AS dij_result
            JOIN utility_info.roads_network_noded ON dij_result.edge = utility_info.roads_network_noded.id
            ) AS shortest_path";
        $polygon_result = DB::select($polygon_query);
        $polygon['id'] = $i;
        $polygon['geom'] = $polygon_result[0]->isochrone;
        $polygon['long'] = $long;
        $polygon['lat'] = $lat;
        array_push($polygons,$polygon );
        $i+=1;
        }
        
        return [
            'polygon' => $polygons
        ];
    }


}
