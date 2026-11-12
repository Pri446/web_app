<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class BuildingDashboardOptimizationTest extends TestCase
{
    public function test_building_dashboard_returns_the_loading_shell_first(): void
    {
        $user = User::role('Municipality - Super Admin')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->firstOrFail();

        $response = $this->actingAs($user)->get(route('buildingdashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard.buildingDashboardShell');
        $response->assertSeeText('Loading dashboard data...');
        $response->assertSee(str_replace('/', '\\/', route('buildingdashboard.content')), false);
        $response->assertSee("link.setAttribute('aria-busy', 'true')", false);
        $response->assertSee('preventRepeatedDashboardNavigation', false);
        $response->assertSee("event.stopImmediatePropagation()", false);
        $response->assertDontSee("link.style.pointerEvents = 'none'", false);
        $response->assertDontSee('id="sidebar-navigation-lock"', false);
    }

    public function test_building_dashboard_content_returns_a_json_html_contract(): void
    {
        $user = User::role('Municipality - Super Admin')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->firstOrFail();

        $response = $this->actingAs($user)
            ->getJson(route('buildingdashboard.content'));

        $response->assertOk();
        $response->assertJsonPath('status', 'ok');
        $this->assertIsString($response->json('html'));
        $this->assertStringContainsString('Buildings', $response->json('html'));
        $this->assertStringContainsString('Sanitation Systems', $response->json('html'));
        $this->assertStringContainsString('<script', $response->json('html'));
    }

    public function test_building_dashboard_shell_and_content_require_authentication(): void
    {
        $this->get(route('buildingdashboard'))
            ->assertRedirect(route('login.show'));

        $this->getJson(route('buildingdashboard.content'))
            ->assertUnauthorized();
    }
}
