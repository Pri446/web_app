<?php

namespace App\Services\Cwis;

use Illuminate\Support\Facades\DB;

class CwisFormService
{
    public function definitions(): array
    {
        $fields = DB::table('cwis.form_fields as field')
            ->join('cwis.data_source as source', 'source.id', '=', 'field.data_source_id')
            ->where('source.outcome', 'equity')->where('field.enabled', true)
            ->select('field.*', 'source.indicator_code')->orderBy('field.sort_order')->orderBy('field.id')->get();

        return $fields->map(function ($field) {
            $field->not_greater_than = json_decode($field->not_greater_than ?? '[]', true);
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $field->input_name)
                || !is_array($field->not_greater_than)
                || !in_array($field->kind, ['number', 'policy'], true)
                || ($field->kind === 'number' && (!in_array($field->validation_type, ['integer', 'numeric'], true)
                    || !is_numeric($field->maximum) || $field->maximum < 0))) {
                throw new \LogicException('Unsupported CWIS field type: ' . $field->input_name);
            }
            // Metadata may only target the dedicated Equity source tables, never arbitrary application tables.
            if (!in_array($field->storage_table, ['cwis.equity_subsidies', 'cwis.sanitation_personnel_snapshot', 'cwis.sanitation_worker_policy'], true)
                || !preg_match('/^[a-z][a-z0-9_]*$/', $field->storage_column)
                || in_array($field->storage_column, ['id', 'submission_id', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'], true)
                || ($field->document_column && !preg_match('/^[a-z][a-z0-9_]*_document_id$/', $field->document_column))) {
                throw new \LogicException('Invalid CWIS storage mapping: ' . $field->input_name);
            }
            return $field;
        })->all();
    }

    public function applicable(array $definitions, array $codes): array
    {
        return array_values(array_filter($definitions, function ($field) use ($codes) {
            return in_array($field->indicator_code, $codes, true)
                && (!$field->parent_code || in_array($field->parent_code, $codes, true));
        }));
    }

    public function presentation(array $fields): array
    {
        $sections = [];
        $policies = [];
        foreach ($fields as $field) {
            if ($field->kind === 'policy') {
                $policies[] = [$field->indicator_code, $field->label, $field->parent_code ?? '', $field->evidence_hint, $field->input_name, $field->required_on_submit];
                continue;
            }
            $sections[$field->section]['title'] = $field->section;
            $sections[$field->section]['fields'][] = [$field->html_id, $field->input_name,
                $field->display_code, $field->label, $field->help_text, $field->step,
                $field->maximum, $field->required_on_submit, $field->not_greater_than];
        }
        return ['sections' => array_values($sections), 'policies' => $policies];
    }
}
