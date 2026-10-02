<?php

use App\Enums\BorrowingStatus;
use App\Enums\PaymentMethod;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\BorrowingRepayment;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\User;
use App\Repositories\BorrowingRepository;
use App\Support\MenuBuilder;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create(['username' => 'adminuser', 'is_active' => true]);
    $this->admin->assignRole('super-admin');
});

it('lists borrowings for authorized users', function () {
    Borrowing::factory()->create([
        'lender_name' => 'Karim Traders',
        'purpose' => 'Treadmill repair',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.borrowings.index'))
        ->assertSuccessful()
        ->assertSee('Borrowings')
        ->assertSee('Repayments')
        ->assertSee('Karim Traders')
        ->assertSee('Treadmill repair');
});

it('filters borrowings by search, status, and date range', function () {
    Borrowing::factory()->create([
        'borrowing_no' => 'BOR-20260816-00001',
        'borrowing_date' => '2026-08-10',
        'lender_name' => 'Visible Lender',
        'status' => BorrowingStatus::Active,
    ]);

    Borrowing::factory()->cancelled()->create([
        'borrowing_no' => 'BOR-20260816-00002',
        'borrowing_date' => '2026-07-01',
        'lender_name' => 'Hidden Lender',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.borrowings.index', [
            'search' => 'Visible',
            'status' => BorrowingStatus::Active->value,
            'from_date' => '2026-08-01',
            'to_date' => '2026-08-31',
        ]))
        ->assertSuccessful()
        ->assertSee('Visible Lender')
        ->assertDontSee('Hidden Lender');
});

it('paginates the borrowing list', function () {
    Borrowing::factory()->count(16)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.borrowings.index'))
        ->assertSuccessful()
        ->assertSee('page=2', false);
});

it('creates an active borrowing with an auto-generated number', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('admin.borrowings.store'), [
            'lender_name' => 'Rahim Store',
            'lender_phone' => '01711112222',
            'borrowing_date' => '2026-08-16',
            'amount' => 5000,
            'purpose' => 'AC repair',
            'due_date' => '2026-09-16',
            'payment_method' => PaymentMethod::Bank->value,
            'description' => 'Short-term cash',
            'status' => BorrowingStatus::Cancelled->value,
        ]);

    $borrowing = Borrowing::query()->first();

    expect($borrowing)->not->toBeNull()
        ->and($borrowing->borrowing_no)->toStartWith('BOR-'.now()->format('Ymd'))
        ->and($borrowing->lender_name)->toBe('Rahim Store')
        ->and((float) $borrowing->amount)->toBe(5000.0)
        ->and($borrowing->status)->toBe(BorrowingStatus::Active)
        ->and($borrowing->created_by)->toBe($this->admin->id);

    $response->assertRedirect(route('admin.borrowings.show', $borrowing));
});

it('shows a borrowing and its repayment history', function () {
    $recorder = User::factory()->create(['name' => 'Karim Recorder', 'is_active' => true]);

    $borrowing = Borrowing::factory()->create([
        'lender_name' => 'Office Depot',
        'borrowing_date' => '2026-08-01',
        'due_date' => '2026-09-01',
        'amount' => 2000,
        'purpose' => 'Flooring',
        'created_by' => $this->admin->id,
    ]);

    BorrowingRepayment::factory()->create([
        'borrowing_id' => $borrowing->id,
        'repayment_no' => 'RPY-20260816-00001',
        'repayment_date' => '2026-08-16',
        'amount' => 500,
        'payment_method' => PaymentMethod::Cash,
        'created_by' => $recorder->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.borrowings.show', $borrowing))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'Record Repayment',
            'Borrowing No',
            $borrowing->borrowing_no,
            'Lender',
            'Office Depot',
            'Borrowing Date',
            'Original Amount',
            '2,000',
            'Total Repaid',
            '500',
            'Remaining Amount',
            '1,500',
            'Due Date',
            'Purpose',
            'Flooring',
            'Status',
            'Repayment History',
            'Repayment No',
            'Payment Method',
            'Created By',
            'RPY-20260816-00001',
            'Karim Recorder',
        ]);
});

it('hides record repayment when nothing remains or the borrowing is closed', function (BorrowingStatus $status) {
    $borrowing = Borrowing::factory()->create([
        'amount' => 1000,
        'status' => $status,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.borrowings.show', $borrowing))
        ->assertSuccessful()
        ->assertDontSee('Record Repayment');
})->with([
    BorrowingStatus::FullyRepaid,
    BorrowingStatus::Cancelled,
]);

it('updates a borrowing', function () {
    $borrowing = Borrowing::factory()->create([
        'amount' => 2000,
        'status' => BorrowingStatus::Active,
    ]);

    $response = $this->actingAs($this->admin)
        ->put(route('admin.borrowings.update', $borrowing), [
            'lender_name' => 'Updated Lender',
            'lender_phone' => '01800000000',
            'borrowing_date' => '2026-08-17',
            'amount' => 1800,
            'purpose' => 'Updated purpose',
            'due_date' => '2026-10-01',
            'payment_method' => PaymentMethod::Cash->value,
            'description' => 'Updated note',
            'status' => BorrowingStatus::PartiallyRepaid->value,
        ]);

    $borrowing->refresh();

    expect($borrowing->lender_name)->toBe('Updated Lender')
        ->and((float) $borrowing->amount)->toBe(1800.0)
        ->and($borrowing->status)->toBe(BorrowingStatus::Active)
        ->and($borrowing->borrowing_no)->not->toBeNull();

    $response->assertRedirect(route('admin.borrowings.show', $borrowing));
});

it('deletes a borrowing that has no repayment history', function () {
    $borrowing = Borrowing::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.borrowings.destroy', $borrowing))
        ->assertRedirect(route('admin.borrowings.index'));

    expect(Borrowing::query()->count())->toBe(0);
});

it('does not delete a borrowing that has repayment history', function () {
    $borrowing = Borrowing::factory()->create();
    $repayment = BorrowingRepayment::factory()->create([
        'borrowing_id' => $borrowing->id,
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.borrowings.show', $borrowing))
        ->delete(route('admin.borrowings.destroy', $borrowing))
        ->assertRedirect(route('admin.borrowings.show', $borrowing))
        ->assertSessionHasErrors('borrowing');

    expect(Borrowing::query()->whereKey($borrowing->id)->exists())->toBeTrue()
        ->and(BorrowingRepayment::query()->whereKey($repayment->id)->exists())->toBeTrue();
});

it('rejects a borrowing amount that is not greater than zero', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.borrowings.create'))
        ->post(route('admin.borrowings.store'), [
            'lender_name' => 'Rahim Store',
            'borrowing_date' => '2026-08-16',
            'amount' => 0,
            'payment_method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect(route('admin.borrowings.create'))
        ->assertSessionHasErrors('amount');

    expect(Borrowing::query()->count())->toBe(0);
});

it('rejects a due date before the borrowing date', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.borrowings.create'))
        ->post(route('admin.borrowings.store'), [
            'lender_name' => 'Rahim Store',
            'borrowing_date' => '2026-08-16',
            'due_date' => '2026-08-01',
            'amount' => 1000,
            'payment_method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect(route('admin.borrowings.create'))
        ->assertSessionHasErrors('due_date');
});

it('rejects an amount below the amount already returned', function () {
    $borrowing = Borrowing::factory()->create(['amount' => 2000]);
    BorrowingRepayment::factory()->create([
        'borrowing_id' => $borrowing->id,
        'amount' => 800,
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.borrowings.edit', $borrowing))
        ->put(route('admin.borrowings.update', $borrowing), [
            'lender_name' => $borrowing->lender_name,
            'borrowing_date' => $borrowing->borrowing_date->toDateString(),
            'amount' => 500,
            'payment_method' => PaymentMethod::Cash->value,
            'status' => BorrowingStatus::Active->value,
        ])
        ->assertRedirect(route('admin.borrowings.edit', $borrowing))
        ->assertSessionHasErrors('amount');

    expect((float) $borrowing->refresh()->amount)->toBe(2000.0);
});

it('keeps borrowing status aligned with repayment history', function () {
    $borrowing = Borrowing::factory()->create([
        'amount' => 2000,
        'status' => BorrowingStatus::PartiallyRepaid,
    ]);

    BorrowingRepayment::factory()->create([
        'borrowing_id' => $borrowing->id,
        'amount' => 800,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.borrowings.update', $borrowing), [
            'lender_name' => $borrowing->lender_name,
            'borrowing_date' => $borrowing->borrowing_date->toDateString(),
            'amount' => 2000,
            'payment_method' => PaymentMethod::Cash->value,
            'status' => BorrowingStatus::FullyRepaid->value,
        ])
        ->assertRedirect(route('admin.borrowings.show', $borrowing));

    $borrowing->refresh();

    expect($borrowing->status)->toBe(BorrowingStatus::PartiallyRepaid)
        ->and($borrowing->remaining_amount)->toBe(1200.0);

    $this->actingAs($this->admin)
        ->put(route('admin.borrowings.update', $borrowing), [
            'lender_name' => $borrowing->lender_name,
            'borrowing_date' => $borrowing->borrowing_date->toDateString(),
            'amount' => 800,
            'payment_method' => PaymentMethod::Cash->value,
            'status' => BorrowingStatus::Active->value,
        ])
        ->assertRedirect(route('admin.borrowings.show', $borrowing));

    expect($borrowing->refresh()->status)->toBe(BorrowingStatus::FullyRepaid)
        ->and($borrowing->remaining_amount)->toBe(0.0);
});

it('keeps a cancelled borrowing closed when it is edited', function () {
    $borrowing = Borrowing::factory()->cancelled()->create([
        'amount' => 1500,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.borrowings.update', $borrowing), [
            'lender_name' => 'Closed Lender',
            'borrowing_date' => $borrowing->borrowing_date->toDateString(),
            'amount' => 1500,
            'payment_method' => PaymentMethod::Cash->value,
            'status' => BorrowingStatus::Cancelled->value,
        ])
        ->assertRedirect(route('admin.borrowings.show', $borrowing));

    expect($borrowing->refresh()->status)->toBe(BorrowingStatus::Cancelled)
        ->and($borrowing->lender_name)->toBe('Closed Lender');
});

it('does not record a borrowing or repayment as revenue, an expense, or an asset', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.borrowings.store'), [
            'lender_name' => 'Audit Lender',
            'borrowing_date' => now()->toDateString(),
            'amount' => 1000,
            'payment_method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect();

    $borrowing = Borrowing::query()->firstOrFail();

    $this->actingAs($this->admin)
        ->post(route('admin.borrowings.repayments.store'), [
            'borrowing_id' => $borrowing->id,
            'repayment_date' => now()->toDateString(),
            'amount' => 250,
            'payment_method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect();

    expect(Payment::query()->count())->toBe(0)
        ->and(Expense::query()->count())->toBe(0)
        ->and(Asset::query()->count())->toBe(0)
        ->and($borrowing->refresh()->status)->toBe(BorrowingStatus::PartiallyRepaid)
        ->and($borrowing->remaining_amount)->toBe(750.0);
});

it('loads borrowing lists without a query per repayment', function () {
    Borrowing::factory()->count(3)->create()->each(function (Borrowing $borrowing): void {
        BorrowingRepayment::factory()->create([
            'borrowing_id' => $borrowing->id,
            'amount' => 25,
        ]);
    });

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($this->admin)
        ->get(route('admin.borrowings.index'))
        ->assertSuccessful();

    $repaymentQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'borrowing_repayments'))
        ->count();

    expect($repaymentQueries)->toBeLessThanOrEqual(2);

    $borrowing = Borrowing::query()->firstOrFail();

    DB::flushQueryLog();

    $this->actingAs($this->admin)
        ->get(route('admin.borrowings.show', $borrowing))
        ->assertSuccessful()
        ->assertSee('Record Repayment');

    $showQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'borrowing_repayments'))
        ->count();

    expect($showQueries)->toBeLessThanOrEqual(2);

    DB::flushQueryLog();

    $this->actingAs($this->admin)
        ->get(route('admin.borrowings.repayments.index'))
        ->assertSuccessful();

    $listQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'borrowing_repayments'))
        ->count();

    expect($listQueries)->toBeLessThanOrEqual(2);

    DB::flushQueryLog();

    $this->actingAs($this->admin)
        ->get(route('admin.borrowings.repayments.create'))
        ->assertSuccessful();

    $createQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'borrowing_repayments') && ! str_contains(strtolower($query['query']), 'borrowings'))
        ->count();

    expect($createQueries)->toBe(0);
});

it('forbids borrowing management without permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $borrowing = Borrowing::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.borrowings.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.borrowings.create'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.borrowings.show', $borrowing))
        ->assertForbidden();
});

it('shows borrowings in the finance sidebar for authorized users', function () {
    $this->actingAs($this->admin);

    $keys = MenuBuilder::authorizedGroups()
        ->firstWhere('key', 'finance')['items']
        ->pluck('key')
        ->take(4)
        ->all();

    expect($keys)->toBe(['payments', 'expenses', 'invoices', 'borrowings']);

    $this->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Borrowings', false);
});

it('hides borrowings in the sidebar without permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->givePermissionTo(['dashboard.view', 'payments.view', 'expenses.view']);

    $this->actingAs($user);

    $keys = MenuBuilder::authorizedGroups()
        ->firstWhere('key', 'finance')['items']
        ->pluck('key');

    expect($keys->all())->toContain('payments', 'expenses')
        ->not->toContain('borrowings');
});

it('generates unique borrowing numbers for same-day records', function () {
    $first = app(BorrowingRepository::class)->nextBorrowingNumber();
    Borrowing::factory()->create(['borrowing_no' => $first]);
    $second = app(BorrowingRepository::class)->nextBorrowingNumber();

    expect($first)->not->toBe($second)
        ->and($first)->toStartWith('BOR-'.now()->format('Ymd'))
        ->and($second)->toStartWith('BOR-'.now()->format('Ymd'));
});
