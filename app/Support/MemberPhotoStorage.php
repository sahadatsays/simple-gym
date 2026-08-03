<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class MemberPhotoStorage
{
    private const int MAX_WIDTH = 800;

    private const int MAX_HEIGHT = 800;

    private const int JPEG_QUALITY = 85;

    public function store(UploadedFile $photo): string
    {
        $contents = $this->optimize($photo);
        $path = 'members/photos/'.Str::uuid().'.jpg';

        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
    }

    private function optimize(UploadedFile $photo): string
    {
        if (! extension_loaded('gd')) {
            return file_get_contents($photo->getRealPath()) ?: throw new RuntimeException('Unable to read uploaded photo.');
        }

        $source = $this->createImageResource($photo);

        if ($source === false) {
            return file_get_contents($photo->getRealPath()) ?: throw new RuntimeException('Unable to read uploaded photo.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height);
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($canvas === false) {
            imagedestroy($source);

            throw new RuntimeException('Unable to prepare the uploaded photo.');
        }

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($canvas, null, self::JPEG_QUALITY);
        $contents = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        if ($contents === false) {
            throw new RuntimeException('Unable to optimize the uploaded photo.');
        }

        return $contents;
    }

    private function createImageResource(UploadedFile $photo): \GdImage|false
    {
        $path = $photo->getRealPath();

        return match ($photo->getMimeType()) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            default => throw new InvalidArgumentException('Unsupported image type.'),
        };
    }
}
