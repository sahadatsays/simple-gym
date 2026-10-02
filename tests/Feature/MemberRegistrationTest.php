<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\MemberStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\RfidCardStatus;
use App\Exceptions\PaymentFailedException;
use App\Models\GymSetting;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\RfidCard;
use App\Models\User;
use App\Services\PaymentService;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create([
        'username' => 'adminuser',
        'is_active' => true,
    ]);
    $this->admin->assignRole('super-admin');

    $this->plan = MembershipPlan::factory()->create([
        'name' => 'Monthly Plan',
        'duration_days' => 30,
        'admission_fee' => 500,
        'membership_fee' => 1500,
    ]);
});

it('shows the registration form', function () {
    RfidCard::factory()->create([
        'card_number' => 'CARD001',
        'status' => RfidCardStatus::Available,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.register.create'))
        ->assertSuccessful()
        ->assertSee('Register Member')
        ->assertSee('Monthly Plan')
        ->assertSee('CARD001')
        ->assertSeeInOrder([
            'Plan fees',
            'Receive Payment',
            'discount_amount',
            'due_at',
            'Balance due',
        ]);
});

it('completes the full registration workflow', function () {
    Storage::fake('public');

    $card = RfidCard::factory()->create([
        'card_number' => 'CARD999',
        'status' => RfidCardStatus::Available,
    ]);

    $joinedAt = now()->toDateString();

    $this->actingAs($this->admin)
        ->post(route('admin.members.register.store'), [
            'name' => 'Registered Member',
            'phone' => '01755556666',
            'email' => 'registered@example.com',
            'gender' => 'male',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => $joinedAt,
            'payment_method' => 'cash',
            'amount_received' => 2000,
            'rfid_card_id' => $card->id,
            'photo' => UploadedFile::fake()->image('member.jpg'),
        ])
        ->assertRedirect();

    $member = Member::query()->where('phone', '01755556666')->first();

    expect($member)->not->toBeNull()
        ->and($member->status)->toBe(MemberStatus::Active)
        ->and($member->member_code)->toStartWith('M')
        ->and($member->membership_plan_id)->toBe($this->plan->id)
        ->and($member->membership_expires_at?->toDateString())->toBe(now()->parse($joinedAt)->addDays(30)->toDateString())
        ->and($member->photo_path)->not->toBeNull();

    $invoice = Invoice::query()->where('member_id', $member->id)->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and((float) $invoice->total)->toBe(2000.0)
        ->and($invoice->line_items)->toHaveCount(2);

    $payment = Payment::query()->where('member_id', $member->id)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Completed)
        ->and($payment->type)->toBe(PaymentType::MembershipFee)
        ->and((float) $payment->amount)->toBe(2000.0)
        ->and($payment->receipt_number)->toStartWith('RCP-')
        ->and($payment->invoice_id)->toBe($invoice->id);

    $card->refresh();

    expect($card->status)->toBe(RfidCardStatus::Assigned)
        ->and($card->member_id)->toBe($member->id)
        ->and($member->fresh()->rfid_card)->toBe('CARD999');
});

it('collects configured rfid charges on a separate invoice during registration', function () {
    GymSetting::query()->first()->update([
        'rfid_card_fee' => 100,
        'rfid_card_deposit' => 50,
    ]);

    $card = RfidCard::factory()->create([
        'card_number' => 'CARDCHARGE',
        'status' => RfidCardStatus::Available,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.members.register.store'), [
            'name' => 'Card Charge Member',
            'phone' => '01755557777',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => now()->toDateString(),
            'payment_method' => 'cash',
            'amount_received' => 2000,
            'rfid_card_id' => $card->id,
        ])
        ->assertRedirect();

    $member = Member::query()->where('phone', '01755557777')->first();
    $membershipInvoice = Invoice::query()->where('member_id', $member->id)->where('type', InvoiceType::Registration)->first();
    $rfidInvoice = Invoice::query()->where('member_id', $member->id)->where('type', InvoiceType::RfidCard)->first();
    $rfidPayment = Payment::query()->where('invoice_id', $rfidInvoice?->id)->first();

    expect($member->status)->toBe(MemberStatus::Active)
        ->and($card->fresh()->status)->toBe(RfidCardStatus::Assigned)
        ->and((float) $membershipInvoice->total)->toBe(2000.0)
        ->and($membershipInvoice->line_items)->toHaveCount(2)
        ->and((float) $rfidInvoice->total)->toBe(150.0)
        ->and($rfidPayment->type)->toBe(PaymentType::RfidCard)
        ->and((float) $rfidPayment->amount)->toBe(150.0);
});

it('does not register the member when the rfid card payment fails', function () {
    GymSetting::query()->first()->update([
        'rfid_card_fee' => 100,
        'rfid_card_deposit' => 0,
    ]);

    $card = RfidCard::factory()->create([
        'card_number' => 'CARDFAIL',
        'status' => RfidCardStatus::Available,
    ]);

    $paymentService = $this->app->make(PaymentService::class);
    $mock = Mockery::mock($paymentService)->makePartial();
    $mock->shouldReceive('settleInvoice')
        ->andReturnUsing(function (Invoice $invoice, Member $member, float $amountPaid, PaymentMethod|string|null $paymentMethod, PaymentType $type) use ($paymentService): ?Payment {
            if ($type === PaymentType::RfidCard) {
                throw PaymentFailedException::declined();
            }

            return $paymentService->settleInvoice(
                invoice: $invoice,
                member: $member,
                amountPaid: $amountPaid,
                paymentMethod: $paymentMethod,
                type: $type,
            );
        });
    $this->instance(PaymentService::class, $mock);

    $this->actingAs($this->admin)
        ->from(route('admin.members.register.create'))
        ->post(route('admin.members.register.store'), [
            'name' => 'Failed Card Member',
            'phone' => '01755558888',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => now()->toDateString(),
            'payment_method' => 'cash',
            'amount_received' => 2000,
            'rfid_card_id' => $card->id,
        ])
        ->assertRedirect(route('admin.members.register.create'));

    expect(Member::query()->where('phone', '01755558888')->exists())->toBeFalse()
        ->and($card->fresh()->status)->toBe(RfidCardStatus::Available)
        ->and($card->fresh()->member_id)->toBeNull()
        ->and(Invoice::query()->count())->toBe(0)
        ->and(Payment::query()->count())->toBe(0);
});

it('registers a member against the discounted admission and plan total', function () {
    $card = RfidCard::factory()->create([
        'card_number' => 'CARDDISCOUNT',
        'status' => RfidCardStatus::Available,
    ]);

    $joinedAt = now()->toDateString();

    $this->actingAs($this->admin)
        ->post(route('admin.members.register.store'), [
            'name' => 'Discounted Member',
            'phone' => '01755550001',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => $joinedAt,
            'payment_method' => 'cash',
            'discount_amount' => 400,
            'amount_received' => 1600,
            'rfid_card_id' => $card->id,
        ])
        ->assertRedirect();

    $member = Member::query()->where('phone', '01755550001')->first();

    expect($member)->not->toBeNull()
        ->and($member->status)->toBe(MemberStatus::Active)
        ->and($member->membership_expires_at?->toDateString())->toBe(now()->parse($joinedAt)->addDays(30)->toDateString());

    $invoice = Invoice::query()->where('member_id', $member->id)->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and((float) $invoice->subtotal)->toBe(2000.0)
        ->and((float) $invoice->discount_amount)->toBe(400.0)
        ->and((float) $invoice->total)->toBe(1600.0)
        ->and($invoice->line_items[0]['amount'])->toEqual(500)
        ->and($invoice->line_items[1]['amount'])->toEqual(1500);

    $payment = Payment::query()->where('member_id', $member->id)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Completed)
        ->and((float) $payment->amount)->toBe(1600.0)
        ->and((float) $payment->discount_amount)->toBe(400.0)
        ->and($card->fresh()->member_id)->toBe($member->id);

    $this->actingAs($this->admin)
        ->get(route('admin.invoices.print', $invoice))
        ->assertSuccessful()
        ->assertSee('Discount');
});

it('activates membership when the admission and plan bill is fully discounted', function () {
    $joinedAt = now()->toDateString();

    $this->actingAs($this->admin)
        ->post(route('admin.members.register.store'), [
            'name' => 'Waived Member',
            'phone' => '01755550002',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => $joinedAt,
            'payment_method' => 'cash',
            'discount_amount' => 2000,
            'amount_received' => 0,
        ])
        ->assertRedirect();

    $member = Member::query()->where('phone', '01755550002')->first();
    $invoice = Invoice::query()->where('member_id', $member->id)->first();
    $payment = Payment::query()->where('invoice_id', $invoice->id)->first();

    expect($member->status)->toBe(MemberStatus::Active)
        ->and($member->membership_expires_at?->toDateString())->toBe(now()->parse($joinedAt)->addDays(30)->toDateString())
        ->and($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and((float) $invoice->discount_amount)->toBe(2000.0)
        ->and((float) $invoice->total)->toBe(0.0)
        ->and((float) $invoice->outstandingBalance())->toBe(0.0)
        ->and((float) $payment->amount)->toBe(0.0)
        ->and((float) $payment->discount_amount)->toBe(2000.0);
});

it('rejects a registration discount above the admission and plan subtotal', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.members.register.store'), [
            'name' => 'Too Much Discount',
            'phone' => '01755550003',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => now()->toDateString(),
            'payment_method' => 'cash',
            'discount_amount' => 2500,
            'amount_received' => 0,
        ])
        ->assertSessionHasErrors(['discount_amount']);

    expect(Member::query()->where('phone', '01755550003')->exists())->toBeFalse();
});

it('rejects a registration payment that ignores the discount', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.members.register.store'), [
            'name' => 'Gross Payment',
            'phone' => '01755550004',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => now()->toDateString(),
            'payment_method' => 'cash',
            'discount_amount' => 400,
            'amount_received' => 2000,
        ])
        ->assertSessionHasErrors(['amount_received']);

    expect(Member::query()->where('phone', '01755550004')->exists())->toBeFalse()
        ->and(Invoice::query()->count())->toBe(0)
        ->and(Payment::query()->count())->toBe(0);
});

it('requires a due date when the registration payment is below the discounted total', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.members.register.store'), [
            'name' => 'Short Payment',
            'phone' => '01755550005',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => now()->toDateString(),
            'payment_method' => 'cash',
            'discount_amount' => 400,
            'amount_received' => 1000,
        ])
        ->assertSessionHasErrors(['due_at']);

    expect(Member::query()->where('phone', '01755550005')->exists())->toBeFalse();
});

it('shows the receipt after registration', function () {
    $member = Member::factory()->create([
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
    ]);

    $invoice = Invoice::factory()->paid()->create([
        'member_id' => $member->id,
        'membership_plan_id' => $this->plan->id,
        'total' => 2000,
    ]);

    $payment = Payment::factory()->membershipFee()->create([
        'member_id' => $member->id,
        'invoice_id' => $invoice->id,
        'amount' => 2000,
        'receipt_number' => 'RCP-TEST-00001',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.receipt', [$member, $invoice]))
        ->assertRedirect(route('admin.invoices.show', $invoice));

    $this->actingAs($this->admin)
        ->get(route('admin.invoices.print', $invoice))
        ->assertSuccessful()
        ->assertSee('RCP-TEST-00001')
        ->assertSee($member->name)
        ->assertSee($invoice->invoice_number);
});

it('does not register a member when a short payment has no due date', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.members.register.store'), [
            'name' => 'Failed Member',
            'phone' => '01777778888',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => now()->toDateString(),
            'payment_method' => 'cash',
            'amount_received' => 100,
        ])
        ->assertSessionHasErrors(['due_at']);

    expect(Member::query()->where('phone', '01777778888')->exists())->toBeFalse()
        ->and(Invoice::query()->count())->toBe(0)
        ->and(Payment::query()->count())->toBe(0);
});

it('rejects a registration due date in the past', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.members.register.store'), [
            'name' => 'Past Due',
            'phone' => '01777779999',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => now()->toDateString(),
            'payment_method' => 'cash',
            'amount_received' => 500,
            'due_at' => now()->subDay()->toDateString(),
        ])
        ->assertSessionHasErrors(['due_at']);

    expect(Member::query()->where('phone', '01777779999')->exists())->toBeFalse();
});

it('shows registration errors beside the fields and at the top of the form', function () {
    Member::factory()->create(['phone' => '01700000099']);

    $this->actingAs($this->admin)
        ->from(route('admin.members.register.create'))
        ->followingRedirects()
        ->post(route('admin.members.register.store'), [
            'name' => 'Stuck Member',
            'phone' => '01700000099',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => now()->toDateString(),
            'payment_method' => 'cash',
            'amount_received' => 500,
        ])
        ->assertSuccessful()
        ->assertSee('Please fix the following:')
        ->assertSee('The phone has already been taken.')
        ->assertSee('Choose a due date when the amount received is less than the total.')
        ->assertSee('name="phone"', false)
        ->assertSee('is-invalid', false)
        ->assertSee('amountReceived: 500', false);
});

it('accepts a paid-in-full registration when a leftover due date is in the past', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.members.register.store'), [
            'name' => 'Paid Member',
            'phone' => '01755550099',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => now()->toDateString(),
            'payment_method' => 'cash',
            'amount_received' => 2000,
            'due_at' => now()->subDay()->toDateString(),
        ])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    expect(Member::query()->where('phone', '01755550099')->exists())->toBeTrue();
});

it('activates a member and keeps the unpaid balance when registration is partial', function () {
    $joinedAt = now()->toDateString();
    $dueAt = now()->addDays(7)->toDateString();

    $this->actingAs($this->admin)
        ->post(route('admin.members.register.store'), [
            'name' => 'Partial Member',
            'phone' => '01755550021',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => $joinedAt,
            'payment_method' => 'cash',
            'amount_received' => 500,
            'due_at' => $dueAt,
        ])
        ->assertRedirect();

    $member = Member::query()->where('phone', '01755550021')->first();
    $invoice = Invoice::query()->where('member_id', $member->id)->first();
    $payment = Payment::query()->where('invoice_id', $invoice->id)->first();

    expect($member->status)->toBe(MemberStatus::Active)
        ->and($member->membership_expires_at?->toDateString())->toBe(now()->parse($joinedAt)->addDays(30)->toDateString())
        ->and($invoice->status)->toBe(InvoiceStatus::Partial)
        ->and((float) $invoice->outstandingBalance())->toBe(1500.0)
        ->and($invoice->due_at?->toDateString())->toBe($dueAt)
        ->and((float) $payment->amount)->toBe(500.0);

    $this->actingAs($this->admin)
        ->get(route('admin.members.index'))
        ->assertSuccessful()
        ->assertSee('Partial Member')
        ->assertSee('1,500');

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', ['report' => 'membership']))
        ->assertSuccessful()
        ->assertSee('Outstanding Due')
        ->assertSee('1,500');

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee($invoice->invoice_number);
});

it('registers a member with the full bill due and does not record a payment', function () {
    $dueAt = now()->addDays(10)->toDateString();

    $this->actingAs($this->admin)
        ->post(route('admin.members.register.store'), [
            'name' => 'Due Member',
            'phone' => '01755550022',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => now()->toDateString(),
            'amount_received' => 0,
            'due_at' => $dueAt,
        ])
        ->assertRedirect();

    $member = Member::query()->where('phone', '01755550022')->first();
    $invoice = Invoice::query()->where('member_id', $member->id)->first();

    expect($member->status)->toBe(MemberStatus::Active)
        ->and($invoice->status)->toBe(InvoiceStatus::Unpaid)
        ->and((float) $invoice->outstandingBalance())->toBe(2000.0)
        ->and($invoice->due_at?->toDateString())->toBe($dueAt)
        ->and(Payment::query()->where('invoice_id', $invoice->id)->exists())->toBeFalse();
});

it('rejects duplicate phone registration', function () {
    Member::factory()->create([
        'phone' => '01799990000',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.members.register.store'), [
            'name' => 'Duplicate Member',
            'phone' => '01799990000',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => now()->toDateString(),
            'payment_method' => 'cash',
            'amount_received' => 2000,
        ])
        ->assertSessionHasErrors(['phone']);

    expect(Member::query()->where('name', 'Duplicate Member')->exists())->toBeFalse();
});

it('requires an active membership plan', function () {
    $inactivePlan = MembershipPlan::factory()->inactive()->create([
        'admission_fee' => 100,
        'membership_fee' => 900,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.members.register.store'), [
            'name' => 'Plan Test',
            'phone' => '01788889999',
            'membership_plan_id' => $inactivePlan->id,
            'joined_at' => now()->toDateString(),
            'payment_method' => 'cash',
            'amount_received' => 1000,
        ])
        ->assertSessionHasErrors(['membership_plan_id']);
});

it('denies registration without permission', function () {
    $staff = User::factory()->create(['username' => 'staffuser', 'is_active' => true]);
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->get(route('admin.members.register.create'))
        ->assertForbidden();
});
