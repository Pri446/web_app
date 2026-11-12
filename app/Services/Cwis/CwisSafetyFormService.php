<?php

namespace App\Services\Cwis;

use Illuminate\Support\Facades\DB;

class CwisSafetyFormService
{
    public function ready(): bool
    {
        return DB::table('information_schema.tables')->where('table_schema','cwis')
            ->whereIn('table_name',['safety_submissions','safety_answers','safety_waiting_times','safety_public_spaces'])->count() === 4;
    }

    public function codes(string $country): array
    {
        return app(CwisIndicatorService::class)->forCountry($country)->where('outcome','safety')->pluck('indicator_code')->all();
    }

    public function questions(string $country): array
    {
        $questions = [];
        foreach (array_intersect(config('cwis_safety_d22.policies'),$this->codes($country)) as $code) {
            $questions[$code] = config('cwis_safety_d22.indicators')[$code]['label'];
        }
        return $questions;
    }

    public function fields(string $country): array
    {
        $questions = $this->questions($country);
        return $questions ? [__('Sanitation Worker Safety and Certification') => $questions] : [];
    }

    public function available(string $country): bool
    {
        return $this->ready() && (bool) array_intersect(config('cwis_safety_d22.manual'),$this->codes($country));
    }

    public function toilets(string $country)
    {
        $types = [];
        if (in_array('SF-3d',$this->codes($country),true)) { $types[] = 'community toilet'; }
        if (in_array('SF-4c',$this->codes($country),true)) { $types[] = 'public toilet'; }
        return DB::table('fsm.toilets')->whereNull('deleted_at')->where('status',true)
            ->whereIn(DB::raw('lower(type)'),$types)->orderBy('name')->get(['id','name','type']);
    }

    public function publicToilets()
    {
        return DB::table('fsm.toilets')->whereNull('deleted_at')->where('status',true)
            ->whereRaw("lower(type) = 'public toilet'")->orderBy('name')->get(['id','name']);
    }
}
