<?php

namespace App\Services\Fsm;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Illuminate\Session\SessionManager;
use Yajra\DataTables\DataTables;
use App\Models\Fsm\TreatmentPlantPerformanceTest;
use App\Models\Fsm\CwisSetting;
use App\Models\Fsm\Nsd;
use Illuminate\Support\Facades\DB;

class CwisSettingService
{

    protected $session;
    protected $instance;

    /**
     * Constructs a new CwisSetting object.
     *
     *
     */
    public function __construct()
    {
        /*Session code
        ....
         here*/
    }


    /**
     * Store or update a newly created resource in storage.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */


    public function storeOrUpdate($data)
    {
        $performance_test = CwisSetting::where('category', 'cwis_setting')->whereNull('deleted_at')->get();
        $updates = array_intersect_key($data, array_flip(array_merge(['active_country'], array_keys(config('cwis_safety_settings')), array_keys(config('cwis_safety_d22.decisions')))));
        DB::transaction(function () use ($updates, $performance_test) {
            foreach ($updates as $key => $value) {
                $setting = $performance_test->where('name', $key)->first();
                if (!$setting) {
                    throw new \RuntimeException('Missing CWIS setting: ' . $key . '. Run the CWIS settings migration first.');
                }
                $setting->value = $value;
                $setting->save();
            }
        });
    }
}
