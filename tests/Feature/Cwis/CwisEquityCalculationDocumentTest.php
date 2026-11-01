<?php

namespace Tests\Feature\Cwis;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CwisEquityCalculationDocumentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_calculation_migration_installs_and_rolls_back_its_definitions(): void
    {
        require_once database_path('migrations/cwis/equity/2026_10_13_000001_install_cwis_equity_calculation_functions.php');
        $migration = new \InstallCwisEquityCalculationFunctions();
        $migration->up();
        $this->assertEqualsWithDelta(2 / 3, DB::selectOne('SELECT cwis.calculate_eq_2(40,100,600,1000) AS value')->value, 0.000001);
        $this->assertSame('No', DB::selectOne('SELECT cwis.equity_policy_result(false, NULL) AS value')->value);
        $migration->down();
        $this->assertNull(DB::selectOne("SELECT to_regprocedure('cwis.calculate_eq_3(bigint, character varying)') AS value")->value);
    }

    public function test_canonical_sql_compiles_and_calculates_without_installing_permanently(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/cwis/equity/cwis_equity_calculations.sql')));
        $this->assertEqualsWithDelta(2 / 3, DB::selectOne('SELECT cwis.calculate_eq_2(40,100,600,1000) AS value')->value, 0.000001);
        $this->assertNull(DB::selectOne('SELECT cwis.calculate_eq_2(0,100,0,1000) AS value')->value);
        $id = DB::table('cwis.equity_submissions')->insertGetId([
            'reporting_year' => now()->year, 'country_code' => 'ALL', 'status' => 'approved', 'approved_at' => now(),
        ]);
        DB::table('cwis.equity_subsidies')->insert(['submission_id' => $id, 'total_subsidies_amount_nss' => 100, 'total_subsidies_amount_ss' => 50]);
        DB::table('cwis.sanitation_personnel_snapshot')->insert([
            'submission_id' => $id, 'women_employee_count' => 20, 'total_employee_count' => 80,
            'women_leadership_count' => 3, 'total_leadership_count' => 10,
            'average_salary_of_women_in_sanitation' => 200, 'average_salary_of_men_in_sanitation' => 250,
        ]);
        $doc = DB::table('cwis.equity_documents')->insertGetId(['disk' => 'public', 'path' => 'test/evidence.pdf', 'original_name' => 'evidence.pdf']);
        DB::table('cwis.sanitation_worker_policy')->insert([
            'submission_id' => $id, 'training_certification_required' => true, 'training_requirement_document_id' => $doc,
            'covers_labor_rights_and_recourse' => true, 'labor_rights_document_id' => $doc,
            'covers_safety_health_and_sop' => false, 'worker_union_exists' => false,
            'all_workers_social_security_covered' => true,
        ]);
        foreach (['3' => 2, '4' => 25, '4a' => 30, '5' => 0.2] as $suffix => $expected) {
            $this->assertEquals($expected, DB::selectOne("SELECT cwis.calculate_eq_{$suffix}(?, 'ALL') AS value", [$id])->value);
        }
        foreach (['6_1' => 'Yes', '6_1a' => 'Yes', '6_1b' => 'No', '6_2' => null, '6_3' => 'No',
            '6_3a' => 'Not applicable', '6_3b' => 'Not applicable', '6_4' => null, '6_5' => null] as $suffix => $expected) {
            $this->assertSame($expected, DB::selectOne("SELECT cwis.calculate_eq_{$suffix}(?, 'ALL') AS value", [$id])->value);
        }
        DB::table('cwis.equity_subsidies')->where('submission_id', $id)->update(['total_subsidies_amount_ss' => 0]);
        $this->assertNull(DB::selectOne("SELECT cwis.calculate_eq_3(?, 'ALL') AS value", [$id])->value);
        DB::table('cwis.sanitation_personnel_snapshot')->where('submission_id', $id)->update(['average_salary_of_women_in_sanitation' => 300]);
        $this->assertEquals(-0.2, DB::selectOne("SELECT cwis.calculate_eq_5(?, 'ALL') AS value", [$id])->value);
        foreach (['draft', 'submitted'] as $status) {
            DB::table('cwis.equity_submissions')->where('id', $id)->update(['status' => $status]);
            try {
                DB::transaction(function () use ($id) { DB::selectOne("SELECT cwis.calculate_eq_3(?, 'ALL')", [$id]); });
                $this->fail('Unapproved submission was accepted');
            } catch (\Illuminate\Database\QueryException $error) {
                $this->assertStringContainsString('requires an approved submission', $error->getMessage());
            }
        }
        DB::table('cwis.equity_submissions')->where('id', $id)->update(['status' => 'approved']);
        try {
            DB::transaction(function () use ($id) { DB::selectOne("SELECT cwis.calculate_eq_3(?, 'NP')", [$id]); });
            $this->fail('Wrong country was accepted');
        } catch (\Illuminate\Database\QueryException $error) {
            $this->assertStringContainsString('country does not match', $error->getMessage());
        }
    }
}
