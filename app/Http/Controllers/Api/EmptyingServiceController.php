<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fsm\EmptyingApiRequest;
use App\Http\Requests\Fsm\EmptyingRequest;
use App\Models\Fsm\Application;
use App\Models\Fsm\Containment;
use App\Models\Fsm\EmployeeInfo;
use App\Models\Fsm\Emptying;
use App\Models\Fsm\TreatmentPlant;
use App\Models\Fsm\VacutugType;
use App\Services\Fsm\EmptyingService;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use stdClass;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Emptying Service",
 *     description="APIs used by emptying operators to list applications, load dropdown data and submit emptying records."
 * )
 *
 * @OA\Schema(
 *   schema="EmptyingApplication",
 *   description="Application prepared for emptying",
 *   @OA\Property(property="id", type="integer", example=101),
 *   @OA\Property(property="bin", type="string", example="BRN-001-000123"),
 *   @OA\Property(property="building_house_number", type="string", example="123/4"),
 *   @OA\Property(property="carrying_width", type="number", format="float", example=3.5),
 *   @OA\Property(property="containment_size", type="number", format="float", example=4.0),
 *   @OA\Property(property="emptying_status", type="integer", example=0),
 *   @OA\Property(
 *      property="geometry",
 *      type="object",
 *      nullable=true,
 *      example={"type": "Point", "coordinates": {85.333, 27.683}}
 *   ),
 *   @OA\Property(property="image_status", type="string", example="true")
 * )
 */
class EmptyingServiceController extends Controller
{
    protected $emptyingService;

    public function __construct(EmptyingService $emptyingService)
    {
        $this->emptyingService = $emptyingService;
    }

    /**
     * @OA\Get(
     *     path="/api/assessed-applications",
     *     summary="List assessed applications for emptying",
     *     description="Returns applications that are ready to be emptied or have a proposed emptying date. For emptying operators, results are filtered to their assigned service provider.",
     *     tags={"Emptying Service"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of assessed applications",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="applications",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/EmptyingApplication")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Applications retrieved successfully."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
  public function getAssessedApplications(Request $request)
{
    try {
        $user = Auth::user();

        // pagination size (default 50, max 200)
        $perPage = (int) $request->get('per_page', 50);
        $perPage = $perPage > 200 ? 200 : $perPage;

        // --- Subquery: latest emptying trip_no per application
        $emptyingLatest = DB::table('fsm.emptyings')
            ->select('application_id', DB::raw('MAX(trip_no) as emptying_trip_no'))
            ->groupBy('application_id');

        // --- Subquery: latest sludge trip_no per application
        $sludgeLatest = DB::table('fsm.sludge_collections')
            ->select('application_id', DB::raw('MAX(trip_no) as sludge_trip_no'))
            ->groupBy('application_id');

        $query = Application::query()
            ->select(
                'applications.*',
                'buildings.house_number as building_house_number',
                'roads.carrying_width',
                'containments.size as containment_size',
                DB::raw('public.ST_AsGeoJSON(buildings.geom) AS geometry_json'),

                // latest trip numbers (0 if none)
                DB::raw('COALESCE(e.emptying_trip_no, 0) AS emptying_trip_no'),
                DB::raw('COALESCE(s.sludge_trip_no, 0) AS sludge_trip_no'),

                // Helpful flag for mobile. Status columns are PostgreSQL integers.
                DB::raw("
                    CASE
                        WHEN applications.proposed_emptying_date IS NOT NULL
                             AND applications.emptying_status = 0
                        THEN 'first'
                        WHEN applications.emptying_status = 1
                             AND applications.sludge_collection_status = 1
                             AND COALESCE(e.emptying_trip_no,0) = COALESCE(s.sludge_trip_no,0)
                             AND COALESCE(e.emptying_trip_no,0) < applications.trip_count
                        THEN 'next'
                        ELSE NULL
                    END AS emptying_add_mode
                ")
            )
            ->leftJoin('building_info.buildings as buildings', function ($join) {
                $join->on(DB::raw('CAST(applications.bin AS VARCHAR)'), '=', 'buildings.bin');
            })
            ->leftJoin('utility_info.roads as roads', 'applications.road_code', '=', 'roads.code')
            ->leftJoin('fsm.containments as containments', 'applications.containment_id', '=', 'containments.id')
            ->leftJoinSub($emptyingLatest, 'e', function ($join) {
                $join->on('e.application_id', '=', 'applications.id');
            })
            ->leftJoinSub($sludgeLatest, 's', function ($join) {
                $join->on('s.application_id', '=', 'applications.id');
            })
            ->whereNotNull('applications.proposed_emptying_date')
            // Only return applications where emptying can be added now.
            ->where(function ($q) {
                // (1) First emptying allowed.
                $q->where('applications.emptying_status', 0);

                // (2) Next emptying allowed.
                $q->orWhere(function ($q2) {
                    $q2->where('applications.emptying_status', 1)
                        ->where('applications.sludge_collection_status', 1)
                        ->whereRaw('COALESCE(e.emptying_trip_no,0) = COALESCE(s.sludge_trip_no,0)')
                        ->whereRaw('COALESCE(e.emptying_trip_no,0) < applications.trip_count');
                });
            });

        // Filter by SP for emptying operator
        if ($user->hasRole('Service Provider - Emptying Operator')) {
            $query->where('applications.service_provider_id', $user->service_provider_id);
        }

        // (Optional) filters
        if ($request->application_id) {
            $query->where('applications.id', $request->application_id);
        }
        if ($request->ward) {
            $query->where('applications.ward', $request->ward);
        }
        if ($request->road_code) {
            $query->where('applications.road_code', $request->road_code);
        }

        // Server-side search across application, location, and applicant fields
        if ($request->filled('search')) {
            $term = '%' . trim($request->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('CAST(applications.id AS VARCHAR) ILIKE ?', [$term])
                  ->orWhereRaw('CAST(applications.bin AS VARCHAR) ILIKE ?', [$term])
                  ->orWhere('applications.applicant_name', 'ILIKE', $term)
                  ->orWhere('applications.applicant_contact', 'ILIKE', $term)
                  ->orWhere('applications.customer_name', 'ILIKE', $term)
                  ->orWhere('applications.customer_contact', 'ILIKE', $term)
                  ->orWhere('applications.road_code', 'ILIKE', $term)
                  ->orWhere('buildings.house_number', 'ILIKE', $term);
            });
        }

        // Filter by is_anf (accepts "true"/"false"/1/0; omitted returns all)
        if ($request->filled('is_anf')) {
            if ($request->boolean('is_anf')) {
                $query->where('applications.is_anf', true);
            } else {
                $query->where(function ($q) {
                    $q->where('applications.is_anf', false)
                      ->orWhereNull('applications.is_anf');
                });
            }
        }

        // Cast incoming status filters to integers to match the database columns.
        if (!is_null($request->emptying_status) && $request->emptying_status !== '') {
            $query->where(
                'applications.emptying_status',
                (int) $request->emptying_status
            );
        }
        if (!is_null($request->sludge_collection_status) && $request->sludge_collection_status !== '') {
            $query->where(
                'applications.sludge_collection_status',
                (int) $request->sludge_collection_status
            );
        }

        if ($request->date_from && $request->date_to && $request->date_from <= $request->date_to) {
            $query->whereDate('applications.application_date', '>=', $request->date_from);
            $query->whereDate('applications.application_date', '<=', $request->date_to);
        }

        $applications = $query->paginate($perPage);

        $imageFolder = storage_path('app/public/emptyings/houses');

        $applications->getCollection()->transform(function ($application) use ($imageFolder) {
            $application->geometry = $application->geometry_json
                ? json_decode($application->geometry_json)
                : null;
            unset($application->geometry_json);

            $imageFile = $imageFolder . DIRECTORY_SEPARATOR . $application->bin . '.jpg';
            $application->image_status = file_exists($imageFile) ? "true" : "false";

            $application->can_add_emptying = !empty($application->emptying_add_mode) && !$application->is_anf ? "true" : "false";

            return $application;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'applications' => $applications->items(),
                'pagination'   => [
                    'current_page' => $applications->currentPage(),
                    'last_page'    => $applications->lastPage(),
                    'per_page'     => $applications->perPage(),
                    'total'        => $applications->total(),
                ]
            ],
            'message' => __('Applications retrieved successfully.'),
        ]);
    } catch (\Throwable $th) {
        return response()->json([
            'status' => false,
            'message' => $th->getMessage()
        ], 500);
    }
}


    /**
     * @OA\Get(
     *     path="/api/treatment-plants",
     *     summary="Get operational treatment plants",
     *     tags={"Emptying Service"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of treatment plants"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function getTreatmentPlants()
    {
        try {
            $treatmentplants = TreatmentPlant::Operational()
                ->whereIn('type', [3, 4])
                ->latest()
                ->select('id', 'name')
                ->get();
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'treatment-plants' => $treatmentplants
            ],
            'message' => __('Treatment Plants'),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/vacutugs",
     *     summary="Get vacutugs for logged-in service provider",
     *     tags={"Emptying Service"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of vacutugs"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function getVacutugs()
    {
        try {
            $vacutugs = VacutugType::where(function ($q) {
                $q->where("status", "=", true)
                    ->where("service_provider_id", '=', Auth::user()->service_provider_id);
            })
                ->orderBy('capacity')
                ->select('id', 'license_plate_number', 'width', 'capacity')
                ->get();
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }

        return [
            'success' => true,
            'data' => $vacutugs,
            'message' => __('Vacutugs')
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/drivers",
     *     summary="Get active drivers for logged-in service provider",
     *     tags={"Emptying Service"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of drivers"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function getDrivers()
    {
        try {
            $drivers = EmployeeInfo::Active()->where(function ($q) {
                $q->where('employee_type', '=', 'Driver')
                    ->where("service_provider_id", '=', Auth::user()->service_provider_id);
            })
                ->pluck('name', 'id')
                ->toArray();
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }

        return [
            'success' => true,
            'data' => $drivers,
            'message' => __('Drivers')
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/emptiers",
     *     summary="Get active emptiers for logged-in service provider",
     *     tags={"Emptying Service"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of emptiers"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function getEmptiers()
    {
        try {
            $emptiers = EmployeeInfo::Active()->where(function ($q) {
                $q->where('employee_type', '=', 'Cleaner/Emptier')
                    ->where("service_provider_id", '=', Auth::user()->service_provider_id);
            })
                ->pluck('name', 'id')
                ->toArray();
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }

        return [
            'success' => true,
            'data' => $emptiers,
            'message' => __('Emptiers')
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/emptyingForm-fields/{application_id}",
     *     summary="Get dynamic form fields for emptying (multi-trip support)",
     *     tags={"Emptying Service"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="application_id",
     *         in="path",
     *         required=true,
     *         description="ID of the application",
     *         @OA\Schema(type="integer", example=101)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Form field definition for the next trip"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Application not found"
     *     )
     * )
     */
    public function getMultiTripFormFields(Request $request, $application_id)
    {
        // Helper to convert associative arrays into [{value:x,label:y}]
        $formatOptions = function ($options) {
            return collect($options)
                ->map(function ($label, $value) {
                    return [
                        'value' => $value,
                        'label' => $label,
                    ];
                })
                ->values()
                ->all();
        };

        $application = Application::findOrFail($application_id);
        $containment = Containment::find($application->containment_id);
        $exists      = Emptying::where('application_id', $application->id)->exists();

        /** -------------------------
         *  DROPDOWN OPTION SOURCES
         * --------------------------*/

        // Vacutugs
        $vacutugsRaw = !(Auth::user()->hasRole('Super Admin') && Auth::user()->hasRole('Municipality - Super Admin'))
            ? VacutugType::Operational()
                ->where('service_provider_id', $application->service_provider_id)
                ->pluck('license_plate_number', 'id')->toArray()
            : VacutugType::Operational()->pluck('license_plate_number', 'id')->toArray();

        // Drivers
        $driversRaw = !(Auth::user()->hasRole('Super Admin') && Auth::user()->hasRole('Municipality - Super Admin'))
            ? EmployeeInfo::Active()
                ->where('service_provider_id', $application->service_provider_id)
                ->where('employee_type', 'Driver')
                ->pluck('name', 'id')->toArray()
            : EmployeeInfo::Active()
                ->where('employee_type', 'Driver')
                ->pluck('name', 'id')->toArray();

        // Emptiers
        $emptiersRaw = !(Auth::user()->hasRole('Super Admin') && Auth::user()->hasRole('Municipality - Super Admin'))
            ? EmployeeInfo::Active()
                ->where('service_provider_id', $application->service_provider_id)
                ->where('employee_type', 'Cleaner/Emptier')
                ->pluck('name', 'id')->toArray()
            : EmployeeInfo::Active()
                ->where('employee_type', 'Cleaner/Emptier')
                ->pluck('name', 'id')->toArray();

        // Treatment Plants
        $treatmentPlantsRaw = TreatmentPlant::Operational()
            ->whereIn('type', [3, 4])
            ->pluck('name', 'id')
            ->toArray();

        /** -------------------------
         *  FORMAT INTO [{value,label}]
         * --------------------------*/
        $vacutugs        = $formatOptions($vacutugsRaw);
        $drivers         = $formatOptions($driversRaw);
        $emptiers        = $formatOptions($emptiersRaw);
        $treatmentplants = $formatOptions($treatmentPlantsRaw);

        /** -------------------------
         *  CASE 1: Already Has Trips
         * --------------------------*/
        if ($exists) {
            $emptying = Emptying::where('application_id', $application->id)->latest()->first();
            $tripNo   = $emptying->trip_no + 1;

            $formFields = [
                [
                    'label'       => __('Application ID'),
                    'name'        => 'application_id',
                    'input_type'  => 'hidden',
                    'value'       => $application->id,
                    'prefilled'   => true,
                    'required'    => true,
                    'validation'  => 'required|integer',
                    'placeholder' => __('Application ID'),
                ],
                [
                    'label'       => __('Containment ID'),
                    'name'        => 'containment_id',
                    'input_type'  => 'hidden',
                    'value'       => $application->containment_id,
                    'prefilled'   => true,
                    'placeholder' => __('Containment ID'),
                    // no explicit rule in FormRequest, so no validation string
                ],

                [
                    'label'       => __('Date'),
                    'name'        => 'emptied_date',
                    'input_type'  => 'date',
                    'value'       => $emptying->emptied_date->format('Y-m-d'),
                    'disabled'    => true,
                    'prefilled'   => true,
                    'required'    => true,
                    'placeholder' => __('Date'),
                    // no backend rule for emptied_date
                ],
                [
                    'label'       => __('Service Receiver Name'),
                    'name'        => 'service_receiver_name',
                    'input_type'  => 'text',


                    'value'       => $application->applicant_name,
                    'disabled'    => true,


                    'prefilled'   => true,
                    'required'    => true,
                    'placeholder' => __('Service Receiver Name'),
                ],
            [
                    'label'       => __('Service Receiver Gender'),
                    'name'        => 'service_receiver_gender',
                    'input_type'  => 'select', // should be select if using options


                    'value'       => $application->applicant_gender,
                    'disabled'    => true,


                    'prefilled'   => true,
                    'required'    => true,
                    'options' => [
                        [
                            'value' => 'Male',
                            'label' => 'Male',
                        ],
                        [
                            'value' => 'Female',
                            'label' => 'Female',
                        ],
                        [
                                'value' => 'others',
                                'label' => 'Others',
                            ],
                    ],
                    'placeholder' => __('Service Receiver Gender'),
                ],
                [
                    'label'       => __('Service Receiver Contact Number'),
                    'name'        => 'service_receiver_contact',
                    'input_type'  => 'text',


                    'value'       => $application->applicant_contact,
                    'disabled'    => true,


                    'prefilled'   => true,
                    'required'    => true,
                    'placeholder' => __('Service Receiver Contact Number'),
                ],
                [
                    'label'       => __('Reason for Emptying'),
                    'name'        => 'emptying_reason',
                    'input_type'  => 'text',
                    'value'       => $emptying->emptying_reason,
                    'disabled'    => true,
                    'prefilled'   => true,
                    'required'    => true,
                    'placeholder' => __('Reason for Emptying'),
                    // In rules(): required only in mid-trip branch; here it's prefilled/disabled
                ],
                [
                    'label'       => __('No. of Trips'),
                    'name'        => 'trip_count',
                    'input_type'  => 'number',
                    'value'       => $application->trip_count,
                    'validation'  => 'required|integer|min:1',
                    'placeholder' => __('No. of Trips'),
                ],
                [
                    'label'       => __('Current Trip No.'),
                    'name'        => 'trip_no',
                    'input_type'  => 'text',
                    'value'       => $tripNo,
                    'disabled'    => true,
                    'prefilled'   => true,
                    'required'    => true,
                    'validation'  => 'required|integer|min:1',
                    'placeholder' => __('Current Trip No.'),
                ],
                [
                    'label'       => __('Sludge Volume (m³)'),
                    'name'        => 'volume_of_sludge',
                    'input_type'  => 'number',
                    'required'    => true,
                    'validation'  => 'required|numeric|min:0',
                    'placeholder' => __('Sludge Volume (m³)'),
                ],

                [
                    'label'       => __('Desludging Vehicle Number Plate'),
                    'name'        => 'desludging_vehicle_id',
                    'input_type'  => 'select', 
                    'options'     => $vacutugs,
                    'value'       => $emptying->desludging_vehicle_id,
                    'disabled'    => true,
                    'prefilled'   => true,
                    'required'    => true,
                    'validation'  => 'required|integer',
                    'placeholder' => __('Desludging Vehicle Number Plate'),
                ],

                [
                    'label'       => __('Driver Name'),
                    'name'        => 'driver',
                    'input_type'  => 'select',
                    'options'     => $drivers,
                    'value'    => $emptying->driver,
                    'disabled'    => true,
                    'prefilled'   => true,
                    'required'    => true,
                    'validation'  => 'required|integer',
                    'placeholder' => __('Driver Name'),
                ],

                [
                    'label'       => __('Emptier 1 Name'),
                    'name'        => 'emptier1',
                    'input_type'  => 'select',
                    'options'     => $emptiers,
                    'value'    => $emptying->emptier1,
                    'disabled'    => true,
                    'prefilled'   => true,
                    'required'    => true,
                    'validation'  => 'required|integer',
                    'placeholder' => __('Emptier 1 Name'),
                ],

                [
                    'label'       => __('Emptier 2 Name'),
                    'name'        => 'emptier2',
                    'input_type'  => 'select',
                    'options'     => $emptiers,
                    'value'    => $emptying->emptier2,
                    'disabled'    => true,
                    'prefilled'   => true,
                    'required'    => true,
                    'validation'  => 'nullable|integer',
                    'placeholder' => __('Emptier 2 Name'),
                ],

                [
                    'label'       => __('Start Time'),
                    'name'        => 'start_time',
                    'input_type'  => 'time',
                    'validation'  => 'date_format:H:i',
                    'placeholder' => __('Start Time'),
                    // required on create, nullable on update → keep validation generic
                ],
                [
                    'label'       => __('End Time'),
                    'name'        => 'end_time',
                    'input_type'  => 'time',
                    'validation'  => 'date_format:H:i|after:start_time',
                    'placeholder' => __('End Time'),
                ],

                [
                    'label'       => __('Disposal Place'),
                    'name'        => 'treatment_plant_id',
                    'input_type'  => 'select',
                    'options'     => $treatmentplants,
                    'value'    => $emptying->treatment_plant_id,
                    'disabled'    => true,
                    'prefilled'   => true,
                    'required'    => true,
                    'validation'  => 'required|integer',
                    'placeholder' => __('Disposal Place'),
                ],

                [
                    'label'       => __('Comments (if any)'),
                    'name'        => 'comments',
                    'input_type'  => 'text',
                    'multiline'   => true,
                    'value'       => $emptying->comments,
                    'placeholder' => __('Comments (if any)'),
                ],

                [
                    'label'       => __('Receipt Number'),
                    'name'        => 'receipt_number',
                    'input_type'  => 'text',
                    'required'    => true,
                    'validation'  => 'required',
                    'placeholder' => __('Receipt Number'),
                    'show_when'   => [
                        'field'       => 'trip_no',
                        'operator'    => '=',
                        'value_field' => 'trip_count'
                    ]
                ],
                [
                    'label'       => __('Total Cost'),
                    'name'        => 'total_cost',
                    'input_type'  => 'number',
                    'required'    => true,
                    'validation'  => 'required|numeric|min:0',
                    'placeholder' => __('Total Cost'),
                    'show_when'   => [
                        'field'       => 'trip_no',
                        'operator'    => '=',
                        'value_field' => 'trip_count'
                    ]
                ],
                [
                    'label'       => __('House Image'),
                    'name'        => 'house_image',
                    'input_type'  => 'file_upload',
                    'required'    => true,
                    // in rules: required on create, nullable on update → only encode file constraints here
                    'validation'  => 'file|mimes:jpeg,jpg,png|max:5120',
                    'placeholder' => __('House Image'),
                    'show_when'   => [
                        'field'       => 'trip_no',
                        'operator'    => '=',
                        'value_field' => 'trip_count'
                    ]
                ],
                [
                    'label'       => __('Receipt Image'),
                    'name'        => 'receipt_image',
                    'input_type'  => 'file_upload',
                    'required'    => true,
                    'validation'  => 'file|mimes:jpeg,jpg,png|max:5120',
                    'placeholder' => __('Receipt Image'),
                    'show_when'   => [
                        'field'       => 'trip_no',
                        'operator'    => '=',
                        'value_field' => 'trip_count'
                    ]
                ],
            ];

        } else {

            /** -------------------------
             *  CASE 2: FIRST TRIP
             * --------------------------*/
            $formFields = [
                [
                    'label'       => __('Application ID'),
                    'name'        => 'application_id',
                    'input_type'  => 'hidden',
                    'value'       => $application->id,
                    'prefilled'   => true,
                    'required'    => true,
                    'validation'  => 'required|integer',
                    'placeholder' => __('Application ID'),
                ],
                [
                    'label'       => __('Containment ID'),
                    'name'        => 'containment_id',
                    'input_type'  => 'hidden',
                    'value'       => $application->containment_id,
                    'prefilled'   => true,
                    'placeholder' => __('Containment ID'),
                ],
                [
                    'label'       => __('Date'),
                    'name'        => 'emptied_date',
                    'input_type'  => 'date',
                    'value'       => now()->format('Y-m-d'),
                    'disabled'    => true,
                    'prefilled'   => true,
                    'required'    => true,
                    'placeholder' => __('Date'),
                ],
                [
                    'label'       => __('Service Receiver Name'),
                    'name'        => 'service_receiver_name',
                    'input_type'  => 'text',
                    'value'       => $application->applicant_name,
                    'disabled'    => false,
                    'prefilled'   => true,
                    'required'    => true,
                    'placeholder' => __('Service Receiver Name'),
                ],
            [
                    'label'       => __('Service Receiver Gender'),
                    'name'        => 'service_receiver_gender',
                    'input_type'  => 'select', // should be select if using options
                    'value'       => $application->applicant_gender,
                    'disabled'    => false,
                    'prefilled'   => true,
                    'required'    => true,
                    'options' => [
                        [
                            'value' => 'Male',
                            'label' => 'Male',
                        ],
                        [
                            'value' => 'Female',
                            'label' => 'Female',
                        ],
                        [
                            'value' => 'others',
                            'label' => 'Others',
                        ],
                    ],
                    'placeholder' => __('Service Receiver Gender'),
                ],
                [
                    'label'       => __('Service Receiver Contact Number'),
                    'name'        => 'service_receiver_contact',
                    'input_type'  => 'text',
                    'value'       => $application->applicant_contact,
                    'disabled'    => false,
                    'prefilled'   => true,
                    'required'    => true,
                    'validation'  => 'required|integer',
                    'placeholder' => __('Service Receiver Contact Number'),
                ],
                [
                    'label'       => __('Reason for Emptying'),
                    'name'        => 'emptying_reason',
                    'input_type'  => 'text',
                    'required'    => true,
                    'validation'  => 'required',
                    'placeholder' => __('Reason for Emptying'),
                ],
                [
                    'label'       => __('No. of Trips'),
                    'name'        => 'trip_count',
                    'input_type'  => 'number',
                    'required'    => true,
                    'validation'  => 'required|integer|min:1',
                    'placeholder' => __('No. of Trips'),
                ],
                [
                    'label'       => __('Current Trip No.'),
                    'name'        => 'trip_no',
                    'input_type'  => 'text',
                    'value'       => 1,
                    'disabled'    => true,
                    'prefilled'   => true,
                    'required'    => true,
                    'validation'  => 'required|integer|min:1',
                    'placeholder' => __('Current Trip No.'),
                ],
                [
                    'label'       => __('Sludge Volume (m³)'),
                    'name'        => 'volume_of_sludge',
                    'input_type'  => 'number',
                    'required'    => true,
                    'validation'  => 'required|numeric|min:0',
                    'placeholder' => __('Sludge Volume (m³)'),
                ],

                [
                    'label'       => __('Desludging Vehicle Number Plate'),
                    'name'        => 'desludging_vehicle_id',
                    'input_type'  => 'select',
                    'options'     => $vacutugs,
                    'required'    => true,
                    'validation'  => 'required|integer',
                    'placeholder' => __('Desludging Vehicle Number Plate'),
                ],

                [
                    'label'       => __('Driver Name'),
                    'name'        => 'driver',
                    'input_type'  => 'select',
                    'options'     => $drivers,
                    'required'    => true,
                    'validation'  => 'required|integer',
                    'placeholder' => __('Driver Name'),
                ],

                [
                    'label'       => __('Emptier 1 Name'),
                    'name'        => 'emptier1',
                    'input_type'  => 'select',
                    'options'     => $emptiers,
                    'required'    => true,
                    'validation'  => 'required|integer',
                    'placeholder' => __('Emptier 1 Name'),
                ],

                [
                    'label'       => __('Emptier 2 Name'),
                    'name'        => 'emptier2',
                    'input_type'  => 'select',
                    'options'     => $emptiers,
                    'validation'  => 'nullable|integer',
                    'placeholder' => __('Emptier 2 Name'),
                ],

                [
                    'label'       => __('Start Time'),
                    'name'        => 'start_time',
                    'input_type'  => 'time',
                    'required'    => true,
                    'validation'  => 'date_format:H:i',
                    'placeholder' => __('Start Time'),
                ],
                [
                    'label'       => __('End Time'),
                    'name'        => 'end_time',
                    'input_type'  => 'time',
                    'required'    => true,
                    'validation'  => 'date_format:H:i|after:start_time',
                    'placeholder' => __('End Time'),
                ],

                [
                    'label'       => __('Disposal Place'),
                    'name'        => 'treatment_plant_id',
                    'input_type'  => 'select',
                    'options'     => $treatmentplants,
                    'required'    => true,
                    'validation'  => 'required|integer',
                    'placeholder' => __('Disposal Place'),
                ],

                [
                    'label'       => __('Receipt Number'),
                    'name'        => 'receipt_number',
                    'input_type'  => 'text',
                    'required'    => true,
                    'validation'  => 'required',
                    'placeholder' => __('Receipt Number'),
                    'show_when'   => [
                        'field'       => 'trip_no',
                        'operator'    => '=',
                        'value_field' => 'trip_count'
                    ]
                ],
                [
                    'label'       => __('Total Cost'),
                    'name'        => 'total_cost',
                    'input_type'  => 'number',
                    'required'    => true,
                    'validation'  => 'required|numeric|min:0',
                    'placeholder' => __('Total Cost'),
                    'show_when'   => [
                        'field'       => 'trip_no',
                        'operator'    => '=',
                        'value_field' => 'trip_count'
                    ]
                ],
                [
                    'label'       => __('House Image'),
                    'name'        => 'house_image',
                    'input_type'  => 'file_upload',
                    'required'    => true,
                    'validation'  => 'file|mimes:jpeg,jpg,png|max:5120',
                    'placeholder' => __('House Image'),
                    'show_when'   => [
                        'field'       => 'trip_no',
                        'operator'    => '=',
                        'value_field' => 'trip_count'
                    ]
                ],
                [
                    'label'       => __('Receipt Image'),
                    'name'        => 'receipt_image',
                    'input_type'  => 'file_upload',
                    'required'    => true,
                    'validation'  => 'file|mimes:jpeg,jpg,png|max:5120',
                    'placeholder' => __('Receipt Image'),
                    'show_when'   => [
                        'field'       => 'trip_no',
                        'operator'    => '=',
                        'value_field' => 'trip_count'
                    ]
                ],

                [
                    'label'       => __('Comments (if any)'),
                    'name'        => 'comments',
                    'input_type'  => 'text',
                    'multiline'   => true,
                    'placeholder' => __('Comments (if any)'),
                ],
            ];
        }

        return response()->json([
            'application_id' => $application->id,
            'form_fields'    => $formFields,
        ]);
    }



    /**
 * @OA\Post(
 *     path="/api/save-emptying",
 *     summary="Store emptying service record",
 *     description="Create an emptying record, update containment and application status.",
 *     tags={"Emptying Service"},
 *     security={{"bearerAuth":{}}},

 *     @OA\RequestBody(
 *         required=true,
 *         description="Emptying payload with optional house image and required receipt image.",
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 required={
 *                    "application_id","containment_id","emptying_reason",
 *                    "trip_count","trip_no","volume_of_sludge",
 *                    "desludging_vehicle_id","driver","emptier1",
 *                    "start_time","end_time","treatment_plant_id",
 *                    "receipt_number","total_cost","receipt_image"
 *                 },
 *
 *                 @OA\Property(
 *                     property="application_id",
 *                     type="integer",
 *                     example=101,
 *                     description="ID of the application"
 *                 ),
 *                 @OA\Property(
 *                     property="containment_id",
 *                     type="integer",
 *                     example=55,
 *                     description="ID of the containment"
 *                 ),
 *                  @OA\Property(
 *                     property="emptied_date",
 *                     type="date",
 *                     example="2025-11-24"
 *                 ),
 *                 @OA\Property(
 *                     property="emptying_reason",
 *                     type="string",
 *                     example="Overflowing containment"
 *                 ),
 *                 @OA\Property(
 *                     property="trip_count",
 *                     type="integer",
 *                     example=3
 *                 ),
 *                 @OA\Property(
 *                     property="trip_no",
 *                     type="integer",
 *                     example=1,
 *                     description="Current trip number"
 *                 ),
 *                 @OA\Property(
 *                     property="volume_of_sludge",
 *                     type="number",
 *                     format="float",
 *                     example=2.5
 *                 ),
 *                 @OA\Property(
 *                     property="desludging_vehicle_id",
 *                     type="integer",
 *                     example=4
 *                 ),
 *                 @OA\Property(
 *                     property="driver",
 *                     type="integer",
 *                     example=12,
 *                     description="Driver employee ID"
 *                 ),
 *                 @OA\Property(
 *                     property="emptier1",
 *                     type="integer",
 *                     example=33,
 *                     description="Primary emptier employee ID"
 *                 ),
 *                 @OA\Property(
 *                     property="emptier2",
 *                     type="integer",
 *                     example=34,
 *                     description="Secondary emptier employee ID (optional)"
 *                 ),
 *                 @OA\Property(
 *                     property="start_time",
 *                     type="time without time zone",
 *                     example="10:30"
 *                 ),
 *                 @OA\Property(
 *                     property="end_time",
 *                     type="time without time zone",
 *                     example="11:15"
 *                 ),
 *                 @OA\Property(
 *                     property="treatment_plant_id",
 *                     type="integer",
 *                     example=2
 *                 ),
 *                 @OA\Property(
 *                     property="comments",
 *                     type="string",
 *                     nullable=true,
 *                     example="No issues found"
 *                 ),
 *                 @OA\Property(
 *                     property="receipt_number",
 *                     type="string",
 *                     example="RCPT-1234"
 *                 ),
 *                 @OA\Property(
 *                     property="total_cost",
 *                     type="number",
 *                     format="float",
 *                     example=1500
 *                 ),
 *
 *                 @OA\Property(
 *                     property="house_image",
 *                     type="string",
 *                     format="binary",
 *                     nullable=true,
 *                     description="Optional image of the house (JPG/PNG)"
 *                 ),
 *                 @OA\Property(
 *                     property="receipt_image",
 *                     type="string",
 *                     format="binary",
 *                     description="Required receipt image (JPG/PNG)"
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Emptying saved successfully"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation or business rule error"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error"
 *     )
 * )
 */


    public function store(EmptyingRequest $request)
    {
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', 300);

        return $this->emptyingService->createEmptying($request, 'api');
    }

    public function getTreatmentPlantDetails()
    {
        try {
            $applicationController = app(\App\Http\Controllers\Fsm\ApplicationController::class);
            $response = $applicationController->getTreatmentPlant();
            return $response;
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $th->getMessage()
            ], 500);
        }
    }
}
    