<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DashboardFilterRequest;
use App\Services\DashboardAlertService;
use App\Services\DashboardService;
use App\Services\GymNotificationService;
use App\Services\GymSettingService;
use App\Support\DashboardDateRange;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboard,
        private DashboardAlertService $dashboardAlerts,
        private GymNotificationService $notifications,
        private GymSettingService $gymSettings,
    ) {}

    public function index(DashboardFilterRequest $request): View
    {
        $range = $this->resolveDateRange($request);
        $alerts = $this->dashboardAlerts->alerts();
        $this->notifications->syncForUser($request->user(), $alerts);

        $currency = $this->gymSettings->get()->currency;

        return view('admin.dashboard', [
            'stats' => $this->dashboard->stats($range, $currency),
            'recentRegistrations' => $this->dashboard->recentRegistrations($range),
            'recentPayments' => $this->dashboard->recentPayments($range),
            'lowStockProducts' => $this->dashboard->lowStockProducts(),
            'upcomingDueOrders' => $request->user()->can('payments.view')
                ? $this->dashboard->upcomingDueOrders()
                : collect(),
            'revenueSeries' => $this->dashboard->revenueSeries($range),
            'registrationSeries' => $this->dashboard->registrationSeries($range),
            'dateRange' => $range,
            'filters' => $range->queryParameters(),
            'unreadNotificationsCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    private function resolveDateRange(DashboardFilterRequest $request): DashboardDateRange
    {
        if ($request->query->count() === 0) {
            return DashboardDateRange::default();
        }

        return $request->dateRange();
    }
}
