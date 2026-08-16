<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class InvestmentAttachmentStorage
{
    /**
     * @var list<string>
     */
    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

    public function store(UploadedFile $attachment): string
    {
        $extension = strtolower($attachment->getClientOriginalExtension());

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new InvalidArgumentException('Unsupported attachment type.');
        }

        $path = 'investments/attachments/'.Str::uuid().'.'.$extension;

        Storage::disk('public')->putFileAs(
            'investments/attachments',
            $attachment,
            basename($path),
        );

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
    }
}
