<?php

namespace App\Http\Requests\Cwis;

use App\Services\Cwis\CwisIndicatorService;
use App\Services\Cwis\CwisAccountabilityFormService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CwisAccountabilityDataRequest extends FormRequest
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
        $complete = $this->input('save_mode') === 'submitted' || $this->route()->getActionMethod() === 'approve';
        $keys = array_map(function ($code) { return str_replace('.', '_', $code); },
            array_keys(app(CwisAccountabilityFormService::class)->questions($country)));
        $rules = [
            'year' => ['required', 'integer', Rule::in(range(now()->year, now()->year - 5))],
            'country_code' => ['required', Rule::in([$country])],
            'save_mode' => ['sometimes', Rule::in(['draft', 'submitted'])],
            'answers' => $keys ? ['nullable', 'array:' . implode(',', $keys)] : ['prohibited'],
            'evidence' => $keys ? ['nullable', 'array:' . implode(',', $keys)] : ['prohibited'],
        ];
        foreach ($keys as $key) {
            $rules['answers.' . $key] = [$complete ? 'required' : 'nullable', Rule::in(['Yes', 'No'])];
            $rules['evidence.' . $key] = $this->input('answers.' . $key) === 'Yes'
                ? \App\Support\CwisEvidence::uploadRules() : ['prohibited'];
        }
        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('save_mode') !== 'submitted' && $this->route()->getActionMethod() !== 'approve') { return; }
            $country = app(CwisIndicatorService::class)->activeCountry();
            $source = DB::table('cwis.accountability_submissions')->where('id', $this->route('submission'))
                ->where('country_code', $country)->first();
            if ($this->route('submission')) {
                abort_unless($source, 404);
                abort_unless(in_array($source->status, ['draft', 'submitted'], true), 403);
            }
            foreach (app(CwisAccountabilityFormService::class)->questions($country) as $code => $label) {
                $key = str_replace('.', '_', $code);
                if ($this->input('answers.' . $key) !== 'Yes' || $this->hasFile('evidence.' . $key)) { continue; }
                $path = DB::table('cwis.accountability_answers')->where('submission_id', $source->id ?? 0)
                    ->where('indicator_code', $code)->value('document_path');
                if (!$path || !Storage::disk('local')->exists($path)) {
                    $validator->errors()->add('evidence.' . $key,
                        __(':indicator requires an evidence document before submission or approval.', ['indicator' => __($label)]));
                }
            }
        });
    }

    public function attributes(): array
    {
        $attributes = [];
        foreach (app(CwisAccountabilityFormService::class)->questions(app(CwisIndicatorService::class)->activeCountry()) as $code => $label) {
            $key = str_replace('.', '_', $code);
            $attributes['answers.' . $key] = __($label);
            $attributes['evidence.' . $key] = __($label) . ' ' . __('evidence');
        }
        return $attributes;
    }
}
