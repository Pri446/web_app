<?php

namespace App\Http\Controllers\UtilityInfo;

use Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UtilityInfo\Roadline;
use App\Models\BuildingInfo\Building;
use App\Http\Requests\UtilityInfo\RoadLineRequest;
use App\Services\UtilityInfo\RoadlineService;
use DB;

class RoadlineController extends Controller
{
    protected RoadlineService $roadlineService;
    public function __construct(RoadlineService $roadlineService)
    {
        $this->middleware('auth');
        $this->middleware('permission:List Roadlines', ['only' => ['index']]);
        $this->middleware('permission:View Roadline', ['only' => ['show']]);
        $this->middleware('permission:Add Roadline', ['only' => ['create', 'store']]);
        $this->middleware('permission:Edit Roadline', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete Roadline', ['only' => ['destroy']]);
        $this->middleware('permission:Import Roadlines to Shape', ['only' => ['importShp', 'importShpStore']]);
        $this->middleware('permission:Export Roadlines to CSV', ['only' => ['export']]);
        $this->roadlineService = $roadlineService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = __('Road Network');
        return view('utility-info/road-lines.index', compact('page_title'));
    }

    public function getData(Request $request)
    {
        $data = $request->all();
        return $this->roadlineService->getAllData($data);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $page_title = __("Create Road");
        return view('roadlines.create', compact('page_title'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RoadLineRequest $request)
    {
        $data = $request->all();
        $this->roadlineService->storeOrUpdate($id = null,$data);
        return redirect('utilityinfo/roadlines')->with('success',__('Road created successfully.'));
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $roadline = Roadline::find($id);

        if ($roadline) {
            // Format the carrying_width attribute to display only two decimal places
            $roadline->carrying_width = number_format($roadline->carrying_width, 2);
            $roadline->right_of_way = number_format($roadline->right_of_way, 2);
            // Format the length attribute to display only two decimal places
            $roadline->length = number_format($roadline->length, 2);

            $page_title = __("Road Network Details");
            return view('utility-info/road-lines.show', compact('page_title', 'roadline'));
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
        $roadline = Roadline::find($id);
        $roadHierarchy = Roadline::where('hierarchy','!=',null)->groupBy('hierarchy')->pluck('hierarchy','hierarchy');
        $roadSurfaceTypes = Roadline::where('surface_type','!=',null)->groupBy('surface_type')->pluck('surface_type','surface_type');

        if ($roadline) {
            // Format the carrying_width attribute to display only two decimal places
            $roadline->carrying_width = number_format($roadline->carrying_width, 2);
            $roadline->right_of_way = number_format($roadline->right_of_way, 2);
            // Format the length attribute to display only two decimal places
            $roadline->length = number_format($roadline->length, 2);

            $page_title = __("Edit Road Network");
            return view('utility-info/road-lines.edit', compact('page_title', 'roadline','roadHierarchy','roadSurfaceTypes'));
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
    public function update(RoadLineRequest $request, $id)
    {
        $roadline = Roadline::find($id);
        if ($roadline) {
            $data = $request->all();
            $this->roadlineService->storeOrUpdate($roadline->code,$data);
            return redirect('utilityinfo/roadlines')->with('success',__('Road Network updated successfully.'));

        } else {
            return redirect('utilityinfo/roadlines')->with('error',__('Failed to update road'));
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
        $roadline = Roadline::find($id);
        if ($roadline) {
            if ($roadline->buildings()->exists()) {
                return redirect('utilityinfo/roadlines')->with('error',__('Cannot delete Road that is associated with Building Information.'));
            } 
            if($roadline->sewers()->exists()) {
                return redirect('utilityinfo/roadlines')->with('error',__('Cannot delete Road that is associated with Sewer Information.'));
            } 
            if($roadline->drains()->exists()) {
                return redirect('utilityinfo/roadlines')->with('error',__('Cannot delete Road that is associated with Drain Information.'));
            } 
            if($roadline->water_supply()->exists()) {
                return redirect('utilityinfo/roadlines')->with('error',__('Cannot delete Road that is associated with Water Supply Network Information.'));
            } 
            $roadline->delete();
            return redirect('utilityinfo/roadlines')->with('success',__('Road deleted successfully.'));
        } else {
            return redirect('utilityinfo/roadlines')->with('error',__('Failed to delete road.'));
        }
    }

    /**
     * Display history of the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function history($id)
    {
        $roadline = Roadline::find($id);
        if ($roadline) {
            $page_title = __("Road Network History");
            return view('utility-info/road-lines.history', compact('page_title', 'roadline'));
        } else {
            abort(404);
        }
    }

    /**
     * Export a listing of the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $data = $request->all();
        return $this->roadlineService->download($data);

    }

    public function getRoadNames(){

        // Start with an Eloquent query instead of rebuilding one from an
        // already-loaded collection. Road codes are strings (for example,
        // R000003); Collection::toQuery() previously cast them to integer
        // model keys and produced an invalid PostgreSQL `code IN (0, ...)`.
        $query = Roadline::query();
        if (request()->search){
            $query->where('name', 'ilike', '%'.request()->search.'%')
            ->orWhere('code','ilike','%'.request()->search.'%');
        }
        if (request()->bin){
            $building = Building::where('bin',request()->bin)->first();
            if ($building && $building->road_code) {
                $query->where('code', '=', $building->road_code);
            }
        }
        if (request()->ward){
            $building = Building::where('ward',request()->ward)->first();
            if ($building && $building->road_code) {
                $query->where('code', '=', $building->road_code);
            }
        }
        $total = $query->count();


        $limit = 10;
        if (request()->page) {
            $page  = request()->page;
        }
        else{
            $page=1;
        };
        $start_from = ($page-1) * $limit;

        $total_pages = ceil($total / $limit);
        if($page < $total_pages){
            $more = true;
        }
        else
        {
            $more = false;
        }
        $roads = $query->offset($start_from)
            ->limit($limit)
            ->get();
        $json = [];
        foreach($roads as $road)
        {
            $json[] = ['id'=>$road['code'], 'text'=>$road['name']?? $road['code']];
        }

        return response()->json(['results' =>$json, 'pagination' => ['more' => $more] ]);
    }
    /**
     * This function updates the geom of the road. Currently used in Add Road Tool in the Maps.
     *
     * @param string  $request We get all road information and geom from this request
     */

     public function updateRoadGeom(Request $request)
     {
         $roadcd = $request->roadcd ? $request->roadcd : null;
     
         if ($roadcd) {
             $roadline = Roadline::find($roadcd);
         } else {
             return response()->json([
                 'success' => false,
                 'data' => [],
                 'error' => "Couldn't find the required road!",
             ]);
         }
     
         // Update the geometry field
         $roadline->geom = DB::raw("ST_SetSRID(ST_GeomFromText('". $request->geom ."'), 4326)"); // Ensure the SRID is set properly
         $roadline->length = $request->length;
         $roadline->save();
         
         return response()->json([
             'success' => true,
             'data' => [],
             'error' => "{{__('Updated the road geometry successfully!')}}",
         ]);
     }

     public function getGeometry($code)
     {
         $geometry = DB::table('utility_info.roads')
         ->where('code', $code)
         ->value(DB::raw('ST_AsText(geom) as geometry'));
     
         return response()->json(['geometry' => $geometry]);
     
     }

}
