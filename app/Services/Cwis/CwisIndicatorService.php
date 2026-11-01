<?php

namespace App\Services\Cwis;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CwisIndicatorService
{
    public const COUNTRIES = ['ALL' => 'All', 'NP' => 'Nepal', 'BD' => 'Bangladesh'];

    public function activeCountry(): string
    {
        $country = DB::table('public.site_settings')->where('category', 'cwis_setting')
            ->where('name', 'active_country')->whereNull('deleted_at')->value('value') ?? 'ALL';
        $this->validateCountry($country);
        return $country;
    }

    public function forCountry(string $country)
    {
        $this->validateCountry($country);
        return collect(DB::select('SELECT * FROM cwis.indicators_for_country(?) ORDER BY id', [$country]));
    }

    public function validateCountry(string $country): void
    {
        if (!array_key_exists($country, self::COUNTRIES)) {
            throw ValidationException::withMessages(['country_code' => 'Select a valid CWIS country.']);
        }
    }

    public function resultCountry(int $year): string
    {
        return DB::table('cwis.generation_runs')->where('year', $year)->value('country_code') ?? 'ALL';
    }

    public function resultsForCountry(int $year, string $country)
    {
        $codes = $this->forCountry($country)->pluck('indicator_code');
        // Legacy results have no country audit record and must not be relabelled as NP or BD.
        if ($this->resultCountry($year) !== $country) {
            return collect();
        }
        return \App\Models\Cwis\cwis_mne::where('year', $year)->whereNull('deleted_at')
            ->whereIn('indicator_code', $codes)->get()->keyBy('indicator_code');
    }
}
