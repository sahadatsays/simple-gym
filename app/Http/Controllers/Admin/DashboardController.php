<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\GymSettingService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboard,
        private GymSettingService $gymSettings,
    ) {}

    public function index(): View
    {
        $this->authorizePermission('dashboard.view');

        $currency = $this->gymSettings->get()->currency;

        return view('admin.dashboard', [
            'stats' => $this->dashboard->stats($currency),
            'recentMembers' => $this->dashboard->recentMembers(),
            'recentPayments' => $this->dashboard->recentPayments(),
            'monthlyRevenue' => $this->dashboard->monthlyRevenue(),
            'membershipGrowth' => $this->dashboard->membershipGrowth(),
        ]);
    }
}
