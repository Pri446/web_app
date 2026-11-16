<?php

namespace App\Http\Controllers;

use App\Services\ApplicationPiiAccessService;
use App\Services\OwnerPiiAuditService;
use App\Services\PiiAccessService;
use App\Services\PiiEncryptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MapsPiiAccessController extends Controller
{
    private PiiAccessService $piiAccess;
    private ApplicationPiiAccessService $appPiiAccess;
    private OwnerPiiAuditService $audit;
    private PiiEncryptionService $encryption;

    public function __construct(
        PiiAccessService $piiAccess,
        ApplicationPiiAccessService $appPiiAccess,
        OwnerPiiAuditService $audit,
        PiiEncryptionService $encryption
    ) {
        $this->middleware('auth');
        $this->piiAccess = $piiAccess;
        $this->appPiiAccess = $appPiiAccess;
        $this->audit = $audit;
        $this->encryption = $encryption;
    }

    public function unlock(Request $request)
    {
        $user = $request->user() ?? Auth::user();

        $canUnlockBuilding = $user && $this->piiAccess->canUnlock($user);
        $canUnlockApp = $user && $this->appPiiAccess->canUnlock($user);

        if (!$canUnlockBuilding && !$canUnlockApp) {
            abort(403, __('You are not authorized to unlock owner information.'));
        }

        $password = (string) $request->input('current_password', '');
        $passwordHash = (string) ($user->password ?? '');

        if ($password === '' || $passwordHash === '' || !Hash::check($password, $passwordHash)) {
            $this->piiAccess->lock();
            $this->appPiiAccess->lock();

            $this->audit->record(
                'map_pii_password_failed',
                false,
                $user,
                null,
                ['surface' => 'map_view'],
                'password_reentry'
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Password confirmation failed.'),
                    'errors' => ['current_password' => [__('Password confirmation failed.')]],
                ], 422);
            }

            return back()->withErrors([
                'map_pii_password' => __('Password confirmation failed.'),
            ]);
        }

        $scopes = ['list', 'view', 'map'];
        if ($user->can('Edit Building Structure')) {
            $scopes[] = 'edit';
        }

        if ($canUnlockBuilding) {
            $this->piiAccess->unlock(
                $user,
                'password_reentry',
                $scopes,
                PiiAccessService::ALL_OWNERS_RESOURCE_ID
            );
        }

        if ($canUnlockApp) {
            $appScopes = ['list', 'view', 'map'];
            if ($user->can('Edit Application')) {
                $appScopes[] = 'edit';
            }
            if ($user->can('Add Application')) {
                $appScopes[] = 'owner_lookup';
            }
            $this->appPiiAccess->unlock(
                $user,
                'password_reentry',
                $appScopes,
                ApplicationPiiAccessService::ALL_APPLICATIONS_RESOURCE_ID
            );
        }

        $request->session()->regenerate();

        $this->audit->record(
            'map_pii_reveal_granted',
            true,
            $user,
            null,
            [
                'surface' => 'map_view',
                'expires_in_minutes' => (int) config('pii.unlock_minutes', 5),
            ],
            'password_reentry'
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Owner information is revealed on the map for five minutes.'),
                'seconds_remaining' => (int) config('pii.unlock_minutes', 5) * 60,
            ]);
        }

        return redirect()->back(303)->with(
            'success',
            __('Owner information is revealed on the map for five minutes.')
        );
    }

    public function lock(Request $request)
    {
        $user = $request->user() ?? Auth::user();

        $this->piiAccess->lock();
        $this->appPiiAccess->lock();
        $request->session()->regenerate();

        if ($user) {
            $this->audit->record(
                'map_pii_locked',
                true,
                $user,
                null,
                ['surface' => 'map_view'],
                null
            );
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Owner information has been locked.'),
            ]);
        }

        return redirect()->back(303)->with(
            'success',
            __('Owner information has been locked.')
        );
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user() ?? Auth::user();
        $isUnlocked = $user && ($this->piiAccess->isUnlocked($user, 'view') || $this->appPiiAccess->isUnlocked($user, 'view'));
        $seconds = $isUnlocked ? max(
            $this->piiAccess->secondsRemaining($user, 'view'),
            $this->appPiiAccess->secondsRemaining($user, 'view')
        ) : 0;

        return response()->json([
            'unlocked' => $isUnlocked,
            'seconds_remaining' => $seconds,
        ]);
    }

    public function decrypt(Request $request): JsonResponse
    {
        $user = $request->user() ?? Auth::user();
        $isUnlocked = $user && ($this->piiAccess->isUnlocked($user, 'view') || $this->appPiiAccess->isUnlocked($user, 'view'));

        if (!$isUnlocked) {
            return response()->json(['decrypted' => []], 403);
        }

        $values = (array) $request->input('values', []);
        $decrypted = [];

        foreach ($values as $val) {
            if (is_string($val) && (str_starts_with($val, 'enc:v1:') || str_starts_with($val, 'enc:'))) {
                try {
                    $decrypted[$val] = $this->encryption->decrypt($val);
                } catch (\Throwable $e) {
                    $decrypted[$val] = '********';
                }
            }
        }

        return response()->json([
            'decrypted' => $decrypted,
        ]);
    }
}
