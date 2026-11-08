<?php

namespace App\Http\Controllers\Fsm;

use DB;
use Auth;
use DomXpath;

use DataTables;
use DOMDocument;
use Carbon\Carbon;
use Box\Spout\Common\Type;

use Illuminate\Http\Request;

use App\Models\Fsm\Containment;
use App\Models\UtilityInfo\Drain;
use Box\Spout\Writer\Style\Color;
use App\Models\Fsm\ContainmentType;
use Box\Spout\Writer\WriterFactory;
use App\Http\Controllers\Controller;
use App\Models\BuildingInfo\Building;
use App\Models\UtilityInfo\SewerLine;
use Venturecraft\Revisionable\Revision;
use App\Services\Fsm\ContainmentService;
use Box\Spout\Writer\Style\StyleBuilder;
use App\Models\BuildingInfo\BuildContain;
use App\Helpers\KeywordMatcher;

use App\Http\Requests\Fsm\ContainmentRequest;
use App\Models\BuildingInfo\SanitationSystemTechnology;
use App\Models\Fsm\Application;
use App\Services\BuildingInfo\BuildingStructureService;

class ContainmentController extends Controller
{
    protected BuildingStructureService $buildingStructureService;
    protected ContainmentService $containmentService;
    public function __construct(ContainmentService $containmentService, BuildingStructureService $buildingStructureService)
    {
        $this->middleware('auth');
        $this->middleware('permission:List Containments', ['only' => ['index']]);
        $this->middleware('permission:View Containment', ['only' => ['show']]);
        $this->middleware('permission:Add Containment', ['only' => ['create', 'store']]);
        $this->middleware('permission:Edit Containment', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete Containment', ['only' => ['destroy']]);
        $this->middleware('permission:Import Containment from Shape', ['only' => ['importShp', 'importShpStore']]);
        $this->middleware('permission:Export Containments to Excel', ['only' => ['export']]);
        $this->middleware('permission:List Containment Buildings', ['only' => ['listBuildings']]);
        $this->middleware('permission:Add Containment Building', ['only' => ['addBuildings', 'saveBuildings']]);
        $this->middleware('permission:Delete Containment Building', ['only' => ['deleteBuilding']]);
        $this->buildingStructureService = $buildingStructureService;
        $this->containmentService = $containmentService;
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = __("Containments");
        $location = Containment::distinct()->whereNotNull('location')->pluck('location', 'location')->except('Outside the property boundary')->all();
        $uniqueArray = [];

        $containmentLocations =  Containment::select('location')
        ->distinct()->whereNotnull('location')
        ->pluck('location');



        $containmentTypes = ContainmentType::distinct()->pluck('type', 'id')->all();



        return view("fsm.containments.index", compact('page_title', 'containmentLocations', 'containmentTypes'));
    }


    public function getData(Request $request)
    {
        return ($this->containmentService->fetchData($request));
    }

    // Data table for containments of particular building Only
    public function getContainment(Request $request)
    {
        return ($this->containmentService->fetchBuildingContainmentData($request));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createContainment(Request $request, $id)
    {
        $page_title = __("Add Containment");
        $building = Building::find($id);
        $containment_building = $building;


        $containment_type = ContainmentType::pluck('type', 'id');
        $sewer_code = SewerLine::pluck('code', 'code')->all();
        $drain_code = Drain::pluck('code', 'code')->all();
        return view('fsm.containments.create', compact('id', 'page_title', 'building', 'containment_building', 'containment_type', 'sewer_code', 'drain_code'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // stores containment data added from buildings ->edit page -> add new containment to building button
    public function storeContainment(ContainmentRequest $request, $id)
    {
        try {
            $request->bin = $id;
            // storing new containment
            $this->buildingStructureService->storeContainmentInfo($flag = 'containment', $type = 'createContainOnly', $request);
            // updating building fields
            $this->buildingStructureService->updateBuildingFromContainment($request);

            if ($request->filled('application_id')) {
                $containmentId = $request->containment_id;
                if ($containmentId) {
                    $applicationService = app(\App\Services\Fsm\ApplicationService::class);
                    $applicationService->resolveAnf((int) $request->application_id, $id, $containmentId);
                }
                DB::commit();
                return redirect()->route('emptying.create-id', $request->application_id)->with('success', __("Containment created and linked to application successfully"));
            }

            DB::commit();
            return redirect('building-info/buildings/'.$id.'/edit')->with('success', __("Containment created successfully"));
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', __("Containment could not be created") . $e);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $page_title = __("Containment Details");
        $containment = Containment::find($id);

        if ($containment->septic_criteria === true) {
            $septic_criteria = "Yes";
        } elseif($containment->septic_criteria === false) {
            $septic_criteria = "No";
        }else {
            $septic_criteria = null;
        }
        $building = null;
        if ($containment) {
            return view('fsm.containments.show', compact('page_title', 'containment', 'building', 'septic_criteria'));
        } else {
            return view('errors.404');
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
        $page_title = __("Edit Containment");
        $containment = Containment::find($id);

        $containment->pit_shape = $containment->tank_length ? "Rectangular" : "Cylindrical";
        $containment->pit_depth = $containment->depth;
        $containment_building = $containment->buildings->first();

        $containment_type = ContainmentType::pluck('type', 'id');
        $sewer_code = SewerLine::pluck('code', 'code')->all();
        $drain_code = Drain::pluck('code', 'code')->all();
        return view('fsm.containments.edit', compact('id', 'page_title', 'containment', 'containment_building', 'containment_type', 'sewer_code', 'drain_code'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ContainmentRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            // assigning containment id to request->$id
            $request->id = $id;
            // updating containment fields
            $this->buildingStructureService->storeContainmentInfo($flag = 'containment', $type = 'update', $request);
            // updating building fields
            $this->buildingStructureService->updateBuildingFromContainment($request);
            DB::commit();
            return redirect('fsm/containments')->with('success', __("Containment updated successfully"));
        } catch (Exception $e) {
            DB::rollback();
            return redirect('fsm/containments')->with('error', __("Containment could not be updated") . $e);
        }
    }

    public function history($id)
    {
        $containment = Containment::find($id);
        if ($containment) {
            $page_title = __("Containment History");
            return view('fsm.containments.history', compact('page_title', 'containment'));
        } else {
            abort(404);
        }
    }

    public function getContainmentID()
    {
        return ($this->containmentService->fetchContainmentID());
    }
    public function typeChangeHistory($id)
    {
        $containment = Containment::findOrFail($id);
        $revisions = Revision::all()
            ->where('revisionable_type', get_class($containment))
            ->where('revisionable_id', $id)
            ->groupBy(function ($item) {
                return $item->created_at->format("D M j Y");
            })
            ->sortByDesc('created_at')
            ->reverse();
        if ($containment) {
            $page_title = __("Containment Type Change History");
            return view('fsm.containments.type-change-history', compact('page_title', 'containment', 'revisions'));
        } else {
            abort(404);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try
        {
            DB::beginTransaction();
            $containment = Containment::find($id);
            if ($containment)
            {
                if ($containment->buildings()->exists())
                {
                    return redirect('fsm/containments')->with('error', __("Failed to delete Containment, it is Associated with Building Information"));
                }
                else
                {
                    $containment->delete();
                    DB::commit();
                    return redirect('fsm/containments')->with('success', __("Containment Deleted Successfully"));
                }
            }
        } catch (\Exception $e) {
                    DB::rollback();
                    return redirect('fsm/containments')->with('error', __("Failed to Delete Containment"));
                }
    }



    public function export(Request $request)
    {
        $data = $request->all();
        return ($this->containmentService->getExport($data));
    }



    public function exportBuildingContainment(Request $request)
    {
        $data = $request->all();
        return ($this->containmentService->getExportBuildingContainment($data));
    }
    public function listBuildings($id)
    {
        $containment = Containment::find($id);

        if ($containment) {
            $page_title = __("Building Connected to Containment") . ": " . $containment->id;
            $buildings = $containment->buildings;

            return view('fsm.containments.listBuilding', compact('page_title', 'containment', 'buildings'));
        } else {
            abort(404);
        }
    }


    public function containmentBySanitation($id)
    {
        $containment = Containment::find($id);
        $buildings = $containment->buildings;
        $sanitationSystemIds = $buildings->pluck('sanitation_system_id')->unique();
        $containmentTypes = ContainmentType::whereIn('sanitation_system_id', $sanitationSystemIds)->get();
        return response()->json($containmentTypes);
    }
    // function to delete / remove containment connection from building->edit page & containment->listbuildings page
    public function deleteBuilding($id, $bin)
    {
    try
    {
        DB::beginTransaction();
        $containment = Containment::find($id);
        $building = Building::find($bin);
        $containment_connection = 1;
        if ($containment)
        {
            //check if containment has application
            if($containment->applications()->exists())
            {
                // fetch and checking if the running application has emptying status true or false
                $emptyingStatusContain = $containment->applications()->latest()->value('emptying_status') ;
                // not allow deletion if containment has running application i.e. emptying status is false
                if($emptyingStatusContain != true )
                {
                    return redirect()->back()->with('error', __('Cannot delete Containment that has running Application (Emptying not completed).'));
                }
            }
            // removing the connection between the building and containment, if there are no running applications
            DB::table('building_info.build_contains')
            ->where('bin', $bin)
            ->where('containment_id', $id)
            ->update(array('deleted_at' => Carbon::now()));
            // checking drain and sewer code status and removing them from building table if building does not have any remaining sewer or drain connections
            $status_drain = false;
            $status_sewer = false;
            if($building->containments()->exists())
            {
                // looping through each containments, checking and setting flag if found
                foreach ($building->containments as $contain) {
                    // setting status true if there is any sewer drain connection
                    if (KeywordMatcher::matchKeywords($contain->containmentType->type, ["drain"])) {
                        $status_drain = true;
                    }
                    if (KeywordMatcher::matchKeywords($contain->containmentType->type, ["sewer"])) {
                        $status_sewer = true;
                    }
                }
            }
            // nullify drain and sewer code only if there is no containment with sewer/drain connection
            if ($status_drain == false) {
                $building->drain_code = null;
            }
            if ($status_sewer == false) {
                $building->sewer_code = null;
            }
            // saving updated sewer and drain code
            $building->save();
            // count no. of connections remaining for same containment, excluding the BIN we just removed
            $containment_connection = count(BuildContain::where('containment_id', $id)->where('bin','!=',$bin)->whereNull('deleted_at')->get());
            // if connection is zero, deleting containment itself
            if($containment_connection == 0)
            {
                $containment->delete();
            }

        }
        DB::commit();
        // success message if containment also deleted
        if ($containment_connection == 0)
        {
            // controlling redirect according to URL
            if(KeywordMatcher::matchKeywords(url()->previous(),["containment"]))
            {
                return redirect("fsm/containments")->with('success', __('Containment Connection and Containment Deleted Successfully.'));
            }
            else
            {
                return redirect()->back()->with('success', __('Containment Connection and Containment Deleted Successfully.'));
            }
        }
        if(KeywordMatcher::matchKeywords(url()->previous(),["containment"]))
        {
            return redirect("fsm/containments")->with('success', __('Containment Connection Deleted Successfully.'));
        }
        else
        {
            return redirect()->back()->with('success', __('Containment Connection Deleted Successfully.'));
        }
    }  catch (Exception $e) {
        DB::rollback();
        if(KeywordMatcher::matchKeywords(url()->previous(),["containment"]))
        {
            return redirect("fsm/containments")->with('error', __('Failed to Delete Containment'));
        }
        else
        {
            return redirect()->back()->with('error', __('Failed to Delete Containment'));
        }
    }
    }
}
