<?php
namespace Tests\Unit;
use App\Http\Controllers\GISProxyController;
use App\Http\Controllers\GISExportController;
use App\Http\Controllers\GISPrintController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
class MobileWmsProxyTest extends TestCase {
 protected function setUp():void { parent::setUp(); config(['services.geoserver.url'=>'https://gis.example.test/geoserver','services.geoserver.workspace'=>'imis','services.geoserver.auth_key'=>'test-key','services.mapfish.print_url'=>'https://print.example.test/print']); Http::fake(['*'=>Http::response('fixture',200,['Content-Type'=>'image/png'])]); }
 private function req(array $params=[],bool $allowed=true):Request { $r=Request::create('/gis/wms','GET',$params); $r->setUserResolver(fn()=>new class($allowed){private $allowed;function __construct($allowed){$this->allowed=$allowed;}function can($permission){return $this->allowed;}}); return $r; }
 public function test_allowed_wms_uses_configured_backend_and_key():void { $r=app(GISProxyController::class)->wms($this->req(['SERVICE'=>'WMS','REQUEST'=>'GetMap','LAYERS'=>'imis:buildings_layer','authkey'=>'untrusted'])); $this->assertSame('no-store, private',$r->headers->get('Cache-Control')); Http::assertSent(fn($r)=>str_starts_with($r->url(),'https://gis.example.test/geoserver/wms')&&$r['authkey']==='test-key'); }
 /** @dataProvider deniedWms */
 public function test_disallowed_wms_is_blocked(array $params,bool $permission,int $status):void {try{app(GISProxyController::class)->wms($this->req($params,$permission));$this->fail('Expected rejection');}catch(HttpException $e){$this->assertSame($status,$e->getStatusCode());}Http::assertNothingSent();}
 public static function deniedWms():array{return [ [['SERVICE'=>'WFS','REQUEST'=>'GetMap'],true,400], [['SERVICE'=>'WMS','REQUEST'=>'GetCapabilities'],true,403], [['SERVICE'=>'WMS','REQUEST'=>'GetMap','LAYERS'=>'other:buildings_layer'],true,403], [['SERVICE'=>'WMS','REQUEST'=>'GetMap','LAYERS'=>'imis:unlisted'],true,403], [['SERVICE'=>'WMS','REQUEST'=>'GetMap','LAYERS'=>'imis:buildings_layer'],false,403] ];}
 public function test_mobile_route_requires_sanctum():void {$route=app('router')->getRoutes()->getByName('mobile.gis.wms');$this->assertNotNull($route);$this->assertContains('auth:sanctum',$route->gatherMiddleware());$this->assertStringEndsWith('/api/gis/wms',route('mobile.gis.wms'));}
}
