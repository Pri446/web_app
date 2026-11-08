<?php

namespace Tests\Feature\Fsm;

use App\Models\Fsm\ServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServiceProviderCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_provider_table_defaults_to_newest_first_and_allows_column_sorting(): void
    {
        $older = $this->createServiceProvider();
        $older->company_name = 'ZZZ sorting ' . $older->id;
        $older->created_at = now()->subDay();
        $older->save();
        $newer = $this->createServiceProvider();
        $newer->company_name = 'AAA sorting ' . $newer->id;
        $newer->created_at = now();
        $newer->save();

        $this->actingAs($this->testUser())->withoutMiddleware();
        $parameters = [
            'company_name' => 'sorting', 'ward' => '', 'email' => '',
            'contact_person' => '', 'company_location' => '', 'status' => '',
            'draw' => 1, 'start' => 0, 'length' => 10,
            'columns' => [['data' => 'company_name', 'name' => 'company_name', 'orderable' => 'true']],
        ];
        $this->getJson('/fsm/service-providers/data?' . http_build_query($parameters))
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);

        $parameters['order'] = [['column' => 0, 'dir' => 'desc']];
        $this->getJson('/fsm/service-providers/data?' . http_build_query($parameters))
            ->assertOk()
            ->assertJsonPath('data.0.id', $older->id)
            ->assertJsonPath('data.1.id', $newer->id);
    }

    /** @test */
    public function it_creates_a_service_provider(): void
    {
        Storage::fake('public');

        $fixture = $this->loadDataset(1);
        $payload = $this->uniquePayload($fixture['payload']);
        $payload['contract_document_pdf'] = UploadedFile::fake()->create(
            'service-provider-contract.pdf',
            100,
            'application/pdf'
        );

        $this->assertDatabaseMissing('fsm.service_providers', [
            'company_name' => $payload['company_name'],
        ]);

        $response = $this->withoutMiddleware()
            ->post('/fsm/service-providers', $payload);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success')
            ->assertRedirect('/fsm/service-providers');

        $serviceProvider = ServiceProvider::query()
            ->where('company_name', $payload['company_name'])
            ->firstOrFail();

        $this->assertSame(
            $fixture['expected']['service_area'],
            $serviceProvider->service_area
        );
        $this->assertDatabaseHas(
            'fsm.service_providers',
            array_merge($fixture['expected'], [
                'id' => $serviceProvider->id,
                'company_name' => $payload['company_name'],
                'email' => $payload['email'],
            ])
        );
        $this->assertEqualsCanonicalizing(
            [2, 4, 7],
            DB::table('fsm.service_provider_wards')
                ->where('service_provider_id', $serviceProvider->id)
                ->pluck('ward')
                ->map(function ($ward) {
                    return (int) $ward;
                })
                ->all()
        );
        Storage::disk('public')->assertExists(
            $serviceProvider->contract_document_pdf
        );
    }

    /** @test */
    public function it_displays_a_service_provider(): void
    {
        $serviceProvider = $this->createServiceProvider();

        $response = $this->actingAs($this->testUser())
            ->withoutMiddleware()
            ->get('/fsm/service-providers/' . $serviceProvider->id);

        $response
            ->assertOk()
            ->assertViewIs('fsm.service-providers.show')
            ->assertViewHas('serviceProvider', function ($viewModel) use (
                $serviceProvider
            ) {
                return $viewModel->is($serviceProvider);
            })
            ->assertSeeText($serviceProvider->company_name)
            ->assertSeeText($serviceProvider->email)
            ->assertSeeText($serviceProvider->contact_person);
    }

    /** @test */
    public function it_updates_a_service_provider(): void
    {
        $serviceProvider = $this->createServiceProvider();
        $uniqueValue = Str::lower(Str::random(12));

        $payload = [
            'company_name' => 'Updated Provider ' . $uniqueValue,
            'email' => $uniqueValue . '@example.test',
            'ward' => 5,
            'company_location' => 'Updated Address',
            'contact_person' => 'Updated Contact',
            'contact_gender' => 'Female',
            'contact_number' => '9811111111',
            'service_area' => [9, 3, 6, 3],
            'status' => 0,
        ];

        $response = $this->withoutMiddleware()
            ->patch(
                '/fsm/service-providers/' . $serviceProvider->id,
                $payload
            );

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success')
            ->assertRedirect('/fsm/service-providers');

        $this->assertDatabaseHas('fsm.service_providers', [
            'id' => $serviceProvider->id,
            'company_name' => $payload['company_name'],
            'email' => $payload['email'],
            'ward' => 5,
            'company_location' => 'Updated Address',
            'contact_person' => 'Updated Contact',
            'contact_gender' => 'Female',
            'contact_number' => '9811111111',
            'service_area' => '3,6,9',
            'status' => 0,
        ]);
        $this->assertEqualsCanonicalizing(
            [3, 6, 9],
            DB::table('fsm.service_provider_wards')
                ->where('service_provider_id', $serviceProvider->id)
                ->pluck('ward')
                ->map(function ($ward) {
                    return (int) $ward;
                })
                ->all()
        );
    }

    /** @test */
    public function it_soft_deletes_a_service_provider_without_dependencies(): void
    {
        $serviceProvider = $this->createServiceProvider();

        $response = $this->withoutMiddleware()
            ->delete('/fsm/service-providers/' . $serviceProvider->id);

        $response
            ->assertSessionHas('success')
            ->assertRedirect('/fsm/service-providers');

        $this->assertNull(ServiceProvider::find($serviceProvider->id));

        $deletedServiceProvider = ServiceProvider::withTrashed()
            ->findOrFail($serviceProvider->id);

        $this->assertNotNull($deletedServiceProvider->deleted_at);
    }

    /** @test */
    public function it_rejects_create_when_required_fields_are_missing(): void
    {
        $fixture = $this->loadDataset(2);

        $response = $this->from('/fsm/service-providers/create')
            ->withoutMiddleware()
            ->post('/fsm/service-providers', $fixture['payload']);

        $response
            ->assertRedirect('/fsm/service-providers/create')
            ->assertSessionHasErrors($fixture['validation_errors']);
    }

    private function createServiceProvider(): ServiceProvider
    {
        $uniqueValue = Str::lower(Str::random(12));
        $serviceProvider = new ServiceProvider();
        $serviceProvider->company_name = 'Existing Provider ' . $uniqueValue;
        $serviceProvider->email = $uniqueValue . '@example.test';
        $serviceProvider->ward = 1;
        $serviceProvider->company_location = 'Existing Address';
        $serviceProvider->contact_person = 'Existing Contact';
        $serviceProvider->contact_gender = 'Male';
        $serviceProvider->contact_number = '9800000000';
        $serviceProvider->service_area = '1,2';
        $serviceProvider->status = 1;
        $serviceProvider->save();

        return $serviceProvider;
    }

    private function testUser(): User
    {
        $uniqueValue = Str::lower(Str::random(12));
        $user = new User();
        $user->name = 'PHPUnit User';
        $user->email = $uniqueValue . '@example.test';
        $user->username = 'phpunit-' . $uniqueValue;
        $user->password = 'not-used';
        $user->user_type = 'Municipality';
        $user->status = 1;
        $user->save();

        return $user;
    }

    private function uniquePayload(array $payload): array
    {
        $uniqueValue = Str::lower(Str::random(12));
        $payload['company_name'] .= ' ' . $uniqueValue;
        $payload['email'] = $uniqueValue . '@example.test';

        return $payload;
    }

    private function loadDataset(int $id): array
    {
        $path = base_path(
            'tests/Fixtures/Fsm/ServiceProvider/service_providers.json'
        );
        $datasets = json_decode(
            file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($datasets as $dataset) {
            if ((int) $dataset['id'] === $id) {
                return $dataset;
            }
        }

        $this->fail("Dataset with id {$id} was not found at {$path}.");
    }
}
