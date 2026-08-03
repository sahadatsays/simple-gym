<?php

namespace App\Services;

use App\Enums\AlertType;
use App\Models\User;
use App\Notifications\GymAlertNotification;
use Illuminate\Support\Collection;

class GymNotificationService
{
    public function __construct(
        private DashboardAlertService $dashboardAlerts,
    ) {}

    public function syncForUser(User $user): void
    {
        if (! $user->can('dashboard.view')) {
            return;
        }

        $alerts = $this->dashboardAlerts->alerts();

        foreach ($alerts as $alert) {
            $this->syncAlert($user, $alert);
        }

        $this->removeStaleAlerts($user, $alerts);
    }

    public function syncForAllUsers(): int
    {
        $count = 0;

        User::query()
            ->where('is_active', true)
            ->each(function (User $user) use (&$count): void {
                $this->syncForUser($user);
                $count++;
            });

        return $count;
    }

    /**
     * @param  array{
     *     type: AlertType,
     *     title: string,
     *     message: string,
     *     count: int,
     *     severity: string,
     *     action_url: string,
     *     items: array<int, array{name: string, detail: ?string}>
     * }  $alert
     */
    private function syncAlert(User $user, array $alert): void
    {
        $key = $alert['type']->value;

        $latest = $user->notifications()
            ->where('type', GymAlertNotification::class)
            ->where('data->key', $key)
            ->latest()
            ->first();

        if ($latest !== null) {
            $latestCount = (int) ($latest->data['count'] ?? 0);

            if ($latestCount === $alert['count']) {
                return;
            }

            if ($latest->read_at === null) {
                $latest->delete();
            }
        }

        $user->notify(new GymAlertNotification(
            alertType: $alert['type'],
            title: $alert['title'],
            message: $alert['message'],
            count: $alert['count'],
            actionUrl: $alert['action_url'],
            items: $alert['items'],
        ));
    }

    /**
     * @param  Collection<int, array{type: AlertType}>  $alerts
     */
    private function removeStaleAlerts(User $user, Collection $alerts): void
    {
        $activeKeys = $alerts->map(fn (array $alert): string => $alert['type']->value)->all();

        $user->notifications()
            ->where('type', GymAlertNotification::class)
            ->get()
            ->each(function ($notification) use ($activeKeys): void {
                $key = $notification->data['key'] ?? null;

                if ($key !== null && ! in_array($key, $activeKeys, true)) {
                    $notification->delete();
                }
            });
    }
}
