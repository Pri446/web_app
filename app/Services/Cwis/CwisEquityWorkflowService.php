<?php

namespace App\Services\Cwis;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CwisEquityWorkflowService
{
    public function approvedSubmission(int $year, string $country)
    {
        return DB::table('cwis.equity_submissions')->where('reporting_year',$year)
            ->where('country_code',$country)->where('status','approved')->whereNotNull('approved_at')
            ->whereNull('deleted_at')->orderByDesc('approved_at')->orderByDesc('id')->first();
    }

    public function requireApproval(int $year, string $country): void
    {
        if ($this->requiresManualInputs($country) && !$this->approvedSubmission($year,$country)) {
            throw ValidationException::withMessages(['equity_approval' => __('Approve the Equity indicator submission for :year (:country) before generating CWIS results. Submitted is not yet approved.', [
                'year' => $year, 'country' => __(CwisIndicatorService::COUNTRIES[$country]),
            ])]);
        }
    }

    public function requiresManualInputs(string $country): bool
    {
        return app(CwisIndicatorService::class)->forCountry($country)->contains(function ($indicator) {
            return $indicator->outcome === 'equity' && !in_array($indicator->indicator_code,['EQ-1','EQ-2'],true);
        });
    }

    public function validateSource($submission): void
    {
        $forms = app(CwisFormService::class);
        $fields = $forms->applicable($forms->definitions(), app(CwisIndicatorService::class)
            ->forCountry($submission->country_code)->pluck('indicator_code')->all());
        if (!$fields) {
            throw ValidationException::withMessages(['submission' => __('No applicable Equity inputs are configured for this submission.')]);
        }
        $rows = [];
        foreach (array_unique(array_column($fields,'storage_table')) as $table) {
            $rows[$table] = DB::table($table)->where('submission_id',$submission->id)->whereNull('deleted_at')->first();
        }
        $values = $rules = $labels = $policyAnswers = [];
        foreach ($fields as $field) {
            $row = $rows[$field->storage_table];
            $value = $row->{$field->storage_column} ?? null;
            $values[$field->input_name] = $field->kind === 'policy' && $value !== null ? ($value ? 'Yes' : 'No') : $value;
            if ($field->kind === 'policy') { $policyAnswers[$field->indicator_code] = $values[$field->input_name]; }
        }
        foreach ($fields as $field) {
            $labels[$field->input_name] = __($field->label);
            $applicable = !$field->parent_code || ($policyAnswers[$field->parent_code] ?? null) === 'Yes';
            if (!$applicable) { continue; }
            $rules[$field->input_name] = ['required'];
            if ($field->kind === 'number') {
                $rules[$field->input_name] = array_merge($rules[$field->input_name], [$field->validation_type,'min:0','max:' . $field->maximum]);
            } else {
                $rules[$field->input_name][] = \Illuminate\Validation\Rule::in(['Yes','No']);
            }
        }
        $validator = Validator::make($values,$rules,[],$labels);
        $validator->after(function ($validator) use ($fields,$values,$rows,$policyAnswers) {
            foreach ($fields as $field) {
                if ($field->parent_code && ($policyAnswers[$field->parent_code] ?? null) !== 'Yes') { continue; }
                foreach ($field->not_greater_than as $total) {
                    if (is_numeric($values[$field->input_name]) && is_numeric($values[$total] ?? null)
                        && $values[$field->input_name] > $values[$total]) {
                        $validator->errors()->add($field->input_name, __($field->label) . ': ' . __('The count cannot exceed its corresponding total.'));
                    }
                }
                if ($field->kind === 'policy' && $values[$field->input_name] === 'Yes' && $field->document_column) {
                    $document = $rows[$field->storage_table]->{$field->document_column} ?? null;
                    if (!$document || !DB::table('cwis.equity_documents')->where('id',$document)->whereNotNull('path')->where('path','!=','')->exists()) {
                        $validator->errors()->add($field->input_name, $field->indicator_code . ' - ' . __($field->label) . ': ' . __('A Yes answer requires an attached evidence document before approval.'));
                    }
                }
            }
        });
        $validator->validate();
    }
}
