<?php
namespace Tests\Unit;
use App\Http\Controllers\BuildingInfo\BuildingController;
use App\Models\BuildingInfo\Building;
use App\Models\BuildingInfo\Owner;
use App\Models\Fsm\Containment;
use App\Models\User;
use App\Services\PiiAccessService;
use App\Services\OwnerPiiPresenter;
use App\Services\OwnerPiiAuditService;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;
class AnfBuildingPopupTest extends TestCase
{
    /** @dataProvider popupCases */
    public function test_popup_retains_public_ids_and_only_reveals_unlocked_pii(bool $unlocked, bool $hasContainment): void
    {
        $user=Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);
        $access=Mockery::mock(PiiAccessService::class);
        $access->shouldReceive('canUnlock')->andReturn(true);
        $access->shouldReceive('isUnlocked')->andReturn($unlocked);
        if($unlocked){$access->shouldReceive('authenticationMethod')->andReturn('password_reentry');}
        $this->app->instance(PiiAccessService::class,$access);
        $presenter=Mockery::mock(OwnerPiiPresenter::class);
        $audit=Mockery::mock(OwnerPiiAuditService::class);
        if($unlocked){
            $presenter->shouldReceive('presentPlaintext')->once()->andReturn(['owner_name'=>'Private owner','owner_gender'=>'Other']);
            $audit->shouldReceive('record')->once();
        }else{
            $presenter->shouldNotReceive('presentPlaintext');$audit->shouldNotReceive('record');
        }
        $this->app->instance(OwnerPiiPresenter::class,$presenter);
        $this->app->instance(OwnerPiiAuditService::class,$audit);
        $building=new Building();$building->setRawAttributes(['bin'=>'B00001','public_id'=>'11111111-1111-4111-8111-111111111111']);
        $building->setRelation('Owners',new Owner());
        $containment=new Containment();$containment->setRawAttributes(['id'=>'C001','public_id'=>'22222222-2222-4222-8222-222222222222']);
        $containment->setRelation('containmentType',(object)['type'=>'Septic tank']);
        $building->setRelation('containments',collect($hasContainment?[$containment]:[]));
        $result=$this->app->make(BuildingController::class)->listContainments($building);
        $this->assertSame('B00001',$result['building_bin']);
        $this->assertSame($building->public_id,$result['building_public_id']);
        $this->assertSame(!$unlocked,$result['owner_pii_locked']);
        $this->assertSame($unlocked?'Private owner':null,$result['owner_name']);
        if($hasContainment){
            $this->assertSame($containment->public_id,$result['containments'][0]['public_id']);
            $this->assertStringContainsString('data-containment-public-id="'.$containment->public_id.'"',$result['popContentsHtml']);
        }else{$this->assertSame([],$result['containments']);}
    }
    public static function popupCases(): array{return [[false,true],[true,true],[false,false]];}
}
