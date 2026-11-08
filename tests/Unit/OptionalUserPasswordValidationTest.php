<?php

namespace Tests\Unit;

use App\Http\Requests\Fsm\HelpDeskRequest;
use App\Http\Requests\Fsm\ServiceProviderRequest;
use App\Http\Requests\Fsm\TreatmentPlantRequest;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class OptionalUserPasswordValidationTest extends TestCase
{
    /** @dataProvider passwordScenarios */
    public function test_password_validation_follows_the_create_user_option(
        string $requestClass,
        array $input,
        bool $shouldPass,
        bool $shouldIncludePassword,
        bool $uncompromised
    ): void {
        // Keep password breach checks deterministic and avoid external requests.
        $verifier = $this->createMock(UncompromisedVerifier::class);
        $verifier->method('verify')->willReturn($uncompromised);
        $this->app->instance(UncompromisedVerifier::class, $verifier);

        $request = $requestClass::create('/fsm/test', 'POST', $input);
        $request->setRouteResolver(function () use ($request) {
            return (new Route('POST', '/fsm/test', function () {}))->bind($request);
        });

        // Exercise the real password rules without unrelated database constraints.
        $validator = Validator::make($input, [
            'password' => $request->rules()['password'],
        ]);

        $this->assertSame($shouldPass, $validator->passes());
        if ($shouldPass) {
            $this->assertSame($shouldIncludePassword, array_key_exists('password', $validator->validated()));
        } else {
            $this->assertTrue($validator->errors()->has('password'));
        }
    }

    public static function passwordScenarios(): array
    {
        $strongPassword = 'Example-Strong-Password-73!';
        $scenarios = [
            'unchecked, no password' => [[], true, false, true],
            'unchecked, empty password' => [['password' => '', 'password_confirmation' => ''], true, false, true],
            'unchecked, null password' => [['password' => null, 'password_confirmation' => null], true, false, true],
            'unchecked, autofilled mismatch' => [['password' => $strongPassword, 'password_confirmation' => 'different'], true, false, true],
            'unchecked, stale weak password' => [['password' => 'weak', 'password_confirmation' => 'different'], true, false, true],
            'checked, missing password' => [['create_user' => 'on'], false, false, true],
            'checked, missing confirmation' => [['create_user' => 'on', 'password' => $strongPassword], false, false, true],
            'checked, mismatched confirmation' => [['create_user' => 'on', 'password' => $strongPassword, 'password_confirmation' => 'different'], false, false, true],
            'checked, weak password' => [['create_user' => 'on', 'password' => 'weak', 'password_confirmation' => 'weak'], false, false, true],
            'checked, compromised password' => [['create_user' => 'on', 'password' => $strongPassword, 'password_confirmation' => $strongPassword], false, false, false],
            'checked, matching strong password' => [['create_user' => 'on', 'password' => $strongPassword, 'password_confirmation' => $strongPassword], true, true, true],
        ];

        $cases = [];
        foreach ([HelpDeskRequest::class, ServiceProviderRequest::class, TreatmentPlantRequest::class] as $requestClass) {
            foreach ($scenarios as $name => $scenario) {
                $cases[$requestClass . ': ' . $name] = array_merge([$requestClass], $scenario);
            }
        }

        return $cases;
    }
}
