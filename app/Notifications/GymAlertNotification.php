<?php

namespace App\Notifications;

use App\Enums\AlertType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GymAlertNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, array{name: string, detail?: string|null}>  $items
     */
    public function __construct(
        public AlertType $alertType,
        public string $title,
        public string $message,
        public int $count,
        public ?string $actionUrl = null,
        public array $items = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'key' => $this->alertType->value,
            'type' => $this->alertType->value,
            'title' => $this->title,
            'message' => $this->message,
            'count' => $this->count,
            'severity' => $this->alertType->severity(),
            'action_url' => $this->actionUrl,
            'items' => $this->items,
        ];
    }
}
