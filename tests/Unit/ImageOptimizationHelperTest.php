<?php
namespace Tests\Unit;
use App\Helpers\ImageOptimizationHelper;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
class ImageOptimizationHelperTest extends TestCase
{
    /** @dataProvider imageSizes */
    public function test_uploaded_images_are_resized_without_stretching_or_upscaling(int $width,int $height,int $expectedWidth,int $expectedHeight):void
    {
        if(!extension_loaded('gd')){$this->markTestSkipped('GD is required for image processing.');}
        $file=UploadedFile::fake()->image('house.jpg',$width,$height);
        $destination=sys_get_temp_dir().'/imis-upload-test-'.bin2hex(random_bytes(8)).'.jpg';
        try {
            $this->assertTrue(ImageOptimizationHelper::optimizeAndSave($file,$destination));
            $size=getimagesize($destination);
            $this->assertSame($expectedWidth,$size[0]);
            $this->assertSame($expectedHeight,$size[1]);
        } finally {
            if(is_file($destination)){unlink($destination);}
        }
    }
    public static function imageSizes():array{return [[2000,1000,1600,800],[300,200,300,200]];}
}
