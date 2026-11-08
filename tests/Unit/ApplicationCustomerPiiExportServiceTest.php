<?php

namespace Tests\Unit;

use App\Services\ApplicationCustomerPiiExportService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ApplicationCustomerPiiExportServiceTest extends TestCase
{
    public function test_it_parses_and_deduplicates_application_ids(): void
    {
        $service = app(ApplicationCustomerPiiExportService::class);

        $ids = $service->parseCsvText("application_id\n12\n15\n12\n");

        $this->assertSame(['12', '15'], $ids);
    }

    public function test_it_requires_the_application_id_header(): void
    {
        $service = app(ApplicationCustomerPiiExportService::class);

        $this->expectException(ValidationException::class);
        $service->parseCsvText("bin\nB016742\n");
    }

    public function test_it_rejects_non_numeric_application_ids(): void
    {
        $service = app(ApplicationCustomerPiiExportService::class);

        $this->expectException(ValidationException::class);
        $service->parseCsvText("application_id\nAPP-12\n");
    }

    public function test_it_parses_normalizes_and_deduplicates_bins(): void
    {
        $service = app(ApplicationCustomerPiiExportService::class);

        $bins = $service->parseBinCsvText("bin\nb016742\nB016741\nB016742\n");

        $this->assertSame(['B016742', 'B016741'], $bins);
    }

    public function test_bin_export_requires_a_bin_header(): void
    {
        $service = app(ApplicationCustomerPiiExportService::class);

        $this->expectException(ValidationException::class);
        $service->parseBinCsvText("application_id\n12\n");
    }
}
