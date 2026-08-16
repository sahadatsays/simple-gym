<?php

namespace App\Providers;

use App\Enums\PaymentMethod;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDisposal;
use App\Models\AssetMaintenance;
use App\Models\AttendanceLog;
use App\Models\Category;
use App\Models\ExpenseCategory;
use App\Models\GymSetting;
use App\Models\Investment;
use App\Models\InvestmentCategory;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\ZktecoDevice;
use App\Policies\AssetCategoryPolicy;
use App\Policies\AssetDisposalPolicy;
use App\Policies\AssetMaintenancePolicy;
use App\Policies\AssetPolicy;
use App\Policies\AttendanceLogPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ExpenseCategoryPolicy;
use App\Policies\GymSettingPolicy;
use App\Policies\InvestmentCategoryPolicy;
use App\Policies\InvestmentPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\ZktecoDevicePolicy;
use App\Support\MenuBuilder;
use App\Support\MoneyFormatter;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers());

        Gate::policy(AttendanceLog::class, AttendanceLogPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(GymSetting::class, GymSettingPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(ZktecoDevice::class, ZktecoDevicePolicy::class);
        Gate::policy(Investment::class, InvestmentPolicy::class);
        Gate::policy(InvestmentCategory::class, InvestmentCategoryPolicy::class);
        Gate::policy(ExpenseCategory::class, ExpenseCategoryPolicy::class);
        Gate::policy(Asset::class, AssetPolicy::class);
        Gate::policy(AssetCategory::class, AssetCategoryPolicy::class);
        Gate::policy(AssetMaintenance::class, AssetMaintenancePolicy::class);
        Gate::policy(AssetDisposal::class, AssetDisposalPolicy::class);

        $this->shareGymContext();

        View::composer(['layouts.admin', 'layouts.partials.sidebar'], function ($view): void {
            $view->with('menuGroups', MenuBuilder::authorizedGroups());
        });
    }

    private function shareGymContext(): void
    {
        $composer = function ($view): void {
            $defaults = config('gym.defaults');
            $currency = $defaults['currency'];
            $gymName = $defaults['name'];
            $gymLogoUrl = null;
            $receiptFooter = $defaults['receipt_footer'] ?? null;
            $enabledPaymentMethods = PaymentMethod::options();

            if (Schema::hasTable('gym_settings')) {
                $settings = GymSetting::query()->first();

                if ($settings !== null) {
                    $currency = $settings->currency ?? $currency;
                    $gymName = $settings->name ?? $gymName;
                    $gymLogoUrl = $settings->logo_url;
                    $receiptFooter = $settings->receipt_footer ?? $receiptFooter;
                    $enabledPaymentMethods = $settings->paymentMethodOptions();
                }
            }

            $view->with([
                'gymName' => $gymName,
                'gymCurrency' => $currency,
                'currencySymbol' => MoneyFormatter::symbol($currency),
                'gymLogoUrl' => $gymLogoUrl,
                'receiptFooter' => $receiptFooter,
                'enabledPaymentMethods' => $enabledPaymentMethods,
            ]);
        };

        View::composer('layouts.partials.topbar', function ($view): void {
            $user = auth()->user();

            if ($user === null || ! $user->can('dashboard.view')) {
                $view->with([
                    'unreadNotificationsCount' => 0,
                    'recentNotifications' => collect(),
                ]);

                return;
            }

            $view->with([
                'unreadNotificationsCount' => $user->unreadNotifications()->count(),
                'recentNotifications' => $user->unreadNotifications()->latest()->limit(5)->get(),
            ]);
        });

        View::composer([
            'layouts.admin',
            'admin.*',
            'admin.*.*',
            'admin.*.*.*',
        ], $composer);
    }
}
