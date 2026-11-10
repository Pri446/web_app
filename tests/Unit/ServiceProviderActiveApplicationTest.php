<?php
namespace Tests\Unit;
use App\Models\Fsm\Application;
use App\Services\Fsm\ServiceProviderService;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesIsolatedAuthDatabase;
use Tests\TestCase;
class ServiceProviderActiveApplicationTest extends TestCase
{
    use UsesIsolatedAuthDatabase;
    protected function setUp(): void
    {
        parent::setUp();$this->setUpIsolatedAuthDatabase();
        DB::statement("ATTACH DATABASE ':memory:' AS fsm");
        DB::statement('CREATE TABLE fsm.applications (id INTEGER PRIMARY KEY, service_provider_id INTEGER, emptying_status INTEGER, trip_count INTEGER, deleted_at TEXT)');
        DB::statement('CREATE TABLE fsm.emptyings (id INTEGER PRIMARY KEY, application_id INTEGER, trip_no INTEGER, deleted_at TEXT)');
        Application::addGlobalScope('isolated-test-relations',function($query){$query->setEagerLoads([]);});
    }
    protected function tearDown(): void {Application::clearBootedModels();parent::tearDown();}
    /** @dataProvider statuses */
    public function test_inactivation_checks_status_and_actual_completed_trips(int $status,int $required,int $completed,bool $active): void
    {
        DB::table('fsm.applications')->insert(['id'=>1,'service_provider_id'=>5,'emptying_status'=>$status,'trip_count'=>$required]);
        DB::table('fsm.emptyings')->insert(['id'=>1,'application_id'=>1,'trip_no'=>$completed]);
        $service=app(ServiceProviderService::class);
        $this->assertSame($active,$service->hasActiveApplications(5));
        $this->assertFalse($service->hasActiveApplications(6));
    }
    public static function statuses():array
    {
        return [[0,1,0,true],[1,1,1,true],[2,2,1,true],[2,2,2,false],[2,0,1,false]];
    }
}
