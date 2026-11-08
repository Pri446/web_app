<?php

use App\Http\Controllers\Api\ApiServiceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BuildingSurveyController;
use App\Http\Controllers\Api\SewerConnectionController;
use App\Http\Controllers\Api\EmptyingServiceController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\BuildingInfo\BuildingController;
use App\Http\Controllers\BuildingSearchController;
use App\Http\Controllers\Api\BuildingApiController;
use App\Http\Controllers\Api\RoadApiController;
use App\Http\Controllers\Api\DrainApiController;
use App\Http\Controllers\Api\WaterSupplyApiController;
use App\Http\Controllers\Api\SewerApiController;
use App\Http\Controllers\Api\SwmPaymentApiController;
use App\Http\Controllers\Api\TaxPaymentApiController;
use App\Http\Controllers\Api\WaterSupplyPaymentApiController;
use App\Http\Controllers\Fsm\ApplicationController;
use App\Http\Controllers\Api\SludgeCollectionController;
use App\Http\Controllers\Api\ContainmentsApiController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
    Route::get('/test-xss', function () {
    return response()->json([
        'message' => '<script>alert("XSS Attack!")</script>'
    ]);
})->middleware('security.headers');

/*
|
| Login--------------------------------------------------------------------
|
| An API route for logging in to the application.
|
*/
Route::get('get-Building-sewercode/{sewercode}',[BuildingSearchController::class,'getSewerCode']);
Route::post('/login', [AuthController::class, 'login']);

/*
|
| Protected API Routes ----------------------------------------------------
|
| All API routes in this group are protected by the auth:sanctum middleware
|
*/
Route::group([
    'name' => 'protected-api-routes',
    'middleware' => 'auth:sanctum'
],function (){
    /*
    |
    | Logout---------------------------------------------------------------
    |
    | An API route for logging out of the application.
    |
    */
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/footer', [AuthController::class, 'footer']);

    /*
    |
    | API Service Routes---------------------------------------------------
    |
    | All common API routes are grouped here.
    | /application/{id} : Get Application with ID of {id}
    | /containment/{application-id} : Get Containment(s) of application with
    |                                 ID of {application-id}
    | /service-providers : Get list of service providers
    |
    */
    Route::group(['name' => 'apiService'],function (){
        Route::get('/application/{id}',[ApiServiceController::class,'getApplicationDetails']);
        Route::get('/containment/{application_id}',[ApiServiceController::class,'getContainmentDetails']);
        Route::get('/service-providers',[ApiServiceController::class,'getServiceProviders']);
    });

    /*
    |
    | Emptying Routes---------------------------------------------------
    |
    | /assessed-applications : Get list of assessed applications
    | /treatment-plants : Get list of treatment plants
    | /vacutug-types : Get list of desludging vehicles
    | /drivers : Get list of drivers
    | /emptiers : Get list of emptiers
    | /save-emptying : Save the emptying data
    |
    */

    /*
    |
    | Building Survey Routes---------------------------------------------------
    |
    | /wms/buildings : Get WMS layer of buildings
    | /wms/containments : Get WMS layer of containments
    | /wms/wards : Get WMS layer of wards
    | /wms/roads' : Get WMS layer of roads
    | /save-building : Save the building survey
    | /save-containment : Save the containment survey
    |
    */
    Route::group(['name' => 'buildingSurvey'],function (){
        Route::group(['name' => 'wms','prefix' => 'wms'],function (){
            Route::get('/buildings',[BuildingSurveyController::class,'getBuildingWms']);
            Route::get('/containments',[BuildingSurveyController::class,'getContainmentWms']);
            Route::get('/wards',[BuildingSurveyController::class,'getWardWms']);
            Route::get('/roads',[BuildingSurveyController::class,'getRoadWms']);
        });
        Route::post('/save-building',[BuildingSurveyController::class,'saveBuilding']);
        Route::post('/save-containment',[BuildingSurveyController::class,'saveContainment']);
    });
    Route::group(['name' => 'sewerConnection'],function (){
        Route::get('/buildingcode',[BuildingSurveyController::class,'getBuildingCodes']);
        Route::get('/sewercode',[BuildingSurveyController::class,'getSewerCodes']);
        Route::group(['name' => 'wms','prefix' => 'wms'],function (){
            Route::get('/sewers',[SewerConnectionController::class,'getSewerWms']);

        });
        Route::post('/save-sewerconnection',[SewerConnectionController::class,'saveSewerConnections']);

    });
    Route::group([
        'name' => 'language',
        'prefix' => 'language',
        'namespace' => 'Language',
        'middleware' => 'auth'
    ], function () {

        Route::get('languages', [LanguageController::class, 'headerDropdown']);
        Route::get('translations/{lang_id}', [LanguageController::class, 'getTranslation']);
    });


    Route::prefix('revamp')->group(function() {
        Route::get('/collection', [BuildingSearchController::class,'getAll']);
        Route::get('get-Building-bin/{bin}',[BuildingSearchController::class,'getBuildingBin']);
        Route::get('get-Building-roadcode/{roadcode}',[BuildingSearchController::class,'getBuildingRoadcode']);
        Route::get('get-Building-housenumber/{housenumber}',[BuildingSearchController::class,'getBuildingHouseNumber']);
        Route::get('get-Building-sewercode/{sewercode}',[BuildingSearchController::class,'getSewerCode']);
        Route::get('get-Building-preconnected/{housenumber}',[BuildingSearchController::class,'getBinOfPreconnectedBuilding']);
        Route::get('get-Building-sanitation/{sanitation}',[BuildingSearchController::class,'getSanitationSystem']);
        Route::get('get-Building-housenumber/{housenumber}',[BuildingSearchController::class,'getBuildingHouseNumber']);

        });

        Route::group(['name' => 'building'], function () {
        Route::get('/buildings/form-fields', [BuildingApiController::class, 'getAddBuildingFormFields']);
        Route::post('/buildings/save', [BuildingApiController::class, 'storeBuildingDataMobile']);
        Route::get('/map/building-by-point', [BuildingSurveyController::class, 'locateBuildingByPoint']);
    });
   // Write endpoints — force JSON on validation/auth/throttle failures
    Route::middleware([
        'auth:sanctum',
        'force.json',
        'security.headers',
        'permission:Access Building Data API',
        'throttle:building-write',
    ])->group(function () {
        Route::post('/storeBuilding', [BuildingApiController::class, 'store']);
        Route::post('/storeBuildings', [BuildingApiController::class, 'store']);
        Route::put('/updateBuilding/{bin}', [BuildingApiController::class, 'update']);
    });

    
    Route::middleware([
    'force.json',
    'auth:sanctum',
    'security.headers',
    'permission:Access Road Connection Data Update API',
    'throttle:building-write',
    ])->group(function () {
        Route::post('/storeRoad', [RoadApiController::class, 'store']);
        Route::put('/updateRoad/{code}', [RoadApiController::class, 'update']);
        Route::get('/roads/export', [RoadApiController::class, 'export']);
    });

    Route::middleware([
    'force.json',
    'auth:sanctum',
    'security.headers',
    'permission:Access Drain Connection Data Update API',
    'throttle:building-write',
    ])->group(function () {
        Route::post('/storeDrain', [DrainApiController::class, 'store']);
        Route::put('/updateDrain/{code}', [DrainApiController::class, 'update']);
        Route::get('/drains/export', [DrainApiController::class, 'export']);
    });

    Route::middleware([
    'force.json',
    'auth:sanctum',
    'security.headers',
    'permission:Access Water Supply Connection Data Update API',
    'throttle:building-write',
    ])->group(function () {
        Route::post('/storeWaterSupply', [WaterSupplyApiController::class, 'store']);
        Route::put('/updateWaterSupply/{code}', [WaterSupplyApiController::class, 'update']);
        Route::get('/waterSupplies/export', [WaterSupplyApiController::class, 'export']);
    });

    Route::middleware([
    'force.json',
    'auth:sanctum',
    'security.headers',
    'permission:Access Sewer Connection Data Update API',
    'throttle:building-write',
    ])->group(function () {
        Route::post('/storeSewer', [SewerApiController::class, 'store']);
        Route::put('/updateSewer/{code}', [SewerApiController::class, 'update']);
        Route::get('/sewers/export', [SewerApiController::class, 'export']);
    });

    Route::middleware([
    'force.json',
    'auth:sanctum',
    'security.headers',
    'permission:Access Tax Payment Data Update API',
    'throttle:building-write',
    ])->group(function () {
        Route::post('/storeTaxPayment', [TaxPaymentApiController::class, 'store']);
        Route::get('/taxPayments/export', [TaxPaymentApiController::class, 'export']);
        Route::get('/taxPayments/exportunmatched', [TaxPaymentApiController::class, 'exportunmatched']);
        Route::put('/taxPayments/update/{tax_code}', [TaxPaymentApiController::class, 'update']);
    });

    Route::middleware([
    'force.json',
    'auth:sanctum',
    'security.headers',
    'permission:Access Water Payment Data Update API',
    'throttle:building-write',
    ])->group(function () {
        Route::post('/storeWaterSupplyPayment', [WaterSupplyPaymentApiController::class, 'store']);
        Route::get('/waterSupplyPayments/export', [WaterSupplyPaymentApiController::class, 'export']);
        Route::get('/waterSupplyPayments/exportunmatched', [WaterSupplyPaymentApiController::class, 'exportunmatched']);
        Route::put('/waterSupplyPayments/update/{water_customer_id}', [WaterSupplyPaymentApiController::class, 'update']);
    });

    Route::middleware([
    'force.json',
    'auth:sanctum',
    'security.headers',
    'permission:Access Solid Waste Payment Data Update API',
    'throttle:building-write',
    ])->group(function () {
        Route::post('/storeSwmPayment', [SwmPaymentApiController::class, 'store']);
        Route::get('/swmPayments/export', [SwmPaymentApiController::class, 'export']);
        Route::get('/swmPayments/exportunmatched', [SwmPaymentApiController::class, 'exportunmatched']);
        Route::put('/swmPayments/update/{swm_customer_id}', [SwmPaymentApiController::class, 'update']);
    });

    Route::group(['name' => 'sludgecollection'], function () {
        Route::get('assessed-sludgeapplications', [SludgeCollectionController::class, 'getAssessedSludgeApplications']);
        Route::get('/sludgedisposal-form-fields/{application_id}', [SludgeCollectionController::class, 'getSludgeDisposalFormFields']);
        Route::post('/save-sludge', [SludgeCollectionController::class, 'store']);
    });

     Route::group(['name' => 'emptyingService'], function () {
        Route::get('/assessed-applications', [EmptyingServiceController::class, 'getAssessedApplications']);
        Route::get('/treatment-plants', [EmptyingServiceController::class, 'getTreatmentPlants']);
        Route::get('/vacutugs', [EmptyingServiceController::class, 'getVacutugs']);
        Route::get('/drivers', [EmptyingServiceController::class, 'getDrivers']);
        Route::get('/emptiers', [EmptyingServiceController::class, 'getEmptiers']);
        Route::post('/save-emptying', [EmptyingServiceController::class, 'store']);
        Route::get('/get-treatment-plant', [EmptyingServiceController::class, 'getTreatmentPlantDetails']);
        Route::get('/emptyingForm-fields/{application_id}', [EmptyingServiceController::class, 'getMultiTripFormFields']);
        Route::patch('applications/{id}/resolve-anf', [ApplicationController::class, 'resolveAnf'])->name('api.application.resolve-anf');
    });

    Route::group(['name' => 'containment'], function () {
        Route::get('/containments/form-fields', [ContainmentsApiController::class, 'getAddContainmentFormFields']);
        Route::post('/containments/save', [ContainmentsApiController::class, 'store']);
        Route::post('/addContainment/{bin}', [BuildingApiController::class, 'addContainment']);
    });
});
