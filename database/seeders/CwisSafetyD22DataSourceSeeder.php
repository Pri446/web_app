<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CwisSafetyD22DataSourceSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            DB::statement('LOCK TABLE cwis.data_source IN SHARE ROW EXCLUSIVE MODE');
            $next = (int) DB::table('cwis.data_source')->max('id') + 1;
            foreach (config('cwis_safety_d22.indicators') as $code => $definition) {
                if (!DB::table('cwis.data_source')->where('indicator_code', $code)->exists()) {
                    DB::table('cwis.data_source')->insert(['id' => $next++, 'indicator_code' => $code, 'label' => $definition['label'], 'outcome' => 'safety']);
                }
            }
            foreach (DB::table('cwis.data_source')->where('outcome', 'safety')->get() as $source) {
                DB::table('cwis.indicator_country')->insertOrIgnore(['data_source_id' => $source->id, 'country_code' => 'BD']);
                if (in_array($source->indicator_code, config('cwis_safety_d22.nepal'), true) || in_array($source->indicator_code, ['SF-0a','SF-0b','SF-0c'], true)) {
                    DB::table('cwis.indicator_country')->insertOrIgnore(['data_source_id' => $source->id, 'country_code' => 'NP']);
                }
            }
        });
    }
}
