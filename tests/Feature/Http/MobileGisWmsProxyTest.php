<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileGisWmsProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'services.geoserver.url' => 'https://geoserver.test/geoserver/imis_base',
            'services.geoserver.workspace' => 'imis_base',
            'services.geoserver.auth_key' => 'server-side-key',
        ]);
        Http::fake(['geoserver.test/*' => Http::response('png-bytes', 200, ['Content-Type' => 'image/png'])]);
    }

    private function actingAsMobileUser(): void
    {
        $user = new User();
        $user->id = 1;
        Sanctum::actingAs($user);
    }

    private function tileUrl(array $overrides = []): string
    {
        return '/api/gis/wms?' . http_build_query(array_merge([
            'SERVICE' => 'WMS',
            'VERSION' => '1.3.0',
            'REQUEST' => 'GetMap',
            'FORMAT' => 'image/png',
            'LAYERS' => 'imis_base:buildings_layer',
            'STYLES' => 'imis_base:buildings_layer_none',
            'CRS' => 'EPSG:3857',
            'WIDTH' => 512,
            'HEIGHT' => 512,
            'BBOX' => '9499294,3201237,9505273,3207651',
        ], $overrides));
    }

    public function test_it_requires_a_token(): void
    {
        $this->getJson($this->tileUrl())->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_every_mobile_layer_loads_without_web_map_permissions(): void
    {
        $this->actingAsMobileUser();

        foreach (config('gis.mobile_wms_layers') as $pair) {
            $this->get($this->tileUrl([
                'LAYERS' => 'imis_base:' . $pair['layer'],
                'STYLES' => 'imis_base:' . $pair['style'],
            ]))->assertOk()->assertHeader('Content-Type', 'image/png');
        }

        Http::assertSentCount(count(config('gis.mobile_wms_layers')));
    }

    public function test_the_auth_key_stays_on_the_server(): void
    {
        $this->actingAsMobileUser();

        $response = $this->get($this->tileUrl());

        $this->assertStringNotContainsString('server-side-key', $response->getContent());
        Http::assertSent(fn ($request) => str_contains($request->url(), 'authkey=server-side-key'));
    }

    /**
     * @dataProvider blockedRequests
     */
    public function test_it_blocks_anything_beyond_allowed_map_images(array $overrides): void
    {
        $this->actingAsMobileUser();

        $this->get($this->tileUrl($overrides))->assertForbidden();

        Http::assertNothingSent();
    }

    public function blockedRequests(): array
    {
        return [
            'feature info' => [['REQUEST' => 'GetFeatureInfo', 'QUERY_LAYERS' => 'imis_base:buildings_layer']],
            'capabilities' => [['REQUEST' => 'GetCapabilities']],
            'layer outside the list' => [['LAYERS' => 'imis_base:low_income_communities_layer']],
            'second layer outside the list' => [['LAYERS' => 'imis_base:buildings_layer,imis_base:landuses_layer']],
            'other workspace' => [['LAYERS' => 'other:buildings_layer']],
            'style outside the list' => [['STYLES' => 'imis_base:buildings_tax_status']],
            'vector output' => [['FORMAT' => 'application/vnd.google-earth.kml+xml']],
            'custom style document' => [['SLD' => 'https://attacker.test/style.sld']],
            'inline style document' => [['SLD_BODY' => '<StyledLayerDescriptor/>']],
        ];
    }

    public function test_parameter_names_are_not_case_sensitive(): void
    {
        $this->actingAsMobileUser();

        $this->get('/api/gis/wms?' . http_build_query([
            'service' => 'WMS',
            'request' => 'GetMap',
            'format' => 'image/png',
            'layers' => 'imis_base:low_income_communities_layer',
            'styles' => '',
        ]))->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_a_second_spelling_of_a_parameter_cannot_reach_geoserver(): void
    {
        $this->actingAsMobileUser();

        $this->get($this->tileUrl(['FORMAT' => 'kml']) . '&format=image/png')->assertOk();

        Http::assertSent(fn ($request) => !str_contains($request->url(), 'kml'));
    }

    public function test_tiles_do_not_use_the_shared_api_rate_limit(): void
    {
        $this->actingAsMobileUser();

        foreach (range(1, 61) as $request) {
            $this->get($this->tileUrl())->assertOk();
        }

        $this->getJson('/api/gis/wms?SERVICE=WMS&REQUEST=GetCapabilities')->assertForbidden();
    }

    public function test_tiles_have_their_own_rate_limit(): void
    {
        $this->actingAsMobileUser();

        $response = $this->get($this->tileUrl());

        $response->assertOk()->assertHeader('X-RateLimit-Limit', 600);
    }

    public function test_a_repeat_tile_is_served_from_the_cache(): void
    {
        $this->actingAsMobileUser();

        $this->get($this->tileUrl())->assertOk()->assertHeader('X-Tile-Cache', 'MISS');
        $second = $this->get($this->tileUrl());

        $second->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Tile-Cache', 'HIT');
        $this->assertSame('png-bytes', $second->getContent());
        Http::assertSentCount(1);
    }

    public function test_parameter_order_does_not_change_the_cached_tile(): void
    {
        $this->actingAsMobileUser();

        $this->get($this->tileUrl())->assertOk();
        $reordered = '/api/gis/wms?BBOX=9499294,3201237,9505273,3207651&HEIGHT=512&WIDTH=512'
            . '&CRS=EPSG:3857&STYLES=imis_base:buildings_layer_none&LAYERS=imis_base:buildings_layer'
            . '&FORMAT=image/png&REQUEST=GetMap&VERSION=1.3.0&SERVICE=WMS';
        $this->get($reordered)->assertOk()->assertHeader('X-Tile-Cache', 'HIT');

        Http::assertSentCount(1);
    }

    public function test_a_different_tile_is_fetched_again(): void
    {
        $this->actingAsMobileUser();

        $this->get($this->tileUrl())->assertOk();
        $this->get($this->tileUrl(['BBOX' => '1,2,3,4']))->assertOk()->assertHeader('X-Tile-Cache', 'MISS');

        Http::assertSentCount(2);
    }

    public function test_geoserver_errors_are_not_cached(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['geoserver.test/*' => Http::sequence()
            ->push('<ServiceExceptionReport/>', 200, ['Content-Type' => 'application/vnd.ogc.se_xml'])
            ->push('', 500)
            ->push('png-bytes', 200, ['Content-Type' => 'image/png'])]);
        $this->actingAsMobileUser();

        $this->get($this->tileUrl());
        $this->get($this->tileUrl());
        $this->get($this->tileUrl())->assertOk()->assertHeader('X-Tile-Cache', 'MISS');

        Http::assertSentCount(3);
    }

    public function test_a_cached_tile_still_needs_a_token(): void
    {
        $this->actingAsMobileUser();
        $this->get($this->tileUrl())->assertOk();

        $this->app['auth']->forgetGuards();
        $this->getJson($this->tileUrl())->assertUnauthorized();
    }

    public function test_the_cache_can_be_turned_off(): void
    {
        config(['gis.mobile_wms_cache_seconds' => 0]);
        $this->actingAsMobileUser();

        $this->get($this->tileUrl())->assertOk();
        $this->get($this->tileUrl())->assertOk();

        Http::assertSentCount(2);
    }
}
