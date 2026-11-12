<?php
// Last Modified Date: 14-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Services\UtilityInfo;

use App\Models\UtilityInfo\Drain;
use App\Models\Fsm\TreatmentPlant;
use Illuminate\Support\Collection;
use Illuminate\Session\SessionManager;
use DB;
use Carbon\Carbon;
use Auth;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Yajra\DataTables\DataTables;

class DrainService {

    protected $session;
    protected $instance;

    /**
     * Constructs a new Drain object.
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
     * Get all list of resource.
     *
     *
     * @return array[]|Collection
     */
    public function getAllData($data)
    {
        $drainData = Drain::select('*');
        return Datatables::of($drainData)
                ->filter(function ($query) use ($data) {
                if ($data['code']) {
                    $query->where('code', 'ILIKE', '%' .  $data['code'] . '%');
                }

                if ($data['cover_type']) {
                    $query->whereRaw('LOWER(cover_type) LIKE ? ', [trim(strtolower($data['cover_type']))]);
                }

            })
            ->addColumn('action', function ($model) {
                $content = \Form::open(['method' => 'DELETE', 'route' => ['drains.destroy', $model->code]]);

                if (Auth::user()->can('Edit Drain')) {
                    $content .= '<a title="' . e(__("Edit")) . '" href="' . action("UtilityInfo\DrainController@edit", [$model->code]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-edit"></i></a> ';
                }

                if (Auth::user()->can('View Drain')) {
                    $content .= '<a title="' . e(__("Detail")) . '" href="' . action("UtilityInfo\DrainController@show", [$model->code]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                }

                if (Auth::user()->can('View Drain History')) {
                    $content .= '<a title="' . e(__("History")) . '" href="' . action("UtilityInfo\DrainController@history", [$model->code]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-history"></i></a> ';
                }

                if (Auth::user()->can('Delete Drain')) {
                    $content .= '<a href="#" title="' . e(__("Delete")) . '"  class="delete btn btn-danger btn-sm mb-1"><i class="fa fa-trash"></i></a> ';
                }

                if (Auth::user()->can('View Drain On Map')) {
                    $content .= '<a title="' . e(__("Map")) . '" href="' . action("MapsController@index", ['layer' => 'drains_layer', 'field' => 'code', 'val' => $model->code]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-map-marker"></i></a> ';
                }

                $content .= \Form::close();
                return $content;
            })
            ->editColumn('treatment_plant_id', function ($model) {
                $treatmentPlant = TreatmentPlant::select('name')
                    ->where('id', $model->treatment_plant_id)
                    ->first();
                if ($treatmentPlant) {
                    return $treatmentPlant->name;
                }
                return null; // or any default value you prefer if treatment plant not found
            })
            ->make(true);
    }
    /**
     * Store or update a newly created resource in storage.
     *
     * @param character $code
     * @param array $data
     * @return bool
     */
    public function storeOrUpdate($code = null,$data)
    {
        if(empty($code)){
            $drainTemp = DB::select("SELECT ST_AsText(geom) AS geom FROM drain_temp");
            $geom = ($drainTemp[0]->geom);

            $maxcode = Drain::withTrashed()->max('code');
            $maxcode = str_replace('D', '', $maxcode);
            $drain = new Drain();
            $drain->code = 'D' . sprintf('%04d', $maxcode + 1);
            $drain->user_id = Auth::id();
            $drain->road_code = $data['road_code'] ? $data['road_code'] : null;
            $drain->surface_type = $data['surface_type'] ? $data['surface_type'] : null;
            $drain->cover_type = $data['cover_type'] ? $data['cover_type'] : null;
            $drain->treatment_plant_id = $data['treatment_plant_id'] ? $data['treatment_plant_id'] : null;
            $drain->size = $data['size'] ? $data['size'] : null;
            $drain->length = $data['length'] ? $data['length'] : null;
            $drain->geom = $data['geom'] ? DB::raw("ST_Multi(ST_GeomFromText('" . $geom . "', 4326))") : null;
            $drain->save();
        }
        else{
            $drain = Drain::find($code);
            $drain->user_id = Auth::id();
            $drain->surface_type = $data['surface_type'] ? $data['surface_type'] : null;
            $drain->treatment_plant_id = $data['treatment_plant_id'] ? $data['treatment_plant_id'] : null;
            $drain->cover_type = $data['cover_type'] ? $data['cover_type'] : null;
            $drain->size = $data['size'] ? $data['size'] : null;
            $drain->length = $data['length'] ? $data['length'] : null;
          
            $drain->save();
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

        $searchData = $data['searchData'] ? $data['searchData'] : null;
        $code = $data['code'] ? $data['code'] : null;
        $cover_type = $data['cover_type'] ? $data['cover_type'] : null;
        $columns = ['Code','Road Code', 'Cover Type', 'Surface Type', 'Width (mm)', 'Length (m)','Treatment Plant'];
        $query = Drain::select('drains.code', 'drains.road_code', 'drains.cover_type', 'drains.surface_type', 'drains.size', 'drains.length', 'fsm.treatment_plants.name as Treatment Plant')
        ->leftJoin('fsm.treatment_plants', 'drains.treatment_plant_id', '=', 'fsm.treatment_plants.id')
        ->whereNull('drains.deleted_at');
        if (!empty($code)) {
            $query->where('code','ILIKE', '%'. $code .'%');

        }

        if (!empty($cover_type)) {
            $query->where('cover_type', $cover_type);
        }

        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();

        $writer = WriterFactory::create(Type::CSV);
        $writer->openToBrowser('Drain Network.csv')
            ->addRowWithStyle($columns, $style); //Top row of excel

        $query->chunk(5000, function ($drains) use ($writer) {
            $writer->addRows($drains->toArray());
        });

        $writer->close();

    }

}
