<?php

namespace App\Services\Cwis;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CwisRpmWorkflowService
{
    public function requireApproval(int $year, string $country): void
    {
        if (!app(CwisRpmFormService::class)->questions($country)) { return; }
        $source = DB::table('cwis.rpm_submissions')->where('reporting_year', $year)->where('country_code', $country)
            ->where('status', 'approved')->whereNotNull('approved_at')->first();
        if (!$source) {
            throw ValidationException::withMessages(['rpm_approval' => __('Approve the RPM indicator submission for :year (:country) before generating CWIS results.', [
                'year' => $year, 'country' => __(CwisIndicatorService::COUNTRIES[$country]),
            ])]);
        }
        $this->validateSource($source);
    }

    public function validateSource($source): void
    {
        if ($source->status !== 'approved' || !$source->approved_at) {
            throw ValidationException::withMessages(['submission' => __('Approve this RPM submission before calculation.')]);
        }
        $questions = app(CwisRpmFormService::class)->questions($source->country_code);
        if (!$questions) {
            throw ValidationException::withMessages(['submission' => __('RPM is unavailable for the selected country.')]);
        }
        $answers = DB::table('cwis.rpm_answers')->where('submission_id', $source->id)->get()->keyBy('indicator_code');
        $values = $rules = $labels = [];
        foreach ($questions as $code => $label) {
            $key = str_replace('.', '_', $code);
            $values[$key] = $answers->get($code)->answer ?? null;
            $rules[$key] = ['required', Rule::in(['Yes', 'No'])];
            $labels[$key] = __($label);
        }
        $validator = Validator::make($values, $rules, [], $labels);
        $validator->after(function ($validator) use ($questions, $answers) {
            foreach ($questions as $code => $label) {
                $answer = $answers->get($code);
                if ($answer && $answer->answer === 'Yes' && (!$answer->document_path || !Storage::disk('local')->exists($answer->document_path))) {
                    $validator->errors()->add(str_replace('.', '_', $code), __(':indicator requires an evidence document before calculation.', ['indicator' => __($label)]));
                }
            }
        });
        $validator->validate();
    }
}
