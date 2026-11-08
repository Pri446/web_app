<?php

namespace App\Http\Controllers\Fsm;

use App\Http\Controllers\Controller;
use App\Services\ApplicationPiiAccessService;
use App\Services\OwnerPiiAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApplicationPiiAccessController extends Controller
{
    private ApplicationPiiAccessService $access;
    private OwnerPiiAuditService $audit;

    public function __construct(
        ApplicationPiiAccessService $access,
        OwnerPiiAuditService $audit
    ) {
        $this->middleware('auth');
        $this->access = $access;
        $this->audit = $audit;
    }

    public function unlockList(Request $request)
    {
        $user = $request->user();
        $allowed = $this->access->canUnlock($user)
            && $user->can('List Applications');

        abort_unless($allowed, 403);
        $request->validate([
            'current_password' => ['required', 'string', 'max:255'],
        ]);

        if (!Hash::check(
            (string) $request->input('current_password'),
            (string) $user->password
        )) {
            $this->access->lock();
            $this->audit->record(
                'application_pii_password_failed',
                false,
                $user,
                null,
                ['surface' => 'application_data_table'],
                'password_reentry'
            );

            return back()->withErrors([
                'application_pii_password' => __('Password confirmation failed.'),
            ]);
        }

        // This single step-up authentication is intentionally shared by the
        // Application list and Add Application owner lookup. The browser does
        // not decide whether PII is unlocked: every DataTable/BIN request must
        // still pass ApplicationPiiAccessService::isUnlocked() on the server.
        $scopes = ['list', 'view'];

        // Only users who may create an Application receive the owner_lookup
        // scope. This prevents a list-only user from calling the BIN endpoint
        // directly to retrieve building owner PII.
        if ($user->can('Add Application')) {
            $scopes[] = 'owner_lookup';
        }

        if ($user->can('Edit Application')) {
            $scopes[] = 'edit';
        }

        $this->access->unlock(
            $user,
            'password_reentry',
            $scopes,
            ApplicationPiiAccessService::ALL_APPLICATIONS_RESOURCE_ID
        );
        $request->session()->regenerate();
        $this->audit->record(
            'application_pii_bulk_reveal_granted',
            true,
            $user,
            null,
            [
                'surface' => 'application_data_table',
                'expires_in_minutes' => (int) config('pii.unlock_minutes', 5),
            ],
            'password_reentry'
        );

        return redirect()->route('application.index', [], 303)->with(
            'success',
            __('Application owner PII is revealed for five minutes.')
        );
    }

    public function lock(Request $request)
    {
        $this->access->lock();
        $request->session()->regenerate();
        $this->audit->record(
            'application_pii_locked',
            true,
            $request->user(),
            null,
            ['surface' => 'application_module'],
            'session'
        );

        return back()->with('success', __('Application owner PII is locked.'));
    }
}
