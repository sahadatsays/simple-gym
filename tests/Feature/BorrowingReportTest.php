<?php

use App\Enums\BorrowingStatus;
use App\Models\Borrowing;
use App\Models\BorrowingRepayment;
use App\Models\User;
use App\Services\FinancialSummaryService;
use App\Support\DashboardDateRange;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create(['username' => 'adminuser', 'is_active' => true]);
    $this->admin->assignRole('super-admin');
});

it('shows the borrowing report on the reports hub', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.reports.index'))
        ->assertSuccessful()
        ->assertSee('Borrowing Report');
});

it('filters borrowings and totals repaid and remaining amounts', function () {
    $borrowing = Borrowing::factory()->create([
        'borrowing_no' => 'BOR-REPORT-001',
        'lender_name' => 'Karim Traders',
        'borrowing_date' => now()->toDateString(),
        'due_date' => now()->addDays(10)->toDateString(),
        'amount' => 2000,
        'status' => BorrowingStatus::PartiallyRepaid,
    ]);

    BorrowingRepayment::factory()->create([
        'borrowing_id' => $borrowing->id,
        'amount' => 500,
        'repayment_date' => now()->toDateString(),
    ]);

    Borrowing::factory()->create([
        'borrowing_no' => 'BOR-REPORT-OLD',
        'lender_name' => 'Karim Traders',
        'borrowing_date' => now()->subMonths(2)->toDateString(),
        'amount' => 9000,
        'status' => BorrowingStatus::Active,
    ]);

    Borrowing::factory()->create([
        'borrowing_no' => 'BOR-REPORT-OTHER',
        'lender_name' => 'Rahim Store',
        'borrowing_date' => now()->toDateString(),
        'amount' => 7000,
        'status' => BorrowingStatus::Active,
    ]);

    Borrowing::factory()->cancelled()->create([
        'borrowing_no' => 'BOR-REPORT-CANCELLED',
        'lender_name' => 'Karim Traders',
        'borrowing_date' => now()->toDateString(),
        'amount' => 3000,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'borrowings',
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
            'status' => BorrowingStatus::PartiallyRepaid->value,
            'search' => 'Karim',
        ]))
        ->assertSuccessful()
        ->assertSee('Borrowing Report')
        ->assertSee('Borrowing No')
        ->assertSee('Original Amount')
        ->assertSee('Total Repaid')
        ->assertSee('Remaining Amount')
        ->assertSee('BOR-REPORT-001')
        ->assertSee('Karim Traders')
        ->assertSee('2,000')
        ->assertSee('500')
        ->assertSee('1,500')
        ->assertSee('Partially Repaid')
        ->assertSee('Total Borrowed')
        ->assertSee('Total Outstanding')
        ->assertDontSee('BOR-REPORT-OLD')
        ->assertDontSee('BOR-REPORT-OTHER')
        ->assertDontSee('BOR-REPORT-CANCELLED')
        ->assertDontSee('9,000')
        ->assertDontSee('7,000')
        ->assertDontSee('3,000');
});

it('keeps borrowing totals out of the financial summary', function () {
    Borrowing::factory()->create([
        'borrowing_date' => now()->toDateString(),
        'amount' => 8000,
        'status' => BorrowingStatus::Active,
    ]);

    $summary = app(FinancialSummaryService::class)->forRange(DashboardDateRange::fromInput([
        'preset' => 'custom',
        'from_date' => now()->startOfMonth()->toDateString(),
        'to_date' => now()->toDateString(),
    ]));

    expect($summary['revenue'])->toBe(0.0)
        ->and($summary['expenses'])->toBe(0.0)
        ->and($summary['net_operating_result'])->toBe(0.0);
});

it('exports and prints the borrowing report', function () {
    Borrowing::factory()->create([
        'borrowing_no' => 'BOR-REPORT-002',
        'lender_name' => 'Office Depot',
        'borrowing_date' => now()->toDateString(),
        'amount' => 1500,
        'status' => BorrowingStatus::Active,
    ]);

    $excel = $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'borrowings',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'export' => 'excel',
        ]));

    $excel->assertSuccessful()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($excel->streamedContent())
        ->toContain('Borrowing No')
        ->toContain('BOR-REPORT-002')
        ->toContain('Office Depot');

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'borrowings',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'export' => 'pdf',
        ]))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'borrowings',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'export' => 'print',
        ]))
        ->assertSuccessful()
        ->assertSee('Print Report')
        ->assertSee('Borrowing Report')
        ->assertSee('BOR-REPORT-002')
        ->assertSee('1,500');
});

it('hides the borrowing report without borrowing permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->givePermissionTo('reports.view');

    $this->actingAs($user)
        ->get(route('admin.reports.index'))
        ->assertSuccessful()
        ->assertDontSee('Borrowing Report')
        ->assertSee('Daily Collection');

    $this->actingAs($user)
        ->get(route('admin.reports.show', ['report' => 'borrowings']))
        ->assertForbidden();
});

it('shows only the borrowing report when the user can view borrowings', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->givePermissionTo('borrowings.view');

    $this->actingAs($user)
        ->get(route('admin.reports.index'))
        ->assertSuccessful()
        ->assertSee('Borrowing Report')
        ->assertDontSee('Daily Collection')
        ->assertDontSee('Expense Report');
});
