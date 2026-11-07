<?php

namespace App\Http\Controllers\Cwis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;
use App\Services\Cwis\CwisIndicatorService;
use App\Http\Requests\Cwis\CwisIndicatorDataRequest;
use Illuminate\Validation\ValidationException;

class CwisGeneratorDataController extends Controller
{
    private CwisIndicatorService $indicators;
    private \App\Services\Cwis\CwisFormService $forms;

    public function __construct(CwisIndicatorService $indicators)
    {
        $this->indicators = $indicators;
        $this->forms = app(\App\Services\Cwis\CwisFormService::class);
        $this->middleware('auth');
        $this->middleware(\App\Http\Middleware\CwisFormResponse::class)->only(['store', 'update', 'approve']);
        $this->middleware('permission:List CWIS');
    }

    private function filterData(Request $request, bool $listing = false): array
    {
        $currentYear = now()->year;
        $years = collect(range($currentYear, $currentYear - 5));
        $serviceOutcomes = ['equity' => __('Equity'), 'safety' => __('Safety'), 'sustainability' => __('Sustainability'), 'responsibility' => __('Responsibility'), 'accountability' => __('Accountability'), 'rpm' => __('Resource Planning and Management')];
        $filters = $request->validate([
            'country_code' => ['nullable', Rule::in(array_keys(CwisIndicatorService::COUNTRIES))],
            'year' => ['nullable', 'integer', Rule::in($years->all())],
            'service_outcome' => ['nullable', Rule::in(array_keys($serviceOutcomes))],
        ]);
        $year = $filters['year'] ?? ($listing ? null : $currentYear);
        $country = $filters['country_code'] ?? $this->indicators->activeCountry();
        $countryLabel = __(CwisIndicatorService::COUNTRIES[$country]);
        $outcomes = $this->indicators->forCountry($country)->pluck('outcome')->unique()->all();
        $serviceOutcomes = array_intersect_key($serviceOutcomes, array_flip($outcomes));

        return compact('years', 'serviceOutcomes', 'filters', 'year', 'country', 'countryLabel');
    }

    public function index(Request $request)
    {
        $data = $this->filterData($request, true);
        $activeCountry = $this->indicators->activeCountry();
        if ($data['country'] !== $activeCountry) {
            return redirect()->action('Cwis\CwisGeneratorDataController@index', array_merge(
                $data['filters'], ['country_code' => $activeCountry]
            ))->with('cwis_input_notice', __('The listing now uses the country selected in CWIS Settings.'));
        }
        $fields = $this->forms->applicable($this->forms->definitions(),
            $this->indicators->forCountry($data['country'])->pluck('indicator_code')->all());
        // Each manual-input outcome has its own source records and available years.
        $usedYears = DB::table('cwis.equity_submissions')->whereNull('deleted_at')
            ->where('country_code', $data['country'])->pluck('reporting_year')->all();
        $availableInputYears = $data['years']->diff($usedYears)->values();
        $inputOutcomes = empty($fields) ? [] : ['equity' => __('Equity')];
        $inputYearsByOutcome = ['equity' => $availableInputYears->all()];
        if (app(\App\Services\Cwis\CwisSafetyFormService::class)->available($data['country'])) {
            $inputOutcomes['safety'] = __('Safety');
            $usedSafetyYears = DB::table('cwis.safety_submissions')->where('country_code', $data['country'])->pluck('reporting_year')->all();
            $inputYearsByOutcome['safety'] = $data['years']->diff($usedSafetyYears)->values()->all();
        }
        if (app(\App\Services\Cwis\CwisSustainabilityFormService::class)->available($data['country'])) {
            $inputOutcomes['sustainability'] = __('Sustainability');
            $data['serviceOutcomes']['sustainability'] = __('Sustainability');
            $usedSustainabilityYears = DB::table('cwis.sustainability_submissions')
                ->where('country_code', $data['country'])->pluck('reporting_year')->all();
            $inputYearsByOutcome['sustainability'] = $data['years']->diff($usedSustainabilityYears)->values()->all();
            $availableInputYears = $availableInputYears->merge($inputYearsByOutcome['sustainability'])->unique()->sortDesc()->values();
        }
        if (app(\App\Services\Cwis\CwisResponsibilityFormService::class)->available($data['country'])) {
            $inputOutcomes['responsibility'] = __('Responsibility');
            $data['serviceOutcomes']['responsibility'] = __('Responsibility');
            $usedResponsibilityYears = DB::table('cwis.responsibility_submissions')
                ->where('country_code', $data['country'])->pluck('reporting_year')->all();
            $inputYearsByOutcome['responsibility'] = $data['years']->diff($usedResponsibilityYears)->values()->all();
        }
        if (app(\App\Services\Cwis\CwisAccountabilityFormService::class)->available($data['country'])) {
            $inputOutcomes['accountability'] = __('Accountability');
            $usedYears = DB::table('cwis.accountability_submissions')->where('country_code', $data['country'])->pluck('reporting_year')->all();
            $inputYearsByOutcome['accountability'] = $data['years']->diff($usedYears)->values()->all();
        }
        if (app(\App\Services\Cwis\CwisRpmFormService::class)->available($data['country'])) {
            $inputOutcomes['rpm'] = __('Resource Planning and Management');
            $usedYears = DB::table('cwis.rpm_submissions')->where('country_code', $data['country'])->pluck('reporting_year')->all();
            $inputYearsByOutcome['rpm'] = $data['years']->diff($usedYears)->values()->all();
        }
        $availableInputYears = collect(array_keys($inputOutcomes))->flatMap(function ($outcome) use ($inputYearsByOutcome) {
            return $inputYearsByOutcome[$outcome] ?? [];
        })->unique()->sortDesc()->values();

        return view('cwis.generator-data.index', $data + [
            'page_title' => __('CWIS Indicator Generation Data'),
            'inputOutcomes' => $inputOutcomes,
            'inputYearsByOutcome' => $inputYearsByOutcome,
            'availableInputYears' => $availableInputYears,
        ]);
    }

    public function getData(Request $request)
    {
        $data = $this->filterData($request, true);
        $activeCountry = $this->indicators->activeCountry();
        if ($data['country'] !== $activeCountry) {
            return response()->json([
                'country_changed' => true,
                'redirect' => action('Cwis\CwisGeneratorDataController@index', array_merge(
                    $data['filters'], ['country_code' => $activeCountry]
                )),
            ], 409);
        }
        $rows = DB::table('cwis.equity_submissions')->select('id', 'reporting_year as year', 'status', 'updated_at')
            ->selectRaw("'equity' as outcome_key")
            ->whereNull('deleted_at')->where('country_code', $data['country']);
        if ($data['year'] !== null) {
            $rows->where('reporting_year', $data['year']);
        }
        if (!empty($data['filters']['service_outcome']) && $data['filters']['service_outcome'] !== 'equity') {
            $rows->whereRaw('1 = 0');
        }
        if (app(\App\Services\Cwis\CwisSustainabilityFormService::class)->ready()) {
            $sustainability = DB::table('cwis.sustainability_submissions')->select('id', 'reporting_year as year', 'status', 'updated_at')
                ->selectRaw("'sustainability' as outcome_key")->where('country_code', $data['country']);
            if ($data['year'] !== null) { $sustainability->where('reporting_year', $data['year']); }
            if (!empty($data['filters']['service_outcome']) && $data['filters']['service_outcome'] !== 'sustainability') {
                $sustainability->whereRaw('1 = 0');
            }
            $rows = DB::query()->fromSub($rows->unionAll($sustainability), 'submissions')->select('submissions.*');
        }

        if (app(\App\Services\Cwis\CwisResponsibilityFormService::class)->ready()) {
            $responsibility = DB::table('cwis.responsibility_submissions')->select('id', 'reporting_year as year', 'status', 'updated_at')
                ->selectRaw("'responsibility' as outcome_key")->where('country_code', $data['country']);
            if ($data['year'] !== null) { $responsibility->where('reporting_year', $data['year']); }
            if (!empty($data['filters']['service_outcome']) && $data['filters']['service_outcome'] !== 'responsibility') {
                $responsibility->whereRaw('1 = 0');
            }
            $rows = DB::query()->fromSub($rows->unionAll($responsibility), 'outcome_submissions')->select('outcome_submissions.*');
        }

        if (app(\App\Services\Cwis\CwisAccountabilityFormService::class)->available($data['country'])) {
            $accountability = DB::table('cwis.accountability_submissions')->select('id', 'reporting_year as year', 'status', 'updated_at')
                ->selectRaw("'accountability' as outcome_key")->where('country_code', $data['country']);
            if ($data['year'] !== null) { $accountability->where('reporting_year', $data['year']); }
            if (!empty($data['filters']['service_outcome']) && $data['filters']['service_outcome'] !== 'accountability') {
                $accountability->whereRaw('1 = 0');
            }
            $rows = DB::query()->fromSub($rows->unionAll($accountability), 'all_outcome_submissions')->select('all_outcome_submissions.*');
        }

        if (app(\App\Services\Cwis\CwisRpmFormService::class)->available($data['country'])) {
            $rpm = DB::table('cwis.rpm_submissions')->select('id', 'reporting_year as year', 'status', 'updated_at')
                ->selectRaw("'rpm' as outcome_key")->where('country_code', $data['country']);
            if ($data['year'] !== null) { $rpm->where('reporting_year', $data['year']); }
            if (!empty($data['filters']['service_outcome']) && $data['filters']['service_outcome'] !== 'rpm') {
                $rpm->whereRaw('1 = 0');
            }
            $rows = DB::query()->fromSub($rows->unionAll($rpm), 'rpm_outcome_submissions')->select('rpm_outcome_submissions.*');
        }

        if (app(\App\Services\Cwis\CwisSafetyFormService::class)->available($data['country'])) {
            $safety = DB::table('cwis.safety_submissions')->select('id', 'reporting_year as year', 'status', 'updated_at')
                ->selectRaw("'safety' as outcome_key")->where('country_code', $data['country']);
            if ($data['year'] !== null) { $safety->where('reporting_year', $data['year']); }
            if (!empty($data['filters']['service_outcome']) && $data['filters']['service_outcome'] !== 'safety') {
                $safety->whereRaw('1 = 0');
            }
            $rows = DB::query()->fromSub($rows->unionAll($safety), 'safety_outcome_submissions')->select('safety_outcome_submissions.*');
        }

        return DataTables::of($rows)
            ->addColumn('service_outcome', function ($row) { return $row->outcome_key === 'rpm' ? __('Resource Planning and Management') : __(ucfirst($row->outcome_key)); })
            ->addColumn('status_label', function ($row) { return __(ucfirst($row->status)); })
            ->addColumn('action', function ($row) {
                if ($row->outcome_key === 'safety') {
                    return view('cwis.safety.actions', compact('row'))->render();
                }
                if ($row->outcome_key === 'rpm') {
                    return view('cwis.rpm.actions', compact('row'))->render();
                }
                if ($row->outcome_key === 'accountability') {
                    return view('cwis.accountability.actions', compact('row'))->render();
                }
                if ($row->outcome_key === 'responsibility') {
                    return view('cwis.responsibility.actions', compact('row'))->render();
                }
                if ($row->outcome_key === 'sustainability') {
                    return view('cwis.sustainability.actions', compact('row'))->render();
                }
                return view('cwis.generator-data.actions', compact('row'))->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function show(Request $request, int $submission)
    {
        $data = $this->filterData($request);
        $record = DB::table('cwis.equity_submissions')->where('id', $submission)
            ->where('country_code', $data['country'])->whereNull('deleted_at')->first();
        abort_unless($record, 404);
        $fields = $this->forms->applicable($this->forms->definitions(),
            $this->indicators->forCountry($record->country_code)->pluck('indicator_code')->all());
        $sourceRows = [];
        foreach (array_unique(array_column($fields, 'storage_table')) as $table) {
            $sourceRows[$table] = DB::table($table)->where('submission_id', $record->id)->whereNull('deleted_at')->first();
        }
        return view('cwis.generator-data.show', $data + [
            'page_title' => __('CWIS Indicator Data'), 'submission' => $record,
            'fields' => $fields, 'sourceRows' => $sourceRows,
            'evidenceNames' => DB::table('cwis.equity_documents')->whereIn('id', collect($fields)
                ->filter(function ($field) { return (bool) $field->document_column; })
                ->map(function ($field) use ($sourceRows) { return $sourceRows[$field->storage_table]->{$field->document_column} ?? null; })
                ->filter()->all())->pluck('original_name', 'id'),
            'canManageCountry' => $record->country_code === $this->indicators->activeCountry(),
            'hasGeneratedResults' => DB::table('cwis.data_cwis')->where('year',$record->reporting_year)->exists(),
        ]);
    }

    public function approve(CwisIndicatorDataRequest $request, int $submission)
    {
        abort_unless(auth()->user()->can('Approve CWIS'), 403);
        $country = $this->indicators->activeCountry();
        if ($request->boolean('review_form')) {
            $record = DB::table('cwis.equity_submissions')->where('id',$submission)
                ->where('country_code',$country)->whereNull('deleted_at')->first();
            abort_unless($record,404);
            abort_unless(in_array($record->status,['draft','submitted'],true),403,__('Approved submissions are read-only.'));
            $request->validate([
                'year' => ['required','integer',Rule::in([$record->reporting_year])],
                'country_code' => ['required',Rule::in([$record->country_code])],
                'save_mode' => ['required',Rule::in(['submitted'])],
            ]);
            return $this->saveSubmission($request,$submission,true);
        }
        try {
        $year = DB::transaction(function () use ($submission,$country) {
            $record = DB::table('cwis.equity_submissions')->where('id',$submission)
                ->where('country_code',$country)->whereNull('deleted_at')->lockForUpdate()->first();
            abort_unless($record,404);
            if (!in_array($record->status, ['draft','submitted'], true)) {
                throw ValidationException::withMessages(['submission' => __('Only a draft or submitted record can be approved.')]);
            }
            app(\App\Services\Cwis\CwisEquityWorkflowService::class)->validateSource($record);
            DB::table('cwis.equity_submissions')->where('id',$record->id)->update([
                'status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now(),
                'submitted_by' => $record->submitted_by ?? auth()->id(), 'submitted_at' => $record->submitted_at ?? now(),
                'updated_by' => auth()->id(), 'updated_at' => now(),
            ]);
            return $record->reporting_year;
        });
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) { throw $exception; }
            return redirect()->route('cwis.generator-data.show', ['submission' => $submission,'country_code' => $country])
                ->withErrors($exception->errors());
        }
        return $this->approvalRedirect($submission, $country, $year);
    }

    public function calculate(Request $request, int $submission)
    {
        abort_unless(auth()->user()->can('Add CWIS'),403);
        $country = $this->indicators->activeCountry();
        $record = DB::table('cwis.equity_submissions')->where('id',$submission)
            ->where('country_code',$country)->whereNull('deleted_at')->first();
        abort_unless($record,404);
        try {
        DB::transaction(function () use ($record,$country) {
            DB::select('SELECT pg_advisory_xact_lock(73921, ?)', [(int) $record->reporting_year]);
            $source = DB::table('cwis.equity_submissions')->where('id',$record->id)
                ->whereNull('deleted_at')->lockForUpdate()->first();
            $workflow = app(\App\Services\Cwis\CwisEquityWorkflowService::class);
            $approved = $workflow->approvedSubmission($record->reporting_year,$country);
            if (!$source || !$approved || $approved->id !== $source->id) {
                throw ValidationException::withMessages(['submission' => __('Use the latest approved submission to calculate this year. Submitted records cannot be calculated.')]);
            }
            $workflow->validateSource($source);
            app(\App\Services\Cwis\CwisSustainabilityWorkflowService::class)->requireApproval($record->reporting_year, $country);
            app(\App\Services\Cwis\CwisResponsibilityWorkflowService::class)->requireApproval($record->reporting_year, $country);
            app(\App\Services\Cwis\CwisAccountabilityWorkflowService::class)->requireApproval($record->reporting_year, $country);
            app(\App\Services\Cwis\CwisRpmWorkflowService::class)->requireApproval($record->reporting_year, $country);
            app(\App\Services\Cwis\CwisSafetyWorkflowService::class)->requireApproval($record->reporting_year, $country);
            $results = DB::table('cwis.data_cwis')->where('year',$record->reporting_year)->orderBy('id')->get();
            if ($results->isNotEmpty()) {
                $backup = json_encode([
                    'year' => $record->reporting_year, 'country' => $country,
                    'results' => $results, 'generation_run' => DB::table('cwis.generation_runs')->where('year',$record->reporting_year)->first(),
                    'sustainability_generation_run' => DB::table('cwis.sustainability_generation_runs')->where('year',$record->reporting_year)->first(),
                    'responsibility_generation_run' => DB::table('cwis.responsibility_generation_runs')->where('year',$record->reporting_year)->first(),
                    'accountability_generation_run' => DB::table('cwis.accountability_generation_runs')->where('year',$record->reporting_year)->first(),
                    'rpm_generation_run' => DB::table('cwis.rpm_generation_runs')->where('year',$record->reporting_year)->first(),
                ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                $path = 'cwis/result-backups/' . $record->reporting_year . '_' . gmdate('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.json';
                if (!Storage::disk('local')->put($path,$backup)) {
                    throw new \RuntimeException('Unable to back up existing results. No results were replaced.');
                }
                DB::table('cwis.data_cwis')->where('year',$record->reporting_year)->delete();
            }
            DB::select('SELECT public.generate_cwis_with_rpm(?)', [(int) $record->reporting_year]);
        });
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) { throw $exception; }
            return redirect()->route('cwis.generator-data.show', ['submission' => $submission,'country_code' => $country])
                ->withErrors($exception->errors());
        } catch (\Throwable $exception) {
            report($exception);
            if ($request->expectsJson()) { throw $exception; }
            return redirect()->route('cwis.generator-data.show', ['submission' => $submission,'country_code' => $country])
                ->withErrors(['calculation' => __('CWIS rebuilding failed. Existing results were preserved. Please try again or contact your administrator.')]);
        }
        return redirect()->route('cwis.generator-data.index', ['country_code' => $country])
            ->with('cwis_saved', __('CWIS results rebuilt successfully for reporting year :year.', ['year' => $record->reporting_year]));
    }

    public function create(Request $request)
    {
        if ($request->input('service_outcome') === 'safety') {
            return redirect()->route('cwis.safety.create', $request->only('country_code', 'year'));
        }
        if ($request->input('service_outcome') === 'rpm') {
            return redirect()->route('cwis.rpm.create', $request->only('year', 'country_code'));
        }
        if ($request->input('service_outcome') === 'accountability') {
            return redirect()->route('cwis.accountability.create', $request->only('year', 'country_code'));
        }
        if ($request->input('service_outcome') === 'responsibility') {
            return redirect()->route('cwis.responsibility.create', $request->only('year', 'country_code'));
        }
        if ($request->input('service_outcome') === 'sustainability') {
            return redirect()->route('cwis.sustainability.create', $request->only('year', 'country_code'));
        }
        $data = $this->filterData($request);
        $allowedCodes = $this->indicators->forCountry($data['country'])->pluck('indicator_code')->all();
        $request->validate([
            'service_outcome' => ['nullable', Rule::in(['equity'])],
        ]);
        $fields = $this->forms->applicable($this->forms->definitions(), $allowedCodes);
        ['sections' => $sections, 'policies' => $policies] = $this->forms->presentation($fields);
        if (empty($sections) && empty($policies)) {
            return redirect()->action('Cwis\CwisGeneratorDataController@index', [
                'country_code' => $data['country'], 'year' => $data['year'],
            ])->with('cwis_input_notice', __('No indicator input form is available for the selected country. Its mapped indicators use existing IMIS data.'));
        }
        if ($this->submissionExists($data['country'], $data['year'])) {
            return redirect()->action('Cwis\CwisGeneratorDataController@index', [
                'country_code' => $data['country'], 'year' => $data['year'], 'service_outcome' => 'equity',
            ])->with('cwis_input_notice', __('Equity indicator data already exists for this year and country. Please use the existing record.'));
        }

        return view('cwis.generator-data.create', $data + [
            'page_title' => __('Add Indicator Data'),
            'sections' => $sections,
            'policies' => $policies,
        ]);
    }

    public function edit(Request $request, int $submission)
    {
        return $this->submissionForm($request,$submission,false);
    }

    public function review(Request $request, int $submission)
    {
        return $this->submissionForm($request,$submission,true);
    }

    private function submissionForm(Request $request, int $submission, bool $approvalReview)
    {
        abort_unless(auth()->user()->can($approvalReview ? 'Approve CWIS' : 'Edit CWIS'),403);
        $country = $this->indicators->activeCountry();
        $record = DB::table('cwis.equity_submissions')->where('id',$submission)
            ->where('country_code',$country)->whereNull('deleted_at')->first();
        abort_unless($record,404);
        abort_unless(in_array($record->status,['draft','submitted'],true),403,__('Approved submissions are read-only.'));
        $data = $this->filterData(Request::create('/', 'GET', ['country_code' => $country,'year' => $record->reporting_year]));
        $fields = $this->forms->applicable($this->forms->definitions(),$this->indicators->forCountry($country)->pluck('indicator_code')->all());
        $presentation = $this->forms->presentation($fields);
        if ($approvalReview) {
            foreach ($presentation['sections'] as &$section) {
                foreach ($section['fields'] as &$fieldDefinition) { $fieldDefinition[7] = true; }
                unset($fieldDefinition);
            }
            unset($section);
            foreach ($presentation['policies'] as &$policyDefinition) { $policyDefinition[5] = true; }
            unset($policyDefinition);
        }
        $sourceRows = [];
        foreach (array_unique(array_column($fields,'storage_table')) as $table) {
            $sourceRows[$table] = DB::table($table)->where('submission_id',$record->id)->whereNull('deleted_at')->first();
        }
        $savedInputs = $savedPolicies = $savedEvidence = [];
        foreach ($fields as $field) {
            $row = $sourceRows[$field->storage_table];
            $value = $row->{$field->storage_column} ?? null;
            if ($field->kind === 'number') { $savedInputs[$field->input_name] = $value; }
            else {
                $savedPolicies[$field->input_name] = $value === null ? '' : ($value ? 'Yes' : 'No');
                $document = $field->document_column ? ($row->{$field->document_column} ?? null) : null;
                if ($document) {
                    $savedEvidence[$field->input_name] = DB::table('cwis.equity_documents')->where('id',$document)->first();
                }
            }
        }
        return view('cwis.generator-data.create', $data + $presentation + [
            'page_title' => $approvalReview ? __('Review CWIS Indicator Data') : __('Edit CWIS Indicator Data'), 'submission' => $record,
            'savedInputs' => $savedInputs, 'savedPolicies' => $savedPolicies, 'savedEvidence' => $savedEvidence,
            'approvalReview' => $approvalReview,
        ]);
    }

    public function update(CwisIndicatorDataRequest $request, int $submission)
    {
        abort_unless(auth()->user()->can('Edit CWIS'),403);
        $record = DB::table('cwis.equity_submissions')->where('id',$submission)
            ->where('country_code',$this->indicators->activeCountry())->whereNull('deleted_at')->first();
        abort_unless($record,404);
        abort_unless(in_array($record->status,['draft','submitted'],true),403,__('Approved submissions are read-only.'));
        $request->validate([
            'year' => ['required','integer',Rule::in([$record->reporting_year])],
            'country_code' => ['required',Rule::in([$record->country_code])],
        ]);
        return $this->saveSubmission($request,$submission);
    }

    public function store(CwisIndicatorDataRequest $request)
    {
        return $this->saveSubmission($request);
    }

    private function saveSubmission(CwisIndicatorDataRequest $request, ?int $existingId = null, bool $approveAfterSave = false)
    {
        $validated = $request->validated();
        $country = $validated['country_code'];
        $fields = $this->forms->applicable($this->forms->definitions(), $this->indicators->forCountry($country)->pluck('indicator_code')->all());
        abort_if(empty($fields), 404, 'No Equity input fields are available for this country.');

        $inputs = $validated['inputs'] ?? [];
        $policies = $validated['policies'] ?? [];
        $files = $request->file('evidence', []);
        $userId = auth()->id();
        $now = now();

        $storedPaths = [];
        try {
        $submissionId = DB::transaction(function () use ($validated, $inputs, $policies, $files, $fields, $userId, $now, $existingId, $approveAfterSave, &$storedPaths) {
            // Serialize concurrent saves for the same country/year, including double clicks.
            $lockKey = 73930 + array_search($validated['country_code'], array_keys(CwisIndicatorService::COUNTRIES), true);
            DB::select('SELECT pg_advisory_xact_lock(?, ?)', [$lockKey, (int) $validated['year']]);
            if ($existingId === null && $this->submissionExists($validated['country_code'], $validated['year'])) {
                throw ValidationException::withMessages([
                    'year' => __('Equity indicator data already exists for this year and country. Please use the existing record.'),
                ]);
            }
            $header = [
                'reporting_year' => $validated['year'],
                'country_code' => $validated['country_code'],
                'status' => $validated['save_mode'],
                'submitted_by' => $validated['save_mode'] === 'submitted' ? $userId : null,
                'submitted_at' => $validated['save_mode'] === 'submitted' ? $now : null,
                'updated_by' => $userId,
                'updated_at' => $now,
            ];
            $existingRows = [];
            if ($existingId !== null) {
                $record = DB::table('cwis.equity_submissions')->where('id',$existingId)
                    ->where('country_code',$validated['country_code'])->where('reporting_year',$validated['year'])
                    ->whereNull('deleted_at')->lockForUpdate()->first();
                abort_unless($record,404);
                abort_unless(in_array($record->status,['draft','submitted'],true),409,__('This submission has been approved and can no longer be edited.'));
                $submissionId = $record->id;
                if ($approveAfterSave) {
                    $header['submitted_by'] = $record->submitted_by ?? $userId;
                    $header['submitted_at'] = $record->submitted_at ?? $now;
                }
                $header['approved_by'] = null;
                $header['approved_at'] = null;
                DB::table('cwis.equity_submissions')->where('id',$submissionId)->update($header);
                foreach (array_unique(array_column($fields,'storage_table')) as $table) {
                    $existingRows[$table] = DB::table($table)->where('submission_id',$submissionId)->whereNull('deleted_at')->first();
                }
            } else {
                $submissionId = DB::table('cwis.equity_submissions')->insertGetId($header + ['created_by' => $userId,'created_at' => $now]);
            }

            $rows = [];
            foreach ($fields as $field) {
                $table = $field->storage_table;
                $rows[$table] = $rows[$table] ?? [
                    'submission_id' => $submissionId, 'updated_by' => $userId,
                    'updated_at' => $now, 'deleted_at' => null,
                ];
                if (empty($existingRows[$table])) {
                    $rows[$table]['created_by'] = $userId;
                    $rows[$table]['created_at'] = $now;
                }
                if ($field->kind === 'number') {
                    $rows[$table][$field->storage_column] = $inputs[$field->input_name] ?? null;
                    continue;
                }
                $value = $policies[$field->input_name] ?? null;
                $rows[$table][$field->storage_column] = $value === 'Yes' ? true : ($value === 'No' ? false : null);
                if ($field->document_column) {
                    $rows[$table][$field->document_column] = $value === 'Yes'
                        ? ($existingRows[$table]->{$field->document_column} ?? null) : null;
                }
                if ($value === 'Yes' && isset($files[$field->input_name]) && $field->document_column) {
                    $file = $files[$field->input_name];
                    $path = $file->store('cwis/equity-documents', 'public');
                    if ($path === false) {
                        throw new \RuntimeException('Unable to store CWIS evidence document.');
                    }
                    $storedPaths[] = $path;
                    $rows[$table][$field->document_column] = DB::table('cwis.equity_documents')->insertGetId([
                        'disk' => 'public', 'path' => $path, 'original_name' => $file->getClientOriginalName(),
                        'created_by' => $userId, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
            foreach ($rows as $table => $row) {
                if ($existingId === null) { DB::table($table)->insert($row); }
                else { DB::table($table)->updateOrInsert(['submission_id' => $submissionId],$row); }
            }
            if ($approveAfterSave) {
                $candidate = DB::table('cwis.equity_submissions')->where('id',$submissionId)->first();
                app(\App\Services\Cwis\CwisEquityWorkflowService::class)->validateSource($candidate);
                DB::table('cwis.equity_submissions')->where('id',$submissionId)->update([
                    'status' => 'approved', 'approved_by' => $userId, 'approved_at' => $now,
                ]);
            }

            return $submissionId;
        });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            throw $exception;
        }

        if ($existingId !== null) {
            if ($approveAfterSave) {
                return $this->approvalRedirect($submissionId, $country, (int) $validated['year']);
            }
            return redirect()->route('cwis.generator-data.show',['submission' => $submissionId,'country_code' => $country])
                ->with('cwis_saved', $approveAfterSave ? __('CWIS indicator data updated and approved successfully.') : __('CWIS indicator data updated successfully.'));
        }
        return redirect()
            ->action('Cwis\CwisGeneratorDataController@index', ['country_code' => $country])
            ->with('cwis_saved', __(
                $validated['save_mode'] === 'submitted'
                    ? 'CWIS Equity data submitted for approval. Submission ID: :id'
                    : 'CWIS Equity data saved as draft. Submission ID: :id',
                ['id' => $submissionId]
            ));
    }

    private function approvalRedirect(int $submission, string $country, int $year)
    {
        $hasResults = DB::table('cwis.data_cwis')->where('year', $year)->exists();
        return ($hasResults
            ? redirect()->route('cwis.generator-data.show', ['submission' => $submission, 'country_code' => $country])
            : redirect()->route('cwis.generator-data.index', ['country_code' => $country]))
            ->with('cwis_saved', $hasResults
                ? __('Indicator data approved. Click Rebuild CWIS Results to update the saved results.')
                : __('Indicator data approved. No CWIS results have been generated yet.'));
    }

    public function document(Request $request, int $submission, int $document)
    {
        $record = DB::table('cwis.equity_submissions')->where('id', $submission)
            ->where('country_code', $this->indicators->activeCountry())->whereNull('deleted_at')->first();
        abort_unless($record, 404);
        $fields = $this->forms->applicable($this->forms->definitions(),
            $this->indicators->forCountry($record->country_code)->pluck('indicator_code')->all());
        $linked = false;
        foreach ($fields as $field) {
            if ($field->document_column && DB::table($field->storage_table)->where('submission_id', $submission)
                ->whereNull('deleted_at')->where($field->document_column, $document)->exists()) {
                $linked = true;
                break;
            }
        }
        abort_unless($linked, 404);
        $file = DB::table('cwis.equity_documents')->where('id', $document)->first();
        abort_unless($file, 404);
        return \App\Support\CwisEvidence::response($file->disk, $file->path, $file->original_name, $request->boolean('preview'));
    }

    private function submissionExists(string $country, int $year): bool
    {
        return DB::table('cwis.equity_submissions')->whereNull('deleted_at')
            ->where('country_code', $country)->where('reporting_year', $year)->exists();
    }
}
