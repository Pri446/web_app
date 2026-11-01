<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CwisRpmDataSourceSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            DB::statement('LOCK TABLE cwis.data_source IN SHARE ROW EXCLUSIVE MODE');
            $nextId = ((int) DB::table('cwis.data_source')->max('id')) + 1;
            foreach (config('cwis_rpm.groups') as $questions) {
                foreach ($questions as $code => $label) {
                    if (!DB::table('cwis.data_source')->where('indicator_code', $code)->exists()) {
                        DB::table('cwis.data_source')->insert([
                            'id' => $nextId++, 'indicator_code' => $code, 'outcome' => 'rpm', 'label' => $label,
                        ]);
                    }
                    $id = DB::table('cwis.data_source')->where('indicator_code', $code)->value('id');
                    DB::table('cwis.indicator_country')->insertOrIgnore(['data_source_id' => $id, 'country_code' => 'BD']);
                }
            }
        });
    }
}
