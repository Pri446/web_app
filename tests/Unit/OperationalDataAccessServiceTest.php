<?php
namespace Tests\Unit;

use App\Models\User;
use App\Services\Fsm\OperationalDataAccessService;
use Illuminate\Database\Query\Builder;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class OperationalDataAccessServiceTest extends TestCase
{
    private function userForRole($role, $provider, $plant): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill(['service_provider_id' => $provider, 'treatment_plant_id' => $plant]);
        $user->shouldReceive('hasAnyRole')->andReturnUsing(function ($roles) use ($role) {
            return in_array($role, (array) $roles, true);
        });
        $user->shouldReceive('hasRole')->andReturnUsing(function ($required) use ($role) {
            return $role === $required;
        });
        return $user;
    }

    /** @dataProvider recordCases */
    public function test_record_access($role, $provider, $plant, $recordProvider, $recordPlant, $allowed): void
    {
        $user = $this->userForRole($role, $provider, $plant);
        try {
            OperationalDataAccessService::authorizeRecord($recordProvider, $recordPlant, $user);
            $this->assertTrue($allowed, 'Foreign or unlinked record was permitted');
        } catch (HttpExceptionInterface $error) {
            $this->assertFalse($allowed, 'Own record was denied');
            $this->assertSame(403, $error->getStatusCode());
        }
    }

    public function recordCases(): array
    {
        return [
            'provider own' => ['Service Provider - Admin', 11, null, 11, 22, true],
            'provider foreign' => ['Service Provider - Admin', 11, null, 12, 22, false],
            'operator own' => ['Service Provider - Emptying Operator', 11, null, 11, null, true],
            'operator foreign' => ['Service Provider - Emptying Operator', 11, null, 12, null, false],
            'provider unlinked' => ['Service Provider - Admin', null, null, 11, null, false],
            'provider record unassigned' => ['Service Provider - Admin', 11, null, null, null, false],
            'plant own' => ['Treatment Plant - Admin', null, 22, 11, 22, true],
            'plant foreign' => ['Treatment Plant - Admin', null, 22, 11, 23, false],
            'plant record unassigned' => ['Treatment Plant - Admin', null, 22, 11, null, false],
            'plant unlinked' => ['Treatment Plant - Admin', null, null, 11, 22, false],
            'municipality uses action permissions' => ['Municipality - Help Desk', null, null, 11, 22, true],
        ];
    }

    /** @dataProvider scopeCases */
    public function test_query_scoping($role, $provider, $plant, $column, $value): void
    {
        $user = $this->userForRole($role, $provider, $plant);
        $query = Mockery::mock(Builder::class);
        if ($column !== null) {
            $query->shouldReceive('where')->once()->with($column, $value)->andReturnSelf();
        } else {
            $query->shouldNotReceive('where');
        }
        $this->assertSame($query, OperationalDataAccessService::scopeQuery($query, 'e.service_provider_id', 'e.treatment_plant_id', $user));
    }

    public function scopeCases(): array
    {
        return [
            ['Service Provider - Admin', 11, null, 'e.service_provider_id', 11],
            ['Service Provider - Emptying Operator', 11, null, 'e.service_provider_id', 11],
            ['Service Provider - Admin', null, null, 'e.service_provider_id', -1],
            ['Treatment Plant - Admin', null, 22, 'e.treatment_plant_id', 22],
            ['Treatment Plant - Admin', null, null, 'e.treatment_plant_id', -1],
            ['Municipality - Help Desk', null, null, null, null],
        ];
    }
}