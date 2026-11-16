<?php

namespace App\Http\Controllers;
use App\Models\BuildingInfo\Building;
use App\Services\BuildingInfo\BuildingStructureService;
use App\Services\Api\MobileBuildingPresenter;
use App\Http\Requests\BuildingInfo\BuildingRequest;
use Illuminate\Http\Request;

class BuildingSearchController extends Controller
{
    protected BuildingStructureService $buildingStructureService;
    private MobileBuildingPresenter $mobileBuildingPresenter;
    private $points;

    public function __construct(
        BuildingStructureService $buildingStructureService,
        MobileBuildingPresenter $mobileBuildingPresenter
    ) {
        $this->middleware('auth');
        $this->middleware('permission:List Building Structures', ['only' => ['index']]);
        $this->middleware('permission:View Building Structure', ['only' => ['show']]);
        $this->middleware('permission:Add Building Structure', ['only' => ['create', 'store']]);
        $this->middleware('permission:Edit Building Structure', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete Building Structure', ['only' => ['destroy']]);
        $this->middleware('permission:Export Building Structure to Excel', ['only' => ['export']]);
        /**
         * creating a service class instance
         */
        $this->buildingStructureService = $buildingStructureService;
        $this->mobileBuildingPresenter = $mobileBuildingPresenter;

    }
    ///api to search building , bin , road by id and also get similar id//
    public function getBuildingBin($bin){
        $similarBuildings = Building::whereRaw("bin ILIKE '".$bin."%'")->take(10)->get();
        return response()->json([
            'status' => 200,
            'code' => 'Fetched',
            'message' => __('User Records'),
            'data' => $this->presentBuildings($similarBuildings),
            'error' => null
        ]);
    }


    public function getBuildingRoadcode($roadcode){
        $similarBuildings = Building::whereRaw("road_code ILIKE '".$roadcode."%'")->take(10)->get();
        return response()->json([
            'status' => 200,
            'code' => 'Fetched',
            'message' => __('User Records'),
            'data' => $this->presentBuildings($similarBuildings),
            'error' => null
        ]);
    }

    public function getBuildingHouseNumber($housenumber){
        $similarBuildings = Building::whereRaw("house_number ILIKE '".$housenumber."%'")->take(10)->get();
        return response()->json([
            'status' => 200,
            'code' => 'Fetched',
            'message' => __('User Records'),
            'data' => $this->presentBuildings($similarBuildings),
            'error' => null
        ]);
    }


    public function getSewerCode($sewercode){
        $similarBuildings = Building::whereRaw("sewer_code ILIKE '".$sewercode."%'")->take(10)->get();
        return response()->json([
            'status' => 200,
            'code' => 'Fetched',
            'message' => __('User Records'),
            'data' => $this->presentBuildings($similarBuildings),
            'error' => null
        ]);
    }

    public function getBinOfPreconnectedBuilding($Pcbin){
        $similarBuildings = Building::whereRaw("house_number ILIKE '".$Pcbin."%'")->take(10)->get();
        return response()->json([
            'status' => 200,
            'code' => 'Fetched',
            'message' => __('User Records'),
            'data' => $this->presentBuildings($similarBuildings),
            'error' => null
        ]);
    }

    public function getSanitationSystem($Sanitation){
        $similarBuildings = Building::whereRaw("sanitation_system ILIKE '".$Sanitation."%'")->take(10)->get();
        return response()->json([
            'status' => 200,
            'code' => 'Fetched',
            'message' => __('User Records'),
            'data' => $this->presentBuildings($similarBuildings),
            'error' => null
        ]);
    }


    public function buildingStore(BuildingRequest $request){
        return($this->buildingStructureService->storeBuildingData($request));
    }

    private function presentBuildings($buildings)
    {
        return $buildings
            ->map(fn (Building $building) =>
                $this->mobileBuildingPresenter->present($building)
            )
            ->values();
    }



}
