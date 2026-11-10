<?php

namespace App\Services\BuildingInfo;

use DB;

use Auth;
use Redirect;
use DataTables;
use DOMDocument;
use App\Models\Fsm\Ctpt;
use Box\Spout\Common\Type;
use Illuminate\Http\Request;
use App\Helpers\KeywordMatcher;
use App\Models\Fsm\BuildToilet;
use App\Models\Fsm\Containment;
use Box\Spout\Writer\Style\Color;
use App\Models\BuildingInfo\Owner;
use App\Models\Fsm\ContainmentType;
// use App\Services\Fsm\ContainmentService;
use Box\Spout\Writer\WriterFactory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use App\Models\BuildingInfo\Building;
use App\Models\BuildingInfo\UseCategory;
use App\Models\BuildingInfo\WaterSource;
use App\Services\Fsm\ContainmentService;
use Box\Spout\Writer\Style\StyleBuilder;
use App\Models\BuildingInfo\BuildContain;
use App\Models\BuildingInfo\FunctionalUse;
use Illuminate\Support\Facades\Storage;
use App\Models\BuildingInfo\StructureType;
use App\Models\BuildingInfo\BuildingSurvey;
use App\Models\BuildingInfo\SanitationSystem;
use App\Http\Requests\BuildingInfo\BuildingRequest;
use App\Models\BuildingInfo\SanitationSystemTechnology;
use Intervention\Image\Facades\Image;
use App\Services\OwnerPiiPresenter;
use App\Helpers\ImageOptimizationHelper;
use Illuminate\Contracts\Encryption\DecryptException;

class BuildingStructureService
{
    private OwnerPiiPresenter $ownerPiiPresenter;

    public function __construct(OwnerPiiPresenter $ownerPiiPresenter)
    {
        $this->ownerPiiPresenter = $ownerPiiPresenter;
    }

    public function storeBuildingData(Request $request)
    {
        DB::beginTransaction();
        try {

            $maxBIN = Building::withTrashed()->max('bin');
            $maxBIN = (int) str_replace('B', '', $maxBIN); // cast to integer
            // creating new model to add data to buildings table
            $building = new Building();
            $building->bin = 'B' . sprintf('%06d', $maxBIN + 1);
            $request->bin = $building->bin;
            // owner information stored to BuildingInfo.owners
            // check if main building, if not main, store main building bin in associated_to column
            if ($request->main_building == false) {
                $building->building_associated_to = $request->building_associated_to ? $request->building_associated_to : null;
            }
            $building->ward = $request->ward ? $request->ward : null;
            $building->road_code = $request->road_code ? $request->road_code : null;
            $building->house_number = $request->house_number ? $request->house_number : null;
            $building->house_locality = $request->house_locality ? $request->house_locality : null;
            $building->tax_code = $request->tax_code ? $request->tax_code : null;
            $building->structure_type_id = $request->structure_type_id ? $request->structure_type_id : null;
            //year of building construction
            $building->surveyed_date = $request->surveyed_date ? $request->surveyed_date : null;
            $building->construction_year = $request->construction_year ? $request->construction_year : null;
            $building->floor_count = $request->floor_count ? $request->floor_count : null;
            $building->functional_use_id = $request->functional_use_id ? $request->functional_use_id :  $request->functional_use_id;
            $building->use_category_id = $request->use_category_id ? $request->use_category_id : null;
            // checking if building is residential or not, if yes then not taking office business name
            if ($building->functional_use_id != "1") {
                $building->office_business_name = $request->office_business_name ? $request->office_business_name : null;
            }
            $building->household_served = $request->household_served ? $request->household_served : '0' ;
            $building->population_served = $request->population_served ? $request->population_served : '0' ;
            //male female other popn
            $building->male_population = $request->male_population ? $request->male_population : null;
            $building->female_population = $request->female_population ? $request->female_population : null;
            $building->other_population = $request->other_population ? $request->other_population : null;
            //disabled popn
            $building->diff_abled_male_pop = $request->diff_abled_male_pop ? $request->diff_abled_male_pop : null;
            $building->diff_abled_female_pop = $request->diff_abled_female_pop ? $request->diff_abled_female_pop : null;
            $building->diff_abled_others_pop = $request->diff_abled_others_pop ? $request->diff_abled_others_pop : null;
            //check wheter the building is low income building
            $building->low_income_hh = $request->low_income_hh;
            //check wheter the building is located in LIC area
            if ($request->lic_status == true) {
                $building->lic_id = $request->lic_id ? $request->lic_id : null;
            }
            $building->water_source_id = $request->water_source_id ? $request->water_source_id : null;
            // water source Municipal/Public water supply
            if(KeywordMatcher::matchKeywords($building->WaterSource->source, ["municipal","public"]))
            {
                $building->water_customer_id = $request->water_customer_id ? $request->water_customer_id : null;
                $building->watersupply_pipe_code = $request->watersupply_pipe_code ? $request->watersupply_pipe_code : null;
            }
            $building->well_presence_status = $request->well_presence_status;
            if ($building->well_presence_status == true) {
                $building->distance_from_well = $request->distance_from_well ? $request->distance_from_well : null;
            }
            $building->swm_customer_id = $request->swm_customer_id ? $request->swm_customer_id : null;
            $building->toilet_status = $request->toilet_status;
            // storing house image in folder /public/emptyings/houses
            if ($request->hasFile('house_image')) {
                // Validate the uploaded image
                $extension_house = strtolower($request->house_image->getClientOriginalExtension());
                $filename_house = $building->bin . '.' . $extension_house;

                $imageSaved = ImageOptimizationHelper::optimizeAndSave(
                    $request->house_image,
                    Storage::disk('local')->path('/public/emptyings/houses/' . $filename_house),
                    1600,
                    70
                );
                if (!$imageSaved) {
                    throw new \RuntimeException(__('Unable to save the building image.'));
                }
            }
            //if toilet status is false sanitaiton system id will take request-> defecation_place
            if ($building->toilet_status == false) {
                $building->sanitation_system_id = $request->defecation_place ? $request->defecation_place : null;
            } else if ($building->toilet_status == true) {
                $building->toilet_count = $request->toilet_count ? $request->toilet_count : null;
                //NUMBER OF HOUSEHOLD THAT SHARE TOILET
                $building->household_with_private_toilet = $request->household_with_private_toilet ? $request->household_with_private_toilet : null;
                $building->population_with_private_toilet = $request->population_with_private_toilet ? $request->population_with_private_toilet : null;
                $building->sanitation_system_id = $request->sanitation_system_id ? $request->sanitation_system_id : null;
                $request->sanitation_system = SanitationSystem::find($building->sanitation_system_id)->sanitation_system;
                if (KeywordMatcher::matchKeywords($request->sanitation_system, ["sewer", "septic", "pit"])) {
                    $building->sewer_code = $request->sewer_code ? $request->sewer_code : null;
                } elseif (KeywordMatcher::matchKeywords($request->sanitation_system, ["drain", "septic", "pit"])) {
                    $building->drain_code = $request->drain_code ? $request->drain_code : null;
                }
            }
            $building->verification_status = 1;
            // checking if KML is available, if yes then converting kml to geom
            $building->geom = $this->storeGeomInfo($request, 'building', 'create');
            $building->estimated_area = $this->storeGeomInfo($request, 'area', 'create');
            $building->surveyed_date = $request->surveyed_date ? $request->surveyed_date : null;
            // save building data
            $building->user_id = Auth::id();
            $building->desludging_vehicle_accessible = $request->desludging_vehicle_accessible ? $request->desludging_vehicle_accessible : null;
            $building->save();

            if ($building) {
                if (!empty($request->survey_id)) {
                    $buildingSurvey = DB::table('building_info.building_surveys')
                    ->where('id', $request->survey_id)
                    ->update(['is_enabled' => false]);
                }
                $this->storeOwnerInfo($request);
                // check sanitiation type and send to function with respective flag
                //  conditions if sanitation system is shared containment
                // matching keyword shared of Shared Containment
                if ((KeywordMatcher::matchKeywords($request->sanitation_system, ["shared"]))) {
                    $building->sanitation_system_id = $this->storeContainmentInfo($flag = 'shared', $type = 'create', $request);
                    $building->save();
                } elseif (
                    KeywordMatcher::matchKeywords($request->sanitation_system, ["septic", "pit"]) &&
                    (KeywordMatcher::matchKeywords($request->sanitation_system, ["shared"])) == false
                ) {
                    $this->storeContainmentInfo($flag = 'containment', $type = 'create', $request);
                } elseif (KeywordMatcher::matchKeywords($request->sanitation_system, ["drain", "sewer", "onsite", "water", "ground", "composting"])) {
                    //    no containment so do nothing.
                } elseif (KeywordMatcher::matchKeywords($request->defecation_place, ["9"])) {
                    $this->storeContainmentInfo($flag = 'communal', $type = 'create', $request);
                }
            }
            DB::commit();

            if ($request->filled('application_id')) {
                $application = \App\Models\Fsm\Application::find($request->application_id);
                if ($application) {
                    $application->bin = $building->bin;
                    $application->ward = $building->ward ?? $application->ward;
                    $application->road_code = $building->road_code ?? $application->road_code;
                    $owner = $building->owners;
                    if ($owner) {
                        $application->customer_name = $owner->owner_name ?? $application->customer_name;
                        $application->customer_contact = $owner->owner_contact ?? $application->customer_contact;
                        $application->customer_gender = $owner->owner_gender ?? $application->customer_gender;
                    }
                    $firstContainment = $building->containments->first();
                    if ($firstContainment) {
                        $application->containment_id = $firstContainment->id;
                        $application->is_anf = false;
                    }
                    $application->save();
                }
                return redirect()->route('emptying.create-id', $request->application_id)->with('success', __("Building created successfully"));
            }

            return redirect('building-info/buildings')->with('success', __("Building created successfully"));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect('building-info/buildings')->with('error', __("Building could not be created"));
        }
    }


    public function storeContainmentInfo($flag, $type, $request)
    {

        if ($flag == 'communal' || $flag == 'shared') {
            if ($flag == 'communal') {

                // removing previous connection if update
                if ($type == 'update' && !empty(BuildToilet::where('bin', $request->bin)->whereNULL('deleted_at')->first())) {

                    $buildToiletPivot = BuildToilet::where('bin', $request->bin)->WhereNULL('deleted_at');
                    $buildToiletPivot->delete();
                }

                // storing connection to of building and toilet to build_toilet
                $toilet = new BuildToilet();
                $toilet->bin = $request->bin;
                $toilet->toilet_id =  $request->ctpt_name ?  $request->ctpt_name : null;

                $toilet->save();
            } else if ($flag == 'shared') {

                $building = Building::find($request->build_contain);

                $containment_id = $building->containments->first()->id;

                $this->storeBuildContainInfo($request->bin, $containment_id);

                return $building->sanitation_system_id;
            }
        } //pit / holding or septic
        else if ($flag == 'containment') {

            if ($type == 'create' || $type == 'createContainOnly') {
                $maxContainmentCode = Containment::withTrashed()->max('id');
                $maxContainmentCode = (int) str_replace('C', '', $maxContainmentCode); // cast to integer
                $containment = new Containment();
                $containment->id = 'C' . sprintf('%06d', $maxContainmentCode + 1);
                $containment->responsible_bin = $request->bin;
            } elseif ($type == 'update') {
                $containment = Containment::find($request->id);
            }
            $request->containment_id = $containment->id;
            $containment->type_id =  $request->type_id ? $request->type_id : null;
            $septic_types = ContainmentType::where('type', 'ILIKE', '%septic%')->pluck('type')->toArray();
            $holding_tanks = ContainmentType::where('type', 'NOT ILIKE', '%septic%')->pluck('type')->toArray();
            $containment_type = ContainmentType::find($containment->type_id)->type;

            // comparing if type is septic tank
            if (in_array($containment_type, $septic_types)) {
                if (!empty($containment->buildings)) {
                    foreach ($containment->buildings as $building) {
                        $building = Building::find($building->bin);
                        $building->sewer_code = $request->sewer_code;
                        $building->drain_code = $request->drain_code;
                        $building->save();
                    }
                }
                $containment->type_id = $request->type_id;
                $containment->septic_criteria = $request->septic_criteria ? $request->septic_criteria : null;
                $containment->tank_length = $request->tank_length ? $request->tank_length : null;
                $containment->tank_width = $request->tank_width ? $request->tank_width : null;
                $containment->depth = $request->depth ? $request->depth : null;
                $containment->size = $request->size ? $request->size : $containment->size;
                $containment->pit_diameter = null;
            } elseif (in_array($containment_type, $holding_tanks)) {

                if ($request->pit_shape == "Rectangular") {
                    $containment->tank_length = $request->tank_length ? $request->tank_length : null;
                    $containment->tank_width = $request->tank_width ? $request->tank_width : null;
                    $containment->depth = $request->depth ? $request->depth : null;
                    $containment->pit_diameter = null;
                } elseif ($request->pit_shape == "Cylindrical") {
                    $containment->pit_diameter = $request->pit_diameter ? $request->pit_diameter : null;
                    $containment->depth = $request->pit_depth ? $request->pit_depth : null;
                    $containment->tank_length =  null;
                    $containment->tank_width =  null;
                }
                $containment->size = $request->size ? $request->size : $containment->size ;
            }
            $containment->septic_criteria = $request->septic_criteria ? $request->septic_criteria : null;
            $containment->construction_date = $request->construction_date ? $request->construction_date : null;
            $containment->location = $request->location ? $request->location : null;
            $containment->emptied_status = 'false';
            $containment->no_of_times_emptied = 0;


            // getting centroid of kml and storing it as geom of containment
            if ($type == 'createContainOnly') {

                // fetch building data from building table and only create containment centroid from geom in table
                $building = Building::find($request->bin);
                $building->sanitation_system_id = $containment->containmentType->sanitation_system_id;
                $building->toilet_status = true;
                $buildToiletPivot = BuildToilet::where('bin', $request->bin)->WhereNULL('deleted_at');
                if ($buildToiletPivot) {
                    $buildToiletPivot->delete();
                }
                $geom = $building->geom;
                $building->save();
                if ($geom) {

                    $containment_point = DB::select(DB::raw("SELECT (ST_AsText(st_centroid(st_union(geom)))) AS central_point FROM building_info.buildings WHERE bin = '$building->bin'"));
                    $containment->geom = DB::raw("ST_GeomFromText('" . $containment_point[0]->central_point . "', 4326)");
                }
            } elseif ($type == 'update') {
                // do no changes to geom if containment data is being updated only
            } else {

                // create new point from lat/lng if API request without KML file
                if ($request->has('lat') && $request->has('lng') && !$request->hasFile('geom') && !$request->kml) {
                    $lat = (float) $request->lat;
                    $lng = (float) $request->lng;
                    $containment->geom = DB::raw("ST_GeomFromText('POINT({$lng} {$lat})', 4326)");
                } elseif (!empty($request->bin) && !$request->hasFile('geom') && !$request->kml) {
                    $building = Building::where('bin', $request->bin)->first();
                    if ($building && $building->geom) {
                        $containment_point = DB::select(DB::raw("SELECT (ST_AsText(st_centroid(st_union(geom)))) AS central_point FROM building_info.buildings WHERE bin = '{$building->bin}'"));
                        if (!empty($containment_point[0]->central_point)) {
                            $containment->geom = DB::raw("ST_GeomFromText('" . $containment_point[0]->central_point . "', 4326)");
                        }
                    }
                } else {
                    // create new point from buildings centroid if new building and containment (web/kml)
                    $containment->geom = $this->storeGeomInfo($request, 'containment', 'create');
                }
            }
            $containment->save();
            $this->storeBuildContainInfo($request->bin, $containment->id);
            DB::commit();
        }
    }
    // if owner info is present, updates, else creates new and stores
    public function storeOwnerInfo($request)
    {
        $owner = Owner::where('bin', $request->bin)->whereNULL('deleted_at')->first();
        $isNewOwner = empty($owner);

        if ($isNewOwner) {
            $owner = new Owner();
            $owner->bin = $request->bin;
        }

        foreach (['owner_name', 'owner_gender', 'owner_contact', 'nid'] as $field) {
            if ($isNewOwner || $request->filled($field)) {
                $owner->{$field} = $request->filled($field)
                    ? $request->input($field)
                    : null;
            }
        }

        $owner->save();
    }

    public function storeBuildContainInfo($bin, $containment_id)
    {
        $build_contain = new BuildContain;
        $build_contain->bin = $bin;
        $build_contain->containment_id = $containment_id;
        $build_contain->save();
    }

    protected array $cachedKmlPoints = [];

    /**
     * Parse and extract clean, rounded coordinate points from KML (cached per request).
     */
    protected function extractKmlPoints($request, $type): ?array
    {
        $cacheKey = spl_object_hash($request) . '_' . ($request->survey_id ?? 'direct') . '_' . $type;
        if (isset($this->cachedKmlPoints[$cacheKey])) {
            return $this->cachedKmlPoints[$cacheKey];
        }

        $xml = new DOMDocument();
        if ($type == 'create') {
            if ($request->hasFile('geom')) {
                $xml->load($request->geom);
            } elseif ($request->kml) {
                $filepath = storage_path('app/public/building-survey-kml/' . $request->kml);
                if (File::exists($filepath)) {
                    $xml->load($filepath);
                } else {
                    return null;
                }
            }
        } elseif ($type == 'update') {
            if ($request->hasFile('geom')) {
                if (@$xml->load($request->geom) === false) {
                    return null;
                }
            } else {
                return null;
            }
        }

        $polygons = $xml->getElementsByTagName('Polygon');
        if ($polygons->length === 0) {
            return null;
        }

        $coordinates = $polygons[0]->getElementsByTagName('coordinates');
        if ($coordinates->length === 0) {
            return null;
        }

        $value = $coordinates[0]->nodeValue;
        $rawPoints = preg_split('/\s+/', trim($value));
        $cleanedPoints = [];

        foreach ($rawPoints as $point) {
            $arr = explode(',', trim($point));
            if (count($arr) >= 2 && is_numeric($arr[0]) && is_numeric($arr[1])) {
                // Round coordinates to 6 decimal places (~10cm precision) to optimize geometry storage
                $lng = number_format((float) $arr[0], 6, '.', '');
                $lat = number_format((float) $arr[1], 6, '.', '');
                $cleanedPoints[] = "{$lng} {$lat}";
            }
        }

        if (count($cleanedPoints) < 3) {
            return null;
        }

        // Auto-close linear ring if first and last point do not match
        if ($cleanedPoints[0] !== end($cleanedPoints)) {
            $cleanedPoints[] = $cleanedPoints[0];
        }

        return $this->cachedKmlPoints[$cacheKey] = $cleanedPoints;
    }

    public function storeGeomInfo($request, $flag, $type)
    {
        $points = $this->extractKmlPoints($request, $type);

        if (!$points) {
            if ($request->kml && !File::exists(storage_path('app/public/building-survey-kml/' . $request->kml))) {
                return Redirect::back()->with('error', __("Failed to update building structure : kml file not found"));
            }
            if ($type == 'update') {
                return Redirect::back()->with('error', __("Failed to update building structure due to invalid kml file"));
            }
            return null;
        }

        $wktPolygon = "MULTIPOLYGON(((" . implode(',', $points) . ")))";

        if ($flag == 'building') {
            // ST_MakeValid repairs self-intersections, ST_CollectionExtract extracts polygon geometries (3), ST_Multi enforces MULTIPOLYGON
            return DB::raw("ST_Multi(ST_CollectionExtract(ST_MakeValid(ST_GeomFromText('{$wktPolygon}', 4326)), 3))");
        } elseif ($flag == 'area') {
            return DB::raw("ST_Area(ST_Transform(ST_Multi(ST_CollectionExtract(ST_MakeValid(ST_GeomFromText('{$wktPolygon}', 4326)), 3)), 4326)::geography)");
        } elseif ($flag == 'containment') {
            // Direct PostGIS centroid computation without extra synchronous query
            return DB::raw("ST_Centroid(ST_GeomFromText('MULTIPOINT(" . implode(',', $points) . ")', 4326))");
        }
    }


    public function updateBuildingData($request, $id)
    {
        DB::beginTransaction();
        try {
            // settting error flag as no error initially
            $err = "no_error";
            $building = Building::find($id);
            $request->bin = $id;
            // if main building is no (0) then store BIN; else nullify it
            if ($request->main_building == false) {
                $building->building_associated_to = $request->building_associated_to ? $request->building_associated_to : null;
            }
            else {

                $building->building_associated_to = null;
            }
            $building->ward = $request->ward ? $request->ward : null;
            $building->road_code = $request->road_code ? $request->road_code : null;
            $building->house_number = $request->house_number ? $request->house_number : null;
            $building->house_locality = $request->house_locality ? $request->house_locality : null;
            $building->tax_code = $request->tax_code ? $request->tax_code : null;
            $building->structure_type_id = $request->structure_type_id ? $request->structure_type_id : null;
            $building->surveyed_date = $request->surveyed_date ? $request->surveyed_date : null;
            $building->construction_year = $request->construction_year ? $request->construction_year : null;
            $building->floor_count = $request->floor_count ? $request->floor_count : null;
            $building->functional_use_id = $request->functional_use_id ? $request->functional_use_id :  $request->functional_use_id;
            $building->use_category_id = $request->use_category_id ? $request->use_category_id : null;
            // if building is not residential, then store office business name, else nullify it
            if ($building->functional_use_id != "1") {
                $building->office_business_name = $request->office_business_name ? $request->office_business_name : null;
            }
            else
            {
                $building->office_business_name = null;
            }
            $building->household_served = $request->household_served ;
            $building->population_served = $request->population_served ;
            //male female other popn
            $building->male_population = $request->male_population ? $request->male_population : null;
            $building->female_population = $request->female_population ? $request->female_population : null;
            $building->other_population = $request->other_population ? $request->other_population : null;
            //disabled popn
            $building->diff_abled_male_pop = $request->diff_abled_male_pop ? $request->diff_abled_male_pop : null;
            $building->diff_abled_female_pop = $request->diff_abled_female_pop ? $request->diff_abled_female_pop : null;
            $building->diff_abled_others_pop = $request->diff_abled_others_pop ? $request->diff_abled_others_pop : null;
            //check whether the building is low income building
            $building->low_income_hh = $request->low_income_hh ;
            // if building is not in LIC, remove LIC id
            if($request->lic_status == true)
            {
                $building->lic_id = $request->lic_id ;
            }
            else
            {
                $building->lic_id = null;

            }
            $building->water_source_id = $request->water_source_id ? $request->water_source_id : null;
            // water source Municipal/Public water supply
            if(KeywordMatcher::matchKeywords($building->WaterSource->source, ["municipal","public"]))
            {
                $building->water_customer_id = $request->water_customer_id ? $request->water_customer_id : null;
                $building->watersupply_pipe_code = $request->watersupply_pipe_code ? $request->watersupply_pipe_code : null;
            }
            else
            {
                $building->water_customer_id = null;
                $building->watersupply_pipe_code = null;
            }

            $building->well_presence_status = $request->well_presence_status;
            if($building->well_presence_status)
            {
                $building->distance_from_well = $request->distance_from_well ? $request->distance_from_well : null;
            }
            else
            {
                $building->distance_from_well = null;
            }
            $building->swm_customer_id = $request->swm_customer_id ? $request->swm_customer_id : null;
            $building->toilet_status = $request->toilet_status;


            if ($request->hasFile('geom')) {
                $building->geom = $this->storeGeomInfo($request, 'building', 'update');
                $building->estimated_area = $this->storeGeomInfo($request, 'area', 'update');
            }
            $building->user_id = Auth::id();
            $this->storeOwnerInfo($request, $id);

            // toilet_status
            if ($building->toilet_status == true) {
                $building->toilet_count = $request->toilet_count ? $request->toilet_count : null;
                //NUMBER OF HOUSEHOLD THAT SHARE TOILET
                $building->household_with_private_toilet = $request->household_with_private_toilet ? $request->household_with_private_toilet : null;
                $building->population_with_private_toilet = $request->population_with_private_toilet ? $request->population_with_private_toilet : null;
                $buildToiletPivot = BuildToilet::where('bin', $request->bin)->WhereNULL('deleted_at');
                $buildToiletPivot->delete();
                $building->desludging_vehicle_accessible = $request->input('desludging_vehicle_accessible');

                $building->sanitation_system_id = null;
                // setting santiation system id from toilet connection if yes toilet
                $building->sanitation_system_id = $request->sanitation_system_id ? $request->sanitation_system_id : null;
                $request->sanitation_system = SanitationSystem::find($building->sanitation_system_id)->sanitation_system;
                //  conditions if sanitation system is shared containment
                // matching keyword shared of Shared Containment
                if ((KeywordMatcher::matchKeywords($request->sanitation_system, ["shared"]))) {
                    // store build contain relationship of new containment to existing BIN
                    // building is identified on basis $request->build_contain value
                    // corresponding containment ID is fetched of $request->build_contain
                    //  build_contain relation is updated with bin of this building and fetched containment ID
                    $building->sanitation_system_id  = $this->storeContainmentInfo($flag = 'shared', $type = 'create', $request);
                }
                // condition if sanitation system is septic tank or pit
                // matching keyword septic and pit, and not accepting shared keyword.
                elseif (
                    KeywordMatcher::matchKeywords($request->sanitation_system, ["septic", "pit"]) &&
                    (KeywordMatcher::matchKeywords($request->sanitation_system, ["shared"])) == false
                ) {
                    $building->sewer_code = $request->sewer_code ? $request->sewer_code : null;
                    $building->drain_code = $request->drain_code ? $request->drain_code : null;
                    // check if connected containment exists and the existing containment connection matches with the sanitation system that has been chosen
                    // corresponding sanitation_system_id of containment type is stored in containment_type table

                    if (empty($building->containments->first()) || $building->containments->first()->containmentType->sanitation_system_id != $building->sanitation_system_id) {
                        // validation e rror flag
                        $err = "containment_mismatch";
                    } else {
                        $err = "no_error";
                    }
                }
                // condition if sanitation system is sewer or drain or others
                // matching keyword drain, sewer and other left values
                elseif (KeywordMatcher::matchKeywords($request->sanitation_system, ["drain", "sewer", "onsite", "water", "ground", "composting"])) {
                    // check if there is existing containment connection
                    if (!empty($building->containments->first())) {
                        // validation error flag
                        $err = "containment_mismatch";
                    } else {
                        $err = "no_error";
                        // storing drain code if no previous containment connection and updated sanitation system is drain
                        if (KeywordMatcher::matchKeywords($request->sanitation_system, ["drain"]) != false) {
                            $building->drain_code = $request->drain_code ? $request->drain_code : null;
                            $building->sewer_code = null;
                        }
                        // storing sewer code if no previous containment connection and updated sanitation system is sewer
                        elseif (KeywordMatcher::matchKeywords($request->sanitation_system, ["sewer"]) != false) {
                            $building->sewer_code = $request->sewer_code ? $request->sewer_code : null;
                            $building->drain_code = null;
                        } else {
                            // flushing drain_code and sewer_code values if not connected to sewer, drain, septic or pit/holding tank
                            $building->drain_code = null;
                            $building->sewer_code = null;
                        }
                    }
                }
            } else {
                $building->toilet_count =  null;
                //NUMBER OF HOUSEHOLD THAT SHARE TOILET
                $building->household_with_private_toilet =  null;
                $building->population_with_private_toilet =  null;
                $building->drain_code = null;
                $building->sewer_code = null;
                // setting santiation system id from defecation area if no toilet
                $building->sanitation_system_id = $request->defecation_place ? $request->defecation_place : null;

                if (!KeywordMatcher::matchKeywords($request->sanitation_system, ["community"])) {
                    $buildToiletPivot = BuildToilet::where('bin', $request->bin)->WhereNULL('deleted_at');
                    $buildToiletPivot->delete();
                }
                $request->sanitation_system = SanitationSystem::find($building->sanitation_system_id)->sanitation_system;
                // toilet not present, so checking if containment connection is present.
                if (!empty($building->containments->first())) {
                    // validation error flag
                    $err = "containment_mismatch";
                } else {
                    // checking if new defecation area is community toilet
                    if (KeywordMatcher::matchKeywords($request->sanitation_system, ["community"])) {

                        // checks if there is any previous ct connection if build_toilet, removes it if any (if $type = update)
                        // creates new connection of CT with building in build_toilet table
                        $this->storeContainmentInfo($flag = 'communal', $type = 'update', $request);
                    }

                    //  set toilet count null, shared toilet count null, population that uses shared toilet null
                }
            }

            if ($err == 'containment_mismatch') {
                DB::rollback();
                return Redirect::back()->with('error', __("Sanitation System does not match with existing containment data, please update containment information and try again"));
            } else if ($err == 'false') {
                // no error so do nothing
            }
             // storing house image in folder /public/emptyings/houses
             if ($request->hasFile('house_image')) {
                // Validate the uploaded image
                $extension_house = strtolower($request->house_image->getClientOriginalExtension());
                $filename_house = $building->bin . '.' . $extension_house;

                $imageSaved = ImageOptimizationHelper::optimizeAndSave(
                    $request->house_image,
                    Storage::disk('local')->path('/public/emptyings/houses/' . $filename_house),
                    1600,
                    70
                );
                if (!$imageSaved) {
                    throw new \RuntimeException(__('Unable to save the building image.'));
                }
            }
            $building->save();
            // store owner
            $this->storeOwnerInfo($request);
            DB::commit();
            return Redirect("building-info/buildings")->with('success', __("Building Information updated successfully"));
        } catch (\Exception $e) {
            DB::rollback();
            return Redirect("building-info/buildings")->with('error', __("Failed to update building structure") . $e);
        }
    }

    public function updateBuildingFromContainment($request)
    {


        // fetching containment details to change building's sanitation system
        $containment = Containment::find($request->containment_id ?? $request);
        // update sanitation system  in connected buildings
        if ($containment->buildings) {
            // for each for multiple connected buildings
            foreach ($containment->buildings as $b) {
                $building = Building::find($b->bin);
                // assigning new sanitation system id as per new containment
                $building->sanitation_system_id = $containment->containmentType->sanitation_system_id;
                $request->sanitation_system = SanitationSystem::find($building->sanitation_system_id)->sanitation_system;

                // setting toilet status as true
                $building->toilet_status = true;
                // if sanitation system  or containment type has drian, storing drain code
                if (
                    KeywordMatcher::matchKeywords($request->sanitation_system, ["drain"]) ||
                    KeywordMatcher::matchKeywords($containment->containmentType->type, ["drain"])
                ) {
                    $building->drain_code = $request->drain_code ?? $building->drain_code;
                }
                // if sanitation system or containment type has sewer, storing sewer code
                elseif (
                    KeywordMatcher::matchKeywords($request->sanitation_system, ["sewer"]) ||
                    KeywordMatcher::matchKeywords($containment->containmentType->type, ["sewer"])
                ) {
                    $building->sewer_code = $request->sewer_code ?? $building->sewer_code;
                }
                // if building does not have any new sewer/ drain connections, checking existing containments for any sewer/ drain connections
                $status_drain = false;
                $status_sewer = false;
                // for each connected containment, checking if there is any drain or sewer connection previously
                foreach ($building->containments as $contain) {
                    // setting status true if there is any sewer drain connection
                    if (KeywordMatcher::matchKeywords($contain->containmentType->type, ["drain"])) {
                        $status_drain = true;
                    }
                    if (KeywordMatcher::matchKeywords($contain->containmentType->type, ["sewer"])) {
                        $status_sewer = true;
                    }
                }
                // nullify drain and sewer code only if there is no containment with sewer/drain connection
                if ($status_drain == false) {
                    $building->drain_code = null;
                }
                if ($status_sewer == false) {
                    $building->sewer_code = null;
                }
                $building->save();
            }
        }
    }
    public function fetchExport(bool $includeOwnerPii = false)
    {
        $searchData = isset($_GET['searchData']) ? $_GET['searchData'] : null;
        $bin = isset($_GET['bin']) ? $_GET['bin'] : null;
        $house_number =  isset($_GET['house_number']) ? $_GET['house_number'] : null;
        $structype = isset($_GET['structype']) ? $_GET['structype'] : null;
        $ward = isset($_GET['ward']) ? $_GET['ward'] : null;
        $functional_use = isset($_GET['functional_use_id']) ? $_GET['functional_use_id'] : null;
        $roadcd = isset($_GET['roadcd']) ? $_GET['roadcd'] : null;
        $toilet = isset($_GET['toilet']) ? $_GET['toilet'] : null;
        $toiletconn = isset($_GET['toiletconn']) ? $_GET['toiletconn'] : null;
        $watersourc = isset($_GET['watersourc']) ? $_GET['watersourc'] : null;
        $well_prese = isset($_GET['well_prese']) ? $_GET['well_prese'] : null;
        $functional_use = isset($_GET['functional_use']) ? $_GET['functional_use'] : null;
        $sanitation_system_id = isset($_GET['sanitation_system_id']) ? $_GET['sanitation_system_id'] : null;
        $floor_count = isset($_GET['floor_count']) ? $_GET['floor_count'] : null;
        $use_category_select = isset($_GET['use_category_select']) ? $_GET['use_category_select'] : null;
        $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;
        $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
        $columns = [
            __('BIN'),
            __('BIN of Main Building'),
            __('Ward'),
            __('Road Code'),
            __('House Number'),
            __('House Locality/Address'),
            __('Tax Code/Holding ID'),
            __('Structure Type'),
            __('Surveyed Date'),
            __('Construction Date'),
            __('Number of Floors'),
            __('Functional Use of Building'),
            __('Use Category of Building'),
            __('Office or Business Name'),
            __('Number of Households'),
            __('Male Population'),
            __('Female Population'),
            __('Other Population'),
            __('Population of Building'),
            __('Differently Abled Male Population'),
            __('Differently Abled Female Population'),
            __('Differently Abled Other Population'),
            __('Is Low Income House'),
            __('LIC Name'),
            __('Main Drinking Water Source'),
            __('Water Supply Customer ID'),
            __('Water Supply Pipe Line Code'),
            __('Well in Premises'),
            __('Distance of Well from Closest Containment (m)'),
            __('SWM Customer ID'),
            __('Presence of Toilet'),
            __('Number of Toilets'),
            __('Households with Private Toilet'),
            __('Population with Private Toilet'),
            __('Toilet Connection'),
            __('Sewer Code'),
            __('Drain Code'),
            __('Building Accessible to Desludging Vehicle'),
            __('Estimated Area of the Building (m²)'),
            __('Community Toilet Name'),
            __('Verification Status'),

        ];

        if ($includeOwnerPii) {
            // Keep the ordinary export non-PII by default. These headings are
            // appended only when the controller confirms an active, audited
            // list-wide owner reveal grant for the current user.
            $columns[] = __('Owner Name');
            $columns[] = __('Owner Contact');
            $columns[] = __('Owner Gender');
        }

        $query = DB::table('building_info.buildings as b')
        ->LeftJoin('building_info.structure_types as st', 'st.id', '=', 'b.structure_type_id')
        ->LeftJoin('building_info.functional_uses as f', 'f.id', '=', 'b.functional_use_id')
        ->LeftJoin('building_info.use_categorys as u', 'u.id', '=', 'b.use_category_id')
        ->LeftJoin('building_info.sanitation_systems as ss', 'ss.id', '=', 'b.sanitation_system_id')
        ->LeftJoin('building_info.water_sources as s', 's.id', '=', 'b.water_source_id')
        ->LeftJoin('layer_info.low_income_communities as lic', 'lic.id', '=', 'b.lic_id')
        ->leftJoin('fsm.build_toilets as bt', function($join) {
            $join->on('bt.bin', '=', 'b.bin')
                 ->whereNull('bt.deleted_at'); // Check if deleted_at is NULL
        })
        ->LeftJoin('fsm.toilets as t', 'bt.toilet_id', '=', 't.id') // Added join with fsm.toilets
        ->select(
            'b.bin',
            'b.tax_code',
            'b.house_number',
            'b.house_locality',
            'b.ward',
            'b.road_code',
            'st.type as structure_type',
            'b.floor_count',
            'b.construction_year',
            'b.household_served',
            'b.population_served',
            'b.surveyed_date',
            'f.name as functional_use_id',
            'u.name as use_category_id',
            'b.office_business_name',
            's.source as water_source',
            'b.building_associated_to',
            'b.well_presence_status',
            'b.distance_from_well',
            'b.toilet_status',
            'b.toilet_count',
            'b.household_with_private_toilet',
            'b.population_with_private_toilet',
            'ss.sanitation_system as sanitation_system',
            'b.sewer_code',
            'b.drain_code',
            'b.desludging_vehicle_accessible',
            'b.swm_customer_id',
            'b.water_customer_id',
            'b.estimated_area',
            'b.male_population',
            'b.female_population',
            'b.other_population',
            'b.diff_abled_male_pop',
            'b.diff_abled_female_pop',
            'b.diff_abled_others_pop',
            'b.verification_status',
            'lic.community_name as community_name',
            'b.low_income_hh',
            'b.watersupply_pipe_code',
            'bt.toilet_id',
            't.name as toilet_name' // Added toilets.name as toilet_name
        )
        ->orderBy('b.bin')
        ->distinct('b.bin')
        ->whereNull('b.deleted_at');

        if ($includeOwnerPii) {
            // Query encrypted owner values only for an authorized PII export.
            // Locked ordinary exports never select owner columns at all.
            $query->leftJoin('building_info.owners as owner', 'owner.bin', '=', 'b.bin')
                ->addSelect([
                    'owner.owner_name as owner_name',
                    'owner.owner_contact as owner_contact',
                    'owner.owner_gender as owner_gender',
                ]);
        }

        if (!empty($bin)) {
            $query->where('b.bin','ILIKE', '%'. $bin .'%');
        }

        if (!empty($structype)) {
            $query->where('b.structure_type_id', $structype);
        }
        if (!empty($house_number)) {
            $query->where('b.house_number','ILIKE', '%'. $house_number.'%');
        }
        if (!empty($ward)) {
            $query->where('b.ward', $ward);
        }

        if (!empty($functional_use)) {
            $query->where('b.functional_use_id', $functional_use);
        }

        if (!empty($roadcd)) {
            $query->where('b.road_code', $roadcd);
        }

        if (!empty($toilet)) {
            $query->where('b.toilet_status', $toilet);
        }

        if (!empty($watersourc)) {
            $query->where('b.water_source_id', $watersourc);
        }
        if (!empty($well_prese)) {
            $query->where('b.well_presence_status', $well_prese);
        }
        if (!empty($sanitation_system_id)) {
            $query->where('b.sanitation_system_id', $sanitation_system_id);
        }
        if (!empty($floor_count)) {
            $query->where('b.floor_count','ILIKE', $floor_count .'%');
        }
        if (!empty($use_category_select)) {
            $query->where('b.use_category_id','ILIKE', $use_category_select .'%');
        }
        if (!empty($date_from && $date_to)) {
            $query->whereDate('construction_year', '>=', $date_from);
            $query->whereDate('construction_year', '<=', $date_to);
        }
        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();
        $writer = WriterFactory::create(Type::CSV);
        $writer->openToBrowser('Buildings.csv')
            ->addRowWithStyle($columns, $style); //Top row of excel

        $query->chunk(5000, function ($buildings) use ($writer, $includeOwnerPii) {
        $query = DB::table('building_info.buildings as b')
        ->LeftJoin('building_info.structure_types as st', 'st.id', '=', 'b.structure_type_id')
        ->LeftJoin('building_info.functional_uses as f', 'f.id', '=', 'b.functional_use_id')
        ->LeftJoin('building_info.use_categorys as u', 'u.id', '=', 'b.use_category_id')
        ->LeftJoin('building_info.sanitation_systems as ss', 'ss.id', '=', 'b.sanitation_system_id')
        ->LeftJoin('building_info.water_sources as s', 's.id', '=', 'b.water_source_id')
        ->LeftJoin('layer_info.low_income_communities as lic', 'lic.id', '=', 'b.lic_id')
        ->leftJoin('fsm.build_toilets as bt', function($join) {
            $join->on('bt.bin', '=', 'b.bin')
                 ->whereNull('bt.deleted_at'); // Check if deleted_at is NULL
        })
        ->LeftJoin('fsm.toilets as t', 'bt.toilet_id', '=', 't.id') // Added join with fsm.toilets
        ->select(
            'b.bin',
            'b.tax_code',
            'b.house_number',
            'b.house_locality',
            'b.ward',
            'b.road_code',
            'st.type as structure_type',
            'b.floor_count',
            'b.construction_year',
            'b.household_served',
            'b.population_served',
            'b.surveyed_date',
            'f.name as functional_use_id',
            'u.name as use_category_id',
            'b.office_business_name',
            's.source as water_source',
            'b.building_associated_to',
            'b.well_presence_status',
            'b.distance_from_well',
            'b.toilet_status',
            'b.toilet_count',
            'b.household_with_private_toilet',
            'b.population_with_private_toilet',
            'ss.sanitation_system as sanitation_system',
            'b.sewer_code',
            'b.drain_code',
            'b.desludging_vehicle_accessible',
            'b.swm_customer_id',
            'b.water_customer_id',
            'b.estimated_area',
            'b.male_population',
            'b.female_population',
            'b.other_population',
            'b.diff_abled_male_pop',
            'b.diff_abled_female_pop',
            'b.diff_abled_others_pop',
            'b.verification_status',
            'lic.community_name as community_name',
            'b.low_income_hh',
            'b.watersupply_pipe_code',
            'bt.toilet_id',
            't.name as toilet_name'
        )
        ->distinct('b.bin')
        ->orderBy('b.bin')
        ->whereNull('b.deleted_at');
        foreach ($buildings as $building) {
            $values = [];
            $values[] = $building->bin;
            $values[] = $building->building_associated_to;
            $values[] = $building->ward;
            $values[] = $building->road_code;
            $values[] = $building->house_number;
            $values[] = $building->house_locality;
            $values[] = $building->tax_code;
            $values[] = $building->structure_type;
            $values[] = $building->surveyed_date;
            $values[] = $building->construction_year;
            $values[] = $building->floor_count;
            $values[] = $building->functional_use_id;
            $values[] = $building->use_category_id;
            $values[] = $building->office_business_name;
            $values[] = $building->household_served;
            $values[] = $building->male_population;
            $values[] = $building->female_population;
            $values[] = $building->other_population;
            $values[] = $building->population_served;
            $values[] = $building->diff_abled_male_pop;
            $values[] = $building->diff_abled_female_pop;
            $values[] = $building->diff_abled_others_pop;
            $values[] = is_null($building->low_income_hh)
            ? ''
            : ($building->low_income_hh === true ? 'Yes' : 'No');
            $values[] = $building->community_name;
            $values[] = $building->water_source;
            $values[] = $building->water_customer_id;
            $values[] = $building->watersupply_pipe_code;
            $values[] = is_null($building->well_presence_status)
            ? ''
            : ($building->well_presence_status === true ? 'Yes' : 'No');
            $values[] = $building->distance_from_well;
            $values[] = $building->swm_customer_id;
            $values[] = $building->toilet_status ? 'Yes' : 'No';
            $values[] = $building->toilet_count;
            $values[] = $building->household_with_private_toilet;
            $values[] = $building->population_with_private_toilet;
            $values[] = $building->sanitation_system;
            $values[] = $building->sewer_code;
            $values[] = $building->drain_code;
            $values[] = is_null($building->desludging_vehicle_accessible)
            ? ''
            : ($building->desludging_vehicle_accessible === true ? 'Yes' : 'No');
            $values[] = $building->estimated_area;
            $values[] = $building->toilet_name;
            $values[] = $building->verification_status ? 'Yes' : 'No';

            if ($includeOwnerPii) {
                // Values remain encrypted in PostgreSQL and are decrypted one
                // row at a time immediately before CSV output. Nothing is
                // written back to the database or stored in the session.
                $values[] = $this->escapeSpreadsheetValue(
                    $this->ownerPiiPresenter->presentPlaintextValue(
                        $building->owner_name
                    )
                );
                $values[] = $this->escapeSpreadsheetValue(
                    $this->ownerPiiPresenter->presentPlaintextValue(
                        $building->owner_contact
                    )
                );
                $values[] = $this->escapeSpreadsheetValue(
                    $this->ownerPiiPresenter->presentPlaintextValue(
                        $building->owner_gender
                    )
                );
            }

            $writer->addRow($values);
        }
        });

        $writer->close();
    }

    private function escapeSpreadsheetValue(?string $value): ?string
    {
        if ($value !== null && preg_match('/^[=+\-@]/u', $value) === 1) {
            return "'" . $value;
        }

        return $value;
    }
    public function fetchData(Request $request, bool $revealOwnerPii = false)
    {
        $matchingOwnerBins = null;
        $ownerNameSearch = mb_substr(
            trim((string) $request->input('ownername', '')),
            0,
            100
        );

        if ($revealOwnerPii && $ownerNameSearch !== '') {
            $matchingOwnerBins = [];

            foreach (Owner::select(['bin', 'owner_name'])->cursor() as $owner) {
                $ownerName = $owner->getAttributes()['owner_name'] ?? null;

                try {
                    if ($this->ownerPiiPresenter->ownerNameContains(
                        $ownerName,
                        $ownerNameSearch
                    )) {
                        $matchingOwnerBins[] = $owner->bin;
                    }
                } catch (DecryptException $exception) {
                    // Fail closed for legacy/invalid PII. The encryption
                    // verification command reports the affected rows without
                    // exposing their values.
                    continue;
                }
            }

            $matchingOwnerBins = array_values(array_unique($matchingOwnerBins));
        }

        // A query-builder allowlist prevents Building's automatically loaded
        // relationships from leaking owner records into the JSON response.
        $buildingData = DB::table('building_info.buildings AS b')
            ->leftJoin('building_info.owners AS o', 'b.bin', '=', 'o.bin')
            ->leftJoin('building_info.structure_types AS st', 'st.id', '=', 'b.structure_type_id')
            ->leftJoin('building_info.sanitation_systems AS ss', 'b.sanitation_system_id', '=', 'ss.id')
            ->select(
                'b.bin AS bin',
                'b.public_id AS public_id',
                'b.house_number AS house_number',
                'st.type AS type',
                'b.ward AS ward',
                'b.floor_count AS floor_count',
                'b.toilet_status AS toilet_status',
                'b.road_code AS road_code',
                'o.owner_name AS owner_name',
                'ss.sanitation_system AS sanitation_system_id'
            )
            ->whereNull('b.deleted_at');
        return DataTables::of($buildingData)
            ->filter(function ($query) use ($request, $matchingOwnerBins) {
                if ($request->bin) {
                    $query->where('b.bin','ILIKE', '%'.$request->bin .'%');
                }

                if ($request->structype) {
                    $query->where('b.structure_type_id', $request->structype);
                }

                if ($request->ward) {
                    $query->where('b.ward', $request->ward);
                }

                if ($request->functional_use) {
                    $query->where('b.functional_use_id', '=', $request->functional_use);
                }

                if ($request->roadcd) {
                    $query->where('b.road_code', $request->roadcd);
                }

                if ($request->toilet) {
                    $query->where('b.toilet_status', $request->toilet);
                }

                if ($request->watersourc) {
                    $query->where('b.water_source_id', $request->watersourc);
                }

                if ($request->well_prese) {
                    $query->where('b.well_presence_status', $request->well_prese);
                }

                if ($request->sanitation_system_id) {
                    $query->where('b.sanitation_system_id', $request->sanitation_system_id);
                }
                if ($request->floor_count) {

                    $query->where('b.floor_count','ILIKE', $request->floor_count .'%');
                }
                if ($request->house_number) {

                    $query->where('b.house_number','ILIKE', '%'.  $request->house_number.'%');
                }
                if ($matchingOwnerBins !== null) {
                    $query->whereIn(
                        'b.bin',
                        $matchingOwnerBins
                    );
                }
                if ($request->date_from && $request->date_to) {
                    $query->whereDate('b.construction_year', '>=', $request->date_from);
                    $query->whereDate('b.construction_year', '<=', $request->date_to);
                }

                if ($request->use_category_select) {

                    $query->where('b.use_category_id','ILIKE', '%'.  $request->use_category_select.'%');
                }
            })
            ->editColumn('owner_name', function ($model) use ($revealOwnerPii) {
                if (!$revealOwnerPii) {
                    return $this->ownerPiiPresenter->maskFully($model->owner_name);
                }

                try {
                    return $this->ownerPiiPresenter->presentPlaintextValue($model->owner_name);
                } catch (DecryptException $exception) {
                    return $this->ownerPiiPresenter->maskFully($model->owner_name);
                }
            })
            ->addColumn('action', function ($model) {

                $content = \Form::open(['method' => 'DELETE', 'route' => ['buildings.destroy', $model->public_id]]);

                if (auth()->user()->can('View Containments Connected to Buildings')) {
                    $content .= '<a title="' . e(__("View Containments Connected to Building")) . '" data-id="' . $model->public_id . '" class="containment btn btn-info btn-sm mb-1" data-toggle="modal" data-target="#containmentsModal"><i class="fa-solid fa-building"></i></a> ';
                }

                if (auth()->user()->can('Edit Building Structure')) {
                    $content .= '<a title="' . e(__("Edit")) . '" href="' . action("BuildingInfo\BuildingController@edit", [$model->public_id]) . '" class="btn btn-info btn-sm mb-1"  ><i class="fas fa-edit"></i></a> ';
                }

                if (auth()->user()->can('View Building Structure')) {
                    $content .= '<a title="' . e(__("Detail")) . '" href="' . action("BuildingInfo\BuildingController@show", [$model->public_id]) . '" class="btn btn-info btn-sm mb-1"  ><i class="fas fa-list"></i></a> ';
                }

                if (auth()->user()->can('View Building Structures History')) {
                    $content .= '<a title="' . e(__("History")) . '" href="' . action("BuildingInfo\BuildingController@history", [$model->public_id]) . '" class="btn btn-info btn-sm mb-1"  ><i class="fas fa-history"></i></a> ';
                }

                if (auth()->user()->can('Delete Building Structure')) {
                    $content .= '<a href="#" title="' . e(__("Delete")) . '" class="delete btn btn-danger btn-sm mb-1 "  ><i class="fas fa-trash"></i></a> ';
                }

                if (auth()->user()->can('View Building On Map')) {
                    $content .= '<a title="' . e(__("Map")) . '" href="' . action("MapsController@index", ['layer' => 'buildings_layer', 'field' => 'public_id', 'val' => $model->public_id]) . '" class="btn btn-info btn-sm mb-1"  ><i class="fas fa-map-marker"></i></a> ';
                }
                if (auth()->user()->can('View Nearest Road To Building On Map')) {
                    $content .= '<a title="' . e(__("Nearest Road")) . '" href="' . action("MapsController@index", ['layer' => 'buildings_layer', 'field' => 'public_id', 'val' => $model->public_id, 'action' => 'building-road']) . '" class="btn btn-info btn-sm mb-1"  ><i class="fas fa-road"></i></a> ';
                }

                $content .= \Form::close();
                return $content;
            })
            ->editColumn('toilet_status', function ($model) {
                return is_null($model->toilet_status) ? '-' : ($model->toilet_status ? 'Yes' : 'No');
            })
            ->removeColumn('public_id')
            ->make(true);
    }
    // buildings drop down (bin of pre connected building, application page-> house_number )
    // Dropdown values- House Number where available, else BIN number is displayed, all buildings with containments
    public function fetchHouseNumber()
    {
        $query = Building::whereHas('containments'); // Only include buildings with containments

        if (request()->search) {
            $query->where(function ($query) {
                $query->where('bin', 'ilike', '%' . request()->search . '%')
                    ->orWhere('house_number', 'ilike', '%' . request()->search . '%');
            });
        }

        if (request()->exclude_bin) {
            $query->where('bin', '!=', request()->exclude_bin);
        }

        if (request()->road_code) {
            $query->where('road_code', '=', request()->road_code);
        }

        $total = $query->count();
        $limit = 10;
        $page = request()->page ?? 1;
        $start_from = ($page - 1) * $limit;
        $total_pages = ceil($total / $limit);
        $more = $page < $total_pages;

        $bins = $query->offset($start_from)
            ->limit($limit)
            ->get(['bin', 'house_number']); // Retrieve only the required fields

        $json = [];
        foreach ($bins as $bin) {
            $json[] = [
                'id' => $bin['bin'],
                'text' => $bin['house_number'] ?? $bin['bin']
            ];
        }

        return response()->json([
            'results' => $json,
            'pagination' => ['more' => $more]
        ]);
    }

    // buildings drop down (bin of main building)
    // Dropdown values- House Number where available, else BIN number is displayed, all buildings with building_associated_to null
    public function fetchHouseNumberAll()
    {
        $query = Building::select('*')
            ->whereNull('deleted_at')
            ->whereNull('building_associated_to');
        if (request()->search) {
            $query->where(function ($query) {
                $query->where('bin', 'ilike', '%' . request()->search . '%')
                    ->orWhere('house_number', 'ilike', '%' . request()->search . '%');
            });
        }
        if (request()->road_code) {
            $query->where('road_code', '=', request()->road_code);
        }
        $total = $query->count();
        $limit = 10;
        if (request()->page) {
            $page  = request()->page;
        } else {
            $page = 1;
        };
        $start_from = ($page - 1) * $limit;
        $total_pages = ceil($total / $limit);
        if ($page < $total_pages) {
            $more = true;
        } else {
            $more = false;
        }
        $house_numbers = $query->offset($start_from)
            ->limit($limit)
            ->get();
        $json = [];
        foreach ($house_numbers as $house_number) {
            $json[] = ['id' => $house_number['bin'], 'text' => $house_number['house_number'] ?? $house_number['bin']];
        }
        return response()->json(['results' => $json, 'pagination' => ['more' => $more]]);
    }

     // Dropdown values- for CTPT BIN dropdown that filters buildings with use category as Toilets and is not already in Toilets table
     public function fetchCTPTHouseNumber()
     {

        $query = Building::select('building_info.buildings.bin', 'building_info.buildings.house_number')
        ->join('building_info.use_categorys', 'building_info.buildings.use_category_id', '=', 'building_info.use_categorys.id')
        ->leftJoin('fsm.toilets', 'building_info.buildings.bin', '=', 'fsm.toilets.bin')
        ->where('building_info.use_categorys.name', 'ILIKE', request('toilet_type'))
        ->whereNull('fsm.toilets.id')
        ->whereNull('fsm.toilets.deleted_at')
        ->whereNull('building_info.buildings.deleted_at');


     if (request()->search) {

         $query->where(function ($query) {

             $query->where('building_info.buildings.bin', 'ilike', '%' . request()->search . '%')
                 ->orWhere('building_info.buildings.house_number', 'ilike', '%' . request()->search . '%');
         });
     }
     $buildings = $query->get();
     $total = $query->count();

     $limit = 10;
     if (request()->page) {
         $page  = request()->page;
     } else {
         $page = 1;
     };
     $start_from = ($page - 1) * $limit;

     $total_pages = ceil($total / $limit);
     if ($page < $total_pages) {
         $more = true;
     } else {
         $more = false;
     }
     $house_numbers = $query->offset($start_from)
         ->limit($limit)
         ->get();
     $json = [];
     foreach ($house_numbers as $house_number) {
         $json[] = ['id' => $house_number['bin'], 'text' => $house_number['house_number'] ?? $house_number['bin']];
     }

     return response()->json(['results' => $json, 'pagination' => ['more' => $more]]);
 }
}
// Last Modified Date: 09-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)    for .php files
