<?php

use App\Enums\BorrowingStatus;
use App\Enums\PaymentMethod;
use App\Models\Borrowing;
use App\Models\BorrowingRepayment;
use App\Models\User;
use App\Repositories\BorrowingRepository;
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

it('lists borrowings for authorized users', function () {
    Borrowing::factory()->create([
        'lender_name' => 'Karim Traders',
        'purpose' => 'Treadmill repair',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.borrowings.index'))
        ->assertSuccessful()
        ->assertSee('Borrowings')
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
        ->and($borrowing->status)->toBe(BorrowingStatus::PartiallyRepaid)
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

it('shows borrowings in the sidebar for authorized users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Borrowings', false);
});

it('generates unique borrowing numbers for same-day records', function () {
    $first = app(BorrowingRepository::class)->nextBorrowingNumber();
    Borrowing::factory()->create(['borrowing_no' => $first]);
    $second = app(BorrowingRepository::class)->nextBorrowingNumber();

    expect($first)->not->toBe($second)
        ->and($first)->toStartWith('BOR-'.now()->format('Ymd'))
        ->and($second)->toStartWith('BOR-'.now()->format('Ymd'));
});
