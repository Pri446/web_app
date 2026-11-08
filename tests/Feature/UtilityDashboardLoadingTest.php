<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class UtilityDashboardLoadingTest extends TestCase
{
    public function test_utility_dashboard_returns_the_loading_shell_first(): void
    {
        $user = User::role('Municipality - Super Admin')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->firstOrFail();

        $response = $this->actingAs($user)->get(route('utilitydashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard.utilityDashboardShell');
        $response->assertSeeText('Loading dashboard data...');
        $response->assertSee('var endpoint', false);
        $response->assertSee(str_replace('/', '\\/', route('utilitydashboard.content')), false);
        $this->assertGreaterThanOrEqual(
            6,
            substr_count($response->getContent(), 'data-dashboard-navigation class')
        );
        $response->assertSee("label.textContent = \"Loading...\"", false);
        $response->assertSee("link.setAttribute('aria-busy', 'true')", false);
        $response->assertSee('preventRepeatedDashboardNavigation', false);
        $response->assertSee("link.setAttribute('tabindex', '-1')", false);
        $response->assertDontSee("link.style.pointerEvents = 'none'", false);
        $response->assertDontSee('id="sidebar-navigation-lock"', false);
        $response->assertDontSee('navigationLinks.forEach(function (dashboardLink)', false);
    }

    public function test_utility_dashboard_content_returns_a_json_html_contract(): void
    {
        $user = User::role('Municipality - Super Admin')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->firstOrFail();

        $response = $this->actingAs($user)
            ->getJson(route('utilitydashboard.content'));

        $response->assertOk();
        $response->assertJsonPath('status', 'ok');
        $this->assertIsString($response->json('html'));
        $this->assertStringContainsString('Road', $response->json('html'));
        $this->assertStringContainsString('<script', $response->json('html'));
    }

    public function test_utility_dashboard_shell_and_content_require_authentication(): void
    {
        $this->get(route('utilitydashboard'))
            ->assertRedirect(route('login.show'));

        $this->getJson(route('utilitydashboard.content'))
            ->assertUnauthorized();
    }
}
