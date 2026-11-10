<?php
namespace Tests\Unit;
use App\Services\Fsm\ApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
class ServiceProviderChainRegressionTest extends TestCase
{
    protected function setUp():void {
        parent::setUp();DB::statement("ATTACH DATABASE ':memory:' AS fsm");
        DB::statement('CREATE TABLE fsm.service_provider_sequence(id INTEGER PRIMARY KEY,service_provider_id INTEGER,current_sequence BOOLEAN,sequence_order INTEGER,desludging_vehicle_size INTEGER)');
        DB::table('fsm.service_provider_sequence')->insert([
            ['id'=>1,'service_provider_id'=>10,'current_sequence'=>true,'sequence_order'=>2,'desludging_vehicle_size'=>5000],
            ['id'=>2,'service_provider_id'=>20,'current_sequence'=>true,'sequence_order'=>1,'desludging_vehicle_size'=>5000],
            ['id'=>3,'service_provider_id'=>30,'current_sequence'=>true,'sequence_order'=>1,'desludging_vehicle_size'=>6000],
        ]);
    }
    public function test_explicit_capacity_wins_and_selection_is_deterministic():void {
        $this->app->instance('request',Request::create('/','GET',['capacity'=>6000]));
        $service=(new \ReflectionClass(ApplicationService::class))->newInstanceWithoutConstructor();
        $this->assertSame(20,$service->getNextServiceProviderId(5000));
        $this->assertSame(30,$service->getNextServiceProviderId());
        $this->assertSame(20,$service->getNextServiceProviderId(5000));
        $this->assertSame(3,DB::table('fsm.service_provider_sequence')->where('current_sequence',true)->count());
    }
}
