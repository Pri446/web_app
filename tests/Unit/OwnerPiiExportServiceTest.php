<?php

namespace Tests\Unit;

use App\Services\OwnerPiiExportService;
use App\Services\PiiEncryptionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class OwnerPiiExportServiceTest extends TestCase
{
    public function test_it_parses_normalizes_and_deduplicates_bins(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'bins.csv',
            "\xEF\xBB\xBFbin\n b016739 \nB016739\nB016741\n"
        );

        $this->assertSame(
            ['B016739', 'B016741'],
            $this->service()->parseBins($file)
        );
    }

    public function test_it_parses_validated_csv_text(): void
    {
        $this->assertSame(
            ['B016739', 'B016741'],
            $this->service()->parseCsvText(
                "\xEF\xBB\xBFbin\r\n b016739 \r\nB016739\r\nB016741\r\n"
            )
        );
    }

    public function test_it_rejects_a_csv_without_a_bin_header(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'bins.csv',
            "building_id\nB016739\n"
        );

        $this->expectException(ValidationException::class);

        $this->service()->parseBins($file);
    }

    public function test_it_enforces_the_configured_unique_bin_limit(): void
    {
        config(['pii.export.max_bins' => 1]);
        $file = UploadedFile::fake()->createWithContent(
            'bins.csv',
            "bin\nB016739\nB016741\n"
        );

        $this->expectException(ValidationException::class);

        $this->service()->parseBins($file);
    }

    public function test_it_escapes_spreadsheet_formula_values(): void
    {
        $service = $this->service();
        $method = new ReflectionMethod($service, 'escapeSpreadsheetValue');
        $method->setAccessible(true);

        $this->assertSame("'=2+2", $method->invoke($service, '=2+2'));
        $this->assertSame('Normal Owner', $method->invoke($service, 'Normal Owner'));
    }

    private function service(): OwnerPiiExportService
    {
        return new OwnerPiiExportService(
            Mockery::mock(PiiEncryptionService::class)
        );
    }
}
