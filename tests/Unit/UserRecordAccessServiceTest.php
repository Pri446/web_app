<?php
namespace Tests\Unit;
use App\Models\User;
use App\Http\Controllers\Auth\UserController;
use App\Services\Auth\UserService;
use App\Services\UserRecordAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
class UserRecordAccessServiceTest extends TestCase
{
    private function actor($id,$provider,$plant,bool $permission=true,bool $super=false): User {
        $actor=Mockery::mock(User::class)->makePartial();$actor->setRawAttributes(['id'=>$id,'service_provider_id'=>$provider,'treatment_plant_id'=>$plant]);
        $actor->shouldReceive('hasRole')->with('Super Admin')->andReturn($super);$actor->shouldReceive('can')->with('View User')->andReturn($permission);return $actor;
    }
    /** @dataProvider recordCases */
    public function test_permission_and_record_scope_are_enforced($actorId,$provider,$plant,$targetId,$targetProvider,$targetPlant,bool $permission,bool $super,bool $allowed):void {
        $actor=$this->actor($actorId,$provider,$plant,$permission,$super);$target=new User();$target->setRawAttributes(['id'=>$targetId,'service_provider_id'=>$targetProvider,'treatment_plant_id'=>$targetPlant]);
        try {UserRecordAccessService::authorizeView($target,$actor);$this->assertTrue($allowed,'Foreign or unauthorized user record was exposed');}
        catch(HttpException $e){$this->assertFalse($allowed,'Authorized user record was denied');$this->assertSame(403,$e->getStatusCode());}
    }
    public static function recordCases():array{return [
        [1,null,null,2,20,30,false,true,true],
        [1,null,null,1,null,null,true,false,true],
        [1,null,null,1,null,null,false,false,false],
        [1,20,null,2,20,null,true,false,true],
        [1,20,null,2,21,null,true,false,false],
        [1,null,30,2,null,30,true,false,true],
        [1,null,30,2,null,31,true,false,false],
        [1,null,null,2,null,null,true,false,false],
        [1,20,null,2,20,null,false,false,false],
        [1,null,30,2,null,30,false,false,false],
        [1,'20',null,2,20,null,true,false,true],
        [null,null,null,null,null,null,true,false,false],
    ];}
    public function test_missing_actor_is_denied():void {
        Auth::shouldReceive('user')->andReturn(null);$this->expectException(HttpException::class);UserRecordAccessService::authorizeView(new User());
    }
    /** @dataProvider controllerCases */
    public function test_user_controller_checks_scope_before_loading_user_details(bool $allowed):void {
        DB::statement('CREATE TABLE auth.users (id INTEGER PRIMARY KEY,service_provider_id INTEGER,treatment_plant_id INTEGER,deleted_at TEXT)');
        DB::table('auth.users')->insert(['id'=>2,'service_provider_id'=>$allowed?20:21]);
        Auth::shouldReceive('user')->andReturn($this->actor(1,20,null));$service=Mockery::mock(UserService::class);
        if($allowed){$service->shouldReceive('getUserRelatedData')->once()->with(2)->andThrow(new \RuntimeException('Authorized detail loading reached'));$this->expectExceptionMessage('Authorized detail loading reached');$this->expectException(\RuntimeException::class);}
        else{$service->shouldNotReceive('getUserRelatedData');$this->expectException(HttpException::class);}
        (new UserController($service))->show(2);
    }
    public static function controllerCases():array{return [[true],[false]];}
}
