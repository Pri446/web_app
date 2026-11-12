<?php

namespace App\Services\Cwis;

use Illuminate\Support\Facades\DB;

class CwisSustainabilityFormService
{
    public function ready(): bool
    {
        // Laravel 8's hasTable checks the default schema rather than qualified names.
        return DB::table('information_schema.tables')->where('table_schema', 'cwis')
            ->whereIn('table_name', ['sustainability_submissions', 'sustainability_financials', 'sustainability_campaigns'])
            ->count() === 3;
    }

    public function fields(string $country): array
    {
        $codes = app(CwisIndicatorService::class)->forCountry($country)->pluck('indicator_code')->all();
        return [
            'sections' => array_intersect_key(config('cwis_sustainability.financial_sections'), array_flip($codes)),
            'campaigns' => array_intersect_key(config('cwis_sustainability.campaigns'), array_flip($codes)),
        ];
    }

    public function available(string $country): bool
    {
        $fields = $this->fields($country);
        return $this->ready() && (!empty($fields['sections']) || !empty($fields['campaigns']));
    }
}
