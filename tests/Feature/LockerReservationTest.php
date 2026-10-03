<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\LockerReservationStatus;
use App\Enums\LockerStatus;
use App\Enums\PaymentType;
use App\Exceptions\PaymentFailedException;
use App\Models\Invoice;
use App\Models\Locker;
use App\Models\LockerReservation;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use App\Services\FinancialSummaryService;
use App\Services\LockerReservationService;
use App\Services\PaymentService;
use App\Support\DashboardDateRange;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create([
        'name' => 'Locker Admin',
        'username' => 'adminuser',
        'is_active' => true,
    ]);
    $this->admin->assignRole('super-admin');
});

it('calculates a monthly period with the start date on or before the end date', function () {
    $period = app(LockerReservationService::class)->periodForMonth('2026-10');

    expect($period['start_date'])->toBe('2026-10-01')
        ->and($period['end_date'])->toBe('2026-10-31')
        ->and($period['start_date'] <= $period['end_date'])->toBeTrue();
});

it('activates a paid monthly reservation through the existing invoice system', function () {
    $member = Member::factory()->create(['name' => 'Rina Akter']);
    $locker = Locker::factory()->create([
        'locker_number' => 'L-12',
        'monthly_fee' => 300,
        'status' => LockerStatus::Available,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.locker-reservations.store'), [
            'member_id' => $member->id,
            'locker_id' => $locker->id,
            'start_month' => '2026-10',
            'payment_method' => 'cash',
        ])
        ->assertRedirect();

    $reservation = LockerReservation::query()->first();
    $invoice = Invoice::query()->first();
    $payment = Payment::query()->first();

    expect($reservation)->not->toBeNull()
        ->and($reservation->status)->toBe(LockerReservationStatus::Active)
        ->and($reservation->start_date->toDateString())->toBe('2026-10-01')
        ->and($reservation->end_date->toDateString())->toBe('2026-10-31')
        ->and((float) $reservation->monthly_fee)->toBe(300.0)
        ->and($reservation->created_by)->toBe($this->admin->id)
        ->and($locker->fresh()->status)->toBe(LockerStatus::Reserved)
        ->and($invoice->type)->toBe(InvoiceType::Locker)
        ->and($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->line_items[0]['description'])->toBe('Locker')
        ->and((float) $invoice->line_items[0]['amount'])->toBe(300.0)
        ->and((float) $invoice->total)->toEqual(collect($invoice->line_items)->sum('amount'))
        ->and($payment->type)->toBe(PaymentType::Locker)
        ->and((float) $payment->amount)->toBe(300.0)
        ->and(app(FinancialSummaryService::class)->forRange(DashboardDateRange::default())['membership_payments'])->toBe(0.0)
        ->and(app(FinancialSummaryService::class)->forRange(DashboardDateRange::default())['revenue'])->toBe(0.0);
});

it('activates a free locker without creating an invoice', function () {
    $member = Member::factory()->create();
    $locker = Locker::factory()->create([
        'monthly_fee' => 0,
        'status' => LockerStatus::Available,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.locker-reservations.store'), [
            'member_id' => $member->id,
            'locker_id' => $locker->id,
            'start_month' => now()->format('Y-m'),
        ])
        ->assertRedirect();

    expect(LockerReservation::query()->first()->status)->toBe(LockerReservationStatus::Active)
        ->and(Invoice::query()->count())->toBe(0)
        ->and($locker->fresh()->status)->toBe(LockerStatus::Reserved);
});

it('does not activate a reservation when payment fails', function () {
    $this->mock(PaymentService::class, function ($mock): void {
        $mock->shouldReceive('settleInvoice')->once()->andThrow(PaymentFailedException::declined());
    });

    $member = Member::factory()->create();
    $locker = Locker::factory()->create([
        'monthly_fee' => 250,
        'status' => LockerStatus::Available,
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.locker-reservations.create'))
        ->post(route('admin.locker-reservations.store'), [
            'member_id' => $member->id,
            'locker_id' => $locker->id,
            'start_month' => now()->format('Y-m'),
            'payment_method' => 'cash',
        ])
        ->assertRedirect(route('admin.locker-reservations.create'))
        ->assertSessionHasErrors('reservation');

    expect(LockerReservation::query()->count())->toBe(0)
        ->and(Invoice::query()->count())->toBe(0)
        ->and(Payment::query()->count())->toBe(0)
        ->and($locker->fresh()->status)->toBe(LockerStatus::Available);
});

it('rejects an overlapping active reservation in the service', function () {
    $member = Member::factory()->create();
    $other = Member::factory()->create();
    $locker = Locker::factory()->create([
        'monthly_fee' => 100,
        'status' => LockerStatus::Available,
    ]);
    $service = app(LockerReservationService::class);

    $service->reserve($locker, $member, '2026-11', 'cash', $this->admin->id);

    expect(fn () => $service->reserve($locker->fresh(), $other, '2026-11', 'cash', $this->admin->id))
        ->toThrow(InvalidArgumentException::class, 'already has an active reservation');

    expect(LockerReservation::query()->count())->toBe(1);
});

it('keeps the previous reservation when a later month is renewed', function () {
    $member = Member::factory()->create();
    $locker = Locker::factory()->create([
        'monthly_fee' => 180,
        'status' => LockerStatus::Available,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.locker-reservations.store'), [
            'member_id' => $member->id,
            'locker_id' => $locker->id,
            'start_month' => '2026-10',
            'payment_method' => 'cash',
        ])
        ->assertRedirect();

    $original = LockerReservation::query()->first();

    $this->actingAs($this->admin)
        ->post(route('admin.locker-reservations.renew', $original), [
            'payment_method' => 'cash',
        ])
        ->assertRedirect();

    $original->refresh();
    $november = LockerReservation::query()->whereDate('start_date', '2026-11-01')->first();

    expect(LockerReservation::query()->count())->toBe(2)
        ->and($original->start_date->toDateString())->toBe('2026-10-01')
        ->and($original->end_date->toDateString())->toBe('2026-10-31')
        ->and($original->status)->toBe(LockerReservationStatus::Active)
        ->and((float) $original->monthly_fee)->toBe(180.0)
        ->and($november)->not->toBeNull()
        ->and($november->end_date->toDateString())->toBe('2026-11-30')
        ->and($november->id)->not->toBe($original->id)
        ->and((float) $november->monthly_fee)->toBe(180.0)
        ->and($november->invoice_id)->not->toBe($original->invoice_id);

    $this->actingAs($this->admin)
        ->post(route('admin.locker-reservations.renew', $november), [
            'payment_method' => 'cash',
        ])
        ->assertRedirect();

    $original->refresh();
    $november->refresh();
    $december = LockerReservation::query()->whereDate('start_date', '2026-12-01')->first();

    expect(LockerReservation::query()->count())->toBe(3)
        ->and($original->end_date->toDateString())->toBe('2026-10-31')
        ->and($november->start_date->toDateString())->toBe('2026-11-01')
        ->and($november->end_date->toDateString())->toBe('2026-11-30')
        ->and($december)->not->toBeNull()
        ->and($december->end_date->toDateString())->toBe('2026-12-31');
});

it('blocks renewal when the reservation is cancelled or the locker cannot be reserved', function (string $blocker) {
    $member = Member::factory()->create();
    $locker = Locker::factory()->create([
        'monthly_fee' => 120,
        'status' => LockerStatus::Available,
    ]);
    $service = app(LockerReservationService::class);
    $reservation = $service->reserve($locker, $member, '2026-10', 'cash', $this->admin->id);

    if ($blocker === 'cancelled') {
        $service->cancel($reservation);
    } else {
        $locker->update([
            'status' => $blocker === 'maintenance' ? LockerStatus::Maintenance : LockerStatus::Disabled,
        ]);
    }

    expect(fn () => $service->renew($reservation->fresh(), 'cash', $this->admin->id))
        ->toThrow(InvalidArgumentException::class);

    expect(LockerReservation::query()->count())->toBe(1)
        ->and($reservation->fresh()->start_date->toDateString())->toBe('2026-10-01')
        ->and($reservation->fresh()->end_date->toDateString())->toBe('2026-10-31');
})->with([
    'cancelled',
    'maintenance',
    'disabled',
]);

it('blocks renewal when the next month is already reserved', function () {
    $member = Member::factory()->create();
    $other = Member::factory()->create();
    $locker = Locker::factory()->create([
        'monthly_fee' => 100,
        'status' => LockerStatus::Available,
    ]);
    $service = app(LockerReservationService::class);
    $october = $service->reserve($locker, $member, '2026-10', 'cash', $this->admin->id);
    $service->reserve($locker->fresh(), $other, '2026-11', 'cash', $this->admin->id);

    expect(fn () => $service->renew($october->fresh(), 'cash', $this->admin->id))
        ->toThrow(InvalidArgumentException::class, 'already has an active reservation');

    expect(LockerReservation::query()->count())->toBe(2)
        ->and($october->fresh()->end_date->toDateString())->toBe('2026-10-31');
});

it('does not extend a reservation when the renewal payment fails', function () {
    $member = Member::factory()->create();
    $locker = Locker::factory()->create([
        'monthly_fee' => 150,
        'status' => LockerStatus::Available,
    ]);
    $reservation = app(LockerReservationService::class)->reserve($locker, $member, '2026-10', 'cash', $this->admin->id);

    $this->mock(PaymentService::class, function ($mock): void {
        $mock->shouldReceive('settleInvoice')->once()->andThrow(PaymentFailedException::declined());
    });

    $this->actingAs($this->admin)
        ->from(route('admin.locker-reservations.show', $reservation))
        ->post(route('admin.locker-reservations.renew', $reservation), [
            'payment_method' => 'cash',
        ])
        ->assertRedirect(route('admin.locker-reservations.show', $reservation))
        ->assertSessionHasErrors('reservation');

    expect(LockerReservation::query()->count())->toBe(1)
        ->and(Invoice::query()->count())->toBe(1)
        ->and($reservation->fresh()->end_date->toDateString())->toBe('2026-10-31')
        ->and($reservation->fresh()->status)->toBe(LockerReservationStatus::Active);
});

it('refuses maintenance and disabled lockers', function (LockerStatus $status) {
    $member = Member::factory()->create();
    $locker = Locker::factory()->create([
        'monthly_fee' => 90,
        'status' => $status,
    ]);

    expect(fn () => app(LockerReservationService::class)->reserve($locker, $member, '2026-10', 'cash', $this->admin->id))
        ->toThrow(InvalidArgumentException::class, 'cannot be reserved');

    expect($locker->fresh()->status)->toBe($status)
        ->and(LockerReservation::query()->count())->toBe(0);
})->with([
    LockerStatus::Maintenance,
    LockerStatus::Disabled,
]);

it('cancels an active reservation and releases an idle locker', function () {
    $member = Member::factory()->create();
    $locker = Locker::factory()->create([
        'monthly_fee' => 75,
        'status' => LockerStatus::Available,
    ]);

    $reservation = app(LockerReservationService::class)->reserve($locker, $member, now()->format('Y-m'), 'cash', $this->admin->id);
    $invoiceId = $reservation->invoice_id;

    $this->actingAs($this->admin)
        ->patch(route('admin.locker-reservations.cancel', $reservation))
        ->assertRedirect(route('admin.locker-reservations.show', $reservation));

    expect($reservation->fresh()->status)->toBe(LockerReservationStatus::Cancelled)
        ->and($reservation->fresh()->invoice_id)->toBe($invoiceId)
        ->and(LockerReservation::query()->count())->toBe(1)
        ->and($locker->fresh()->status)->toBe(LockerStatus::Available);
});

it('expires a past reservation when the list is opened', function () {
    $locker = Locker::factory()->create(['status' => LockerStatus::Reserved]);
    $reservation = LockerReservation::factory()->create([
        'locker_id' => $locker->id,
        'start_date' => now()->subMonth()->startOfMonth()->toDateString(),
        'end_date' => now()->subDay()->toDateString(),
        'status' => LockerReservationStatus::Active,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.locker-reservations.index'))
        ->assertSuccessful()
        ->assertSee('Expired');

    expect($reservation->fresh()->status)->toBe(LockerReservationStatus::Expired)
        ->and($locker->fresh()->status)->toBe(LockerStatus::Available);
});

it('lists reservations by member and status', function () {
    $member = Member::factory()->create(['name' => 'Nabila Hasan']);
    $locker = Locker::factory()->create(['locker_number' => 'L-44']);
    LockerReservation::factory()->create([
        'locker_id' => $locker->id,
        'member_id' => $member->id,
        'status' => LockerReservationStatus::Active,
    ]);
    LockerReservation::factory()->create([
        'locker_id' => Locker::factory()->create(['locker_number' => 'L-99'])->id,
        'status' => LockerReservationStatus::Cancelled,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.locker-reservations.index', [
            'search' => 'Nabila',
            'status' => 'active',
        ]))
        ->assertSuccessful()
        ->assertSee('L-44')
        ->assertSee('table-responsive')
        ->assertDontSee('L-99');
});

it('lets staff view reservations but not create them', function () {
    $staff = User::factory()->create([
        'username' => 'staffuser',
        'is_active' => true,
    ]);
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->get(route('admin.locker-reservations.index'))
        ->assertSuccessful();

    $this->actingAs($staff)
        ->post(route('admin.locker-reservations.store'), [
            'member_id' => Member::factory()->create()->id,
            'locker_id' => Locker::factory()->create()->id,
            'start_month' => now()->format('Y-m'),
        ])
        ->assertForbidden();
});

it('does not delete a locker that has reservation history', function () {
    $reservation = LockerReservation::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.lockers.destroy', $reservation->locker_id))
        ->assertRedirect();

    expect(Locker::query()->whereKey($reservation->locker_id)->exists())->toBeTrue()
        ->and(LockerReservation::query()->count())->toBe(1);
});
