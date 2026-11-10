<?php

namespace App\Services;

use App\Models\OwnerPiiAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class OwnerPiiAuditService
{
    private const FORBIDDEN_CONTEXT_KEYS = [
        'owner_name',
        'owner_gender',
        'owner_contact',
        'nid',
        // Application customer PII is never permitted in audit context.
        'customer_name',
        'customer_gender',
        'customer_contact',
        'current_password',
        'export_password',
        'password',
        'pin',
        'pii_pin',
        'key',
        'encryption_key',
        'ciphertext',
        'payload',
        'session',
        'token',
    ];

    public function record(
        string $event,
        bool $successful,
        ?User $user = null,
        ?string $buildingPublicId = null,
        array $context = [],
        ?string $authenticationMethod = null
    ): void {
        if (!(bool) config('pii.audit_enabled', true)) {
            return;
        }

        $request = app()->bound('request') ? request() : null;

        OwnerPiiAuditLog::create([
            'user_id' => $user ? $user->getKey() : null,
            'building_public_id' => $buildingPublicId,
            'event' => $event,
            'successful' => $successful,
            'authentication_method' => $authenticationMethod,
            'ip_address' => $request instanceof Request
                ? $request->ip()
                : null,
            'user_agent' => $request instanceof Request
                ? mb_substr((string) $request->userAgent(), 0, 1000)
                : null,
            'context' => $this->safeContext($context),
        ]);
    }

    private function safeContext(array $context): array
    {
        $safe = [];

        foreach ($context as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (in_array($normalizedKey, self::FORBIDDEN_CONTEXT_KEYS, true)) {
                continue;
            }

            if (is_null($value) || is_scalar($value)) {
                $safe[$normalizedKey] = is_string($value)
                    ? mb_substr($value, 0, 255)
                    : $value;
            }
        }

        return $safe;
    }
}
