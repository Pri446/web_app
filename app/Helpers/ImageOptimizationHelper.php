<?php

namespace App\Helpers;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;

class ImageOptimizationHelper
{
    /**
     * Optimize, orientate, resize, and save an image.
     *
     * 1. Auto-orientates based on EXIF data to fix smartphone rotation (portrait/landscape).
     * 2. Constrains maximum bounding box (width and height) while keeping original aspect ratio.
     * 3. Prevents upscaling of smaller images.
     * 4. Applies optimal compression quality (default 70%), dramatically reducing storage and network transfer time.
     *
     * @param mixed $file UploadedFile or path to source image
     * @param string $destinationPath Absolute path where the image should be saved
     * @param int $maxDimension Maximum width/height in pixels (default: 1600)
     * @param int $quality JPEG quality (1-100, default: 70)
     * @return bool
     */
    public static function optimizeAndSave($file, string $destinationPath, int $maxDimension = 1600, int $quality = 70): bool
    {
        $directory = dirname($destinationPath);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $image = Image::make($file);

        // Fix smartphone EXIF rotation
        $image->orientate();

        // Constrain dimensions within max bounding box without stretching or upscaling
        $image->resize($maxDimension, $maxDimension, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        // Save with compression
        $image->save($destinationPath, $quality);

        return File::exists($destinationPath);
    }
}
