<?php

namespace App\Http\Controllers\Fsm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Fsm\TreatmentplantPerformanceTestRequest;
use App\Models\Fsm\CwisSetting;
use App\Services\Fsm\TreatmentplantPerformanceTestService;
use App\Services\Fsm\CwisSettingService;
use App\Models\Fsm\TreatmentPlantPerformanceTest;
use App\Models\SiteSetting;
use DB;
use App\Services\Cwis\CwisIndicatorService;
use Illuminate\Validation\Rule;

class CwisSettingController extends Controller
{
    protected CwisSettingService $cwissetting;
    public function __construct(CwisSettingService $cwissetting)
    {
        $this->middleware('auth');
        $this->cwissetting = $cwissetting;
    }

    public function index()
    {
        $page_title = __('CWIS Setting');
    
        // Retrieve all settings
        $data1 = CwisSetting::all();
    
        // Get the most recent updated_at timestamp
        $updated = $data1->isNotEmpty() ? $data1->max('updated_at') : null;
    
        // Get settings categorized as 'cwis_setting', return as key-value pairs
        $data = CwisSetting::where('category', 'cwis_setting')->pluck('value', 'name');
    
        $countries = CwisIndicatorService::COUNTRIES;
        $parameters = config('cwis_safety_settings');
        $decisions = config('cwis_safety_d22.decisions');
        return view('fsm/cwis-setting.index', compact('page_title', 'data', 'updated', 'countries', 'parameters', 'decisions'));
    }
    

    public function store(Request $request)
    {
        abort_unless($request->user()->can('Edit CWIS'),403);

        $rules = ['active_country' => ['required', Rule::in(array_keys(CwisIndicatorService::COUNTRIES))]];
        foreach (config('cwis_safety_settings') as $key => $parameter) {
            $rules[$key] = ['required', 'numeric', 'min:' . $parameter['min'], 'max:' . $parameter['max']];
        }
        foreach (config('cwis_safety_d22.decisions') as $key => $decision) {
            $rules[$key] = ['sometimes','required',Rule::in(array_keys($decision['options']))];
        }
        $data = $request->validate($rules);
        $result = $this->cwissetting->storeOrUpdate($data);
        return redirect('fsm/cwis-setting')->with('success', __('Site Setting updated successfully.'));
    }

}
