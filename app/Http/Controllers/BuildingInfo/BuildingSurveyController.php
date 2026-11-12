<?php

namespace App\Http\Controllers\BuildingInfo;

use DB;
use Auth;
use DataTables;

use DOMDocument;
use Carbon\Carbon;
use App\Enums\LicStatus;
use App\Models\Fsm\Ctpt;
use Illuminate\Http\Request;
use App\Models\LayerInfo\Lic;
use App\Models\LayerInfo\Ward;
use App\Models\Fsm\Containment;
use App\Models\UtilityInfo\Drain;
use App\Models\BuildingInfo\Owner;
use App\Models\Fsm\ContainmentType;
use App\Http\Controllers\Controller;
use App\Models\UtilityInfo\Roadline;
use Illuminate\Support\Facades\File;
use App\Models\BuildingInfo\Building;
use App\Models\UtilityInfo\SewerLine;
use App\Models\BuildingInfo\UseCategory;
use App\Models\BuildingInfo\WaterSource;
use App\Models\UtilityInfo\WaterSupplys;
use App\Models\BuildingInfo\BuildContain;
use App\Models\BuildingInfo\FunctionalUse;
use App\Models\BuildingInfo\StructureType;
use App\Models\BuildingInfo\BuildingSurvey;
use App\Models\BuildingInfo\SanitationSystem;
use App\Models\BuildingInfo\SanitationSystemTechnology;

class BuildingSurveyController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:List Building Surveys', ['only' => ['index']]);
        $this->middleware('permission:Delete Building Survey', ['only' => ['destroy']]);
        $this->middleware('permission:Download Building Survey', ['only' => ['download']]);
        $this->middleware('permission:Approve Building Survey', ['only' => ['approve', 'saveApproved']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = __("Building Survey");

        return view('building-info.building-surveys.index',compact('page_title'));
    }

    public function create()
    {

    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getData(Request $request)
    {
        $buildingSurvey = BuildingSurvey::where('is_enabled', true)->whereNull('deleted_at');
        return Datatables::of($buildingSurvey)
            ->filter(function ($query) use ($request) {


                if ($request->temp_building_code) {
                    $query->where('temp_building_code','ILIKE', '%'.  $request->temp_building_code.'%');

                }
                if ($request->date_from && $request->date_to) {
                    $query->whereDate('collected_date', '>=', $request->date_from);
                    $query->whereDate('collected_date', '<=', $request->date_to);
                }

            })
            ->addColumn('action', function ($model) {
                $content = \Form::open(['method' => 'DELETE', 'route' => ['building-surveys.destroy', $model->id]]);

                 if (Auth::user()->can('Approve Building Survey')) {
                     $content .= '<a title="' . e(__("Approve Building Structure")) . '" href="' . action("BuildingInfo\BuildingSurveyController@approve", [$model->id]) . '" class="btn btn-info btn-sm mb-1 '. ( !file_exists(storage_path('app/public/building-survey-kml/' . $model->kml)) ? ' anchor-disabled' : '' ) . '"  ><i class="fas fa-check"></i></a> ';
                 }
                if (Auth::user()->can('Preview Building Survey')) {
                    $content .= '<a title="' . e(__("Preview Building Location")) . '" data-toggle="modal" data-target="#kml-previewer" data-id="'.$model->kml.'" class="btn btn-info btn-sm mb-1" ><i class="fas fa-eye"></i></a> ';
                }
                if (Auth::user()->can('Download Building Survey')) {
                    $content .= '<a title="' . e(__("Download Building KML File")) . '" href="' . action("BuildingInfo\BuildingSurveyController@download", [$model->kml]) . '" class="btn btn-info btn-sm mb-1"  download><i class="fas fa-download"></i></a> ';
                }
                if (Auth::user()->can('Delete Building Survey')) {
                    $content .= '<a title="' . e(__("Delete")) . '" class="delete btn btn-danger btn-sm mb-1" ><i class="fas fa-trash"></i></a> ';
                }

                $content .= \Form::close();
                return $content;
            })
            ->make(true);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $buildingSurvey = BuildingSurvey::where('id', $id)->where('is_enabled', true)->first();

        if ($buildingSurvey) {
            if ($buildingSurvey->kml) {
                if(file_exists(storage_path('app/public/building-survey-kml/' . $buildingSurvey->kml)))
                {
                    File::delete(storage_path('app/public/building-survey-kml/' . $buildingSurvey->kml));
                }
            }
            $buildingSurvey->delete();
            return redirect('building-info/building-surveys')->with('success',__('Building Survey deleted successfully.'));
        } else {
            return redirect('building-info/building-surveys')->with('error',__('Failed to delete Building Survey'));
        }
    }

    public function download($filename)
    {
        ob_end_clean();
        $filepath = storage_path('app/public/building-survey-kml/' . $filename);
        //echo $filepath;die;
        if (File::exists($filepath)) {
            return response()->file($filepath);
        } else {
            abort(404);
        }
    }

    public function approve($id){
        $page_title = __("Approve Building Structure");

        $structure_type = StructureType::orderBy('type', 'asc')->pluck('type', 'id')->all();
        $water_source = WaterSource::orderBy('source', 'asc')->pluck('source', 'id')->all();
        // Capitalize the first letter of each word in the arrays
        $structure_type = array_map('ucwords', $structure_type);
        $water_source = moveOthersToEnd(array_map('ucwords', $water_source));
        $toiletConnection = SanitationSystem::whereNotIn('id', [9, 10,12])->pluck('sanitation_system', 'id')->all();
        $defecationPlace = SanitationSystem::whereIn('id', [9, 10,12])->pluck('sanitation_system', 'id')->all();
        $containment_type = ContainmentType::pluck('type','id')->all();
        $buildingBin = Building::distinct('bin')->pluck('bin', 'bin')->whereNull('building_associated_to')->whereNull('deleted_at');

        $bin = BuildContain::distinct('bin')->pluck('bin', 'bin')->whereNull('deleted_at');
        $ward = Ward::orderBy('ward')->pluck('ward', 'ward');

        $road_code = Roadline::get(['code', 'name'])->mapWithKeys(function ($item) {
            return [$item->code => ($item->name ? $item->code . ' - ' . $item->name : $item->code)];
        })->toArray();

        $sewer_code = SewerLine::pluck('code', 'code')->whereNull('deleted_at')->all();
        $containment_id = Containment::distinct('id')->pluck('id', 'id')->whereNull('deleted_at');

        $ctpt = Ctpt::where('status', true)
            ->where('type', 'Community Toilet')
            ->get(['id', 'name'])
            ->mapWithKeys(function ($item) {
                return [$item->id => ($item->name ? $item->id . ' - ' . $item->name : $item->id)];
            })
            ->toArray();


        $capitalizedctpt = array_map(function ($value) {
            return ucwords($value);
        }, $ctpt);
        $containment = [];
        // passing footprint that was previously collected through mobile application
        $buildingSurvey = BuildingSurvey::find($id);;

        $drain_code =  Drain::pluck('code', 'code')->all();


        $licNames = Lic::orderBy('community_name')->pluck('community_name', 'id')->whereNull('deleted_at');
        $models = UseCategory::select('id', 'name', 'functional_use_id')->orderBy('name')->get();
        $functional_use = FunctionalUse::orderBy('name')->pluck('name', 'id')->all();
        $use_category_id = [];
        foreach ($models as $model) {
            $use_category_id[$model->functional_use_id][$model->id] = $model->name;
        }

        $usecatgsJson = json_encode($use_category_id);
        $waterSupply = WaterSupplys::pluck('code', 'code');
        return view('building-info.buildings.create', compact(
            'page_title',
            'buildingBin',
            'bin',
            'containment_id',
            'water_source',
            'structure_type',
            'functional_use',
            'usecatgsJson',
            'containment',
            'road_code',
            'ward',
            'buildingSurvey',
            'sewer_code',
            'ctpt',
            'drain_code',
            'use_category_id',
            'licNames',
            'capitalizedctpt',
            'toiletConnection',
            'defecationPlace',
            'containment_type',
            'waterSupply'
        ));
    }





}
