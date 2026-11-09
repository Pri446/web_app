<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Http\Controllers\Fsm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fsm\EmptyingApiRequest;
use App\Http\Requests\Fsm\EmptyingRequest;
use App\Models\Fsm\Application;
use App\Models\Fsm\Emptying;
use App\Models\Fsm\SludgeCollection;
use App\Models\Fsm\TreatmentPlant;
use App\Services\Fsm\EmptyingService;
use App\Services\Fsm\OperationalDataAccessService;
use DateTimeZone;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Intervention\Image\Facades\Image;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Fsm\Containment;
use DB;

class EmptyingController extends Controller
{
    protected EmptyingService $emptyingService;

    public function __construct(EmptyingService $emptyingService)
    {
        $this->middleware('auth');
        $this->middleware('permission:List Emptyings', ['only' => ['index', 'getData']]);
        $this->middleware('permission:View Emptying', ['only' => ['show']]);
        $this->middleware('permission:Add Emptying', ['only' => ['create', 'store']]);
        $this->middleware('permission:Edit Emptying', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete Emptying', ['only' => ['destroy']]);
        $this->middleware('permission:View Emptyings History', ['only' => ['history']]);
        $this->middleware('permission:Export Emptyings', ['only' => ['export']]);
        $this->emptyingService = $emptyingService;
    }

    /**
     * Display a list of emptying history.
     *
     * @return View
     */
    public function index(Request $request)
    {
        $filterFormFields = $this->emptyingService->getFilterFormFields();
        $containment_code = '';
        if ($request->filled('containment_public_id')) {
            $containment_code = Containment::where('public_id', $request->containment_public_id)
                ->firstOrFail()
                ->id;
        } elseif ($request->filled('containment_code')) {
            // Temporary compatibility for existing bookmarks.
            $containment_code = $request->containment_code;
        }
        $exportBtnLink = Auth::user()->can('Export Emptyings')?$this->emptyingService->getExportRoute():null;
        return view('fsm.emptying.index',compact('filterFormFields','exportBtnLink','containment_code'));
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
        return $this->emptyingService->getDatatable($request);
    }

    /**
     * Display the create form for emptying.
     *
     * @return View
     */
    public function create(int $id)
    {
        $exists = Emptying::where('application_id', $id)->exists();
        $application = Application::findOrFail($id);
        OperationalDataAccessService::authorizeApplication($application);
        
        return view('fsm.emptying.create',[
            'formAction' => $this->emptyingService->getCreateFormAction(),
            'formFields' => $this->emptyingService->getCreateFormFields($id),
            'indexAction' => url()->previous(),
            'application_id' => $id,
            'exists' => $exists,
            'is_anf'         => (bool) $application->is_anf,
        ]);
    }

    /**
     * Store a newly created emptying in storage.
     *
     * @param EmptyingRequest $request
     * @return RedirectResponse|Redirector
     */
     public function store(EmptyingRequest $request)
    {
        return $this->emptyingService->createEmptying($request, 'web');
    }

    /**
     * Display the specified emptying.
     *
     * @param  int  $id
     * @return View
     */
    public function show($id)
    {
        $emptying = Emptying::findOrFail($id);
        OperationalDataAccessService::authorizeRecord(
            $emptying->service_provider_id,
            $emptying->treatment_plant_id
        );

        $page_title = __("Emptying Details");
        $formFields = $this->emptyingService->getShowFormFields($emptying);
        $indexAction = url()->previous();
        return view('layouts.show',compact('page_title','formFields','emptying','indexAction'));
    }

    /**
     * Show the form for editing the specified emptying.
     *
     * @param  int  $id
     * @return View
     */
    public function edit($id)
    {
        $emptying = Emptying::findOrFail($id);
        OperationalDataAccessService::authorizeRecord(
            $emptying->service_provider_id,
            $emptying->treatment_plant_id
        );
        $application = Application::with('emptying')
            ->findOrFail($emptying->application_id);
        $is_anf = (bool) $application->is_anf;

        if( !(Auth::user()->hasRole('Super Admin') || Auth::user()->hasRole('Municipality - Sanitation Department')) )
        {
            if($emptying->created_at->diffInDays(today()) > 1)
            {
                return redirect('fsm/application')->with('error',__('Cannot update Emptying Information 24 hours after creation. Please contact Sanitation Department for more information.'));
            }
        }
        $page_title = __("Edit Emptying Service Details");
        $formFields = $this->emptyingService->getEditFormFields($emptying);
        $formAction = $this->emptyingService->getEditFormAction($emptying);
        $indexAction = url()->previous();

        return view('fsm.emptying.edit', compact(
            'page_title',
            'formFields',
            'formAction',
            'indexAction',
            'emptying',
            'application',
            'is_anf'
        ));
    }

    /**
     * Update the specified emptying in storage.
     *
     * @param EmptyingRequest $request
     * @param int $id
     * @return Redirector|RedirectResponse
     */
    public function update(EmptyingRequest $request, $id)
    {
        return $this->emptyingService->updateEmptying($request,$id);
    }

    /**
     * Get the history of changes on the specified emptying
     *
     * @param  int  $id
     * @return Redirector|RedirectResponse
     */
    public function history($id)
    {
        return $this->emptyingService->getEmptyingHistory($id);
    }

    /**
     * Remove the specified emptying from storage.
     *
     * @param  int  $id
     * @return Redirector|RedirectResponse
     */
public function destroy($id)
{
    try {
        $emptying = Emptying::findOrFail($id);
        OperationalDataAccessService::authorizeRecord(
            $emptying->service_provider_id,
            $emptying->treatment_plant_id
        );

        if ($emptying->feedback()->exists()) {
            return redirect(route('emptying.index'))
                ->with('error', __('Cannot delete Emptying that has associated Feedback Information.'));
        }

        // allowing super admin and sanitation department to delete anytime,
        // else only allowing deletion within 24 hours.
        if (!(Auth::user()->hasRole('Super Admin') || Auth::user()->hasRole('Municipality - Sanitation Department'))) {
            if ($emptying->created_at->diffInDays(today()) > 1) {
                return redirect(route('emptying.index'))
                    ->with('error', __('Cannot delete Emptying Information 24 hours after creation. Please contact Sanitation Department for more information.'));
            }
        }

        $application = Application::findOrFail($emptying->application_id);
        $sludgecollection = SludgeCollection::where('application_id', $emptying->application_id)->first();

        // If sludge collection exists and trip_no mismatches
        if ($sludgecollection && $emptying->trip_no != $sludgecollection->trip_no) {
            return redirect('fsm/emptying')->with([
                'popup_confirm' => true,
                'popup_message' => __('This Emptying is linked to a different Sludge Collection. Deleting it will also delete the entire application. Do you want to proceed?'),
                'application_id' => $emptying->application_id
            ]);
        }

        // If sludge collection exists and matches, block deletion
        if ($sludgecollection && $emptying->trip_no == $sludgecollection->trip_no) {
            return redirect(route('emptying.index'))
                ->with('error', __('Cannot delete Emptying that has associated Sludge Collection Log Information.'));
        }

        // updating emptying status for particular application
        if ($application->sludge_collection_status == 0) {

            $application->emptying_status = 0;
            $application->save();

            // deleting emptying record
            $emptying->delete();

            // updating containment info
            $containment = Containment::findOrFail($application->containment_id);

            if ($containment->no_of_times_emptied > 0) {
                $containment->no_of_times_emptied -= 1;
            }

            if ($containment->no_of_times_emptied == 0) {
                $containment->emptied_status = 0;
                $containment->last_emptied_date = null;
                $containment->next_emptying_date = null;
            }
        } else {

            $containment = Containment::findOrFail($application->containment_id);

            $previous_application = Application::where('containment_id', $application->containment_id)
                ->where('id', '!=', $application->id)
                ->whereIn('emptying_status', [1, 2])
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->get();

            if ($previous_application && $previous_application->emptying()->exists()) {

                $previous_emptying = Emptying::where('application_id', $previous_application->id)
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($previous_emptying) {
                    $containment->emptied_status = true;
                    $containment->last_emptied_date = $previous_emptying->emptied_date;
                    $containment->next_emptying_date = $previous_emptying->emptied_date->addYears(3);
                }
            } else {
                $containment->last_emptied_date = null;
                $containment->next_emptying_date = null;
            }
        }

        $containment->save();

        return redirect('fsm/emptying')
            ->with('success', __('Emptying deleted successfully.'));

    } catch (\Throwable $e) {
        \Log::error('Emptying delete failed', [
            'emptying_id' => $id,
            'error' => $e->getMessage()
        ]);

        return redirect(route('emptying.index'))
            ->with('error', __('Something went wrong while deleting the Emptying. Please try again.'));
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
            $this->emptyingService->export($request);
        } catch (\Throwable $e) {
            return redirect(route('emptying.index'))->with('error',__('Failed to export emptyings.'));
        }
    }
    
    public function capacityTreatmentPlant()
    {
        $totalCapacity = (float) TreatmentPlant::where('status', true)->whereIn('type', [3,5])->get();

        $totalBooked = (float) Application::sum(DB::raw('COALESCE(desludging_vehicle_size, 0)'));

        $available = max($totalCapacity -  $totalBooked, 0.0);
        $remainingAfter = $available;
        $blocked = ($totalCapacity <= 0) || ($totalBooked >= $totalCapacity);

        return response()->json([
        'total_capacity'  => $totalCapacity,
        'booked_total'    => $totalBooked,
        'available'       => $available,
        'remaining_after' => $remainingAfter,
        'blocked'         => $blocked,
    ]);
    }
}
