<?php

namespace App\Http\Controllers\Cwis;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cwis\CwisRpmDataRequest;
use App\Services\Cwis\CwisIndicatorService;
use App\Services\Cwis\CwisRpmFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CwisRpmDataController extends Controller
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
        abort_unless(app(CwisRpmFormService::class)->available($country), 404);
        $record = DB::table('cwis.rpm_submissions')->where('country_code', $country)
            ->where('reporting_year', (int) $request->input('year'))->first();
        if ($record) {
            return redirect()->route('cwis.rpm.show', $record->id);
        }
        return $this->form($country, (int) $request->input('year'));
    }

    private function record(int $submission)
    {
        $country = app(CwisIndicatorService::class)->activeCountry();
        abort_unless(app(CwisRpmFormService::class)->available($country), 404);
        $record = DB::table('cwis.rpm_submissions')->where('id', $submission)->where('country_code', $country)->first();
        abort_unless($record, 404);
        return $record;
    }

    public function show(Request $request, int $submission)
    {
        $record = $this->record($submission);
        return $this->form($record->country_code, $record->reporting_year, $record, true);
    }

    public function edit(Request $request, int $submission)
    {
        abort_unless($request->user()->can('Edit CWIS'), 403);
        $record = $this->record($submission);
        abort_unless(in_array($record->status, ['draft', 'submitted'], true), 403);
        return $this->form($record->country_code, $record->reporting_year, $record);
    }

    public function review(Request $request, int $submission)
    {
        abort_unless($request->user()->can('Approve CWIS'), 403);
        $record = $this->record($submission);
        abort_unless(in_array($record->status, ['draft', 'submitted'], true), 403);
        return $this->form($record->country_code, $record->reporting_year, $record, false, true);
    }

    private function form(string $country, int $year, $record = null, bool $readOnly = false, bool $approvalReview = false)
    {
        return view($readOnly ? 'cwis.rpm.show' : 'cwis.rpm.form', [
            'page_title' => $approvalReview ? __('Review Indicator Data') : ($record ? __('CWIS Indicator Data') : __('Add Indicator Data')),
            'country' => $country, 'countryLabel' => __(CwisIndicatorService::COUNTRIES[$country]),
            'year' => $year, 'submission' => $record, 'readOnly' => $readOnly, 'approvalReview' => $approvalReview,
            'hasGeneratedResults' => $record && DB::table('cwis.data_cwis')->where('year', $year)->exists(),
            'groups' => app(CwisRpmFormService::class)->fields($country),
            'savedAnswers' => $record ? DB::table('cwis.rpm_answers')->where('submission_id', $record->id)->get()->keyBy('indicator_code') : collect(),
        ]);
    }

    public function store(CwisRpmDataRequest $request) { return $this->save($request); }
    public function update(CwisRpmDataRequest $request, int $submission) { return $this->save($request, $submission); }
    public function approve(CwisRpmDataRequest $request, int $submission) { return $this->save($request, $submission, true); }

    private function save(CwisRpmDataRequest $request, ?int $submission = null, bool $approve = false)
    {
        $country = app(CwisIndicatorService::class)->activeCountry();
        $service = app(CwisRpmFormService::class);
        abort_unless($service->available($country), 404);
        $questions = $service->questions($country);
        $newFiles = [];
        $retiredFiles = [];
        try {
            DB::transaction(function () use ($request, $country, $questions, $submission, $approve, &$newFiles, &$retiredFiles) {
                $year = (int) $request->input('year');
                $status = $approve ? 'approved' : $request->input('save_mode', 'draft');
                DB::select('SELECT pg_advisory_xact_lock(73925, ?)', [$year]);
                if ($submission) {
                    $record = DB::table('cwis.rpm_submissions')->where('id', $submission)
                        ->where('country_code', $country)->lockForUpdate()->first();
                    abort_unless($record, 404);
                    abort_unless(in_array($record->status, ['draft', 'submitted'], true), 403);
                    if ((int) $record->reporting_year !== $year) {
                        throw ValidationException::withMessages(['year' => __('The reporting year cannot be changed.')]);
                    }
                    DB::table('cwis.rpm_submissions')->where('id', $submission)->update([
                        'status' => $status, 'updated_by' => auth()->id(), 'updated_at' => now(),
                        'submitted_by' => $status !== 'draft' ? ($record->submitted_by ?? auth()->id()) : null,
                        'submitted_at' => $status !== 'draft' ? ($record->submitted_at ?? now()) : null,
                        'approved_by' => $approve ? auth()->id() : null, 'approved_at' => $approve ? now() : null,
                    ]);
                } else {
                    if (DB::table('cwis.rpm_submissions')->where('country_code', $country)->where('reporting_year', $year)->exists()) {
                        throw ValidationException::withMessages(['year' => __('RPM data already exists for this year and country. Use the existing record.')]);
                    }
                    $submission = DB::table('cwis.rpm_submissions')->insertGetId([
                        'country_code' => $country, 'reporting_year' => $year, 'status' => $status,
                        'created_by' => auth()->id(), 'updated_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now(),
                        'submitted_by' => $status === 'submitted' ? auth()->id() : null,
                        'submitted_at' => $status === 'submitted' ? now() : null,
                    ]);
                }
                foreach ($questions as $code => $label) {
                    $key = str_replace('.', '_', $code);
                    $old = DB::table('cwis.rpm_answers')->where('submission_id', $submission)->where('indicator_code', $code)->first();
                    $answer = $request->input('answers.' . $key);
                    $path = $answer === 'Yes' ? ($old->document_path ?? null) : null;
                    $name = $answer === 'Yes' ? ($old->document_name ?? null) : null;
                    if ($request->hasFile('evidence.' . $key)) {
                        $file = $request->file('evidence.' . $key);
                        $path = $file->store('cwis/rpm-documents', 'local');
                        if (!$path) { throw new \RuntimeException('Unable to store evidence.'); }
                        $newFiles[] = $path;
                        $name = $file->getClientOriginalName();
                    }
                    if ($approve && $answer === 'Yes' && (!$path || !Storage::disk('local')->exists($path))) {
                        throw ValidationException::withMessages(['evidence.' . $key => __(':indicator requires an evidence document before approval.', ['indicator' => __($label)])]);
                    }
                    if ($old && $old->document_path && $old->document_path !== $path) { $retiredFiles[] = $old->document_path; }
                    DB::table('cwis.rpm_answers')->updateOrInsert(['submission_id' => $submission, 'indicator_code' => $code],
                        ['answer' => $answer, 'document_path' => $path, 'document_name' => $name, 'updated_at' => now()] + ($old ? [] : ['created_at' => now()]));
                }
            });
        } catch (\Throwable $exception) {
            foreach ($newFiles as $path) { Storage::disk('local')->delete($path); }
            throw $exception;
        }
        foreach ($retiredFiles as $path) { Storage::disk('local')->delete($path); }
        return redirect()->route('cwis.generator-data.index', ['country_code' => $country])->with('cwis_saved',
            $approve ? __('RPM indicator data approved successfully.') : ($request->input('save_mode') === 'submitted'
                ? __('RPM indicator data submitted for approval.') : __('RPM indicator data saved as draft.')));
    }

    public function calculate(Request $request, int $submission)
    {
        abort_unless($request->user()->can('Add CWIS'), 403);
        $record = $this->record($submission);
        $country = $record->country_code;
        try {
            DB::transaction(function () use ($record, $country) {
                DB::select('SELECT pg_advisory_xact_lock(73921, ?)', [(int) $record->reporting_year]);
                $source = DB::table('cwis.rpm_submissions')->where('id', $record->id)->lockForUpdate()->first();
                app(\App\Services\Cwis\CwisRpmWorkflowService::class)->validateSource($source);
                $results = DB::table('cwis.data_cwis')->where('year', $source->reporting_year)->orderBy('id')->get();
                if ($results->isEmpty()) {
                    app(\App\Services\Cwis\CwisEquityWorkflowService::class)->requireApproval($source->reporting_year, $country);
                    app(\App\Services\Cwis\CwisSustainabilityWorkflowService::class)->requireApproval($source->reporting_year, $country);
                    app(\App\Services\Cwis\CwisResponsibilityWorkflowService::class)->requireApproval($source->reporting_year, $country);
                    app(\App\Services\Cwis\CwisAccountabilityWorkflowService::class)->requireApproval($source->reporting_year, $country);
                } else {
                    if (app(CwisIndicatorService::class)->resultCountry($source->reporting_year) !== $country) {
                        throw ValidationException::withMessages(['calculation' => __('Existing CWIS results belong to another country. They have not been changed.')]);
                    }
                    $backup = json_encode([
                        'year' => $source->reporting_year, 'country' => $country, 'results' => $results,
                        'generation_run' => DB::table('cwis.generation_runs')->where('year', $source->reporting_year)->first(),
                        'rpm_generation_run' => DB::table('cwis.rpm_generation_runs')->where('year', $source->reporting_year)->first(),
                    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                    $path = 'cwis/result-backups/rpm_' . $source->reporting_year . '_' . gmdate('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.json';
                    if (!Storage::disk('local')->put($path, $backup)) { throw new \RuntimeException('Unable to back up CWIS results.'); }
                }
                DB::select('SELECT public.rebuild_cwis_rpm(?, ?)', [(int) $source->reporting_year, (int) $source->id]);
            });
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) { throw $exception; }
            return redirect()->route('cwis.rpm.show', $submission)->withErrors($exception->errors());
        } catch (\Throwable $exception) {
            report($exception);
            if ($request->expectsJson()) { throw $exception; }
            return redirect()->route('cwis.rpm.show', $submission)
                ->withErrors(['calculation' => __('CWIS rebuilding failed. Existing results were preserved. Please contact your administrator.')]);
        }
        return redirect()->route('cwis.generator-data.index', ['country_code' => $country])
            ->with('cwis_saved', __('CWIS results rebuilt successfully for reporting year :year.', ['year' => $record->reporting_year]));
    }

    public function document(Request $request, int $submission, int $document)
    {
        $source = $this->record($submission);
        $answer = DB::table('cwis.rpm_answers')->where('submission_id', $source->id)->where('id', $document)->first();
        abort_unless($answer && $answer->document_path && Storage::disk('local')->exists($answer->document_path), 404);
        return \App\Support\CwisEvidence::response('local', $answer->document_path, $answer->document_name, $request->boolean('preview'));
    }
}
