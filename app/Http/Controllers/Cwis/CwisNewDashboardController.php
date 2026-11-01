<?php

namespace App\Http\Controllers\Cwis;

use App\Exports\MneCsvExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cwis\cwis_mne;
use Maatwebsite\Excel\Excel;
use Illuminate\Support\Facades\DB;
use PDF;

class CwisNewDashboardController extends Controller
{
    public function __construct(Excel $excel)
    {
        $this->excel = $excel;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('cwis.cwis-dashboard.chart-layout.cwis-dash-layout');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($emc)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    public function getall(Request $request, $year = null)
    {
      
        // Fetch available years and the latest year
        $presentYears = cwis_mne::distinct()->orderBy('year', 'desc')->pluck('year');
        $latestYear = cwis_mne::orderBy('year', 'desc')->pluck('year')->first();
        $selectedYear = $request->year ?? $latestYear;
        $indicatorService = app(\App\Services\Cwis\CwisIndicatorService::class);
        $country = $indicatorService->activeCountry();
        $countryLabel = __(\App\Services\Cwis\CwisIndicatorService::COUNTRIES[$country]);
        $countryIndicators = $indicatorService->forCountry($country);
        $allowedIndicatorCodes = $countryIndicators->pluck('indicator_code');
        $equityIndicators = $countryIndicators->where('outcome', 'equity');
        $safetyIndicators = $countryIndicators->where('outcome', 'safety');
        $sustainabilityIndicators = $countryIndicators->where('outcome', 'sustainability');
        $responsibilityIndicators = $countryIndicators->where('outcome', 'responsibility');
        $accountabilityIndicators = $countryIndicators->where('outcome', 'accountability');
        $rpmIndicators = $countryIndicators->where('outcome', 'rpm');
        $savedResults = $indicatorService->resultsForCountry((int) $selectedYear, $country);
        $data = $savedResults->pluck('data_value', 'indicator_code');
        $resultCountryMismatch = cwis_mne::where('year', $selectedYear)->exists()
            && $indicatorService->resultCountry((int) $selectedYear) !== $country;
    
        // Define indicator codes and their corresponding variable names
        $indicatorCodes = [
            'EQ-1' =>'eq1',
            'SF-1' => 'sf1',
            'SF-1a' => 'sf1a',
            'SF-1b' => 'sf1b',
            'SF-1c' => 'sf1c',
            'SF-1d' => 'sf1d',
            'SF-1e' => 'sf1e',
            'SF-1f' => 'sf1f',
            'SF-1g' => 'sf1g',
            'SF-2a' => 'sf2a',
            'SF-2b' => 'sf2b',
            'SF-2c' => 'sf2c',
            'SF-3'  => 'sf3',
            'SF-3b' => 'sf3b',
            'SF-3c' => 'sf3c',
            'SF-3e' => 'sf3e',
            'SF-4a' => 'sf4a',
            'SF-4b' => 'sf4b',
            'SF-4d' => 'sf4d',
            'SF-5' => 'sf5',
            'SF-6' => 'sf6',
            'SF-7' => 'sf7',
            'SF-9' => 'sf9',
            'SS-1' => 'ss1',
        ];
    
        $results = [];
        $hasData = false;
        $hasInvalidValues = false;
    
        // Process each indicator
        foreach ($indicatorCodes as $code => $varName) {
            $queryResult = $savedResults->has($code) ? collect([$savedResults->get($code)]) : collect();
    
            $results[$varName] = $queryResult;
    
            foreach ($queryResult as $resultRow) {
                $dataValue = strtolower($resultRow->data_value);
                if ($dataValue === 'nan' || $dataValue === 'na') {
                    $hasInvalidValues = true; // Set flag if invalid value found
                }
            }
    
            if ($queryResult->isNotEmpty()) {
                $hasData = true;
            }
        }

        // Check if there's no data
        $noDataMessage = $presentYears->isEmpty()
            ? "Dashboard cannot be displayed because there is no data for the selected year."
            : null;
    
        // Pass data to the view
        return view(
            'cwis.cwis-dashboard.chart-layout.cwis-dash-layout',
            array_merge(
                $results,
                compact('presentYears', 'latestYear', 'noDataMessage', 'hasInvalidValues', 'country', 'countryLabel', 'equityIndicators', 'safetyIndicators', 'sustainabilityIndicators', 'responsibilityIndicators', 'accountabilityIndicators', 'rpmIndicators', 'allowedIndicatorCodes', 'data', 'resultCountryMismatch', 'selectedYear')
            )
        );
    }
    

    
    

    public function exportCsv($year)
    {

        $year = intval($year);
        ob_end_clean();
        ob_start();
        return $this->excel->download(new MneCsvExport($year), 'CWIS_ ' . $year . '.xlsx');
    }

}
