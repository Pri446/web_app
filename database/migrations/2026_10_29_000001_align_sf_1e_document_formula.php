<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlignSf1eDocumentFormula extends Migration
{
    public function up()
    {
        $signature = 'cwis.calculate_safety_revised(integer,character varying,numeric,numeric,numeric,numeric)';
        $original = DB::selectOne('SELECT pg_get_functiondef(?::regprocedure) AS definition', [$signature])->definition;
        DB::statement('CREATE TABLE cwis.sf_1e_formula_backup (definition text NOT NULL)');
        DB::table('cwis.sf_1e_formula_backup')->insert(['definition' => $original]);
        $sql = file_get_contents(database_path('sql/cwis_safety_revised.sql'));
        $start = strpos($sql, 'CREATE OR REPLACE FUNCTION cwis.calculate_safety_revised(');
        $end = strpos($sql, 'CREATE OR REPLACE FUNCTION cwis.write_safety_revised(', $start === false ? 0 : $start);
        if ($start === false || $end === false) {
            throw new RuntimeException('Cannot locate revised Safety calculation function.');
        }
        DB::unprepared(substr($sql, $start, $end - $start));
    }

    public function down()
    {
        $original = DB::table('cwis.sf_1e_formula_backup')->value('definition');
        DB::unprepared($original);
        DB::statement('DROP TABLE cwis.sf_1e_formula_backup');
    }
}
