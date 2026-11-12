<?php

namespace App\Http\Controllers\BuildingInfo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


use App\Models\BuildingInfo\SanitationSystemTechnology;
use App\Models\BuildingInfo\StructureType;
use App\Models\BuildingInfo\FunctionalUse;
use App\Models\BuildingInfo\UseCategory;
use App\Models\BuildingInfo\Building;
use App\Models\BuildingInfo\Owner;
use App\Models\BuildingInfo\SanitationSystem;
use App\Models\Fsm\Containment;
use App\Models\Fsm\Application;
use App\Models\Fsm\ContainmentType;
use App\Helpers\KeywordMatcher;

use App\Models\Fsm\Ctpt;
use App\Models\UtilityInfo\Drain;
use App\Models\BuildingInfo\BuildContain;
use App\Models\BuildingInfo\WaterSource;
use App\Models\UtilityInfo\SewerLine;
use App\Models\UtilityInfo\Roadline;
use App\Models\LayerInfo\Ward;
use App\Models\LayerInfo\Lic;
use App\Models\UtilityInfo\WaterSupplys;
use App\Models\Fsm\ContaimentType;
use App\Enums\LicStatus;
use App\Services\BuildingInfo\BuildingStructureService;
use App\Http\Requests\BuildingInfo\BuildingRequest;
use DOMDocument;
use DomXpath;
use DB;
use Redirect;
use Carbon\Carbon;
use App\Models\Fsm\BuildToilet;
use App\Services\OwnerPiiPresenter;
use App\Services\OwnerPiiAuditService;
use App\Services\ApplicationPiiAccessService;
use App\Services\Fsm\OperationalDataAccessService;
use Illuminate\Support\Str;
use App\Services\PiiAccessService;
use Illuminate\Support\Facades\Auth;

class BuildingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected BuildingStructureService $buildingStructureService;
    protected OwnerPiiPresenter $ownerPiiPresenter;
    protected PiiAccessService $piiAccessService;
    protected ApplicationPiiAccessService $applicationPiiAccessService;
    protected OwnerPiiAuditService $ownerPiiAuditService;
    private $points;

    public function __construct(
        BuildingStructureService $buildingStructureService,
        OwnerPiiPresenter $ownerPiiPresenter,
        PiiAccessService $piiAccessService,
        ApplicationPiiAccessService $applicationPiiAccessService,
        OwnerPiiAuditService $ownerPiiAuditService
    ) {
        $this->middleware('auth');
        $this->middleware(
            'permission:List Building Structures',
            ['only' => ['index', 'getData']]
        );
        $this->middleware(
            'permission:View Building Structure',
            ['only' => ['show']]
        );
        $this->middleware(
            'permission:Add Building Structure',
            ['only' => ['create', 'store']]
        );
        $this->middleware(
            'permission:Edit Building Structure',
            ['only' => ['edit', 'update']]
        );
        $this->middleware(
            'permission:Delete Building Structure',
            ['only' => ['destroy']]
        );
        $this->middleware(
            'permission:Export Building Structures',
            ['only' => ['export']]
        );
        $this->middleware(
            'pii.no-cache',
            ['only' => ['index', 'getData', 'show', 'edit', 'export', 'listContainments', 'mapLookup']]
        );

        $this->buildingStructureService = $buildingStructureService;
        $this->ownerPiiPresenter = $ownerPiiPresenter;
        $this->piiAccessService = $piiAccessService;
        $this->applicationPiiAccessService = $applicationPiiAccessService;
        $this->ownerPiiAuditService = $ownerPiiAuditService;
    }
    public function getData(Request $request)
    {
        $piiUnlocked = $this->ownerPiiListIsUnlocked();

        if ($piiUnlocked) {
            $this->ownerPiiAuditService->record(
                'pii_bulk_viewed',
                true,
                Auth::user(),
                null,
                [
                    'surface' => 'building_data_table',
                    'page_start' => (int) $request->input('start', 0),
                    'page_length' => (int) $request->input('length', 10),
                    'owner_name_filter_used' => $request->filled('ownername'),
                ],
                $this->piiAccessService->authenticationMethod(
                    Auth::user(),
                    'list',
                    PiiAccessService::ALL_OWNERS_RESOURCE_ID
                )
            );
        }

        return $this->buildingStructureService->fetchData(
            $request,
            $piiUnlocked
        );
    }
    public function index()
    {
        $page_title = __("Buildings");
        $piiListUnlocked = $this->ownerPiiListIsUnlocked();
        $canUnlockOwnerPiiList = $this->piiAccessService->canUnlock(Auth::user());
        $piiListUnlockSeconds = $piiListUnlocked
            ? $this->piiAccessService->secondsRemaining(
                Auth::user(),
                'list',
                PiiAccessService::ALL_OWNERS_RESOURCE_ID
            )
            : 0;

        $structure_type = StructureType::orderBy('type', 'asc')->pluck('type', 'id')->all();
        $water_sources = WaterSource::orderBy('source', 'asc')->pluck('source', 'id')->all();

        $sanitation_systems = SanitationSystem::orderBy('sanitation_system', 'asc')->whereNotIn('id', [11])->pluck('sanitation_system', 'id')->all();

        $functional_use = FunctionalUse::orderBy('name')->pluck('name', 'id')->all();
        $floorCount = Building::select('floor_count')
            ->whereNotNull('floor_count')
            ->whereNull('deleted_at')
            ->orderBy('floor_count', 'asc')
            ->pluck('floor_count', 'floor_count');
        $ward = Ward::orderBy('ward', 'asc')->pluck('ward', 'ward')->all();
        $toiletPresence = Building::pluck('toilet_status')->get('*');
        // Capitalize the first letter of each word in the arrays
        $structure_type = array_map('ucwords', $structure_type);
        $water_sources = array_map('ucwords', $water_sources);
        return view('building-info.buildings.index', compact(
            'page_title',
            'structure_type',
            'functional_use',
            'sanitation_systems',
            'water_sources',
            'ward',
            'toiletPresence',
            'floorCount',
            'piiListUnlocked',
            'canUnlockOwnerPiiList',
            'piiListUnlockSeconds'
        ));
    }

    private function ownerPiiListIsUnlocked(): bool
    {
        $user = Auth::user();

        return $user
            && $this->piiAccessService->isUnlocked(
                $user,
                'list',
                PiiAccessService::ALL_OWNERS_RESOURCE_ID
            );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $page_title = __("Add Building");
        $structure_type = StructureType::orderBy('type', 'asc')->pluck('type', 'id')->all();
        $water_source = WaterSource::orderBy('source', 'asc')->pluck('source', 'id')->all();
        // Capitalize the first letter of each word in the arrays
        $structure_type = array_map('ucwords', $structure_type);
        $water_source = moveOthersToEnd(array_map('ucwords', $water_source));
        $toiletConnection = SanitationSystem::whereNotIn('id', [9, 10, 12])->pluck('sanitation_system', 'id')->all();
        $defecationPlace = SanitationSystem::whereIn('id', [9, 10, 12])->pluck('sanitation_system', 'id')->all();
        $containment_type = ContainmentType::pluck('type', 'id')->all();
        $buildingBin = Building::distinct('bin')->pluck('bin', 'bin')->take(10)->whereNull('building_associated_to')->whereNull('deleted_at');

        $bin = BuildContain::distinct('bin')->pluck('bin', 'bin')->take(10)->whereNull('deleted_at');
        $ward = Ward::orderBy('ward')->pluck('ward', 'ward');

        $road_code = Roadline::get(['code', 'name'])->mapWithKeys(function ($item) {
            return [$item->code => ($item->name ? $item->code . ' - ' . $item->name : $item->code)];
        })->toArray();

        $sewer_code = SewerLine::pluck('code', 'code')->whereNull('deleted_at')->all();
        $containment_id = Containment::distinct('id')->pluck('id', 'id')->whereNull('deleted_at');

        $ctpt = Ctpt::where('status', true)
            ->where('type', 'Community Toilet')
            ->get(['id', 'name'])
            ->mapWithKeys(function ($item) {
                return [$item->id => ($item->name ? $item->id . ' - ' . $item->name : $item->id)];
            })
            ->toArray();
        $capitalizedctpt = array_map(function ($value) {
            return ucwords($value);
        }, $ctpt);
        $containment = [];

        $buildingSurvey = null;

        $drain_code = Drain::pluck('code', 'code')->all();


        $licNames = Lic::whereNull('deleted_at')->orderBy('community_name')->pluck('community_name', 'id');

        $models = UseCategory::select('id', 'name', 'functional_use_id')->orderBy('name')->get();
        $functional_use = FunctionalUse::orderBy('name')->pluck('name', 'id')->all();
        $use_category_id = [];
        foreach ($models as $model) {
            $use_category_id[$model->functional_use_id][$model->id] = $model->name;
        }

        $usecatgsJson = json_encode($use_category_id);
        $waterSupply = WaterSupplys::pluck('code', 'code');
        return view('building-info.buildings.create', compact(
            'page_title',
            'buildingBin',
            'bin',
            'containment_id',
            'water_source',
            'structure_type',
            'functional_use',
            'use_category_id',
            'usecatgsJson',
            'containment',
            'road_code',
            'ward',
            'buildingSurvey',
            'sewer_code',
            'ctpt',
            'drain_code',
            'use_category_id',
            'licNames',
            'capitalizedctpt',
            'toiletConnection',
            'defecationPlace',
            'containment_type',
            'waterSupply'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(BuildingRequest $request)
    {
        return ($this->buildingStructureService->storeBuildingData($request));
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Building $building)
    {
        $page_title = __("Building Details");
        $piiUnlocked = $this->ownerPiiIsUnlocked($building, 'view');
        $ownerPii = $building->Owners
            ? ($piiUnlocked
                ? $this->ownerPiiPresenter->presentPlaintext($building->Owners)
                : $this->ownerPiiPresenter->presentMasked($building->Owners))
            : [
                'owner_name' => null,
                'owner_gender' => null,
                'owner_contact' => null,
                'nid' => null,
            ];

        if ($piiUnlocked && $building->Owners) {
            $this->ownerPiiAuditService->record(
                'pii_viewed',
                true,
                Auth::user(),
                (string) $building->public_id,
                ['surface' => 'building_details'],
                $this->piiAccessService->authenticationMethod(
                    Auth::user(),
                    'view',
                    (string) $building->public_id
                )
            );
        }
        $statusLIH = LicStatus::getDescription($building->is_lih);
        $status = LicStatus::getDescription($building->is_lic);
        $containment = $building->containments[0] ?? null;
        $imageSrc = false;
        foreach (['jpg', 'jpeg', 'JPG', 'JPEG'] as $extension) {
            $relativePath = "storage/emptyings/houses/{$building->bin}.{$extension}";
            $absolutePath = public_path($relativePath);
            if (file_exists($absolutePath)) {
                $imageSrc = asset($relativePath) . '?v=' . filemtime($absolutePath);
                break;
            }
        }
        if ($building) {
            return view('building-info.buildings.show', compact('page_title', 'building', 'containment', 'status', 'statusLIH', 'imageSrc', 'ownerPii', 'piiUnlocked'));
        } else {
            return view('errors.404');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Building $building)
    {

        $page_title = __("Edit Building");
        $piiUnlocked = $this->ownerPiiIsUnlocked($building, 'edit');
        $piiUnlockSeconds = $piiUnlocked
            ? $this->piiAccessService->secondsRemaining(
                Auth::user(),
                'edit',
                (string) $building->public_id
            )
            : 0;
        $ownerPii = [
            'owner_name' => '********',
            'owner_gender' => '********',
            'owner_contact' => '********',
            'nid' => '********',
        ];

        if ($piiUnlocked && !empty($building->Owners)) {
            $ownerPii = $this->ownerPiiPresenter->presentPlaintext(
                $building->Owners
            );
        }

        if ($piiUnlocked && !empty($building->Owners)) {
            $this->ownerPiiAuditService->record(
                'pii_viewed',
                true,
                Auth::user(),
                (string) $building->public_id,
                ['surface' => 'building_edit'],
                $this->piiAccessService->authenticationMethod(
                    Auth::user(),
                    'edit',
                    (string) $building->public_id
                )
            );
        }

        // Plaintext enters editable values only during an authorized unlock.
        $building->owner_name = $piiUnlocked ? $ownerPii['owner_name'] : null;
        $building->owner_gender = $piiUnlocked ? $ownerPii['owner_gender'] : null;
        $building->owner_contact = $piiUnlocked ? $ownerPii['owner_contact'] : null;
        $building->nid = $piiUnlocked ? $ownerPii['nid'] : null;

        $building->main_building = $building->building_associated_to ? false : true;
        $structure_type = StructureType::orderBy('type', 'asc')->pluck('type', 'id')->all();
        $water_source = WaterSource::orderBy('source', 'asc')->pluck('source', 'id')->all();
        // Capitalize the first letter of each word in the arrays
        $structure_type = array_map('ucwords', $structure_type);
        $water_source = moveOthersToEnd(array_map('ucwords', $water_source));
        $building->lic_status = $building->lic_id ? "1" : "0";
        // when toilet presence is NO
        // handling defecation place when sanitation system is shared toilet, open defecation and community toilet
        if ($building->sanitation_system_id == 10 || $building->sanitation_system_id == 9 || $building->sanitation_system_id == 12) {
            $building->defecation_place = $building->sanitation_system_id;
        }
        $toiletConnection = SanitationSystem::whereNotIn('id', [9, 10, 12])->pluck('sanitation_system', 'id')->all();
        $defecationPlace = SanitationSystem::whereIn('id', [9, 10, 12])->pluck('sanitation_system', 'id')->all();
        $building->ctpt_name = $building->sharedToilets->pluck('id') ?? null;
        $buildingBin = Building::distinct('bin')->pluck('bin', 'bin')->whereNull('building_associated_to')->whereNull('deleted_at');
        $bin = BuildContain::distinct('bin')->pluck('bin', 'bin')->whereNull('deleted_at');
        $ward = Ward::orderBy('ward')->pluck('ward', 'ward');
        $road_code = Roadline::get(['code', 'name'])->mapWithKeys(function ($item) {
            return [$item->code => ($item->name ? $item->code . ' - ' . $item->name : $item->code)];
        })->toArray();
        $sewer_code = SewerLine::pluck('code', 'code')->whereNull('deleted_at')->all();
        $containment_id = Containment::distinct('id')->pluck('id', 'id')->whereNull('deleted_at');


        $ctpt = Ctpt::where('status', true)
            ->where('type', 'Community Toilet')
            ->get(['id', 'name'])
            ->mapWithKeys(function ($item) {
                return [$item->id => ($item->name ? $item->id . ' - ' . $item->name : $item->id)];
            })
            ->toArray();
        $capitalizedctpt = array_map(function ($value) {
            return ucwords($value);
        }, $ctpt);
        $containment = [];
        $buildingSurvey = null;
        $drain_code = Drain::pluck('code', 'code')->all();
        $licNames = Lic::whereNull('deleted_at')->orderBy('community_name')->pluck('community_name', 'id');
        $models = UseCategory::select('id', 'name', 'functional_use_id')->orderBy('name')->get();
        $functional_use = FunctionalUse::orderBy('name')->pluck('name', 'id')->all();
        $use_category_id = [];
        foreach ($models as $model) {
            $use_category_id[$model->functional_use_id][$model->id] = $model->name;
        }

        $usecatgsJson = json_encode($use_category_id);

        $drain_status = 0;
        $sewer_status = 0;
        // flag for sewer and drain code to display them if containment type has sewer or drain
        if ($building->containments()->exists()) {
            foreach ($building->containments as $containment) {
                if (KeywordMatcher::matchKeywords($containment->containmentType->type, ["drain"])) {
                    $drain_status = true;
                }
                if (KeywordMatcher::matchKeywords($containment->containmentType->type, ["sewer"])) {
                    $sewer_status = true;
                }
            }
        }
        $waterSupply = WaterSupplys::pluck('code', 'code');
        return view('building-info.buildings.edit', compact(
            'page_title',
            'building',
            'buildingBin',
            'bin',
            'containment_id',
            'water_source',
            'structure_type',
            'functional_use',
            'usecatgsJson',
            'containment',
            'road_code',
            'ward',
            'buildingSurvey',
            'sewer_code',
            'ctpt',
            'drain_code',
            'use_category_id',
            'licNames',
            'capitalizedctpt',
            'toiletConnection',
            'defecationPlace',
            'waterSupply',
            'drain_status',
            'sewer_status',
            'use_category_id',
            'ownerPii',
            'piiUnlocked',
            'piiUnlockSeconds'
        ));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(BuildingRequest $request, Building $building)
    {
        $containsOwnerPii = $this->containsOwnerPiiUpdate($request);

        if ($containsOwnerPii && !$this->ownerPiiIsUnlocked($building, 'edit')) {
            $this->ownerPiiAuditService->record(
                'pii_update_denied',
                false,
                Auth::user(),
                (string) $building->public_id,
                ['reason' => 'privileged_session_required'],
                'session'
            );

            abort(403, __('Owner PII must be unlocked before it can be changed.'));
        }

        if ($containsOwnerPii) {
            $this->ownerPiiAuditService->record(
                'pii_update_authorized',
                true,
                Auth::user(),
                (string) $building->public_id,
                ['surface' => 'building_edit'],
                $this->piiAccessService->authenticationMethod(
                    Auth::user(),
                    'edit',
                    (string) $building->public_id
                )
            );
        }

        return $this->buildingStructureService->updateBuildingData(
            $request,
            $building->bin
        );
    }

    private function containsOwnerPiiUpdate(Request $request): bool
    {
        foreach (['owner_name', 'owner_gender', 'owner_contact', 'nid'] as $field) {
            if ($request->filled($field)) {
                return true;
            }
        }

        return false;
    }

    private function canUnlockOwnerPii(): bool
    {
        $user = Auth::user();

        return $this->piiAccessService->canUnlock($user);
    }

    private function ownerPiiIsUnlocked(
        Building $building,
        string $scope
    ): bool
    {
        $user = Auth::user();

        return $this->canUnlockOwnerPii()
            && $this->piiAccessService->isUnlocked(
                $user,
                $scope,
                (string) $building->public_id
            );
    }
    public function history(Building $building)
    {
        if ($building) {
            $page_title = __("Building History");
            return view('building-info.buildings.history', compact('page_title', 'building'));
        } else {
            abort(404);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Building $building)
    {
        if ($building) {
            if ($building->containments()->exists()) {
                return redirect('building-info/buildings')->with('error', __("Failed to delete Building, it is associated with Containment Information"));
            } else {
                $building->delete();
                return redirect('building-info/buildings')->with('success', __("Building Deleted Successfully"));
            }
        } else {
            return redirect('building-info/buildings')->with('error', __("Failed to Delete Building"));
        }
    }
    public function export()
    {
        $user = Auth::user();
        $includeOwnerPii = $this->ownerPiiListIsUnlocked()
            && $user
            && $user->can('Export Owner PII');

        if ($includeOwnerPii) {
            // The ordinary Building export may contain plaintext owner values
            // only while this same user's list-wide reveal grant is active.
            // Record the disclosure separately from the dedicated PII export.
            $this->ownerPiiAuditService->record(
                'pii_exported',
                true,
                $user,
                null,
                [
                    'surface' => 'building_standard_csv_export',
                    'includes_owner_pii' => true,
                    'owner_field_count' => 3,
                ],
                $this->piiAccessService->authenticationMethod(
                    $user,
                    'list',
                    PiiAccessService::ALL_OWNERS_RESOURCE_ID
                ) ?? 'password_reentry'
            );
        }

        return $this->buildingStructureService->fetchExport($includeOwnerPii);
    }
    public function getHouseNumbers()
    {
        return ($this->buildingStructureService->fetchHouseNumber());
    }

    //counts the total number of building on the road using road code ,using ajax the house address value if provided
    public function checkHouse(Request $request)
    {
        $roadCode = $request->input('road_code');
        $buildingCount = Building::where('road_code', $roadCode)->count();
        return response()->json([
            'exists' => $buildingCount > 0,
            'count' => $buildingCount,
        ]);
    }



    public function getHouseNumbersAll()
    {
        return ($this->buildingStructureService->fetchHouseNumberAll());
    }
    public function listContainments($id)
    {
        $building = ($id instanceof Building) ? $id : (
            Str::isUuid($id)
                ? Building::where('public_id', $id)->first()
                : Building::where('bin', $id)->first()
        );

        if (!$building) {
            return response()->json(['message' => __('Building not found.')], 404);
        }

        return $this->buildingMapLookupPayload($building);
    }

    public function mapLookup(Request $request)
    {
        $validated = $request->validate([
            'bin' => ['required', 'string', 'max:100'],
            'application_id' => ['nullable', 'integer'],
        ]);

        $building = Building::where('bin', $validated['bin'])->firstOrFail();
        $applicationPiiUnlocked = false;
        $applicationId = isset($validated['application_id'])
            ? (int) $validated['application_id']
            : null;

        if ($applicationId !== null) {
            $application = Application::findOrFail($applicationId);
            OperationalDataAccessService::authorizeApplication($application);
            $user = $request->user();

            $applicationPiiUnlocked = $user
                && $user->can('Add Application')
                && $this->applicationPiiAccessService->isUnlocked(
                    $user,
                    'owner_lookup',
                    ApplicationPiiAccessService::ALL_APPLICATIONS_RESOURCE_ID
                );
        }

        return $this->buildingMapLookupPayload(
            $building,
            $applicationPiiUnlocked,
            $applicationId
        );
    }

    private function buildingMapLookupPayload(
        Building $building,
        bool $applicationPiiUnlocked = false,
        ?int $applicationId = null
    ): array
    {
        $buildingPiiUnlocked = $this->ownerPiiIsUnlocked($building, 'view');
        $piiUnlocked = $applicationPiiUnlocked || $buildingPiiUnlocked;
        $ownerPii = [
            'owner_name' => null,
            'owner_gender' => null,
        ];

        if ($piiUnlocked && $building->Owners) {
            $plaintext = $this->ownerPiiPresenter->presentPlaintext(
                $building->Owners
            );
            $ownerPii['owner_name'] = $plaintext['owner_name'];
            $ownerPii['owner_gender'] = $plaintext['owner_gender'];

            $usingApplicationGrant = $applicationPiiUnlocked;
            $this->ownerPiiAuditService->record(
                $usingApplicationGrant
                    ? 'application_owner_pii_lookup_viewed'
                    : 'pii_viewed',
                true,
                Auth::user(),
                $usingApplicationGrant ? null : (string) $building->public_id,
                [
                    'surface' => 'emptying_locate_building',
                    'application_id' => $applicationId,
                    'building_public_id' => (string) $building->public_id,
                ],
                $usingApplicationGrant
                    ? $this->applicationPiiAccessService->authenticationMethod(
                        Auth::user(),
                        'owner_lookup',
                        ApplicationPiiAccessService::ALL_APPLICATIONS_RESOURCE_ID
                    )
                    : $this->piiAccessService->authenticationMethod(
                        Auth::user(),
                        'view',
                        (string) $building->public_id
                    )
            );
        }

        $containments = $building->containments->map(function ($containment) {
            return [
                'id' => $containment->id,
                'public_id' => $containment->public_id,
                'type' => optional($containment->containmentType)->type,
            ];
        })->values()->all();

        $popContentsHtml = empty($containments)
            ? __('No Containment Found.')
            : $this->popUpContentHtml($containments);

        return [
            'title' => __('Containments Connected to Building') . ': ' . $building->bin,
            'popContentsHtml' => $popContentsHtml,
            'containments' => $containments,
            'building_bin' => $building->bin,
            'building_public_id' => $building->public_id,
            'owner_pii_locked' => !$piiUnlocked,
            'owner_name' => $ownerPii['owner_name'],
            'owner_gender' => $ownerPii['owner_gender'],
        ];
    }

    public function popUpContentHtml($containments)
    {
        $tbody = '<tbody>';
        foreach ($containments as $row1) {
            $containmentPublicId = (string) ($row1['public_id'] ?? '');
            $tbody .= '<tr data-containment-public-id="' . e($containmentPublicId) . '">';
            $tbody .= '<td>' . $row1['id'] . '</td>';
            $tbody .= '<td>' . ($row1['containment_type']['type'] ?? ($row1['type'] ?? '-')) . '</td>';
            $tbody .= '<td class="text-center">
                        <a title="' . e(__('Containment Detail')) . '"
                           href="' . action("Fsm\ContainmentController@show", ['containment' => $containmentPublicId]) . '"
                           class="btn btn-info btn-sm mb-1">
                           <i class="fa fa-info-circle" aria-hidden="true"></i>
                        </a>
                       </td>';
            $tbody .= '</tr>';
        }
        $tbody .= '</tbody>';

        $thead = '<thead>';
        $thead .= '<tr>';
        $thead .= '<th>' . __('Containment ID') . '</th>';
        $thead .= '<th>' . __('Containment Type') . '</th>';
        $thead .= '<th>' . __('Actions') . '</th>';
        $thead .= '</tr>';
        $thead .= '</thead>';

        $html = '<table class="table table-bordered">';
        $html .= $thead;
        $html .= $tbody;
        $html .= '</table>';

        return $html;
    }


    public function getContainmentTypes(Request $request)
    {
        $sanitationSystemId = $request->input('sanitation_system_id');
        $containmentTypes = ContainmentType::where('sanitation_system_id', $sanitationSystemId)->get();
        return response()->json($containmentTypes);
    }

    public function getCTPTHouseNumbers()
    {
        return ($this->buildingStructureService->fetchCTPTHouseNumber());
    }


    public function getUseCategories($functionalUseId)
    {
        $useCategories = UseCategory::where('functional_use_id', $functionalUseId)
            ->orderBy('id')
            ->pluck('name', 'id');

        return response()->json($useCategories);
    }

    public function getSanitationSystem()
    {
        $building = Building::find(request()->bin);

        $sewer_code = $building->sewer_code ?? "No Sewer Code";
        $drain_code = $building->drain_code ?? "No Drain Code";
        $containment_ids = implode(',', $building->containments()->get()->pluck('id')->toArray()) ?? "No Containment Connected";
        $containment_infos = [];
        foreach ($building->containments()->get() as $containment) {
            array_push($containment_infos, $containment->containmentType->type . " (" . $containment->id . ") <br>");
        }
        $containments = $containment_infos ? implode('', $containment_infos) : "No Containment Connected<br>";
        $data = "Building Toilet Connection:" . $building->SanitationSystem->sanitation_system . "<br> Containment Info:<br>" . $containments . "Drain Code: " . $drain_code . "<br>Sewer Code:" . $sewer_code;
        if ($building) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }
    }
}
