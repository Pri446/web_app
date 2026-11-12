<?php
// Last Modified Date: 14-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Services\SewerConnection;

use App\Models\BuildingInfo\BuildContain;
use App\Models\BuildingInfo\Building;
use App\Models\UtilityInfo\SewerLine;

use App\Models\SewerConnection\SewerConnection;
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

class SewerConnectionService {

    protected $session;
    protected $instance;

    /**
     * Constructs a new SewerConnection object.
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
     
        $sewercollectionData = SewerConnection::select('*');
        return Datatables::of($sewercollectionData)
                ->filter(function ($query) use ($data) {

                if ($data['sewer_code']) {
                    $query->where('sewer_code', 'ILIKE', '%' .  $data['sewer_code'] . '%');
                }

                if ($data['bin']) {
                    $query->whereRaw('LOWER(bin) LIKE ? ', [trim(strtolower($data['bin']))]);
                }

            })
            ->addColumn('action', function ($model) {
                
                $content = \Form::open(['method' => 'DELETE', 'route' => ['sewerconnection.destroy', $model->id]]);
                if (Auth::user()->can('Approve Sewer Connection')) {
                $content .= '<a  title="' . e(__("Approve sewer connection")) . '" data-toggle="modal" data-target="#exampleModalCenter" data-id="'.$model->bin.'" data-sewer-code="'.$model->sewer_code.'" class="btn btn-info btn-sm mb-1 mr-2 approve-button"><i class="fas fa-check"></i></a>';
                }
                if (Auth::user()->can('Preview Sewer Connection')) {
                $content .= '<a title="' . e(__("Preview Sewer Location")) . '" data-toggle="modal" data-target="#exampleModal" class="btn btn-info btn-sm mb-1 mr-1" data-id="'.$model->bin.'" data-sewer-code="'.$model->sewer_code.'"><i class="fas fa-eye"></i></a> ';
                }
                if (Auth::user()->can('Delete Sewer Connection')) {
                $content .= '<a href="#" title="' . e(__("Delete")) . '"  class="delete btn btn-danger btn-sm mb-1"><i class="fa fa-trash"></i></a> ';
                }
               $content .= \Form::close();
                return $content;
            })
           
            ->make(true);
    }

    public function approve($bin)
    {
        // Retrieve the sewer_code value from the request parameters
        $sewerCode = request('sewer');
        
        // Search for the Building and BuildContain with the given bin value
        $building = Building::where('bin', $bin)->first();
        $buildContain = BuildContain::where('bin', $bin)->first();
        
        // If the BuildContain with the given bin exists, soft delete it
        if ($buildContain) {
            $buildContain->delete();
        }
       
        // If the building with the given bin exists
        if ($building) {
            // Update the sanitation_system_id attribute to 1
            $building->sanitation_system_id = 1;
    
            // Update the sewer_code attribute
            $building->sewer_code = $sewerCode;
    
            // Save the changes to the building
            $building->save();
            
            // Soft delete the SewerConnection record if it exists
            $sewerConnection = SewerConnection::where('bin', $bin)->first();
            if ($sewerConnection) {
                $sewerConnection->delete();
            }
            
            // Return a JSON response indicating success
            return response()->json(['status' => 'success']);
            
        } else {
            // Return a JSON response indicating that the building with the given bin was not found
            return response()->json(['status' => 'error', 'message' => __('Building not found.')], 404);
        }
    }
    

    public function getGeom($bin)
    {
        
        $building = Building::where('bin', $bin)->first();

        // If the building with the given bin exists
        if ($building) {
            // Retrieve the geom value
            $geoms = $building->geom;
        
            // Execute the SQL query to convert the geom to WKT
            $wktResult = DB::select("SELECT ST_AsText('$geoms') AS wkt_geom");
        
            // Extract the WKT representation from the query result
            $geom = $wktResult[0]->wkt_geom;
           
            // Return the WKT representation in a JSON response
            return response()->json(['wkt_geom' => $geom]);
        } else {
            // Return a JSON response indicating that the building with the given bin was not found
            return response()->json(['status' => 'error', 'message' => __('Building not found.')], 404);
        }
    }

    public function getsewerGeom($sewer)
    {
        $sewerCode = SewerLine::where('code', $sewer)->first();
    
        // If the sewer line with the given code exists
        if ($sewerCode) {
            // Retrieve the geom value
            $geomsewer = $sewerCode->geom;
    
            // Execute the SQL query to convert the sewer geom to WKT
            $wktResult = DB::select("SELECT ST_AsText('$geomsewer') AS wkt_geom");
    
            // Extract the WKT representation from the query result
            $wktGeom = $wktResult[0]->wkt_geom;
    
            // Return the WKT representation in a JSON response
            return response()->json(['wkt_geom' => $wktGeom]);
        } else {
            // Return a JSON response indicating that the sewer line with the given code was not found
            return response()->json(['status' => 'error', 'message' => __('Sewer line not found.')], 404);
        }
    }
    
    /**
     * Store or update a newly created resource in storage.
     *
     * @param character $code
     * @param array $data
     * @return bool
     */
  
}
