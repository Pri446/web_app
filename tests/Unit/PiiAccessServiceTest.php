<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\PiiAccessService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PiiAccessServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(function () {
            return true;
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_unlock_is_limited_to_the_same_user(): void
    {
        config(['pii.unlock_minutes' => 5]);

        $service = app(PiiAccessService::class);
        $firstUser = $this->userWithId(101);
        $secondUser = $this->userWithId(202);

        $service->unlock($firstUser);

        $this->assertTrue($service->isUnlocked($firstUser));
        $this->assertFalse($service->isUnlocked($secondUser));
    }

    public function test_unlock_expires_after_configured_duration(): void
    {
        config(['pii.unlock_minutes' => 5]);
        Carbon::setTestNow(Carbon::parse('2026-09-07 10:00:00'));

        $service = app(PiiAccessService::class);
        $user = $this->userWithId(101);
        $service->unlock($user);

        Carbon::setTestNow(Carbon::parse('2026-09-07 10:06:00'));

        $this->assertFalse($service->isUnlocked($user));
        $this->assertSame(0, $service->secondsRemaining($user));
    }

    public function test_lock_revokes_access_immediately(): void
    {
        $service = app(PiiAccessService::class);
        $user = $this->userWithId(101);
        $service->unlock($user);

        $service->lock();

        $this->assertFalse($service->isUnlocked($user));
    }

    public function test_unlock_can_be_limited_to_one_building(): void
    {
        $service = app(PiiAccessService::class);
        $user = $this->userWithId(101);

        $service->unlock(
            $user,
            'authenticated_session',
            ['view', 'edit'],
            'building-one'
        );

        $this->assertTrue(
            $service->isUnlocked($user, 'edit', 'building-one')
        );
        $this->assertSame(
            'authenticated_session',
            $service->authenticationMethod($user, 'edit', 'building-one')
        );
        $this->assertFalse(
            $service->isUnlocked($user, 'edit', 'building-two')
        );
    }

    public function test_unlock_respects_the_granted_scope(): void
    {
        $service = app(PiiAccessService::class);
        $user = $this->userWithId(101);

        $service->unlock(
            $user,
            'authenticated_session',
            ['view'],
            'building-one'
        );

        $this->assertTrue(
            $service->isUnlocked($user, 'view', 'building-one')
        );
        $this->assertFalse(
            $service->isUnlocked($user, 'edit', 'building-one')
        );
    }

    public function test_all_owner_grant_can_apply_to_any_building(): void
    {
        $service = app(PiiAccessService::class);
        $user = $this->userWithId(101);

        $service->unlock(
            $user,
            'authenticated_session',
            ['list', 'view', 'edit'],
            PiiAccessService::ALL_OWNERS_RESOURCE_ID
        );

        $this->assertTrue(
            $service->isUnlocked(
                $user,
                'list',
                PiiAccessService::ALL_OWNERS_RESOURCE_ID
            )
        );
        $this->assertTrue(
            $service->isUnlocked($user, 'edit', 'building-one')
        );
        $this->assertTrue(
            $service->isUnlocked($user, 'view', 'building-two')
        );
    }

    public function test_all_owner_view_grant_does_not_allow_edit_scope(): void
    {
        $service = app(PiiAccessService::class);
        $user = $this->userWithId(101);

        $service->unlock(
            $user,
            'authenticated_session',
            ['list', 'view'],
            PiiAccessService::ALL_OWNERS_RESOURCE_ID
        );

        $this->assertTrue(
            $service->isUnlocked($user, 'view', 'building-one')
        );
        $this->assertFalse(
            $service->isUnlocked($user, 'edit', 'building-one')
        );
    }

    private function userWithId(int $id): User
    {
        $user = new User();
        $user->setRawAttributes(['id' => $id]);

        return $user;
    }
}
