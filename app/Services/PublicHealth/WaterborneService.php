<?php

namespace App\Services\PublicHealth;

use App\Enums\HotspotDisease;
use Illuminate\Http\Request;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use Auth;
use DataTables;
use DB;
use App\http\Requests\PublicHealth\YearlyWaterborneRequest;
use App\Models\PublicHealth\YearlyWaterborne;

class WaterborneService
{
    public function fetchData($request)
    {
        $yearlyWaterborne = YearlyWaterborne::whereNull('deleted_at');
        return Datatables::of($yearlyWaterborne)
            ->filter(function ($query) use ($request) {
                if ($request->year) {
                    $query->where('year', $request->year);
                }
                if ($request->infected_disease) {
                    $query->where('infected_disease', $request->infected_disease);
                }
            })
            ->editColumn('infected_disease', function ($model) {
                switch ($model->infected_disease) {
                    case HotspotDisease::Cholera:
                        return 'Cholera';
                    case HotspotDisease::Diarrhea:
                        return 'Diarrhea';
                    case HotspotDisease::Dysentery:
                        return 'Dysentery';
                    case HotspotDisease::HepatitisA:
                        return 'Hepatitis A';
                    case HotspotDisease::Typhoid:
                        return 'Typhoid';
                    case HotspotDisease::Polio:
                        return 'Polio';
                    default:
                        return 'Unknown';
                }
            })

            ->addColumn('action', function ($model) {
                $content = \Form::open(['method' => 'DELETE', 'route' => ['waterborne.destroy', $model->id]]);

                if (Auth::user()->can('Edit Yearly Waterborne Cases')) {
                    $content .= '<a title="' . e(__("Edit")) . '" href="' . action("PublicHealth\YearlyWaterborneController@edit", [$model->id]) . '" class="btn btn-info btn-sm mb-1"  ><i class="fas fa-edit"></i></a> ';
                }
                if (Auth::user()->can('View Yearly Waterborne Cases')) {
                    $content .= '<a title="' . e(__("Detail")) . '" href="' . action("PublicHealth\YearlyWaterborneController@show", [$model->id]) . '"class="btn btn-info btn-sm mb-1"  ><i class="fas fa-list"></i></a> ';
                }
                if (Auth::user()->can('View Yearly Waterborne Case History')) {
                    $content .= '<a title="' . e(__("History")) . '" href="' . action("PublicHealth\YearlyWaterborneController@history", [$model->id]) .'" class="btn btn-info btn-sm mb-1"  ><i class="fas fa-history"></i></a> ';
                }
                if (Auth::user()->can('Delete Yearly Waterborne Cases')) {
                    $content .= '<a href="#" title="' . e(__("Delete")) . '" class="delete btn btn-danger btn-sm mb-1 "  ><i class="fas fa-trash"></i></a> ';
                }
                $content .= \Form::close();
                return $content;
            })
            ->rawColumns(['infected_disease', 'action'])
            ->make(true);
    }


    public function storeData($request)
    {
        $yearlyWaterborne = new YearlyWaterborne();
        $fields = ['infected_disease', 'year', 'notes'];
        $totalCases = 0;
        $totalFatalities = 0;
        foreach ($fields as $field) {
            $yearlyWaterborne->$field = $request->$field ?? null;
        }
        $caseFields = ['male_cases', 'female_cases', 'other_cases'];
        $fatalityFields = ['male_fatalities', 'female_fatalities', 'other_fatalities'];
        foreach ($caseFields as $field) {
            $yearlyWaterborne->$field = $request->$field ?? null;
            $totalCases += $yearlyWaterborne->$field ?? 0;
        }
        foreach ($fatalityFields as $field) {
            $yearlyWaterborne->$field = $request->$field ?? null;
            $totalFatalities += $yearlyWaterborne->$field ?? 0;
        }
        $yearlyWaterborne->total_no_of_cases = $totalCases;
        $yearlyWaterborne->total_no_of_fatalities = $totalFatalities;
        $yearlyWaterborne->save();
        return redirect('publichealth/waterborne')->with('success', __('Waterborne Cases Information created successfully.'));
    }


    public function updateData($request, $id)
    {
        $yearlyWaterborne = YearlyWaterborne::find($id);
        if (!$yearlyWaterborne) {
            return redirect('publichealth/waterborne')->with('error', __('Failed to update Yearly Waterborne.'));
        }
        $fields = ['infected_disease', 'year', 'notes'];
        $totalCases = 0;
        $totalFatalities = 0;
        foreach ($fields as $field) {
            $yearlyWaterborne->$field = $request->$field ?? null;
        }
        $caseFields = ['male_cases', 'female_cases', 'other_cases'];
        $fatalityFields = ['male_fatalities', 'female_fatalities', 'other_fatalities'];
        foreach ($caseFields as $field) {
            $yearlyWaterborne->$field = $request->$field ?? null;
            $totalCases += $yearlyWaterborne->$field ?? 0;
        }
        foreach ($fatalityFields as $field) {
            $yearlyWaterborne->$field = $request->$field ?? null;
            $totalFatalities += $yearlyWaterborne->$field ?? 0;
        }
        $yearlyWaterborne->total_no_of_cases = $totalCases;
        $yearlyWaterborne->total_no_of_fatalities = $totalFatalities;
        $yearlyWaterborne->save();
        return redirect('publichealth/waterborne')->with('success', __('Waterborne Cases Information updated successfully.'));
    }



    public function exportData()
    {
        $infected_disease = request('infected_disease') ?? null;
        $year = request('year') ?? null;
        $columns = [__('ID'), __('Infected Disease'), __('Year'), __('No. of Cases'), __('No. of Fatalities'),__('Notes')];
        $query = YearlyWaterborne::select('id', 'infected_disease', 'year', 'total_no_of_cases', 'total_no_of_fatalities','notes')->whereNull('deleted_at');
        if (!empty($year)) {
            $query->where('year', $year);
        }
        if (!empty($infected_disease)) {
            $query->where('infected_disease', $infected_disease);
        }
        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();

        $writer = WriterFactory::create(Type::CSV);

        $writer->openToBrowser('Waterborne Cases.csv')
            ->addRowWithStyle($columns, $style); // Top row of the CSV

        $query->chunk(5000, function ($yearlyWaterborne) use ($writer) {
            foreach ($yearlyWaterborne as $data) {
                $values = [];
                $values[] = $data->id;
                $values[] = ucwords(HotspotDisease::getDescription($data->infected_disease));
                $values[] = $data->year;
                $values[] = $data->total_no_of_cases;
                $values[] = $data->total_no_of_fatalities;
                $values[] = $data->notes;


                $writer->addRow($values);
            }
        });

        $writer->close();
    }


    public function showData($id)
    {
        $YearlyWaterborne = YearlyWaterborne::find($id);
        if ($YearlyWaterborne) {
            $page_title = __("Waterborne Cases Information Details");
            $enumValue =  (int)$YearlyWaterborne->infected_disease;
            $diseaseName = HotspotDisease::getDescription($enumValue);
            return view('public-health.waterborne.show', compact('page_title', 'YearlyWaterborne', 'diseaseName'));
        } else {
            abort(404);
        }
    }
}
