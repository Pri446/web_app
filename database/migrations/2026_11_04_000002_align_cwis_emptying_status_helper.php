<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlignCwisEmptyingStatusHelper extends Migration
{
    public function up()
    {
        $type = DB::selectOne("SELECT data_type FROM information_schema.columns
            WHERE table_schema = 'fsm' AND table_name = 'applications' AND column_name = 'emptying_status'")->data_type ?? null;
        if (!in_array($type, ['boolean', 'smallint', 'integer', 'bigint'], true)) {
            throw new RuntimeException('Unsupported application emptying status type.');
        }
        $signature = 'public.execute_select_build_sanisys_nd_criterias_part3()';
        $row = DB::selectOne('SELECT pg_get_functiondef(?::regprocedure) AS definition,
            pg_get_function_result(?::regprocedure) AS result', [$signature, $signature]);
        $updated = $row->definition;
        if ($type !== 'boolean') {
            $updated = preg_replace('/\ba\.emptying_status\s+IS\s+TRUE\b/i', 'a.emptying_status IN (1, 2)', $updated);
            // Preserve the declared boolean result even though the source field is now an integer.
            if (preg_match('/latest_emptying_status\s+boolean\b/i', $row->result)) {
                $updated = preg_replace('/\bbsc\.emptying_status\s+AS\s+latest_emptying_status\b/i',
                    '(bsc.emptying_status IN (1, 2)) AS latest_emptying_status', $updated);
            }
        }
        DB::statement('CREATE TABLE cwis.emptying_status_function_backup (signature text PRIMARY KEY, definition text NOT NULL)');
        DB::table('cwis.emptying_status_function_backup')->insert(['signature' => $signature, 'definition' => $row->definition]);
        if ($updated !== $row->definition) {
            DB::unprepared($updated);
        }
    }

    public function down()
    {
        foreach (DB::table('cwis.emptying_status_function_backup')->get() as $function) {
            DB::unprepared($function->definition);
        }
        DB::statement('DROP TABLE cwis.emptying_status_function_backup');
    }
}
