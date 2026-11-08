<?php
// Last Modified Date: 19-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)
namespace App\Http\Controllers;

use App\Models\Fsm\KeyPerformanceIndicator;
use Illuminate\Http\Request;
use App\Models\Fsm\VacutugType;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildingInfo\Building;
use App\Models\Fsm\Containment;
use App\Models\Fsm\Emptying;
use App\Models\Fsm\ServiceProvider;
use App\Models\Fsm\Application;
use App\Models\Fsm\Feedback;
use App\Models\Fsm\TreatmentPlant;
use App\Models\Fsm\SludgeCollection;
use App\Services\DashboardService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use App\Models\UtilityInfo\Roadline;
use App\Models\UtilityInfo\SewerLine;
use App\Models\UtilityInfo\Drain;
use App\Models\Fsm\Ctpt;
use App\Models\Fsm\CtptUsers;
use App\Models\PublicHealth\Hotspots;
use App\Models\PublicHealth\YearlyWaterborne;
use App\Models\UtilityInfo\WaterSupplys;
use App\Models\WaterSupplyInfo\WaterSupply;
use App\Models\BuildingInfo\FunctionalUse;
use App\Models\BuildingInfo\SanitationSystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected DashboardService $dashboardService;
    public function __construct(DashboardService $dashboardService)
    {
        $this->middleware('auth');
        $this->dashboardService = $dashboardService;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */


    public function index()
    {
        return view('dashboard.indexAdmin', [
            'page_title' => __('IMIS Dashboard'),
        ]);
    }

    /**
     * Return the authenticated user's dashboard as a stable JSON contract.
     * The data cache is scoped by user, role/permissions, provider, plant and filters.
     */
    public function content(Request $request): JsonResponse
    {
        $user = $request->user();
        $filters = $request->only(['year']);
        $cacheKey = $this->dashboardService->dashboardCacheKey($user, $filters);
        $cacheHit = $this->dashboardService->dashboardHtmlCacheHas($cacheKey);

        try {
            $html = $this->dashboardService->rememberDashboardHtml(
                $cacheKey,
                function () use ($cacheKey) {
                    $data = $this->dashboardService->rememberDashboardData(
                        $cacheKey,
                        function () {
                            return $this->buildDashboardData();
                        }
                    );

                    // Rendering occurs only after the current user's permissions have
                    // been included in the cache key.
                    return view('dashboard._content', $data)->render();
                }
            );

            return response()->json([
                'status' => 'ok',
                'html' => $html,
                'meta' => [
                    'cache' => $cacheHit ? 'hit' : 'miss',
                    'ttl_seconds' => $this->dashboardService->dashboardCacheTtl(),
                ],
            ])->header('Cache-Control', 'private, no-store');
        } catch (\Throwable $exception) {
            Log::error('Dashboard content failed to load.', [
                'user_id' => $user->id,
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
        $page_title = __("IMIS Dashboard");

        $buildingCount = Building::whereNull('deleted_at')->count();



        $commercialBuildCount = $this->dashboardService->countBuildingsByUseExact('Commercial');
        $residentialBuildingCount = $this->dashboardService->countBuildingsByUseExact('Residential');
        $mixedBuildCount = $this->dashboardService->countBuildingsByUseExact('Mixed (Residential, Commercial, Office uses)');
        $industrialBuildingCount = $this->dashboardService->countBuildingsByUseExact('Industrial');
        $educationBuildingCount = $this->dashboardService->countBuildingsByUseExact('Educational');
        $institutionBuildingCount = $this->dashboardService->countBuildingsByUse('Institution');
        $institutionNames = FunctionalUse::where('name', 'like', '%Institution%')
            ->pluck('name')
            ->implode('<br>');
        // Number of buildings with other functional uses (Displayed as "Others" in UI)
        $othersCount = $buildingCount - ($commercialBuildCount + $residentialBuildingCount + $mixedBuildCount  + $industrialBuildingCount + $institutionBuildingCount  + $educationBuildingCount);

        //Sanitation Systems Count
        // Total number of sanitation systems (Displayed as "Total Containments" in UI)
        $containmentCount = Containment::whereNull('deleted_at')->count();
        //Utility Count
        // Calculate the total length of roads (Displayed as "Total length (m) of roads" in UI)
        $sumRoads = Roadline::sum('length');
        // Calculate the total length of sewers (Displayed as "Total length (m) of sewers" in UI)
        $sumSewers = SewerLine::sum('length');
        // Calculate the total length of drains (Displayed as "Total length (m) of drains" in UI)
        $sumDrains = Drain::sum('length');
        // Calculate the total length of water supply (Displayed as "Total length (m) of water supply" in UI)
        $sumWatersupply = WaterSupplys::sum('length');
        //Ptct count
        $ctCount = Ctpt::where('type', 'Community Toilet')->where('status', true)->whereNull('deleted_at')->count();
        $ptCount = Ctpt::where('type', 'Public Toilet')->where('status', true)->whereNull('deleted_at')->count();
        // Query to calculate the total number of users served by community toilets for the specified year
        $communityToilet = DB::table('fsm.toilets as t')
            ->select(DB::raw('sum(b.population_served) as toilet_users')) // Selecting the sum of users served by community toilets
            ->leftJoin('fsm.build_toilets as bt', function($join) {
            $join->on('bt.toilet_id', '=', 't.id')
                 ->whereNull('bt.deleted_at'); // Check if deleted_at is NULL
            })
            ->leftJoin('building_info.buildings as b', 'b.bin', '=', 'bt.bin') // Joining with buildings table
            ->where('t.type', '=', 'Community Toilet')
            ->where('t.status', true)
            ->whereNull('t.deleted_at')->first(); // Retrieve the first row

        // Extracting the total number of users served by community toilets
        $totalCtUser = $communityToilet->toilet_users;
        $sanitationSystemOther = DB::table('building_info.buildings as b')
            ->join('building_info.sanitation_systems as s', 'b.sanitation_system_id', '=', 's.id')
            ->where('s.dashboard_display', false)
            ->whereNull('b.deleted_at')
            ->count();
            $sanitationSystemOthername = $sanitationSystemNames = DB::table('building_info.buildings as b')
            ->join('building_info.sanitation_systems as s', 'b.sanitation_system_id', '=', 's.id')
            ->where('s.dashboard_display', false)
            ->where('s.id', '!=', 11) // Exclude sanitation_systems.id == 12
            ->whereNull('b.deleted_at')
            ->distinct()
            ->select('s.sanitation_system')
            ->get();





        // Query to calculate the total number of users served by public toilets for the specified year
        $publicToilet = DB::table('fsm.toilets as t')
            ->join('fsm.ctpt_users as u', 't.id', '=', 'u.toilet_id') // Joining with ctpt_users table
            ->selectRaw('sum(u.no_male_user) as total_male_user, sum(u.no_female_user) as total_female_user') // Selecting the sum of male and female users
            ->where('t.type', 'Public Toilet') // Filtering for public toilets
            ->where('t.status', true)
            ->whereNull('t.deleted_at')->whereNull('u.deleted_at')->first(); // Retrieve the first row

        // Calculating the total number of users served by public toilets
        $totalPtUser = $publicToilet->total_male_user + $publicToilet->total_female_user;
        $maxDate = date('Y');
        $minDate = date('Y') - 4;
        if (Auth::user()->hasRole('Service Provider - Admin') || Auth::user()->hasRole('Service Provider - Help Desk')) {
            $whereRawEmptyingsServiceProvider = "emptyings.service_provider_id = " . Auth::user()->service_provider_id;
            $whereRawApplicationsServiceProvider = "applications.service_provider_id = " . Auth::user()->service_provider_id;
            $whereRawSludgeCollectionServiceProvider = "sludge_collections.service_provider_id = " . Auth::user()->service_provider_id;
            $whereRawDesludgingVehicleServiceProvider = "service_provider_id = " . Auth::user()->service_provider_id;
            $whereRawFeedbackServiceProvider = "feedbacks.service_provider_id = " . Auth::user()->service_provider_id;
            $whereUserId = "users.id = " . Auth::user()->id;
            $whereRawSludgeServiceProvider = "sludge_collections.service_provider_id = " . Auth::user()->service_provider_id;
        } else {
            $whereRawEmptyingsServiceProvider = "1 = 1";
            $whereRawApplicationsServiceProvider = "1 = 1";
            $whereRawSludgeCollectionServiceProvider = "1 = 1";
            $whereRawDesludgingVehicleServiceProvider = "1 = 1";
            $whereRawFeedbackServiceProvider = "1 = 1";
            $whereUserId = "1 = 1";
            $whereRawSludgeServiceProvider = "1 = 1";
        }
        if (Auth::user()->hasRole('Treatment Plant - Admin')) {
            $whereRawTreatmentPlant = "treatment_plant_id = " . Auth::user()->treatment_plant_id;
        } else {
            $whereRawTreatmentPlant = "1 = 1";
        }



        //FSM Service Dashboard
        // Check if a specific year is provided

        // Fetching the number of emptying by months chart data
        $numberOfEmptyingbyMonthsChart = $this->dashboardService->getNumberOfEmptyingbyMonths(null);
        // Counting the unique containment codes emptied
        $uniqueContainCodeEmptiedCount = Application::where('emptying_status', true)
            ->whereNull('deleted_at')
            ->whereRaw($whereRawApplicationsServiceProvider)
            ->distinct('containment_id')
            ->count('containment_id');
        // Counting the number of emptying services
        $emptyingServiceCount = Application::whereRaw($whereRawApplicationsServiceProvider)->where('emptying_status', true)->whereNull('deleted_at')->count('id');

        // Fetching service providers and their IDs
        $serviceProvidersRs = ServiceProvider::leftJoin('auth.users', 'service_providers.id', '=', 'users.service_provider_id')
            ->select('service_providers.id')
            ->where('service_providers.status', 1)
            ->whereRaw($whereUserId)
            ->whereNull('service_providers.deleted_at')
            ->groupBy(['service_providers.id', 'service_providers.company_name'])
            ->get();
        // Counting the number of service providers
        $serviceProviderCount = (count($serviceProvidersRs));

        // Counting the total number of applications
        $applicationCount = Application::whereNull('deleted_at')
            ->whereRaw($whereRawApplicationsServiceProvider)
            ->count();

        // Fetching the cost paid by containment owner per ward chart data
        $costPaidByContainmentOwnerPerwardChart = $this->dashboardService->getCostPaidByContainmentOwnerPerward(null);

        // Summing the volume of sludge collected from emptying services
        $sludgeCollectionEmptyingServices = Emptying::whereNull('deleted_at')
            ->whereRaw($whereRawEmptyingsServiceProvider)
            ->sum('volume_of_sludge');

        // Summing the total volume of sludge collected from sludge collection services
        $sludgeCollectionsCount = SludgeCollection::whereNull('deleted_at')
            ->whereRaw($whereRawSludgeCollectionServiceProvider)
            ->sum('volume_of_sludge');

        // Counting the number of treatment plants
        $treatmentPlantCount = TreatmentPlant::leftJoin('auth.users', 'treatment_plants.id', '=', 'users.treatment_plant_id')->where('treatment_plants.status', 1)->whereNull('treatment_plants.deleted_at')->whereRaw($whereUserId)->distinct('treatment_plants.id')->count('treatment_plants.id');

        // Counting the number of desludging vehicles
        $desludgingVehicleCount = VacutugType::where('status', 1)->whereNull('deleted_at')->whereRaw($whereRawDesludgingVehicleServiceProvider)->count();

        // Summing the total cost paid by owner with receipts
        $costPaidByOwnerWithReceipt = Emptying::whereNull('deleted_at')
            ->whereRaw($whereRawEmptyingsServiceProvider)
            ->sum('total_cost');

        // Fetching data for the emptying service per wards chart
        $emptyingServicePerWardsChart = $this->dashboardService->getEmptyingServicePerWards(null);

        // Fetching monthly application requests by operators
        $monthlyAppRequestByoperators = $this->dashboardService->getMonthlyAppRequestByoperators(null);

        // Fetching data for FSM service quality chart
        $fsmSrvcQltyChart = $this->dashboardService->getFsmSrvcQltyChart(null);

        // Fetching data for Personal Protective Equipment (PPE) chart
        $ppe = $this->dashboardService->getppeChart(null);

        // Fetching data for hotspots per ward chart
        $hotspotsPerWardChart = $this->dashboardService->getHotspotsPerWard(null);

        // Fetching data for sludge collection by treatment plant chart
        $sludgeCollectionByTreatmentPlantChart = $this->dashboardService->getSludgeCollectionByTreatmentPlantChart(null);

        // Counting the total number of hotspots
        $totalHotspot = Hotspots::count();

        // Counting the total number of yearly waterborne diseases
        $totalWaterborne = $totalWaterborne = YearlyWaterborne::sum('total_no_of_cases');



        // Fetching data for buildings per ward chart
        $buildingsPerWardChart = $this->dashboardService->getBuildingsPerWardChart();

        // Fetching data for sanitation systems chart
        $sanitationSystemsChart = $this->dashboardService->getSanitationSystemsChart();

        // Fetching data for emptying requests by structure types chart
        $emptyingRequestsbyStructureTypesChart = $this->dashboardService->getEmptyingRequestsPerStructureTypeChart();

        // Fetching data for containment types per ward chart
        $containmentTypesPerWardChart = $this->dashboardService->getContainmentTypesPerWard();

        // Fetching data for containment types by building uses chart
        $containmentTypesByBldgUsesChart = $this->dashboardService->getContainmentTypesByBldgUse();

        // Fetching data for containment types by building uses (residentials) chart
        $containmentTypesByBldgUsesResidentialsChart = $this->dashboardService->getContainmentTypesByBldgUseResidentials();

        // Fetching data for containment types by land use chart
        $containmentTypesByLanduseChart = $this->dashboardService->getContainmentTypesByLanduse();

        // Fetching data for emptying service by type and year chart
        $emptyingServiceByTypeYearChart = $this->dashboardService->getEmptyingServiceByTypeYear();

        // Fetching data for containment emptied by ward chart
        $containmentEmptiedByWardChart = $this->dashboardService->getcontainmentEmptiedByWard();

        // Fetching data for containment type chart
        $containTypeChart = $this->dashboardService->getContainTypeChart();

        // Fetching data for building use chart
        $buildingUseChart = $this->dashboardService->getBuildingUseChart();

        // Fetching data for next emptying containments chart
        $nextEmptyingContainmentsChart = $this->dashboardService->getNextEmptyingContainmentsChart();

        // Fetching data for tax revenue chart
        $taxRevenueChart = $this->dashboardService->getTaxRevenueChart();

        // Fetching data for SWM service chart
        $solidWasteChart = $this->dashboardService->getSolidWastePaymentChart();

        // Fetching data for water supply payment chart
        $waterSupplyPaymentChart = $this->dashboardService->getWaterSupplyPaymentChart();

        // Fetching data for proposed emptying date containments chart
        $proposedEmptyingDateContainmentsChart = $this->dashboardService->getproposedEmptyingDateContainmentsChart();

        // Fetching data for proposed emptied date containments by ward chart
        $proposedEmptiedDateContainmentsByWardChart = $this->dashboardService->getProposedEmptiedDateContainmentsByWard();

        // Fetching data for sewer length per ward chart
        $sewerLengthPerWardChart = $this->dashboardService->getSewerLengthPerWard();

        // Setting max and min date variables
        $maxDate = date('Y');
        $minDate = date('Y') - 4;



        // Fetching data for road length per ward chart
        $roadLengthPerWardChart = $this->dashboardService->getRoadLengthPerWardChart();

        // Fetching data for waterborne cases chart
        $waterborneCasesChart = $this->dashboardService->getWaterborneCasesChart();
        // Fetching count for sanitation systems
        $sanitationSystems = $this->dashboardService->getBuildingSanitationSystem();
        $sanitationSystemsOthers = $this->dashboardService->getBuildingSanitationSystemOthers();
        $swmPresenceward = $this->dashboardService->swmPresencebyWard();
        $taxCodePresenceward = $this->dashboardService->taxCodePresencebyWard();
        $pipeCodePresenceWard = $this->dashboardService->waterSupplyPipeCodePresenceByWard();
        $treatmentPlantTest = $this->dashboardService->treatmentPlantTestResultsByYear();
        return compact(
            'page_title',
            'buildingCount',
            'commercialBuildCount',
            'residentialBuildingCount',
            'mixedBuildCount',
            'educationBuildingCount',

            'containmentCount',
            'emptyingServiceCount',
            'serviceProviderCount',
            'sludgeCollectionsCount',
            'uniqueContainCodeEmptiedCount',
            'desludgingVehicleCount',
            'applicationCount',
            'buildingsPerWardChart',
            'sanitationSystemsChart',
            'numberOfEmptyingbyMonthsChart',
            'emptyingRequestsbyStructureTypesChart',
            'containmentTypesPerWardChart',
            'emptyingServicePerWardsChart',
            'emptyingServiceByTypeYearChart',
            'containmentEmptiedByWardChart',
            'containTypeChart',
            'buildingUseChart',
            'nextEmptyingContainmentsChart',
            'sludgeCollectionByTreatmentPlantChart',
            'fsmSrvcQltyChart',
            'ppe',
            'taxRevenueChart',
            'waterSupplyPaymentChart',
            'proposedEmptyingDateContainmentsChart',
            'proposedEmptiedDateContainmentsByWardChart',
            'maxDate',
            'minDate',
            'containmentTypesByBldgUsesChart',
            'monthlyAppRequestByoperators',
            'containmentTypesByBldgUsesResidentialsChart',
            'containmentTypesByLanduseChart',
            'costPaidByOwnerWithReceipt',
            'costPaidByContainmentOwnerPerwardChart',
            'sludgeCollectionEmptyingServices',
            'treatmentPlantCount',
            'hotspotsPerWardChart',
            'sanitationSystemOther',
            'sanitationSystemOthername',
            'sewerLengthPerWardChart',
            'industrialBuildingCount',
            'institutionBuildingCount',
            'othersCount',
            'sumRoads',
            'sumDrains',
            'sumSewers',
            'sumWatersupply',
            'ctCount',
            'ptCount',
            'totalCtUser',
            'totalPtUser',
            'totalHotspot',
            'totalWaterborne',
            'roadLengthPerWardChart',
            'waterborneCasesChart',
            'minDate',
            'maxDate',
            'sanitationSystems',
            'sanitationSystemsOthers',
            'institutionBuildingCount',
            'institutionNames',
            'solidWasteChart',
            'swmPresenceward',
            'taxCodePresenceward',
            'pipeCodePresenceWard',
            'treatmentPlantTest',
        );
    }

    public function countBuildingsByUse($useName)
    {
        return Building::whereIn('functional_use_id', function ($query) use ($useName) {
            $query->select('id')
                ->from('building_info.functional_uses')
                ->where('name', 'like', '%' . $useName . '%');
        })
            ->whereNull('deleted_at')
            ->count();
    }
}
