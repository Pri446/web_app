<?php

namespace App\Http\Controllers\Cwis;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cwis\CwisSustainabilityDraftRequest;
use App\Services\Cwis\CwisIndicatorService;
use App\Services\Cwis\CwisSustainabilityFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CwisSustainabilityDataController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:List CWIS']);
        $this->middleware(\App\Http\Middleware\CwisFormResponse::class)->only(['store', 'update', 'approve']);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('Add CWIS'), 403);
        $country = app(CwisIndicatorService::class)->activeCountry();
        $request->validate([
            'country_code' => ['nullable', Rule::in([$country])],
            'year' => ['required', 'integer', Rule::in(range(now()->year, now()->year - 5))],
        ]);
        $service = app(CwisSustainabilityFormService::class);
        abort_unless($service->available($country), 404);
        $record = DB::table('cwis.sustainability_submissions')->where('country_code', $country)
            ->where('reporting_year', (int) $request->input('year'))->first();
        if ($record) {
            return redirect()->route('cwis.sustainability.edit', ['submission' => $record->id]);
        }
        return $this->form($country, (int) $request->input('year'));
    }

    public function edit(Request $request, int $submission)
    {
        abort_unless($request->user()->can('Edit CWIS'), 403);
        $country = app(CwisIndicatorService::class)->activeCountry();
        abort_unless(app(CwisSustainabilityFormService::class)->available($country), 404);
        $record = DB::table('cwis.sustainability_submissions')->where('id', $submission)
            ->where('country_code', $country)->first();
        abort_unless($record, 404);
        abort_unless(in_array($record->status, ['draft', 'submitted'], true), 403);
        return $this->form($country, $record->reporting_year, $record);
    }

    public function show(Request $request, int $submission)
    {
        $country = app(CwisIndicatorService::class)->activeCountry();
        $record = DB::table('cwis.sustainability_submissions')->where('id', $submission)->where('country_code', $country)->first();
        abort_unless($record, 404);
        return $this->form($country, $record->reporting_year, $record, true);
    }

    public function review(Request $request, int $submission)
    {
        abort_unless($request->user()->can('Approve CWIS'), 403);
        $country = app(CwisIndicatorService::class)->activeCountry();
        $record = DB::table('cwis.sustainability_submissions')->where('id', $submission)->where('country_code', $country)->first();
        abort_unless($record, 404);
        abort_unless(in_array($record->status, ['draft', 'submitted'], true), 403);
        return $this->form($country, $record->reporting_year, $record, false, true);
    }

    public function approve(CwisSustainabilityDraftRequest $request, int $submission)
    {
        return $this->save($request, $submission, true);
    }

    private function form(string $country, int $year, $record = null, bool $readOnly = false, bool $approvalReview = false)
    {
        return view($readOnly ? 'cwis.sustainability.show' : 'cwis.sustainability.form', app(CwisSustainabilityFormService::class)->fields($country) + [
            'page_title' => $approvalReview ? __('Review Indicator Data') : ($record ? __('CWIS Indicator Data') : __('Add Indicator Data')), 'country' => $country,
            'readOnly' => $readOnly, 'approvalReview' => $approvalReview,
            'hasGeneratedResults' => $record && DB::table('cwis.data_cwis')->where('year', $year)->exists(),
            'countryLabel' => __(CwisIndicatorService::COUNTRIES[$country]), 'year' => $year,
            'submission' => $record,
            'financials' => $record ? DB::table('cwis.sustainability_financials')->where('submission_id', $record->id)->first() : null,
            'savedCampaigns' => $record ? DB::table('cwis.sustainability_campaigns')->where('submission_id', $record->id)->get()->keyBy('indicator_code') : collect(),
        ]);
    }

    public function store(CwisSustainabilityDraftRequest $request)
    {
        return $this->save($request);
    }

    public function update(CwisSustainabilityDraftRequest $request, int $submission)
    {
        return $this->save($request, $submission);
    }

    private function save(CwisSustainabilityDraftRequest $request, ?int $submission = null, bool $approve = false)
    {
        $country = app(CwisIndicatorService::class)->activeCountry();
        $service = app(CwisSustainabilityFormService::class);
        abort_unless($service->available($country), 404);
        $fields = $service->fields($country);
        $newFiles = [];
        $retiredFiles = [];
        try {
            DB::transaction(function () use ($request, $submission, $country, $fields, $approve, &$newFiles, &$retiredFiles) {
                $year = (int) $request->input('year');
                $status = $approve ? 'approved' : $request->input('save_mode', 'draft');
                DB::select('SELECT pg_advisory_xact_lock(73922, ?)', [$year]);
                if ($submission) {
                    $record = DB::table('cwis.sustainability_submissions')->where('id', $submission)
                        ->where('country_code', $country)->lockForUpdate()->first();
                    abort_unless($record, 404);
                    abort_unless(in_array($record->status, ['draft', 'submitted'], true), 403);
                    if ((int) $record->reporting_year !== $year) {
                        throw ValidationException::withMessages(['year' => __('The reporting year cannot be changed.')]);
                    }
                    DB::table('cwis.sustainability_submissions')->where('id', $submission)->update([
                        'currency_code' => $request->input('currency_code'), 'updated_by' => auth()->id(), 'updated_at' => now(),
                        'status' => $status,
                        'submitted_by' => $status !== 'draft' ? ($record->submitted_by ?? auth()->id()) : null,
                        'submitted_at' => $status !== 'draft' ? ($record->submitted_at ?? now()) : null,
                        'approved_by' => $approve ? auth()->id() : null,
                        'approved_at' => $approve ? now() : null,
                    ]);
                } else {
                    if (DB::table('cwis.sustainability_submissions')->where('country_code', $country)->where('reporting_year', $year)->exists()) {
                        throw ValidationException::withMessages(['year' => __('Sustainability data already exists for this year and country. Use the existing draft.')]);
                    }
                    $submission = DB::table('cwis.sustainability_submissions')->insertGetId([
                        'country_code' => $country, 'reporting_year' => $year, 'status' => $status,
                        'submitted_by' => $status === 'submitted' ? auth()->id() : null,
                        'submitted_at' => $status === 'submitted' ? now() : null,
                        'currency_code' => $request->input('currency_code'),
                        'created_by' => auth()->id(), 'updated_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $amounts = [];
                foreach ($fields['sections'] as $section) {
                    foreach ($section['fields'] as $name => $label) { $amounts[$name] = $request->input('inputs.' . $name); }
                }
                $exists = DB::table('cwis.sustainability_financials')->where('submission_id', $submission)->exists();
                DB::table('cwis.sustainability_financials')->updateOrInsert(['submission_id' => $submission],
                    $amounts + ['updated_at' => now()] + ($exists ? [] : ['created_at' => now()]));
                foreach ($fields['campaigns'] as $code => $label) {
                    $key = str_replace('.', '_', $code);
                    $old = DB::table('cwis.sustainability_campaigns')->where('submission_id', $submission)->where('indicator_code', $code)->first();
                    $answer = $request->input('campaigns.' . $key);
                    $path = $answer === 'Yes' ? ($old->document_path ?? null) : null;
                    $name = $answer === 'Yes' ? ($old->document_name ?? null) : null;
                    if ($request->hasFile('evidence.' . $key)) {
                        $file = $request->file('evidence.' . $key);
                        $path = $file->store('cwis/sustainability-documents', 'local');
                        if (!$path) { throw new \RuntimeException('Unable to store evidence.'); }
                        $newFiles[] = $path;
                        $name = $file->getClientOriginalName();
                    }
                    if ($approve && $answer === 'Yes' && (!$path || !Storage::disk('local')->exists($path))) {
                        throw ValidationException::withMessages(['evidence.' . $key => __(':indicator requires an evidence document before approval.', ['indicator' => __($label)])]);
                    }
                    if ($old && $old->document_path && $old->document_path !== $path) { $retiredFiles[] = $old->document_path; }
                    DB::table('cwis.sustainability_campaigns')->updateOrInsert(
                        ['submission_id' => $submission, 'indicator_code' => $code],
                        ['answer' => $answer, 'document_path' => $path, 'document_name' => $name, 'updated_at' => now()]
                            + ($old ? [] : ['created_at' => now()]));
                }
            });
        } catch (\Throwable $exception) {
            foreach ($newFiles as $path) { Storage::disk('local')->delete($path); }
            throw $exception;
        }
        foreach ($retiredFiles as $path) { Storage::disk('local')->delete($path); }
        return redirect()->route('cwis.generator-data.index', ['country_code' => $country])
            ->with('cwis_saved', $approve ? __('Sustainability indicator data approved successfully.')
                : ($request->input('save_mode') === 'submitted' ? __('Sustainability indicator data submitted for approval.') : __('Sustainability indicator data saved as draft.')));
    }

    public function document(Request $request, int $submission, int $document)
    {
        $country = app(CwisIndicatorService::class)->activeCountry();
        $record = DB::table('cwis.sustainability_campaigns as campaign')
            ->join('cwis.sustainability_submissions as source', 'source.id', '=', 'campaign.submission_id')
            ->where('source.country_code', $country)->where('source.id', $submission)
            ->where('campaign.id', $document)->select('campaign.*')->first();
        abort_unless($record && $record->document_path && Storage::disk('local')->exists($record->document_path), 404);
        return \App\Support\CwisEvidence::response('local', $record->document_path, $record->document_name, $request->boolean('preview'));
    }

    public function calculate(Request $request, int $submission)
    {
        abort_unless($request->user()->can('Add CWIS'), 403);
        $country = app(CwisIndicatorService::class)->activeCountry();
        $record = DB::table('cwis.sustainability_submissions')->where('id', $submission)->where('country_code', $country)->first();
        abort_unless($record, 404);
        try {
            DB::transaction(function () use ($record, $country) {
                DB::select('SELECT pg_advisory_xact_lock(73921, ?)', [(int) $record->reporting_year]);
                $source = DB::table('cwis.sustainability_submissions')->where('id', $record->id)->lockForUpdate()->first();
                if (!$source || $source->status !== 'approved' || !$source->approved_at) {
                    throw ValidationException::withMessages(['submission' => __('Approve this Sustainability submission before calculation.')]);
                }
                $workflow = app(\App\Services\Cwis\CwisSustainabilityWorkflowService::class);
                $workflow->validateSource($source);
                $results = DB::table('cwis.data_cwis')->where('year', $source->reporting_year)->orderBy('id')->get();
                if ($results->isEmpty()) {
                    app(\App\Services\Cwis\CwisEquityWorkflowService::class)->requireApproval($source->reporting_year, $country);
                    app(\App\Services\Cwis\CwisResponsibilityWorkflowService::class)->requireApproval($source->reporting_year, $country);
                    app(\App\Services\Cwis\CwisAccountabilityWorkflowService::class)->requireApproval($source->reporting_year, $country);
                    app(\App\Services\Cwis\CwisRpmWorkflowService::class)->requireApproval($source->reporting_year, $country);
                } else {
                    if (app(CwisIndicatorService::class)->resultCountry($source->reporting_year) !== $country) {
                        throw ValidationException::withMessages(['calculation' => __('Existing CWIS results belong to another country. They have not been changed.')]);
                    }
                    $backup = json_encode([
                        'year' => $source->reporting_year, 'country' => $country, 'results' => $results,
                        'generation_run' => DB::table('cwis.generation_runs')->where('year', $source->reporting_year)->first(),
                        'sustainability_generation_run' => DB::table('cwis.sustainability_generation_runs')->where('year', $source->reporting_year)->first(),
                    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                    $path = 'cwis/result-backups/sustainability_' . $source->reporting_year . '_' . gmdate('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.json';
                    if (!Storage::disk('local')->put($path, $backup)) { throw new \RuntimeException('Unable to back up CWIS results.'); }
                }
                if ($results->isEmpty()) {
                    DB::select('SELECT public.generate_cwis_with_rpm(?)', [(int) $source->reporting_year]);
                } else {
                    DB::select('SELECT public.rebuild_cwis_sustainability(?, ?)', [(int) $source->reporting_year, (int) $source->id]);
                }
            });
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) { throw $exception; }
            return redirect()->route('cwis.sustainability.show', $submission)->withErrors($exception->errors());
        } catch (\Throwable $exception) {
            report($exception);
            if ($request->expectsJson()) { throw $exception; }
            return redirect()->route('cwis.sustainability.show', $submission)
                ->withErrors(['calculation' => __('CWIS rebuilding failed. Existing results were preserved. Please contact your administrator.')]);
        }
        return redirect()->route('cwis.generator-data.index', ['country_code' => $country])
            ->with('cwis_saved', __('CWIS results rebuilt successfully for reporting year :year.', ['year' => $record->reporting_year]));
    }
}
