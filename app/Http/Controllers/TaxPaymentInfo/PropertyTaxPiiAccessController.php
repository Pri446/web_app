<?php

namespace App\Http\Controllers\TaxPaymentInfo;

use App\Http\Controllers\Controller;
use App\Services\OwnerPiiAuditService;
use App\Services\PropertyTaxPiiAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PropertyTaxPiiAccessController extends Controller
{
    private PropertyTaxPiiAccessService $access;
    private OwnerPiiAuditService $audit;

    public function __construct(
        PropertyTaxPiiAccessService $access,
        OwnerPiiAuditService $audit
    ) {
        $this->middleware('auth');
        $this->access = $access;
        $this->audit = $audit;
    }

    public function unlock(Request $request)
    {
        $user = $request->user();
        $allowed = $this->access->canUnlock($user)
            && $user->can('List Property Tax Collection');

        abort_unless($allowed, 403);
        $request->validate([
            'current_password' => ['required', 'string', 'max:255'],
        ]);

        if (empty($user->password)
            || !Hash::check((string) $request->input('current_password'), (string) $user->password)) {
            $this->access->lock();
            $this->audit->record(
                'property_tax_pii_password_failed',
                false,
                $user,
                null,
                ['surface' => 'property_tax_list'],
                'password_reentry'
            );

            return back()->withErrors([
                'property_tax_pii_password' => __('Password confirmation failed.'),
            ]);
        }

        $this->access->unlock($user);
        $request->session()->regenerate();
        $this->audit->record(
            'property_tax_pii_reveal_granted',
            true,
            $user,
            null,
            [
                'surface' => 'property_tax_list',
                'expires_in_minutes' => (int) config('pii.unlock_minutes', 5),
            ],
            'password_reentry'
        );

        return redirect()->route('tax-payment.index', [], 303)->with(
            'success',
            __('Property Tax owner PII is revealed for five minutes.')
        );
    }

    public function lock(Request $request)
    {
        $this->access->lock();
        $request->session()->regenerate();
        $this->audit->record(
            'property_tax_pii_locked',
            true,
            $request->user(),
            null,
            ['surface' => 'property_tax_list'],
            'session'
        );

        return back()->with('success', __('Property Tax owner PII is locked.'));
    }
}
