<?php

namespace App\Http\Requests\Cwis;

use App\Services\Cwis\CwisFormService;
use App\Services\Cwis\CwisIndicatorService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CwisIndicatorDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        $action = $this->route()->getActionMethod();
        if (in_array($action, ['update', 'approve'], true)) {
            if (!$this->user() || !$this->user()->can($action === 'approve' ? 'Approve CWIS' : 'Edit CWIS')) {
                return false;
            }
            $record = DB::table('cwis.equity_submissions')->where('id', $this->route('submission'))
                ->where('country_code', app(CwisIndicatorService::class)->activeCountry())
                ->whereNull('deleted_at')->first();
            abort_unless($record, 404);
            return in_array($record->status, ['draft', 'submitted'], true);
        }
        return $this->user() !== null;
    }

    public function rules(): array
    {
        if ($this->route()->getActionMethod() === 'approve' && !$this->boolean('review_form')) {
            return [];
        }
        $forms = app(CwisFormService::class);
        $country = $this->input('country_code');
        $definitions = $forms->definitions();
        $fields = in_array($country, array_keys(CwisIndicatorService::COUNTRIES), true)
            ? $forms->applicable($definitions, app(CwisIndicatorService::class)->forCountry($country)->pluck('indicator_code')->all())
            : [];
        $activeNames = array_column($fields, 'input_name');
        $numeric = array_values(array_filter($definitions, function ($field) { return $field->kind === 'number'; }));
        $policyFields = array_values(array_filter($definitions, function ($field) { return $field->kind === 'policy'; }));
        $years = range(now()->year, now()->year - 5);
        $approvalReview = $this->route()->getActionMethod() === 'approve';
        $rules = [
            'country_code' => ['required', Rule::in(array_keys(CwisIndicatorService::COUNTRIES))],
            'year' => ['required', 'integer', Rule::in($years)],
            'save_mode' => ['required', Rule::in(['draft', 'submitted'])],
            'inputs' => $numeric ? ['nullable', 'array:' . implode(',', array_column($numeric, 'input_name'))] : ['prohibited'],
            'policies' => $policyFields ? ['nullable', 'array:' . implode(',', array_column($policyFields, 'input_name'))] : ['prohibited'],
            'evidence' => $policyFields ? ['nullable', 'array:' . implode(',', array_column($policyFields, 'input_name'))] : ['prohibited'],
        ];
        foreach ($numeric as $field) {
            $rules['inputs.' . $field->input_name] = in_array($field->input_name, $activeNames, true)
                ? [($approvalReview || $field->required_on_submit) && $this->input('save_mode') === 'submitted' ? 'required' : 'nullable',
                    $field->validation_type, 'min:0', 'max:' . $field->maximum]
                : ['prohibited'];
        }
        $policyByCode = array_column($policyFields, null, 'indicator_code');
        foreach ($policyFields as $field) {
            $parent = $field->parent_code ? ($policyByCode[$field->parent_code] ?? null) : null;
            $applicable = in_array($field->input_name, $activeNames, true)
                && (!$field->parent_code || ($parent && $this->input('policies.' . $parent->input_name) === 'Yes'));
            $rules['policies.' . $field->input_name] = $applicable
                ? [($approvalReview || $field->required_on_submit) && $this->input('save_mode') === 'submitted' ? 'required' : 'nullable', Rule::in(['Yes', 'No'])]
                : ['prohibited'];
            $rules['evidence.' . $field->input_name] = $applicable && $this->input('policies.' . $field->input_name) === 'Yes'
                ? \App\Support\CwisEvidence::uploadRules() : ['prohibited'];
        }
        if ($this->route('submission')) {
            $record = DB::table('cwis.equity_submissions')->where('id', $this->route('submission'))->first();
            $rules['year'] = ['required', 'integer', Rule::in([$record->reporting_year])];
            $rules['country_code'] = ['required', Rule::in([$record->country_code])];
        }
        if ($approvalReview) {
            $rules['save_mode'] = ['required', Rule::in(['submitted'])];
        }
        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('save_mode') === 'submitted') {
                $forms = app(CwisFormService::class);
                $country = $this->input('country_code');
                $fields = in_array($country, array_keys(CwisIndicatorService::COUNTRIES), true)
                    ? $forms->applicable($forms->definitions(),
                        app(CwisIndicatorService::class)->forCountry($country)->pluck('indicator_code')->all()) : [];
                foreach ($fields as $field) {
                    if ($field->kind !== 'policy' || !$field->document_column
                        || $this->input('policies.' . $field->input_name) !== 'Yes'
                        || $this->hasFile('evidence.' . $field->input_name)) { continue; }
                    $documentId = $this->route('submission') ? DB::table($field->storage_table)
                        ->where('submission_id', $this->route('submission'))->whereNull('deleted_at')
                        ->value($field->document_column) : null;
                    $document = $documentId ? DB::table('cwis.equity_documents')->where('id', $documentId)->first() : null;
                    if (!$document || !\Illuminate\Support\Facades\Storage::disk($document->disk)->exists($document->path)) {
                        $validator->errors()->add('evidence.' . $field->input_name,
                            __(':indicator requires an evidence document before submission or approval.', ['indicator' => __($field->label)]));
                    }
                }
            }
            foreach (app(CwisFormService::class)->definitions() as $field) {
                if ($field->kind !== 'number') {
                    continue;
                }
                foreach ($field->not_greater_than as $total) {
                    $partValue = $this->input('inputs.' . $field->input_name);
                    $totalValue = $this->input('inputs.' . $total);
                    if (is_numeric($partValue) && is_numeric($totalValue) && $partValue > $totalValue) {
                        $validator->errors()->add('inputs.' . $field->input_name, __('The count cannot exceed its corresponding total.'));
                    }
                }
            }
        });
    }

    public function attributes(): array
    {
        $attributes = [];
        foreach (app(CwisFormService::class)->definitions() as $field) {
            $attributes[($field->kind === 'number' ? 'inputs.' : 'policies.') . $field->input_name] = __($field->label);
            $attributes['evidence.' . $field->input_name] = __($field->label) . ' ' . __('evidence document');
        }
        return $attributes;
    }
}
