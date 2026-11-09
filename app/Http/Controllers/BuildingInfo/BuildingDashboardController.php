<?php

namespace App\Http\Controllers\BuildingInfo;

use App\Http\Controllers\Controller;
use App\Models\BuildingInfo\Building;
use App\Models\BuildingInfo\FunctionalUse;
use App\Services\BuildingInfo\BuildingDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BuildingDashboardController extends Controller
{
    protected BuildingDashboardService $buildingDashboardService;

    public function __construct(BuildingDashboardService $buildingDashboardService)
    {
        $this->middleware('auth');
        $this->buildingDashboardService = $buildingDashboardService;
    }

    public function index()
    {
        return view('dashboard.buildingDashboardShell', [
            'page_title' => __('Building Dashboard'),
        ]);
    }

    public function content(Request $request): JsonResponse
    {
        try {
            $html = view('dashboard.buildingDashboard', $this->buildDashboardData())->render();

            return response()->json([
                'status' => 'ok',
                'html' => $html,
            ])->header('Cache-Control', 'private, no-store');
        } catch (\Throwable $exception) {
            Log::error('Building Dashboard content failed to load.', [
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
        $user = auth()->user();
        $data = [
            'page_title' => __('Building Dashboard'),
        ];

        if ($user->can('Building CountBox')) {
            $buildingCount = Building::whereNull('deleted_at')->count();
            $commercialBuildCount = $this->buildingDashboardService->countBuildingsByUseExact('Commercial');
            $residentialBuildingCount = $this->buildingDashboardService->countBuildingsByUseExact('Residential');
            $mixedBuildCount = $this->buildingDashboardService->countBuildingsByUseExact('Mixed (Residential, Commercial, Office uses)');
            $industrialBuildingCount = $this->buildingDashboardService->countBuildingsByUseExact('Industrial');
            $educationBuildingCount = $this->buildingDashboardService->countBuildingsByUseExact('Educational');
            $institutionBuildingCount = $this->buildingDashboardService->countBuildingsByUse('Institution');
            $institutionNames = FunctionalUse::where('name', 'like', '%Institution%')
                ->pluck('name')
                ->implode('<br>');

            $data += compact(
                'buildingCount',
                'commercialBuildCount',
                'residentialBuildingCount',
                'mixedBuildCount',
                'industrialBuildingCount',
                'educationBuildingCount',
                'institutionBuildingCount',
                'institutionNames'
            );
            $data['othersCount'] = $buildingCount - (
                $commercialBuildCount
                + $residentialBuildingCount
                + $mixedBuildCount
                + $industrialBuildingCount
                + $institutionBuildingCount
                + $educationBuildingCount
            );
        }

        if ($user->can('Sanitation CountBox')) {
            $data['sanitationSystemOther'] = DB::table('building_info.buildings as b')
                ->join('building_info.sanitation_systems as s', 'b.sanitation_system_id', '=', 's.id')
                ->where('s.dashboard_display', false)
                ->whereNull('b.deleted_at')
                ->count();

            $data['sanitationSystemOthername'] = DB::table('building_info.buildings as b')
                ->join('building_info.sanitation_systems as s', 'b.sanitation_system_id', '=', 's.id')
                ->where('s.dashboard_display', false)
                ->where('s.id', '!=', 11)
                ->whereNull('b.deleted_at')
                ->distinct()
                ->select('s.sanitation_system')
                ->get();
            $data['sanitationSystems'] = $this->buildingDashboardService->getBuildingSanitationSystem();
        }

        if ($user->can('Ward-Wise Distribution of Buildings Chart')) {
            $data['buildingsPerWardChart'] = $this->buildingDashboardService->getBuildingsPerWardChart();
        }

        if ($user->can('Building Use Composition Chart')) {
            $data['buildingUseChart'] = $this->buildingDashboardService->getBuildingUseChart();
        }

        return $data;
    }
}
