<?php
namespace Tests\Unit;
use App\Models\User;
use App\Services\Fsm\ApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;
class ApplicationPiiIntegrationQueryTest extends TestCase {
 /** @dataProvider operationalRoles */
 public function test_pii_selection_retains_trip_count_and_organisation_scope(string $role, ?int $providerId, ?int $plantId, int $expectedId):void {
  $user=Mockery::mock(User::class)->makePartial();
  $user->forceFill(['id'=>100,'service_provider_id'=>$providerId,'treatment_plant_id'=>$plantId]);
  $user->shouldReceive('hasRole')->andReturnUsing(fn($name)=>$name===$role);
  $user->shouldReceive('hasAnyRole')->andReturnUsing(fn($roles)=>in_array($role,(array)$roles,true));
  Auth::shouldReceive('user')->andReturn($user);
  $service=Mockery::mock(ApplicationService::class)->makePartial();
  $query=$service->getAllApplications(Request::create('/fsm/application/data'));
  $this->assertContains('applications.trip_count',$query->getQuery()->columns);
  $this->assertContains('applications.applicant_name',$query->getQuery()->columns);
  foreach(['applications.*','applications.customer_name','applications.customer_gender','applications.customer_contact'] as $column){$this->assertNotContains($column,$query->getQuery()->columns);}
  $this->assertSame([],$query->getEagerLoads());
  $this->assertContains($expectedId,$query->getBindings());
  $this->assertStringContainsString($role==='Treatment Plant - Admin'?'treatment_plant_id':'service_provider_id',$query->toSql());
 }
 public static function operationalRoles():array{return [ ['Service Provider - Admin',5,null,5],['Service Provider - Emptying Operator',null,null,-1],['Service Provider - Help Desk',null,null,-1],['Treatment Plant - Admin',null,7,7] ];}
}
