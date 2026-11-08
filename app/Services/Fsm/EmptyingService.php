<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Services\Fsm;

use DB;
use App\Classes\FormField;
use App\Models\Fsm\EmployeeInfo;
use App\Models\Fsm\Emptying;
use App\Models\Fsm\TreatmentPlant;
use App\Models\Fsm\VacutugType;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use DateTimeZone;
use DateTime;
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
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Venturecraft\Revisionable\Revision;
use DataTables;
use Throwable;
use App\Models\Notification;
use App\Models\User;

class EmptyingService
{

    protected $session;
    protected $instance;
    protected string $indexAction;
    protected $createRoute, $exportRoute;
    protected $createPartialForm, $createFormFields, $createFormAction;
    protected $showFormFields, $editFormFields, $filterFormFields;

    /**
     * Constructs a new Emptying object.
     *
     *
     */
    public function __construct()
    {
        /*Session code
        ....
         here*/

        $this->createPartialForm = 'fsm.emptying.partial-form';
        $this->createFormAction = route('emptying.store');
        $this->indexAction = route('emptying.index');
        $this->createRoute = route('emptying.create');
        $this->exportRoute = route('emptying.export');

        $this->filterFormFields = [
            [
                new FormField(
                    label: __('Application ID'),
                    labelFor: 'application_id',
                    labelClass: 'control-label col-md-2',
                    inputType: 'number',
                    inputId: 'application_id',
                    placeholder: __('Application ID'),
                    oninput: "this.value = this.value.replace(/[^0-9]/g, '')"
                ),
                new FormField(
                    label: __('Containment ID'),
                    labelFor: 'containment_id',
                    labelClass: 'control-label col-md-2',
                    inputType: 'text',
                    inputId: 'containment_id',
                    placeholder: __('Containment ID'),
                    oninput: "this.value = this.value.replace(/[^a-zA-Z0-9]/g, '')"
                ),
                new FormField(
                    label: __('Emptied Date From'),
                    labelFor: 'date_from',
                    labelClass: 'control-label col-md-2',
                    inputType: 'date',
                    inputId: 'date_from',
                    placeholder: __('Date From'),
                ),
            ],
            [


                new FormField(
                    label: __('Emptied Date To'),
                    labelFor: 'date_to',
                    labelClass: 'control-label col-md-2',
                    inputType: 'date',
                    inputId: 'date_to',
                    required: true,
                    placeholder: __('Date To'),
                ),
            ],
        ];
    }

    /**
     * Get form fields for creating emptying.
     *
     * @return array
     */
    public function getCreateFormFields($id)
    {
        $application = Application::findOrFail($id);
        $containment = Containment::find($application->containment_id);
        $exists = Emptying::where('application_id', $application->id)->exists();

        if ($exists) {
            $emptying = Emptying::where('application_id', $application->id)->first();

            $this->createFormFields = [
                new FormField(
                    label: __('Application ID'),
                    labelFor: 'application_id',
                    inputType: 'textarea',
                    inputId: 'application_id',
                    inputValue: $emptying->application_id,
                    hidden: true
                ),
                new FormField(
                    label: __('Containment ID'),
                    labelFor: 'containment_id',
                    inputType: 'text',
                    inputId: 'containment_id',
                    inputValue: is_null($application->buildings) ? "-" : $application->buildings->containments->pluck('id'),
                    hidden: true
                ),
                new FormField(
                    label: __('Date'),
                    labelFor: 'emptied_date',
                    inputType: 'label',
                    inputId: 'emptied_date',
                    labelValue: ($emptying->emptied_date)->format('m/d/Y'),
                    placeholder: __('Date'),
                ),
                new FormField(
                    label: __('Service Receiver Name'),
                    labelFor: 'service_receiver_name',
                    inputType: 'text',
                    inputId: 'service_receiver_name',
                    inputValue: $emptying->service_receiver_name,
                    disabled: true,
                    required: true,
                    placeholder: __('Service Receiver Name'),
                ),
                new FormField(
                    label: __('Service Receiver Gender'),
                    labelFor: 'service_receiver_gender',
                    inputType: 'select',
                    inputId: 'service_receiver_gender',
                    selectedValue: $emptying->service_receiver_gender,
                    disabled: true,
                    selectValues: ["Male" => "Male", "Female" => "Female", "Others" => "Others"],
                    required: true,
                    placeholder: __('Service Receiver Gender'),
                ),
                new FormField(
                    label: __('Service Receiver Contact Number'),
                    labelFor: 'service_receiver_contact',
                    inputType: 'text',
                    inputId: 'service_receiver_contact',
                    inputValue: $emptying->service_receiver_contact,
                    disabled: true,
                    required: true,
                    placeholder: __('Service Receiver Contact Number'),
                    oninput: "validateOwnerContactInput(this)",
                ),

                new FormField(
                    label: __('Reason for Emptying'),
                    labelFor: 'emptying_reason',
                    inputType: 'text',
                    inputId: 'emptying_reason',
                    inputValue: ($emptying->emptying_reason),
                    required: true,
                    disabled: true,
                    placeholder: __('Reason for Emptying'),
                ),
                new FormField(
                    label: __('No. of Trips'),
                    labelFor: 'trip_count',
                    inputType: 'text',
                    inputId: 'trip_count',
                    required: true,
                    inputValue: ($application->trip_count),
                    placeholder: __('No. of Trips'),
                    oninput: "this.value = this.value.replace(/[^0-9]/g, '').replace(/^0+/, '1')"
                ),
                new FormField(
                    label: __('Current Trip No.'),
                    labelFor: 'trip_no',
                    inputType: 'text',
                    inputId: 'trip_no',
                    required: true,
                    inputValue: ($emptying->trip_no) + 1,
                    placeholder: __('Current Trip No.'),
                    disabled: true,
                ),
                new FormField(
                    label: __('Sludge Volume (m³)'),
                    labelFor: 'volume_of_sludge',
                    inputType: 'text',
                    inputId: 'volume_of_sludge',
                    required: true,
                    placeholder: __('Sludge Volume (m³)'),
                    oninput: "this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1'); this.value = this.value < 0 || this.value.startsWith('-') ? '' : this.value;"
                ),
                new FormField(
                    label: __('Desludging Vehicle Number Plate'),
                    labelFor: 'desludging_vehicle_id',
                    inputType: 'select',
                    inputId: 'desludging_vehicle_id',
                    selectValues: !(Auth::user()->hasRole('Super Admin') && Auth::user()->hasRole('Municipality - Super Admin')) ?
                        VacutugType::Operational()->where('service_provider_id', $application->service_provider_id)->pluck('license_plate_number', 'id')->toArray() : VacutugType::Operational()->pluck('license_plate_number', 'id')->toArray(),
                    selectedValue: $emptying->desludging_vehicle_id,
                    required: true,
                    disabled: true,
                    placeholder: __('Desludging Vehicle Number Plate'),
                ),

                new FormField(
                    label: __('Driver Name'),
                    labelFor: 'driver',
                    inputType: 'select',
                    inputId: 'driver',
                    selectValues: !(Auth::user()->hasRole('Super Admin') && Auth::user()->hasRole('Municipality - Super Admin')) ?
                        EmployeeInfo::Active()->where('service_provider_id', $application->service_provider_id)->where('employee_type', '=', 'Driver')->pluck('name', 'id')->toArray()
                        : EmployeeInfo::Active()->where('employee_type', '=', 'Driver')->pluck('name', 'id')->toArray(),
                    selectedValue: $emptying->driver,
                    required: true,
                    disabled: true,
                    placeholder: __('Driver Name'),
                ),
                new FormField(
                    label: __('Emptier 1 Name'),
                    labelFor: 'emptier1',
                    inputType: 'select',
                    inputId: 'emptier1',
                    selectedValue: $emptying->emptier1,
                    selectValues: !(Auth::user()->hasRole('Super Admin') && Auth::user()->hasRole('Municipality - Super Admin')) ?
                        EmployeeInfo::Active()->where('service_provider_id', $application->service_provider_id)->where('employee_type', '=', 'Cleaner/Emptier')->pluck('name', 'id')->toArray()
                        : EmployeeInfo::Active()->where('employee_type', '=', 'Cleaner/Emptier')->pluck('name', 'id')->toArray(),
                    required: true,
                    disabled: true,
                    placeholder: __('Emptier 1 Name'),
                ),
                new FormField(
                    label: __('Emptier 2 Name'),
                    labelFor: 'emptier2',
                    inputType: 'select',
                    inputId: 'emptier2',
                    disabled: true,
                    selectedValue: $emptying->emptier2,
                    selectValues: !(Auth::user()->hasRole('Super Admin') && Auth::user()->hasRole('Municipality - Super Admin')) ?
                        EmployeeInfo::Active()->where('service_provider_id', $application->service_provider_id)->where('employee_type', '=', 'Cleaner/Emptier')->pluck('name', 'id')->toArray()
                        : EmployeeInfo::Active()->where('employee_type', '=', 'Cleaner/Emptier')->pluck('name', 'id')->toArray(),
                    placeholder: __('Emptier 2 Name'),
                ),
                new FormField(
                    label: __('Start Time'),
                    labelFor: 'start_time',
                    inputType: 'time',
                    inputId: 'start_time',
                    required: true,
                    placeholder: __('Start Time'),

                ),
                new FormField(
                    label: __('End Time'),
                    labelFor: 'end_time',
                    inputType: 'time',
                    inputId: 'end_time',
                    required: true,
                    placeholder: __('End Time'),
                ),

                new FormField(
                    label: __('Disposal Place'),
                    labelFor: 'treatment_plant_id',
                    inputType: 'select',
                    inputId: 'treatment_plant_id',
                    selectValues: TreatmentPlant::Operational()->whereIn('type', [3, 4])->pluck('name', 'id')->toArray(),
                    selectedValue: $emptying->treatment_plant_id,
                    required: true,
                    placeholder: __('Disposal Place'),
                    disabled: true,

                ),

                new FormField(
                    label: __('Comments (if any)'),
                    labelFor: 'comments',
                    inputType: 'textarea',
                    inputId: 'comments',
                    inputValue: $emptying->comments,
                    placeholder: __('Comments (if any)'),
                ),

            ];

            if ($application->trip_count == $emptying->trip_no + 1) {
                $folderPathBuildJpg = public_path('/storage/emptyings/houses/' . $application->bin . '.jpg');
                $folderPathBuildJpeg = public_path('/storage/emptyings/houses/' . $application->bin . '.jpgeg');
                $imagePathBuildJpg = 'storage/emptyings/houses/' . $application->bin . '.jpg';
                $imagePathBuildJpeg = 'storage/emptyings/houses/' . $application->bin . '.jpeg';
                if (file_exists($folderPathBuildJpg) == true) {
                    $imageBuildSrc = asset($imagePathBuildJpg);
                    $labelNameBuild = $application->bin . '.jpg';
                } elseif (file_exists($folderPathBuildJpeg) == true) {
                    $imageBuildSrc = asset($imagePathBuildJpeg);
                    $labelNameBuild = $application->bin . '.jpeg';
                } else {
                    $imageBuildSrc = false;
                    $labelNameBuild = "No House Image";
                }
                // receipt
                $folderPathReceipt = public_path('/storage/emptyings/receipts/' . $emptying->receipt_image);
                $imagePathReceipt = 'storage/emptyings/receipts/' . $emptying->receipt_image;
                if (file_exists($folderPathReceipt) == true) {
                    $imageReceiptSrc = asset($imagePathReceipt);
                } else {
                    $imageReceiptSrc = false;
                }


                $this->createFormFields[] = new FormField(
                    label: __('Receipt Number'),
                    labelFor: 'receipt_number',
                    inputType: 'text',
                    inputId: 'receipt_number',
                    required: true,
                    placeholder: __('Receipt Number'),
                    hidden: false
                );

                $this->createFormFields[] = new FormField(
                    label: __('Total Cost'),
                    labelFor: 'total_cost',
                    inputType: 'text',
                    inputId: 'total_cost',
                    required: true,
                    placeholder: __('Total Cost'),
                    oninput: "this.value = this.value.replace(/[^0-9.]/g,'').replace(/(\..*)\./g,'$1'); this.value = this.value < 0 || this.value.startsWith('-') ? '' : this.value;"
                );

                $this->createFormFields[] = new FormField(
                    label: __('House Image'),
                    labelFor: 'house_image',
                    inputType: 'file_upload',
                    inputId: 'house_image',
                    required: true,
                    placeholder: __('House Image'),
                );

                $this->createFormFields[] = new FormField(
                    label: __('Receipt Image'),
                    labelFor: 'receipt_image',
                    inputType: 'file_upload',
                    inputId: 'receipt_image',
                    required: true,
                    placeholder: __('Receipt Image'),
                );
            }
        } else {

            $this->createFormFields = [
                new FormField(
                    label: __('Application ID'),
                    labelFor: 'application_id',
                    inputType: 'text',
                    inputId: 'application_id',
                    inputValue: $id,
                    hidden: true
                ),
                new FormField(
                    label: __('Containment ID'),
                    labelFor: 'containment_id',
                    inputType: 'text',
                    inputId: 'containment_id',
                    inputValue: is_null($application->buildings) ? "-" : $application->buildings->containments->pluck('id'),
                    hidden: true
                ),
                new FormField(
                    label: __('Date'),
                    labelFor: 'emptied_date',
                    inputType: 'text',
                    inputId: 'emptied_date',
                    inputValue: now()->format('Y-m-d'),
                    placeholder: __('Date'),
                    disabled: true,
                ),
                new FormField(
                    label: __('Service Receiver Name'),
                    labelFor: 'service_receiver_name',
                    inputType: 'text',
                    inputId: 'service_receiver_name',
                    required: true,
                    inputValue: $application->applicant_name,
                    placeholder: __('Service Receiver Name'),
                ),
                new FormField(
                    label: __('Service Receiver Gender'),
                    labelFor: 'service_receiver_gender',
                    inputType: 'select',
                    inputId: 'service_receiver_gender',
                    selectedValue: $application->applicant_gender,
                    selectValues: [
                        'Male'   => 'Male',
                        'Female' => 'Female',
                        'Others' => 'Others',
                    ],
                    required: true,
                ),

                new FormField(
                    label: __('Service Receiver Contact Number'),
                    labelFor: 'service_receiver_contact',
                    inputType: 'text',
                    inputId: 'service_receiver_contact',
                    inputValue: $application->applicant_contact,
                    required: true,
                    placeholder: __('Service Receiver Contact Number'),
                    oninput: "validateOwnerContactInput(this)",
                ),
                new FormField(
                    label: __('Reason for Emptying'),
                    labelFor: 'emptying_reason',
                    inputType: 'textarea',
                    inputId: 'emptying_reason',
                    required: true,
                    placeholder: __('Reason for Emptying'),
                ),
                new FormField(
                    label: __('No. of Trips'),
                    labelFor: 'trip_count',
                    inputType: 'text',
                    inputId: 'trip_count',
                    required: true,
                    placeholder: __('No. of Trips'),
                    oninput: "this.value = this.value.replace(/[^0-9]/g, '').replace(/^0+/, '1')"
                ),
                new FormField(
                    label: __('Current Trip No.'),
                    labelFor: 'trip_no',
                    inputType: 'text',
                    inputId: 'trip_no',
                    required: true,
                    placeholder: __('Current Trip No.'),
                    disabled: true,
                    inputValue: 1
                ),
                new FormField(
                    label: __('Sludge Volume (m³)'),
                    labelFor: 'volume_of_sludge',
                    inputType: 'text',
                    inputId: 'volume_of_sludge',
                    required: true,
                    placeholder: __('Sludge Volume (m³)'),
                    oninput: "this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1'); this.value = this.value < 0 || this.value.startsWith('-') ? '' : this.value;"
                ),
                new FormField(
                    label: __('Desludging Vehicle Number Plate'),
                    labelFor: 'desludging_vehicle_id',
                    inputType: 'select',
                    inputId: 'desludging_vehicle_id',
                    selectValues: !(Auth::user()->hasRole('Super Admin') && Auth::user()->hasRole('Municipality - Super Admin')) ?
                        VacutugType::Operational()->where('service_provider_id', $application->service_provider_id)->pluck('license_plate_number', 'id')->toArray()
                        : VacutugType::Operational()->pluck('license_plate_number', 'id')->toArray(),
                    required: true,
                    placeholder: __('Desludging Vehicle Number Plate'),
                ),

                new FormField(
                    label: __('Driver Name'),
                    labelFor: 'driver',
                    inputType: 'select',
                    inputId: 'driver',
                    selectValues: !(Auth::user()->hasRole('Super Admin') && Auth::user()->hasRole('Municipality - Super Admin')) ?
                        EmployeeInfo::Active()->where('service_provider_id', $application->service_provider_id)->where('employee_type', '=', 'Driver')->pluck('name', 'id')->toArray()
                        : EmployeeInfo::Active()->where('employee_type', '=', 'Driver')->pluck('name', 'id')->toArray(),
                    required: true,
                    placeholder: __('Driver Name'),
                ),
                new FormField(
                    label: __('Emptier 1 Name'),
                    labelFor: 'emptier1',
                    inputType: 'select',
                    inputId: 'emptier1',
                    selectValues: !(Auth::user()->hasRole('Super Admin') && Auth::user()->hasRole('Municipality - Super Admin')) ?
                        EmployeeInfo::Active()->where('service_provider_id', $application->service_provider_id)->where('employee_type', '=', 'Cleaner/Emptier')->pluck('name', 'id')->toArray()
                        : EmployeeInfo::Active()->where('employee_type', '=', 'Cleaner/Emptier')->pluck('name', 'id')->toArray(),
                    required: true,
                    placeholder: __('Emptier 1 Name'),
                ),
                new FormField(
                    label: __('Emptier 2 Name'),
                    labelFor: 'emptier2',
                    inputType: 'select',
                    inputId: 'emptier2',
                    selectValues: !(Auth::user()->hasRole('Super Admin') && Auth::user()->hasRole('Municipality - Super Admin')) ?
                        EmployeeInfo::Active()->where('service_provider_id', $application->service_provider_id)->where('employee_type', '=', 'Cleaner/Emptier')->pluck('name', 'id')->toArray()
                        : EmployeeInfo::Active()->where('employee_type', '=', 'Cleaner/Emptier')->pluck('name', 'id')->toArray(),
                    placeholder: __('Emptier 2 Name'),
                ),
                new FormField(
                    label: __('Start Time'),
                    labelFor: 'start_time',
                    inputType: 'time',
                    inputId: 'start_time',
                    required: true,
                    placeholder: __('Start Time'),
                ),
                new FormField(
                    label: __('End Time'),
                    labelFor: 'end_time',
                    inputType: 'time',
                    inputId: 'end_time',
                    required: true,
                    placeholder: __('End Time'),
                ),

                new FormField(
                    label: __('Disposal Place'),
                    labelFor: 'treatment_plant_id',
                    inputType: 'select',
                    inputId: 'treatment_plant_id',
                    selectValues: TreatmentPlant::Operational()->whereIn('type', [3, 4])->pluck('name', 'id')->toArray(),
                    required: true,
                    placeholder: __('Disposal Place'),
                ),

                new FormField(
                    label: __('Receipt Number'),
                    labelFor: 'receipt_number',
                    inputType: 'text',
                    inputId: 'receipt_number',
                    required: true,
                    placeholder: __('Receipt Number'),

                ),
                new FormField(
                    label: __('Total Cost'),
                    labelFor: 'total_cost',
                    inputType: 'text',
                    inputId: 'total_cost',
                    required: true,
                    placeholder: __('Total Cost'),
                    oninput: "this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1'); this.value = this.value < 0 || this.value.startsWith('-') ? '' : this.value;"
                ),
                new FormField(
                    label: __('House Image'),
                    labelFor: 'house_image',
                    inputType: 'file_upload',
                    inputId: 'house_image',
                    required: true,
                    placeholder: __('House Image'),
                ),
                new FormField(
                    label: __('Receipt Image'),
                    labelFor: 'receipt_image',
                    inputType: 'file_upload',
                    inputId: 'receipt_image',
                    required: true,
                    placeholder: __('Receipt Image'),
                ),
                new FormField(
                    label: __('Comments (if any)'),
                    labelFor: 'comments',
                    inputType: 'textarea',
                    inputId: 'comments',
                    placeholder: __('Comments (if any)'),
                )
            ];
        }

        return $this->createFormFields;
    }

    /**
     * Get form fields for showing emptying.
     *
     * @return array
     */
    public function getShowFormFields($emptying)
    {
        $application = Application::findOrFail($emptying->application_id);
        $containment = Containment::findOrFail($application->containment_id);
        // houses
        $folderPathBuildJpg = public_path('/storage/emptyings/houses/' . $application->bin . '.jpg');
        $folderPathBuildJpeg = public_path('/storage/emptyings/houses/' . $application->bin . '.jpgeg');
        $imagePathBuildJpg = 'storage/emptyings/houses/' . $application->bin . '.jpg';
        $imagePathBuildJpeg = 'storage/emptyings/houses/' . $application->bin . '.jpeg';
        if (file_exists($folderPathBuildJpg) == true) {
            $imageBuildSrc = asset($imagePathBuildJpg);
            $labelNameBuild = $application->bin . '.jpg';
        } elseif (file_exists($folderPathBuildJpeg) == true) {
            $imageBuildSrc = asset($imagePathBuildJpeg);
            $labelNameBuild = $application->bin . '.jpeg';
        } else {
            $imageBuildSrc = false;
            $labelNameBuild = "No House Image";
        }
        // receipt
        $folderPathReceipt = public_path('/storage/emptyings/receipts/' . $emptying->receipt_image);
        $imagePathReceipt = 'storage/emptyings/receipts/' . $emptying->receipt_image;
        if (file_exists($folderPathReceipt) == true) {
            $imageReceiptSrc = asset($imagePathReceipt);
        } else {
            $imageReceiptSrc = false;
        }
        $this->showFormFields = [
            new FormField(
                label: __('Application ID'),
                labelFor: 'application_id',
                inputType: 'label',
                inputId: 'application_id',
                labelValue: $emptying->application_id ?? __('Application ID'),
            ),
            new FormField(
                label: __('Date'),
                labelFor: 'emptied_date',
                inputType: 'label',
                inputId: 'emptied_date',
                labelValue: date('m/d/Y', strtotime($emptying->emptied_date)) ?? __('Date'),
            ),
            new FormField(
                label: __('Service Receiver Name'),
                labelFor: 'service_receiver_name',
                inputType: 'label',
                inputId: 'service_receiver_name',
                labelValue: $emptying->service_receiver_name ?? __('Service Receiver Name')
            ),
            new FormField(
                label: __('Service Receiver Gender'),
                labelFor: 'service_receiver_gender',
                inputType: 'label',
                inputId: 'service_receiver_gender',
                labelValue: $emptying->service_receiver_gender ?? __('Service Receiver Gender')
            ),
            new FormField(
                label: __('Service Receiver Contact Number'),
                labelFor: 'service_receiver_contact',
                inputType: 'label',
                inputId: 'service_receiver_contact',
                labelValue: $emptying->service_receiver_contact ?? __('Service Receiver Contact Number')
            ),
            new FormField(
                label: __('Reason for Emptying'),
                labelFor: 'emptying_reason',
                inputType: 'label',
                inputId: 'emptying_reason',
                labelValue: $emptying->emptying_reason ?? __('Reason for Emptying')

            ),
            new FormField(
                label: __('No. of Trips'),
                labelFor: 'trip_count',
                inputType: 'label',
                inputId: 'trip_count',
                labelValue: $application->trip_count ?? __('No. of Trips')
            ),
            new FormField(
                label: __('Current Trip No.'),
                labelFor: 'trip_no',
                inputType: 'label',
                inputId: 'trip_no',
                labelValue: $emptying->trip_no ?? __('Current Trip No.')
            ),
            new FormField(
                label: __('Sludge Volume (m³)'),
                labelFor: 'volume_of_sludge',
                inputType: 'label',
                inputId: 'volume_of_sludge',
                labelValue: $emptying->volume_of_sludge ?? __('Sludge Volume (m³)')
            ),

            new FormField(
                label: __('Desludging Vehicle Number Plate'),
                labelFor: 'desludging_vehicle_id',
                inputType: 'label',
                inputId: 'desludging_vehicle_id',
                labelValue: $emptying->vacutug()->withTrashed()->first()->license_plate_number ?? __('Desludging Vehicle Number Plate')
            ),
            new FormField(
                label: __('Disposal Place'),
                labelFor: 'treatment_plant_id',
                inputType: 'label',
                inputId: 'treatment_plant_id',
                labelValue: $emptying->treatmentPlant()->withTrashed()->first()->name ?? __('Disposal Place')
            ),
            new FormField(
                label: __('Driver Name'),
                labelFor: 'driver',
                inputType: 'label',
                inputId: 'driver',
                labelValue: EmployeeInfo::withTrashed()->where('id', $emptying->driver)->first()->name ?? __('Driver Name')
            ),
            new FormField(
                label: __('Emptier 1 Name'),
                labelFor: 'emptier1',
                inputType: 'label',
                labelValue: EmployeeInfo::withTrashed()->where('id', $emptying->emptier1)->first()->name ?? __('Emptier 1 Name')
            ),
            new FormField(
                label: __('Emptier 2 Name'),
                labelFor: 'emptier2',
                inputType: 'label',
                inputId: 'emptier2',
                labelValue: EmployeeInfo::withTrashed()->where('id', $emptying->emptier2)->first()->name ?? __('Emptier 2 Name')
            ),
            new FormField(
                label: __('Total Time (mins)'),
                labelFor: 'total_time',
                inputType: 'label',
                inputId: 'total_time',
                labelValue: $emptying->total_time ?? __('Total Time (mins)')
            ),
            new FormField(
                label: __('Receipt Number'),
                labelFor: 'receipt_number',
                inputType: 'label',
                labelValue: $emptying->receipt_number ?? __('Receipt Number')
            ),
            new FormField(
                label: __('Total Cost'),
                labelFor: 'total_cost',
                inputType: 'label',
                inputId: 'total_cost',
                labelValue: $emptying->total_cost ?? __('Total Cost')
            ),
            new FormField(
                label: __('House Image'),
                labelFor: 'house_image',
                inputType: 'file_viewer',
                inputId: 'house_image',
                labelValue: $labelNameBuild,
                fileUrl: $imageBuildSrc
            ),
            new FormField(
                label: __('Receipt Image'),
                labelFor: 'receipt_image',
                inputType: 'file_viewer',
                inputId: 'receipt_image',
                labelValue: !($imageReceiptSrc) ? "No Receipt Image" : $emptying->receipt_image,
                fileUrl: $imageReceiptSrc,
            ),
            new FormField(
                label: __('Comments (if any)'),
                labelFor: 'comments',
                inputType: 'label',
                inputId: 'comments',
                labelValue: $emptying->comments ?? __('Comments (if any)'),

            ),
        ];


        return $this->showFormFields;
    }

    /**
     * Get form fields for editing emptying.
     *
     * @return array
     */
    public function getEditFormFields($emptying)
    {
        $application = Application::findOrFail($emptying->application_id);
        $containment = Containment::findOrFail($application->containment_id);


        if (!Auth::user()->hasRole('Super Admin') && !Auth::user()->hasRole('Municipality - Super Admin')) {
            if ($application->emptying_status && $application->sludge_collection_status) {
                $selectValueVacutugType = VacutugType::withTrashed()->where('service_provider_id', $application->service_provider_id)->pluck('license_plate_number', 'id')->toArray();
            } else {
                $selectValueVacutugType = VacutugType::Operational()->where('service_provider_id', $application->service_provider_id)->pluck('license_plate_number', 'id')->toArray();
            }
        } else {
            if ($application->emptying_status && $application->sludge_collection_status) {
                $selectValueVacutugType = VacutugType::withTrashed()->pluck('license_plate_number', 'id')->toArray();
            } else {
                $selectValueVacutugType = VacutugType::Operational()->pluck('license_plate_number', 'id')->toArray();
            }
        }
        if (!Auth::user()->hasRole('Super Admin') && !Auth::user()->hasRole('Municipality - Super Admin')) {
            if ($application->emptying_status && $application->sludge_collection_status) {
                $selectValuesDriver = EmployeeInfo::withTrashed()->where('service_provider_id', $application->service_provider_id)->where('employee_type', '=', 'Driver')->pluck('name', 'id')->toArray();
            } else {
                $selectValuesDriver =  EmployeeInfo::Active()->where('service_provider_id', $application->service_provider_id)->where('employee_type', '=', 'Driver')->pluck('name', 'id')->toArray();
            }
        } else {
            if ($application->emptying_status && $application->sludge_collection_status) {
                $selectValuesDriver = EmployeeInfo::withTrashed()->where('employee_type', '=', 'Driver')->pluck('name', 'id')->toArray();
            } else {
                $selectValuesDriver =  EmployeeInfo::Active()->where('employee_type', '=', 'Driver')->pluck('name', 'id')->toArray();
            }
        }
        if (!Auth::user()->hasRole('Super Admin') && !Auth::user()->hasRole('Municipality - Super Admin')) {
            if ($application->emptying_status && $application->sludge_collection_status) {
                $selectValuesCleanerEmptier = EmployeeInfo::withTrashed()->where('service_provider_id', $application->service_provider_id)->where('employee_type', '=', 'Cleaner/Emptier')->pluck('name', 'id')->toArray();
            } else {
                $selectValuesCleanerEmptier = EmployeeInfo::Active()->where('service_provider_id', $application->service_provider_id)->where('employee_type', '=', 'Cleaner/Emptier')->pluck('name', 'id')->toArray();
            }
        } else {
            if ($application->emptying_status && $application->sludge_collection_status) {
                $selectValuesCleanerEmptier = EmployeeInfo::withTrashed()->where('employee_type', '=', 'Cleaner/Emptier')->pluck('name', 'id')->toArray();
            } else {
                $selectValuesCleanerEmptier = EmployeeInfo::Active()->where('employee_type', '=', 'Cleaner/Emptier')->pluck('name', 'id')->toArray();
            }
        }
        if ((($application->trip_count == $application->emptying->trip_no) && $application->sludge_collection_status == 1) || !($application->emptying->trip_no === 1)) {
            $this->editFormFields = [
                new FormField(
                    label: __('Application ID'),
                    labelFor: 'application_id',
                    inputType: 'textarea',
                    inputId: 'application_id',
                    inputValue: $emptying->application_id,
                    hidden: true
                ),
                new FormField(
                    label: __('Containment ID'),
                    labelFor: 'containment_id',
                    inputType: 'text',
                    inputId: 'containment_id',
                    inputValue: is_null($application->buildings) ? "-" : $application->buildings->containments->pluck('id'),
                    hidden: true
                ),
                new FormField(
                    label: __('Date'),
                    labelFor: 'emptied_date',
                    inputType: 'label',
                    inputId: date('m/d/Y', strtotime($emptying->emptied_date)),
                    placeholder: __('Date'),
                    disabled: true
                ),
                new FormField(
                    label: __('Service Receiver Name'),
                    labelFor: 'service_receiver_name',
                    inputType: 'text',
                    inputId: 'service_receiver_name',
                    inputValue: $emptying->service_receiver_name,
                    required: true,
                    placeholder: __('Service Receiver Name'),
                    disabled: true

                ),
                new FormField(
                    label: __('Service Receiver Gender'),
                    labelFor: 'service_receiver_gender',
                    inputType: 'select',
                    inputId: 'service_receiver_gender',
                    selectedValue: $emptying->service_receiver_gender,
                    selectValues: ["Male" => "Male", "Female" => "Female", "Others" => "Others"],
                    required: true,
                    placeholder: __('Service Receiver Gender'),
                    disabled: true

                ),
                new FormField(
                    label: __('Service Receiver Contact Number'),
                    labelFor: 'service_receiver_contact',
                    inputType: 'text',
                    inputId: 'service_receiver_contact',
                    inputValue: $emptying->service_receiver_contact,
                    required: true,
                    placeholder: __('Service Receiver Contact Number'),
                    oninput: "validateOwnerContactInput(this)",
                    disabled: true

                ),

                new FormField(
                    label: __('Reason for Emptying'),
                    labelFor: 'emptying_reason',
                    inputType: 'textarea',
                    inputId: 'emptying_reason',
                    inputValue: $emptying->emptying_reason,
                    required: true,
                    placeholder: __('Reason for Emptying'),
                    disabled: true
                ),
                new FormField(
                    label: __('No. of Trips'),
                    labelFor: 'trip_count',
                    inputType: 'text',
                    inputId: 'trip_count',
                    inputValue: $application->trip_count,
                    required: true,
                    placeholder: __('No. of Trips'),
                    oninput: "this.value = this.value.replace(/[^0-9]/g, '').replace(/^0+/, '1')"
                ),

                new FormField(
                    label: __('Current Trip No.'),
                    labelFor: 'trip_no',
                    inputType: 'text',
                    inputId: 'trip_no',
                    inputValue: $emptying->trip_no,
                    required: true,
                    placeholder: __('Current Trip No.'),
                    disabled: true
                ),
                new FormField(
                    label: __('Sludge Volume (m³)'),
                    labelFor: 'volume_of_sludge',
                    inputType: 'text',
                    inputId: 'volume_of_sludge',
                    inputValue: $emptying->volume_of_sludge,
                    required: true,
                    placeholder: __('Sludge Volume (m³)'),
                ),

                new FormField(
                    label: __('Desludging Vehicle Number Plate'),
                    labelFor: 'desludging_vehicle_id',
                    inputType: 'select',
                    inputId: 'desludging_vehicle_id',
                    selectValues: $selectValueVacutugType,
                    selectedValue: $emptying->desludging_vehicle_id,
                    required: true,
                    placeholder: __('Desludging Vehicle Number Plate'),
                    disabled: true
                ),
                new FormField(
                    label: __('Disposal Place'),
                    labelFor: 'treatment_plant_id',
                    inputType: 'select',
                    inputId: 'treatment_plant_id',
                    selectValues: $application->emptying_status && $application->sludge_collection_status ? TreatmentPlant::withTrashed()->pluck('name', 'id')->whereIn('type', [3, 4])->toArray() : TreatmentPlant::Operational()->whereIn('type', [3, 4])->pluck('name', 'id')->toArray(),
                    selectedValue: $emptying->treatment_plant_id,
                    required: true,
                    placeholder: __('Disposal Place'),
                    disabled: true

                ),
                new FormField(
                    label: __('Driver Name'),
                    labelFor: 'driver',
                    inputType: 'select',
                    inputId: 'driver',
                    selectValues: $selectValuesDriver,
                    selectedValue: $emptying->driver,
                    required: true,
                    disabled: true,
                    placeholder: __('Driver Name'),
                ),
                new FormField(
                    label: __('Emptier 1 Name'),
                    labelFor: 'emptier1',
                    inputType: 'select',
                    inputId: 'emptier1',
                    selectValues: $selectValuesCleanerEmptier,
                    selectedValue: $emptying->emptier1,
                    required: true,
                    placeholder: __('Emptier 1 Name'),
                    disabled: true
                ),
                new FormField(
                    label: __('Emptier 2 Name'),
                    labelFor: 'emptier2',
                    inputType: 'select',
                    inputId: 'emptier2',
                    selectValues: $selectValuesCleanerEmptier,
                    selectedValue: $emptying->emptier2,
                    placeholder: __('Emptier 2 Name'),
                    disabled: true

                ),
                new FormField(
                    label: __('Total Time (mins)'),
                    labelFor: 'total_time',
                    inputType: 'text',
                    inputId: 'total_time',
                    inputValue: $emptying->total_time,
                    required: true,
                    placeholder: __('Total Time (mins)'),
                    oninput: "this.value = this.value.replace(/[^0-9]/g, '')"
                ),

                new FormField(
                    label: __('Receipt Number'),
                    labelFor: 'receipt_number',
                    inputType: 'text',
                    inputId: 'receipt_number',
                    inputValue: $emptying->receipt_number,
                    required: true,
                    placeholder: __('Receipt Number'),

                ),
                new FormField(
                    label: __('Total Cost'),
                    labelFor: 'total_cost',
                    inputType: 'text',
                    inputId: 'total_cost',
                    inputValue: $emptying->total_cost,
                    required: true,
                    placeholder: __('Total Cost'),
                    oninput: "this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1'); this.value = this.value < 0 || this.value.startsWith('-') ? '' : this.value;"
                ),
                new FormField(
                    label: __('House Image'),
                    labelFor: 'house_image',
                    inputType: 'file_upload',
                    inputId: 'house_image',
                    inputValue: $emptying->house_image,
                    required: true,
                    placeholder: __('House Image'),
                ),
                new FormField(
                    label: __('Receipt Image'),
                    labelFor: 'receipt_image',
                    inputType: 'file_upload',
                    inputId: 'receipt_image',
                    inputValue: $emptying->receipt_image,
                    required: true,
                    placeholder: __('Receipt Image'),
                ),
                new FormField(
                    label: __('Comments (if any)'),
                    labelFor: 'comments',
                    inputType: 'textarea',
                    inputId: 'comments',
                    inputValue: $emptying->comments,
                    placeholder: __('Comments (if any)'),

                )
            ];
        } else {

            $this->editFormFields = [
                new FormField(
                    label: __('Application ID'),
                    labelFor: 'application_id',
                    inputType: 'textarea',
                    inputId: 'application_id',
                    inputValue: $emptying->application_id,
                    hidden: true
                ),
                new FormField(
                    label: __('Containment ID'),
                    labelFor: 'containment_id',
                    inputType: 'text',
                    inputId: 'containment_id',
                    inputValue: is_null($application->buildings) ? "-" : $application->buildings->containments->pluck('id'),
                    hidden: true
                ),
                new FormField(
                    label: __('Date'),
                    labelFor: 'emptied_date',
                    inputType: 'label',
                    inputId: date('m/d/Y', strtotime($emptying->emptied_date)),
                    placeholder: __('Date'),
                ),
                new FormField(
                    label: __('Service Receiver Name'),
                    labelFor: 'service_receiver_name',
                    inputType: 'text',
                    inputId: 'service_receiver_name',
                    inputValue: $emptying->service_receiver_name,
                    required: true,
                    placeholder: __('Service Receiver Name'),
                ),
                new FormField(
                    label: __('Service Receiver Gender'),
                    labelFor: 'service_receiver_gender',
                    inputType: 'select',
                    inputId: 'service_receiver_gender',
                    selectedValue: $emptying->service_receiver_gender,
                    selectValues: ["Male" => "Male", "Female" => "Female", "Others" => "Others"],
                    required: true,
                    placeholder: __('Service Receiver Gender'),
                ),
                new FormField(
                    label: __('Service Receiver Contact Number'),
                    labelFor: 'service_receiver_contact',
                    inputType: 'text',
                    inputId: 'service_receiver_contact',
                    inputValue: $emptying->service_receiver_contact,
                    required: true,
                    placeholder: __('Service Receiver Contact Number'),
                    oninput: "validateOwnerContactInput(this)",
                ),
                new FormField(
                    label: __('Reason for Emptying'),
                    labelFor: 'emptying_reason',
                    inputType: 'textarea',
                    inputId: 'emptying_reason',
                    inputValue: $emptying->emptying_reason,
                    required: true,
                    placeholder: __('Reason for Emptying'),
                ),
                new FormField(
                    label: __('No. of Trips'),
                    labelFor: 'trip_count',
                    inputType: 'text',
                    inputId: 'trip_count',
                    inputValue: $application->trip_count,
                    required: true,
                    placeholder: __('No. of Trips'),
                    oninput: "this.value = this.value.replace(/[^0-9]/g, '').replace(/^0+/, '1')"
                ),
                new FormField(
                    label: __('Current Trip No.'),
                    labelFor: 'trip_no',
                    inputType: 'text',
                    inputId: 'trip_no',
                    inputValue: $emptying->trip_no,
                    required: true,
                    placeholder: __('Current Trip No.'),
                    disabled: true
                ),
                new FormField(
                    label: __('Sludge Volume (m³)'),
                    labelFor: 'volume_of_sludge',
                    inputType: 'text',
                    inputId: 'volume_of_sludge',
                    inputValue: $emptying->volume_of_sludge,
                    required: true,
                    placeholder: __('Sludge Volume (m³)'),
                    oninput: "this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1'); this.value = this.value < 0 || this.value.startsWith('-') ? '' : this.value;"
                ),

                new FormField(
                    label: __('Desludging Vehicle Number Plate'),
                    labelFor: 'desludging_vehicle_id',
                    inputType: 'select',
                    inputId: 'desludging_vehicle_id',
                    selectValues: $selectValueVacutugType,
                    selectedValue: $emptying->desludging_vehicle_id,
                    required: true,
                    placeholder: __('Desludging Vehicle Number Plate'),
                ),
                new FormField(
                    label: __('Disposal Place'),
                    labelFor: 'treatment_plant_id',
                    inputType: 'select',
                    inputId: 'treatment_plant_id',
                    selectValues: TreatmentPlant::Operational()->whereIn('type', [3, 4])->pluck('name', 'id')->toArray(),
                    selectedValue: $emptying->treatment_plant_id,
                    required: true,
                    placeholder: __('Disposal Place'),

                ),
                new FormField(
                    label: __('Driver Name'),
                    labelFor: 'driver',
                    inputType: 'select',
                    inputId: 'driver',
                    selectValues: $selectValuesDriver,
                    selectedValue: $emptying->driver,
                    required: true,
                    placeholder: __('Driver Name'),
                ),
                new FormField(
                    label: __('Emptier 1 Name'),
                    labelFor: 'emptier1',
                    inputType: 'select',
                    inputId: 'emptier1',
                    selectValues: $selectValuesCleanerEmptier,
                    selectedValue: $emptying->emptier1,
                    required: true,
                    placeholder: __('Emptier 1 Name'),
                ),
                new FormField(
                    label: __('Emptier 2 Name'),
                    labelFor: 'emptier2',
                    inputType: 'select',
                    inputId: 'emptier2',
                    selectValues: $selectValuesCleanerEmptier,
                    selectedValue: $emptying->emptier2,
                    placeholder: __('Emptier 2 Name'),

                ),
                new FormField(
                    label: __('Total Time (mins)'),
                    labelFor: 'total_time',
                    inputType: 'text',
                    inputId: 'total_time',
                    inputValue: $emptying->total_time,
                    required: true,
                    placeholder: __('Total Time (mins)'),
                    oninput: "this.value = this.value.replace(/[^0-9]/g, '')"
                ),

                new FormField(
                    label: __('Receipt Number'),
                    labelFor: 'receipt_number',
                    inputType: 'text',
                    inputId: 'receipt_number',
                    inputValue: $emptying->receipt_number,
                    required: true,
                    placeholder: __('Receipt Number'),

                ),
                new FormField(
                    label: __('Total Cost'),
                    labelFor: 'total_cost',
                    inputType: 'text',
                    inputId: 'total_cost',
                    inputValue: $emptying->total_cost,
                    required: true,
                    placeholder: __('Total Cost'),
                    oninput: "this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1'); this.value = this.value < 0 || this.value.startsWith('-') ? '' : this.value;"
                ),
                new FormField(
                    label: __('House Image'),
                    labelFor: 'house_image',
                    inputType: 'file_upload',
                    inputId: 'house_image',
                    inputValue: $emptying->house_image,
                    required: true,
                    placeholder: __('House Image'),
                ),
                new FormField(
                    label: __('Receipt Image'),
                    labelFor: 'receipt_image',
                    inputType: 'file_upload',
                    inputId: 'receipt_image',
                    inputValue: $emptying->receipt_image,
                    required: true,
                    placeholder: __('Receipt Image'),
                ),
                new FormField(
                    label: __('Comments (if any)'),
                    labelFor: 'comments',
                    inputType: 'textarea',
                    inputId: 'comments',
                    inputValue: $emptying->comments,
                    placeholder: __('Comments (if any)'),

                )
            ];
        }


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
     * Get action/route for index page of Emptyings.
     *
     * @return String
     */
    public function getIndexAction()
    {
        return $this->indexAction;
    }

    /**
     * Get action/route for index page of Application.
     *
     * @return String
     */
    public function getApplicationIndexAction()
    {
        return route('application.index');
    }

    /**
     * Get action/route for create page of Emptyings.
     *
     * @return String
     */
    public function getCreateRoute()
    {
        return $this->createRoute;
    }

    /**
     * Get action/route for exporting Emptyings.
     *
     * @return String
     */
    public function getExportRoute()
    {
        return $this->exportRoute;
    }

    /**
     * Get action/route for edit form.
     *
     * @return String
     */
    public function getEditFormAction($emptying)
    {
        $this->editFormAction = route('emptying.update', $emptying);
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
     * Get all the emptyings.
     *
     *
     * @return Emptying[]|Collection
     */
    public function getAllEmptyings()
    {
        if (Auth::user()->hasRole('Service Provider - Admin')) {
            return Emptying::join('fsm.applications', function ($join) {
                $join->on('fsm.emptyings.application_id', '=', 'fsm.applications.id')
                    ->whereNull('fsm.applications.deleted_at');
            })
                ->whereNull('fsm.emptyings.deleted_at')
                ->where('fsm.emptyings.service_provider_id', Auth::user()->service_provider_id)
                ->select('fsm.emptyings.*');
        } else if (Auth::user()->hasRole('Treatment Plant - Admin')) {
            return Emptying::join('fsm.applications', function ($join) {
                $join->on('fsm.emptyings.application_id', '=', 'fsm.applications.id')
                    ->whereNull('fsm.applications.deleted_at');
            })
                ->whereNull('fsm.emptyings.deleted_at')
                ->where('fsm.emptyings.treatment_plant_id', Auth::user()->treatment_plant_id)
                ->select('fsm.emptyings.*');
        } else {
            return Emptying::join('fsm.applications', function ($join) {
                $join->on('fsm.emptyings.application_id', '=', 'fsm.applications.id')
                    ->whereNull('fsm.applications.deleted_at');
            })
                ->whereNull('fsm.emptyings.deleted_at')
                ->select('fsm.emptyings.*');
        }
    }

    /**
     * Get Datatables of Applications.
     *
     * @return DataTables
     * @throws Exception
     */
    public function getDatatable(Request $request)
    {
        return DataTables::of($this->getAllEmptyings())
            ->filter(function ($query) use ($request) {

                if ($request->application_id) {
                    $query->where('application_id', "=", $request->application_id);
                }
                if ($request->bin) {
                    $query->whereHas("application.buildings", function ($q) use ($request) {
                        $q->where("bin", "=", $request->bin);
                    });
                }
                if ($request->emptied_date) {
                    $query->whereDate('emptied_date', $request->emptied_date);
                }
                if ($request->containment_code) {
                    $query->whereHas("application", function ($q) use ($request) {
                        $q->where('containment_id', 'ILIKE', '%' . $request->containment_code . '%');
                    });
                }
                if ($request->date_from && $request->date_to && $request->date_from <= $request->date_to) {
                    $query->whereDate('emptied_date', '>=', $request->date_from);
                    $query->whereDate('emptied_date', '<=', $request->date_to);
                }
            })
            ->addColumn('action', function ($model) {
                $content = \Form::open(['method' => 'DELETE', 'route' => ['emptying.destroy', $model->id]]);
                $content .= '<div class="">';
                if (Auth::user()->can('View Emptying')) {
                    $content .= '<a title="' . __("Detail") . '" href="' . route('emptying.show', [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                }
                if (Auth::user()->can('View Emptyings History')) {
                    $content .= '<a title="' . __("History") . '" href="' . route('emptying.history', $model->id) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-history"></i></a> ';
                }
                if (Auth::user()->can('Delete Emptying')) {
                    $content .= '<a title="' . __("Delete") . '"  class="delete  btn-danger btn  btn-sm mb-1"><i class="fa fa-trash"></i></a> ';
                }
                $content .= '</div>';
                $content .= \Form::close();
                return $content;
            })
            ->editColumn('service_provider_id', function ($model) {
                return $model->service_provider->company_name ?? '-';
            })
            ->rawColumns(['emptying_status', 'feedback_status', 'action'])
            ->make(true);
    }

    /**
     * Create emptying.
     *
     * @throws Exception
     */

    public function createEmptying($request, string $mode = 'web')
    {
        $emptying = null;

        DB::beginTransaction();

        try {
            // If it's a FormRequest, validation is handled by Laravel framework
            if ($request instanceof \Illuminate\Foundation\Http\FormRequest) {
                try {
                    $request->validated();
                } catch (\Throwable $e) {
                    // Ignore if validator is already processed
                }
            }

            // Extract data
            $data = $request->except('no_of_trips', 'start_time', 'end_time');
            $application = Application::findOrFail($request->application_id);

            // ✅ Block saving emptying if ANF is not resolved
            if ($application->is_anf) {
                DB::rollBack();

                if ($mode === 'api') {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Error! Please locate and link the building before saving emptying service.',
                    ], 422);
                }

                return redirect()->back()
                    ->withInput()
                    ->with('error', __('Error! Please locate and link the building (ANF) before saving emptying service.'));
            }

            // ✅ Block if required fields are missing (ANF was not properly resolved)
            if (empty($application->bin) || empty($application->containment_id) || empty($application->road_code)) {
                DB::rollBack();

                if ($mode === 'api') {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Error! Building information is incomplete. Please locate the building first.',
                    ], 422);
                }

                return redirect()->back()
                    ->withInput()
                    ->with('error', __('Error! Building information is incomplete. Please locate the building first.'));
            }

            $exists = Emptying::where('application_id', $application->id)->exists();

            // Check if the emptying record already exists for this application
            if ($exists) {
                $emptying = Emptying::where('application_id', $application->id)->first();

                // Add time and volume
                $emptying->total_time = $emptying->total_time + $this->sumHms($request->start_time,  $request->end_time) ?: null;
                $emptying->volume_of_sludge = $emptying->volume_of_sludge + $request->volume_of_sludge;
            } else {
                // Create the Emptying record
                $data['trip_no'] = 0;
                $emptying = Emptying::create($data);
                $emptying->total_time = $this->sumHms($request->start_time, $request->end_time) ?: null;

                // Update containment information
                $containment = Containment::find($application->containment_id);
                $containment->last_emptied_date = $emptying->emptied_date = now();
                $containment->next_emptying_date = now()->addYears(3);
                $containment->emptied_status = true;
                $containment->no_of_times_emptied = ($containment->no_of_times_emptied ?? 0) + 1;
                $containment->save();

                // Check emptying status and handle accordingly
                if ($application->emptying_status) {
                    if ($emptying) {
                        $emptying->forceDelete();
                        $application->emptying_status = 0;
                        $application->save();
                    }

                    DB::rollBack();

                    if ($mode === 'api') {
                        return response()->json([
                            'status'  => false,
                            'message' => "Emptying service is already done for application {$application->id}",
                        ], 400);
                    }

                    return redirect()->back()
                        ->withInput()
                        ->with('error', "Emptying service is already done for application {$application->id}");
                }

                // Assign service provider and user ID to the emptying
                $emptying->service_provider_id = $application->service_provider_id;
                $emptying->user_id = auth()->id();
            }

            // Handle trip count and image upload
            $application->trip_count = $request->trip_count;

            if ($application->trip_count == ($emptying->trip_no + 1)) {

                // --- Image upload required on final trip ---
                $allowedFileExt = ['jpg', 'jpeg'];

                if (!$request->hasFile('receipt_image') || !$request->hasFile('house_image')) {
                    DB::rollBack();

                    if ($emptying) {
                        $emptying->delete();
                        $application->emptying_status = 0;
                        $application->save();
                    }

                    if ($mode === 'api') {
                        return response()->json([
                            'status'  => false,
                            'message' => 'Error! Receipt and house images are required on final trip.'
                        ], 422);
                    }

                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Error! Receipt and house images are required on final trip.');
                }

                $extension_receipt = $request->receipt_image->getClientOriginalExtension();
                $extension_house   = $request->house_image->getClientOriginalExtension();

                $dateTime = now()->setTimezone(new DateTimeZone('Asia/Kathmandu'))->format('Y_m_d');

                $check = in_array($extension_receipt, $allowedFileExt) &&
                    in_array($extension_house, $allowedFileExt);

                if (!$check) {
                    DB::rollBack();

                    if ($emptying) {
                        $emptying->delete();
                        $application->emptying_status = 0;
                        $application->save();
                    }

                    if ($mode === 'api') {
                        return response()->json([
                            'status'  => false,
                            'message' => 'Error! Invalid image format.'
                        ], 422);
                    }

                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Error! Invalid image format.');
                }

                // Save images
                $filename_house = $application->bin . '.' . $extension_house;
                if (Storage::disk('local')->exists("/public/emptyings/houses/{$filename_house}")) {
                    $dateTime = now()->format('Y-m-d_H-i-s');
                    $new_archived_filename = "{$application->bin}_{$dateTime}.{$extension_house}";
                    Storage::disk('local')->move(
                        "/public/emptyings/houses/{$filename_house}",
                        "/public/emptyings/houses/{$new_archived_filename}"
                    );
                }
                $storeHouseImg = Image::make($request->house_image)
                    ->save(Storage::disk('local')->path("/public/emptyings/houses/{$filename_house}"), 50);
                $emptying->house_image = $filename_house;
                $filename_receipt = "{$emptying->id}_{$emptying->application_id}_{$emptying->receipt_number}_{$dateTime}.{$extension_receipt}";

                $storeReceiptImg = Image::make($request->receipt_image)
                    ->save(Storage::disk('local')->path("/public/emptyings/receipts/{$filename_receipt}"), 50);
                // Assign image paths to emptying
                $emptying->receipt_image  = $filename_receipt;
                $emptying->house_image    = $filename_house;
                $emptying->receipt_number = $request->receipt_number;
                $emptying->total_cost     = $request->total_cost;
                $application->emptying_status = 2;
                $emptying->trip_no++;
            } else {
                // Not final trip yet
                $application->emptying_status = 1;
                $emptying->trip_no++;
            }
            // Save the emptying record
            $emptying->save();
            // Final save for application
            $application->save();

            // Commit the transaction
            DB::commit();

            // Notify FSTP Operator
            DB::afterCommit(function () use ($application, $emptying) {

               $fstpUsers = User::where('treatment_plant_id', $emptying->treatment_plant_id)->get();

if (!empty($emptying->user_id)) {
    $creator = User::find($emptying->user_id);

    if ($creator) {
        $fstpUsers->push($creator);
    }
}

$fstpUsers = $fstpUsers->unique('id')->values();

$fstpMessage = sprintf(
    'New Application #%s is approaching FSTP for disposal of fecal sludge. Date: %s.',
    $application->id,
    Carbon::now()->format('M d, Y')
);

foreach ($fstpUsers as $user) {
    Notification::create([
        'user_id' => $user->id,
        'mode'    => 'web',
        'message' => $fstpMessage,
        'status'  => false,
    ]);
}

$fstpUserIds = $fstpUsers
    ->pluck('id')
    ->map(fn($uid) => (string) $uid)
    ->unique()
    ->values()
    ->all();


$etoUserIds = DB::table('fsm.employees')
    ->where('service_provider_id', $emptying->service_provider_id)
    ->where('status', true)
    ->whereNotNull('user_id')
    ->pluck('user_id')
    ->map(fn($uid) => (string) $uid)
    ->unique()
    ->values()
    ->all();

$serviceProviderAdminIds = User::where('service_provider_id', $emptying->service_provider_id)
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

$emptyingMessage = sprintf(
    'Application #%s has been provided emptying services on %s.',
    $application->id,
    Carbon::now()->format('M d, Y')
);

foreach ($recipientIds as $userId) {
    Notification::create([
        'user_id' => $userId,
        'mode'    => 'web',
        'message' => $emptyingMessage,
        'status'  => false,
    ]);
}

});
            // ✅ SUCCESS RESPONSE
            if ($mode === 'api') {
                return response()->json([
                    'status'  => true,
                    'message' => 'Emptying Service Details created successfully.',
                ], 200);
            }

            return redirect(route('application.index'))
                ->with('success', __('Emptying Service Details created successfully.'));
        } catch (Throwable $e) {

            DB::rollBack();

            if ($emptying) {
                $emptying->delete();
                if (isset($application)) {
                    $application->emptying_status = 0;
                    $application->save();
                }
            }

            if ($mode === 'api') {
                return response()->json([
                    'status'  => false,
                    'message' => __("Error! Emptying couldn't be created.") . ' ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', __("Error! Emptying couldn't be created.") . ' ' . $e->getMessage());
        }
    }

    /**
     * Difference two time strings ("H:i" or "H:i:s"), 
     */
    function sumHms(?string $t1, ?string $t2): ?string
    {
        $entry_time = Carbon::parse($t1);
        $exit_time  = Carbon::parse($t2);

        $diffInMinutes = $exit_time->diffInMinutes($entry_time);
        return $diffInMinutes;
    }

    /**
     * Update emptying.
     *
     * @throws Exception
     */
    public function updateEmptying(Request $request, $id)
    {
        $emptying = Emptying::findOrFail($id);
        $application = Application::findOrFail($request->application_id);
        DB::beginTransaction();
        try {
            if ($request->validated()) {
                $emptying->updateOrFail($request->all());
                $dateTime = now();
                $dateTime->setTimezone(new DateTimeZone('Asia/Kathmandu'));
                $dateTime = $dateTime->format('Y_m_d');
                //check image upload
                $allowedFileExt = ['jpg', 'jpeg'];
                if ($request->hasFile('receipt_image')) {
                    $extension_receipt = $request->receipt_image->getClientOriginalExtension();
                    $check = in_array($extension_receipt, $allowedFileExt);
                    if ($check) {
                        try {
                            $filename_receipt = $emptying->id . '_' . $emptying->application_id . '_' . $emptying->receipt_number . '_' . $dateTime . '.' . $extension_receipt;
                            $storeReceiptImg = Image::make($request->receipt_image)->save(Storage::disk('local')->path('/public/emptyings/receipts/' . $filename_receipt), 50);
                            if (!Storage::disk('local')->exists('/public/emptyings/receipts/' . $filename_receipt)) {
                                return redirect()->back()->withInput()->with('error', __("Error! Unable to save receipt image."));
                            }
                            $emptying->receipt_image = $filename_receipt;
                        } catch (\Throwable $th) {
                            DB::rollBack();
                            return redirect()->back()->withInput()->with('error', __("Error! Unable to save images."));
                        }
                    } else {
                        DB::rollBack();
                        return redirect()->back()->withInput()->with('error', __("Error! Invalid image format."));
                    }
                } elseif ($request->filled('receipt_image_existing')) {
                    $emptying->receipt_image = $request->receipt_image_existing;
                }

                if ($request->hasFile('house_image')) {
                    $extension_house = $request->house_image->getClientOriginalExtension();
                    $check = in_array($extension_house, $allowedFileExt);
                    if ($check) {
                        try {
                            $filename_house = $application->bin . '.' . $extension_house;
                            if (Storage::disk('local')->exists("/public/emptyings/houses/{$filename_house}")) {
                                $dateTime = now()->format('Y-m-d_H-i-s');
                                $new_archived_filename = "{$application->bin}_{$dateTime}.{$extension_house}";
                                Storage::disk('local')->move(
                                    "/public/emptyings/houses/{$filename_house}",
                                    "/public/emptyings/houses/{$new_archived_filename}"
                                );
                            }
                            $storeHouseImg = Image::make($request->house_image)
                                ->save(Storage::disk('local')->path("/public/emptyings/houses/{$filename_house}"), 50);
                            $emptying->house_image = $filename_house;
                        } catch (\Throwable $th) {
                            DB::rollBack();
                            return redirect()->back()->withInput()->with('error', __("Error! Unable to save images."));
                        }
                    } else {
                        DB::rollBack();
                        return redirect()->back()->withInput()->with('error', __("Error! Invalid image format."));
                    }
                } elseif ($request->filled('house_image_existing')) {
                    $emptying->house_image = $request->house_image_existing;
                }
                $application->trip_count = $request->trip_count;
                if ($application->trip_count != $emptying->trip_no) {
                    $application->emptying_status = 1;
                } else {
                    $application->emptying_status = 2;
                }
                $application->save();
                $emptying->save();
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', __('Failed to update Emptying.'));
        }
        return redirect(route('application.index'))->with('success', __('Emptying updated successfully.'));
    }

    /**
     * Retrieve emptying history.
     *
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|RedirectResponse|Redirector
     */
    public function getEmptyingHistory($id)
    {
        try {
            $emptying = Emptying::findOrFail($id);
            $revisions = Revision::all()
                ->where('revisionable_type', get_class($emptying))
                ->where('revisionable_id', $id)
                ->groupBy(function ($item) {
                    return $item->created_at->format("D M j Y");
                })
                ->sortByDesc('created_at')
                ->reverse();
        } catch (\Throwable $e) {
            return redirect(route('emptying.index'))->with('error', __('Failed to generate history.'));
        }
        return view('fsm.emptying.history', compact('emptying', 'revisions'));
    }

    /**
     * Export emptyings.
     *
     * @throws Exception
     */
    public function export(Request $request)
    {

        $application_id = $request->application_id;
        $bin = $request->bin;
        $emptied_date = $request->emptied_date;
        $date_from = $request->date_from;
        $date_to = $request->date_to;
        $containment_id = $request->containment_id;

        $headers = [
            __('Application ID'),
            __('House Number'),
            __('Date'),
            __('Service Provider Name'),
            __('Service Receiver Name'),
            __('Service Receiver Gender'),
            __('Service Receiver Contact Number'),
            __('No. of Trips'),
            __('Reason for Emptying'),
            __('Sludge Volume (m³)'),
            __('Desludging Vehicle License Plate'),
            __('Driver Name'),
            __('Emptier 1 Name'),
            __('Emptier 2 Name'),
            __('Total Time'),
            __('Receipt Number'),
            __('Total Cost'),
            __('Disposal Place'),
            __('Uploaded By')
        ];


        $query =  DB::table('fsm.emptyings AS e')
            ->leftJoin('fsm.applications AS a', 'a.id', '=', 'e.application_id')
            ->leftJoin('building_info.buildings AS b', 'b.bin', '=', 'a.bin')
            ->leftJoin('fsm.containments AS c', 'a.containment_id', '=', 'c.id')
            ->leftJoin('fsm.desludging_vehicles AS dv', 'dv.id', '=', 'e.desludging_vehicle_id')
            ->leftJoin('fsm.service_providers AS s', 's.id', '=', 'e.service_provider_id')
            ->leftJoin('fsm.employees AS d', 'd.id', '=', 'e.driver')
            ->leftJoin('fsm.employees AS e1', 'e1.id', '=', 'e.emptier1')
            ->leftJoin('fsm.employees AS e2', 'e2.id', '=', 'e.emptier2')
            ->leftJoin('fsm.treatment_plants AS t', 't.id', '=', 'e.treatment_plant_id')
            ->leftJoin('auth.users AS u', 'u.id', '=', 'e.user_id')
            ->select(
                'e.application_id',
                'b.bin AS bin',
                'e.emptied_date',
                's.company_name',
                'e.service_receiver_name',
                'e.service_receiver_gender',
                'e.service_receiver_contact',
                'e.no_of_trips',
                'e.emptying_reason',
                'e.volume_of_sludge',
                'c.distance_closest_well',
                'dv.license_plate_number AS desludging_vehicle_license_plate',
                't.name AS treatment_plant_name',
                'd.name AS driver_name',
                'e1.name AS emptier1_name',
                'e2.name AS emptier2_name',
                'e.total_time',
                'e.receipt_number',
                'e.total_cost',
                'u.name AS user'
            )
            ->orderBy('e.id')
            ->whereNull('e.deleted_at');

        if (!Auth::user()->hasRole('Super Admin') && !Auth::user()->hasRole('Municipality - Super Admin') && !Auth::user()->hasRole('Municipality - IT Admin') && !Auth::user()->hasRole('Municipality - Sanitation Department')) {
            $query->where('u.name', '=', Auth::user()->name);
        }

        if (!empty($application_id)) {
            $query->where('e.application_id', $application_id);
        }
        if (!empty($bin)) {
            $query->whereHas("application.buildings", function ($q) use ($bin) {
                $q->where("bin", "=", $bin);
            });
        }
        if (!empty($emptied_date)) {
            $query->whereDate('e.emptied_date', $emptied_date);
        }
        if ($date_from && $date_to) {
            $query->whereBetween('e.emptied_date', [$date_from, $date_to]);
        }
        if (!empty($containment_id)) {
            $query->whereExists(function ($q) use ($containment_id) {
                $q->select(DB::raw(1))
                    ->from('fsm.applications AS a')
                    ->whereRaw('a.id = e.application_id')
                    ->where('a.containment_id', 'ILIKE', '%' . $containment_id . '%');
            });
        }

        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();

        $writer = WriterFactory::create(Type::CSV);
        $writer->openToBrowser('Emptyings.csv')
            ->addRowWithStyle($headers, $style); //Top row of excel

        $query->chunk(5000, function ($emptyings) use ($writer) {
            foreach ($emptyings as $emptying) {

                $values = [];
                $values[] = $emptying->application_id ?? "-";
                $values[] = $emptying->bin ?? "-";
                $values[] = $emptying->emptied_date ?? "-";
                $values[] = $emptying->company_name ?? "-";
                $values[] = $emptying->service_receiver_name ?? "-";
                $values[] = $emptying->service_receiver_gender ?? "-";
                $values[] = $emptying->service_receiver_contact ?? "-";
                $values[] = $emptying->emptying_reason ?? "-";
                $values[] = $emptying->no_of_trips ?? "-";
                $values[] = $emptying->volume_of_sludge ?? "-";
                $values[] = $emptying->desludging_vehicle_license_plate ?? "-";
                $values[] = $emptying->driver_name ?? "-";
                $values[] = $emptying->emptier1_name ?? "-";
                $values[] = $emptying->emptier2_name ?? "-";
                $values[] = $emptying->total_time ?? "-";
                $values[] = $emptying->receipt_number ?? "-";
                $values[] = $emptying->total_cost ?? "-";
                $values[] = $emptying->treatment_plant_name ?? "-";
                $values[] = $emptying->user ?? "-";
                $writer->addRow($values);
            }
        });

        $writer->close();
    }
}
