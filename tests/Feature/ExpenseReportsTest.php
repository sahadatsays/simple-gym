<?php

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create(['username' => 'adminuser', 'is_active' => true]);
    $this->admin->assignRole('super-admin');

    $this->expenseCategory = ExpenseCategory::factory()->create(['name' => 'Report Rent']);
    $this->utilityCategory = ExpenseCategory::factory()->create(['name' => 'Report Utilities']);
});

it('shows expense report on the reports hub', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.reports.index'))
        ->assertSuccessful()
        ->assertSee('Expense Report');
});

it('shows expense report with filters and totals', function () {
    Expense::factory()->create([
        'expense_category_id' => $this->expenseCategory->id,
        'expense_number' => 'EXP-REPORT-001',
        'expensed_at' => now(),
        'amount' => 12000,
        'payment_method' => PaymentMethod::Bank,
        'paid_to' => 'Landlord Co',
        'description' => 'Monthly rent',
        'status' => ExpenseStatus::Paid,
        'created_by' => $this->admin->id,
    ]);

    Expense::factory()->create([
        'expense_category_id' => $this->utilityCategory->id,
        'expense_number' => 'EXP-OLD-001',
        'expensed_at' => now()->subMonths(2),
        'amount' => 5000,
        'status' => ExpenseStatus::Paid,
    ]);

    Expense::factory()->cancelled()->create([
        'expense_category_id' => $this->expenseCategory->id,
        'expense_number' => 'EXP-CANCELLED-001',
        'expensed_at' => now(),
        'amount' => 3000,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'expenses',
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
            'expense_category_id' => $this->expenseCategory->id,
            'payment_method' => PaymentMethod::Bank->value,
            'status' => ExpenseStatus::Paid->value,
        ]))
        ->assertSuccessful()
        ->assertSee('Expense Report')
        ->assertSee('EXP-REPORT-001')
        ->assertSee('Monthly rent')
        ->assertSee('Landlord Co')
        ->assertSee('Report Rent')
        ->assertSee('12,000')
        ->assertSee('Total Expense')
        ->assertSee('Number of Expenses')
        ->assertSee('Category-wise Expense Total')
        ->assertSee($this->admin->name)
        ->assertDontSee('EXP-OLD-001')
        ->assertDontSee('EXP-CANCELLED-001');
});

it('shows category-wise expense totals in the expense report', function () {
    Expense::factory()->create([
        'expense_category_id' => $this->expenseCategory->id,
        'expensed_at' => now(),
        'amount' => 10000,
        'status' => ExpenseStatus::Paid,
    ]);

    Expense::factory()->create([
        'expense_category_id' => $this->expenseCategory->id,
        'expensed_at' => now(),
        'amount' => 2500,
        'status' => ExpenseStatus::Paid,
    ]);

    Expense::factory()->create([
        'expense_category_id' => $this->utilityCategory->id,
        'expensed_at' => now(),
        'amount' => 1500,
        'status' => ExpenseStatus::Paid,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'expenses',
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
        ]))
        ->assertSuccessful()
        ->assertSee('Report Rent')
        ->assertSee('Report Utilities')
        ->assertSee('12,500')
        ->assertSee('1,500');
});

it('exports expense report as csv and pdf', function () {
    Expense::factory()->create([
        'expense_category_id' => $this->expenseCategory->id,
        'expensed_at' => now(),
        'amount' => 5000,
        'status' => ExpenseStatus::Paid,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'expenses',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'export' => 'excel',
        ]))
        ->assertSuccessful()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'expenses',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'export' => 'pdf',
        ]))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

it('shows print friendly expense report view', function () {
    Expense::factory()->create([
        'expense_category_id' => $this->expenseCategory->id,
        'expensed_at' => now(),
        'amount' => 10000,
        'status' => ExpenseStatus::Paid,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'expenses',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'export' => 'print',
        ]))
        ->assertSuccessful()
        ->assertSee('Print Report')
        ->assertSee('Expense Report')
        ->assertSee('Category-wise Expense Total');
});

it('denies expense report without permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $this->actingAs($user)
        ->get(route('admin.reports.show', ['report' => 'expenses']))
        ->assertForbidden();
});

it('allows expense reports hub access for users with expense report permission only', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->givePermissionTo('expense-reports.view');

    $this->actingAs($user)
        ->get(route('admin.reports.index'))
        ->assertSuccessful()
        ->assertSee('Expense Report')
        ->assertDontSee('Daily Collection')
        ->assertDontSee('Investment Report');
});
