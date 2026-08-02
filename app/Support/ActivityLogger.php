<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $properties = [],
    ): void {
        defer(function () use ($action, $subject, $description, $properties): void {
            ActivityLog::query()->create([
                'user_id' => auth()->id(),
                'action' => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'properties' => $properties ?: null,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        });
    }
}
