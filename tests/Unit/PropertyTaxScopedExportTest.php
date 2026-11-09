<?php
namespace Tests\Unit;
use App\Http\Controllers\TaxPaymentInfo\PropertyTaxPiiExportController;
use App\Services\PropertyTaxPiiExportService;
use App\Services\PropertyTaxPiiAccessService;
use App\Services\OwnerPiiAuditService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;
class PropertyTaxScopedExportTest extends TestCase
{
    /** @dataProvider deniedCases */
    public function test_unsafe_export_requests_are_blocked(array $data,bool $admin,int $status):void {
        $user=Mockery::mock(User::class)->makePartial();$user->setRawAttributes(['id'=>1,'password'=>Hash::make('correct')]);
        $user->shouldReceive('can')->andReturn(true);$user->shouldReceive('hasAnyRole')->andReturn($admin);
        $request=Request::create('/','POST',$data+['property_tax_export_password'=>'correct']);$request->setUserResolver(fn()=>$user);
        $exporter=Mockery::mock(PropertyTaxPiiExportService::class);$exporter->shouldNotReceive('rowsForAllRecords','rowsForBins','parseCsvText');
        $audit=Mockery::mock(OwnerPiiAuditService::class);$audit->shouldReceive('record')->zeroOrMoreTimes();
        $controller=new PropertyTaxPiiExportController($exporter,app(PropertyTaxPiiAccessService::class),$audit);
        try{$controller->export($request);$this->fail('Unsafe request was accepted');}
        catch(ValidationException $e){$this->assertSame(422,$status);}
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame($status,$e->getStatusCode());}
    }
    public static function deniedCases():array{return [
        [['export_mode'=>'all','confirm_export_all'=>true],false,403],
        [['export_mode'=>'all'],true,422],
        [['export_mode'=>'bin_list'],true,422],
        [['export_mode'=>'invalid'],true,422],
    ];}
    public function test_selected_bins_export_uses_only_requested_bins():void {
        $user=Mockery::mock(User::class)->makePartial();$user->setRawAttributes(['id'=>2,'password'=>Hash::make('correct')]);$user->shouldReceive('can')->andReturn(true);
        $request=Request::create('/','POST',['export_mode'=>'bin_list','bin_csv'=>"bin\nB001\n",'property_tax_export_password'=>'correct']);$request->setUserResolver(fn()=>$user);
        $exporter=Mockery::mock(PropertyTaxPiiExportService::class);$exporter->shouldReceive('parseCsvText')->once()->with("bin\nB001\n")->andReturn(['B001']);
        $exporter->shouldReceive('headers')->once()->andReturn(['tax_code','bin']);
        $exporter->shouldReceive('rowsForBins')->once()->with(['B001'])->andReturn(['rows'=>[],'requested_count'=>1,'exported_count'=>0,'missing_count'=>1]);$exporter->shouldNotReceive('rowsForAllRecords');
        $audit=Mockery::mock(OwnerPiiAuditService::class);$audit->shouldReceive('record')->once();
        $response=(new PropertyTaxPiiExportController($exporter,app(PropertyTaxPiiAccessService::class),$audit))->export($request);
        $this->assertSame(200,$response->getStatusCode());$this->assertStringContainsString('no-store',$response->headers->get('Cache-Control'));
    }
}
