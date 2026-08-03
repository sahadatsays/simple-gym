<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GymNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private GymNotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizePermission('dashboard.view');

        $this->notifications->syncForUser($request->user());

        return view('admin.notifications.index', [
            'notifications' => $request->user()
                ->notifications()
                ->latest()
                ->paginate(config('gym.pagination.per_page'))
                ->withQueryString(),
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $this->authorizePermission('dashboard.view');

        $record = $this->findNotification($request, $notification);

        if ($record->read_at === null) {
            $record->markAsRead();
        }

        $actionUrl = $record->data['action_url'] ?? null;

        if (filled($actionUrl)) {
            return redirect($actionUrl);
        }

        return redirect()->route('admin.notifications.index');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $this->authorizePermission('dashboard.view');

        $request->user()->unreadNotifications->markAsRead();

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', 'All notifications marked as read.');
    }

    private function findNotification(Request $request, string $notificationId): DatabaseNotification
    {
        return $request->user()
            ->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();
    }
}
