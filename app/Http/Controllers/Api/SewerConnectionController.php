<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuildingInfo\BuildingSurveyRequest;
use App\Http\Requests\Fsm\ContainmentSurveyRequest;
use App\Http\Requests\UtilityInfo\CreateSewerConnectionRequest;

use App\Models\UtilityInfo\SewerLine;
use App\Models\BuildingInfo\Building;
use App\Models\SewerConnection\SewerConnection;
use App\Models\BuildingInfo\BuildingSurvey;
use App\Models\BuildingInfo\WmsLink;
use App\Models\Fsm\ContainmentSurvey;
use Carbon\Carbon;

use DOMDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class SewerConnectionController extends Controller
{
    
    
    public function getSewerWms(){
        return $this->getWmsLink("sewers");
    }

    public function getBuildingCodes()
    {
        try {   
            $buildingCodes = Building::whereNull('deleted_at')->pluck('bin');
            
            if ($buildingCodes->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => __('No building codes found.')
                ], 404);
            }
            
            return response()->json([
                'status' => 200,
                'message' => __('Building codes fetched successfully.'),
                'data' => $buildingCodes,
               
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSewerCodes()
    {
        try {   
            $sewerCodes = SewerLine::whereNull('deleted_at')->pluck('code');
            
            if ($sewerCodes->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => __('No sewer codes found.')
                ], 404);
            }
            
            return response()->json([
                'status' => 200,
                'message' => __('Sewer codes fetched successfully.'),
                'data' => $sewerCodes,
               
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    function innerHTML(\DOMElement $element)
    {
        $doc = $element->ownerDocument;
        $html = '<Polygon>';

        foreach ($element->childNodes as $node) {
            $html .= $doc->saveHTML($node);
        }

        $html .= '</Polygon>';

        return $html;
    }
    public function saveSewerConnections(CreateSewerConnectionRequest $request){
      
        try {
            if ($request->validated()){
                $sewerConnection = SewerConnection::create($request->all());
            }
        } catch (\Throwable $th){
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
        return [
            'success' => true,
            'data' => '',
            'message' => __('Sewer Connection is uploaded successfully.')
        ];
    }
    /**
     * Get link of specified layer
     *
     * @return JsonResponse
     */
    public function getWmsLink($layer): JsonResponse
    {
        try {
            $wms = WmsLink::all()->where('name', '=', $layer)->pluck('link', 'name');
            return response()->json([
                'success' => true,
                'baseUrl' => config("constants.GEOSERVER_URL"),
                'data' => $wms,
                'bbox'    => config('constants.GEOSERVER_BBOX'),
                'width'   => config('constants.GEOSERVER_WIDTH'),
                'height'  => config('constants.GEOSERVER_HEIGHT'),
                'message' => __('WMS layer for:').$layer,
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * KML Validator
     *
     * @return JsonResponse
     */
   
}
