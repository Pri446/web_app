<?php
// Last Modified Date: 11-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Services\Fsm;

use App\Classes\FormField;
use App\Http\Requests\Fsm\ApplicationRequest;
use App\Models\Fsm\HelpDesk;
use App\Models\Fsm\ServiceProvider;
use App\Models\LayerInfo\Ward;
use App\Models\User;
use App\Http\Controllers\Controller;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\BuildingInfo\Building;
use App\Models\Fsm\Application;
use App\Models\Fsm\Containment;
use App\Models\Swm\Route;
use App\Models\UtilityInfo\Roadline;
use Carbon\Carbon;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Auth;
use Venturecraft\Revisionable\Revision;
use Yajra\DataTables\Facades\DataTables;
use DB;
use Datetime;
use PDF;
use App\Models\Notification;
use App\Models\Fsm\ServiceProviderSequence;
use App\Models\Fsm\VacutugType;
use App\Enums\ApplicationStatus;
use App\Enums\FeedbackStatus;
use App\Models\Fsm\Emptying;


class ApplicationService
{

    protected $session;
    protected $instance;
    protected string $indexAction;
    protected $createRoute, $exportRoute;
    protected $createPartialForm, $createFormFields, $createFormAction;
    protected $showFormFields, $editFormFields, $filterFormFields;
    protected $reportRoute;
    /**
     * Constructs a new ApplicationService object.
     *
     *
     */
    public function __construct()
    {

        $this->createPartialForm = 'fsm.application.partial-form';
        $nextSpId = $this->getNextServiceProviderId();
        $nextSpName = $this->getNextServiceProviderName();
        $selectValues = VacutugType::query()
            ->whereNotNull('capacity')->distinct()->orderBy('capacity')
            ->pluck('capacity')
            ->map(fn($c) => 0 + $c)
            ->mapWithKeys(fn($c) => [(string)$c => "$c m³"])
            ->toArray();


        $this->createFormFields = [
            [
                "title" => __('Address'),
                "anfCheckbox" => true,
                "fields" => [
                    new FormField(
                        label: __('Street Name / Street Code'),
                        labelFor: 'road_code',
                        inputType: 'multiple-select',
                        inputId: 'road_code',
                        selectValues: [],
                        required: true
                    ),
                    new FormField(
                        label: __('House Number / BIN'),
                        labelFor: 'bin',
                        inputType: 'multiple-select',
                        inputId: 'bin',
                        selectValues: [],
                        required: true
                    ),
                    new FormField(
                        label: __('Containment ID'),
                        labelFor: 'containment_id',
                        inputType: 'text',
                        inputId: 'containment_id',
                        selectValues: [],
                        placeholder: __('Containment ID')
                    ),
                    new FormField(
                        label: __('Ward Number'),
                        labelFor: 'ward',
                        inputType: 'select',
                        inputId: 'ward',
                        placeholder: __('Ward Number'),
                        selectValues: Ward::orderBy('ward')->pluck('ward', 'ward')->toArray(),
                    ),
                ]
            ],
            [
                "title" => __('Owner Details'),
                "id" => "owner-details-card",
                "fields" => [
                    new FormField(
                        label: __('Owner Name'),
                        labelFor: 'customer_name',
                        inputType: 'text',
                        inputId: 'customer_name',
                        placeholder: __('Owner Name'),
                        required: true
                    ),
                    new FormField(
                        label: __('Owner Gender'),
                        labelFor: 'customer_gender',
                        inputType: 'select',
                        inputId: 'customer_gender',
                        selectValues: ["Male" => "Male", "Female" => "Female", "Others" => "Others"],
                        placeholder: __('Owner Gender'),
                        required: true
                    ),
                    new FormField(
                        label: __('Owner Contact (Phone)'),
                        labelFor: 'customer_contact',
                        inputType: 'text',
                        inputId: 'customer_contact',
                        selectValues: [],
                        placeholder: __('Owner Contact (Phone)'),
                        oninput: "validateOwnerContactInput(this)",
                    )
                ]
            ],
            [
                "title" => __('Applicant Details'),
                "copyDetails" => true,
                "fields" => [
                    new FormField(
                        label: __('Applicant Name'),
                        labelFor: 'applicant_name',
                        inputType: 'text',
                        inputId: 'applicant_name',
                        selectValues: [],
                        required: true,
                        placeholder: __('Applicant Name'),
                    ),
                    new FormField(
                        label: __('Applicant Gender'),
                        labelFor: 'applicant_gender',
                        inputType: 'select',
                        inputId: 'applicant_gender',
                        selectValues: ["Male" => "Male", "Female" => "Female", "Others" => "Others"],
                        required: true,
                        placeholder: __('Applicant Gender'),
                    ),
                    new FormField(
                        label: __('Applicant Contact (Phone)'),
                        labelFor: 'applicant_contact',
                        inputType: 'text',
                        inputId: 'applicant_contact',
                        selectValues: [],
                        required: true,
                        placeholder: __('Applicant Contact (Phone)'),
                        oninput: "validateOwnerContactInput(this)",
                    ),
                ]
            ],

            [
                "title" =>  __('Service Provider Details'),
                "fields" => [
                    new FormField(
                        label: __('Desludging Vehicle Size'),
                        labelFor: 'desludging_vehicle_size',
                        inputType: 'select',
                        inputId: 'desludging_vehicle_size',
                        required: true,
                        placeholder: __('Desludging Vehicle Size'),
                        selectValues: $selectValues,
                    ),
                    new FormField(
                        label: __('Service Provider Name'),
                        labelFor: 'service_provider_name',
                        inputType: 'text',
                        inputId: 'service_provider_name',
                        required: false,
                        placeholder: __('Service Provider Name'),
                        disabled: true
                    ),
                    new FormField(
                        label: __('Service Provider ID'),
                        labelFor: 'service_provider_id',
                        inputType: 'number',
                        inputId: 'service_provider_id',
                        hidden: true,
                        inputValue: $nextSpId,
                        required: true
                    ),
                ]
            ],
        ];
        $this->createFormAction = route('application.store');
        $this->indexAction = route('application.index');
        $this->createRoute = route('application.create');
        $this->exportRoute = route('application.export');
        $this->reportRoute = 'true';
        $this->filterFormFields = [
            [
                new FormField(
                    label: __('BIN'),
                    labelFor: 'bin',
                    labelClass: 'col-md-2 col-form-label ',
                    inputType: 'text',
                    inputId: 'bin',
                    selectValues: [],
                    required: true,
                    placeholder: __('BIN'),
                ),

                new FormField(
                    label: __('House Number'),
                    labelFor: 'house_address',
                    labelClass: 'col-md-2 col-form-label ',
                    inputType: 'text',
                    inputId: 'house_address',
                    placeholder: __('House Number'),
                ),
                new FormField(
                    label: __('Applicant Name'),
                    labelFor: 'applicant_name',
                    labelClass: 'col-md-2 col-form-label ',
                    inputType: 'text',
                    inputId: 'applicant_name',
                    placeholder: __('Applicant Name'),
                ),

            ],
            [
                new FormField(
                    label: __('Application ID'),
                    labelFor: 'application_id',
                    labelClass: 'col-md-2 col-form-label ',
                    inputType: 'text',
                    inputId: 'application_id',
                    placeholder: __('Application ID'),
                ),
                new FormField(
                    label: __('Emptying Status'),
                    labelFor: 'emptying_status',
                    labelClass: 'col-md-2 col-form-label ',
                    inputType: 'select',
                    inputId: 'emptying_status',
                    selectValues: ApplicationStatus::toEnumArray(),
                    placeholder: __('Emptying Status'),
                    autoComplete: "off",
                ),
                new FormField(
                    label: __('Sludge Collection Status'),
                    labelFor: 'sludge_collection_status',
                    labelClass: 'col-md-2 col-form-label ',
                    inputType: 'select',
                    inputId: 'sludge_collection_status',
                    selectValues: ApplicationStatus::toEnumArray(),
                    placeholder: __('Sludge Collection Status'),
                    autoComplete: "off",
                ),

            ],
            [
                new FormField(
                    label: __('Feedback Status'),
                    labelFor: 'feedback_status',
                    labelClass: 'col-md-2 col-form-label ',
                    inputType: 'select',
                    inputId: 'feedback_status',
                    selectValues: FeedbackStatus::toEnumArray(),
                    placeholder: __('Feedback Status'),
                    autoComplete: "off",
                ),
                new FormField(
                    label: __('Street Name / Street Code'),
                    labelFor: 'road_code',
                    labelClass: 'col-md-2 col-form-label ',
                    inputType: 'multiple-select',
                    inputId: 'road_code',
                    selectValues: [],
                    placeholder: __('Street Name / Street Code'),
                ),
                new FormField(
                    label: __('Proposed Emptying Date'),
                    labelFor: 'proposed_emptying_date',
                    labelClass: 'col-md-2 col-form-label ',
                    inputType: 'date',
                    inputId: 'proposed_emptying_date',
                    placeholder: __('Proposed Emptying Date'),
                ),

            ],
            [
                new FormField(
                    label: __('Ward Number'),
                    labelFor: 'ward',
                    labelClass: 'col-md-2 col-form-label ',
                    inputType: 'select',
                    inputId: 'ward',
                    placeholder: __('Ward Number'),
                    selectValues: Ward::orderBy('ward')->pluck('ward', 'ward')->toArray(),
                ),
                new FormField(
                    label: __('Service Provider Name'),
                    labelFor: 'service_provider_id',
                    labelClass: 'col-md-2 col-form-label ',
                    inputType: 'select',
                    inputId: 'service_provider_id',
                    placeholder: __('Service Provider Name'),
                    selectValues: [],
                ),
                new FormField(
                    label: __('Date From'),
                    labelFor: 'date_from',
                    labelClass: 'col-md-2 col-form-label ',
                    inputType: 'date',
                    inputId: 'date_from',
                    placeholder: __('Date From'),
                ),
            ],
            [
                new FormField(
                    label: __('Date To'),
                    labelFor: 'date_to',
                    labelClass: 'col-md-2 col-form-label ',
                    inputType: 'date',
                    inputId: 'date_to',
                    required: true,
                    placeholder: __('Date To'),
                ),
            ],
        ];
    }

    /**
     * Get form fields for creating application.
     *
     * @return array
     */
    public function getCreateFormFields()
    {
        $actionType = session('action_type')
            ?? request('action_type')
            ?? old('action_type');

        if ($actionType === 'confirm') {
            $serviceProviders = ServiceProvider::Operational()->pluck('company_name', 'id')->toArray();
            $scheduleAccept = session('schedule_accept');
            $scheduledSpId = session('service_provider_id')
                ?? (is_array($scheduleAccept) ? ($scheduleAccept['service_provider_id'] ?? null) : null);

            return [
                [
                    'title' => __('Address'),
                    'fields' => [
                        new FormField(
                            label: __('Street Name / Street Code'),
                            labelFor: 'road_code',
                            inputType: 'multiple-select',
                            inputId: 'road_code',
                            selectValues: [],
                            disabled: true,
                            required: true
                        ),
                        new FormField(
                            label: __('House Number / BIN'),
                            labelFor: 'bin',
                            inputType: 'multiple-select',
                            inputId: 'bin',
                            selectValues: [],
                            disabled: true,
                            required: true
                        ),
                        new FormField(
                            label: __('Containment ID'),
                            labelFor: 'containment_id',
                            inputType: 'text',
                            inputId: 'containment_id',
                            disabled: true,
                            placeholder: __('Containment ID')
                        ),
                        new FormField(
                            label: __('Ward Number'),
                            labelFor: 'ward',
                            inputType: 'select',
                            inputId: 'ward',
                            disabled: true,
                            placeholder: __('Ward Number'),
                            selectValues: Ward::orderBy('ward')
                                ->pluck('ward', 'ward')
                                ->toArray()
                        ),
                    ],
                ],
                [
                    'title' => __('Owner Details'),
                    'fields' => [
                        new FormField(
                            label: __('Owner Name'),
                            labelFor: 'customer_name',
                            inputType: 'text',
                            inputId: 'customer_name',
                            placeholder: __('Owner Name'),
                            required: true
                        ),
                        new FormField(
                            label: __('Owner Gender'),
                            labelFor: 'customer_gender',
                            inputType: 'select',
                            inputId: 'customer_gender',
                            selectValues: [
                                'Male' => 'Male',
                                'Female' => 'Female',
                                'Others' => 'Others',
                            ],
                            placeholder: __('Owner Gender'),
                            required: true
                        ),
                        new FormField(
                            label: __('Owner Contact (Phone)'),
                            labelFor: 'customer_contact',
                            inputType: 'text',
                            inputId: 'customer_contact',
                            placeholder: __('Owner Contact (Phone)'),
                            oninput: 'validateOwnerContactInput(this)'
                        ),
                    ],
                ],
                [
                    'title' => __('Applicant Details'),
                    'copyDetails' => true,
                    'fields' => [
                        new FormField(
                            label: __('Applicant Name'),
                            labelFor: 'applicant_name',
                            inputType: 'text',
                            inputId: 'applicant_name',
                            required: true,
                            placeholder: __('Applicant Name')
                        ),
                        new FormField(
                            label: __('Applicant Gender'),
                            labelFor: 'applicant_gender',
                            inputType: 'select',
                            inputId: 'applicant_gender',
                            selectValues: [
                                'Male' => 'Male',
                                'Female' => 'Female',
                                'Others' => 'Others',
                            ],
                            required: true,
                            placeholder: __('Applicant Gender')
                        ),
                        new FormField(
                            label: __('Applicant Contact (Phone)'),
                            labelFor: 'applicant_contact',
                            inputType: 'text',
                            inputId: 'applicant_contact',
                            required: true,
                            placeholder: __('Applicant Contact (Phone)'),
                            oninput: 'validateOwnerContactInput(this)'
                        ),
                    ],
                ],
                [
                    'title' => __('Application Details'),
                    'fields' => [
                        new FormField(
                            label: __('Proposed Emptying Date'),
                            labelFor: 'proposed_emptying_date',
                            inputType: 'date',
                            inputId: 'proposed_emptying_date',
                            required: true,
                            placeholder: __('Proposed Emptying Date')
                        ),
                        new FormField(
                            label: __('Service Provider Name'),
                            labelFor: 'service_provider_id',
                            inputType: 'multiple-select',
                            inputId: 'service_provider_id',
                            selectValues: $serviceProviders,
                            selectedValue: $scheduledSpId ? (string) $scheduledSpId : null,
                            disabled: true,
                            required: true,
                            placeholder: __('Service Provider Name')
                        ),
                    ],
                ],
            ];
        }

        return $this->createFormFields;
    }



    /**
     * Get form fields for showing application.
     *
     * @return array
     */
    public function getShowFormFields($application)
    {
        $isAnf = (bool) $application->is_anf;
        $address = $isAnf
            ? null
            : Application::select('building_info.buildings.house_number AS house_address')
                ->leftJoin('building_info.buildings', 'building_info.buildings.bin', '=', 'applications.bin')
                ->where('applications.bin', $application->bin)
                ->first();

        $addressFields = $isAnf
            ? [
                new FormField(
                    label: __('Ward Number'),
                    labelFor: 'anf_ward',
                    inputType: 'label',
                    inputId: 'anf_ward',
                    labelValue: $application->ward ?? '-'
                ),
                new FormField(
                    label: __('Locality Name'),
                    labelFor: 'anf_locality',
                    inputType: 'label',
                    inputId: 'anf_locality',
                    labelValue: $application->anf_locality ?? '-'
                ),
                new FormField(
                    label: __('Nearest Landmark'),
                    labelFor: 'anf_nearest_locality',
                    inputType: 'label',
                    inputId: 'anf_nearest_locality',
                    labelValue: $application->anf_nearest_locality ?? '-'
                ),
            ]
            : [
                new FormField(
                    label: __('Street Name / Street Code'),
                    labelFor: 'road_code',
                    inputType: 'label',
                    inputId: 'road_code',
                    labelValue: $application->road_code ?? '-'
                ),
                new FormField(
                    label: __('BIN'),
                    labelFor: 'bin',
                    inputType: 'label',
                    inputId: 'bin',
                    labelValue: $application->bin ?? '-'
                ),
                new FormField(
                    label: __('House Number'),
                    labelFor: 'house_address',
                    inputType: 'label',
                    inputId: 'house_address',
                    labelValue: $address?->house_address ?? '-'
                ),
                new FormField(
                    label: __('Containment ID'),
                    labelFor: 'containment_id',
                    inputType: 'label',
                    inputId: 'containment_id',
                    labelValue: $application->containment_id ?? '-'
                ),
                new FormField(
                    label: __('Ward Number'),
                    labelFor: 'ward',
                    inputType: 'label',
                    inputId: 'ward',
                    labelValue: $application->ward ?? '-'
                ),
            ];

        $this->showFormFields = [
            [
                "title" => $isAnf ? __('Address (House Number Unknown)') : __('Address'),
                "fields" => $addressFields,
            ],
        ];

        if (!$isAnf) {
            $this->showFormFields[] = [
                "title" => __("Owner Details"),
                "fields" => [
                    new FormField(
                        label: __('Owner Name'),
                        labelFor: 'customer_name',
                        inputType: 'label',
                        inputId: 'customer_name',
                        labelValue: $application->customer_name  ?? __('Owner Name'),
                    ),
                    new FormField(
                        label: __('Owner Gender'),
                        labelFor: 'customer_gender',
                        inputType: 'label',
                        inputId: 'customer_gender',
                        labelValue: $application->customer_gender  ?? __('Owner Gender'),
                    ),
                    new FormField(
                        label: __('Owner Contact (Phone)'),
                        labelFor: 'customer_contact',
                        inputType: 'label',
                        inputId: 'customer_contact',
                        labelValue: $application->customer_contact ?? __('Owner Contact (Phone)'),

                    ),
                ],
            ];
        }

        $this->showFormFields[] = [
                "title" => __("Applicant Details"),
                "fields" => [
                    new FormField(
                        label: __("Applicant Name"),
                        labelFor: 'applicant_name',
                        inputType: 'label',
                        inputId: 'applicant_name',
                        labelValue: $application->applicant_name  ?? __('Applicant Name'),
                    ),
                    new FormField(
                        label: __("Applicant Gender"),
                        labelFor: 'applicant_gender',
                        inputType: 'label',
                        inputId: 'applicant_gender',
                        labelValue: $application->applicant_gender ?? __('Applicant Gender'),
                    ),
                    new FormField(
                        label: __("Applicant Contact (Phone)"),
                        labelFor: 'applicant_contact',
                        inputType: 'label',
                        inputId: 'applicant_contact',
                        labelValue: $application->applicant_contact ?? __('Applicant Contact (Phone)'),
                    ),
                ],
            ];

        $this->showFormFields[] = [
                "title" => __("Service Provider Details"),
                "fields" => [
                    new FormField(
                        label: __('Desludging Vehicle Size'),
                        labelFor: 'desludging_vehicle_size',
                        inputType: 'label',
                        inputId: 'desludging_vehicle_size',
                        labelValue: $application->desludging_vehicle_size . ' m³' ?? __('Desludging Vehicle Size'),
                    ),

                    new FormField(
                        label: __('Service Provider Name'),
                        labelFor: 'service_provider_id',
                        inputType: 'label',
                        inputId: 'service_provider_id',
                        labelValue: $application->service_provider ? $application->service_provider()->withTrashed()->first()->company_name : 'Not Assigned',
                    ),
                ],
            ];

        return $this->showFormFields;
    }

    /**
     * Get form fields for editing application.
     *
     * @return array
     */
    public function getEditFormFields($application)
    {
        // Load service provider relation if not already loaded
        $application->loadMissing('service_provider');
        $isAnf = (bool) $application->is_anf;

        // If emptying_status true => include trashed, else only operational
        if ($application->emptying_status) {
            $allProviders = ServiceProvider::withTrashed()->pluck('company_name', 'id')->toArray();
        } else {
            $allProviders = ServiceProvider::Operational()->pluck('company_name', 'id')->toArray();
        }

        // Vehicle sizes dropdown
        $selectValues = VacutugType::query()
            ->whereNotNull('capacity')->distinct()->orderBy('capacity')
            ->pluck('capacity')
            ->map(fn($c) => 0 + $c)
            ->mapWithKeys(fn($c) => [(string)$c => "$c m³"])
            ->toArray();

        $address = $isAnf
            ? null
            : Application::select('building_info.buildings.house_number AS house_address')
                ->leftJoin('building_info.buildings', 'building_info.buildings.bin', '=', 'applications.bin')
                ->where('applications.bin', $application->bin)
                ->first();

        // Initial provider list based on saved size (this is what you will swap via JS on change)
        $serviceProviders = ServiceProviderSequence::where('desludging_vehicle_size', $application->desludging_vehicle_size)
            ->where('current_sequence', true)
            ->whereHas('service_provider', function ($q) use ($application) {
                if (!$application->emptying_status) {
                    $q->where('status', 'true');
                }
            })
            ->with('service_provider:id,company_name')
            ->get()
            ->mapWithKeys(function ($row) {
                return [$row->service_provider_id => $row->service_provider->company_name];
            })
            ->toArray();

        // Ensure saved provider exists in dropdown even if not returned by current filter
        if ($application->service_provider_id && !isset($serviceProviders[$application->service_provider_id])) {
            $sp = ServiceProvider::withTrashed()->find($application->service_provider_id);
            if ($sp) {
                $serviceProviders[$sp->id] = $sp->company_name;
            }
        }

        $addressFields = $isAnf
            ? [
                new FormField(
                    label: __('Ward Number'),
                    labelFor: 'anf_ward',
                    inputType: 'select',
                    inputId: 'anf_ward',
                    selectValues: Ward::orderBy('ward')->pluck('ward', 'ward')->toArray(),
                    selectedValue: old('anf_ward', $application->ward),
                    required: true,
                    placeholder: __('Ward Number')
                ),
                new FormField(
                    label: __('Locality Name'),
                    labelFor: 'anf_locality',
                    inputType: 'text',
                    inputId: 'anf_locality',
                    inputValue: old('anf_locality', $application->anf_locality),
                    required: true,
                    placeholder: __('Locality Name')
                ),
                new FormField(
                    label: __('Nearest Landmark'),
                    labelFor: 'anf_nearest_locality',
                    inputType: 'text',
                    inputId: 'anf_nearest_locality',
                    inputValue: old('anf_nearest_locality', $application->anf_nearest_locality),
                    required: true,
                    placeholder: __('Nearest Landmark')
                ),
            ]
            : [
                new FormField(
                    label: __('Street Name / Street Code'),
                    labelFor: 'road_code',
                    inputType: 'label',
                    inputId: 'road_code',
                    labelValue: $application->road_code,
                    placeholder: __('Street Name / Street Code')
                ),
                new FormField(
                    label: __('BIN'),
                    labelFor: 'bin',
                    inputType: 'label',
                    inputId: 'bin',
                    labelValue: $application->bin,
                    placeholder: __('BIN')
                ),
                new FormField(
                    label: __('House Number'),
                    labelFor: 'house_address',
                    inputType: 'label',
                    inputId: 'house_address',
                    labelValue: $address?->house_address
                ),
                new FormField(
                    label: __('Containment ID'),
                    labelFor: 'containment_id',
                    inputType: 'label',
                    inputId: 'containment_id',
                    labelValue: $application->containment_id
                ),
                new FormField(
                    label: __('Ward Number'),
                    labelFor: 'ward',
                    inputType: 'label',
                    inputId: 'ward',
                    labelValue: $application->ward,
                    placeholder: __('Ward Number')
                ),
            ];

        $this->editFormFields = [
            [
                "title" => $isAnf ? __('Address (House Number Unknown)') : __('Address'),
                "fields" => $addressFields,
            ],
        ];

        if (!$isAnf) {
            $this->editFormFields[] = [
                "title" => __("Owner Details"),
                "fields" => [
                    new FormField(
                        label: __('Owner Name'),
                        labelFor: 'customer_name',
                        inputType: 'text',
                        inputId: 'customer_name',
                        inputValue: $application->customer_name,
                        placeholder: __('Owner Name'),
                        disabled: true
                    ),
                    new FormField(
                        label: __('Owner Gender'),
                        labelFor: 'customer_gender',
                        inputType: 'select',
                        inputId: 'customer_gender',
                        selectValues: ["Male" => "Male", "Female" => "Female", "Others" => "Others"],
                        selectedValue: $application->customer_gender,
                        placeholder: __('Owner Gender'),
                        disabled: true
                    ),
                    new FormField(
                        label: __('Owner Contact (Phone)'),
                        labelFor: 'customer_contact',
                        inputType: 'number',
                        inputId: 'customer_contact',
                        inputValue: $application->customer_contact,
                        placeholder: __('Owner Contact (Phone)'),
                        disabled: true,
                    ),
                ],
            ];
        }

        $this->editFormFields[] = [
                "title" => __("Applicant Details"),
                "copyDetails" => !$isAnf,
                "fields" => [
                    new FormField(
                        label: __("Applicant Name"),
                        labelFor: 'applicant_name',
                        inputType: 'text',
                        inputId: 'applicant_name',
                        inputValue: $application->applicant_name,
                        required: true,
                        placeholder: __('Applicant Name'),
                    ),
                    new FormField(
                        label: __("Applicant Gender"),
                        labelFor: 'applicant_gender',
                        inputType: 'select',
                        inputId: 'applicant_gender',
                        selectValues: ["Male" => "Male", "Female" => "Female", "Others" => "Others"],
                        selectedValue: $application->applicant_gender,
                        required: true,
                        placeholder: __('Applicant Gender'),
                    ),
                    new FormField(
                        label: __("Applicant Contact (Phone)"),
                        labelFor: 'applicant_contact',
                        inputType: 'number',
                        inputId: 'applicant_contact',
                        inputValue: $application->applicant_contact,
                        required: true,
                        placeholder: __('Applicant Contact (Phone)'),
                        oninput: "validateOwnerContactInput(this)",
                    ),
                ],
            ];

        $this->editFormFields[] = [
                "title" => __("Service Provider Details"),
                "fields" => [
                    new FormField(
                        label: __('Desludging Vehicle Size'),
                        labelFor: 'desludging_vehicle_size',
                        inputType: 'select',
                        inputId: 'desludging_vehicle_size',
                        required: true,
                        placeholder: __('Desludging Vehicle Size'),
                        selectValues: $selectValues,
                        selectedValue: old('desludging_vehicle_size', $application->desludging_vehicle_size),
                    ),

                    new FormField(
                        label: __('Service Provider Name'),
                        labelFor: 'service_provider_name',
                        inputType: 'text',
                        inputId: 'service_provider_name',
                        required: false,
                        placeholder: __('Service Provider Name'),
                        disabled: true,
                        inputValue: old(
                            'service_provider_name',
                            optional($application->service_provider)->company_name
                        ),
                    ),

                    new FormField(
                        label: __('Service Provider ID'),
                        labelFor: 'service_provider_id',
                        inputType: 'number',
                        inputId: 'service_provider_id',
                        hidden: true,
                        inputValue: old('service_provider_id', $application->service_provider_id),
                        required: true
                    ),

                ],
            ];

        return $this->editFormFields;
    }


    /**
     * Get action/route for create form.
     *
     * @return String
     */
    public function getCreateFormAction()
    {
        return $this->createFormAction;
    }

    /**
     * Get action/route for index page of Applications.
     *
     * @return String
     */
    public function getIndexAction()
    {
        return $this->indexAction;
    }

    /**
     * Get action/route for create page of Applications.
     *
     * @return String
     */
    public function getCreateRoute()
    {
        return $this->createRoute;
    }

    /**
     * Get action/route for exporting Applications.
     *
     * @return String
     */
    public function getExportRoute()
    {
        return $this->exportRoute;
    }

    public function getReportRoute()
    {
        return $this->reportRoute;
    }

    /**
     * Get action/route for edit form.
     *
     * @return String
     */
    public function getEditFormAction($application)
    {
        $this->editFormAction = route('application.update', $application);
        return $this->editFormAction;
    }

    /**
     * Get form fields for filter.
     *
     * @return array
     */
    public function getFilterFormFields()
    {
        return $this->filterFormFields;
    }

    /**
     * Get all the applications.
     *
     *
     * @return Application[]|Collection
     */
    public function getAllApplications(Request $request)
    {
        $query = Application::select(
                'applications.*',
                'building_info.buildings.house_number AS house_address'
            )
            ->leftJoin(
                'building_info.buildings',
                'building_info.buildings.bin',
                '=',
                'applications.bin'
            )
            ->whereNull('applications.deleted_at');

        if (Auth::user()->hasRole('Service Provider - Help Desk')) {
            return $query->where(
                'applications.service_provider_id',
                Auth::user()->service_provider_id ?? -1
            );
        }

        return OperationalDataAccessService::scopeApplicationQuery($query);
    }

    /**
     * Get Datatables of Applications.
     *
     * @return DataTables
     * @throws Exception
     */
    public function getDatatable(Request $request)
    {

        return DataTables::of($this->getAllApplications($request))

            ->filter(function ($query) use ($request) {
                if ($request->bin) {
                    $query->whereHas('buildings', function ($query) use ($request) {
                        $query->where('bin', 'ILIKE', '%' . $request->bin . '%');
                        $query->orWhere('bin', 'ILIKE', '%' . $request->bin . '%');
                    });
                }
                if ($request->house_address) {
                    $query->where('building_info.buildings.house_number', 'ILIKE', '%' . $request->house_address . '%');
                }
                if ($request->applicant_name) {
                    $query->where('applicant_name', 'ILIKE', '%' . $request->applicant_name . '%');
                }
                if ($request->ward) {
                    $query->where('applications.ward', $request->ward);
                }
                if ($request->application_id) {
                    $query->where('id', $request->application_id);
                }
                if (!is_null($request->emptying_status)) {
                    $query->where('emptying_status', $request->emptying_status);
                }
                if (!is_null($request->feedback_status)) {
                    $query->where('feedback_status', $request->feedback_status);
                }
                if (!is_null($request->sludge_collection_status)) {
                    $query->where('sludge_collection_status', $request->sludge_collection_status);
                }
                if ($request->road_code) {
                    $query->where('applications.road_code', $request->road_code);
                }
                if ($request->proposed_emptying_date) {
                    $query->where('proposed_emptying_date', $request->proposed_emptying_date);
                }
                if ($request->service_provider_id) {
                    $query->where('service_provider_id', $request->service_provider_id);
                }
                if ($request->date_from && $request->date_to && $request->date_from <= $request->date_to) {
                    $query->whereDate('application_date', '>=', $request->date_from);
                    $query->whereDate('application_date', '<=', $request->date_to);
                }
            })
            ->addColumn('action', function ($model) {
                $emptyingTripNo = optional($model->emptying)->trip_no ?? 0;
                $collectionTripNo = optional($model->sludge_collection)->trip_no ?? 0;
                $content = \Form::open(['method' => 'DELETE', 'route' => ['application.destroy', $model->id]]);
                $content .= '<div class="">';
                if (Auth::user()->can('Edit Application')) {
                    $content .= '<a title="' . __('Edit  Application Details') . '" href="' . route('application.edit', [$model->id]) . '" class="btn btn btn-info btn-sm mb-1 mb-1 ' . (($model->emptying_status == 1 || $model->emptying_status == 2) ? ' anchor-disabled' : '') . '"><i class="fa fa-edit"></i></a> ';
                }
                if (Auth::user()->can('View Application')) {
                    $content .= '<a title="' . __('View Application Details') . '" href="' . route('application.show', [$model->id]) . '" class="btn btn btn-info btn-sm mb-1 mb-1"><i class="fa fa-list"></i></a> ';
                }
                if (Auth::user()->can('Edit Emptying')) {
                    $emptyingId = optional($model->emptying)->id;
                    if ($emptyingId) {
                        $content .= '<a title="' . __('Edit Emptying Service Details') . '" href="'
                            . route('emptying.edit', $emptyingId)
                            . '" class="btn btn-info btn-sm mb-1'
                            . ($emptyingTripNo == $collectionTripNo  ? ' anchor-disabled' : '')
                            . '"><i class="fa fa-recycle"></i></a> ';
                    }
                }
                if (Auth::user()->can('Edit Sludge Collection')) {
                    $sludgeId = optional($model->sludge_collection_log()->latest()->first())->id;

                    if ($sludgeId) {
                        $content .= '<a title="' . __('Edit Sludge Collection Details') . '" '
                            . ($emptyingTripNo > $collectionTripNo ? '' : 'href="' . route('sludge-collection.edit', $sludgeId) . '"')
                            . ' class="btn btn-info btn-sm mb-1 '
                            . ($model->feedback_status == 1 || $emptyingTripNo > $collectionTripNo ? 'anchor-disabled' : '')
                            . '"><i class="fa fa-truck-moving"></i></a> ';
                    }
                }

                if (Auth::user()->can('View Application History')) {
                    $content .= '<a title="' . __("History") . '" href="' . route('application.history', $model->id) . '" class="btn btn btn-info btn-sm mb-1 mb-1"><i class="fa fa-history"></i></a> ';
                    if (Auth::user()->can('Delete Application')) {
                        $content .= '<a title="' . __("Delete") . '"   class="delete btn btn-danger  btn-sm mb-1"><i class="fa fa-trash"></i></a> ';
                    }
                }

                 if($model->bin != '' && Auth::user()->can('View Application History')) {
                    $content .= '<a title="' . __("Map") . '" href="' . action("MapsController@index", ['layer' => 'buildings_layer', 'field' => 'bin', 'val' => $model->bin]) . '" class="btn btn-info btn-sm mb-1"  ><i class="fas fa-map-marker"></i></a> ';
                 }

                if (Auth::user()->can('Generate Application Report')) {
                    if ($model->emptying_status == 2) {
                        $content .= '<a title="' . __("Generate Report") . '" href="' . route('application.report', [$model->id]) . '" class="btn btn btn-info btn-sm mb-1 mb-1"><i class="fa-regular fa-file-pdf"></i></a> ';
                    }
                }

                $content .= '</div>';
                $content .= \Form::close();

                return $content;
            })
            ->editColumn('emptying_status', function ($model) {
                $emptyingTripNo = optional($model->emptying)->trip_no ?? 0;
                $collectionTripNo = optional($model->sludge_collection)->trip_no ?? 0;
                $content = '<div class="application-quick__actions">';

                $effectiveEmptyingStatus = 0;
                if ($model->emptying_status || $emptyingTripNo > 0) {
                    $effectiveEmptyingStatus = ($emptyingTripNo >= ($model->trip_count ?? 1)) ? 2 : 1;
                }
                $content .= ($effectiveEmptyingStatus == 0) ? '<i class="fa fa-times"></i>' : (($effectiveEmptyingStatus == 1) ? '<i class="fa fa-repeat"></i>' : '<i class="fa fa-check"></i>');

                if ($model->proposed_emptying_date == null) {
                    if (Auth::user()->can('Add Emptying')) {
                        $content .= '<a title="' . __("Add Emptying Service Details") . '" class="btn btn-info btn-sm mb-1 anchor-disabled"><i class="fa fa-recycle"></i></a> ';
                    }
                } else if ($model->emptying_status == 0 && $model->proposed_emptying_date != '') {
                    if (Auth::user()->can('Add Emptying')) {
                        $content .= '<a title="' . __("Add Emptying Service Details") . '" href="' . route("emptying.create-id", [$model->id]) . '" class="btn btn-success btn-sm mb-1 "><i class="fa fa-recycle"></i></a> ';
                    }
                } else if (
                    $model->emptying_status == 1 &&
                    $model->sludge_collection_status == 1 &&
                    $emptyingTripNo != $model->trip_count  && $emptyingTripNo  == $collectionTripNo

                ) {
                    if (Auth::user()->can('Add Emptying')) {
                        $content .= '<a title="' . __("Add Next Emptying Service Details") . '" href="'
                            . route("emptying.create-id", [$model->id, 'mode' => 'next'])
                            . '" class="btn btn-success btn-sm mb-1"><i class="fa fa-recycle"></i></a> ';
                    }
                } else if (($model->emptying_status == 1 && $model->sludge_collection_status == 0) && ($emptyingTripNo > $collectionTripNo)) {
                    if (Auth::user()->can('View Emptying')) {
                        $content .= '<a title="' . __("View Emptying Service Details") . '" href="' . route("emptying.show", [$model->with('emptying')->where('id', $model->id)->get()->first()->emptying->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-recycle"></i></a> ';
                    }
                } else {
                    if (Auth::user()->can('View Emptying')) {
                        $content .= '<a title="' . __("View Emptying Service Details") . '" href="' . route("emptying.show", [$model->with('emptying')->where('id', $model->id)->get()->first()->emptying->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-recycle"></i></a> ';
                    }
                }

                $content .= '</div>';
                return $content;
            })

            ->editColumn('sludge_collection_status', function ($model) {
                $collectionTripNo = optional($model->sludge_collection)->trip_no ?? 0;
                $effectiveSludgeStatus = 0;
                if ($model->sludge_collection_status || $collectionTripNo > 0) {
                    $effectiveSludgeStatus = ($collectionTripNo >= ($model->trip_count ?? 1)) ? 2 : 1;
                }
                $content = '<div class="application-quick__actions">';
                $content .= ($effectiveSludgeStatus == 0) ? '<i class="fa fa-times"></i>' : (($effectiveSludgeStatus == 1) ? '<i class="fa fa-repeat"></i>' : '<i class="fa fa-check"></i>');

                if (($model->emptying_status == 1 || $model->emptying_status == 2) && $model->sludge_collection_status == 0) {
                    if (Auth::user()->can('Add Sludge Collection')) {
                        $content .= '<a title="' . __("Add Sludge Collection Details") . '" href="' .
                            route("sludge-collection.create-log-id", [$model->id]) .
                            '" class="btn btn-success btn-sm mb-1' .
                            ($model->emptying_status == 1 || $model->emptying_status == 2 ? '' : ' anchor-disabled') .
                            '"><i class="fa fa-truck-moving"></i></a> ';
                    }
                } else if ($model->sludge_collection_status == 0) {
                    if (Auth::user()->can('Add Sludge Collection')) {
                        $content .= '<a title="' . __("Add Sludge Collection Details") . '" href="' .
                            route("sludge-collection.create-log-id", [$model->id]) .
                            '" class="btn btn-info btn-sm mb-1' .
                            ($model->emptying_status == 1 || $model->emptying_status == 2 ? '' : ' anchor-disabled') .
                            '"><i class="fa fa-truck-moving"></i></a> ';
                    }
                } else if ($model->emptying_status == 1 &&  $model->sludge_collection_status == 1 && $model->emptying->trip_no  != $model->sludge_collection->trip_no) {
                    if (Auth::user()->can('Add Sludge Collection')) {
                        $content .= '<a title="' . __("Add Next Sludge Collection Details") . '" href="' .
                            route("sludge-collection.create-log-id", [$model->id]) .
                            '" class="btn btn-success btn-sm mb-1' .
                            ($model->emptying_status == 1 || $model->emptying_status == 2 ? '' : ' anchor-disabled') .
                            '"><i class="fa fa-truck-moving"></i></a> ';
                    }
                } else if ($model->emptying_status == 2 &&  $model->sludge_collection_status == 1 && $model->emptying->trip_no  != $model->sludge_collection->trip_no) {
                    if (Auth::user()->can('Add Sludge Collection')) {
                        $content .= '<a title="' . __("Add Next Sludge Collection Details") . '" href="' .
                            route("sludge-collection.create-log-id", [$model->id]) .
                            '" class="btn btn-success btn-sm mb-1' .
                            ($model->emptying_status == 1 || $model->emptying_status == 2 ? '' : ' anchor-disabled') .
                            '"><i class="fa fa-truck-moving"></i></a> ';
                    }
                } else {
                    if (Auth::user()->can('View Sludge Collection')) {
                        $content .= '<a title="' . __("View Sludge Collection Details") . '" href="' . route("sludge-collection.show-details", [$model->sludge_collection->id]) . '" class="btn btn-info btn-sm mb-1' . ($model->emptying_status ? '' : ' anchor-disabled') . '"><i class="fa fa-truck-moving"></i></a> ';
                    }
                }
                $content .= '</div>';
                return $content;
            })
            ->editColumn('feedback_status', function ($model) {
                $content = '<div class="application-quick__actions">';
                $content .= ($model->feedback_status == 0) ? '<i class="fa fa-times"></i>' : (($model->feedback_status == 1) ? '<i class="fa fa-check"></i>' : '');

                if (
                    $model->emptying_status == 2 &&
                    $model->sludge_collection_status == 2 &&
                    $model->feedback_status == 0 &&
                    $model->emptying->trip_no == $model->sludge_collection->trip_no &&
                    $model->sludge_collection->trip_no == $model->trip_count
                ) {
                    if (Auth::user()->can('Add Feedback')) {

                        $content .= '<a title="' . __("Add Feedback Details") . '"href="' . route("feedback.create-Feedback", [$model->id]) . '" class="btn btn-success btn-sm mb-1"><i class="fa fa-pencil"></i></a> ';
                    }
                } else if ($model->feedback_status == 1) {
                    if (Auth::user()->can('View Feedback')) {
                        $content .= '<a title="' . __("Feedback Details") . '" href="'
                            . action("Fsm\FeedbackController@show", [$model->feedback->id])
                            . '" class="btn btn-info btn-sm mb-1'
                            . (($model->sludge_collection_status == 1 || $model->sludge_collection_status == 2) ? '' : ' anchor-disabled')
                            . '"><i class="fa fa-pencil"></i></a> ';
                    }
                } else {
                    if (Auth::user()->can('Add Feedback')) {
                        // disable the feedback button
                        $content .= '<a title="' . __("Feedback Disabled") . '" href="#" class="btn btn-info btn-sm mb-1 anchor-disabled " tabindex="-1" aria-disabled="true"><i class="fa fa-pencil"></i></a> ';
                    }
                }

                $content .= '</div>';
                return $content;
            })


            ->editColumn('proposed_emptying_date', function ($model) {


                if (!empty($model->proposed_emptying_date) && strtotime($model->proposed_emptying_date)) {
                    return Carbon::parse($model->proposed_emptying_date)->format('l, F jS Y');
                } else {
                    if (Auth::user()->can('Proposed Emptying Date')) {
                       return '<div class="d-flex align-items-center justify-content-between w-100">
                            <i class="fa fa-times"></i>
                            <a href="' . route("emptying-scheduling", [$model->id]) . '" class="btn btn-success btn-sm" title="' . __("Proposed Emptying Date") . '">
                                <i class="fa fa-calendar-plus"></i>
                            </a>
                        </div>';
                    }
                }
            })
            ->editColumn('service_provider_id', function ($model) {
                return $model->service_provider()->withTrashed()->first()->company_name ?? 'Not Assigned';
            })
            ->rawColumns(['emptying_status', 'feedback_status', 'sludge_collection_status', 'proposed_emptying_date', 'action'])

            ->make(true);
    }

    /**
     * Store new application.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|RedirectResponse|Redirector
     */
    public function createApplication(ApplicationRequest $request)
    {
        $isAnf = $request->input('is_anf') == '1';

        // Duplicate containment check — only for normal (non-ANF) applications
        if (!$isAnf && !empty($request->containment_id)) {
            $previous_application_status = Application::where('containment_id', $request->containment_id)
                ->where('emptying_status', 0)
                ->whereNull('deleted_at')
                ->exists();

            if ($previous_application_status) {
                return redirect()->back()->withInput()
                    ->with('error', __("Error! Containment already has running Application."));
            }
        }

        if ($request->validated()) {
            try {
                DB::transaction(function () use ($request, $isAnf) {

                    $appData = $request->all();
                    if ($isAnf) {
                        $appData['road_code'] = null;
                        $appData['bin'] = null;
                        $appData['containment_id'] = null;
                        $appData['anf_locality'] = $request->anf_locality;
                        $appData['anf_nearest_locality'] = $request->anf_nearest_locality;
                    } else {
                        $appData['anf_locality'] = null;
                        $appData['anf_nearest_locality'] = null;
                    }

                    // ✅ Single create call
                    $application = Application::create($appData);

                    // ✅ Always set these
                    $application->application_date          = now()->format('Y-m-d H:i:s');
                    $application->user_id                   = Auth::user()->id;
                    $application->emptying_status           = 0;
                    $application->sludge_collection_status  = 0;
                    $application->feedback_status           = 0;
                    $application->desludging_vehicle_size   = $request->desludging_vehicle_size;
                    $application->service_provider_id       = $request->service_provider_id;

                    if ($isAnf) {
                        // ---- ANF PATH: no BIN/building lookup ----
                        $application->road_code        = null;
                        $application->bin              = null;
                        $application->containment_id   = null;
                        $application->ward             = $request->anf_ward;
                        $application->anf_locality     = $request->anf_locality;
                        $application->anf_nearest_locality = $request->anf_nearest_locality;
                        $application->customer_name    = $request->customer_name ?? null;
                        $application->customer_contact = $request->customer_contact ?? null;
                        $application->customer_gender  = $request->customer_gender ?? null;
                    } else {
                        // ---- NORMAL PATH: existing BIN/building logic ----
                        $application->anf_locality     = null;
                        $application->anf_nearest_locality = null;

                        $building = Building::where('bin', '=', $application->bin)->firstOrFail();
                        $owner    = $building->owners;

                        $application->containment_id   = $request->containment_id;
                        $application->customer_name    = $request->customer_name    ?? $owner->owner_name;
                        $application->customer_contact = $request->customer_contact ?? $owner->owner_contact;
                        $application->customer_gender  = $request->customer_gender  ?? $owner->owner_gender;

                        $owner->fill([
                            "owner_name"    => $request->customer_name    ?? $owner->owner_name,
                            "owner_gender"  => $request->customer_gender  ?? $owner->owner_gender,
                            "owner_contact" => $request->customer_contact ?? $owner->owner_contact,
                        ])->save();

                        $building->fill([
                            "ward"      => $request->ward ?? $building->ward,
                            "road_code" => $request->road_code,
                        ])->save();

                        if ($request->filled('household_served')) {
                            $building->household_served = $request->household_served;
                        }

                        if ($request->filled('population_served')) {
                            $building->population_served = $request->population_served;
                        }

                        if ($request->filled('toilet_count')) {
                            $building->toilet_count = $request->toilet_count;
                        }

                        $building->save();
                    }

                    // ✅ Applicant autofill — works correctly for both paths
                    if ($request->autofill === 'on') {
                        $application->applicant_name    = $application->customer_name    ?? null;
                        $application->applicant_contact = $application->customer_contact ?? null;
                        $application->applicant_gender  = $application->customer_gender  ?? null;
                    } else {
                        $application->applicant_name    = $request->applicant_name;
                        $application->applicant_contact = $request->applicant_contact;
                        $application->applicant_gender  = $request->applicant_gender;
                    }

                    if (empty($application->desludging_vehicle_size) && !empty($application->service_provider_id)) {
                        $application->desludging_vehicle_size = VacutugType::where('service_provider_id', $application->service_provider_id)
                            ->whereNotNull('capacity')
                            ->value('capacity')
                            ?? VacutugType::whereNotNull('capacity')->value('capacity');
                    }
                    $application->save();

                    if ($request->action_type === 'confirm') {
                        Containment::where('id', $request->containment_id)
                            ->whereNull('deleted_at')
                            ->update(['status' => 1]);

                        DB::table('fsm.desludging_schedule_temp')
                            ->where(
                                'containment_id',
                                (string) $request->containment_id
                            )
                            ->delete();
                    }

                    $capacity = (int) $request->input('desludging_vehicle_size', 0);
                    $this->rotateServiceProviderSequence($capacity);

                    // Notifications — unchanged from original
                    $message       = "New Application (ID: {$application->id}) has been assigned to you.";
                    $webRecipients = User::where('service_provider_id', $application->service_provider_id)->get();

                    if (!empty($request->employee_id)) {
                        $employee = DB::table('fsm.employees')
                            ->where('id', $request->employee_id)
                            ->where('status', true)
                            ->where('service_provider_id', $application->service_provider_id)
                            ->first();

                        if (!empty($employee?->user_id)) {
                            $employeeUser = User::where('id', $employee->user_id)
                                ->where('service_provider_id', $application->service_provider_id)
                                ->first();
                            if ($employeeUser) {
                                $webRecipients->push($employeeUser);
                            }
                        }
                    }

                    $webRecipients = $webRecipients->unique('id')->values();
                    foreach ($webRecipients as $user) {
                        Notification::create([
                            'user_id'        => $user->id,
                            'mode'           => 'web',
                            'message'        => $message,
                            'status'         => false,
                            'application_id' => $application->id,
                        ]);
                    }

                    $etoUserIds = DB::table('fsm.employees')
                        ->where('service_provider_id', $application->service_provider_id)
                        ->where('status', true)
                        ->whereNotNull('user_id')
                        ->pluck('user_id')
                        ->map(fn($id) => (string) $id)
                        ->unique()->values()->all();

                    // Also include Service Provider admins/users directly linked to the SP
                    $spUserIds = DB::table('auth.users')
                        ->where('service_provider_id', $application->service_provider_id)
                        ->pluck('id')
                        ->map(fn($id) => (string) $id)
                        ->unique()->values()->all();

                    $etoUserIds = array_values(array_unique(array_merge($etoUserIds, $spUserIds)));

                    if (!empty($request->employee_id)) {
                        $assigned = DB::table('fsm.employees')
                            ->where('id', $request->employee_id)
                            ->where('status', true)
                            ->where('service_provider_id', $application->service_provider_id)
                            ->value('user_id');

                        if ($assigned) {
                            $etoUserIds[] = (string) $assigned;
                            $etoUserIds   = array_values(array_unique($etoUserIds));
                        }
                    }
                   
                });
            } catch (\Illuminate\Database\QueryException $e) {
                \Log::error('Database error in application creation: ' . $e->getMessage());
                return redirect()->back()->withInput()
                    ->with('error', __('Database error occurred. Please try again.'));
                    
            } catch (\Throwable $e) {
                \Log::error('Application creation failed: ' . $e->getMessage() . ' line ' . $e->getLine() . ' file ' . $e->getFile());
                return redirect()->back()->withInput()
                    ->with('error', __("Error! Application couldn't be created. ") . $e->getMessage());
            }
        }

        if ($request->action_type === 'confirm') {
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

        if ($request->action_type === 'confirm') {
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

        return redirect(route('application.index'))->with('success',  __('Application created successfully.'));
    }

    /**
     * Update application.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|RedirectResponse|Redirector
     */
    public function updateApplication(ApplicationRequest $request, $id)
    {

        try {
            $application = Application::findOrFail($id);
            OperationalDataAccessService::authorizeApplication($application);
            $isAnf = (bool) $application->is_anf;

            // The saved address state controls which fields may be updated.
            // Editing an application must not switch it between ANF and linked-building states.
            $application->fill($request->except([
                'is_anf',
                'road_code',
                'bin',
                'ward',
                'containment_id',
                'customer_name',
                'customer_gender',
                'customer_contact',
            ]));

            if (!$isAnf) {
                $building = Building::where('bin', '=', $application->bin)->firstOrFail();
                $owner = $building->owners;
                $application->containment_id = $request->containment_id ?? $application->containment_id;
                $application->customer_name = $request->customer_name ?? $owner->owner_name;
                $application->customer_contact = $request->customer_contact ?? $owner->owner_contact;
                $application->customer_gender = $request->customer_gender ?? $owner->owner_gender;
                $owner->fill(
                    [
                        "owner_name" => $request->customer_name ?? $owner->owner_name,
                        "owner_gender" => $request->customer_gender ?? $owner->owner_gender,
                        "owner_contact" => $request->customer_contact ?? $owner->owner_contact,
                        "containment_id" => $request->containment_id ?? $application->containment_id
                    ]
                )->save();
                $building->fill([
                    "ward" => $request->ward ?? $building->ward,
                    "road_code" => $request->road_code ?? $building->road_code
                ])->save();
            }

            if ($isAnf) {
                $application->is_anf = true;
                $application->ward = $request->anf_ward;
                $application->anf_locality = $request->anf_locality;
                $application->anf_nearest_locality = $request->anf_nearest_locality;
            }

            $application->save();
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', __('Failed to update Application.') . $e);
        }
        return redirect(route('application.index'))->with('success', __('Application updated successfully.'));
    }

    /**
     * Retrieve application history.
     *
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|RedirectResponse|Redirector
     */
    public function getApplicationHistory($id)
    {
        try {
            $application = Application::findOrFail($id);
            OperationalDataAccessService::authorizeApplication($application);
            $revisions = Revision::all()
                ->where('revisionable_type', get_class($application))
                ->where('revisionable_id', $id)
                ->groupBy(function ($item) {
                    return $item->created_at->format("D M j Y");
                })
                ->sortByDesc('created_at')
                ->reverse();
        } catch (\Throwable $e) {
            return redirect(route('application.index'))->with('error', __('Failed to generate history.'));
        }
        return view('fsm.applications.history', compact('application', 'revisions'));
    }

    /**
     * Export applications.
     *
     * @throws Exception
     */
    // Ensure you have imported the Application model if not already imported

    public function export(Request $request)
    {
        // Retrieve request parameters
        $house_number = $request->bin;
        $house_address = $request->house_address;
        $customer_name = $request->customer_name;
        $ward = $request->ward;
        $application_id = $request->application_id;
        $emptying_status = $request->emptying_status;
        $feedback_status = $request->feedback_status;
        $sludge_collection_status = $request->sludge_collection_status;
        $road = $request->road_code;
        $proposed_emptying_date = $request->proposed_emptying_date;
        $service_provider_id = $request->service_provider_id;
        $date_from = $request->date_from;
        $date_to = $request->date_to;

        // Define CSV column headers
        $columns = [
            __('Road Code'),
            __('BIN'),
            __('House Number'),
            __('Ward Number'),
            __('Owner Name'),
            __('Owner Gender'),
            __('Owner Contact (Phone)'),
            __('Application Date'),
            __('Applicant Name'),
            __('Applicant Gender'),
            __('Applicant Contact (Phone)'),
            __('Proposed Emptying Date'),
            __('Service Provider Name'),
            __('Number of Households'),
            __('Population of Building'),
            __('Number of Toilets'),
            __('Emptying Status'),
            __('Sludge Collection Status'),
            __('Feedback Status'),
        ];


        // Build the base query
        $query = DB::table('fsm.applications as a')
            ->leftJoin('building_info.buildings as b', 'b.bin', '=', 'a.bin')
            ->leftJoin('fsm.service_providers as s', 's.id', '=', 'a.service_provider_id')
            ->leftJoin('fsm.emptyings as e', function ($join) {
                $join->on('e.application_id', '=', 'a.id')
                    ->whereNull('e.deleted_at');
            })
            ->select(
                'a.bin',
                'b.house_number as house_address',
                'a.road_code',
                'a.ward',
                'a.customer_name',
                'a.customer_gender',
                'a.customer_contact',
                'a.application_date',
                'a.applicant_name',
                'a.applicant_gender',
                'a.applicant_contact',
                'a.proposed_emptying_date',
                's.company_name',
                'b.household_served',
                'b.population_served',
                'b.toilet_count',
                'a.emptying_status',
                'a.sludge_collection_status',
                'a.feedback_status'
            )
            ->whereNull('a.deleted_at')
            ->distinct()
            ->orderBy('a.bin');

        if (Auth::user()->hasRole('Service Provider - Help Desk')) {
            $query->where('a.service_provider_id', Auth::user()->service_provider_id);
        } else {
            OperationalDataAccessService::scopeQuery(
                $query,
                'a.service_provider_id',
                'e.treatment_plant_id'
            );
        }

        // Apply filters based on request parameters
        if (!empty($house_number)) {
            $query->where(function ($q) use ($house_number) {
                $q->where('a.bin', 'ILIKE', '%' . $house_number . '%')
                    ->orWhere('b.bin', 'ILIKE', '%' . $house_number . '%');
            });
        }
        if (!empty($house_address)) {
            $query->where('b.house_number', 'ILIKE', '%' . $house_address . '%');
        }

        if (!empty($customer_name)) {
            $query->where('a.customer_name', 'ILIKE', '%' . $customer_name . '%');
        }
        if (!empty($ward)) {
            $query->where('a.ward', $ward);
        }
        if (!empty($application_id)) {
            $query->where('a.id', $application_id);
        }
        if (!is_null($emptying_status)) {
            $query->where('a.emptying_status', $emptying_status);
        }
        if (!empty($feedback_status)) {
            $query->where('a.feedback_status', $feedback_status);
        }
        if (!empty($sludge_collection_status)) {
            $query->where('a.sludge_collection_status', $sludge_collection_status);
        }
        if (!empty($road)) {
            $query->where('a.road_code', $road);
        }
        if (!empty($proposed_emptying_date)) {
            $query->where('a.proposed_emptying_date', $proposed_emptying_date);
        }
        if (!empty($service_provider_id)) {
            $query->where('a.service_provider_id', $service_provider_id);
        }
        if ($date_from && $date_to) {
            $query->whereBetween('a.application_date', [$date_from, $date_to]);
        }

        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();

        $writer = WriterFactory::create(Type::CSV);
        $writer->openToBrowser('Applications.csv')
            ->addRowWithStyle($columns, $style);
        $query->chunk(5000, function ($applications) use ($writer) {
            // Add data rows to CSV
            foreach ($applications as $application) {

                // Prepare data for CSV row
                $values = [
                    $application->road_code,
                    $application->bin,
                    $application->house_address,
                    $application->ward,
                    $application->customer_name,
                    $application->customer_gender,
                    $application->customer_contact,
                    $application->application_date,
                    $application->applicant_name,
                    $application->applicant_gender,
                    $application->applicant_contact,
                    $application->proposed_emptying_date,
                    $application->company_name,
                    $application->household_served,
                    $application->population_served,
                    $application->toilet_count,
                    ApplicationStatus::toEnumArray()[$application->emptying_status] ?? '',
                    ApplicationStatus::toEnumArray()[$application->sludge_collection_status] ?? '',
                    FeedbackStatus::toEnumArray()[$application->feedback_status] ?? '',
                ];

                // Add row to CSV
                $writer->addRow($values);
            }
        });

        // Close the CSV file
        $writer->close();
    }

    /**
     * Fetches and generates a monthly report.
     *
     * @param int $year The year for the report.
     * @param int $month The month for the report.
     * @return \Illuminate\Http\Response The generated PDF report.
     */
    public function fethMonthlyReport($year, $month)
    {
        if (Auth::user()->hasRole('Super Admin') || Auth::user()->hasRole('Municipality - Super Admin') || Auth::user()->hasRole('Municipality - IT Admin') && !Auth::user()->hasRole('Municipality - Executive') || Auth::user()->hasRole('Municipality - Help Desk')) {
            $monthWisequery = 'WITH application AS(

           SELECT service_providers.company_name AS serv_name, count(applications.id) AS applicationCount
            from fsm.service_providers
            LEFT JOIN fsm.applications ON service_providers.id= applications.service_provider_id where EXTRACT(YEAR FROM application_date) = ' . $year . '
            and EXTRACT(Month from application_date)  = ' . $month . '
            AND fsm.service_providers.deleted_at IS NULL
            AND fsm.applications.deleted_at IS NULL
            GROUP BY serv_name
        ),
        emptying as(
            select service_providers.company_name as serv_name, count(emptyings.id)  as emptyCount, sum(total_cost) as totalCost  , sum(volume_of_sludge) as sludgeCount, count(volume_of_sludge) as sCount
            from fsm.service_providers
            LEFT JOIN fsm.applications ON service_providers.id= applications.service_provider_id
            Left JOIN fsm.emptyings ON emptyings.application_id = applications.id  where EXTRACT(YEAR FROM emptyings.emptied_date) =' . $year . '
            and EXTRACT(Month from emptyings.emptied_date)  = ' . $month . ' and EXTRACT(YEAR FROM applications.application_date) = ' . $year . '
            and EXTRACT(Month from applications.application_date)  = ' . $month . '
            AND fsm.service_providers.deleted_at IS NULL
            GROUP BY service_providers.company_name
        )
        select application.serv_name, applicationCount, emptyCount, sludgeCount, totalCost,sCount  from application full join emptying ON application.serv_name = emptying.serv_name; ';

            $monthWisecount = DB::Select($monthWisequery);

            $yearCountquery = 'with application as(
            select  count(applications.id) as applicationCount
            from fsm.applications
            where EXTRACT(YEAR FROM application_date) = ' . $year . ' and EXTRACT(Month from application_date)  <= ' . $month . ' AND fsm.applications.deleted_at IS NULL

        ),
        emptying as(
            SELECT COUNT(emptyings.id) AS emptyCount,
            SUM(total_cost) AS totalCost, SUM(volume_of_sludge) AS sludgeCount, count(volume_of_sludge) AS sCount  FROM  fsm.emptyings
            LEFT JOIN fsm.applications ON emptyings.application_id = applications.id WHERE
            EXTRACT(YEAR FROM emptyings.emptied_date) = ' . $year . '
            AND EXTRACT(MONTH FROM emptyings.emptied_date) <= ' . $month . '
            AND EXTRACT(YEAR FROM applications.application_date) = ' . $year . '
            AND EXTRACT(MONTH FROM applications.application_date) <= ' . $month . '

        )
        select applicationCount, emptyCount, sludgeCount, totalCost , sCount from application, emptying; ';

            $yearCount = DB::Select($yearCountquery);

            $wardMonthlyquery = ' with application as(
            select count(applications.id) as applicationCount ,APPLICATIONS.ward as award
                   from fsm.applications
                   where EXTRACT(YEAR FROM application_date) = ' . $year . ' and EXTRACT(MONTH FROM application_date) <= ' . $month . ' AND fsm.applications.deleted_at IS NULL
                   GROUP BY APPLICATIONS.ward
         ),
          emptying as(
            select count(emptyings.id)  as emptyCount, sum(total_cost) as totalCost, sum(volume_of_sludge) as sludgeCount, count(volume_of_sludge) as sCount,ward as eward
                   from fsm.emptyings
                   Left JOIN fsm.applications  ON applications.id= emptyings.application_id  WHERE EXTRACT(YEAR FROM emptyings.emptied_date) = ' . $year . '
                   and EXTRACT(MONTH FROM emptyings.emptied_date) <= ' . $month . '
                   AND EXTRACT(YEAR FROM applications.application_date) = ' . $year . '
                   AND EXTRACT(MONTH FROM applications.application_date) <= ' . $month . '
                   GROUP BY APPLICATIONS.ward
               )
               select  applicationCount, emptyCount, sludgeCount, totalCost, sCount, award  from application a
               left join emptying e ON a.award = e.eward ORDER BY award ;';

            // converts month number to mont name
            $wardData = DB::Select($wardMonthlyquery);
            $dateObj   = DateTime::createFromFormat('!m', $month);
            $monthName = $dateObj->format('F');

            // return view('fsm.applications.monthly_report', compact('year', 'monthName','monthWisecount','yearCount','wardData'));
            return PDF::loadView('fsm.applications.monthly_report', compact('year', 'monthName', 'monthWisecount', 'yearCount', 'wardData'))->inline('Monthly Report.pdf');
        } else {

            $service_provider_id = User::where('id', '=', Auth::id())->pluck('service_provider_id')->first();

            $monthWisequery = 'with application as(
                select service_providers.company_name as serv_name, count(applications.id) as applicationCount
                from fsm.service_providers
                LEFT JOIN fsm.applications ON service_providers.id= applications.service_provider_id
                where EXTRACT(YEAR FROM application_date) = ' . $year . '
                and EXTRACT(Month from application_date)  = ' . $month . '
                and APPLICATIONS.service_provider_id=' . $service_provider_id . '
                AND fsm.service_providers.deleted_at IS NULL
                AND fsm.applications.deleted_at IS NULL
                GROUP BY service_providers.company_name
            ),
            emptying as(
                select service_providers.company_name as serv_name, count(emptyings.id)  as emptyCount, sum(total_cost) as totalCost  , sum(volume_of_sludge) as sludgecount, count(volume_of_sludge) as sCount
                from fsm.service_providers
                LEFT JOIN fsm.applications ON service_providers.id= applications.service_provider_id
                Left JOIN fsm.emptyings ON applications.id= emptyings.application_id  where EXTRACT(YEAR FROM emptied_date) =' . $year . '
                and EXTRACT(Month from emptied_date)  = ' . $month . '
                AND EXTRACT(YEAR FROM applications.application_date) = ' . $year . '
                AND EXTRACT(MONTH FROM applications.application_date) = ' . $month . '
                and emptyings.service_provider_id=' . $service_provider_id . '
                AND fsm.service_providers.deleted_at IS NULL

                GROUP BY service_providers.company_name
            )
            select application.serv_name, applicationCount, emptyCount,sludgecount, totalCost, sCount  from application full join emptying ON application.serv_name = emptying.serv_name; ';

            $monthWisecount = DB::Select($monthWisequery);
            $yearCountquery = 'with application as(
                select  count(applications.id) as applicationCount
                from fsm.applications
                where EXTRACT(YEAR FROM application_date) = ' . $year . '
                and EXTRACT(Month from application_date)  <= ' . $month . '
                and applications.service_provider_id=' . $service_provider_id . 'AND fsm.applications.deleted_at IS NULL
            ),
            emptying as(
                select  count(emptyings.id)  as emptyCount, sum(total_cost) as totalCost, sum(volume_of_sludge) as sludgeCount,  count(volume_of_sludge) as sCount
                from fsm.emptyings
                LEFT JOIN fsm.applications ON emptyings.application_id = applications.id
                 where EXTRACT(YEAR FROM emptied_date) = ' . $year . '
                 and  EXTRACT(Month from emptied_date)  <= ' . $month . '
                 AND EXTRACT(YEAR FROM applications.application_date) = ' . $year . '
                   AND EXTRACT(MONTH FROM applications.application_date) <= ' . $month . '
                and emptyings.service_provider_id=' . $service_provider_id . '

            )
            select applicationCount, emptyCount, sludgeCount, totalCost, sCount  from application, emptying; ';

            $yearCount = DB::Select($yearCountquery);

            $wardMonthlyquery = ' with application as(
                select count(applications.id) as applicationCount ,APPLICATIONS.ward as award
                    from fsm.APPLICATIONS
                    where EXTRACT(YEAR FROM application_date) = ' . $year . '
                    and EXTRACT(MONTH FROM application_date) <= ' . $month . '
                    and applications.service_provider_id=' . $service_provider_id . '
                    AND fsm.applications.deleted_at IS NULL
                       GROUP BY APPLICATIONS.ward
             ),
              emptying as(
                select count(emptyings.id)  as emptyCount, sum(total_cost) as totalCost,count(volume_of_sludge) as sludgeCount,  sum(volume_of_sludge) as sCount, ward as eward
                    from fsm.emptyings
                    Left JOIN fsm.applications ON applications.id= emptyings.application_id  WHERE EXTRACT(YEAR FROM emptied_date) = ' . $year . '
                    and EXTRACT(MONTH FROM emptied_date) <= ' . $month . '
                    AND EXTRACT(YEAR FROM applications.application_date) = ' . $year . '
                    AND EXTRACT(MONTH FROM applications.application_date) <= ' . $month . '
                    and emptyings.service_provider_id=' . $service_provider_id . '
                    GROUP BY APPLICATIONS.ward
                   )

                   select  applicationCount, emptyCount, sludgeCount, totalCost, award, sCount from application a
	                left join emptying e ON a.award = e.eward ORDER BY award; ';

            $wardData = DB::Select($wardMonthlyquery);
            // converts month number to mont name
            $dateObj   = DateTime::createFromFormat('!m', $month);
            $monthName = $dateObj->format('F');

            return PDF::loadView('fsm.applications.monthly_report', compact('year', 'monthName', 'monthWisecount', 'yearCount', 'wardData'))->inline('Monthly Report.pdf');
        }
    }
    /**
     * Generate a PDF report for a specific application.
     *
     * @param int $id The ID of the application.
     * @return \Illuminate\Http\Response The generated PDF report.
     */
    public function getApplicationReport($id)
    {

        $application = Application::find($id);
        $containment = Containment::query()
            ->leftJoin('fsm.containment_types as ct', 'ct.id', '=', 'containments.type_id')
            ->select('containments.*', 'ct.type')
            ->where('containments.id', $application->containment_id)
            ->whereNull('containments.deleted_at')
            ->get();

        return PDF::View('fsm.applications.application_report', compact('application', 'containment'))->inline('Application Report.pdf');
    }
    public function getBuildingDetails(Request $request)
    {
        try {
            // Fetch building by BIN
            $building = Building::where('bin', '=', $request->bin)->firstOrFail();


            // Use the `getContainmentIds` function to fetch filtered containment IDs
            $containmentIds = $building->containments->pluck('id');
            $containment_size = Containment::where('id', $building->containments->first()->id)->pluck('size')->first();
    
            // Fetch additional related data
            $owner = $building->owners;
            $road = $building->roadlines;
            $application = Application::orderBy('id', 'DESC')->where('bin', $request->bin)->first();

            // Check if containments are empty


            // Debug the status value


            // Return the response
            return JsonResponse::fromJsonString(json_encode([
                'test' => $road,
                "customer_name" => $owner->owner_name ?? null,
                "customer_gender" => $owner->owner_gender ?? null,
                "customer_contact" => $owner->owner_contact ?? null,
                "road" => $road->code ?? null,
                "building_accessible" => $building->desludging_vehicle_accessible ?? null,
                "ward" => $building->ward ?? null,
                "containments" => $containmentIds,
                "household_served" => $building->household_served ?? null,
                "population_served" => $building->population_served ?? null,
                "toilet_count" => $building->toilet_count ?? null,
                "status" => !empty($containmentIds),
                "containment_size" => $containment_size

            ]), 200);
        } catch (Throwable $e) {
            // Handle exceptions
            return JsonResponse::fromJsonString(json_encode([
                "error" => __("Error getting building details!"),
                "details" => $e->getMessage()
            ]), 500);
        }
    }

    public function ServiceProviderOrder(?int $capacity = null): array
    {
        $capInput = $capacity ?? request()->input('capacity', null);
        $desiredCapacity = ($capInput !== null && $capInput !== '') ? (int)$capInput : null;
        $useCapacity = $desiredCapacity !== null;

        $query = ServiceProviderSequence::query()
            ->where('current_sequence', true)
            ->orderBy('sequence_order', 'asc');

        if ($useCapacity) {
            $query->where('desludging_vehicle_size', $desiredCapacity);
        }

        return $query->pluck('service_provider_id')->toArray();
    }


    public function getNextServiceProviderId(?int $capacity = null): ?int
    {
        $capInput = request()->input('capacity', null);

        $query = ServiceProviderSequence::where('current_sequence', true);

        if (!empty($capInput)) {
            $query->where('desludging_vehicle_size', (int) $capInput);
        }
        $active = $query->first();
        return $active?->service_provider_id;
    }

    public function getNextServiceProviderName(?string $capacity = null): ?string
    {
        $capInput = request()->input('capacity', null);

        $query = ServiceProviderSequence::where('current_sequence', true)->with('service_provider');
        if (!empty($capInput)) {
            $query->where('desludging_vehicle_size', (int) $capInput);
        }

        $active = $query->first();


        return $active?->service_provider?->company_name;
    }

    public function rotateServiceProviderSequence(?int $capacity = null)
    {
        $query = ServiceProviderSequence::orderBy('sequence_order');

        if (!is_null($capacity)) {
            $query->where('desludging_vehicle_size', $capacity);
        }

        $sequences = $query->get();

        if ($sequences->isEmpty()) {
            return;
        }

        // Find current
        $currentIndex = $sequences->search(fn($item) => $item->current_sequence === true);

        // Reset all
        foreach ($sequences as $item) {
            $item->current_sequence = false;
            $item->save();
        }

        // Determine next
        $nextIndex = ($currentIndex === false || $currentIndex === $sequences->count() - 1)
            ? 0
            : $currentIndex + 1;

        $sequences[$nextIndex]->current_sequence = true;
        $sequences[$nextIndex]->save();
    }

    public function resolveAnf(int $applicationId, string $bin, string $containmentCode): void
    {
        $application = Application::findOrFail($applicationId);

        if (!$application->is_anf) {
            throw new \Exception(__('This application is not an Address Not Found (ANF) application or has already been resolved.'));
        }

        // Find containment by code field
        $containment = Containment::where('id', $containmentCode)->firstOrFail();

        // Check no duplicate containment in applications table (covers both not started and in-progress multi-trip)
        $alreadyExists = Application::where('containment_id', $containment->id)
            ->whereIn('emptying_status', [0, 1])
            ->whereNull('deleted_at')
            ->where('id', '!=', $applicationId)
            ->exists();

        if ($alreadyExists) {
            throw new \Exception(__('Error! This containment already has a running application.'));
        }

        $building = Building::where('bin', $bin)->firstOrFail();

        // Ensure the containment belongs to the selected building
        if (!$building->containments()->where('containments.id', $containment->id)->exists()) {
            throw new \Exception(__('The selected containment does not belong to this building.'));
        }

        $owner    = $building->owners;

        $application->bin              = $building->bin;
        $application->road_code        = $building->road_code;
        $application->ward             = $building->ward;
        $application->containment_id   = $containment->id;
        $application->customer_name    = $owner->owner_name    ?? $application->customer_name;
        $application->customer_contact = $owner->owner_contact ?? $application->customer_contact;
        $application->customer_gender  = $owner->owner_gender  ?? $application->customer_gender;
        $application->is_anf           = false;

        $application->save();
    }
}
