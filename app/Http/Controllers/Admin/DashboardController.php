<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardAlertService;
use App\Services\DashboardService;
use App\Services\GymNotificationService;
use App\Services\GymSettingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboard,
        private DashboardAlertService $dashboardAlerts,
        private GymNotificationService $notifications,
        private GymSettingService $gymSettings,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizePermission('dashboard.view');

        $alerts = $this->dashboardAlerts->alerts();
        $this->notifications->syncForUser($request->user(), $alerts);

        $currency = $this->gymSettings->get()->currency;
        $unreadNotificationsCount = $request->user()->unreadNotifications()->count();

        return view('admin.dashboard', [
            'stats' => $this->dashboard->stats($currency),
            'recentMembers' => $this->dashboard->recentMembers(),
            'recentPayments' => $this->dashboard->recentPayments(),
            'monthlyRevenue' => $this->dashboard->monthlyRevenue(),
            'membershipGrowth' => $this->dashboard->membershipGrowth(),
            'alerts' => $alerts,
            'unreadNotificationsCount' => $unreadNotificationsCount,
        ]);
    }
}
