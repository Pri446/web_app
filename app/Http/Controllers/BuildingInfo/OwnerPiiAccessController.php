<?php

namespace App\Http\Controllers\BuildingInfo;

use App\Http\Controllers\Controller;
use App\Services\OwnerPiiAuditService;
use App\Services\PiiAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class OwnerPiiAccessController extends Controller
{
    private PiiAccessService $piiAccess;
    private OwnerPiiAuditService $audit;

    public function __construct(
        PiiAccessService $piiAccess,
        OwnerPiiAuditService $audit
    ) {
        $this->middleware('auth');
        $this->piiAccess = $piiAccess;
        $this->audit = $audit;
    }

    public function unlockList(Request $request)
    {
        $this->authorizePiiListUnlock($request);
        $user = $request->user();
        $password = (string) $request->input('current_password', '');
        $passwordHash = (string) ($user->password ?? '');

        if ($password === ''
            || $passwordHash === ''
            || !Hash::check($password, $passwordHash)) {
            $this->piiAccess->lock();
            $this->audit->record(
                'pii_password_confirmation_failed',
                false,
                $user,
                null,
                ['surface' => 'building_data_table'],
                'password_reentry'
            );

            return back()->withErrors([
                'current_password' => __('Password confirmation failed.'),
            ]);
        }

        $this->audit->record(
            'pii_password_confirmation_succeeded',
            true,
            $user,
            null,
            ['surface' => 'building_data_table'],
            'password_reentry'
        );

        $scopes = ['list', 'view'];
        $canEditOwnerPii = $user->can('Edit Building Structure');

        if ($canEditOwnerPii) {
            $scopes[] = 'edit';
        }

        $this->audit->record(
            'bulk_reveal_granted',
            true,
            $user,
            null,
            [
                'surface' => 'building_data_table',
                'expires_in_minutes' => (int) config('pii.unlock_minutes', 5),
                'edit_enabled' => $canEditOwnerPii,
            ],
            'password_reentry'
        );
        $request->session()->regenerate();
        $this->piiAccess->unlock(
            $user,
            'password_reentry',
            $scopes,
            PiiAccessService::ALL_OWNERS_RESOURCE_ID
        );

        return redirect()->route('buildings.index', [], 303)->with(
            'success',
            __('Owner PII is revealed across the building list and building pages for five minutes. This access has been audited.')
        );
    }

    public function lock(Request $request)
    {
        $this->piiAccess->lock();
        $request->session()->regenerate();
        $this->audit->record(
            'pii_locked',
            true,
            $request->user(),
            null,
            [],
            'session'
        );

        return back()->with('success', __('Owner PII has been locked.'));
    }

    private function authorizePiiListUnlock(Request $request): void
    {
        $user = $request->user();
        $allowed = $user
            && $user->can('Unlock Owner PII')
            && $user->can('View Owner PII')
            && $user->can('List Building Structures');

        if (!$allowed) {
            $this->audit->record(
                'bulk_reveal_denied',
                false,
                $user,
                null,
                ['surface' => 'building_data_table'],
                'authenticated_session'
            );
        }

        abort_unless($allowed, 403);
    }

}
