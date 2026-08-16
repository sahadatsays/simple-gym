<?php

namespace App\Providers;

use App\Contracts\Repositories\ActivityLogRepositoryInterface;
use App\Contracts\Repositories\AssetDisposalRepositoryInterface;
use App\Contracts\Repositories\AssetMaintenanceRepositoryInterface;
use App\Contracts\Repositories\AssetRepositoryInterface;
use App\Contracts\Repositories\ExpenseCategoryRepositoryInterface;
use App\Contracts\Repositories\ExpenseRepositoryInterface;
use App\Contracts\Repositories\GymSettingRepositoryInterface;
use App\Contracts\Repositories\InvestmentCategoryRepositoryInterface;
use App\Contracts\Repositories\InvestmentRepositoryInterface;
use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Contracts\Repositories\MembershipPlanRepositoryInterface;
use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\RfidCardRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Repositories\ActivityLogRepository;
use App\Repositories\AssetDisposalRepository;
use App\Repositories\AssetMaintenanceRepository;
use App\Repositories\AssetRepository;
use App\Repositories\ExpenseCategoryRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\GymSettingRepository;
use App\Repositories\InvestmentCategoryRepository;
use App\Repositories\InvestmentRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\MemberRepository;
use App\Repositories\MembershipPlanRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\ProductRepository;
use App\Repositories\RfidCardRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        MembershipPlanRepositoryInterface::class => MembershipPlanRepository::class,
        MemberRepositoryInterface::class => MemberRepository::class,
        InvoiceRepositoryInterface::class => InvoiceRepository::class,
        PaymentRepositoryInterface::class => PaymentRepository::class,
        InvestmentRepositoryInterface::class => InvestmentRepository::class,
        InvestmentCategoryRepositoryInterface::class => InvestmentCategoryRepository::class,
        ExpenseCategoryRepositoryInterface::class => ExpenseCategoryRepository::class,
        ExpenseRepositoryInterface::class => ExpenseRepository::class,
        AssetRepositoryInterface::class => AssetRepository::class,
        AssetMaintenanceRepositoryInterface::class => AssetMaintenanceRepository::class,
        AssetDisposalRepositoryInterface::class => AssetDisposalRepository::class,
        ProductRepositoryInterface::class => ProductRepository::class,
        RfidCardRepositoryInterface::class => RfidCardRepository::class,
        GymSettingRepositoryInterface::class => GymSettingRepository::class,
        ActivityLogRepositoryInterface::class => ActivityLogRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->bindings as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }
}
