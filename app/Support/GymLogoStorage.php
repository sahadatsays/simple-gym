<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class GymLogoStorage
{
    private const int MAX_WIDTH = 400;

    private const int MAX_HEIGHT = 400;

    private const int JPEG_QUALITY = 90;

    public function store(UploadedFile $logo): string
    {
        $contents = $this->optimize($logo);
        $extension = $logo->getMimeType() === 'image/png' ? 'png' : 'jpg';
        $path = 'gym/logos/'.Str::uuid().'.'.$extension;

        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
    }

    private function optimize(UploadedFile $logo): string
    {
        if (! extension_loaded('gd')) {
            return file_get_contents($logo->getRealPath()) ?: throw new RuntimeException('Unable to read uploaded logo.');
        }

        $source = $this->createImageResource($logo);

        if ($source === false) {
            return file_get_contents($logo->getRealPath()) ?: throw new RuntimeException('Unable to read uploaded logo.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height);
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($canvas === false) {
            imagedestroy($source);

            throw new RuntimeException('Unable to prepare the uploaded logo.');
        }

        if ($logo->getMimeType() === 'image/png') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
        }

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();

        if ($logo->getMimeType() === 'image/png') {
            imagepng($canvas);
        } else {
            imagejpeg($canvas, null, self::JPEG_QUALITY);
        }

        $contents = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        if ($contents === false) {
            throw new RuntimeException('Unable to optimize the uploaded logo.');
        }

        return $contents;
    }

    private function createImageResource(UploadedFile $logo): \GdImage|false
    {
        $path = $logo->getRealPath();

        return match ($logo->getMimeType()) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            default => throw new InvalidArgumentException('Unsupported image type.'),
        };
    }
}
