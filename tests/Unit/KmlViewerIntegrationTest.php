<?php
namespace Tests\Unit;
use App\Exports\BuildingsKmlExport;
use App\Exports\BuildingsListKmlExport;
use App\Exports\SummaryInfoMultiSheetKmlExport;
use App\Http\Controllers\MapsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;
class KmlViewerIntegrationTest extends TestCase
{
    private function controller(): MapsController { return Mockery::mock(MapsController::class)->makePartial(); }
    public function test_named_and_legacy_geometries_preserve_names_and_intersection_results(): void
    {
        $controller=$this->controller();
        $controller->shouldReceive('checkGeometryType')->with('POLYGON ((0 0,1 0,0 0))')->once()->andReturn('ST_Polygon');
        $controller->shouldReceive('checkGeometryType')->with('POINT (1 2)')->once()->andReturn('ST_Point');
        DB::shouldReceive('select')->with(Mockery::type('string'),['POLYGON ((0 0,1 0,0 0))'])->once()->andReturn([(object)['id'=>1]]);
        DB::shouldReceive('select')->with(Mockery::type('string'),['POINT (1 2)'])->once()->andReturn([]);
        $result=$controller->checkGeometry(Request::create('/','POST',['geometries'=>[
            ['name'=>'Named area','wkt'=>'POLYGON ((0 0,1 0,0 0))'],'POINT (1 2)',
        ]]))->getData(true);
        $this->assertSame('Named area',$result['details'][0]['name']);
        $this->assertTrue($result['details'][0]['intersects']);
        $this->assertSame('Unnamed Feature',$result['details'][1]['name']);
        $this->assertFalse($result['details'][1]['intersects']);
    }
    public function test_missing_and_unsupported_geometry_do_not_query_intersections(): void
    {
        $controller=$this->controller();
        $controller->shouldReceive('checkGeometryType')->with('MULTIPOINT ((1 2))')->andReturn('ST_MultiPoint');
        DB::shouldReceive('select')->never();
        $details=$controller->checkGeometry(Request::create('/','POST',['geometries'=>[
            ['name'=>'Missing','wkt'=>''],'MULTIPOINT ((1 2))',
        ]]))->getData(true)['details'];
        $this->assertSame('Missing geometry',$details[0]['message']);
        $this->assertFalse($details[1]['intersects']);
    }
    public function test_summary_limits_building_lookup_to_one_hundred_polygons(): void
    {
        $controller=$this->controller();
        $controller->shouldReceive('checkGeometryType')->andReturn('ST_POLYGON');
        $controller->shouldReceive('getKmlBufferPolygonBuildings')->once()
            ->with(Mockery::on(fn($request)=>count($request->bufferPolygonGeoms)===100))
            ->andReturn(['buildings'=>[]]);
        $items=array_fill(0,105,['wkt'=>'POLYGON ((0 0,1 0,0 0))']);
        $this->assertSame(['buildings'=>[]],$controller->getKmlSummaryInfo(Request::create('/','POST',['geometries'=>$items])));
    }
    public function test_summary_reports_when_no_polygons_are_supplied(): void
    {
        $controller=$this->controller();
        $controller->shouldReceive('checkGeometryType')->with('POINT (1 2)')->andReturn('ST_POINT');
        $result=$controller->getKmlSummaryInfo(Request::create('/','POST',['geometries'=>[['wkt'=>'POINT (1 2)']]]))->getData(true);
        $this->assertFalse($result['success']);
    }
    /** @dataProvider ownerValues */
    public function test_standard_kml_export_masks_owner_fields_without_decrypting(?string $value,?string $expected): void
    {
        $row=new class {public $bin='B001';public $owner_name;public $owner_gender;public $owner_contact;public $nid;public function __get($key){return null;}};
        foreach(['owner_name','owner_gender','owner_contact','nid'] as $field){$row->$field=$value;}
        $export=new BuildingsListKmlExport([['bin'=>'B001','polygon_name'=>'North'],['bin'=>'B001','polygon_name'=>'South']]);
        $mapped=$export->map($row);$headings=$export->headings();
        $this->assertSame('North',$mapped[0]);
        foreach(['Owner Name','Owner Gender','Owner Contact','Owner NID'] as $heading){$this->assertSame($expected,$mapped[array_search($heading,$headings,true)]);}
    }
    public static function ownerValues():array{return [['Sensitive owner','********'],['enc:v1:ciphertext','********'],['',''],[null,null]];}
    public function test_building_summary_total_row_renders_and_escapes_polygon_names(): void
    {
        $builder=Mockery::mock();
        $builder->shouldReceive('leftJoin','whereIn','whereNull','where','groupBy','orderBy','selectRaw')->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn(collect([]));
        DB::shouldReceive('table')->with('building_info.buildings as b')->once()->andReturn($builder);
        $html=(new BuildingsKmlExport([['bin'=>'B001','polygon_name'=>'<script>name</script>']]))->view()->render();
        $this->assertStringContainsString('Total',$html);
        $this->assertStringContainsString('&lt;script&gt;name&lt;/script&gt;',$html);
        $this->assertStringNotContainsString('<script>name</script>',$html);
    }
    public function test_workbook_contains_named_polygon_area_and_five_sheets(): void
    {
        DB::shouldReceive('selectOne')->once()->andReturn((object)['area_km2'=>1.25]);
        DB::shouldReceive('select')->once()->with(Mockery::type('string'),['POLYGON ((0 0,1 0,0 0))',0])->andReturn([(object)['bin'=>'B001']]);
        $sheets=(new SummaryInfoMultiSheetKmlExport(['POLYGON ((0 0,1 0,0 0))'],0,['North']))->sheets();
        $this->assertCount(5,$sheets);
        $html=$sheets[4]->view()->render();
        $this->assertStringContainsString('North',$html);
        $this->assertStringContainsString('1.25',$html);
    }
}
