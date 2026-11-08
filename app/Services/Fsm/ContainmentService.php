<?php
// Last Modified Date: 10-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)
namespace App\Services\Fsm;

use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use Auth;
use DataTables;
use DB;
use DOMDocument;
use DomXpath;
use App\Models\Fsm\Containment;
use App\Models\Fsm\Application;
use App\Models\BuildingInfo\Building;
use App\Models\BuildingInfo\BuildContain;
use Illuminate\Http\Request;
use App\Services\BuildingInfo\BuildingStructureService;
use App\Models\BuildingInfo\SanitationSystemTechnology;

class ContainmentService
{

    public function fetchData($request)
    {
        $containmentData = Containment::select('*')->whereNull('deleted_at');

        return DataTables::of($containmentData)
            ->filter(function ($query) use ($request) {
                if ($request->containment_id) {
                    $query->where('id', 'ILIKE', '%' .  $request->containment_id . '%');
                }
                if ($request->volume_min && $request->volume_max && $request->volume_min < $request->volume_max) {

                    $query->where('size', '>=', $request->volume_min);
                    $query->where('size', '<=', $request->volume_max);
                }


                if ($request->type_id) {
                    $query->where('type_id', $request->type_id);
                }
                if (!is_null($request->containment_location)) {
                    $selectedOption = $request->containment_location;
                    $query->where('location', $selectedOption);
                }
                if ($request->emptying_status) {
                    $query->where('emptied_status', $request->emptying_status);
                }
                if ($request->roadcd) {

                    $query->where('road_code', 'ILIKE', '%' . $request->roadcd . '%');
                }
                if ($request->bin) {

                    $query->whereHas("buildings", function ($q) use ($request) {
                        $q->where('buildings.bin', 'ILIKE', '%' . $request->bin . '%');
                    });
                }
                if ($request->house_number) {

                    $query->whereHas("buildings", function ($q) use ($request) {
                        $q->where('buildings.house_number', 'ILIKE', '%' . $request->house_number . '%');
                    });
                }
                if ($request->emptying_status) {
                    $query->where('emptied_status', $request->emptying_status);
                }
                if ($request->septic_compliance) {

                    $query->where('septic_criteria', $request->septic_compliance);
                }
            if ($request->date_from && $request->date_to && $request->date_from <= $request->date_to) {
                $query->whereDate('construction_date', '>=', $request->date_from);
                $query->whereDate('construction_date', '<=', $request->date_to);
                }
            })
            ->addColumn('action', function ($model) {
                $content = \Form::open(['method' => 'DELETE', 'route' => ['containments.destroy', $model->public_id]]);

                if (Auth::user()->can('List Containment Buildings')) {
                    $content .= '<a title="' . __("View Building Connected to Containment") . '" href="' . action("Fsm\ContainmentController@listBuildings", [$model->public_id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa-solid fa-building"></i></a> ';
                }
                if (Auth::user()->can('Edit Containment')) {
                    $content .= '<a title="' . __("Edit") . '" href="' . action("Fsm\ContainmentController@edit", [$model->public_id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-edit"></i></a> ';
                }
                if (Auth::user()->can('View Containment')) {
                    $content .= '<a title="' . __("Detail") . '" href="' . action("Fsm\ContainmentController@show", [$model->public_id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                }

                if (Auth::user()->can('View Containment History')) {
                    $content .= '<a title="' . __("History") . '" href="' . action("Fsm\ContainmentController@history", [$model->public_id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-history"></i></a> ';
                }

                if (Auth::user()->can('View Containment History')) {
                    $content .= '<a title="' . __("Type Change History") . '" href="' . action("Fsm\ContainmentController@typeChangeHistory", [$model->public_id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa-sharp fa-solid fa-file-pen"></i></a> ';
                }

                if (Auth::user()->can('Delete Containment')) {
                    $content .= '<a href="#" title="' . __("Delete") . '" class="delete btn btn-danger btn-sm mb-1"><i class="fa fa-trash"></i></a> ';
                }

                if (Auth::user()->can('View Containment On Map')) {
                    $content .= '<a title="' . __("Map") . '" href="' . action("MapsController@index", ['layer' => 'containments_layer', 'field' => 'public_id', 'val' => $model->public_id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-map-marker"></i></a> ';
                }
                if (Auth::user()->can('Emptying Service History')) {
                    $content .= '<a title="' . __("Emptying Service History") . '" href="' . action("Fsm\EmptyingController@index", ['containment_public_id' => $model->public_id]) . '" class="btn btn-info btn-sm mb-1 ' . (($model->emptyingService()->exists()) ? '"' : 'disabled"') .  '><i class="fa fa-recycle"></i></a> ';
                }


                if (Auth::user()->can('View Nearest Road To Containment On Map')) {
                    $content .= '<a title="' . __("Nearest Road") . '" href="' . action("MapsController@index", ['layer' => 'containments_layer', 'field' => 'public_id', 'val' => $model->public_id, 'action' => 'containment-road']) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-road"></i></a> ';
                }

                $content .= \Form::close();
                return $content;
            })
            ->editColumn('type_id', function ($model) {
                return $model->containmentType->type;
            })

            ->make(true);
    }


    public function fetchBuildingContainmentData($request)
    {
        $containmentData = DB::table('building_info.build_contains AS bc')
                ->leftjoin('building_info.buildings AS b', 'b.bin', '=', 'bc.bin')
                ->leftjoin('fsm.containments AS c', 'c.id', '=', 'bc.containment_id')
                ->leftjoin('fsm.containment_types AS ct', 'ct.id', '=', 'c.type_id')
                ->select('c.id', 'c.public_id', 'ct.type', 'c.size', 'c.location')
                ->where('bc.bin', $request->id)
                ->whereNull('bc.deleted_at')
                ->get();

        return DataTables::of($containmentData)
            ->addColumn('action', function ($model, Request $request) {

                $content = \Form::open(['method' => 'DELETE', 'action' => ['Fsm\ContainmentController@deleteBuilding', $model->public_id, $request->building_public_id]]);
                if (Auth::user()->can('Edit Containment')) {
                    $content .= '<a title="' . __("Edit") . '" href="' . action("Fsm\ContainmentController@edit", [$model->public_id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-edit"></i></a> ';
                }
                if (Auth::user()->can('View Containment')) {
                    $content .= '<a title="' . __("Detail") . '" href="' . action("Fsm\ContainmentController@show", [$model->public_id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                }
                if (Auth::user()->can('View Containment History')) {
                    $content .= '<a title="' . __("History") . '" href="' . action("Fsm\ContainmentController@history", [$model->public_id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-history"></i></a> ';
                }
                if (Auth::user()->can('Delete Building from Containment')) {
                    $content .= '<a href="#" title= "'. __("Delete Connection of Containment from Building") .'" class="delete btn btn-danger btn-sm mb-1"><i class="fa fa-trash"></i></a> ';
                }
                $content .= \Form::close();
                return $content;
            })
            ->make(true);
    }



    public function getExport($data)
    {
        $searchData = $data['searchData'] ? $data['searchData'] : null;
        $containment_id = $data['containment_id'] ? $data['containment_id'] : null;
        $type_id =  $data['type_id'] ? $data['type_id'] : null;
        $volume_min =  $data['volume_min'] ? $data['volume_min'] : null;
        $volume_max =  $data['volume_max'] ? $data['volume_max'] : null;
        $containment_location =  $data['containment_location'] ? $data['containment_location'] : null;
        $emptying_status =  $data['emptying_status'] ? $data['emptying_status'] : null;
        $septic_compliance =  $data['septic_compliance'] ? $data['septic_compliance'] : null;
        $house_number =  $data['house_number'] ? $data['house_number'] : null;
        // $roadcd = isset($_GET['roadcd']) ? $_GET['roadcd'] : null;
        $date_from = $data['date_from'] ?? null;
        $date_to = $data['date_to'] ?? null;
        $bin =  $data['bin'] ? $data['bin'] : null;
        $const_date =  $data['const_date'] ? $data['const_date'] : null;

        $columns = [
            __('Containment Code'),
            __('Containment Type'),
            __('Tank Length (m)'),
            __('Tank Width (m)'),
            __('Depth (m)'),
            __('Pit Diameter (m)'),
            __('Containment Volume (m³)'),
            __('Containment Location'),
            __('Septic Tank Standard Compliance'),
            __('Construction Date'),
            __('Emptied Status'),
            __('Last Emptied Date'),
            __('Next Emptying Date'),
            __('Number of Times Emptied'),
            __('Responsible BIN')

        ];
        $query = Containment::select([
            'containments.*',
            'fsm.containment_types.type as containment_type'
        ])
            ->leftJoin('fsm.containment_types', 'containments.type_id', '=', 'fsm.containment_types.id')
            ->whereNull('containments.deleted_at');

        // Apply your filters here
        if (!empty($containment_id)) {
            $query->where('containments.id','ILIKE', '%'. $containment_id .'%');
        }

        if (!empty($volume_min) && !empty($volume_max)) {
            $query->where('size', '>=', $volume_min);
            $query->where('size', '<=', $volume_max);
        }

        if (!empty($type_id)) {
            $query->where('containments.type_id', $type_id);
        }

        if (!empty($emptying_status)) {
            $query->where('containments.emptied_status', $emptying_status);
        }

        if (!empty($containment_location)) {
            $selectedOption = $_GET['containment_location'];
            $query->where('containments.location', $selectedOption);
        }

        if (!empty($septic_compliance)) {
            $query->where('containments.septic_criteria', $septic_compliance);
        }

        if (!empty($bin)) {
            $query->whereHas("buildings", function ($q) use ($bin) {
                $q->where('buildings.bin', 'ILIKE', '%' . $bin . '%');
            });
        }

        if (!empty($house_number)) {
            $query->whereHas("buildings", function ($q) use ($house_number) {
                $q->where('buildings.house_number', 'ILIKE', '%' . $house_number . '%');
            });
        }

        if ($date_from && $date_to) {
            $query->whereBetween('containments.construction_date', [$date_from, $date_to]);
        }

        // Add styles and export logic
        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();

        $writer = WriterFactory::create(Type::CSV);
        $writer->openToBrowser('Containments.csv')
            ->addRowWithStyle($columns, $style);

        $query->chunk(5000, function ($containments) use ($writer) {
            foreach ($containments as $containment) {
                $values = [];
                $values[] = $containment->id;
                $values[] = $containment->containment_type; // This will now be the type name
                $values[] = $containment->tank_length;
                $values[] = $containment->tank_width;
                $values[] = $containment->depth;
                $values[] = $containment->pit_diameter;
                $values[] = $containment->size;
                $values[] = $containment->location;
                $values[] = $containment->septic_criteria;
                $values[] = $containment->construction_date;
                $values[] = $containment->emptied_status;
                $values[] = $containment->last_emptied_date;
                $values[] = $containment->next_emptying_date;
                $values[] = $containment->no_of_times_emptied;
                $values[] = $containment->responsible_bin;
                $writer->addRow($values);
            }
        });

        $writer->close();
    }
    public function getExportBuildingContainment($data)
    {
        $searchData = $data['searchData'] ? $data['searchData'] : null;
        $containment_id = $data['containment_id'] ? $data['containment_id'] : null;
        $type_id =  $data['type_id'] ? $data['type_id'] : null;
        $volume_min =  $data['volume_min'] ? $data['volume_min'] : null;
        $volume_max =  $data['volume_max'] ? $data['volume_max'] : null;
        $containment_location =  $data['containment_location'] ? $data['containment_location'] : null;
        $emptying_status =  $data['emptying_status'] ? $data['emptying_status'] : null;
        $septic_compliance =  $data['septic_compliance'] ? $data['septic_compliance'] : null;
        $house_number =  $data['house_number'] ? $data['house_number'] : null;
        $bin =  $data['bin'] ? $data['bin'] : null;
        $const_date =  $data['const_date'] ? $data['const_date'] : null;
        $date_from = $data['date_from'] ?? null;
        $date_to = $data['date_to'] ?? null;

        $columns = [
            __('BIN'),
            __('Owner Name'),
            __('Owner NID'),
            __('Owner Gender'),
            __('Owner Contact Number'),
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
            __('Containment Code'),
            __('Containment Type'),
            __('Tank Length (m)'),
            __('Tank Width (m)'),
            __('Depth (m)'),
            __('Pit Diameter (m)'),
            __('Containment Volume (m³)'),
            __('Containment Location'),
            __('Septic Tank Standard Compliance'),
            __('Construction Date'),
            __('Emptied Status'),
            __('Last Emptied Date'),
            __('Next Emptying Date'),
            __('Number of Times Emptied'),
            __('Responsible BIN'),
        ];

        $query = DB::table('fsm.containments as c')
        ->LeftJoin('fsm.containment_types as ct', 'ct.id', '=', 'c.type_id')
        ->LeftJoin('building_info.build_contains as bc', function($join) {
            $join->on('bc.containment_id', '=', 'c.id')
                 ->whereNull('bc.deleted_at')
                 ->whereNoTNull('bc.bin')
                 ->whereNoTNull('bc.containment_id');
        }) // Check if deleted_at is NULL
        ->LeftJoin('building_info.buildings as b', function($join) {
            $join->on('b.bin', '=', 'bc.bin')
                 ->whereNull('b.deleted_at');
        }) // Check if deleted_at is NULL
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
        ->LeftJoin('building_info.owners', 'b.bin', '=', 'owners.bin')
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
            'owners.owner_name',
            'owners.owner_gender',
            'owners.owner_contact',
            'owners.nid',
            'lic.community_name as community_name',
            'b.low_income_hh',
            'b.watersupply_pipe_code',
            'bt.toilet_id',
            't.name as toilet_name', // Added toilets.name as toilet_name
            'c.*',
            'ct.type as containment_type'
            )
        ->orderBy('b.bin')
        ->whereNull('c.deleted_at');
        // Apply your filters here
        if (!empty($containment_id)) {
            $query->where('c.id','ILIKE', '%' .$containment_id .'%');
        }
        if (!empty($volume_min) && !empty($volume_max)) {
            $query->where('size', '>=', $volume_min);
            $query->where('size', '<=', $volume_max);
        }
        if (!empty($type_id)) {
            $query->where('c.type_id', $type_id);
        }

        if (!empty($emptying_status)) {
            $query->where('c.emptied_status', $emptying_status);
        }

        if (!empty($containment_location)) {
            $selectedOption = $_GET['containment_location'];
            $query->where('c.location', $selectedOption);
        }

        if (!empty($septic_compliance)) {
            $query->where('c.septic_criteria', $septic_compliance);
        }

        if (!empty($bin)) {
            $query->where('b.bin', 'ILIKE', '%' . $bin . '%') ;
        }

        if (!empty($house_number)) {
            $query->where('b.house_number', 'ILIKE', '%' . $house_number . '%') ;
        }

        if ($date_from && $date_to) {
            $query->whereBetween('c.construction_date', [$date_from, $date_to]);
        }


        // Add styles and export logic
        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();

        $writer = WriterFactory::create(Type::CSV);
        $writer->openToBrowser('Buildings Containments.csv')
            ->addRowWithStyle($columns, $style);

        $query->chunk(5000, function ($buildng_containments) use ($writer) {
            foreach ($buildng_containments as $buildng_containment) {
                $values = [];
                $values[] = $buildng_containment->bin;
                $values[] = $buildng_containment->owner_name;
                $values[] = $buildng_containment->nid;
                $values[] = $buildng_containment->owner_gender;
                $values[] = $buildng_containment->owner_contact;
                $values[] = $buildng_containment->building_associated_to;
                $values[] = $buildng_containment->ward;
                $values[] = $buildng_containment->road_code;
                $values[] = $buildng_containment->house_number;
                $values[] = $buildng_containment->house_locality;
                $values[] = $buildng_containment->tax_code;
                $values[] = $buildng_containment->structure_type;
                $values[] = $buildng_containment->surveyed_date;
                $values[] = $buildng_containment->construction_year;
                $values[] = $buildng_containment->floor_count;
                $values[] = $buildng_containment->functional_use_id;
                $values[] = $buildng_containment->use_category_id;
                $values[] = $buildng_containment->office_business_name;
                $values[] = $buildng_containment->household_served;
                $values[] = $buildng_containment->male_population;
                $values[] = $buildng_containment->female_population;
                $values[] = $buildng_containment->other_population;
                $values[] = $buildng_containment->population_served;
                $values[] = $buildng_containment->diff_abled_male_pop;
                $values[] = $buildng_containment->diff_abled_female_pop;
                $values[] = $buildng_containment->diff_abled_others_pop;
                $values[] = is_null($buildng_containment->low_income_hh)
                ? ''
                : ($buildng_containment->low_income_hh === true ? 'Yes' : 'No');
                $values[] = $buildng_containment->community_name;
                $values[] = $buildng_containment->water_source;
                $values[] = $buildng_containment->water_customer_id;
                $values[] = $buildng_containment->watersupply_pipe_code;
                $values[] = is_null($buildng_containment->well_presence_status)
                ? ''
                : ($buildng_containment->well_presence_status === true ? 'Yes' : 'No');
                $values[] = $buildng_containment->distance_from_well;
                $values[] = $buildng_containment->swm_customer_id;
                $values[] = $buildng_containment->toilet_status ? 'Yes' : 'No';
                $values[] = $buildng_containment->toilet_count;
                $values[] = $buildng_containment->household_with_private_toilet;
                $values[] = $buildng_containment->population_with_private_toilet;
                $values[] = $buildng_containment->sanitation_system;
                $values[] = $buildng_containment->sewer_code;
                $values[] = $buildng_containment->drain_code;
                $values[] = is_null($buildng_containment->desludging_vehicle_accessible)
                ? ''
                : ($buildng_containment->desludging_vehicle_accessible === true ? 'Yes' : 'No');
                $values[] = $buildng_containment->estimated_area;
                $values[] = $buildng_containment->toilet_name;
                $values[] = $buildng_containment->verification_status ? 'Yes' : 'No';
                $values[] = $buildng_containment->id;
                $values[] = $buildng_containment->containment_type; // This will now be the type name
                $values[] = $buildng_containment->tank_length;
                $values[] = $buildng_containment->tank_width;
                $values[] = $buildng_containment->depth;
                $values[] = $buildng_containment->pit_diameter;
                $values[] = $buildng_containment->size;
                $values[] = $buildng_containment->location;
                $values[] = $buildng_containment->septic_criteria;
                $values[] = $buildng_containment->construction_date;
                $values[] = is_null($buildng_containment->emptied_status)
                ? ''
                : ($buildng_containment->emptied_status === true ? 'Yes' : 'No');
                $values[] = $buildng_containment->last_emptied_date;
                $values[] = $buildng_containment->next_emptying_date;
                $values[] = $buildng_containment->no_of_times_emptied;
                $values[] = $buildng_containment->responsible_bin;
                $writer->addRow($values);
            }
        });

        $writer->close();
    }

    public function fetchContainmentID()
    {
        $query = Containment::select('*');
        if (request()->search) {
            $query->where('id', 'ilike', '%' . request()->search . '%');
        }
        if (request()->type) {
            $query->where('type', 'ilike', '%' . request()->type . '%');
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
            $json[] = ['id' => $house_number['id'], 'text' => $house_number['id']];
        }

        return response()->json(['results' => $json, 'pagination' => ['more' => $more]]);
    }
}
