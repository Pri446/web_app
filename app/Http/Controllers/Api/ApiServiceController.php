<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fsm\Application;
use App\Models\Fsm\Containment;
use App\Models\Fsm\ServiceProvider;
use App\Services\Api\MobileApplicationPresenter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiServiceController extends Controller
{
    private MobileApplicationPresenter $mobileApplicationPresenter;

    public function __construct(
        MobileApplicationPresenter $mobileApplicationPresenter
    ) {
        $this->mobileApplicationPresenter = $mobileApplicationPresenter;
    }

    public function getApplicationDetails($application_id)
    {
        try {
            // Fetch the application with the building house number joined
            $application = Application::select('applications.*', 'buildings.house_number as building_house_number')
                ->join('building_info.buildings', function ($join) {
                    $join->on(DB::raw('CAST(applications.bin AS VARCHAR)'), '=', 'buildings.bin');
                })
                ->whereNull('applications.deleted_at')
                ->where('applications.id', $application_id)
                ->firstOrFail(); // Use firstOrFail to fetch the application with the house number
    
            // Add service provider's company name if related
            if ($application->service_provider) {
                $application->service_provider = $application->service_provider->company_name;
            }
    
            // Add geometry information as GeoJSON if the relationship exists
            $application->geometry = $application->buildings()
                ->select(DB::raw('public.ST_AsGeoJSON(geom) AS coordinates'))
                ->pluck('coordinates')
                ->first() ?? null;
    
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => __('No Application found for ID:') . $application_id ,
            ], 404); // Return 404 if application not found
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500); // Return error message for other exceptions
        }
    
        return response()->json([
            'success' => true,
            'data' => [
                'application' => $this->mobileApplicationPresenter
                    ->present($application)
            ],
            'message' => __('Application Details')
        ]);
    }
    

    public function getContainmentDetails($application_id){
        try {
            $application = Application::findOrFail($application_id);
            $containments = explode(" ",$application->containment_id);
            $data = [];
            foreach ($containments as $containment_id){
                array_push($data,Containment::findOrFail($containment_id));
            }
        } catch(ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => __('No containment/application found with application ID:') . $application_id,
            ], 500);
        } catch (\Throwable $th){
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
        return [
            'success' => true,
            'data' => $data,
            'message' => __('Containment details for application ID:') . $application_id
        ];
    }

    public function getServiceProviders(){
        try {
            $serviceProviders = ServiceProvider::Operational()->pluck('company_name','id')->toArray();
        } catch (\Throwable $th){
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
        return [
            'success' => true,
            'data' => $serviceProviders,
            'message' => __('Service Providers')
        ];
    }

}
