<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Services\Fsm;

use App\Enums\ApplicationStatus;
use App\Enums\EmployeeStatus;
use App\Enums\ServiceProviderStatus;
use App\Enums\UserStatus;
use App\Enums\VacutugStatus;
use App\Models\Fsm\Application;
use App\Models\Fsm\ServiceProvider;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Session\SessionManager;
use Laravel\Sanctum\Sanctum;
use DB;
use Carbon\Carbon;
use Auth;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use Yajra\DataTables\DataTables;
use App\Models\Fsm\Feedback;
use DomainException;

class ServiceProviderService
{

    protected $session;
    protected $instance;

    /**
     * Constructs a new ServiceProvider object.
     *
     *
     */
    public function __construct()
    {
        /*Session code
        ....
         here*/
    }

    /**
     * Get all the All Employee Info.
     *
     *
     * @return AllData[]|Collection
     */
    public function getAllServiceProviders($data)
    {
        $serviceProviderData = ServiceProvider::select('*')->whereNull('deleted_at');

        if (empty($data['order'])) {
            $serviceProviderData->orderByDesc('created_at')->orderByDesc('id');
        }

        return Datatables::of($serviceProviderData)
            ->filter(function ($query) use ($data) {
                if ($data['company_name']) {
                    $query->where('company_name', 'ILIKE', '%' .  trim($data['company_name']) . '%');
                }
                if ($data['ward']) {
                    $query->where('ward', 'ILIKE', '%' .  trim($data['ward']) . '%');
                }
                if ($data['email']) {
                    $query->where('email', 'ILIKE', '%' .  trim($data['email']) . '%');
                }
                if ($data['contact_person']) {
                    $query->where('contact_person', 'ILIKE', '%' .  trim($data['contact_person']) . '%');
                }
                if ($data['company_location']) {
                    $query->where('company_location', 'ILIKE', '%' .  trim($data['company_location']) . '%');
                }
                if ($data['status']) {
                    $query->where('status', $data['status']);
                }
            })

            ->editColumn('rating', function ($model) {
                $content = ServiceProvider::select('service_providers.id', 'feedbacks.fsm_service_quality')
                    ->join('fsm.feedbacks', 'service_providers.id', '=', 'feedbacks.service_provider_id')
                    ->where('service_providers.id',$model->id)
                    ->orderBy('feedbacks.created_at', 'desc')
                    ->limit(5)
                    ->pluck('feedbacks.fsm_service_quality');

                $htmlContent = '';

                foreach ($content as $quality) {
                    $starSymbol = $quality ? '★' : '☆';
                    $htmlContent .= $starSymbol;
                }

                return $htmlContent;
            })
            ->addColumn('action', function ($model) {
                $content = \Form::open(['method' => 'DELETE', 'route' => ['service-providers.destroy', $model->id]]);

                if (Auth::user()->can('Edit Service Provider')) {
                    $content .= '<a title="' . e(__("Edit")) . '" href="' . action("Fsm\ServiceProviderController@edit", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-edit"></i></a> ';
                }

                if (Auth::user()->can('View Service Provider')) {
                    $content .= '<a title="' . e(__("Detail")) . '" href="' . action("Fsm\ServiceProviderController@show", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                }

                if (Auth::user()->can('View Service Provider History')) {
                    $content .= '<a title="' . e(__("History")) . '" href="' . action("Fsm\ServiceProviderController@history", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-history"></i></a> ';
                }

                if (Auth::user()->can('Delete Service Provider')) {
                    $content .= '<a href="#" title="' . e(__("Delete")) . '" class="delete btn btn-danger btn-sm mb-1"><i class="fa fa-trash"></i></a> ';
                }

                $content .= \Form::close();
                return $content;
            })
            ->editColumn('status', function ($model) {
                return ServiceProviderStatus::getDescription($model->status);
            })
            ->make(true);
    }

    /**
     * Return municipality wards that are not covered by any operational
     * service provider. Both the normalized mapping and legacy CSV field are
     * considered so the information remains accurate during migration.
     */
    public function getUncoveredServiceAreaWards(): array
    {
        $municipalityWards = DB::table('layer_info.wards')
            ->whereNotNull('ward')
            ->pluck('ward')
            ->map(function ($ward) {
                return (int) $ward;
            })
            ->filter(function ($ward) {
                return $ward > 0;
            })
            ->unique()
            ->sort()
            ->values();

        $providers = DB::table('fsm.service_providers')
            ->whereNull('deleted_at')
            ->where('status', true)
            ->get(['id', 'service_area']);

        $coveredWards = $providers
            ->pluck('service_area')
            ->flatMap(function ($serviceArea) {
                return explode(',', (string) $serviceArea);
            })
            ->map(function ($ward) {
                return (int) trim($ward);
            })
            ->filter(function ($ward) {
                return $ward > 0;
            });

        if ($providers->isNotEmpty()) {
            $coveredWards = $coveredWards->merge(
                DB::table('fsm.service_provider_wards')
                    ->whereIn(
                        'service_provider_id',
                        $providers->pluck('id')
                    )
                    ->pluck('ward')
                    ->map(function ($ward) {
                        return (int) $ward;
                    })
            );
        }

        return $municipalityWards
            ->diff($coveredWards->unique())
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Store or update a newly created resource in storage.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function storeOrUpdate($id, $data)
    {
        $serviceArea = null;
        $wards = [];

        if (!empty($data['service_area'])) {
            $wards = array_map(
                'intval',
                (array) $data['service_area']
            );

            $wards = array_values(
                array_unique(
                    array_filter($wards, function ($ward) {
                        return $ward > 0;
                    })
                )
            );

            sort($wards);

            $serviceArea = !empty($wards)
                ? implode(',', $wards)
                : null;
        }
        return DB::transaction(function () use ($id, $data, $serviceArea, $wards) {
            $serviceProvider = is_null($id)
                ? new ServiceProvider()
                : ServiceProvider::whereKey($id)->lockForUpdate()->firstOrFail();

            $wasOperational = (bool) $serviceProvider->status;
            $willBeOperational = (bool) $data['status'];

            if (
                !is_null($id)
                && $wasOperational
                && !$willBeOperational
                && $this->hasActiveApplications($id, true)
            ) {
                throw new DomainException(
                    __('The Service Provider cannot be made inactive while it has active or ongoing applications.')
                );
            }

            $serviceProvider->company_name = $data['company_name'] ?: null;
            $serviceProvider->email = $data['email'] ?: null;
            $serviceProvider->ward = $data['ward'] ?: null;
            $serviceProvider->company_location = $data['company_location'] ?: null;
            $serviceProvider->contact_person = $data['contact_person'] ?: null;
            $serviceProvider->contact_gender = $data['contact_gender'] ?: null;
            $serviceProvider->contact_number = $data['contact_number'] ?: null;
            $serviceProvider->status = $willBeOperational;
            $serviceProvider->contract_document_pdf =
                $data['contract_document_pdf'] ?? $serviceProvider->contract_document_pdf;
            $serviceProvider->service_area = $serviceArea;
            $serviceProvider->save();

            $this->syncServiceAreaWards($serviceProvider->id, $wards);

            if (!is_null($id) && $wasOperational !== $willBeOperational) {
                $willBeOperational
                    ? $this->activateProviderAdministrators($serviceProvider)
                    : $this->inactivateDependants($serviceProvider);
            }

            return $serviceProvider->id;
        });
    }

    /** Determine whether a provider still owns work that has not been completed. */
    public function hasActiveApplications(int $serviceProviderId, bool $lock = false): bool
    {
        $query = Application::where('service_provider_id', $serviceProviderId)
            ->select(['id', 'emptying_status', 'trip_count']);

        if ($lock) {
            $query->lockForUpdate();
        }

        $applications = $query->get();

        if ($applications->isEmpty()) {
            return false;
        }

        $applicationIds = $applications->pluck('id');
        $emptyingTrips = DB::table('fsm.emptyings')
            ->whereIn('application_id', $applicationIds)
            ->whereNull('deleted_at')
            ->select('application_id', DB::raw('MAX(COALESCE(trip_no, 0)) AS completed_trips'))
            ->groupBy('application_id')
            ->pluck('completed_trips', 'application_id');

        return $applications->contains(function ($application) use ($emptyingTrips) {
            $requiredTrips = max((int) ($application->trip_count ?: 1), 1);
            $completedTrips = (int) ($emptyingTrips[$application->id] ?? 0);

            return (int) $application->emptying_status !== ApplicationStatus::Completed
                || $completedTrips < $requiredTrips;
        });
    }

    /** Disable every dependent record and invalidate all linked login tokens. */
    private function inactivateDependants(ServiceProvider $serviceProvider): void
    {
        $userIds = $serviceProvider->users()->pluck('id');

        $serviceProvider->users()->update([
            'status' => UserStatus::Inactive,
            'remember_token' => null,
            'updated_at' => now(),
        ]);
        $serviceProvider->employees()->update([
            'status' => EmployeeStatus::Inactive,
            'updated_at' => now(),
        ]);
        $serviceProvider->vacutugTypes()->update([
            'status' => VacutugStatus::NotOperational,
            'updated_at' => now(),
        ]);

        if ($userIds->isNotEmpty()) {
            $personalAccessTokenModel = Sanctum::personalAccessTokenModel();

            $personalAccessTokenModel::query()
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $userIds)
                ->delete();

            DB::table(config('session.table', 'sessions'))
                ->whereIn('user_id', $userIds)
                ->delete();
        }
    }

    /** Reactivate only credentials assigned the Service Provider administrator role. */
    private function activateProviderAdministrators(ServiceProvider $serviceProvider): void
    {
        $serviceProvider->users()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Service Provider - Admin');
            })
            ->update([
                'status' => UserStatus::Active,
                'updated_at' => now(),
            ]);
    }

    /** Keep normalized service-area coverage aligned with the legacy CSV field. */
    private function syncServiceAreaWards(int $serviceProviderId, array $wards): void
    {
        DB::table('fsm.service_provider_wards')
            ->where('service_provider_id', $serviceProviderId)
            ->delete();

        if (empty($wards)) {
            return;
        }

        $timestamp = now();

        DB::table('fsm.service_provider_wards')->insert(
            array_map(function ($ward) use ($serviceProviderId, $timestamp) {
                return [
                    'service_provider_id' => $serviceProviderId,
                    'ward' => $ward,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }, $wards)
        );
    }

    /**
     * Download a listing of the specified resource from storage.
     *
     * @param array $data
     * @return null
     */
    public function download($data)
    {
        $searchData = $data['searchData'] ? $data['searchData'] : null;

        $company_name = $data['company_name'] ? $data['company_name'] : null;
        $ward = $data['ward'] ? $data['ward'] : null;
        $company_location = $data['company_location'] ? $data['company_location'] : null;
        $email = $data['email'] ? $data['email'] : null;
        $contact_person = $data['contact_person'] ? $data['contact_person'] : null;

        $status = $data['status'] ? $data['status'] : null;
        $searchData = $data['searchData'] ? $data['searchData'] : 0;

        $columns = [
            __('Company Name'),
            __('Ward Number'),
            __('Address'),
            __('Email'),
            __('Contact Person Name'),
            __('Contact Person Number'),
            __('Status')
        ];
        
        $query = ServiceProvider::select('company_name', 'ward', 'company_location', 'email', 'contact_person', 'contact_number', 'status')->whereNull('deleted_at');


        if (!empty($company_name)) {
            $query->where('company_name', 'ILIKE', '%' .  trim($company_name) . '%');
        }

        if (!empty($email)) {
            $query->where('email', 'ILIKE', '%' .  trim($email) . '%');
        }

        if (!empty($ward)) {
            $query->where("ward", $ward);
        }

        if (!empty($company_location)) {
            $query->where('company_location', 'ILIKE', '%' .  trim($company_location) . '%');
        }

        if (!empty($contact_person)) {
            $query->where('contact_person', 'ILIKE', '%' .  trim($contact_person) . '%');
        }

        if (!empty($status)) {
            $query->where('status',  $status);
        }

        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();

        $writer = WriterFactory::create(Type::CSV);
        $writer->openToBrowser('Service Providers.csv')
            ->addRowWithStyle($columns, $style); //Top row of excel
        $query->chunk(5000, function ($serviceProviders) use ($writer) {
            foreach ($serviceProviders as $serviceProvider) {
                $values = [];
                $values[] = $serviceProvider->company_name;
                $values[] = $serviceProvider->ward;
                $values[] = $serviceProvider->company_location;
                $values[] = $serviceProvider->email;
                $values[] = $serviceProvider->contact_person;
                $values[] = $serviceProvider->contact_number;
                $values[] = ServiceProviderStatus::getDescription($serviceProvider->status);
                $writer->addRow($values);
            }
        });

        $writer->close();
    }
}
