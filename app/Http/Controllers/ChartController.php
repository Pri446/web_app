<?php

// app/Http/Controllers/ChartController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

class ChartController extends Controller
{
    public function showChart()
    {
        return view('chart');
    }
    public function downloadPDF(Request $request)
    {
        $accountabilityIndicators = collect();
        $accountabilityValues = collect();
        $rpmIndicators = collect();
        $rpmValues = collect();
        $safetyCountIndicators = collect();
        $safetyCountValues = collect();
        $safetyD22Indicators = collect();
        $countryLabel = null;
        $reportingYear = null;
        $pdfAllowedCodes = null;
        if ($request->filled('year')) {
            abort_unless($request->user() && $request->user()->can('List CWIS'), 403);
            $validated = $request->validate(['year' => ['required', 'integer', 'between:1,9999']]);
            $reportingYear = (int) $validated['year'];
            $indicators = app(\App\Services\Cwis\CwisIndicatorService::class);
            $country = $indicators->activeCountry();
            $countryLabel = __(\App\Services\Cwis\CwisIndicatorService::COUNTRIES[$country]);
            $catalogue = $indicators->forCountry($country);
            $pdfAllowedCodes = $catalogue->pluck('indicator_code');
            $accountabilityIndicators = $catalogue->where('outcome', 'accountability');
            $accountabilityValues = $indicators->resultsForCountry($reportingYear, $country)->pluck('data_value', 'indicator_code');
            $rpmIndicators = $catalogue->where('outcome', 'rpm');
            $rpmValues = $accountabilityValues;
            $safetyCountIndicators = $catalogue->where('outcome', 'safety')->whereIn('indicator_code', ['SF-0a', 'SF-0b', 'SF-0c']);
            $safetyCountValues = $accountabilityValues;
            $safetyD22Indicators = $catalogue->where('outcome', 'safety')->whereIn('indicator_code', array_keys(config('cwis_safety_d22.indicators')));
        }
        $eq1 = $request->input('eq1');
        $sf3d = $request->input('sf3d');
        $base64Image = $request->input('chartImage');
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
        $encodedData = base64_encode($imageData);

        //
        $base64safety1aImage = $request->input('safety1aImage');
        $imageDatasf1a = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety1aImage));
        $encodedDatasf1a = base64_encode($imageDatasf1a);

        //
        $base64safety1bImage = $request->input('safety1bImage');
        $imageDatasf1b = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety1bImage));
        $encodedDatasf1b = base64_encode($imageDatasf1b);

         //
        $base64safety1cImage = $request->input('safety1cImage');
        $imageDatasf1bc = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety1cImage));
        $encodedDatasf1c = base64_encode($imageDatasf1bc);

  //
        $base64safety1dImage = $request->input('safety1dImage');
        $imageDatasf1d = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety1dImage));
        $encodedDatasf1d = base64_encode($imageDatasf1d);

   //
            $base64safety1eImage = $request->input('safety1eImage');
            $imageDatasf1e = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety1eImage));
            $encodedDatasf1e = base64_encode($imageDatasf1e);

    //
        $base64safety1fImage = $request->input('safety1fImage');
        $imageDatasf1f = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety1fImage));
        $encodedDatasf1f = base64_encode($imageDatasf1f);

     //
     $base64safety1gImage = $request->input('safety1gImage');
     $imageDatasf1g = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety1gImage));
     $encodedDatasf1g = base64_encode($imageDatasf1g);
     //
        $base64safety2aImage = $request->input('safety2aImage');
        $imageDatasf2a = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety2aImage));
        $encodedDatasf2a = base64_encode($imageDatasf2a);

  //
        $base64safety2bImage = $request->input('safety2bImage');
        $imageDatasf2b = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety2bImage));
        $encodedDatasf2b = base64_encode($imageDatasf2b);

   //
    $base64safety2cImage = $request->input('safety2cImage');
    $imageDatasf2c = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety2cImage));
    $encodedDatasf2c = base64_encode($imageDatasf2c);
    //
        $base64safety3aImage = $request->input('safety3aImage');
        $imageDatasf3a = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety3aImage));
        $encodedDatasf3a = base64_encode($imageDatasf3a);

  //
        $base64safety3bImage = $request->input('safety3bImage');
        $imageDatasf3b = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety3bImage));
        $encodedDatasf3b = base64_encode($imageDatasf3b);

   //
        $base64safety3cImage = $request->input('safety3cImage');
        $imageDatasf3c = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety3cImage));
        $encodedDatasf3c = base64_encode($imageDatasf3c);
        //
        $base64safety5Image = $request->input('sf5Image');
        $imageDatasf5 = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety5Image));
        $encodedDatasf5 = base64_encode($imageDatasf5);
        //
        $base64safety6Image = $request->input('sf6Image');
        $imageDatasf6 = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety6Image));
        $encodedDatasf6 = base64_encode($imageDatasf6);
          //
          $base64safety7Image = $request->input('sf7Image');
          $imageDatasf7 = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety7Image));
          $encodedDatasf7 = base64_encode($imageDatasf7);

           //
           $base64ss1Image = $request->input('ss1Image');
           $imageDatass1 = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64ss1Image));
           $encodedDatass1 = base64_encode($imageDatass1);
//
 //
        $base64safety4aImage = $request->input('safety4aImage');
        $imageDatasf4a = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety4aImage));
        $encodedDatasf4a = base64_encode($imageDatasf4a);

  //
        $base64safety4bImage = $request->input('safety4bImage');
        $imageDatasf4b = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety4bImage));
        $encodedDatasf4b = base64_encode($imageDatasf4b);

   //
        $base64safety4dImage = $request->input('safety4dImage');
        $imageDatasf4d = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety4dImage));
        $encodedDatasf4d = base64_encode($imageDatasf4d);

        $base64safety9Image = $request->input('sf9Image');
        $imageDatasf9 = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64safety9Image));
        $encodedDatasf9 = base64_encode($imageDatasf9); 
        $html = View::make('cwis.cwis-dashboard.chart-layout.cwis-pdf-layout',compact('pdfAllowedCodes','eq1','encodedDatasf1a','encodedDatasf1b','encodedDatasf1c','encodedDatasf1d',
        'encodedDatasf1e','encodedDatasf1f','encodedDatasf1g','encodedDatasf2a','encodedDatasf2b','encodedDatasf2c'
        ,'encodedDatasf3a','encodedDatasf3b','encodedDatasf3c','sf3d'
        ,'encodedDatasf4a','encodedDatasf4b','encodedDatasf4d'
        ,'encodedDatasf5',
        'encodedDatasf6', 'encodedDatasf7', 'encodedDatass1','encodedDatasf9'
        ))->render();
        $html .= View::make('cwis.cwis-dashboard.chart-layout.accountability-pdf-cards', compact(
            'accountabilityIndicators', 'accountabilityValues', 'countryLabel', 'reportingYear'
        ))->render();
        $html .= View::make('cwis.cwis-dashboard.chart-layout.rpm-pdf-cards', compact(
            'rpmIndicators', 'rpmValues', 'countryLabel', 'reportingYear'
        ))->render();

        $html .= View::make('cwis.cwis-dashboard.chart-layout.safety-count-pdf-cards', compact(
            'safetyCountIndicators', 'safetyCountValues', 'countryLabel', 'reportingYear'
        ))->render();

        $html .= View::make('cwis.cwis-dashboard.chart-layout.safety-d22-pdf-cards', [
            'safetyD22Indicators' => $safetyD22Indicators, 'safetyD22Values' => $safetyCountValues,
            'countryLabel' => $countryLabel, 'reportingYear' => $reportingYear,
        ])->render();

        // Generate a unique filename for the PDF
        return Pdf::loadHTML($html)->download('example.pdf');
    }
    // public function generatePdf(Request $request)
    // {
    //     // $html = view('cwis.cwis-dashboard.chart-layout.cwis-pdf-layout')->render(); // Replace 'pdf_template' with your view name
    //     // return Pdf::loadHTML($html)->download('example.pdf');
    //     $html = view('cwis.cwis-dashboard.chart-layout.cwis-pdf-layout')->render();

    //     // Configure Dompdf
    //     $options = new ();
    //     $options->set('isHtml5ParserEnabled', true);

    //     // Instantiate Dompdf
    //     $dompdf = new Dompdf($options);

    //     // Load HTML content
    //     $dompdf->loadHtml($html);

    //     // (Optional) Set paper size and orientation
    //     $dompdf->setPaper('A4', 'portrait');

    //     // Render PDF (generate)
    //     $dompdf->render();

    //     // Output the generated PDF to Browser (Downloadable file)
    //     return $dompdf->stream("output.pdf", array("Attachment" => false));
    // }
}
