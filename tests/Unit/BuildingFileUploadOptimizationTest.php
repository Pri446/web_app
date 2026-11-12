<?php

namespace Tests\Unit;

use Tests\TestCase;

class BuildingFileUploadOptimizationTest extends TestCase
{
    public function test_file_fields_are_at_the_top_of_building_information(): void
    {
        $view = file_get_contents(
            resource_path('views/building-info/buildings/partial-form.blade.php')
        );

        $headingPosition = strpos($view, "__('Building Information')");
        $kmlPosition = strpos($view, "Form::label('geom'");
        $mainBuildingPosition = strpos($view, "Form::label('main_building'");

        $this->assertNotFalse($headingPosition);
        $this->assertNotFalse($kmlPosition);
        $this->assertNotFalse($mainBuildingPosition);
        $this->assertLessThan($kmlPosition, $headingPosition);
        $this->assertLessThan($mainBuildingPosition, $kmlPosition);
        $this->assertStringContainsString("'accept' => '.kml", $view);
        $this->assertStringContainsString("'accept' => '.jpg,.jpeg,image/jpeg'", $view);
    }

    public function test_shared_upload_script_validates_kml_and_compresses_images(): void
    {
        $buildingScript = file_get_contents(public_path('js/building-file-upload.js'));
        $imageScript = file_get_contents(public_path('js/emptying-image-upload.js'));

        $this->assertStringContainsString('MAX_DIMENSION = 1600', $imageScript);
        $this->assertStringContainsString('JPEG_QUALITY = 0.70', $imageScript);
        $this->assertStringContainsString("canvas.toBlob(resolve, 'image/jpeg'", $imageScript);
        $this->assertStringContainsString("getElementsByTagNameNS('*', 'Polygon')", $buildingScript);
        $this->assertStringContainsString('KML_MAX_BYTES = 1 * 1024 * 1024', $buildingScript);
    }

    public function test_house_images_use_short_lived_browser_caching(): void
    {
        $configuration = file_get_contents(public_path('.htaccess'));

        $this->assertStringContainsString('/storage/emptyings/houses/', $configuration);
        $this->assertStringContainsString(
            'Cache-Control "public, max-age=3600, must-revalidate"',
            $configuration
        );
    }

    public function test_php_upload_limit_allows_the_documented_five_megabyte_images(): void
    {
        $configuration = file_get_contents(public_path('.user.ini'));

        $this->assertStringContainsString('upload_max_filesize = 6M', $configuration);
        $this->assertStringContainsString('post_max_size = 12M', $configuration);
    }

    public function test_emptying_images_are_optimized_before_upload(): void
    {
        $script = file_get_contents(public_path('js/emptying-image-upload.js'));

        $this->assertStringContainsString('MAX_DIMENSION = 1600', $script);
        $this->assertStringContainsString('JPEG_QUALITY = 0.70', $script);
        $this->assertStringContainsString("canvas.toBlob(resolve, 'image/jpeg'", $script);
        $this->assertStringContainsString("['house_image', 'receipt_image']", $script);
    }
}
