<?php

namespace Tests\Unit;

use App\Http\Controllers\TaxPaymentInfo\PropertyTaxPiiAccessController;
use App\Models\User;
use App\Services\OwnerPiiAuditService;
use App\Services\PropertyTaxPiiAccessService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class PropertyTaxPiiUnlockValidationTest extends TestCase
{
    public function test_empty_password_is_rejected_and_its_error_is_rendered(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => 903]);
        $user->shouldReceive('can')
            ->with('List Property Tax Collection')
            ->andReturn(true);

        $access = Mockery::mock(PropertyTaxPiiAccessService::class);
        $access->shouldReceive('canUnlock')
            ->once()
            ->with($user)
            ->andReturn(true);

        $controller = new PropertyTaxPiiAccessController(
            $access,
            Mockery::mock(OwnerPiiAuditService::class)
        );

        $request = Request::create('/tax-payment/owner-pii/unlock', 'POST', [
            'current_password' => '',
        ]);
        $request->setUserResolver(fn () => $user);

        try {
            $controller->unlock($request);
            $this->fail('An empty current password should fail validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'current_password',
                $exception->errors()
            );
        }

        // Guard the Blade side of the bug as well: validation is not useful to
        // the user unless the modal reopens and renders this exact error key.
        $view = file_get_contents(
            resource_path('views/taxpayment-info/index.blade.php')
        );

        $this->assertStringContainsString(
            "@error('current_password')",
            $view
        );
        $this->assertStringContainsString(
            '$errors->has(\'property_tax_pii_password\') || $errors->has(\'current_password\')',
            $view
        );
    }
}
