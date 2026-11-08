<?php
// Last Modified Date: 07-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  

namespace App\Http\Controllers\Fsm;

use App\Http\Controllers\Controller;
use App\Models\Fsm\Emptying;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use Illuminate\Http\Request;
use App\Models\Fsm\TreatmentPlant;
use App\Models\Fsm\SludgeCollection;
use App\Models\Fsm\SludgeCollectionLog;
use App\Models\Fsm\Application;
use App\Models\Fsm\VacutugType;
use App\Http\Requests\Fsm\SludgeCollectionRequest;
use App\Models\Fsm\ServiceProvider;
use DB;
use DataTables;
use Carbon\Carbon;
use App\Models\Notification;
use App\Models\User;
use Auth;

class SludgeCollectionController extends Controller
{
    /**
     * Constructor for the SludgeCollectionController.
     * Defines middleware for various permissions related to sludge collections.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:List Sludge Collections', ['only' => ['index']]);
        $this->middleware('permission:View Sludge Collection', ['only' => ['show']]);
        $this->middleware('permission:Add Sludge Collection', ['only' => ['create', 'store']]);
        $this->middleware('permission:Edit Sludge Collection', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete Sludge Collection', ['only' => ['destroy']]);
        $this->middleware('permission:View Sludge Collection History', ['only' => ['history']]);
        $this->middleware('permission:Export Sludge Collections', ['only' => ['export']]);
    }

    /**
     * Display a listing of the sludge collections.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $page_title = __("Sludge Collections");
        if (Auth::user()->hasRole('Treatment Plant - Admin')) {
            $treatmentPlants = TreatmentPlant::where('id', Auth::user()->treatment_plant_id)->orderBy('id')->pluck('id', 'name');
        } else {
            $treatmentPlants = TreatmentPlant::orderBy('id')->pluck('id', 'name');
        }
        $servprov = ServiceProvider::orderBy('company_name', 'asc')->pluck('company_name', 'id')->all();
        return view('fsm.sludge-collection.index', compact('page_title', 'treatmentPlants', 'servprov'));
    }

    /**
     * Data for DataTables list.
     */
    public function getData(Request $request)
    {
        if (Auth::user()->hasRole('Treatment Plant - Admin')) {
            $sludgeCollection = SludgeCollectionLog::join('fsm.applications', function ($join) {
                $join->on('fsm.sludge_collections_log.application_id', '=', 'fsm.applications.id')
                    ->whereNull('fsm.applications.deleted_at');
            })
                ->whereNull('fsm.sludge_collections_log.deleted_at')
                ->where('fsm.sludge_collections_log.treatment_plant_id', Auth::user()->treatment_plant_id)
                ->select('fsm.sludge_collections_log.*');
        } else if (Auth::user()->hasRole('Service Provider - Admin')) {
            $sludgeCollection = SludgeCollectionLog::join('fsm.applications', function ($join) {
                $join->on('fsm.sludge_collections_log.application_id', '=', 'fsm.applications.id')
                    ->whereNull('fsm.applications.deleted_at');
            })
                ->whereNull('fsm.sludge_collections_log.deleted_at')
                ->where('fsm.sludge_collections_log.service_provider_id', Auth::user()->service_provider_id)
                ->select('fsm.sludge_collections_log.*');
        } else {
            $sludgeCollection = SludgeCollectionLog::join('fsm.applications', function ($join) {
                $join->on('fsm.sludge_collections_log.application_id', '=', 'fsm.applications.id')
                    ->whereNull('fsm.applications.deleted_at');
            })
                ->whereNull('fsm.sludge_collections_log.deleted_at')
                ->select('fsm.sludge_collections_log.*');
        }

        return Datatables::of($sludgeCollection)
            ->filter(function ($query) use ($request) {

                if ($request->treatment_plant_id) {
                    $query->where('fsm.sludge_collections_log.treatment_plant_id', $request->treatment_plant_id);
                }
                if ($request->date_from && $request->date_to) {
                    $query->whereBetween('fsm.sludge_collections_log.date', [$request->date_from, $request->date_to]);
                }
                if ($request->application_id) {
                    $query->where('fsm.sludge_collections_log.application_id', $request->application_id);
                }
                if ($request->servprov) {
                    $query->where('fsm.sludge_collections_log.service_provider_id', $request->servprov);
                }
            })
            ->addColumn('action', function ($model) {
                $content = \Form::open(['method' => 'DELETE', 'route' => ['sludge-collection.destroy', $model->id]]);

                if (Auth::user()->can('View Sludge Collection')) {
                    $content .= '<a title="' . __("Detail") . '" href="' . action("Fsm\SludgeCollectionController@show", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                }

                if (Auth::user()->can('View Sludge Collection History')) {
                    $content .= '<a title="' . __("History") . '" href="' . action("Fsm\SludgeCollectionController@history", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-history"></i></a> ';
                }

                if (Auth::user()->can('Delete Sludge Collection')) {
                    $content .= '<a title="' . __("Delete") . '"  class="delete btn btn-danger btn-sm mb-1"><i class="fa fa-trash"></i></a> ';
                }

                $content .= \Form::close();
                return $content;
            })
            ->editColumn('application_id', function ($model) {
                return $model->applications->id ?? '-';
            })
            ->editColumn('date', function ($model) {
                return $model->date ?? '-';
            })
            ->editColumn('service_provider_id', function ($model) {
                return $model->applications?->service_provider()->withTrashed()->first()->company_name ?? 'Not Assigned';
            })
            ->editColumn('treatment_plant_id', function ($model) {
                return $model->treatmentplants()->withTrashed()->first()->name ?? '-';
            })
            ->editColumn('desludging_vehicle_id', function ($model) {
                return $model->emptying?->vacutug()->withTrashed()->first()->license_plate_number ?? '-';
            })
            ->editColumn('volume_of_sludge', function ($model) {
                return $model->emptying->volume_of_sludge ?? '-';
            })
            ->make(true);
    }


    /**
     * Show the form for creating log (multi-trip).
     */
    public function createLog(int $application_id)
    {
        $page_title = __("Add Sludge Collection Log");
        $application = Application::findOrFail($application_id) ?? null;
        $emptying = Emptying::where('application_id', $application_id)->latest()->first() ?? null;
        if ($emptying) {
            $treatment_plant_id = $emptying->treatment_plant_id ?? null;
            $service_provider_id = $application->service_provider_id ?? null;
            $vacutug_id = $emptying->desludging_vehicle_id ?? null;
            $volume_of_sludge = $emptying->volume_of_sludge ?? null;
        }
        if (Auth::user()->hasRole('Treatment Plant - Admin')) {
            $treatmentPlants = TreatmentPlant::Operational()->where('id', Auth::user()->treatment_plant_id)->orderBy('id')->pluck('name', 'id');
        } else {
            $treatmentPlants = TreatmentPlant::Operational()->orderBy('id')->pluck('name', 'id');
        }
        $sludgeCollection = null;
        $entry_time = null;
        $exit_time = null;

        $serviceProviders = ServiceProvider::orderBy('id')->pluck('company_name', 'id');
        $applications = Application::where('emptying_status', '=', '1')->where('sludge_collection_status', 0)->orderBy('id', 'asc')->pluck('id', 'id')->all();
        $VacutugTypes = VacutugType::orderBy('id', 'asc')->pluck('capacity', 'id')->all();
        $emptyingDate = Carbon::parse($emptying->emptied_date)->format('Y-m-d');

        return view('fsm.sludge-collection.create', compact('page_title', 'treatmentPlants', 'emptyingDate', 'exit_time', 'entry_time', 'serviceProviders', 'applications', 'VacutugTypes', 'sludgeCollection', 'application_id', 'service_provider_id', 'treatment_plant_id', 'vacutug_id', 'volume_of_sludge'));
    }

    /**
     * Store a newly created resource in storage (web + api).
     */
    public function store(SludgeCollectionRequest $request)
    {
        // Detect API vs Web
        $mode = ($request->is('api/*') || $request->wantsJson() || $request->expectsJson())
            ? 'api'
            : 'web';

        return $this->createSludgeCollection($request, $mode);
    }

    /**
     * Shared logic for creating sludge collection (web/api).
     */
    public function createSludgeCollection($request, string $mode = 'web')
    {
        DB::beginTransaction();

        try {
            // If it's a FormRequest, validate
            if (method_exists($request, 'validated')) {
                $request->validated();
            }

            // -------------------------
            // SludgeCollectionLog
            // -------------------------
            $sludgeCollectionLog = new SludgeCollectionLog();
            $sludgeCollectionLog->application_id        = $request->application_id ?: null;
            $sludgeCollectionLog->volume_of_sludge      = $request->volume_of_sludge ?? null;
            $sludgeCollectionLog->date                  = $request->date ?: null;
            $sludgeCollectionLog->entry_time            = $request->entry_time ?: null;
            $sludgeCollectionLog->exit_time             = $request->exit_time ?: null;
            $sludgeCollectionLog->treatment_plant_id    = $request->treatment_plant_id ?: null;
            $sludgeCollectionLog->service_provider_id   = $request->service_provider_id ?? null;
            $sludgeCollectionLog->desludging_vehicle_id = $request->desludging_vehicle_id ?? null;
            $sludgeCollectionLog->user_id               = Auth::id();
            $sludgeCollectionLog->tipping_fee_amount    = $request->tipping_fee_amount ?: null;
            $sludgeCollectionLog->tipping_fee_receipt_no = $request->tipping_fee_receipt_no ?: null;
            $sludgeCollectionLog->save();

            // -------------------------
            // SludgeCollection aggregate
            // -------------------------
            $appId = $sludgeCollectionLog->application_id;

            $sludgeCollection = SludgeCollection::where('application_id', $appId)->first();

            if ($sludgeCollection) {
                // Update existing
                $sludgeCollection->volume_of_sludge =
                    $sludgeCollection->volume_of_sludge + $request->volume_of_sludge;

                $sludgeCollection->total_time =
                    $sludgeCollection->total_time + $this->sumHms($request->entry_time, $request->exit_time);

                $sludgeCollection->tipping_fee_amount =
                    ($sludgeCollection->tipping_fee_amount ?? 0) + ($request->tipping_fee_amount ?? 0);

                $sludgeCollection->trip_no = $sludgeCollection->trip_no + 1;
                $sludgeCollection->save();
            } else {
                // Create new
                $sludgeCollection = new SludgeCollection();
                
                $sludgeCollection->application_id        = $sludgeCollectionLog->application_id ?: null;
                $sludgeCollection->volume_of_sludge      = $request->volume_of_sludge ?? null;
                $sludgeCollection->date                  = $request->date ?: null;
                $sludgeCollection->entry_time            = $request->entry_time ?: null;
                $sludgeCollection->exit_time             = $request->exit_time ?: null;
                $sludgeCollection->total_time            = $this->sumHms($request->entry_time, $request->exit_time) ?: null;
                $sludgeCollection->treatment_plant_id    = $request->treatment_plant_id ?: null;
                $sludgeCollection->service_provider_id   = $request->service_provider_id ?? null;
                $sludgeCollection->desludging_vehicle_id = $request->desludging_vehicle_id ?? null;
                $sludgeCollection->user_id               = Auth::id();
                $sludgeCollection->trip_no               = ($sludgeCollection->trip_no ?? 0) + 1;
                $sludgeCollection->tipping_fee_amount    = $request->tipping_fee_amount ?: null;
                $sludgeCollection->tipping_fee_receipt_no = $request->tipping_fee_receipt_no ?: null;
                $sludgeCollection->save();
            }

            // -------------------------
            // Application status
            // -------------------------
            $application = Application::where('id', $appId)->first();
            if ($application) {
                $application->sludge_collection_status = true;
                $application->save();
            }

            DB::commit();
            DB::afterCommit(function () use ($application, $sludgeCollection) {
                if (!$application || !$sludgeCollection) {
                    return;
                }

                $etoUserIds = DB::table('fsm.employees')
    ->where('service_provider_id', $sludgeCollection->service_provider_id)
    ->where('status', true)
    ->whereNotNull('user_id')
    ->pluck('user_id')
    ->map(fn($uid) => (string) $uid)
    ->unique()
    ->values()
    ->all();

$serviceProviderAdminIds = User::where('service_provider_id', $sludgeCollection->service_provider_id)
    ->pluck('id')
    ->map(fn($uid) => (string) $uid)
    ->unique()
    ->values()
    ->all();

$helpDeskUserIds = User::whereNotNull('help_desk_id')
    ->pluck('id')
    ->map(fn($uid) => (string) $uid)
    ->unique()
    ->values()
    ->all();

$recipientIds = array_values(array_unique(array_merge(
    $etoUserIds,
    $serviceProviderAdminIds,
    $helpDeskUserIds
)));

$message = sprintf(
    'Application #%s has been safely disposed at FSTP: %s on %s.',
    $application->id,
    $sludgeCollection->treatmentplants->name ?? 'N/A',
    Carbon::now()->format('M d, Y')
);

foreach ($recipientIds as $userId) {
    Notification::create([
        'user_id' => $userId,
        'mode'    => 'web',
        'message' => $message,
        'status'  => false,
    ]);
}

            });


            // -------------------------
            // SUCCESS RESPONSES
            // -------------------------
            if ($mode === 'api') {
                return response()->json([
                    'status'  => true,
                    'message' => 'Sludge collection log created successfully.'
                    
                ], 200);
            }

            return redirect('fsm/application')
                ->with('success', __('Sludge Collection Log created successfully.'));

        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('SludgeCollection create error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            if ($mode === 'api') {
                return response()->json([
                    'status'  => false,
                    'message' =>  __('Failed to create Sludge Collection Log: ') . $e->getMessage(),
                ], 500);
            }

            return redirect('fsm/application')
                ->with('error', __('Failed to create Sludge Collection Log: ') . $e->getMessage());
        }
    }

    /**
     * Difference between two time strings ("H:i" or "H:i:s") in minutes.
     */
    public function sumHms(?string $t1, ?string $t2): ?int
    {
        if (!$t1 || !$t2) {
            return null;
        }

        $entry_time = Carbon::parse($t1);
        $exit_time  = Carbon::parse($t2);

        $diffInMinutes = $exit_time->diffInMinutes($entry_time);
        return $diffInMinutes;
    }

    /**
     * Display the specified resource (log).
     */
    public function show($id)
    {
        $sludgeCollection = SludgeCollectionLog::find($id);

        $applications = $sludgeCollection->applications;
        $service_provider_id = $applications['service_provider_id'];
        $serviceProvider = ServiceProvider::withTrashed()
            ->where('id', $service_provider_id)
            ->first();

        $treatmentPlant = TreatmentPlant::withTrashed()
            ->where('id', $sludgeCollection->treatment_plant_id)
            ->first();

        if ($sludgeCollection) {
            if (Auth::user()->hasRole('Treatment Plant - Admin')) {
                if ($sludgeCollection->treatment_plant_id != Auth::user()->treatment_plant_id) {
                    abort(403);
                }
            }
            $date = Carbon::parse($sludgeCollection->date)->format('m/d/Y');

            $page_title = __("Sludge Collection Details");
            return view('fsm.sludge-collection.show-log', compact('page_title', 'date', 'sludgeCollection', 'serviceProvider', 'treatmentPlant'));
        } else {
            abort(404);
        }
    }

    /**
     * Display the specified aggregate sludge collection resource.
     */
    public function showDetails($id)
    {
        $sludgeCollection = SludgeCollection::find($id);
        $applications = $sludgeCollection->applications;
        $service_provider_id = $applications['service_provider_id'];
        $serviceProvider = ServiceProvider::withTrashed()
            ->where('id', $service_provider_id)
            ->first();

        $treatmentPlant = TreatmentPlant::withTrashed()
            ->where('id', $sludgeCollection->treatment_plant_id)
            ->first();

        if ($sludgeCollection) {
            if (Auth::user()->hasRole('Treatment Plant - Admin')) {
                if ($sludgeCollection->treatment_plant_id != Auth::user()->treatment_plant_id) {
                    abort(403);
                }
            }
            $date = Carbon::parse($sludgeCollection->date)->format('m/d/Y');

            $page_title = __("Sludge Collection Details");
            return view('fsm.sludge-collection.show', compact('page_title', 'date', 'sludgeCollection', 'serviceProvider', 'treatmentPlant'));
        } else {
            abort(404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $sludgeCollection = SludgeCollectionLog::find($id);
        if (!(Auth::user()->hasRole('Super Admin') || Auth::user()->hasRole('Municipality - Super Admin') || Auth::user()->hasRole('Municipality - Sanitation Department'))) {
            if ($sludgeCollection->created_at->diffInDays(today()) > 1) {
                return redirect('fsm/sludge-collection')->with('error', __('Cannot edit Sludge Collection Information 24 hours after creation. Please contact Sanitation Department for support'));
            }
        }
        if ($sludgeCollection) {
            if (Auth::user()->hasRole('Treatment Plant - Admin')) {
                if ($sludgeCollection->user_id != Auth::user()->id || $sludgeCollection->treatment_plant_id != Auth::user()->treatment_plant_id) {
                    abort(403);
                }
            }
            $page_title = "Edit Sludge Collection Details";
            if (Auth::user()->hasRole('Treatment Plant - Admin')) {
                $treatmentPlants = TreatmentPlant::withTrashed()->where('id', Auth::user()->treatment_plant_id)->orderBy('id')->pluck('name', 'id');
            } else {
                $treatmentPlants = TreatmentPlant::withTrashed()->orderBy('id')->pluck('name', 'id');
            }

            $service_provider_id = '';

            $entry_time = Carbon::parse($sludgeCollection->entry_time)->format('H:i');
            $exit_time = Carbon::parse($sludgeCollection->exit_time)->format('H:i');
            $serviceProviders = ServiceProvider::orderBy('id')->pluck('company_name', 'id');
            $applications = Application::where('sludge_collection_status', '1')->where('id', $sludgeCollection->applications->id)->orderBy('id', 'asc')->pluck('id', 'id')->all();
            $VacutugTypes = VacutugType::orderBy('id', 'asc')->pluck('capacity', 'id')->all();
            $treatment_plant_id = $sludgeCollection->treatment_plant_id ?? null;
            $volume_of_sludge = $sludgeCollection->volume_of_sludge ?? null;
            return view('fsm.sludge-collection.edit', compact('page_title', 'sludgeCollection', 'treatmentPlants', 'serviceProviders', 'applications', 'VacutugTypes', 'treatment_plant_id', 'volume_of_sludge', 'entry_time', 'service_provider_id', 'exit_time'));
        } else {
            abort(404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SludgeCollectionRequest $request, $id)
    {
        $sludgeCollectionLog = SludgeCollectionLog::find($id);
        $prev_total_time = $this->sumHms($sludgeCollectionLog->entry_time, $sludgeCollectionLog->exit_time);
        $prev_tipping_fee_amount = $sludgeCollectionLog->prev_tipping_fee_amount;

        if (Auth::user()->hasRole('Treatment Plant - Admin')) {
            if ($sludgeCollectionLog->user_id != Auth::user()->id || $sludgeCollectionLog->treatment_plant_id != Auth::user()->treatment_plant_id) {
                abort(403);
            }
        }
        $sludgeCollectionLog->date = $request->date ? $request->date : null;

        $sludgeCollectionLog->entry_time = $request->entry_time ? $request->entry_time : null;
        $sludgeCollectionLog->exit_time = $request->exit_time ? $request->exit_time : null;

        if (Auth::user()->hasRole('Treatment Plant - Admin')) {
            $sludgeCollectionLog->treatment_plant_id = Auth::user()->treatment_plant_id;
        } else {
            $sludgeCollectionLog->treatment_plant_id = $request->treatment_plant_id ? $request->treatment_plant_id : null;
        }
        $sludgeCollectionLog->service_provider_id = $request->service_provider_id ? $request->service_provider_id : null;
        $sludgeCollectionLog->user_id = Auth::user()->id;
        $sludgeCollectionLog->tipping_fee_amount = $request->tipping_fee_amount ? $request->tipping_fee_amount : null;
        $sludgeCollectionLog->tipping_fee_receipt_no = $request->tipping_fee_receipt_no ? $request->tipping_fee_receipt_no : null;

        $sludgeCollectionLog->save();

        $appId = $sludgeCollectionLog->application_id;

        $sludgeCollection = SludgeCollection::where('application_id', $appId)->first();
        if ($sludgeCollection) {
            if (Auth::user()->hasRole('Treatment Plant - Admin')) {
                if ($sludgeCollection->user_id != Auth::user()->id || $sludgeCollection->treatment_plant_id != Auth::user()->treatment_plant_id) {
                    abort(403);
                }
            }

            $sludgeCollection->date = $request->date ? $request->date : null;
            $sludgeCollection->entry_time = $request->entry_time ? $request->entry_time : null;
            $sludgeCollection->exit_time = $request->exit_time ? $request->exit_time : null;
            $sludgeCollection->total_time = ($sludgeCollection->total_time - $prev_total_time) + $this->sumHms($request->entry_time, $request->exit_time) ?: null;

            if (Auth::user()->hasRole('Treatment Plant - Admin')) {
                $sludgeCollection->treatment_plant_id = Auth::user()->treatment_plant_id;
            } else {
                $sludgeCollection->treatment_plant_id = $request->treatment_plant_id ? $request->treatment_plant_id : null;
            }
            $sludgeCollection->service_provider_id = $request->service_provider_id ? $request->service_provider_id : null;
            $sludgeCollection->user_id = Auth::user()->id;
            $sludgeCollection->tipping_fee_amount = $request->tipping_fee_amount ? $request->tipping_fee_amount : null;
            $sludgeCollection->tipping_fee_receipt_no = $request->tipping_fee_receipt_no ? $request->tipping_fee_receipt_no : null;
            $sludgeCollection->save();
        }

        return redirect('fsm/application')->with('success', __('Sludge Collection Log updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $sludgeCollectionLog = SludgeCollectionLog::find($id);

        if (!$sludgeCollectionLog) {
            return redirect('fsm/sludge-collection')->with('error', __('Failed to delete Sludge Collection.'));
        }

        // Fetch latest sludge collection log for this application
        $latestSludgeCollection = SludgeCollectionLog::where('application_id', $sludgeCollectionLog->application_id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Fetch related emptying info
        $emptying = Emptying::where('application_id', $sludgeCollectionLog->application_id)->first();

        // Fetch sludge collection record
        $sludgeCollection = SludgeCollection::where('application_id', $sludgeCollectionLog->application_id)->first();

        // Restrict TP Admins from deleting logs from other plants
        if (Auth::user()->hasRole('Treatment Plant - Admin')) {
            if ($sludgeCollectionLog->treatment_plant_id != Auth::user()->treatment_plant_id) {
                return redirect('fsm/sludge-collection')->with('error', __('Cannot delete Sludge Collection not created by your Treatment Plant.'));
            }
        }

        // Restrict deletion after 24 hours for certain roles
        if (!(Auth::user()->hasRole('Super Admin') || Auth::user()->hasRole('Municipality - Super Admin') || Auth::user()->hasRole('Municipality - Sanitation Department'))) {
            if ($sludgeCollectionLog->created_at->diffInDays(today()) > 1) {
                return redirect('fsm/sludge-collection')->with('error', __('Cannot delete Sludge Collection Information 24 hours after creation. Please contact the Sanitation Department for support.'));
            }
        }

        if ($latestSludgeCollection && $latestSludgeCollection->id == $sludgeCollectionLog->id) {

            if ($sludgeCollection && $emptying && $sludgeCollection->trip_no == $emptying->trip_no) {

                $application = Application::findOrFail($sludgeCollectionLog->application_id);
                $application->sludge_collection_status = 1;
                $application->save();

                $sludgeCollection->trip_no -= 1;
                $sludgeCollection->tipping_fee_amount -= $sludgeCollectionLog->tipping_fee_amount;

                if (method_exists($this, 'sumHms')) {
                    $sludgeCollection->total_time -= $this->sumHms($sludgeCollectionLog->entry_time, $sludgeCollectionLog->exit_time);
                }

                $sludgeCollection->save();

                $sludgeCollectionLog->delete();

                return redirect('fsm/sludge-collection')->with('success', __('Sludge Collection deleted successfully.'));
            } else {
                return redirect('fsm/sludge-collection')->with('error', __('The Trip Number does not match the latest Emptying record.'));
            }
        } else {
            return redirect('fsm/sludge-collection')->with('error', __('Only the latest Sludge Collection Log of the same Application can be deleted.'));
        }
        
    }

    /**
     * Display history of the specified resource.
     */
    public function history($id)
    {
        $sludgeCollection = SludgeCollectionLog::find($id);
        if ($sludgeCollection) {
            $page_title = __("Sludge Collection History");
            return view('fsm.sludge-collection.history', compact('page_title', 'sludgeCollection'));
        } else {
            abort(404);
        }
    }

    /**
     * Export sludge collections to CSV.
     */
    public function export()
    {
        $searchData = isset($_GET['searchData']) ? $_GET['searchData'] : null;

        $application_id = $_GET['application_id'] ?? null;
        $date_from = $_GET['date_from'] ?? null;
        $date_to = $_GET['date_to'] ?? null;
        $treatment_plant_id = $_GET['treatment_plant_id'] ?? null;
        $servprov = $_GET['servprov'] ?? null;

        $columns = [
            __('ID'),
            __('Application ID'),
            __('Treatment Plant Name'),
            __('Sludge Volume (m³)'),
            __('Date'),
            __('Entry Time'),
            __('Exit Time'),
             __('Tipping Fee Receipt No'),
            __('Tipping Fee Amount'),
            __('Desludging Vehicle Number Plate'),
            __('Service Provider Name'),
        ];

        $query = DB::table('fsm.sludge_collections_log AS sc')
            ->leftJoin('fsm.treatment_plants AS t', 't.id', '=', 'sc.treatment_plant_id')
            ->leftJoin('fsm.service_providers AS s', 's.id', '=', 'sc.service_provider_id')
            ->leftJoin('fsm.applications AS a', 'a.id', '=', 'sc.application_id')
            ->leftJoin('fsm.desludging_vehicles AS dv', 'dv.id', '=', 'sc.desludging_vehicle_id')
            ->select(
                'sc.id',
                'sc.application_id',
                'sc.treatment_plant_id',
                'sc.service_provider_id',
                's.company_name',
                't.name AS treatment_plant_name',
                'sc.volume_of_sludge',
                'sc.date',
                'sc.entry_time',
                'sc.exit_time',
                'sc.tipping_fee_receipt_no',
                'sc.tipping_fee_amount',
                'dv.license_plate_number AS desludging_vehicle_license_plate'
            )
            ->orderBy('sc.id')
            ->whereNull('sc.deleted_at');

        if (!empty($application_id)) {
            $query->where('sc.application_id', $application_id);
        }

        if (!empty($date_from) && !empty($date_to)) {
            $query->whereBetween('sc.date', [$date_from, $date_to]);
        }

        if (!empty($treatment_plant_id)) {
            $query->where('sc.treatment_plant_id', $treatment_plant_id);
        }

        if (!empty($servprov)) {
            $query->where('sc.service_provider_id', $servprov);
        }

        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();

        $writer = WriterFactory::create(Type::CSV);
        $writer->openToBrowser('Sludge Collections.csv')
            ->addRowWithStyle($columns, $style); //Top row of excel

        $query->chunk(5000, function ($sludgeCollections) use ($writer) {

            foreach ($sludgeCollections as $sludgeCollection) {
                $values = [];
                $values[] = $sludgeCollection->id ?? "-";
                $values[] = $sludgeCollection->application_id ?? "-";
                $values[] = $sludgeCollection->treatment_plant_name ?? "-";
                $values[] = $sludgeCollection->volume_of_sludge ?? "-";
                $values[] = $sludgeCollection->date ?? "-";
                $values[] = $sludgeCollection->entry_time ?? "-";
                $values[] = $sludgeCollection->exit_time ?? "-";
                $values[] = $sludgeCollection->tipping_fee_receipt_no ?? "-";
                $values[] = $sludgeCollection->tipping_fee_amount ?? "-";
                $values[] = $sludgeCollection->desludging_vehicle_license_plate ?? "-";
                $values[] = $sludgeCollection->company_name ?? "-";
                $writer->addRow($values);
            }
        });

        $writer->close();
    }
}
