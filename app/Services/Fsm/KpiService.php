<?php

namespace App\Services\Fsm;

use App\Models\Fsm\KpiTarget;
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
use App\Models\Fsm\ServiceProvider;
use App\Models\Fsm\KeyPerformanceIndicator;
use App\Models\Fsm\Quarters;


class KpiService {

    protected $session;
    protected $instance;

    /**
     * Constructs a new Kpi object.
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
    public function getAllData($data)
    {

        $kpiTargets =  KpiTarget::select('*')->whereNull('deleted_at');
        return Datatables::of($kpiTargets)
            ->filter(function ($query) use ($data) {
               
                if ($data['year']){
                    $query->where('fsm.kpi_targets.year',$data['year']);
                }
                if ($data['indicator_id']){
                    $indicator_id = KeyPerformanceIndicator::where('indicator', $data['indicator_id'])
                    ->value('id');
                    $query->where('fsm.kpi_targets.indicator_id',$indicator_id);
                }
               
              
            })
            ->addColumn('action', function ($model) {
                $content = \Form::open(['method' => 'DELETE',

                'route' => ['kpi-targets.destroy', $model->id]]);

                if (Auth::user()->can('Edit KPI Target')) {
                    $content .= '<a title="' . e(__("Edit")) . '" href="' . action("Fsm\KpiTargetController@edit", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-edit"></i></a> ';
                }
                if (Auth::user()->can('View KPI Target')) {
                    $content .= '<a title="' . e(__("Detail")) . '" href="' . action("Fsm\KpiTargetController@show", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                }

                if (Auth::user()->can('View KPI Target History')) {
                    $content .= '<a title="' . e(__("History")) . '" href="' . action("Fsm\KpiTargetController@history", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-history"></i></a> ';
                }

                if (Auth::user()->can('Delete KPI Target')) {
                    $content .= '<a href="#" title="' . e(__("Delete")) . '"  class="delete btn btn-danger btn-sm mb-1"><i class="fa fa-trash"></i></a> ';
                }

                $content .= \Form::close();
                return $content;
            })

           
            ->editColumn('indicator_id',function ($model){
                return KeyPerformanceIndicator::where('id', $model->indicator_id)->first()->indicator;
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
    public function storeOrUpdate($id = null,$data)
    {
       
        if(is_null($id)){
            $kpi = new KpiTarget();
            $kpi->indicator_id = $data['indicator_id'] ? $data['indicator_id'] : null;
            $kpi->year = $data['year'] ? $data['year'] : null;
            $kpi->target = $data['target'] ? $data['target'] : null;
            $year = $kpi->year;
            $kpi->save();
            $this->quarterdata($year);

        }
        else{
            
            $kpi = KpiTarget::find($id);
            $kpi->indicator_id = $data['indicator_id'] ? $data['indicator_id'] : null;
            $kpi->year = $data['year'] ? $data['year'] : null;
            $year = $kpi->year;
            $kpi->target = $data['target'] ? $data['target'] : null;
            $kpi->save();
           $this->quarterdata($year);

           
        }
    }

    public function quarterdata($year)
    {
        $years = Quarters::pluck('year')->unique()->toArray();
 
        if (!in_array($year, $years)) {
                DB::insert("
                    INSERT INTO fsm.quarters (quartername, starttime, endtime, year)
                    SELECT
                        'Q' || EXTRACT(QUARTER FROM gs::date),
                        gs,
                        (gs + INTERVAL '3 months' - INTERVAL '1 second')::timestamp,
                        EXTRACT(YEAR FROM gs)
                    FROM
                        GENERATE_SERIES(
                            DATE_TRUNC('year', TO_DATE('{$year}-01-01', 'YYYY-MM-DD')),
                            DATE_TRUNC('year', TO_DATE('{$year}-01-01', 'YYYY-MM-DD')) + INTERVAL '1 year' - INTERVAL '1 day',
                            INTERVAL '3 months'
                        ) as gs
                ");
        }
        

    }


     /**
     * Download a listing of the specified resource from storage.
     *
     * @param array $data
     * @return null
     */
    public function download($data)
    {
       
        $indicator_id = $data['indicator_id'] ? $data['indicator_id'] : null;
        $year = $data['year'] ? $data['year'] : null;
      
        $columns = [
            __('ID'),
            __('Indicator'),
            __('Year'),
            __('Target (%)'),
        ];

        $query = KpiTarget::select('id', 'indicator_id', 'year', 'target')->whereNull('deleted_at');
    
        if (!empty($indicator_id)) {
            $indicator = KeyPerformanceIndicator::where('indicator', $indicator_id)
            ->value('id');
            $query->where('fsm.kpi_targets.indicator_id',$indicator);
        }
        if (!empty($year)) {
            $query->where('year', $year);
        }

        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();

        $writer = WriterFactory::create(Type::CSV);

        $writer->openToBrowser('KPI Targets.CSV')
            ->addRowWithStyle($columns, $style); // Top row of the CSV

        $query->chunk(5000, function ($watersamples) use ($writer) {
            foreach ($watersamples as $data) {
                $values = [];
                $values[] = $data->id;
                $values[] =KeyPerformanceIndicator::where('id',  $data->indicator_id)
                ->value('indicator');
                $values[] = $data->year;
                $values[] = $data->target;

                $writer->addRow($values);
            }
        });

        $writer->close();
    }
}   


