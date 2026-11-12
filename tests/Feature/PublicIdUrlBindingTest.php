<?php

namespace Tests\Feature;

use App\Models\Concerns\HasPublicId;
use App\Models\BuildingInfo\Building;
use App\Models\Fsm\Containment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Tests\TestCase;

class PublicIdUrlBindingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Isolated test endpoints prove implicit model binding without depending
        // on application permissions or rendering large production views.
        Route::middleware(SubstituteBindings::class)->get(
            '/_test/buildings/{building}',
            function (Building $building) {
                return response()->json([
                    'bin' => $building->bin,
                    'public_id' => $building->public_id,
                ]);
            }
        )->name('test.buildings.binding');

        Route::middleware(SubstituteBindings::class)->get(
            '/_test/containments/{containment}',
            function (Containment $containment) {
                return response()->json([
                    'id' => $containment->id,
                    'public_id' => $containment->public_id,
                ]);
            }
        )->name('test.containments.binding');
    }

    public function test_building_public_ids_are_populated_unique_uuids(): void
    {
        $counts = DB::table('building_info.buildings')
            ->selectRaw('count(*) AS total, count(public_id) AS populated, count(distinct public_id) AS unique_ids')
            ->first();

        $this->assertGreaterThan(0, (int) $counts->total);
        $this->assertSame((int) $counts->total, (int) $counts->populated);
        $this->assertSame((int) $counts->total, (int) $counts->unique_ids);

        DB::table('building_info.buildings')
            ->select(['bin', 'public_id'])
            ->orderBy('bin')
            ->chunk(500, function ($buildings) {
                foreach ($buildings as $building) {
                    $this->assertTrue(
                        Str::isUuid($building->public_id),
                        "Building {$building->bin} has an invalid public_id."
                    );
                }
            });
    }

    public function test_containment_public_ids_are_populated_unique_uuids(): void
    {
        $counts = DB::table('fsm.containments')
            ->selectRaw('count(*) AS total, count(public_id) AS populated, count(distinct public_id) AS unique_ids')
            ->first();

        $this->assertGreaterThan(0, (int) $counts->total);
        $this->assertSame((int) $counts->total, (int) $counts->populated);
        $this->assertSame((int) $counts->total, (int) $counts->unique_ids);

        DB::table('fsm.containments')
            ->select(['id', 'public_id'])
            ->orderBy('id')
            ->chunk(500, function ($containments) {
                foreach ($containments as $containment) {
                    $this->assertTrue(
                        Str::isUuid($containment->public_id),
                        "Containment {$containment->id} has an invalid public_id."
                    );
                }
            });
    }

    public function test_building_urls_use_public_id_and_not_bin(): void
    {
        $building = Building::query()->firstOrFail();

        $urls = [
            action('BuildingInfo\BuildingController@show', [$building->public_id]),
            action('BuildingInfo\BuildingController@edit', [$building->public_id]),
            action('BuildingInfo\BuildingController@history', [$building->public_id]),
            route('buildings.listContainments', ['building' => $building->public_id]),
            route('buildings.destroy', ['building' => $building->public_id]),
            action('MapsController@index', [
                'layer' => 'buildings_layer',
                'field' => 'public_id',
                'val' => $building->public_id,
            ]),
        ];

        foreach ($urls as $url) {
            $this->assertStringContainsString($building->public_id, $url);
            $this->assertStringNotContainsString($building->bin, $url);
        }
    }

    public function test_containment_urls_use_public_id_and_not_internal_id(): void
    {
        $containment = Containment::query()->firstOrFail();

        $urls = [
            action('Fsm\ContainmentController@show', [$containment->public_id]),
            action('Fsm\ContainmentController@edit', [$containment->public_id]),
            action('Fsm\ContainmentController@history', [$containment->public_id]),
            action('Fsm\ContainmentController@typeChangeHistory', [$containment->public_id]),
            route('containments.destroy', ['containment' => $containment->public_id]),
            action('MapsController@index', [
                'layer' => 'containments_layer',
                'field' => 'public_id',
                'val' => $containment->public_id,
            ]),
            action('MapsController@index', [
                'layer' => 'containments_layer',
                'field' => 'public_id',
                'val' => $containment->public_id,
                'action' => 'containment-road',
            ]),
        ];

        foreach ($urls as $url) {
            $this->assertStringContainsString($containment->public_id, $url);
            $this->assertStringNotContainsString($containment->id, $url);
        }
    }

    public function test_valid_building_public_id_resolves_the_correct_record(): void
    {
        $building = Building::query()->firstOrFail();

        $response = $this->get('/_test/buildings/' . $building->public_id);

        $response->assertOk()->assertJson([
            'bin' => $building->bin,
            'public_id' => $building->public_id,
        ]);

        $this->assertDatabaseHas('building_info.buildings', [
            'bin' => $building->bin,
            'public_id' => $building->public_id,
        ]);
    }

    public function test_valid_containment_public_id_resolves_the_correct_record(): void
    {
        $containment = Containment::query()->firstOrFail();

        $response = $this->get('/_test/containments/' . $containment->public_id);

        $response->assertOk()->assertJson([
            'id' => $containment->id,
            'public_id' => $containment->public_id,
        ]);

        $this->assertDatabaseHas('fsm.containments', [
            'id' => $containment->id,
            'public_id' => $containment->public_id,
        ]);
    }

    public function test_internal_identifiers_and_invalid_values_do_not_bind(): void
    {
        $building = Building::query()->firstOrFail();
        $containment = Containment::query()->firstOrFail();

        $this->get('/_test/buildings/' . $building->bin)
            ->assertNotFound();

        $this->get('/_test/containments/' . $containment->id)
            ->assertNotFound();

        $this->get('/_test/buildings/invalid-public-id')
            ->assertNotFound();

        $this->get('/_test/containments/invalid-public-id')
            ->assertNotFound();
    }


    public function test_creating_models_generates_unique_public_ids(): void
    {
        $first = new PublicIdTestModel();
        $second = new PublicIdTestModel();

        $first->fireCreatingEventForTest();
        $second->fireCreatingEventForTest();

        $this->assertTrue(Str::isUuid($first->public_id));
        $this->assertTrue(Str::isUuid($second->public_id));
        $this->assertNotSame($first->public_id, $second->public_id);
    }
}

/**
 * In-memory model used only to exercise the HasPublicId creating event. It does
 * not connect to or write to the database.
 */
class PublicIdTestModel extends Model
{
    use HasPublicId;

    public function fireCreatingEventForTest(): void
    {
        $this->fireModelEvent('creating');
    }
}
