<?php

namespace Tests\Unit;

use App\Http\Controllers\BuildingInfo\OwnerPiiAccessController;
use App\Models\User;
use App\Services\PiiAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OwnerPiiPasswordReentryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['pii.audit_enabled' => false]);

        Gate::before(function () {
            return true;
        });
    }

    public function test_wrong_password_does_not_unlock_owner_pii(): void
    {
        $user = $this->userWithPassword('correct-password');
        $access = app(PiiAccessService::class);
        $access->unlock(
            $user,
            'password_reentry',
            ['list', 'view'],
            PiiAccessService::ALL_OWNERS_RESOURCE_ID
        );
        $request = $this->authenticatedRequest($user, 'wrong-password');

        app(OwnerPiiAccessController::class)->unlockList($request);

        $this->assertTrue(
            $request->session()->get('errors')->has('current_password')
        );
        $this->assertFalse(
            $access->isUnlocked(
                $user,
                'list',
                PiiAccessService::ALL_OWNERS_RESOURCE_ID
            )
        );
    }

    public function test_correct_password_unlocks_owner_pii(): void
    {
        $user = $this->userWithPassword('correct-password');
        $request = $this->authenticatedRequest($user, 'correct-password');

        $response = app(OwnerPiiAccessController::class)->unlockList($request);

        $this->assertSame(
            route('buildings.index'),
            $response->getTargetUrl()
        );

        $access = app(PiiAccessService::class);

        $this->assertTrue(
            $access->isUnlocked(
                $user,
                'list',
                PiiAccessService::ALL_OWNERS_RESOURCE_ID
            )
        );
        $this->assertSame(
            'password_reentry',
            $access->authenticationMethod(
                $user,
                'list',
                PiiAccessService::ALL_OWNERS_RESOURCE_ID
            )
        );
    }

    private function authenticatedRequest(
        User $user,
        string $password
    ): Request {
        $request = Request::create(
            '/building-info/owner-pii/unlock-list',
            'POST',
            ['current_password' => $password]
        );
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        $request->setLaravelSession($this->app['session']->driver());

        return $request;
    }

    private function userWithPassword(string $password): User
    {
        $user = new User();
        $user->setRawAttributes([
            'id' => 101,
            'password' => Hash::make($password),
        ]);

        return $user;
    }
}
