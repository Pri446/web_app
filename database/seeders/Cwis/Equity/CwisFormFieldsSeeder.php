<?php

namespace Database\Seeders\Cwis\Equity;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CwisFormFieldsSeeder extends Seeder
{
    public function run()
    {
        $sections = [
            [
                'title' => 'Equity of Subsidies (EQ-3)',
                'fields' => [
                    ['nss', 'total_subsidies_amount_nss', 'EQ-3.NSS', 'Total Subsidies Amount NSS', 'Total subsidies amount paid to non-sewered sanitation during the reporting year.', '0.01'],
                    ['ss', 'total_subsidies_amount_ss', 'EQ-3.SS', 'Total Subsidies Amount SS', 'Total subsidies amount paid to sewered sanitation during the reporting year, in the same currency as NSS.', '0.01'],
                ],
            ],
            [
                'title' => 'Gender Equity in Sanitation Workforce',
                'fields' => [
                    ['w4', 'women_employee_count', 'EQ-4.W', 'Women Employees', 'Women in eligible sanitation decision-making bodies, including full-time and contract staff; excluding NGOs and community organizations.', '1'],
                    ['t4', 'total_employee_count', 'EQ-4.T', 'Total Employees', 'All employees in the same eligible bodies.', '1'],
                    ['w4a', 'women_leadership_count', 'EQ-4a.W', 'Women in Leadership', 'Women who are functional or managerial heads.', '1'],
                    ['t4a', 'total_leadership_count', 'EQ-4a.T', 'Total Leadership Positions', 'All people in leadership positions; cannot exceed total employees.', '1'],
                    ['sw', 'average_salary_of_women_in_sanitation', 'EQ-5.W', 'Avg Annual Salary - Women', 'Average annualized pay of eligible women.', '0.01'],
                    ['sm', 'average_salary_of_men_in_sanitation', 'EQ-5.M', 'Avg Annual Salary - Men', 'Average annualized pay of eligible men, on the same basis as women.', '0.01'],
                ],
            ],
        ];
        $policies = [
            ['EQ-6.1', 'Training / Certification Required', '', 'Training requirement document'],
            ['EQ-6.1a', 'Training Covers Labor Rights & Recourse', 'EQ-6.1', 'Training content covering both topics'],
            ['EQ-6.1b', 'Training Covers Safety, Health Risks & SOP', 'EQ-6.1', 'Curriculum covering all three topics'],
            ['EQ-6.2', 'Formal Legal Recourse for All Workers', '', 'Evidence that the channel also covers informal workers'],
            ['EQ-6.3', 'Right to Unionize / Union Registered', '', 'Union registration document'],
            ['EQ-6.3a', 'Worker Union Is Operational', 'EQ-6.3', 'Meeting minutes or similar evidence'],
            ['EQ-6.3b', 'City Supports Running the Union', 'EQ-6.3', 'Evidence of city support'],
            ['EQ-6.4', 'All Sanitation Workers Are Covered by Social Security', '', 'Document verifying social-security coverage of all sanitation workers in the city'],
            ['EQ-6.5', 'All Sanitation Workers Are Covered by Health Insurance', '', 'Document verifying health-insurance coverage of all sanitation workers in the city'],
        ];

        $policyColumns = [
            'EQ-6.1' => ['training_certification_required', 'training_requirement_document'],
            'EQ-6.1a' => ['covers_labor_rights_and_recourse', 'labor_rights_document'],
            'EQ-6.1b' => ['covers_safety_health_and_sop', 'safety_health_sop_document'],
            'EQ-6.2' => ['legal_recourse_available_to_all', 'legal_recourse_document'],
            'EQ-6.3' => ['worker_union_exists', 'worker_union_registration_document'],
            'EQ-6.3a' => ['worker_union_operational', 'worker_union_operation_document'],
            'EQ-6.3b' => ['city_supports_worker_union', 'worker_union_support_document'],
            'EQ-6.4' => ['all_workers_social_security_covered', 'social_security_coverage_document'],
            'EQ-6.5' => ['all_workers_health_insurance_covered', 'health_insurance_coverage_document'],
        ];


        $inputs = config('cwis_equity.inputs');
        $comparisons = [
            'women_employee_count' => ['total_employee_count'],
            'women_leadership_count' => ['total_leadership_count', 'women_employee_count'],
            'total_leadership_count' => ['total_employee_count'],
        ];
        $order = 0;
        DB::transaction(function () use ($sections, $policies, $policyColumns, $inputs, $comparisons, &$order) {
            foreach ($sections as $section) {
                foreach ($section['fields'] as [$id, $name, $display, $label, $hint, $step]) {
                    [$code, $type, $maximum] = $inputs[$name];
                    $maximum = $code === 'EQ-5' ? '9999999999999999.99' : (string) $maximum;
                    $this->seedField($code, $name, [
                        'kind' => 'number', 'section' => $section['title'], 'label' => $label,
                        'help_text' => $hint, 'html_id' => $id, 'display_code' => $display,
                        'validation_type' => $type, 'maximum' => $maximum, 'step' => $step,
                        'required_on_submit' => true,
                        'storage_table' => $code === 'EQ-3' ? 'cwis.equity_subsidies' : 'cwis.sanitation_personnel_snapshot',
                        'storage_column' => $name, 'not_greater_than' => json_encode($comparisons[$name] ?? []),
                        'sort_order' => ++$order,
                    ]);
                }
            }
            foreach ($policies as [$code, $label, $parent, $hint]) {
                [$column, $document] = $policyColumns[$code];
                $this->seedField($code, str_replace(['-', '.'], '_', $code), [
                    'kind' => 'policy', 'section' => 'Sanitation Worker Policies and Coverage',
                    'label' => $label, 'html_id' => str_replace(['-', '.'], '_', $code),
                    'display_code' => $code, 'parent_code' => $parent ?: null,
                    'evidence_hint' => $hint, 'storage_table' => 'cwis.sanitation_worker_policy',
                    'storage_column' => $column, 'document_column' => $document . '_id',
                    'sort_order' => ++$order,
                ]);
            }
        });
    }

    private function seedField(string $code, string $name, array $definition): void
    {
        $ids = DB::table('cwis.data_source')->where('indicator_code', $code)->pluck('id');
        if ($ids->count() !== 1) {
            throw new \LogicException('Expected one CWIS data source for ' . $code);
        }
        // Seed missing definitions only; preserve administrator changes on subsequent runs.
        if (!DB::table('cwis.form_fields')->where('input_name', $name)->exists()) {
            DB::table('cwis.form_fields')->insert($definition + [
                'data_source_id' => $ids->first(), 'input_name' => $name,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
