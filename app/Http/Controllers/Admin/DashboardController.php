<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DashboardFilterRequest;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Expense;
use App\Models\Investment;
use App\Models\Locker;
use App\Models\RfidCard;
use App\Services\DashboardAlertService;
use App\Services\DashboardService;
use App\Services\FinancialSummaryService;
use App\Services\GymNotificationService;
use App\Services\GymSettingService;
use App\Services\LockerReservationService;
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
        private FinancialSummaryService $financialSummary,
        private LockerReservationService $lockerReservations,
    ) {}

    public function index(DashboardFilterRequest $request): View
    {
        $range = $this->resolveDateRange($request);
        $alerts = $this->dashboardAlerts->alerts();
        $this->notifications->syncForUser($request->user(), $alerts);

        $currency = $this->gymSettings->get()->currency;
        $canViewInvestments = $request->user()->can('viewAny', Investment::class);
        $canViewAssets = $request->user()->can('viewAny', Asset::class);
        $canViewExpenses = $request->user()->can('viewAny', Expense::class);
        $canViewPayments = $request->user()->can('payments.view');
        $canViewBorrowings = $request->user()->can('viewAny', Borrowing::class);
        $canViewRfidCards = $request->user()->can('viewAny', RfidCard::class);
        $canViewLockers = $request->user()->can('viewAny', Locker::class);

        if ($canViewLockers) {
            $this->lockerReservations->expireDueReservations();
        }

        return view('admin.dashboard', [
            'stats' => $this->dashboard->stats($range, $currency),
            'financialSummary' => ($canViewPayments || $canViewExpenses || $canViewInvestments)
                ? $this->financialSummary->forRange($range)
                : null,
            'canViewFinancialRevenue' => $canViewPayments,
            'canViewFinancialExpenses' => $canViewExpenses,
            'canViewFinancialInvestment' => $canViewInvestments,
            'assetInvestmentStats' => ($canViewInvestments || $canViewAssets)
                ? $this->dashboard->assetInvestmentStats($range)
                : null,
            'expenseStats' => $canViewExpenses
                ? $this->dashboard->expenseStats($range)
                : null,
            'borrowingStats' => $canViewBorrowings
                ? $this->dashboard->borrowingStats($range)
                : null,
            'recentBorrowings' => $canViewBorrowings
                ? $this->dashboard->recentBorrowings($range)
                : Collection::make(),
            'canViewRfidCards' => $canViewRfidCards,
            'canViewLockers' => $canViewLockers,
            'rfidStats' => $canViewRfidCards
                ? $this->dashboard->rfidStats()
                : null,
            'lockerStats' => $canViewLockers
                ? $this->dashboard->lockerStats($range)
                : null,
            'recentCardAssignments' => $canViewRfidCards
                ? $this->dashboard->recentCardAssignments($range)
                : Collection::make(),
            'expiringLockerReservations' => $canViewLockers
                ? $this->dashboard->expiringLockerReservations($range)
                : Collection::make(),
            'recentRegistrations' => $this->dashboard->recentRegistrations($range),
            'recentPayments' => $this->dashboard->recentPayments($range),
            'recentInvestments' => $canViewInvestments
                ? $this->dashboard->recentInvestments($range)
                : Collection::make(),
            'recentAssetPurchases' => $canViewAssets
                ? $this->dashboard->recentAssetPurchases($range)
                : Collection::make(),
            'recentExpenses' => $canViewExpenses
                ? $this->dashboard->recentExpenses($range)
                : Collection::make(),
            'highestExpenseCategories' => $canViewExpenses
                ? $this->dashboard->expensesByCategory($range, 5)
                : Collection::make(),
            'expenseCategorySeries' => $canViewExpenses
                ? $this->dashboard->expenseCategorySeries($range)
                : ['labels' => [], 'values' => []],
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
