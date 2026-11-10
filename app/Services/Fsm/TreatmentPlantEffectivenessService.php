<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Services\Fsm;

use App\Models\Fsm\TreatmentPlantEffectiveness;
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

class TreatmentPlantEffectivenessService {

//    protected $session;
//    protected $instance;

    /**
     * Constructs a new TreatmentPlantEffectiveness object.
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
     * @return datatable[]|Collection
     */
    public function getAllData($data)
    {
        if(Auth::user()->hasRole('Treatment Plant - Admin'))
        {
        $TreatmentPlantEffectiveness = TreatmentPlantEffectiveness::where('treatment_plant_id' , Auth::user()->treatment_plant_id);

        }
        else{
            $TreatmentPlantEffectiveness = TreatmentPlantEffectiveness::select('*')->whereNull('deleted_at');
        }
        return Datatables::of($TreatmentPlantEffectiveness)
            ->filter(function ($query) use ($data) {
                if ($data['year']) {
                    $query->where('year', trim($data['year']));
                }
            })
            ->addColumn('action', function ($model) {
                $content = \Form::open(['method' => 'DELETE', 'route' => ['treatment-plant-effectiveness.destroy', $model->id]]);
                if (Auth::user()->can('View Treatment Plant Efficiency Standard')) {
                    $content .= '<a title="' . e(__("Detail")) . '" href="' . action("Fsm\TreatmentPlantEffectivenessController@show", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                }
                 if (Auth::user()->can('Edit Treatment Plant Efficiency Standard')) {
                    $content .= '<a title="' . e(__("Edit")) . '" href="' . action("Fsm\TreatmentPlantEffectivenessController@edit", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-edit"></i></a> ';
                }

                if (Auth::user()->can('Delete Treatment Plant Efficiency Standard')) {
                    $content .= '<a title="' . e(__("Delete")) . '"  class="delete btn btn-danger btn-sm mb-1"><i class="fa fa-trash"></i></a> ';
                }
                $content .= \Form::close();
                return $content;
            })
            ->editColumn('treatment_plant_id',function ($model){
                return $model->treatmentplants->name??'-';
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

            $info = new TreatmentPlantEffectiveness();
            $info->treatment_plant_id = $data['treatment_plant_id'] ? $data['treatment_plant_id'] : null;
            $info->year = $data['year'] ? $data['year'] : null;
            $info->recovered_operational_cost = $data['recovered_operational_cost'] ? $data['recovered_operational_cost'] : null;
            $info->treated_fecal_sludge = $data['treated_fecal_sludge'] ? $data['treated_fecal_sludge'] : null;
            $info->water_contamination_compliance = $data['water_contamination_compliance'] ? $data['water_contamination_compliance'] : null;
            $info->tp_effectiveness = $data['tp_effectiveness'] ? $data['tp_effectiveness'] : null;
            $info->save();
        }
        else{

            $info = TreatmentPlantEffectiveness::find($id);
            $info->treatment_plant_id = $data['treatment_plant_id'] ? $data['treatment_plant_id'] : null;
            $info->year = $data['year'] ? $data['year'] : null;
            $info->recovered_operational_cost = $data['recovered_operational_cost'] ? $data['recovered_operational_cost'] : null;
            $info->treated_fecal_sludge = $data['treated_fecal_sludge'] ? $data['treated_fecal_sludge'] : null;
            $info->water_contamination_compliance = $data['water_contamination_compliance'] ? $data['water_contamination_compliance'] : null;
            $info->tp_effectiveness = $data['tp_effectiveness'] ? $data['tp_effectiveness'] : null;
            $info->save();

        }
    }

    /**
     * Download a listing of the specified resource from storage.
     *
     * @param array $data
     * @return null
     */


}
