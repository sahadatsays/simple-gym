<?php

use App\Enums\BorrowingStatus;
use App\Enums\PaymentMethod;
use App\Models\Borrowing;
use App\Models\BorrowingRepayment;
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
});

it('shows borrowed repaid and remaining amounts before a repayment is saved', function () {
    $borrowing = Borrowing::factory()->create([
        'borrowing_no' => 'BOR-20260816-00011',
        'lender_name' => 'Karim Traders',
        'borrowing_date' => '2026-08-01',
        'amount' => 2000,
        'status' => BorrowingStatus::Active,
    ]);

    BorrowingRepayment::factory()->create([
        'borrowing_id' => $borrowing->id,
        'amount' => 500,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.borrowings.repayments.confirm'), [
            'borrowing_id' => $borrowing->id,
            'repayment_date' => '2026-08-16',
            'amount' => 400,
            'payment_method' => PaymentMethod::Cash->value,
            'description' => 'First return',
        ])
        ->assertSuccessful()
        ->assertSeeInOrder([
            'Confirm Repayment',
            'Assigned when you confirm',
            'BOR-20260816-00011',
            'Karim Traders',
            'Borrowed amount',
            '2,000',
            'Total repaid',
            '500',
            'Remaining amount',
            '1,500',
            'Partially Repaid',
        ]);

    expect(BorrowingRepayment::query()->count())->toBe(1)
        ->and($borrowing->refresh()->status)->toBe(BorrowingStatus::Active);
});

it('saves a repayment without changing earlier repayments and marks the borrowing partially repaid', function () {
    $borrowing = Borrowing::factory()->create([
        'borrowing_date' => '2026-08-01',
        'amount' => 2000,
        'status' => BorrowingStatus::Active,
    ]);

    $existing = BorrowingRepayment::factory()->create([
        'borrowing_id' => $borrowing->id,
        'repayment_no' => 'RPY-20260801-00001',
        'amount' => 500,
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('admin.borrowings.repayments.store'), [
            'borrowing_id' => $borrowing->id,
            'repayment_date' => '2026-08-16',
            'amount' => 400,
            'payment_method' => PaymentMethod::Bank->value,
            'description' => 'Second return',
        ]);

    $repayment = BorrowingRepayment::query()->where('amount', 400)->first();

    expect($repayment)->not->toBeNull()
        ->and($repayment->repayment_no)->toStartWith('RPY-'.now()->format('Ymd'))
        ->and($repayment->borrowing_id)->toBe($borrowing->id)
        ->and($repayment->created_by)->toBe($this->admin->id)
        ->and((float) $existing->refresh()->amount)->toBe(500.0)
        ->and($borrowing->refresh()->status)->toBe(BorrowingStatus::PartiallyRepaid)
        ->and($borrowing->total_repaid)->toBe(900.0)
        ->and($borrowing->remaining_amount)->toBe(1100.0);

    $response->assertRedirect(route('admin.borrowings.show', $borrowing));
});

it('marks the borrowing fully repaid when the remaining amount is returned', function () {
    $borrowing = Borrowing::factory()->create([
        'borrowing_date' => '2026-08-01',
        'amount' => 1000,
        'status' => BorrowingStatus::PartiallyRepaid,
    ]);

    BorrowingRepayment::factory()->create([
        'borrowing_id' => $borrowing->id,
        'amount' => 400,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.borrowings.repayments.store'), [
            'borrowing_id' => $borrowing->id,
            'repayment_date' => '2026-08-20',
            'amount' => 600,
            'payment_method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect(route('admin.borrowings.show', $borrowing));

    expect($borrowing->refresh()->status)->toBe(BorrowingStatus::FullyRepaid)
        ->and($borrowing->remaining_amount)->toBe(0.0)
        ->and($borrowing->repayments()->count())->toBe(2);
});

it('rejects a repayment above the remaining amount', function () {
    $borrowing = Borrowing::factory()->create([
        'borrowing_date' => '2026-08-01',
        'amount' => 1000,
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.borrowings.repayments.create', ['borrowing_id' => $borrowing->id]))
        ->post(route('admin.borrowings.repayments.confirm'), [
            'borrowing_id' => $borrowing->id,
            'repayment_date' => '2026-08-16',
            'amount' => 1000.01,
            'payment_method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect(route('admin.borrowings.repayments.create', ['borrowing_id' => $borrowing->id]))
        ->assertSessionHasErrors('amount');

    expect(BorrowingRepayment::query()->count())->toBe(0);
});

it('rejects a repayment against a cancelled or fully repaid borrowing', function (BorrowingStatus $status) {
    $borrowing = Borrowing::factory()->create([
        'borrowing_date' => '2026-08-01',
        'amount' => 1000,
        'status' => $status,
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.borrowings.repayments.create'))
        ->post(route('admin.borrowings.repayments.store'), [
            'borrowing_id' => $borrowing->id,
            'repayment_date' => '2026-08-16',
            'amount' => 100,
            'payment_method' => PaymentMethod::Cash->value,
        ])
        ->assertSessionHasErrors('borrowing_id');

    expect(BorrowingRepayment::query()->count())->toBe(0)
        ->and($borrowing->refresh()->status)->toBe($status);
})->with([
    BorrowingStatus::Cancelled,
    BorrowingStatus::FullyRepaid,
]);

it('rejects a repayment dated before the borrowing date', function () {
    $borrowing = Borrowing::factory()->create([
        'borrowing_date' => '2026-08-16',
        'amount' => 1000,
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.borrowings.repayments.create', ['borrowing_id' => $borrowing->id]))
        ->post(route('admin.borrowings.repayments.confirm'), [
            'borrowing_id' => $borrowing->id,
            'repayment_date' => '2026-08-15',
            'amount' => 100,
            'payment_method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect(route('admin.borrowings.repayments.create', ['borrowing_id' => $borrowing->id]))
        ->assertSessionHasErrors('repayment_date');

    expect(BorrowingRepayment::query()->count())->toBe(0);
});

it('rejects a repayment amount that is not greater than zero', function () {
    $borrowing = Borrowing::factory()->create([
        'borrowing_date' => '2026-08-01',
        'amount' => 1000,
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.borrowings.repayments.create', ['borrowing_id' => $borrowing->id]))
        ->post(route('admin.borrowings.repayments.store'), [
            'borrowing_id' => $borrowing->id,
            'repayment_date' => '2026-08-16',
            'amount' => 0,
            'payment_method' => PaymentMethod::Cash->value,
        ])
        ->assertSessionHasErrors('amount');
});

it('lists repayments from the borrowings section', function () {
    $borrowing = Borrowing::factory()->create([
        'borrowing_no' => 'BOR-REPAY-001',
        'lender_name' => 'Karim Traders',
    ]);

    BorrowingRepayment::factory()->create([
        'borrowing_id' => $borrowing->id,
        'repayment_no' => 'RPY-REPAY-001',
        'repayment_date' => now()->toDateString(),
        'amount' => 450,
        'created_by' => $this->admin->id,
    ]);

    BorrowingRepayment::factory()->create([
        'repayment_no' => 'RPY-REPAY-OLD',
        'repayment_date' => now()->subMonths(2)->toDateString(),
        'amount' => 9000,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.borrowings.repayments.index', [
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
            'search' => 'Karim',
        ]))
        ->assertSuccessful()
        ->assertSee('Repayments')
        ->assertSee('Borrowings')
        ->assertSee('RPY-REPAY-001')
        ->assertSee('BOR-REPAY-001')
        ->assertSee('Karim Traders')
        ->assertSee('450')
        ->assertSee($this->admin->name)
        ->assertDontSee('RPY-REPAY-OLD')
        ->assertDontSee('9,000');
});

it('forbids the repayments list without permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $this->actingAs($user)
        ->get(route('admin.borrowings.repayments.index'))
        ->assertForbidden();
});

it('forbids recording a repayment without permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $borrowing = Borrowing::factory()->create(['amount' => 1000]);

    $this->actingAs($user)
        ->get(route('admin.borrowings.repayments.create'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('admin.borrowings.repayments.store'), [
            'borrowing_id' => $borrowing->id,
            'repayment_date' => now()->toDateString(),
            'amount' => 100,
            'payment_method' => PaymentMethod::Cash->value,
        ])
        ->assertForbidden();
});
