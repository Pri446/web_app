<?php

namespace Tests\Feature\Http\Concerns;

use App\Models\User;
use Illuminate\Support\Str;

trait InputBehaviorTestHelpers
{
    /**
     * Shared dataset loader according to Section 3.1 of Laravel Testing Roadmap.
     *
     * @param string $module
     * @param int $id
     * @return array
     * @throws \Exception
     */
    protected function loadDataset(string $module, int $id): array
    {
        $file = Str::snake($module) . '.json';
        $path = base_path("tests/Fixtures/{$module}/{$file}");

        if (!file_exists($path)) {
            throw new \Exception("Dataset fixture not found: {$path}");
        }

        $all = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        foreach ($all as $entry) {
            if (isset($entry['id']) && (int) $entry['id'] === $id) {
                return $entry;
            }
        }

        throw new \Exception("Dataset with id {$id} not found in {$path}");
    }

    /**
     * Reusable validation helper for empty / invalid field inputs according to Section 6 of Roadmap.
     *
     * @param User $user
     * @param string $storeRoute
     * @param string $table
     * @param array $fixture
     * @param array $routeParams
     * @return void
     */
    protected function assertRequiredFieldValidationFails(
        User $user,
        string $storeRoute,
        string $table,
        array $fixture,
        array $routeParams = []
    ): void {
        $this->assertArrayHasKey('validation_errors', $fixture);

        $response = $this->actingAs($user)
            ->post(route($storeRoute, $routeParams), $fixture['payload']);

        $response->assertSessionHasErrors($fixture['validation_errors']);

        if (isset($fixture['expected'])) {
            $this->assertDatabaseMissing($table, $fixture['expected']);
        }
    }
}
