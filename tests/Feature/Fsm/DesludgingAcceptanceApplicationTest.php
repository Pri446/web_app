<?php

namespace Tests\Feature\Fsm;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Fsm\Concerns\CreatesDesludgingTestData;
use Tests\TestCase;

class DesludgingAcceptanceApplicationTest extends TestCase
{
    use CreatesDesludgingTestData;
    use DatabaseTransactions;

    /** @test */
    public function accepting_and_submitting_creates_an_application(): void
    {
        $provider = $this->createDesludgingProvider(7);
        $property = $this->createScheduledProperty($provider);
        $user = $this->createDesludgingUser();
        $providerUser = $this->createDesludgingUser();
        $providerUser->service_provider_id = $provider->id;
        $providerUser->save();

        $acceptResponse = $this->actingAs($user)
            ->withoutMiddleware()
            ->post('/fsm/desludging-schedule/accept', [
                'bin' => $property['bin'],
                'containment_id' => $property['containmentId'],
                'road_code' => $property['roadCode'],
                'service_provider_id' => $provider->id,
                'next_emptying_date' => now()->addDay()->toDateString(),
            ]);

        $acceptResponse
            ->assertRedirect(route('application.create', [
                'action_type' => 'confirm',
            ]))
            ->assertSessionHas('schedule_accept', function ($data) use (
                $property,
                $provider
            ) {
                return $data['bin'] === $property['bin']
                    && $data['containment_id'] === $property['containmentId']
                    && (int) $data['service_provider_id'] === $provider->id;
            });

        $this->assertDatabaseMissing('fsm.applications', [
            'containment_id' => $property['containmentId'],
        ]);

        $createResponse = $this->actingAs($user)
            ->withoutMiddleware()
            ->post(
                route('application.store'),
                $this->confirmedApplicationPayload($property, $provider)
            );

        $createResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('application.index'));
        $this->assertDatabaseHas('fsm.applications', [
            'bin' => $property['bin'],
            'containment_id' => $property['containmentId'],
            'service_provider_id' => $provider->id,
            'emptying_status' => false,
        ]);
        $this->assertDatabaseHas('fsm.containments', [
            'id' => $property['containmentId'],
            'status' => 1,
        ]);
        $this->assertDatabaseMissing('fsm.desludging_schedule_temp', [
            'containment_id' => $property['containmentId'],
        ]);
        $applicationId = \Illuminate\Support\Facades\DB::table('fsm.applications')
            ->where('containment_id', $property['containmentId'])
            ->value('id');
        $this->assertDatabaseHas('public.notification', [
            'user_id' => $providerUser->id,
            'application_id' => $applicationId,
            'mode' => 'web',
            'status' => false,
        ]);
    }
}
