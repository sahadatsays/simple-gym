<?php

namespace App\Providers;

use App\Enums\PaymentMethod;
use App\Models\GymSetting;
use App\Models\Invoice;
use App\Policies\GymSettingPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Support\MenuBuilder;
use App\Support\MoneyFormatter;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(GymSetting::class, GymSettingPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);

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
