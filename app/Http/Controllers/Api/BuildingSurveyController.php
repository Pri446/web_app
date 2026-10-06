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

class BuildingSurveyController extends Controller
{
    public function getBuildingWms(){
        return $this->getWmsLink("buildings");
    }

    public function getContainmentWms(){
        return $this->getWmsLink("containments");
    }

    public function getWardWms(){
        return $this->getWmsLink("wards");
    }

    public function getRoadWms(){
        return $this->getWmsLink("roads");
    }
    
   

    public function getBuildingCodes()
    {
        try {   
            $buildingCodes = Building::whereNull('deleted_at')->pluck('bin');
            
            if ($buildingCodes->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => __('No building codes found.'),
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
                    'message' => __('No sewer codes found.'),
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

    public function saveBuilding(BuildingSurveyRequest $request){
        $buildingSurvey = null;
        try {
            if ($request->validated()){
                $buildingSurvey = BuildingSurvey::create($request->all());
                
                $buildingSurvey->user_id= Auth::id();
                $kml = $request->kml;

                if (!$kml) {
                    if ($buildingSurvey){
                        $buildingSurvey->forceDelete();
                    }
                    return response()->json([
                        'status' => false,
                        'message' => __('Kml file is required.'),
                    ], 500);
                }

                $kmlValidate = $this->validateKml($kml);

                if ($kmlValidate->getData()->status){
                    $xml = new DOMDocument();
                    $xml->load($kml);
                    $polygons = $xml->getElementsByTagName('Polygon');
                    if($polygons->length > 0) {
                        $isValidKml = DB::select("SELECT ST_IsValid(ST_GeomFromKML('".$this->innerHTML($polygons[0])."')) AS status");
                        if ($isValidKml[0]->status){
                            $filename = $buildingSurvey->temp_building_code . '_' . $buildingSurvey->collected_date . '.kml';
                            $storeKml = $kml->storeAs('/public/building-survey-kml', $filename, 'local');
                            if (!$storeKml){
                                if ($buildingSurvey){
                                    $buildingSurvey->forceDelete();
                                }
                                return response()->json([
                                    'status' => false,
                                    'message' => __('Kml file couldn\'t be stored.')
                                ], 500);
                            }
                            $buildingSurvey->kml = $filename;
                            $buildingSurvey->save();
                            return response()->json([
                                'status' => true,
                                'message' => __('Building Survey is uploaded successfully.')
                            ], 200);
                        } else {
                            if ($buildingSurvey){
                                $buildingSurvey->forceDelete();
                            }
                            return response()->json([
                                'status' => false,
                                'message' => __('Kml file is invalid. Polygons shouldn\'t self-intersect.'),
                            ], 500);
                        }
                    }

                } else {
                    if ($buildingSurvey){
                        $buildingSurvey->forceDelete();
                    }
                    return $kmlValidate;
                }

            }
        } catch (\Throwable $th){
            if ($buildingSurvey){
                $buildingSurvey->forceDelete();
            }
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function saveContainment(ContainmentSurveyRequest $request){
        try {
            if ($request->validated()){
                $contaimentSurvey = ContainmentSurvey::create($request->all());
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
            'message' => __('Service Providers'),
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
                'baseUrl' => route('gis.wms'),
                'data' => $wms,
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
    public function validateKml($kml): JsonResponse
    {
        Validator::extend('file_extension', function ($attribute, $value, $parameters, $validator) {
            if( !in_array( $value->getClientOriginalExtension(), $parameters ) ){
                return false;
            }
            else {
                return true;
            }
        }, "{{ __('Invalid file format. The file must be a .kml file.') }}" );

        $kmlValidator = Validator::make(["kml" => $kml],[
            "kml" => 'required|file|file_extension:kml'
        ]);
        if ($kmlValidator->fails()){
            $error_messages = [];
            foreach ($kmlValidator->errors()->getMessages() as $msgs) {
                array_push($error_messages, $msgs);
            }
            return response()->json([
                'status' => false,
                'message' => $error_messages
            ], 500);
        } else {
            return response()->json([
                'status' => true,
            ], 200);
        }
    }
}
