<?php

namespace App\Providers;

use App\Models\GymSetting;
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

        $this->shareGymContext();

        View::composer('layouts.admin', function ($view): void {
            $view->with('menuItems', MenuBuilder::authorizedItems());
        });
    }

    private function shareGymContext(): void
    {
        $composer = function ($view): void {
            $currency = config('gym.defaults.currency');
            $gymName = config('gym.defaults.name');

            if (Schema::hasTable('gym_settings')) {
                $settings = GymSetting::query()->first();
                $currency = $settings?->currency ?? $currency;
                $gymName = $settings?->name ?? $gymName;
            }

            $view->with([
                'gymName' => $gymName,
                'gymCurrency' => $currency,
                'currencySymbol' => MoneyFormatter::symbol($currency),
            ]);
        };

        View::composer([
            'layouts.admin',
            'admin.*',
            'admin.*.*',
            'admin.*.*.*',
        ], $composer);
    }
}
