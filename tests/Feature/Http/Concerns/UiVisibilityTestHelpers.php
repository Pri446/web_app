<?php

namespace Tests\Feature\Http\Concerns;

trait UiVisibilityTestHelpers
{
    /**
     * Verify that a control element or button with data-testid marker is visible in the HTML response.
     *
     * @param \Illuminate\Testing\TestResponse $response
     * @param string $testId
     * @return void
     */
    protected function assertControlVisible($response, string $testId): void
    {
        $response->assertSee('data-testid="' . $testId . '"', false);
    }

    /**
     * Verify that a control element or button with data-testid marker is hidden from the HTML response.
     *
     * @param \Illuminate\Testing\TestResponse $response
     * @param string $testId
     * @return void
     */
    protected function assertControlHidden($response, string $testId): void
    {
        $response->assertDontSee('data-testid="' . $testId . '"', false);
    }

    protected function assertStableElementIsVisible(\Illuminate\Testing\TestResponse $response, string $id): void
    {
        $response->assertSee('id="'.$id.'"', false);
    }

    protected function assertStableElementIsNotVisible(\Illuminate\Testing\TestResponse $response, string $id): void
    {
        $response->assertDontSee('id="'.$id.'"', false);
    }
}
