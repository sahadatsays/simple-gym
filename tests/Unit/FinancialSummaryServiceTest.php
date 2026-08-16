<?php

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Investment;
use App\Models\InvestmentCategory;
use App\Models\Payment;
use App\Services\FinancialSummaryService;
use App\Support\DashboardDateRange;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates operating revenue expenses net result and owner investment for a range', function () {
    $investmentCategory = InvestmentCategory::factory()->create();
    $expenseCategory = ExpenseCategory::factory()->create();
    $assetCategory = AssetCategory::factory()->create();

    Payment::factory()->create([
        'type' => PaymentType::MembershipFee,
        'status' => PaymentStatus::Completed,
        'amount' => 5000,
        'paid_at' => now(),
    ]);

    Payment::factory()->create([
        'type' => PaymentType::AdmissionFee,
        'status' => PaymentStatus::Completed,
        'amount' => 1000,
        'paid_at' => now(),
    ]);

    Payment::factory()->create([
        'type' => PaymentType::PosSale,
        'status' => PaymentStatus::Completed,
        'amount' => 2500,
        'paid_at' => now(),
    ]);

    Payment::factory()->create([
        'type' => PaymentType::PosSale,
        'status' => PaymentStatus::Failed,
        'amount' => 9999,
        'paid_at' => now(),
    ]);

    Expense::factory()->create([
        'expense_category_id' => $expenseCategory->id,
        'expensed_at' => now(),
        'amount' => 3000,
        'status' => ExpenseStatus::Paid,
        'payment_method' => PaymentMethod::Cash,
    ]);

    Expense::factory()->cancelled()->create([
        'expense_category_id' => $expenseCategory->id,
        'expensed_at' => now(),
        'amount' => 500,
    ]);

    Investment::factory()->create([
        'investment_category_id' => $investmentCategory->id,
        'invested_at' => now(),
        'amount' => 20000,
    ]);

    Asset::factory()->create([
        'asset_category_id' => $assetCategory->id,
        'purchased_at' => now(),
        'purchase_price' => 50000,
    ]);

    $summary = app(FinancialSummaryService::class)->forRange(DashboardDateRange::default());

    expect($summary['membership_payments'])->toBe(6000.0)
        ->and($summary['pos_sales'])->toBe(2500.0)
        ->and($summary['revenue'])->toBe(8500.0)
        ->and($summary['expenses'])->toBe(3000.0)
        ->and($summary['net_operating_result'])->toBe(5500.0)
        ->and($summary['owner_investment'])->toBe(20000.0);
});

it('excludes records outside the selected range', function () {
    $expenseCategory = ExpenseCategory::factory()->create();

    Payment::factory()->create([
        'type' => PaymentType::MembershipFee,
        'status' => PaymentStatus::Completed,
        'amount' => 4000,
        'paid_at' => now()->subMonths(2),
    ]);

    Expense::factory()->create([
        'expense_category_id' => $expenseCategory->id,
        'expensed_at' => now()->subMonths(2),
        'amount' => 1500,
        'status' => ExpenseStatus::Paid,
    ]);

    $summary = app(FinancialSummaryService::class)->forRange(DashboardDateRange::default());

    expect($summary['revenue'])->toBe(0.0)
        ->and($summary['expenses'])->toBe(0.0)
        ->and($summary['net_operating_result'])->toBe(0.0);
});
