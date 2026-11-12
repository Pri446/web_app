<?php
namespace Tests\Feature;
use App\Models\Notification;
use Tests\Concerns\UsesIsolatedAuthDatabase;
use Tests\TestCase;
class NotificationAccessTest extends TestCase
{
    use UsesIsolatedAuthDatabase;
    protected function setUp(): void { parent::setUp(); $this->setUpIsolatedAuthDatabase(); }
    private function notification(int $userId): Notification
    {
        return Notification::create(['user_id'=>$userId,'message'=>'Test notification','status'=>false,'mode'=>'web']);
    }
    public function test_only_the_current_users_notifications_are_returned(): void
    {
        $user=$this->isolatedUser(); $own=$this->notification($user->id); $foreign=$this->notification(999);
        $this->actingAs($user)->getJson('/notifications')->assertOk()->assertJsonPath('unreadCount',1)
            ->assertJsonCount(1,'notifications')->assertJsonPath('notifications.0.id',$own->id);
    }
    public function test_read_action_cannot_update_another_users_notification(): void
    {
        $user=$this->isolatedUser();$foreign=$this->notification(999);
        $this->actingAs($user)->postJson('/notification/read',['id'=>$foreign->id])->assertNotFound();
        $this->assertFalse((bool)$foreign->fresh()->status);
    }
    public function test_single_read_and_bulk_read_use_the_existing_status_column(): void
    {
        $user=$this->isolatedUser();$own=$this->notification($user->id);$foreign=$this->notification(999);
        $this->actingAs($user)->postJson('/notification/read',['id'=>$own->id])->assertOk();
        $this->assertTrue((bool)$own->fresh()->status);
        $this->actingAs($user)->postJson('/notification/read-all')->assertOk();
        $this->assertFalse((bool)$foreign->fresh()->status);
    }
    public function test_clear_all_preserves_other_users_notifications(): void
    {
        $user=$this->isolatedUser();$own=$this->notification($user->id);$foreign=$this->notification(999);
        $this->actingAs($user)->postJson('/notification/clear-all')->assertOk();
        $this->assertNull($own->fresh());$this->assertNotNull($foreign->fresh());
    }
    public function test_guests_cannot_fetch_notifications(): void
    {
        $this->getJson('/notifications')->assertUnauthorized();
    }
}
