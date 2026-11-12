<?php
namespace Tests\Unit;
use App\Http\Controllers\GISProxyController;
use App\Http\Controllers\GISExportController;
use App\Http\Controllers\GISPrintController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
class GisSecurityReleaseTest extends TestCase {
 protected function setUp():void { parent::setUp(); config(['services.geoserver.url'=>'https://gis.example.test/geoserver','services.geoserver.workspace'=>'imis','services.geoserver.auth_key'=>'test-key','services.mapfish.print_url'=>'https://print.example.test/print']); Http::fake(['*'=>Http::response('fixture',200,['Content-Type'=>'image/png'])]); }
 private function req(array $params=[],bool $allowed=true):Request { $r=Request::create('/gis/wms','GET',$params); $r->setUserResolver(fn()=>new class($allowed){private $allowed;function __construct($allowed){$this->allowed=$allowed;}function can($permission){return $this->allowed;}}); return $r; }
 public function test_routes_require_authentication():void {foreach(['gis.wms','gis.wfs','gis.export','gis.print'] as $name){$this->assertContains('auth',app('router')->getRoutes()->getByName($name)->gatherMiddleware());} $this->assertContains('auth:sanctum',app('router')->getRoutes()->getByName('mobile.gis.wms')->gatherMiddleware());}
 public function test_allowed_wms_uses_configured_backend_and_key():void { $r=app(GISProxyController::class)->wms($this->req(['SERVICE'=>'WMS','REQUEST'=>'GetMap','LAYERS'=>'imis:buildings_layer','authkey'=>'untrusted'])); $this->assertSame('no-store, private',$r->headers->get('Cache-Control')); Http::assertSent(fn($r)=>str_starts_with($r->url(),'https://gis.example.test/geoserver/wms')&&$r['authkey']==='test-key'); }
 /** @dataProvider deniedWms */
 public function test_disallowed_wms_is_blocked(array $params,bool $permission,int $status):void {try{app(GISProxyController::class)->wms($this->req($params,$permission));$this->fail('Expected rejection');}catch(HttpException $e){$this->assertSame($status,$e->getStatusCode());}Http::assertNothingSent();}
 public static function deniedWms():array{return [ [['SERVICE'=>'WFS','REQUEST'=>'GetMap'],true,400], [['SERVICE'=>'WMS','REQUEST'=>'GetCapabilities'],true,403], [['SERVICE'=>'WMS','REQUEST'=>'GetMap','LAYERS'=>'other:buildings_layer'],true,403], [['SERVICE'=>'WMS','REQUEST'=>'GetMap','LAYERS'=>'imis:unlisted'],true,403], [['SERVICE'=>'WMS','REQUEST'=>'GetMap','LAYERS'=>'imis:buildings_layer'],false,403] ];}
 public function test_wfs_rejects_missing_layer():void {$this->expectException(HttpException::class);app(GISProxyController::class)->wfs($this->req(['SERVICE'=>'WFS','REQUEST'=>'GetFeature']));}
 public function test_export_requires_layer_permission():void {$this->expectException(HttpException::class);app(GISExportController::class)->export($this->req(['layer'=>'buildings_layer'],false),'csv');}
 public function test_export_rejects_disallowed_format():void {$this->expectException(HttpException::class);app(GISExportController::class)->export($this->req(['layer'=>'buildings_layer']),'exe');}
 public function test_export_rejects_unsafe_filter():void {$this->expectException(HttpException::class);app(GISExportController::class)->export($this->req(['layer'=>'buildings_layer','filter'=>'1=1;--']),'csv');}
 public function test_authorized_export_uses_fixed_layer_and_backend():void {$r=app(GISExportController::class)->export($this->req(['layer'=>'buildings_layer']),'kml');$this->assertSame(200,$r->getStatusCode());$this->assertStringContainsString('Buildings.kml',$r->headers->get('Content-Disposition'));Http::assertSent(fn($r)=>$r['typeName']==='imis:buildings_layer'&&$r['authkey']==='test-key'&&$r['outputFormat']==='KML');}
 public function test_print_rejects_unauthorized_layer():void {$this->expectException(HttpException::class);app(GISPrintController::class)->print($this->req(['spec'=>['layers'=>[['type'=>'WMS','layers'=>['imis:buildings_layer']]]]],false));}
 public function test_print_uses_configured_backend():void {$r=app(GISPrintController::class)->print($this->req(['spec'=>['layers'=>[['type'=>'WMS','layers'=>['imis:buildings_layer'],'baseURL'=>'https://untrusted.example.test']]]]));$this->assertSame('application/pdf',$r->headers->get('Content-Type'));Http::assertSent(function($r){$spec=json_decode($r['spec'],true);return str_starts_with($r->url(),'https://print.example.test/print')&&$spec['layers'][0]['baseURL']==='https://gis.example.test/geoserver/wms?authkey=test-key';});}

 /** @dataProvider ambiguousWms */
 public function test_all_requested_layers_and_parameter_names_are_checked(array $params,int $status):void {
  try {app(GISProxyController::class)->wms($this->req($params));$this->fail('Unauthorized or ambiguous request was forwarded');}
  catch(HttpException $e){$this->assertSame($status,$e->getStatusCode());}
  Http::assertNothingSent();
 }
 public static function ambiguousWms():array{return [
  [['SERVICE'=>'WMS','REQUEST'=>'GetMap','LAYERS'=>'imis:unlisted','QUERY_LAYERS'=>'imis:buildings_layer'],403],
  [['SERVICE'=>'WMS','REQUEST'=>'GetMap'],400],
  [['SERVICE'=>'WMS','service'=>'WFS','REQUEST'=>'GetMap','LAYERS'=>'imis:buildings_layer'],400],
  [['Service'=>'WMS','Request'=>'GetMap','Layers'=>'imis:unlisted'],403],
  [['SERVICE'=>'WMS','REQUEST'=>'GetMap','LAYERS'=>'imis:buildings_layer','layers'=>'imis:unlisted'],400],
 ];}
 public function test_mixed_case_parameters_still_use_the_server_key():void {
  $response=app(GISProxyController::class)->wms($this->req(['Service'=>'WMS','Request'=>'GetMap','Layers'=>'imis:buildings_layer','AUTHKEY'=>'client-key']));
  $this->assertSame(200,$response->getStatusCode());
  Http::assertSent(fn($r)=>$r['authkey']==='test-key'&&!isset($r['AUTHKEY']));
 }
 public function test_print_does_not_log_the_backend_credential():void {
  \Illuminate\Support\Facades\Log::spy();
  app(GISPrintController::class)->print($this->req(['spec'=>['layers'=>[['type'=>'WMS','layers'=>['imis:buildings_layer']]]]]));
  \Illuminate\Support\Facades\Log::shouldNotHaveReceived('info');
 }
 public function test_print_backend_errors_do_not_log_response_bodies():void {
  \Illuminate\Support\Facades\Log::spy();Http::swap(new \Illuminate\Http\Client\Factory());
  Http::fake(['*'=>Http::response('private authkey=test-key',500)]);
  $response=app(GISPrintController::class)->print($this->req(['spec'=>['layers'=>[['type'=>'WMS','layers'=>['imis:buildings_layer']]]]]));
  $this->assertSame(502,$response->getStatusCode());
  \Illuminate\Support\Facades\Log::shouldHaveReceived('error')->once()->with('GIS print service returned an error',['status'=>500]);
 }
 /** @dataProvider mobileControllers */
 public function test_mobile_links_resolve_to_the_token_protected_proxy(string $class,string $layer):void {
  \Illuminate\Support\Facades\DB::statement("ATTACH DATABASE ':memory:' AS building_info");
  \Illuminate\Support\Facades\DB::statement('CREATE TABLE building_info.wms_links (id INTEGER PRIMARY KEY,name TEXT,link TEXT)');
  \Illuminate\Support\Facades\DB::table('building_info.wms_links')->insert(['name'=>$layer,'link'=>'wms?SERVICE=WMS']);
  $response=(new $class())->getWmsLink($layer);$data=$response->getData(true);
  $this->assertSame(200,$response->getStatusCode());
  $this->assertSame(route('mobile.gis.wms').'?SERVICE=WMS',$data['baseUrl'].$data['data'][$layer]);
  $this->assertStringNotContainsString('gis.example.test',$data['baseUrl']);
  $this->assertStringNotContainsString('test-key',json_encode($data));
 }
 public static function mobileControllers():array{return [
  [\App\Http\Controllers\Api\BuildingSurveyController::class,'buildings'],
  [\App\Http\Controllers\Api\SewerConnectionController::class,'sewers'],
 ];}
}
