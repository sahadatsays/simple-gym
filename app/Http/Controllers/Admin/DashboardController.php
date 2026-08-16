<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DashboardFilterRequest;
use App\Models\Asset;
use App\Models\Investment;
use App\Services\DashboardAlertService;
use App\Services\DashboardService;
use App\Services\GymNotificationService;
use App\Services\GymSettingService;
use App\Support\DashboardDateRange;
use Illuminate\Support\Collection;
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
        $canViewInvestments = $request->user()->can('viewAny', Investment::class);
        $canViewAssets = $request->user()->can('viewAny', Asset::class);

        return view('admin.dashboard', [
            'stats' => $this->dashboard->stats($range, $currency),
            'assetInvestmentStats' => ($canViewInvestments || $canViewAssets)
                ? $this->dashboard->assetInvestmentStats($range)
                : null,
            'recentRegistrations' => $this->dashboard->recentRegistrations($range),
            'recentPayments' => $this->dashboard->recentPayments($range),
            'recentInvestments' => $canViewInvestments
                ? $this->dashboard->recentInvestments($range)
                : Collection::make(),
            'recentAssetPurchases' => $canViewAssets
                ? $this->dashboard->recentAssetPurchases($range)
                : Collection::make(),
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
