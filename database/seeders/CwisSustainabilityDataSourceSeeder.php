<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CwisSustainabilityDataSourceSeeder extends Seeder
{
    public function run()
    {
        // Bangladesh Sustainability is enabled; Nepal's existing mappings are untouched.
        $indicators = [
            'SS-1a' => 'Percentage of treated effluent reused',
            'SS-1b' => 'Percentage of treated biosolids reused',
            'SS-2' => 'Sanitation operation and maintenance cost recovery',
            'SS-3' => 'Government-funded sanitation capital expenditure coverage',
        ] + config('cwis_sustainability.campaigns');
        DB::transaction(function () use ($indicators) {
            DB::statement('LOCK TABLE cwis.data_source IN SHARE ROW EXCLUSIVE MODE');
            $nextId = ((int) DB::table('cwis.data_source')->max('id')) + 1;
            foreach ($indicators as $code => $label) {
                if (!DB::table('cwis.data_source')->where('indicator_code', $code)->exists()) {
                    DB::table('cwis.data_source')->insert(['id' => $nextId++, 'indicator_code' => $code, 'outcome' => 'sustainability', 'label' => $label]);
                }
                $sourceId = DB::table('cwis.data_source')->where('indicator_code', $code)->value('id');
                DB::table('cwis.indicator_country')->insertOrIgnore([
                    'data_source_id' => $sourceId, 'country_code' => 'BD',
                ]);
            }
        });
    }
}
