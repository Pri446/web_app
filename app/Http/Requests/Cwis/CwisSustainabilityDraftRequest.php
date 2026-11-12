<?php

namespace App\Http\Requests\Cwis;

use App\Services\Cwis\CwisIndicatorService;
use App\Services\Cwis\CwisSustainabilityFormService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CwisSustainabilityDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $approve = $this->route() && $this->route()->getActionMethod() === 'approve';
        return $this->user() && $this->user()->can('List CWIS')
            && $this->user()->can($approve ? 'Approve CWIS' : ($this->route('submission') ? 'Edit CWIS' : 'Add CWIS'));
    }

    public function rules(): array
    {
        $country = app(CwisIndicatorService::class)->activeCountry();
        $fields = app(CwisSustainabilityFormService::class)->fields($country);
        $numbers = [];
        $complete = $this->input('save_mode') === 'submitted' || ($this->route() && $this->route()->getActionMethod() === 'approve');
        foreach ($fields['sections'] as $section) { $numbers += $section['fields']; }
        $rules = [
            'year' => ['required', 'integer', Rule::in(range(now()->year, now()->year - 5))],
            'country_code' => ['required', Rule::in([$country])],
            'currency_code' => ['nullable', 'regex:/^[A-Z]{3}$/'],
            'save_mode' => ['sometimes', Rule::in(['draft', 'submitted'])],
            'inputs' => $numbers ? ['nullable', 'array:' . implode(',', array_keys($numbers))] : ['prohibited'],
        ];
        foreach ($numbers as $name => $label) {
            $rules['inputs.' . $name] = [$complete ? 'required' : 'nullable', 'numeric', 'min:0', 'regex:/^\d{1,16}(\.\d{1,2})?$/'];
        }
        // Request keys use underscore aliases because Laravel treats dots as nested paths.
        $keys = array_map(function ($code) { return str_replace('.', '_', $code); }, array_keys($fields['campaigns']));
        $rules['campaigns'] = $keys ? ['nullable', 'array:' . implode(',', $keys)] : ['prohibited'];
        $rules['evidence'] = $keys ? ['nullable', 'array:' . implode(',', $keys)] : ['prohibited'];
        foreach ($keys as $key) {
            $rules['campaigns.' . $key] = [$complete ? 'required' : 'nullable', Rule::in(['Yes', 'No'])];
            $rules['evidence.' . $key] = $this->input('campaigns.' . $key) === 'Yes'
                ? \App\Support\CwisEvidence::uploadRules() : ['prohibited'];
        }
        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('save_mode') !== 'submitted' && (!$this->route() || $this->route()->getActionMethod() !== 'approve')) { return; }
            $record = \Illuminate\Support\Facades\DB::table('cwis.sustainability_submissions')
                ->where('id', $this->route('submission'))->where('country_code', app(CwisIndicatorService::class)->activeCountry())->first();
            if ($this->route('submission')) {
                abort_unless($record, 404);
                abort_unless(in_array($record->status, ['draft', 'submitted'], true), 403);
            }
            foreach (app(CwisSustainabilityFormService::class)->fields(app(CwisIndicatorService::class)->activeCountry())['campaigns'] as $code => $label) {
                $key = str_replace('.', '_', $code);
                if ($this->input('campaigns.' . $key) !== 'Yes' || $this->hasFile('evidence.' . $key)) { continue; }
                $path = \Illuminate\Support\Facades\DB::table('cwis.sustainability_campaigns')
                    ->where('submission_id', $record->id ?? 0)->where('indicator_code', $code)->value('document_path');
                if (!$path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
                    $validator->errors()->add('evidence.' . $key, __(':indicator requires an evidence document before submission or approval.', ['indicator' => __($label)]));
                }
            }
        });
    }

    public function attributes(): array
    {
        $attributes = [];
        foreach (config('cwis_sustainability.financial_sections') as $section) {
            foreach ($section['fields'] as $name => $label) { $attributes['inputs.' . $name] = __($label); }
        }
        foreach (config('cwis_sustainability.campaigns') as $code => $label) {
            $key = str_replace('.', '_', $code);
            $attributes['campaigns.' . $key] = __($label);
            $attributes['evidence.' . $key] = __($label) . ' ' . __('evidence');
        }
        return $attributes;
    }
}
