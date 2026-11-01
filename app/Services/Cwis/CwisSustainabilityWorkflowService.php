<?php

namespace App\Services\Cwis;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CwisSustainabilityWorkflowService
{
    public function approvedSubmission(int $year, string $country)
    {
        return DB::table('cwis.sustainability_submissions')->where('reporting_year', $year)->where('country_code', $country)
            ->where('status', 'approved')->whereNotNull('approved_at')->first();
    }

    public function requireApproval(int $year, string $country): void
    {
        $fields = app(CwisSustainabilityFormService::class)->fields($country);
        if (!$fields['sections'] && !$fields['campaigns']) { return; }
        $source = $this->approvedSubmission($year, $country);
        if (!$source) {
            throw ValidationException::withMessages(['sustainability_approval' => __('Approve the Sustainability indicator submission for :year (:country) before generating CWIS results.', [
                'year' => $year, 'country' => __(CwisIndicatorService::COUNTRIES[$country]),
            ])]);
        }
        $this->validateSource($source);
    }

    public function validateSource($source): void
    {
        $fields = app(CwisSustainabilityFormService::class)->fields($source->country_code);
        $financials = DB::table('cwis.sustainability_financials')->where('submission_id', $source->id)->first();
        $campaigns = DB::table('cwis.sustainability_campaigns')->where('submission_id', $source->id)->get()->keyBy('indicator_code');
        $values = $rules = $labels = [];
        foreach ($fields['sections'] as $section) {
            foreach ($section['fields'] as $name => $label) {
                $values[$name] = $financials->$name ?? null;
                $rules[$name] = ['required', 'numeric', 'min:0'];
                $labels[$name] = __($label);
            }
        }
        foreach ($fields['campaigns'] as $code => $label) {
            $key = str_replace('.', '_', $code);
            $values[$key] = $campaigns->get($code)->answer ?? null;
            $rules[$key] = ['required', Rule::in(['Yes', 'No'])];
            $labels[$key] = __($label);
        }
        $validator = Validator::make($values, $rules, [], $labels);
        $validator->after(function ($validator) use ($fields, $campaigns) {
            foreach ($fields['campaigns'] as $code => $label) {
                $row = $campaigns->get($code);
                if ($row && $row->answer === 'Yes' && (!$row->document_path || !Storage::disk('local')->exists($row->document_path))) {
                    $validator->errors()->add(str_replace('.', '_', $code), __(':indicator requires an evidence document before calculation.', ['indicator' => __($label)]));
                }
            }
        });
        $validator->validate();
    }
}
