<?php

namespace Tests\Unit;

use App\Http\Controllers\PlacesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;
use Yajra\DataTables\Facades\DataTables;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PlacesControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_opens_the_list_with_sorted_place_types(): void
    {
        $query = $this->mockTypeQuery(['Hospital', 'School']);

        $places = Mockery::mock('alias:App\Models\Places');
        $places->shouldReceive('whereNotNull')->once()->with('type')->andReturn($query);

        $response = (new PlacesController())->index();

        $this->assertSame('places.index', $response->name());
        $this->assertSame('Places', $response->getData()['page_title']);
        $this->assertSame(['Hospital', 'School'], $response->getData()['types']);
    }

    public function test_create_opens_the_form_with_available_place_types(): void
    {
        $query = $this->mockTypeQuery(['Hospital', 'School']);

        $places = Mockery::mock('alias:App\Models\Places');
        $places->shouldReceive('whereNotNull')->once()->with('type')->andReturn($query);

        $response = (new PlacesController())->create();

        $this->assertSame('places.create', $response->name());
        $this->assertSame('Add Places', $response->getData()['page_title']);
        $this->assertSame(['Hospital', 'School'], $response->getData()['types']);
    }

    public function test_store_saves_a_valid_place_and_returns_to_the_list(): void
    {
        $data = [
            'name' => 'Birendranagar Hospital',
            'ward' => 6,
            'geom' => 'POINT(81.6333 28.6000)',
            'type' => 'Hospital',
            'unique_reference_id' => 'PLACE-001',
        ];

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->andReturn($data);

        $places = Mockery::mock('overload:App\Models\Places');
        // Defining max() lets the overloaded Eloquent mock handle the static call.
        $places->shouldReceive('max')->zeroOrMoreTimes()->with('id')->andReturn(40);
        $places->shouldReceive('save')->once()->andReturnTrue();

        $response = (new PlacesController())->store($request);

        $this->assertSame(route('places.index'), $response->getTargetUrl());
        $this->assertSame('Place created successfully.', session('success'));
    }

    public function test_edit_opens_the_form_for_the_requested_place(): void
    {
        $place = (object) ['id' => 12, 'name' => 'City Hall'];
        $query = $this->mockTypeQuery(['Government Office', 'Hospital']);

        $places = Mockery::mock('alias:App\Models\Places');
        $places->shouldReceive('findOrFail')->once()->with(12)->andReturn($place);
        $places->shouldReceive('whereNotNull')->once()->with('type')->andReturn($query);
        DB::shouldReceive('selectOne')
            ->once()
            ->with(
                'SELECT ST_AsText(geom) AS geom FROM layer_info.places WHERE id = ?',
                [12]
            )
            ->andReturn((object) ['geom' => 'POINT(81.6333 28.6000)']);

        $response = (new PlacesController())->edit(12);

        $this->assertSame('places.edit', $response->name());
        $this->assertSame('Edit Places', $response->getData()['page_title']);
        $this->assertSame($place, $response->getData()['place']);
        $this->assertSame(['Government Office', 'Hospital'], $response->getData()['types']);
        $this->assertSame('POINT(81.6333 28.6000)', $response->getData()['geom']);
    }

    public function test_update_changes_the_place_and_returns_to_the_list(): void
    {
        $data = [
            'name' => 'Updated School',
            'ward' => 9,
            'geom' => 'POINT(81.6400 28.6100)',
            'type' => 'School',
            'unique_reference_id' => 'PLACE-002',
        ];

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->andReturn($data);

        $place = Mockery::mock();
        $place->shouldReceive('save')->once()->andReturnTrue();

        $places = Mockery::mock('alias:App\Models\Places');
        $places->shouldReceive('findOrFail')->once()->with(12)->andReturn($place);

        $response = (new PlacesController())->update($request, 12);

        $this->assertSame($data['name'], $place->name);
        $this->assertSame($data['ward'], $place->ward);
        $this->assertStringContainsString($data['geom'], (string) $place->geom);
        $this->assertSame($data['type'], $place->type);
        $this->assertSame($data['unique_reference_id'], $place->unique_reference_id);
        $this->assertSame(route('places.index'), $response->getTargetUrl());
        $this->assertSame('Place updated successfully.', session('success'));
    }

    public function test_show_opens_the_details_page_for_the_requested_place(): void
    {
        $place = (object) ['id' => 12, 'name' => 'City Hall'];

        $places = Mockery::mock('alias:App\Models\Places');
        $places->shouldReceive('findOrFail')->once()->with(12)->andReturn($place);

        $response = (new PlacesController())->show(12);

        $this->assertSame('places.show', $response->name());
        $this->assertSame('Places Detail', $response->getData()['page_title']);
        $this->assertSame($place, $response->getData()['places']);
    }

    public function test_destroy_deletes_the_place_and_returns_to_the_list(): void
    {
        $place = Mockery::mock();
        $place->shouldReceive('delete')->once()->andReturnTrue();

        $places = Mockery::mock('alias:App\Models\Places');
        $places->shouldReceive('findOrFail')->once()->with(12)->andReturn($place);

        $response = (new PlacesController())->destroy(12);

        $this->assertSame(route('places.index'), $response->getTargetUrl());
        $this->assertSame('Places deleted successfully.', session('success'));
    }

    public function test_get_data_applies_the_places_filters(): void
    {
        $request = Request::create('/places/data', 'GET', [
            'name' => 'City Hall',
            'type' => 'Government Office',
            'ward' => '6',
            'unique_reference_id' => 'PLACE-001',
        ]);

        $placesQuery = Mockery::mock();
        $filteredQuery = Mockery::mock();
        $filteredQuery->shouldReceive('whereRaw')
            ->once()
            ->with('LOWER(name) = LOWER(?)', ['City Hall'])
            ->andReturnSelf();
        $filteredQuery->shouldReceive('where')->once()->with('type', 'Government Office')->andReturnSelf();
        $filteredQuery->shouldReceive('where')->once()->with('ward', '6')->andReturnSelf();
        $filteredQuery->shouldReceive('where')->once()->with('unique_reference_id', 'PLACE-001')->andReturnSelf();

        $places = Mockery::mock('alias:App\Models\Places');
        $places->shouldReceive('whereNull')->once()->with('deleted_at')->andReturn($placesQuery);

        $dataTable = Mockery::mock();
        $dataTable->shouldReceive('filter')
            ->once()
            ->with(Mockery::on(function ($filter) use ($filteredQuery) {
                $filter($filteredQuery);

                return true;
            }))
            ->andReturnSelf();
        $dataTable->shouldReceive('addColumn')->once()->with('action', Mockery::type('Closure'))->andReturnSelf();
        $dataTable->shouldReceive('rawColumns')->once()->with(['action'])->andReturnSelf();
        $dataTable->shouldReceive('make')->once()->with(true)->andReturn('json-response');

        DataTables::shouldReceive('of')->once()->with($placesQuery)->andReturn($dataTable);

        $response = (new PlacesController())->getData($request);

        $this->assertSame('json-response', $response);
    }

    private function mockTypeQuery(array $types)
    {
        $query = Mockery::mock();
        $query->shouldReceive('distinct')->once()->andReturnSelf();
        $query->shouldReceive('orderBy')->once()->with('type')->andReturnSelf();
        $query->shouldReceive('pluck')->once()->with('type')->andReturn(collect($types));

        return $query;
    }
}
