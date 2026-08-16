<?php

use App\Enums\AssetStatus;
use App\Enums\ExpenseStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetMaintenance;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Investment;
use App\Models\InvestmentCategory;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\DashboardService;
use App\Support\DashboardDateRange;
use Database\Seeders\DashboardSeeder;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->user = User::factory()->create([
        'is_active' => true,
    ]);

    $this->user->assignRole('super-admin');
    $this->seed(DashboardSeeder::class);
});

it('displays dashboard in bangla when locale is bn', function () {
    $this->actingAs($this->user)
        ->withSession(['locale' => 'bn'])
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('ব্যবসায়িক ড্যাশবোর্ড', false)
        ->assertSee('নতুন নিবন্ধন', false)
        ->assertSee('সাম্প্রতিক পেমেন্ট', false)
        ->assertSee('তারিখের পরিসর', false);
});

it('displays dashboard widgets for authorized users', function () {
    $this->actingAs($this->user)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Business Dashboard')
        ->assertSee('Register')
        ->assertSee('RFID Card')
        ->assertSee('POS')
        ->assertSee('Orders')
        ->assertSee('Payment')
        ->assertSee('Renew')
        ->assertSee('Date Range')
        ->assertSee('New Registrations')
        ->assertSee('Active Members')
        ->assertSee('Period Revenue')
        ->assertSee('Product Sales')
        ->assertSee('Low Stock Items')
        ->assertSee('Revenue Trend')
        ->assertSee('Registration Trend')
        ->assertSee('Recent Payments')
        ->assertSee('Recent Registrations')
        ->assertSee('Low Stock Products')
        ->assertSee('Upcoming Due Orders');
});

it('displays asset and investment dashboard widgets for authorized users', function () {
    $this->actingAs($this->user)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Assets & Investments')
        ->assertSee('Total Owner Investment')
        ->assertSee('Total Asset Purchase Value')
        ->assertSee('Current Asset Value')
        ->assertSee('Total Maintenance Cost')
        ->assertSee('Active Assets')
        ->assertSee('Assets Under Maintenance')
        ->assertSee('Assets Requiring Maintenance')
        ->assertSee('Recent Investments')
        ->assertSee('Recent Asset Purchases');
});

it('displays financial summary on the dashboard', function () {
    $investmentCategory = InvestmentCategory::factory()->create();
    $expenseCategory = ExpenseCategory::factory()->create();
    $reportDate = now()->subYear()->startOfMonth();

    Payment::factory()->create([
        'type' => PaymentType::MembershipFee,
        'status' => PaymentStatus::Completed,
        'amount' => 5000,
        'paid_at' => $reportDate,
    ]);

    Payment::factory()->create([
        'type' => PaymentType::PosSale,
        'status' => PaymentStatus::Completed,
        'amount' => 2000,
        'paid_at' => $reportDate,
    ]);

    Expense::factory()->create([
        'expense_category_id' => $expenseCategory->id,
        'expensed_at' => $reportDate,
        'amount' => 3000,
        'status' => ExpenseStatus::Paid,
    ]);

    Investment::factory()->create([
        'investment_category_id' => $investmentCategory->id,
        'invested_at' => $reportDate,
        'amount' => 10000,
    ]);

    $from = $reportDate->copy()->startOfMonth()->toDateString();
    $to = $reportDate->copy()->endOfMonth()->toDateString();

    $this->actingAs($this->user)
        ->get(route('admin.dashboard', [
            'preset' => 'custom',
            'from_date' => $from,
            'to_date' => $to,
        ]))
        ->assertSuccessful()
        ->assertSee('Financial Summary')
        ->assertSee('Revenue')
        ->assertSee('Net Operating Result')
        ->assertSee('Owner Investment')
        ->assertSee('7,000')
        ->assertSee('3,000')
        ->assertSee('4,000')
        ->assertSee('10,000');
});

it('displays expense dashboard widgets for authorized users', function () {
    $this->actingAs($this->user)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Expenses')
        ->assertSee('Total Expenses')
        ->assertSee('Expense This Month')
        ->assertSee('Expense Today')
        ->assertSee('Expense by Category')
        ->assertSee('Highest Expense Categories')
        ->assertSee('Recent Expenses');
});

it('filters expense dashboard metrics by date range', function () {
    $rentCategory = ExpenseCategory::factory()->create(['name' => 'Dashboard Rent Category']);
    $utilityCategory = ExpenseCategory::factory()->create(['name' => 'Dashboard Utility Category']);

    Expense::factory()->create([
        'expense_category_id' => $rentCategory->id,
        'expensed_at' => now()->subDays(20),
        'amount' => 10000,
        'expense_number' => 'EXP-OLD-001',
    ]);

    Expense::factory()->create([
        'expense_category_id' => $utilityCategory->id,
        'expensed_at' => now()->subDay(),
        'amount' => 2500,
        'expense_number' => 'EXP-NEW-001',
    ]);

    Expense::factory()->cancelled()->create([
        'expense_category_id' => $rentCategory->id,
        'expensed_at' => now()->subDay(),
        'amount' => 9999,
        'expense_number' => 'EXP-CANCELLED-001',
    ]);

    $from = now()->subDays(7)->toDateString();
    $to = now()->toDateString();

    $this->actingAs($this->user)
        ->get(route('admin.dashboard', [
            'preset' => 'custom',
            'from_date' => $from,
            'to_date' => $to,
        ]))
        ->assertSuccessful()
        ->assertSee('EXP-NEW-001')
        ->assertDontSee('EXP-OLD-001')
        ->assertDontSee('EXP-CANCELLED-001')
        ->assertSee('2,500')
        ->assertSee('Dashboard Utility Category')
        ->assertDontSee('Dashboard Rent Category');
});

it('hides expense widgets without module permissions', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertDontSee('Expenses')
        ->assertDontSee('Recent Expenses')
        ->assertDontSee('Highest Expense Categories');
});

it('filters asset and investment dashboard metrics by date range', function () {
    $investmentCategory = InvestmentCategory::factory()->create();
    $assetCategory = AssetCategory::factory()->create();

    Investment::factory()->create([
        'investment_category_id' => $investmentCategory->id,
        'invested_at' => now()->subDays(20),
        'amount' => 10000,
        'investment_number' => 'INV-OLD-001',
    ]);

    Investment::factory()->create([
        'investment_category_id' => $investmentCategory->id,
        'invested_at' => now()->subDays(2),
        'amount' => 25000,
        'investment_number' => 'INV-NEW-001',
    ]);

    Asset::factory()->create([
        'asset_category_id' => $assetCategory->id,
        'name' => 'Old Treadmill',
        'purchased_at' => now()->subDays(20),
        'purchase_price' => 50000,
        'current_value' => 40000,
        'status' => AssetStatus::Active,
    ]);

    $recentAsset = Asset::factory()->create([
        'asset_category_id' => $assetCategory->id,
        'name' => 'Recent Bike',
        'purchased_at' => now()->subDay(),
        'purchase_price' => 15000,
        'current_value' => 15000,
        'status' => AssetStatus::Active,
    ]);

    AssetMaintenance::factory()->create([
        'asset_id' => $recentAsset->id,
        'maintained_at' => now()->subDays(20),
        'cost' => 500,
    ]);

    AssetMaintenance::factory()->create([
        'asset_id' => $recentAsset->id,
        'maintained_at' => now()->subDay(),
        'cost' => 1500,
    ]);

    $from = now()->subDays(7)->toDateString();
    $to = now()->toDateString();

    $this->actingAs($this->user)
        ->get(route('admin.dashboard', [
            'preset' => 'custom',
            'from_date' => $from,
            'to_date' => $to,
        ]))
        ->assertSuccessful()
        ->assertSee('INV-NEW-001')
        ->assertDontSee('INV-OLD-001')
        ->assertSee('Recent Bike')
        ->assertDontSee('Old Treadmill')
        ->assertSee('25,000')
        ->assertSee('15,000')
        ->assertSee('1,500')
        ->assertSee('55,000');
});

it('counts assets requiring maintenance from the latest schedule', function () {
    $assetCategory = AssetCategory::factory()->create();

    $dueAsset = Asset::factory()->create([
        'asset_category_id' => $assetCategory->id,
        'status' => AssetStatus::Active,
    ]);

    $scheduledAsset = Asset::factory()->create([
        'asset_category_id' => $assetCategory->id,
        'status' => AssetStatus::Active,
    ]);

    AssetMaintenance::factory()->create([
        'asset_id' => $dueAsset->id,
        'maintained_at' => now()->subMonths(2),
        'next_maintenance_at' => now()->subDay(),
    ]);

    AssetMaintenance::factory()->create([
        'asset_id' => $scheduledAsset->id,
        'maintained_at' => now()->subMonths(2),
        'next_maintenance_at' => now()->subMonth(),
    ]);

    AssetMaintenance::factory()->create([
        'asset_id' => $scheduledAsset->id,
        'maintained_at' => now()->subWeek(),
        'next_maintenance_at' => now()->addMonth(),
    ]);

    expect(app(DashboardService::class)->assetInvestmentStats(DashboardDateRange::default())['assets_requiring_maintenance'])
        ->toBe(1);
});

it('hides asset and investment widgets without module permissions', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertDontSee('Assets & Investments')
        ->assertDontSee('Recent Investments')
        ->assertDontSee('Recent Asset Purchases');
});

it('shows upcoming due pos orders on the dashboard', function () {
    $member = Member::factory()->create();
    $product = Product::factory()->create(['selling_price' => 120, 'stock' => 5]);

    $this->actingAs($this->user)
        ->post(route('admin.pos.store'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'member_id' => $member->id,
            'amount_paid' => 0,
            'due_at' => now()->addDays(3)->toDateString(),
        ])
        ->assertRedirect();

    $invoice = Invoice::query()->latest('id')->first();

    $this->actingAs($this->user)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Upcoming Due Orders')
        ->assertSee($invoice->invoice_number)
        ->assertSee($member->name);
});

it('filters dashboard metrics by date range preset', function () {
    $this->actingAs($this->user)
        ->get(route('admin.dashboard', ['preset' => 'today']))
        ->assertSuccessful()
        ->assertSee('Today')
        ->assertSee('Business Dashboard');
});

it('filters dashboard metrics by custom date range', function () {
    $from = now()->subDays(10)->toDateString();
    $to = now()->toDateString();

    $this->actingAs($this->user)
        ->get(route('admin.dashboard', [
            'preset' => 'custom',
            'from_date' => $from,
            'to_date' => $to,
        ]))
        ->assertSuccessful()
        ->assertSee(now()->parse($from)->format('M j, Y'));
});

it('forbids dashboard access without permission', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});
