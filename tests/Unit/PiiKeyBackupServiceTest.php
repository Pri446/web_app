<?php

namespace Tests\Unit;

use App\Contracts\PiiKeyProvider;
use App\Models\User;
use App\Services\PiiKeyBackupService;
use Mockery;
use Tests\TestCase;

class PiiKeyBackupServiceTest extends TestCase
{
    public function test_super_admin_can_back_up_the_key(): void
    {
        $service = $this->serviceWithKey(str_repeat('a', 32));
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasAnyRole')
            ->once()
            ->with(['Super Admin', 'Municipality - IT Admin'])
            ->andReturn(true);

        $this->assertTrue($service->canBackup($user));
    }

    public function test_user_without_an_approved_role_cannot_back_up_the_key(): void
    {
        $service = $this->serviceWithKey(str_repeat('b', 32));
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasAnyRole')
            ->once()
            ->with(['Super Admin', 'Municipality - IT Admin'])
            ->andReturn(false);

        $this->assertFalse($service->canBackup($user));
    }

    public function test_raw_backup_is_base64_of_the_active_key_bytes(): void
    {
        config(['pii.active_key_version' => 'v7']);
        $rawKey = random_bytes(32);
        $provider = Mockery::mock(PiiKeyProvider::class);
        $provider->shouldReceive('key')
            ->once()
            ->with('v7')
            ->andReturn($rawKey);

        $service = new PiiKeyBackupService($provider);

        $this->assertSame(
            base64_encode($rawKey),
            $service->rawBase64Contents()
        );
    }

    private function serviceWithKey(string $rawKey): PiiKeyBackupService
    {
        $provider = Mockery::mock(PiiKeyProvider::class);
        $provider->shouldReceive('key')->zeroOrMoreTimes()->andReturn($rawKey);

        return new PiiKeyBackupService($provider);
    }
}
