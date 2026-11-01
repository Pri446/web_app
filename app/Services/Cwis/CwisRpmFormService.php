<?php

namespace App\Services\Cwis;

use Illuminate\Support\Facades\DB;

class CwisRpmFormService
{
    public function ready(): bool
    {
        return DB::table('information_schema.tables')->where('table_schema', 'cwis')
            ->whereIn('table_name', ['rpm_submissions', 'rpm_answers'])->count() === 2;
    }

    public function fields(string $country): array
    {
        $codes = app(CwisIndicatorService::class)->forCountry($country)->where('outcome', 'rpm')
            ->pluck('indicator_code')->all();
        $groups = [];
        foreach (config('cwis_rpm.groups') as $title => $questions) {
            $questions = array_intersect_key($questions, array_flip($codes));
            if ($questions) { $groups[$title] = $questions; }
        }
        return $groups;
    }

    public function questions(string $country): array
    {
        $questions = [];
        foreach ($this->fields($country) as $group) { $questions += $group; }
        return $questions;
    }

    public function available(string $country): bool
    {
        return $this->ready() && !empty($this->fields($country));
    }
}
