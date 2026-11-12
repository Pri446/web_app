<?php

namespace App\Services\Cwis;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CwisSafetyWorkflowService
{
    public function requireApproval(int $year, string $country): void
    {
        $service = app(CwisSafetyFormService::class);
        if (!array_intersect(config('cwis_safety_d22.manual'),$service->codes($country))) { return; }
        $source = $service->ready() ? DB::table('cwis.safety_submissions')->where('reporting_year',$year)
            ->where('country_code',$country)->where('status','approved')->whereNotNull('approved_at')->first() : null;
        if (!$source) { throw ValidationException::withMessages(['safety_approval' => __('Approve Safety indicator data for the selected year and country before generating CWIS results.')]); }
        $this->validateSource($source);
    }

    public function validateSource($source): void
    {
        if ($source->status !== 'approved' || !$source->approved_at) { throw ValidationException::withMessages(['submission' => __('Approve this Safety submission before calculation.')]); }
        $service = app(CwisSafetyFormService::class);
        $answers = DB::table('cwis.safety_answers')->where('submission_id',$source->id)->get()->keyBy('indicator_code');
        $errors = [];
        foreach ($service->questions($source->country_code) as $code => $label) {
            $parent = config('cwis_safety_d22.parents')[$code] ?? null;
            if ($parent && ($answers->get($parent)->answer ?? null) === 'No') { continue; }
            $row = $answers->get($code);
            if (!$row || !in_array($row->answer,['Yes','No'],true)) { $errors[$code] = __($label).' '.__('requires a Yes or No answer.'); continue; }
            if ($row->answer === 'Yes') {
                foreach (['document_path'] + ($code === 'SF-12' ? [1 => 'biosolids_document_path'] : []) as $field) {
                    if (!$row->$field || !Storage::disk('local')->exists($row->$field)) { $errors[$code] = __($label).' '.__('requires an attached evidence document.'); }
                }
            }
        }
        $waiting = DB::table('cwis.safety_waiting_times')->where('submission_id',$source->id)->get()->keyBy('toilet_id');
        foreach ($service->toilets($source->country_code) as $toilet) {
            $row = $waiting->get($toilet->id);
            if (!$row || $row->waiting_minutes === null || $row->waiting_minutes < 0 || !$row->number_users
                || !$row->document_path || !Storage::disk('local')->exists($row->document_path)) {
                $errors['waiting_'.$toilet->id] = $toilet->name.' '.__('requires waiting time, user count and evidence.');
            }
        }
        if (in_array('SF-4',$service->codes($source->country_code),true) && !$source->public_spaces_complete) {
            $errors['public_spaces_complete'] = __('Confirm that the public-space inventory is complete.');
        }
        if ($errors) { throw ValidationException::withMessages($errors); }
    }
}
