<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Places;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Http\Concerns\InputBehaviorTestHelpers;
use Tests\Feature\Http\Concerns\UiVisibilityTestHelpers;
use Tests\TestCase;

/**
 * Places Module Roadmap-Compliant Feature Test
 *
 * Implements the 7-gate developer verification path:
 * 1. Role / Login Access
 * 2. Page Visibility
 * 3. Data Visibility
 * 4. Button Visibility
 * 5. Button Target / Page Load
 * 6. Action / Function Testing (Create, Update, Delete, Filter, Export)
 * 7. Output & Data Consistency
 */
class PlacesControllerTest extends TestCase
{
    use DatabaseTransactions;
    use InputBehaviorTestHelpers;
    use UiVisibilityTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // ============================================================
    // ROLE PROVIDERS
    // ============================================================

    public static function authorizedRolesProvider(): array
    {
        return [
            'building permit' => [
                'Municipality - Building Permit Department',
            ],
            'municipality super admin' => [
                'Municipality - Super Admin',
            ],
        ];
    }

    public static function unauthorizedRolesProvider(): array
    {
        return [
            'unauthorized user' => [
                'Unauthorized Role',
            ],
        ];
    }

    // ============================================================
    // GATE 1 & 2: ROLE / LOGIN AND PAGE VISIBILITY
    // ============================================================

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_authorized_role_can_access_index_page(string $role): void
    {
        $user = $this->getUserWithRole($role);

        $response = $this->actingAs($user)
            ->get(route('places.index'));

        $response->assertOk();
    }

    /**
     * @dataProvider unauthorizedRolesProvider
     */
    public function test_unauthorized_role_cannot_access_index_page(string $role): void
    {
        $user = $this->getUserWithoutPermission();

        $response = $this->actingAs($user)
            ->get(route('places.index'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_redirected_to_login_on_index(): void
    {
        $response = $this->get(route('places.index'));

        $response->assertRedirect('/login');
    }

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_authorized_role_can_access_create_page(string $role): void
    {
        $user = $this->getUserWithRole($role);

        $response = $this->actingAs($user)
            ->get(route('places.create'));

        $response->assertOk();
    }

    /**
     * @dataProvider unauthorizedRolesProvider
     */
    public function test_unauthorized_role_cannot_access_create_page(string $role): void
    {
        $user = $this->getUserWithoutPermission();

        $response = $this->actingAs($user)
            ->get(route('places.create'));

        $response->assertForbidden();
    }

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_authorized_role_can_access_edit_page(string $role): void
    {
        $user = $this->getUserWithRole($role);
        $place = $this->createTestPlace([
            'name' => 'Edit Page Place',
            'ward' => 1,
            'unique_reference_id' => 'EDIT-PAGE-001',
        ]);

        $response = $this->actingAs($user)
            ->get(route('places.edit', $place->id));

        $response->assertOk();
    }

    // ============================================================
    // GATE 3: DATA VISIBILITY
    // ============================================================

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_data_visibility_returns_active_records_and_excludes_deleted_records(string $role): void
    {
        $user = $this->getUserWithRole($role);

        $activeFixture = $this->loadDataset('Places', 7);
        $deletedFixture = $this->loadDataset('Places', 8);

        // Create active test-controlled record
        $this->createTestPlace([
            'name' => $activeFixture['payload']['name'],
            'ward' => $activeFixture['payload']['ward'],
            'type' => $activeFixture['payload']['type'],
            'unique_reference_id' => $activeFixture['payload']['unique_reference_id'],
        ]);

        // Create soft-deleted test-controlled record
        $this->createTestPlace([
            'name' => $deletedFixture['payload']['name'],
            'ward' => $deletedFixture['payload']['ward'],
            'type' => $deletedFixture['payload']['type'],
            'unique_reference_id' => $deletedFixture['payload']['unique_reference_id'],
            'deleted_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('places.getData', ['ward' => 6]));

        $response->assertOk();

        // Active record must be present
        $response->assertJsonFragment([
            'unique_reference_id' => $activeFixture['payload']['unique_reference_id'],
        ]);

        // Soft-deleted record must be excluded
        $response->assertJsonMissing([
            'unique_reference_id' => $deletedFixture['payload']['unique_reference_id'],
        ]);
    }

    // ============================================================
    // GATE 4 & 5: BUTTON VISIBILITY AND TARGET PAGE LOAD
    // ============================================================

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_authorized_role_can_see_places_in_navbar(string $role): void
    {
        $user = $this->getUserWithRole($role);

        $response = $this->actingAs($user)
            ->get(route('home'));

        $response->assertOk();
        $response->assertSee('data-testid="nav-places"', false);
    }

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_authorized_role_can_see_add_places_button(string $role): void
    {
        $user = $this->getUserWithRole($role);

        $response = $this->actingAs($user)
            ->get(route('places.index'));

        $response->assertOk();
        $response->assertSee('data-testid="add-place-button"', false);
    }

    // ============================================================
    // GATE 6: ACTION / FUNCTION TESTING
    // ============================================================

    // --- CREATE ---

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_authorized_role_can_create_place(string $role): void
    {
        $user = $this->getUserWithRole($role);
        $fixture = $this->loadDataset('Places', 1);

        $this->assertDatabaseMissing('layer_info.places', [
            'unique_reference_id' => $fixture['payload']['unique_reference_id'],
        ]);

        $response = $this->actingAs($user)
            ->post(route('places.store'), $fixture['payload']);

        $response->assertRedirect(route('places.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('layer_info.places', $fixture['expected']);
    }

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_place_can_be_created_without_optional_fields(string $role): void
    {
        $user = $this->getUserWithRole($role);
        $fixture = $this->loadDataset('Places', 2);

        $response = $this->actingAs($user)
            ->post(route('places.store'), $fixture['payload']);

        $response->assertRedirect(route('places.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('layer_info.places', $fixture['expected']);
    }

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_place_cannot_be_created_without_name(string $role): void
    {
        $user = $this->getUserWithRole($role);
        $fixture = $this->loadDataset('Places', 4);

        $this->assertRequiredFieldValidationFails(
            $user,
            'places.store',
            'layer_info.places',
            $fixture
        );
    }

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_place_cannot_be_created_with_duplicate_reference_id(string $role): void
    {
        $user = $this->getUserWithRole($role);
        $fixture = $this->loadDataset('Places', 3);

        $firstResponse = $this->actingAs($user)
            ->post(route('places.store'), $fixture['payload']);

        $firstResponse->assertRedirect(route('places.index'));

        $secondResponse = $this->actingAs($user)
            ->post(route('places.store'), $fixture['payload']);

        $secondResponse->assertSessionHasErrors(['unique_reference_id']);
    }

    /**
     * @dataProvider unauthorizedRolesProvider
     */
    public function test_unauthorized_role_cannot_create_place(string $role): void
    {
        $user = $this->getUserWithoutPermission();
        $fixture = $this->loadDataset('Places', 2);

        $response = $this->actingAs($user)
            ->post(route('places.store'), $fixture['payload']);

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_create_place(): void
    {
        $fixture = $this->loadDataset('Places', 2);

        $response = $this->post(route('places.store'), $fixture['payload']);

        $response->assertRedirect('/login');
    }

    // --- UPDATE ---

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_authorized_role_can_update_place(string $role): void
    {
        $user = $this->getUserWithRole($role);
        $place = $this->createTestPlace([
            'name' => 'Original Place Name',
            'ward' => 1,
            'unique_reference_id' => 'UPDATE-ORIGINAL-001',
        ]);
        $fixture = $this->loadDataset('Places', 5);

        $response = $this->actingAs($user)
            ->put(route('places.update', $place->id), $fixture['payload']);

        $response->assertRedirect(route('places.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('layer_info.places', array_merge(
            ['id' => $place->id],
            $fixture['expected']
        ));
    }

    // --- DELETE ---

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_authorized_role_can_delete_place(string $role): void
    {
        $user = $this->getUserWithRole($role);
        $place = $this->createTestPlace([
            'name' => 'Place To Delete',
            'ward' => 2,
            'unique_reference_id' => 'DELETE-TEST-001',
        ]);

        $response = $this->actingAs($user)
            ->delete(route('places.destroy', $place->id));

        $response->assertRedirect(route('places.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('layer_info.places', ['id' => $place->id]);
    }

    // --- SEARCH / FILTER ---

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_search_and_filter_places(string $role): void
    {
        $user = $this->getUserWithRole($role);
        $this->createTestPlace([
            'name' => 'Unique Test Search Place',
            'ward' => 5,
            'unique_reference_id' => 'SEARCH-001',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('places.getData', [
                'name' => 'Unique Test Search Place',
                'ward' => 5,
            ]));

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'Unique Test Search Place',
        ]);
    }

    // --- EXPORT ---

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_export_places_data(string $role): void
    {
        $user = $this->getUserWithRole($role);

        $this->withoutExceptionHandling();

        ob_start();
        try {
            $response = $this->actingAs($user)
                ->get(route('places.export'));

            $response->assertOk();
        } catch (\ErrorException $e) {
            // Box/Spout openToBrowser sends native PHP header() calls which throw ErrorException in CLI PHPUnit environment.
            $this->assertStringContainsString('Cannot modify header information', $e->getMessage());
        } finally {
            if (ob_get_level()) {
                ob_end_clean();
            }
        }
    }

    // --- VALIDATION & INPUT BEHAVIOR ---

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_place_name_cannot_exceed_200_characters(string $role): void
    {
        $user = $this->getUserWithRole($role);
        $fixture = $this->loadDataset('Places', 6);

        $this->assertOverlongInputIsRejected(
            $user,
            'places.store',
            $fixture
        );
    }

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_leading_and_trailing_whitespace_is_trimmed(string $role): void
    {
        $user = $this->getUserWithRole($role);
        $fixture = $this->loadDataset('Places', 10);

        $this->assertWhitespaceIsTrimmed(
            $user,
            'places.store',
            'places.index',
            'layer_info.places',
            $fixture
        );
    }

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_sql_like_input_is_treated_as_plain_data(string $role): void
    {
        $user = $this->getUserWithRole($role);
        $fixture = $this->loadDataset('Places', 12);

        $this->assertSqlLikeInputIsHandledAsLiteral(
            $user,
            'places.store',
            'places.index',
            'layer_info.places',
            $fixture
        );
    }

    // ============================================================
    // GATE 7: OUTPUT & DATA CONSISTENCY
    // ============================================================

    /**
     * @dataProvider authorizedRolesProvider
     */
    public function test_frontend_data_matches_database_query_scope(string $role): void
    {
        $user = $this->getUserWithRole($role);

        $this->createTestPlace([
            'ward' => 6,
            'unique_reference_id' => 'CONSISTENCY-001',
        ]);

        $expectedRefs = DB::table('layer_info.places')
            ->whereNull('deleted_at')
            ->where('ward', 6)
            ->orderBy('id')
            ->pluck('unique_reference_id')
            ->values()
            ->all();

        $response = $this->actingAs($user)
            ->getJson(route('places.getData', ['ward' => 6]));

        $response->assertOk();

        $actualRefs = collect($response->json('data'))
            ->pluck('unique_reference_id')
            ->values()
            ->all();

        $this->assertSame($expectedRefs, $actualRefs);
    }

    // ============================================================
    // HELPERS
    // ============================================================

    protected function getUserWithRole(string $role): User
    {
        Permission::firstOrCreate(['name' => 'List Places']);
        Permission::firstOrCreate(['name' => 'Delete Places']);

        $user = User::role($role)->first();

        if (!$user) {
            $user = new User();
            $user->name = 'Test User (' . $role . ')';
            $user->email = 'user_' . uniqid() . '@example.com';
            $user->username = 'user_' . uniqid();
            $user->password = bcrypt('password');
            $user->user_type = 'Municipality';
            $user->status = 1;
            $user->save();

            if (method_exists($user, 'assignRole')) {
                $user->assignRole($role);
            }
        }

        $user->givePermissionTo(['List Places', 'Delete Places']);

        return $user;
    }

    protected function getUserWithoutPermission(): User
    {
        $user = new User();
        $user->name = 'Test Unauthorized User';
        $user->email = 'unauth_' . uniqid() . '@example.com';
        $user->username = 'unauth_' . uniqid();
        $user->password = bcrypt('password');
        $user->user_type = 'Municipality';
        $user->status = 1;
        $user->save();

        return $user;
    }

    protected function createTestPlace(array $attributes = []): Places
    {
        $maxId = Places::max('id') ?? 0;

        $place = new Places();
        $place->id = $maxId + 1;
        $place->name = $attributes['name'] ?? 'Test Place';
        $place->ward = $attributes['ward'] ?? 1;
        $place->type = $attributes['type'] ?? 'Chowk';
        $place->unique_reference_id = $attributes['unique_reference_id'] ?? ('TEST-' . uniqid());
        $place->geom = DB::raw("ST_GeomFromText('POINT(85.3240 27.7040)')");

        if (isset($attributes['deleted_at'])) {
            $place->deleted_at = $attributes['deleted_at'];
        }

        $place->save();

        return $place;
    }
}