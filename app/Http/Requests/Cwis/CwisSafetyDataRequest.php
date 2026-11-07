<?php

namespace App\Http\Requests\Cwis;

use App\Services\Cwis\CwisIndicatorService;
use App\Services\Cwis\CwisSafetyFormService;
use App\Support\CwisEvidence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CwisSafetyDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        $approve = $this->route()->getActionMethod() === 'approve';
        return $this->user() && $this->user()->can('List CWIS')
            && $this->user()->can($approve ? 'Approve CWIS' : ($this->route('submission') ? 'Edit CWIS' : 'Add CWIS'));
    }

    public function rules(): array
    {
        $country = app(CwisIndicatorService::class)->activeCountry();
        $service = app(CwisSafetyFormService::class);
        $complete = $this->input('save_mode') === 'submitted' || $this->route()->getActionMethod() === 'approve';
        $keys = array_map(function ($code) { return str_replace('.','_',$code); },array_keys($service->questions($country)));
        $rules = ['country_code' => ['required',Rule::in([$country])], 'year' => ['required','integer',Rule::in(range(now()->year,now()->year-5))],
            'save_mode' => ['required',Rule::in(['draft','submitted'])],
            'answers' => $keys ? ['nullable','array:'.implode(',',$keys)] : ['prohibited'],
            'evidence' => $keys ? ['nullable','array:'.implode(',',$keys)] : ['prohibited'],
            'biosolids_evidence' => ['nullable','array:SF-12']];
        foreach ($service->questions($country) as $code => $label) {
            $key = str_replace('.','_',$code);
            $parent = config('cwis_safety_d22.parents')[$code] ?? null;
            $applies = !$parent || $this->input('answers.'.str_replace('.','_',$parent)) === 'Yes';
            $rules['answers.'.$key] = $applies ? [$complete ? 'required' : 'nullable',Rule::in(['Yes','No'])] : ['prohibited'];
            $rules['evidence.'.$key] = $applies && $this->input('answers.'.$key) === 'Yes' ? CwisEvidence::uploadRules() : ['prohibited'];
        }
        $rules['biosolids_evidence.SF-12'] = in_array('SF-12',$service->codes($country),true) && $this->input('answers.SF-12') === 'Yes' ? CwisEvidence::uploadRules() : ['prohibited'];
        $toilets = $service->toilets($country);
        $ids = $toilets->pluck('id')->all();
        $rules['waiting_times'] = $ids ? ['nullable','array:'.implode(',',$ids)] : ['prohibited'];
        $rules['waiting_evidence'] = $ids ? ['nullable','array:'.implode(',',$ids)] : ['prohibited'];
        foreach ($ids as $id) {
            $rules['waiting_times.'.$id] = ['nullable','array:minutes,users'];
            $rules['waiting_times.'.$id.'.minutes'] = [$complete ? 'required' : 'nullable','numeric','min:0','max:1440'];
            $rules['waiting_times.'.$id.'.users'] = [$complete ? 'required' : 'nullable','integer','min:1','max:2147483647'];
            $rules['waiting_evidence.'.$id] = CwisEvidence::uploadRules();
        }
        $spaces = in_array('SF-4',$service->codes($country),true);
        $rules['public_spaces_complete'] = $spaces ? [$complete ? 'accepted' : 'nullable','boolean'] : ['prohibited'];
        $rules['public_spaces'] = $spaces ? ['nullable','array','max:1000'] : ['prohibited'];
        $rules['public_spaces.*'] = ['array:name,toilet_id'];
        $rules['public_spaces.*.name'] = ['required','string','max:255','distinct:ignore_case'];
        $rules['public_spaces.*.toilet_id'] = ['nullable','integer',Rule::in($service->publicToilets()->pluck('id')->all())];
        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $country = app(CwisIndicatorService::class)->activeCountry();
            $service = app(CwisSafetyFormService::class);
            $source = $this->route('submission') ? DB::table('cwis.safety_submissions')->where('id',$this->route('submission'))->where('country_code',$country)->first() : null;
            if ($this->route('submission')) {
                abort_unless($source,404);
                abort_unless(in_array($source->status,['draft','submitted'],true),403);
                if ((int) $this->input('year') !== (int) $source->reporting_year) { $validator->errors()->add('year',__('The reporting year cannot be changed.')); }
            }
            if ($this->input('save_mode') !== 'submitted' && $this->route()->getActionMethod() !== 'approve') { return; }
            foreach ($service->questions($country) as $code => $label) {
                $key = str_replace('.','_',$code);
                if ($this->input('answers.'.$key) !== 'Yes') { continue; }
                $saved = $source ? DB::table('cwis.safety_answers')->where('submission_id',$source->id)->where('indicator_code',$code)->first() : null;
                foreach (['evidence' => 'document_path'] + ($code === 'SF-12' ? ['biosolids_evidence' => 'biosolids_document_path'] : []) as $input => $column) {
                    if (!$this->hasFile($input.'.'.$key) && (!$saved || !$saved->$column || !Storage::disk('local')->exists($saved->$column))) {
                        $validator->errors()->add($input.'.'.$key, __($label).' '.__('requires an evidence document.'));
                    }
                }
            }
            foreach ($service->toilets($country) as $toilet) {
                $saved = $source ? DB::table('cwis.safety_waiting_times')->where('submission_id',$source->id)->where('toilet_id',$toilet->id)->first() : null;
                if (!$this->hasFile('waiting_evidence.'.$toilet->id) && (!$saved || !$saved->document_path || !Storage::disk('local')->exists($saved->document_path))) {
                    $validator->errors()->add('waiting_evidence.'.$toilet->id, $toilet->name.' '.__('requires waiting-time evidence.'));
                }
            }
        });
    }

    public function attributes(): array
    {
        $labels = ['public_spaces_complete' => __('Complete public-space inventory')];
        foreach (app(CwisSafetyFormService::class)->questions(app(CwisIndicatorService::class)->activeCountry()) as $code => $label) {
            $labels['answers.'.str_replace('.','_',$code)] = __($label);
        }
        return $labels;
    }
}
