<?php
// Last Modified Date: 19-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Http\Controllers\Fsm;
use App\Http\Requests\Fsm\CtptRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Fsm\Ctpt;
use DB;
use App\Models\LayerInfo\Ward;
use App\Models\BuildingInfo\Building;
use DataTables;
use App\Services\Fsm\CtptServiceClass;
use App\Models\BuildingInfo\BuildContain;
use App\Enums\CtptStatus;
use App\Enums\CtptStatusOperational;
use App\Models\Fsm\BuildToilet;
use App\Models\Fsm\CtptUsers;
use App\Helpers\KeywordMatcher;



class CtptController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected CtptServiceClass $ctptServiceClass;

    public function __construct(CtptServiceClass $ctptServiceClass)
    {
        $this->middleware('auth');
        $this->middleware('permission:List PT/CT Toilets', ['only' => ['index']]);
        $this->middleware('permission:View PT/CT Toilet', ['only' => ['show']]);
        $this->middleware('permission:Add PT/CT Toilet', ['only' => ['create', 'store']]);
        $this->middleware('permission:Edit PT/CT Toilet', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete PT/CT Toilet', ['only' => ['destroy']]);
        $this->middleware('permission:View PT/CT Toilet History', ['only' => ['history']]);
        $this->middleware('permission:Export PT/CT Toilets', ['only' => ['export']]);
         /**
         * creating a service class instance
         */
        $this->ctptServiceClass = $ctptServiceClass;
    }
    /**
    * Display a listing of the public/community toilets.
    *
    * @return \Illuminate\View\View
    */
    public function index()
    {
       $page_title = __('Public / Community Toilets');
       $ward = Ward::orderBy('ward','asc')->pluck('ward','ward')->all();
       $status = CtptStatus::asSelectArray();
       $operational = CtptStatusOperational::asSelectArray();
       $bin = Ctpt::orderBy('bin')->pluck('bin','bin')->all();
  

       return view("fsm.ct-pt.index", compact('page_title','ward','status','operational','bin'));
    }

    /**
    * Get data related to public/community toilets.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function getData(Request $request)
    {
        return ($this->ctptServiceClass->fetchData($request));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $page_title = __("Add Public / Community Toilets");
        $bin = $this->buildingOptions(old('bin'));
        $ward = Ward::orderBy('ward')->pluck('ward','ward');
        $status = CtptStatus::asSelectArray();
        $operational = CtptStatusOperational::toEnumArray();
        $building_info = null;
        return view('fsm.ct-pt.create', compact('page_title', 'ward', 'bin','status','operational','building_info'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CtptRequest $request)
    {
       return $this->ctptServiceClass->storeCtptData($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $ctpt = ctpt::find($id);
        if ($ctpt) {
            $page_title = __("Public / Community Toilets Details");
            $enumValue = $ctpt->status; 
            $typeValue = CtptStatusOperational::getDescription($enumValue);
            $address = Ctpt::select('building_info.buildings.house_number AS house_address')
            ->join('building_info.buildings', 'building_info.buildings.bin', '=', 'toilets.bin')
            ->where('toilets.bin', $ctpt->bin)
            ->first();  
            switch ($typeValue) {
                case 'Not operational':
                     $operational ='Not Operational';
                     break;
                case 'Operational':
                     $operational = 'Operational';
                     break;
                default:
                $operational = '';
                break;
            }
            $indicative_sign = ($ctpt->indicative_sign === true) ? 'yes' : (($ctpt->indicative_sign === false) ? 'no' : null);
            $fee_collected = ($ctpt->fee_collected === true) ? 'yes' : (($ctpt->fee_collected === false) ? 'no' : null);
            $male_or_female_facility = ($ctpt->male_or_female_facility === true) ? 'yes' : (($ctpt->male_or_female_facility === false) ? 'no' : null);
            $handicap_facility =  ($ctpt->handicap_facility === true) ? 'yes' : (($ctpt->handicap_facility === false) ? 'no' : null);
            $children_facility =  ($ctpt->children_facility === true) ? 'yes' : (($ctpt->children_facility === false) ? 'no' : null);
            $separate_facility_with_universal_design =  ($ctpt->separate_facility_with_universal_design === true) ? 'yes' : (($ctpt->separate_facility_with_universal_design === false) ? 'no' : null);
            $sanitary_supplies_disposal_facility = ($ctpt->sanitary_supplies_disposal_facility === true) ? 'yes' : (($ctpt->sanitary_supplies_disposal_facility === false) ? 'no' : null);
            
            // displaying buildings information
            $building = Building::find($ctpt->bin);
            $sewer_code = $building->sewer_code ?? "No Sewer Code";
            $drain_code = $building->drain_code ?? "No Drain Code";
            $containment_ids = implode(',',$building->containments()->get()->pluck('id')->toArray()) ?? "No Containment Connected";  
            $containment_infos = [];
            foreach($building->containments()->get() as $containment)
            {
                array_push($containment_infos, $containment->containmentType->type . " (" . $containment->id .") <br>");
            }
            $containments = $containment_infos ? implode('',$containment_infos) : "No Containment Connected<br>"; 
            $building_data = ["Building Toilet Connection:" . $building->SanitationSystem->sanitation_system . "<br> Containment Info:<br>".  $containments . "Drain Code: " . $drain_code ."<br>Sewer Code:" . $sewer_code,
            $building->SanitationSystem->sanitation_system];
            
            return view('fsm.ct-pt.show', compact('page_title', 'ctpt','indicative_sign', 'address','fee_collected','sanitary_supplies_disposal_facility',
        'male_or_female_facility', 'handicap_facility', 'children_facility', 'operational', 'separate_facility_with_universal_design','building_data'));
        } else {
            abort(404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
       $ctpt = Ctpt::find($id);
        if ($ctpt) {
            $page_title = __("Edit Public / Community Toilets");
            $ward = Ward::orderBy('ward')->pluck('ward','ward');
            $bin = $this->buildingOptions(old('bin', $ctpt->bin));
            $status = CtptStatus::asSelectArray();
            $operational = CtptStatusOperational::toEnumArray();
            return view('fsm.ct-pt.edit', compact('page_title','ctpt','ward', 'bin','status','operational'));
        } else {
            abort(404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CtptRequest $request, $id)
    {
        return $this->ctptServiceClass->updateCtptData($request, $id);
    }

    private function buildingOptions($selectedBin): array
    {
        if (!is_string($selectedBin) || $selectedBin === '') {
            return [];
        }

        return Building::query()->setEagerLoads([])
            ->where('bin', $selectedBin)
            ->pluck('house_number', 'bin')
            ->map(function ($houseNumber, $bin) {
                return $houseNumber ?: $bin;
            })->all();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ctpt = Ctpt::find($id);
        if ($ctpt) {
            if(KeywordMatcher::matchKeywords($ctpt->type, ["public"]) != false )
            {
                $pt_log_status = CtptUsers::where('toilet_id',$ctpt->id)->count();
                if($pt_log_status != 0 )
                {
                    return redirect('fsm/ctpt')->with('error', __('Cannot delete Public Toilet that has associated PT Users Log.'));
                }
            }
            elseif(KeywordMatcher::matchKeywords($ctpt->type, ["community"]) != false )
            {
                $ct_connected_building = BuildToilet::where('toilet_id', $id)->whereNULL('deleted_at')->count();
                if($ct_connected_building != 0 )
                {
                    return redirect('fsm/ctpt')->with('error', __('Cannot delete Community Toilet that has associated Building Information.'));
                }  
            }  
            $ctpt->delete();
            return redirect('fsm/ctpt')->with('success', __('Public / Community Toilets Deleted successfully.'));
        } else {
            return redirect('fsm/ctpt')->with('error', __('Failed to delete Public / Community Toilets.'));
        }
    }

    public function history($id)
    {
        $ctpt = Ctpt::find($id);
        if ($ctpt) {
            $page_title = __("Public / Community Toilets History");
            return view('fsm.ct-pt.history', compact('page_title', 'ctpt'));
        } else {
            abort(404);
        }
    }

    /**
    * Export data related to public/community toilets.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function export(Request $request)
    {

        $data = $request->all();

        return $this->ctptServiceClass->exportData($data);
    }
    /**
    * List buildings connected to a specific public/community toilet.
    *
    * @param  int  $id
    * @return \Illuminate\View\View
    */
    public function listBuildings($id)
    {
        $toilet = Ctpt::find($id);
        if ($toilet) {
            $page_title = __("Building Connected to Toilet:")  . $toilet->id;
            $buildings = $toilet->buildings;
            return view('fsm.ct-pt.listBuilding', compact('page_title', 'toilet', 'buildings'));
        } else {
            abort(404);
        }
    }
    /**
    * Show the form for adding buildings to a specific public/community toilet.
    *
    * @param  int  $id
    * @return \Illuminate\View\View
    */
    public function addBuildings($id)
    {
        $toilet = Ctpt::find($id);
        $page_title = __("Add Buildings to Toilet") . ": " . $toilet->id;
        return view('fsm.ct-pt.addBuildings', compact('page_title', 'toilet'));
    }
     /**
     * Get the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllBuildingData(Request $request)
    {
        $allBuildingData = Building::select('*');

        return Datatables::of($allBuildingData)
            ->filter(function ($query) use ($request) {
                if ($request->bin) {
                    $query->where('bin', $request->bin);
                }
                if ($request->holding_num) {
                    $query->where('taxcd', $request->holding_num);
                }
            })
            ->make(true);
    }

    public function saveBuildings(Request $request, $id)
    {
        $toilet = Ctpt::find($id);

        if ($toilet) {
            $this->validate($request, [
                'bin' => 'required',
            ]);

            $toilet->save();
            $toilet->buildings()->syncWithoutDetaching($request->bin);
            $buildings = $toilet->buildings()->orderBy('bin')->get();
            if(count($buildings) == 1)
            {
            $toilet->buildings()->syncWithoutDetaching([
                $buildings[0]->bin => ['main_building' => '1'],
            ]);
            }
            return redirect('fsm.ct-pt/' . $id . '/buildings')->with('success', __('Buildings added to this toilet.'));
        } else {
            return redirect('fsm.ct-pt/' . $id . '/buildings')->with('error', __('Failed to add buildings.'));
        }
    }

    public function deleteBuilding($id, $buildingId)
    {
        $toilet = Ctpt::find($id);

        if ($toilet) {
            $toilet->buildings()->detach($buildingId);
            $buildings = $toilet->buildings()->orderBy('bin')->get();
            if(count($buildings) == 1)
            {
            $toilet->buildings()->syncWithoutDetaching([
                $buildings[0]->bin => ['main_building' => '1'],
            ]);
            }
            return redirect('fsm.ct-pt/' . $id . '/buildings')->with('success', __('Building deleted successfully.'));
        } else {
            return redirect('fsm.ct-pt/' . $id . '/buildings')->with('error', __('Failed to delete building.'));
        }
    }
    /**
    * Get house numbers related to public/community toilets.
    *
    * @return \Illuminate\Http\JsonResponse
    */
    public function getHouseNumbers(){

        return($this->ctptServiceClass->fetchHouseNumber());

    }

}
