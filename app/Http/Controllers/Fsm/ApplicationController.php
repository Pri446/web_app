<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Http\Controllers\Fsm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fsm\ApplicationRequest;
use App\Models\BuildingInfo\Building;
use App\Models\UtilityInfo\Roadline;
use App\Models\Fsm\Application;
use App\Models\Fsm\SludgeCollection;
use App\Models\Fsm\SludgeCollectionLog;
use App\Models\Fsm\Emptying;
use App\Models\Fsm\ServiceProvider;
use App\Models\Fsm\VacutugType;
use App\Services\Fsm\ApplicationService;
use App\Services\Fsm\OperationalDataAccessService;
use Exception;
use App\Models\Fsm\Containment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Venturecraft\Revisionable\Revision;
use Yajra\DataTables\Facades\DataTables;
use  App\Models\Fsm\TreatmentPlant;
use App\Models\LayerInfo\Ward;
use Carbon\Carbon;
use DB;

class ApplicationController extends Controller
{
    protected ApplicationService $applicationService;

    public function __construct(ApplicationService $applicationService)
    {
        $this->middleware('auth');
        $this->middleware('permission:List Applications', ['only' => ['index', 'getData']]);
        $this->middleware('permission:View Application', ['only' => ['show']]);
        $this->middleware('permission:Add Application', ['only' => ['create', 'store']]);
        $this->middleware('permission:Edit Application', ['only' => ['edit', 'update', 'editScheduling', 'schedulingform', 'checkCapacityByDate', 'resolveAnf']]);
        $this->middleware('permission:Delete Application', ['only' => ['destroy', 'forceDelete']]);
        $this->middleware('permission:Export Applications', ['only' => ['export']]);
        $this->middleware('permission:View Application History', ['only' => ['history']]);
        $this->middleware('permission:Generate Application Report', ['only' => ['applicationReport']]);
        $this->applicationService = $applicationService;
    }

    /**
     * Display a list of applications.
     *
     * @return View
     */
    public function index()
    {
        $createBtnLink = Auth::user()->can('Add Application') ? $this->applicationService->getCreateRoute() : null;
        $createBtnTitle = __('Add Application');
        $exportBtnLink = Auth::user()->can('Export Applications') ? $this->applicationService->getExportRoute() : null;
        $reportBtnLink = Auth::user()->can('Generate Application Report') ? $this->applicationService->getReportRoute() : null;
        $filterFormFields = $this->applicationService->getFilterFormFields();
        $application_months = DB::select("select distinct extract(month from application_date) as date1 from fsm.applications where deleted_at is null order by date1 asc");
        $application_years = DB::select("select distinct extract(year from application_date) as date1 from fsm.applications where deleted_at is null order by date1 desc");

        return view('fsm.applications.index', compact('createBtnLink', 'createBtnTitle', 'filterFormFields', 'exportBtnLink', 'reportBtnLink', 'application_months', 'application_years'));
    }

    /**
     * Prepare data for the DataTable.
     *
     * @param Request $request
     * @return DataTables
     * @throws Exception
     */
    public function getData(Request $request)
    {
        return $this->applicationService->getDatatable($request);
    }

    /**
     * Display the create form for application.
     *
     * @return View
     */
    public function create(Request $request)
    {
        $action_type = $request->query('action_type')
            ?? old('action_type');

        if ($action_type !== 'confirm') {
            session()->forget([
                'schedule_accept',
                'action_type',
                'bin',
                'containment_id',
                'road_code',
                'ward',
                'service_provider_id',
                'next_emptying_date',
            ]);
        }

        $scheduleRoadText = null;
        $scheduleBinText = null;

        if ($action_type === 'confirm' && session('schedule_accept')) {
            $scheduleAccept = session('schedule_accept');
            if (!empty($scheduleAccept['road_code'])) {
                $road = Roadline::where('code', $scheduleAccept['road_code'])->first();
                $scheduleRoadText = $road ? ($road->name ?? $road->code) : $scheduleAccept['road_code'];
            }
            if (!empty($scheduleAccept['bin'])) {
                $building = Building::where('bin', $scheduleAccept['bin'])->first();
                $scheduleBinText = $building ? ($building->house_number ?? $building->bin) : $scheduleAccept['bin'];
            }
        }

        return view('fsm.applications.create', [
            'formAction' => $this->applicationService->getCreateFormAction(),
            'formFields' => $this->applicationService->getCreateFormFields(),
            'indexAction' => $this->applicationService->getIndexAction(),
            'action_type' => $action_type,
            'anfWardOptions' => Ward::orderBy('ward')->pluck('ward', 'ward')->toArray(),
            'scheduleRoadText' => $scheduleRoadText,
            'scheduleBinText' => $scheduleBinText,
        ]);
    }

    /**
     * Get the building details for the selected address.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function buildingDetails(Request $request)
    {
        return $this->applicationService->getBuildingDetails($request);
    }

    /**
     * Get desludging vehicle capacities for the selected Service Provider.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getVehiclesByServiceProvider(Request $request): JsonResponse
    {
        $spId = $request->input('service_provider_id');
        if (!$spId) {
            return response()->json([]);
        }

        $capacities = VacutugType::query()
            ->where('service_provider_id', $spId)
            ->whereNotNull('capacity')
            ->distinct()
            ->orderBy('capacity')
            ->pluck('capacity')
            ->map(fn($c) => 0 + $c)
            ->values()
            ->toArray();

        return response()->json($capacities);
    }

    /**
     * Store a newly created application in storage.
     *
     * @param ApplicationRequest $request
     * @return RedirectResponse|Redirector
     */
    public function store(ApplicationRequest $request)
    {
        return $this->applicationService->createApplication($request);
    }

    /**
     * Display the specified application.
     *
     * @param  int  $id
     * @return View
     */
    public function show($id)
    {
        $application = Application::findOrFail($id);
        OperationalDataAccessService::authorizeApplication($application);

        $page_title = __('Application Details');
        $formFields = $this->applicationService->getShowFormFields($application);
        $indexAction = $this->applicationService->getIndexAction();

        return view('layouts.show', compact('page_title', 'formFields', 'application', 'indexAction'))
            ->with('cardForm', true);
    }

    /**
     * Show the form for editing the specified application.
     *
     * @param  int  $id
     * @return View
     */
    public function edit($id)
    {
        $application = Application::findOrFail($id);
        OperationalDataAccessService::authorizeApplication($application);

        $page_title = __("Edit Application");
        $formFields = $this->applicationService->getEditFormFields($application);
        $formAction = $this->applicationService->getEditFormAction($application);
        $indexAction = $this->applicationService->getIndexAction();
        return view('fsm.applications.edit', compact('page_title', 'formFields', 'formAction', 'indexAction', 'application'), ['cardForm' => true]);
    }

    /**
     * Update the specified application in storage.
     *
     * @param ApplicationRequest $request
     * @param int $id
     * @return Redirector|RedirectResponse
     */
    public function update(ApplicationRequest $request, $id)
    {
        return $this->applicationService->updateApplication($request, $id);
    }

    /**
     * Remove the specified application from storage.
     *
     * @param  int  $id
     * @return Redirector|RedirectResponse
     */
    public function destroy($id)
    {
        try {
            $application = Application::findOrFail($id);
            OperationalDataAccessService::authorizeApplication($application);
            if ($application->emptying()->exists()) {
                return redirect('fsm/application')->with('error', __('Cannot delete Application that has associated Emptying Information.'));
            }
            if ($application->sludge_collection()->exists()) {
                return redirect('fsm/application')->with('error', __('Cannot delete Application that has associated Sludge Collection Information.'));
            }
            if ($application->feedback()->exists()) {
                return redirect('fsm/application')->with('error', __('Cannot delete Application that has associated Feedback Information.'));
            }
            $application->delete();
        } catch (\Throwable $e) {
            return redirect('fsm/application')->with('error', __('Failed to delete Application.'));
        }
        return redirect('fsm/application')->with('success', __('Application deleted successfully.'));
    }

    /**
     * Get the history of changes on the specified application.
     *
     * @param  int  $id
     * @return Redirector|RedirectResponse
     */
    public function history($id)
    {
        return $this->applicationService->getApplicationHistory($id);
    }

    public function resolveAnf(Request $request, $id)
    {
        OperationalDataAccessService::authorizeApplication(Application::findOrFail($id));
        $request->validate([
            'bin'            => 'required|string',
            'containment_id' => 'required|string', // 👈 required
        ]);

        $application = Application::find($id);

        if (!$application) {
            return response()->json([
                'status'  => false,
                'message' => __('Application not found.')
            ], 404);
        }

        if (!$application->is_anf) {
            return response()->json([
                'status'  => false,
                'message' => __('This application is not an Address Not Found (ANF) application or has already been resolved.')
            ], 422);
        }

        $user = Auth::user();
        if ($user && $user->hasRole('Service Provider - Emptying Operator')) {
            if ($user->service_provider_id && $application->service_provider_id != $user->service_provider_id) {
                return response()->json([
                    'status'  => false,
                    'message' => __('Unauthorized. You do not have permission to resolve applications for other service providers.')
                ], 403);
            }
        }

        try {
            $this->applicationService->resolveAnf(
                $id,
                $request->bin,
                $request->containment_id
            );

            return response()->json(['status' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Export applications to csv.
     *
     * @return Redirector|RedirectResponse
     */

    public function export(Request $request)
    {
        try {
            $this->applicationService->export($request);
        } catch (\Throwable $e) {
            return redirect(route('application.index'))->with('error', __('Failed to export applications.'));
        }
    }
    /**
     * Generate a PDF report for monthly applications.
     *
     * @param int $year The year for the report.
     * @param int $month The month for the report.
     * @return \Illuminate\Http\Response The generated PDF report.
     */
    public function monthlyApplicationsPdf($year, $month)
    {
        return $this->applicationService->fethMonthlyReport($year, $month);
    }
    /**
     * Retrieve a report for a specific application.
     *
     * @param int $id The ID of the application.
     * @return \Illuminate\Http\Response The application report.
     */
    public function applicationReport($id)
    {
        OperationalDataAccessService::authorizeApplication(Application::findOrFail($id));
        return $this->applicationService->getApplicationReport($id);
    }

    public function editScheduling($id)
    {
        $application = Application::findOrFail($id);
        OperationalDataAccessService::authorizeApplication($application);
        $page_title = __("Emptying Scheduling Form");
        return view('fsm.emptying-scheduling.edit', compact('application', 'page_title'));
    }

    public function schedulingform(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        OperationalDataAccessService::authorizeApplication($application);
        $request->validate([
            'proposed_emptying_date' => 'required|date|after_or_equal:today'
        ]);

        $application->proposed_emptying_date = $request->proposed_emptying_date;
        $application->save();

        // Send Push Notification
        $message = "Application (ID: {$application->id}) has been scheduled for " . \Carbon\Carbon::parse($request->proposed_emptying_date)->format('M d, Y') . ".";
        
        $etoUserIds = \DB::table('fsm.employees')
            ->where('service_provider_id', $application->service_provider_id)
            ->where('status', true)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn($uid) => (string) $uid)
            ->unique()->values()->all();

        $spUserIds = \DB::table('auth.users')
            ->where('service_provider_id', $application->service_provider_id)
            ->pluck('id')
            ->map(fn($uid) => (string) $uid)
            ->unique()->values()->all();

        $etoUserIds = array_values(array_unique(array_merge($etoUserIds, $spUserIds)));

        return redirect()->route('application.index')->with('success', __('Emptying scheduled successfully.'));
    }


    public function byCapacity(Request $request, applicationService $svc)
    {
        $data = $request->validate([
            'capacity' => 'required|integer|min:0',
        ]);

        $capacity = $data['capacity'];
        $svc->ServiceProviderOrder($capacity);

        return response()->json([
            'next_id'   => $svc->getNextServiceProviderId($capacity),
            'next_name' => $svc->getNextServiceProviderName($capacity),
            'sequence'  => $capacity,
        ]);
    }

    public function checkCapacityByDate(Request $request, Application $application)
    {
        OperationalDataAccessService::authorizeApplication($application);
        $validated = $request->validate([
            'proposed_emptying_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $date = $validated['proposed_emptying_date'];
        $vehicleSize = (float) $application->desludging_vehicle_size;

        $totalCapacity = (float) TreatmentPlant::where('status', true)->sum('capacity_per_day');

        $totalBookedForDay = (float) Application::whereDate('proposed_emptying_date', $date)->sum(DB::raw('COALESCE(desludging_vehicle_size, 0)'));

        $available      = max($totalCapacity - $totalBookedForDay, 0.0);
        $totalIfAdded   = $totalBookedForDay + $vehicleSize;
        $remainingAfter = max($totalCapacity - $totalIfAdded, 0.0);
        $canFit         = ($vehicleSize <= $available);
        $blocked        = ($totalCapacity <= 0) || ($totalIfAdded > $totalCapacity);

        return response()->json([
            'proposed_emptying_date' => $date,
            'application_id'         => $application->id,
            'vehicle_size'           => $vehicleSize,
            'total_capacity'         => $totalCapacity,
            'booked_total'           => $totalBookedForDay,
            'available'              => $available,
            'remaining_after'        => $remainingAfter,
            'can_fit'                => $canFit,
            'blocked'                => $blocked,
        ]);
    }

    public function forceDelete($id)
    {
        DB::beginTransaction();

        try {
            $application = Application::findOrFail($id);
            OperationalDataAccessService::authorizeApplication($application);

            // Update containment details
            $containment = Containment::findOrFail($application->containment_id);

            if ($containment->no_of_times_emptied > 0) {
                $containment->no_of_times_emptied--;
            }

            if ($containment->no_of_times_emptied == 0) {
                $containment->emptied_status = false;
                $containment->last_emptied_date = null;
                $containment->next_emptying_date = null;
            } else {
                $previous_application = Application::where('containment_id', $application->containment_id)
                    ->where('id', '!=', $application->id)
                    ->where('emptying_status', 1)
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'desc')
                    ->get();

                if ($previous_application->isNotEmpty() && $previous_application[0]->emptying()->exists()) {
                    $previous_emptying = Emptying::where('application_id', $previous_application[0]->id)
                        ->whereNull('deleted_at')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($previous_emptying) {
                        $containment->emptied_status = true;
                        $containment->last_emptied_date = $previous_emptying->emptied_date;
                        $containment->next_emptying_date = \Carbon\Carbon::parse($previous_emptying->emptied_date)->addYears(3);
                    }
                } else {
                    $containment->last_emptied_date = null;
                    $containment->next_emptying_date = null;
                }
            }

            $containment->save();

            // Permanently delete related data
            SludgeCollectionLog::where('application_id', $id)->forceDelete();
            SludgeCollection::where('application_id', $id)->forceDelete();
            Emptying::where('application_id', $id)->forceDelete();
            Application::where('id', $id)->forceDelete();

            DB::commit();

            return redirect('fsm/emptying')->with('success', __('Application and related data deleted successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect('fsm/emptying')->with('error', __('Failed to delete application. Please try again.'))->withErrors($e->getMessage());
        }
    }

    public function getServiceProvider($service_provider_id = null)
    {
        if ($service_provider_id) {
            // Fetch the specific service provider's data
            return ServiceProvider::Operational()
                ->where('id', $service_provider_id)
                ->pluck('company_name', 'id')
                ->toArray();
        } else {
            // Fetch all operational service providers
            return ServiceProvider::Operational()
                ->pluck('company_name', 'id')
                ->toArray();
        }
    }

public function getTreatmentPlant()
    {
        $date = Carbon::today();
        $dateFormatted = $date->format('F j, Y');

        // Sum of sludge volume for the specific day grouped by treatment plant
        $sludgeTotals = SludgeCollectionLog::whereDate('date', $date) // Filter by today's date
            ->groupBy('treatment_plant_id')
            ->selectRaw('treatment_plant_id, SUM(volume_of_sludge) as total')
            ->pluck('total', 'treatment_plant_id');

        // Fetch only plants of type FSTP (1) or Co-Treatment (2)
        $treatmentPlants = TreatmentPlant::whereIn('type', [3,4])  // Use integer values instead of strings
            ->where('status', true)  // Only active plants
            ->select('id', 'name', 'location', 'capacity_per_day')
            ->get()
            ->map(function ($plant) use ($sludgeTotals) {
                // Get the total sludge for today for the current plant (default to 0 if no data)
                $totalUsed = $sludgeTotals[$plant->id] ?? 0;

                // Calculate the remaining capacity for today
                $plant->remaining_capacity = $plant->capacity_per_day - $totalUsed;

                return $plant;
            });

        // Return the response as JSON, including the date and the plants data
        return response()->json([
            'plants' => $treatmentPlants,
            'date' => $date->toDateString(),
            'date_display' => $dateFormatted,
        ]);
    }
}
