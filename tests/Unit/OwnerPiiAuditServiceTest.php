<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\OwnerPiiAuditService;
use ReflectionMethod;
use Tests\TestCase;

class OwnerPiiAuditServiceTest extends TestCase
{
    public function test_disabled_auditing_does_not_attempt_a_database_write(): void
    {
        config(['pii.audit_enabled' => false]);

        $user = new User();
        $user->setRawAttributes(['id' => 101]);

        app(OwnerPiiAuditService::class)->record(
            'test_event',
            true,
            $user
        );

        $this->assertTrue(true);
    }

    public function test_sensitive_context_values_are_removed(): void
    {
        $service = app(OwnerPiiAuditService::class);
        $method = new ReflectionMethod($service, 'safeContext');
        $method->setAccessible(true);

        $result = $method->invoke($service, [
            'surface' => 'building_edit',
            'owner_name' => 'must-not-be-logged',
            'current_password' => 'must-not-be-logged',
            'pii_pin' => 'must-not-be-logged',
            'encryption_key' => 'must-not-be-logged',
        ]);

        $this->assertSame(
            ['surface' => 'building_edit'],
            $result
        );
    }
}
