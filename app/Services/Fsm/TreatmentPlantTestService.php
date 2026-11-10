<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)
namespace App\Services\Fsm;

use App\Http\Requests\Request;
use App\Models\Fsm\TreatmentPlantPerformanceTest;
use App\Models\Fsm\TreatmentPlantTest;
use Illuminate\Support\Collection;
use Illuminate\Session\SessionManager;
use DB;
use Carbon\Carbon;
use Auth;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use Yajra\DataTables\DataTables;

class TreatmentPlantTestService
{

    //    protected $session;
    //    protected $instance;

    /**
     * Constructs a new TreatmentPlantTest object.
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
     * Get all the All Employee Info.
     *
     *
     * @return EmployeeInfo[]|Collection
     */
    public function getAllTreatmentPlants($data)
    {

        // $temp_data = TreatmentPlantPerformanceTest::all();
        // $updated = $temp_data->isNotEmpty() ? $temp_data->max('updated_at') : null;
        // $data = TreatmentPlantPerformanceTest::where('updated_at', $updated)->first();


        $treatmentPlant =  TreatmentPlantTest::select('*')->whereNull('deleted_at');
        return Datatables::of($treatmentPlant)
            ->filter(function ($query) use ($data) {

                if ($data['treatment_plant_name']) {
                    $query->whereHas('treatmentplants', function ($subQuery) use ($data) {
                        $subQuery->where('name', $data['treatment_plant_name']);
                    });
                }

                if ($data['temperature']) {

                    $query->where('temperature', 'ILIKE', trim($data['temperature']) . '%');
                }
                if ($data['date']) {
                    $query->where('date', 'ILIKE', '%' . $data['date'] . '%');
                }
                if ($data['cod']) {
                    $query->where('cod', 'ILIKE', $data['cod'] . '%');
                }
                if (isset($data['bod_validation'])) {

                    $bodStandard =  TreatmentPlantPerformanceTest::latest('updated_at')->value('bod_standard'); // Fetching the bod_standard value from the table
                    if ($bodStandard !== null) { // Ensure bod_standard exists
                        if ($data['bod_validation'] == 'true') {
                            $query->where('bod', '<=', $bodStandard); // Use the fetched value
                        } elseif ($data['bod_validation'] == 'false') {
                            $query->where('bod', '>', $bodStandard); // Use the fetched value
                        }
                    }
                }
                if (isset($data['ecoli_validation'])) {
                    $ecoliStandard = TreatmentPlantPerformanceTest::latest('updated_at')->value('ecoli_standard'); // Fetching the bod_standard value from the table

                    if ($ecoliStandard !== null) { // Ensure bod_standard exists
                        if ($data['ecoli_validation'] == 'true') {
                            $query->where('ecoli', '<=', $ecoliStandard); // Use the fetched value
                        } elseif ($data['ecoli_validation'] == 'false') {
                            $query->where('ecoli', '>', $ecoliStandard); // Use the fetched value
                        }
                    }
                }
                if (isset($data['tss_validation'])) {
                    $tssStandard = TreatmentPlantPerformanceTest::latest('updated_at')->value('tss_standard'); // Fetching the bod_standard value from the table

                    if ($tssStandard !== null) { // Ensure bod_standard exists
                        if ($data['tss_validation'] == 'true') {
                            $query->where('tss', '<=', $tssStandard); // Use the fetched value
                        } elseif ($data['tss_validation'] == 'false') {
                            $query->where('tss', '>', $tssStandard); // Use the fetched value
                        }
                    }
                }
                if (isset($data['ph_validation'])) {
                    // Fetching ph_min and ph_max values from the table
                    $phMin =TreatmentPlantPerformanceTest::latest('updated_at')->value('ph_min');
                    $phMax = TreatmentPlantPerformanceTest::latest('updated_at')->value('ph_max');

                    if ($phMin !== null && $phMax !== null) { // Ensure ph_min and ph_max exist
                        if ($data['ph_validation'] == 'true') {
                            // Check if the user-entered value falls between ph_min and ph_max
                            $query->whereBetween('ph', [$phMin, $phMax]);
                        } elseif ($data['ph_validation'] == 'false') {
                            // If validation is 0, you might want to check if the value is outside the range
                            $query->where(function ($query) use ($phMin, $phMax) {
                                $query->where('ph', '<', $phMin)->orWhere('ph', '>', $phMax);
                            });
                        }
                    }
                }
            })
            ->addColumn('action', function ($model) {
                $content = \Form::open(['method' => 'DELETE', 'route' => ['treatment-plant-test.destroy', $model->id]]);

                if (Auth::user()->can('Edit Treatment Plant Efficiency Test')) {
                    $content .= '<a title="' . e(__('Edit')) . '" href="' . action("Fsm\TreatmentPlantTestController@edit", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-edit"></i></a> ';
                }
                if (Auth::user()->can('View Treatment Plant Efficiency Test')) {
                    $content .= '<a title="' . e(__('Detail')) . '" href="' . action("Fsm\TreatmentPlantTestController@show", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                }
                if (Auth::user()->can('View Treatment Plant Efficiency Test History')) {
                    $content .= '<a title="' . e(__('History')) . '" href="' . action("Fsm\TreatmentPlantTestController@history", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-history"></i></a> ';
                }
                if (Auth::user()->can('Delete Treatment Plant Efficiency Test')) {
                    $content .= '<a href title="' . e(__('Delete')) . '" class="delete btn btn-danger btn-sm mb-1"><i class="fa fa-trash"></i></a> ';
                }
                $content .= \Form::close();
                return $content;
            })
            ->editColumn('treatment_plant_id', function ($model) {
                return $model->treatmentplants->name ?? '-';
            })

            ->make(true);
    }


    /**
     * Store or update a newly created resource in storage.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function storeTpt($data)
    {
        $treatmentPlant = new TreatmentPlantTest();
        $treatmentPlant->treatment_plant_id = $data['treatment_plant_id'] ? $data['treatment_plant_id'] : null;
        $treatmentPlant->date = $data['date'] ? $data['date'] : null;
        $treatmentPlant->temperature = $data['temperature'] ? $data['temperature'] : null;
        $treatmentPlant->ph = $data['ph'] ? $data['ph'] : null;
        $treatmentPlant->cod = $data['cod'] ? $data['cod'] : null;
        $treatmentPlant->bod = $data['bod'] ? $data['bod'] : null;
        $treatmentPlant->ecoli = $data['ecoli'] ? $data['ecoli'] : null;
        $treatmentPlant->tss = $data['tss'] ? $data['tss'] : null;
        $treatmentPlant->remarks = $data['remarks'] ? $data['remarks'] : null;
        $treatmentPlant->user_id = Auth::user()->id;
        $treatmentPlant->save();
        return redirect('fsm/treatment-plant-test')->with('success', __('Performance Efficiency Test created successfully.'));
    }

    public function updateTpt($request, $id)
    {
        $treatmentPlant = TreatmentPlantTest::find($id);
        if ($treatmentPlant) {
            $treatmentPlant->treatment_plant_id = $request->treatment_plant_id ?? null;
            $treatmentPlant->date = $request->date ?? null;
            $treatmentPlant->temperature = $request->temperature ?? null;
            $treatmentPlant->ph = $request->ph ?? null;
            $treatmentPlant->cod = $request->cod ?? null;
            $treatmentPlant->bod = $request->bod ?? null;
            $treatmentPlant->ecoli = $request->ecoli ?? null;
            $treatmentPlant->tss = $request->tss ?? null;
            $treatmentPlant->remarks = $request->remarks ?? null;

            $treatmentPlant->save();

            return redirect('fsm/treatment-plant-test')->with('success', __('Performance Efficiency Test updated successfully.'));
        } else {
            return redirect('fsm/treatment-plant-test')->with('Error', __('Failed to Updated Performance Efficiency Test'));
        }
    }

    /**
     * Download a listing of the specified resource from storage.
     *
     * @param array $data
     * @return null
     */
    public function exportData($data)
    {

        $searchData = $data['searchData'];

        $treatment_plant_name = $data['treatment_plant_name'];
        $date = $data['date'];
        $temperature = $data['temperature'];
        $cod = $data['cod'];

        $ecoli_validation = $data['ecoli_validation'];
        $bod_validation = $data['bod_validation'];
        $tss_validation = $data['tss_validation'];
        $ph_validation = $data['ph_validation'];
        $ecoliStandard =  TreatmentPlantPerformanceTest::value('ecoli_standard');
        $bodStandard = TreatmentPlantPerformanceTest::value('bod_standard');
        $phMin = TreatmentPlantPerformanceTest::value('ph_min');
        $phMax = TreatmentPlantPerformanceTest::value('ph_max');
        $tssStandard = TreatmentPlantPerformanceTest::value('tss_standard');

        $columns = [
            __('Treatment Plant'),
            __('Sample Date'),
            __('Temperature') . ' °C',
            __('pH'),
            __('COD (mg/l)'),
            __('BOD (mg/l)'),
            __('TSS (mg/l)'),
            __('Ecoli'),
            __('Remark'),
        ];

        $query = TreatmentPlantTest::select('treatment_plant_id', 'date', 'temperature', 'ph', 'cod', 'bod', 'tss', 'ecoli', 'remarks')
            ->whereNull('deleted_at');

        if (!empty($treatment_plant_name)) {
            $query->whereHas('treatmentplants', function ($subQuery) use ($treatment_plant_name) {
                $subQuery->where('name', $treatment_plant_name);
            });
        }
        if (!empty($date)) {
            $query->where('date', $date);
        }
        if (!empty($temperature)) {
            $query->where('temperature', $temperature);
        }
        if (!empty($cod)) {
            $query->where('cod', $cod);
        }

        if (!empty($ecoli_validation)) {
            if ($ecoli_validation == 'true') {
                $query->where('ecoli', '<=', $ecoliStandard);
            } else {
                $query->where('ecoli', '>', $ecoliStandard);
            }
        }


        if (!empty($bod_validation)) {
            if ($bod_validation == 'true') {
                $query->where('bod', '<=', $bodStandard);
            } else {
                $query->where('bod', '>', $bodStandard);
            }
        }



        if (!empty($tss_validation)) {
            if ($tss_validation == 'true') {
                $query->where('tss', '<=', $tssStandard);
            } else {
                $query->where('tss', '>', $tssStandard);
            }
        }



        if (!empty($ph_validation)) {
            if ($phMin !== null && $phMax !== null) {
                if ($data['ph_validation'] == 'true') {
                    $query->whereBetween('ph', [$phMin, $phMax]);
                } elseif ($data['ph_validation'] == 'false') {
                    $query->where(function ($query) use ($phMin, $phMax) {
                        $query->where('ph', '<', $phMin)->orWhere('ph', '>', $phMax);
                    });
                }
            }
        }
        $writer = WriterFactory::create(Type::CSV);

        try {
            $writer->openToBrowser('Performance Efficiency Test.csv');

            // Add header row
            $style = (new StyleBuilder())
                ->setFontBold()
                ->setFontSize(13)
                ->setBackgroundColor(Color::rgb(228, 228, 228))
                ->build();
            $writer->addRowWithStyle($columns, $style);

            // Process and write data
            $query->chunk(5000, function ($treatmentPlantTests) use ($writer) {
                foreach ($treatmentPlantTests as $test) {
                    $values = [
                        $test->treatmentplants->name ?? 'N/A', // Add default value if null
                        $test->date,
                        $test->temperature,
                        $test->ph,
                        $test->cod,
                        $test->bod,
                        $test->tss,
                        $test->ecoli,
                        $test->remarks,
                    ];
                    $writer->addRow($values);
                }
            });

            $writer->close();
        } catch (\Exception $e) {
            // Handle and log any errors
            \Log::error('Error generating CSV: ' . $e->getMessage());
            throw new \Exception('Failed to export data: ' . $e->getMessage());
        }
    }
}
