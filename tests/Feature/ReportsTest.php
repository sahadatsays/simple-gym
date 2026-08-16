<?php

use App\Enums\ExpenseStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Investment;
use App\Models\InvestmentCategory;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\User;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create([
        'username' => 'adminuser',
        'is_active' => true,
    ]);
    $this->admin->assignRole('super-admin');

    $this->plan = MembershipPlan::factory()->create();
});

it('shows the reports hub for authorized users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.reports.index'))
        ->assertSuccessful()
        ->assertSee('Reports')
        ->assertSee('Daily Collection')
        ->assertSee('Stock Report');
});

it('shows financial summary report with operating totals', function () {
    $investmentCategory = InvestmentCategory::factory()->create();
    $expenseCategory = ExpenseCategory::factory()->create();
    $reportDate = now()->subYear()->startOfMonth();
    $from = $reportDate->copy()->startOfMonth()->toDateString();
    $to = $reportDate->copy()->endOfMonth()->toDateString();

    Payment::factory()->create([
        'type' => PaymentType::MembershipFee,
        'status' => PaymentStatus::Completed,
        'amount' => 6000,
        'paid_at' => $reportDate,
    ]);

    Payment::factory()->create([
        'type' => PaymentType::PosSale,
        'status' => PaymentStatus::Completed,
        'amount' => 1500,
        'paid_at' => $reportDate,
    ]);

    Expense::factory()->create([
        'expense_category_id' => $expenseCategory->id,
        'expensed_at' => $reportDate,
        'amount' => 2500,
        'status' => ExpenseStatus::Paid,
    ]);

    Investment::factory()->create([
        'investment_category_id' => $investmentCategory->id,
        'invested_at' => $reportDate,
        'amount' => 12000,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'financial-summary',
            'from_date' => $from,
            'to_date' => $to,
        ]))
        ->assertSuccessful()
        ->assertSee('Financial Summary')
        ->assertSee('Revenue')
        ->assertSee('Expenses')
        ->assertSee('Net Operating Result')
        ->assertSee('Owner Investment')
        ->assertSee('7,500')
        ->assertSee('2,500')
        ->assertSee('5,000')
        ->assertSee('12,000');
});

it('shows daily collection report with totals', function () {
    Payment::factory()->membershipFee()->create([
        'amount' => 1500,
        'paid_at' => now(),
    ]);

    Payment::factory()->posSale()->create([
        'amount' => 250,
        'paid_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'daily-collection',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
        ]))
        ->assertSuccessful()
        ->assertSee('Daily Collection')
        ->assertSee('Total Collection')
        ->assertSee('Export PDF');
});

it('shows expired members report', function () {
    Member::factory()->create([
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Expired,
        'membership_expires_at' => now()->subDays(5),
        'name' => 'Expired Member Test',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'expired-members',
            'from_date' => now()->subMonth()->toDateString(),
            'to_date' => now()->toDateString(),
        ]))
        ->assertSuccessful()
        ->assertSee('Expired Members')
        ->assertSee('Expired Member Test');
});

it('shows product sales report', function () {
    $product = Product::factory()->create(['name' => 'Report Protein']);

    ProductSale::factory()->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 100,
        'line_total' => 200,
        'sold_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'product-sales',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
        ]))
        ->assertSuccessful()
        ->assertSee('Product Sales')
        ->assertSee('Report Protein');
});

it('exports daily collection as csv excel', function () {
    Payment::factory()->membershipFee()->create([
        'amount' => 500,
        'paid_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'daily-collection',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'export' => 'excel',
        ]));

    $response
        ->assertSuccessful()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('exports daily collection as pdf', function () {
    Payment::factory()->membershipFee()->create([
        'amount' => 500,
        'paid_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'daily-collection',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'export' => 'pdf',
        ]));

    $response
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

it('shows print friendly report view', function () {
    Payment::factory()->membershipFee()->create([
        'amount' => 500,
        'paid_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'daily-collection',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'export' => 'print',
        ]))
        ->assertSuccessful()
        ->assertSee('Print Report')
        ->assertSee('Daily Collection');
});

it('denies reports without permission', function () {
    $user = User::factory()->create(['username' => 'basicuser', 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('admin.reports.index'))
        ->assertForbidden();
});

it('rejects unknown report types', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', ['report' => 'unknown-report']))
        ->assertNotFound();
});
