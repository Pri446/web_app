<?php
//Last Modified Date: 19-04-2024
//Developed By: Innovative Solution Pvt. Ltd. (ISPL)  (© ISPL, 2024)

namespace App\Http\Controllers\UtilityInfo;

use App\Http\Controllers\Controller;
use App\Models\UtilityInfo\Drain;
use App\Models\UtilityInfo\Roadline;
use App\Models\UtilityInfo\SewerLine;
use App\Models\UtilityInfo\WaterSupplys;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\UtilityInfo\UtilityDashboardService;
use Illuminate\Support\Facades\Log;

class UtilityDashboardController extends Controller
{
  protected UtilityDashboardService $utilitydashboardService;
  public function __construct(UtilityDashboardService $utilitydashboardService)
  {
    $this->middleware('auth');
    $this->utilitydashboardService = $utilitydashboardService;
  }
  public function index()
  {
    return view('dashboard.utilityDashboardShell', [
      'page_title' => __('Utility Dashboard'),
    ]);
  }

  public function content(Request $request): JsonResponse
  {
    try {
      $html = view('dashboard.utilityDashboard', $this->buildDashboardData())->render();

      return response()->json([
        'status' => 'ok',
        'html' => $html,
      ])->header('Cache-Control', 'private, no-store');
    } catch (\Throwable $exception) {
      Log::error('Utility Dashboard content failed to load.', [
        'user_id' => $request->user()->id,
        'exception' => $exception,
      ]);

      return response()->json([
        'status' => 'error',
        'message' => __('The dashboard could not be loaded.'),
      ], 500)->header('Cache-Control', 'private, no-store');
    }
  }

  private function buildDashboardData(): array
  {
    $page_title = __('Utility Dashboard');

    /**
     ** For countBoxes 
     */
    $sumRoads = Roadline::sum('length');
    /** 
     ** Road Surface Type 
     */
    $sumSurfaceType = Roadline::where('surface_type', 'Metalled')->sum('length');
    $sumSurfaceType1 = Roadline::where('surface_type', 'Earthen')->sum('length');
    $sumSurfaceType2 = Roadline::where('surface_type', 'Brick Paved')->sum('length');
    $sumSurfaceType3 = Roadline::where('surface_type', 'Gravelled')->sum('length');
    /** 
     ** Road Hierarchy
     */
    $sumHierarchy = Roadline::where('hierarchy', 'Other Road')->sum('length');
    $sumHierarchy1 = Roadline::where('hierarchy', 'Strategic Urban Road')->sum('length');
    $sumHierarchy2 = Roadline::where('hierarchy', 'Feeder Road')->sum('length');
    /** 
     ** Road Carrying Width
     */
    $sumWidth = Roadline::where('carrying_width', '<', '3')->sum('length');
    $sumWidth1 = Roadline::where('carrying_width', [3, 5])->sum('length');
    $sumWidth2 = Roadline::where('carrying_width', [5, 8])->sum('length');
    $sumWidth3 = Roadline::where('carrying_width', '>', '8')->sum('length');
    $sumWidth4 = Roadline::where('carrying_width', null)->sum('length');
    /** 
     ** Calculate the total length of sewers (Displayed as "Total length (m) of sewers" in UI)
     */
    $sumSewers = SewerLine::sum('length');
    /** 
     ** Sewer Diameter
     */
    $sumSewerWidth = SewerLine::where('diameter', '<', 160)->sum('length');
    $sumSewerWidth1 = SewerLine::whereBetween('diameter', [160, 300])->sum('length');
    $sumSewerWidth2 = SewerLine::whereNull('diameter')->sum('length');
    $sumSewerWidth3 = SewerLine::where('diameter', '>', 300)->sum('length');
    
    /** 
     ** Calculate the total length of drains (Displayed as "Total length (m) of drains" in UI)
     */
    $sumDrains = Drain::sum('length');
    $sumDrainsWidth = Drain::where('size', '<', 160)->sum('length');
    $sumDrainsWidth1 = Drain::whereBetween('size', [160, 300])->sum('length');
    $sumDrainsWidth2 = Drain::whereNull('size')->sum('length');
    $sumDrainsWidth3 = Drain::where('size', '>', 300)->sum('length');

    /**
     ** For drain Cover Type
     */
    $sumDrainsCoverType = Drain::where('cover_type', 'Open')->sum('length');
    $sumDrainsCoverType1 = Drain::where('cover_type', 'Closed')->sum('length');
    /**
     ** For drain Surface Type
     */
    $sumDrainsSurfaceType = Drain::where('surface_type', 'Lined')->sum('length');
    $sumDrainsSurfaceType1 = Drain::where('surface_type', 'Unlined')->sum('length');

    /** 
     ** Calculate the total length of water supply (Displayed as "Total length (m) of water supply" in UI)
     */
    $sumWatersupply = WaterSupplys::sum('length');
    /** 
     ** Calculate the diameter of water supply (Displayed as "Water Supply Length by Diameter (mm)" in UI)
     */
    $sumWaterSupplyWidth = WaterSupplys::where('diameter', '<', 160)->sum('length');
    $sumWaterSupplyWidth1 = WaterSupplys::whereBetween('diameter', [160, 300])->sum('length');
    $sumWaterSupplyWidth2 = WaterSupplys::whereNull('diameter')->sum('length');
    $sumWaterSupplyWidth3 = WaterSupplys::where('diameter', '>', 300)->sum('length');


    $roadsSurfaceTypePerWardChart = $this->utilitydashboardService->getRoadsSurfaceTypePerWardChart();
    $roadsHierarchyPerWardChart = $this->utilitydashboardService->getRoadsHierarchyPerWardChart();
    $drainsTypePerWardChart = $this->utilitydashboardService->getDrainsTypePerWardChart();
    $drainsSurfaceTypePerWardChart = $this->utilitydashboardService->getDrainsSurfaceTypePerWardChart();
    $sewerLengthPerWardChart = $this->utilitydashboardService->getSewerLengthPerWard();
    $roadLengthPerWardChart = $this->utilitydashboardService->getRoadLengthPerWardChart();
    $drainLengthPerWardChart = $this->utilitydashboardService->getDrainLengthPerWardChart();
    $sewerWidthPerWardChart = $this->utilitydashboardService->getSewerDiameterPerWardChart();
    $drainWidthPerWardChart =  $this->utilitydashboardService->getDrainDiameterPerWardChart();
    $roadWidthPerWardChart =  $this->utilitydashboardService->getRoadDiameterPerWardChart();
    $watersupplyLenghtPerWardChart = $this->utilitydashboardService->getWaterSupplyLengthPerWardChart();
    $watersupplyTypePerWardChart = $this->utilitydashboardService->getWaterSupplyDiameterPerWardChart();


    return compact(
      'page_title',
      'roadsSurfaceTypePerWardChart',
      'roadsHierarchyPerWardChart',
      'drainsTypePerWardChart',
      'sewerLengthPerWardChart',
      'roadLengthPerWardChart',
      'drainLengthPerWardChart',
      'sewerWidthPerWardChart',
      'drainWidthPerWardChart',
      'drainsSurfaceTypePerWardChart',
      'roadWidthPerWardChart',
      'watersupplyLenghtPerWardChart',
      'watersupplyTypePerWardChart',
      'sumRoads',
      'sumSewers',
      'sumDrains',
      'sumWatersupply',
      'sumSurfaceType',
      'sumSurfaceType1',
      'sumSurfaceType2',
      'sumSurfaceType3',
      'sumHierarchy',
      'sumHierarchy1',
      'sumHierarchy2',
      'sumWidth',
      'sumWidth1',
      'sumWidth2',
      'sumWidth3',
      'sumSewerWidth',
      'sumSewerWidth1',
      'sumSewerWidth2',
      'sumSewerWidth3',
      'sumWaterSupplyWidth',
      'sumWaterSupplyWidth1',
      'sumWaterSupplyWidth2',
      'sumWaterSupplyWidth3',
      'sumDrainsWidth3',
      'sumDrainsWidth2',
      'sumDrainsWidth1',
      'sumDrainsWidth',
      'sumDrainsCoverType',
      'sumDrainsCoverType1',
      'sumWidth4',
      'sumDrainsSurfaceType',
      'sumDrainsSurfaceType1'

    );
  }
}
