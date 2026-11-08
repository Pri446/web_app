<?php

namespace Tests\Feature\Fsm;

use Tests\TestCase;

class DownstreamPiiIsolationTest extends TestCase
{
    /** @test */
    public function schedule_and_reintegration_flows_do_not_read_or_return_owner_pii(): void
    {
        $scheduleService = file_get_contents(
            app_path('Services/Fsm/DesludgingScheduleService.php')
        );
        $scheduleView = file_get_contents(
            resource_path('views/fsm/desludging-schedule/index.blade.php')
        );
        $reintegrationController = file_get_contents(
            app_path('Http/Controllers/Fsm/DesludgingReintegrationController.php')
        );
        $reintegrationView = file_get_contents(
            resource_path('views/fsm/desludging-reintegration/index.blade.php')
        );

        $this->assertStringNotContainsString(
            'building_info.owners',
            $scheduleService
        );
        $this->assertStringNotContainsString('owner_name', $scheduleService);
        $this->assertStringNotContainsString("data: 'display_name'", $scheduleView);
        $this->assertStringNotContainsString(
            'building_info.owners',
            $reintegrationController
        );
        $this->assertStringNotContainsString(
            'data-owner-name',
            $reintegrationController
        );
        $this->assertStringNotContainsString(
            "data: 'display_name'",
            $reintegrationView
        );
        $this->assertStringNotContainsString(
            "data: 'display_contact'",
            $reintegrationView
        );
    }

    /** @test */
    public function feedback_writes_and_exports_application_applicant_information(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/Fsm/FeedbackController.php')
        );

        $this->assertStringContainsString(
            '$feedback->customer_name = $application->applicant_name;',
            $source
        );
        $this->assertStringContainsString("'a.applicant_name'", $source);
        $this->assertStringContainsString("'a.applicant_gender'", $source);
        $this->assertStringContainsString("'a.applicant_contact'", $source);
        $this->assertStringNotContainsString(
            '$application->customer_name',
            $source
        );
        $this->assertStringNotContainsString(
            '$request->customer_name',
            $source
        );
    }
}
