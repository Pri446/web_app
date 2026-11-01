<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CwisIndicatorCountryExampleSeeder extends Seeder
{
    public function run()
    {
        $mappings = config('cwis_country_examples');
        $sources = DB::table('cwis.data_source')->get(['id', 'indicator_code']);
        $rows = [];

        foreach ($mappings as $country => $codes) {
            foreach (array_unique($codes) as $code) {
                $matches = $sources->where('indicator_code', $code);
                if ($matches->count() !== 1) {
                    throw new \RuntimeException("Expected exactly one catalog record for CWIS indicator {$code}.");
                }
                $rows[] = [
                    'data_source_id' => $matches->first()->id,
                    'country_code' => $country,
                ];
            }
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                DB::table('cwis.indicator_country')->updateOrInsert($row, $row);
            }
        });

        if ($this->command) {
            $this->command->info('Loaded arbitrary CWIS country examples. These are development mappings, not approved national requirements.');
        }
    }
}
