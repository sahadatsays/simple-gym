<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
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

    $this->plan = MembershipPlan::factory()->create([
        'admission_fee' => 500,
        'membership_fee' => 1500,
    ]);
});

it('shows payment history for authorized users', function () {
    $member = Member::factory()->create(['membership_plan_id' => $this->plan->id]);
    $invoice = Invoice::factory()->paid()->create([
        'member_id' => $member->id,
        'membership_plan_id' => $this->plan->id,
    ]);

    Payment::factory()->membershipFee()->create([
        'member_id' => $member->id,
        'invoice_id' => $invoice->id,
        'amount' => 2000,
        'receipt_number' => 'RCP-HIST-00001',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.payments.index'))
        ->assertSuccessful()
        ->assertSee('Payment History')
        ->assertSee('RCP-HIST-00001')
        ->assertSee($member->name);
});

it('shows the receive payment form', function () {
    $member = Member::factory()->create(['membership_plan_id' => $this->plan->id]);

    Invoice::factory()->create([
        'member_id' => $member->id,
        'membership_plan_id' => $this->plan->id,
        'status' => InvoiceStatus::Unpaid,
        'subtotal' => 2000,
        'total' => 2000,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.payments.create'))
        ->assertSuccessful()
        ->assertSee('Receive Payment')
        ->assertSee('Invoice Payment')
        ->assertSee('POS Sale');
});

it('receives payment for an unpaid invoice', function () {
    $member = Member::factory()->create(['membership_plan_id' => $this->plan->id]);

    $invoice = Invoice::factory()->create([
        'member_id' => $member->id,
        'membership_plan_id' => $this->plan->id,
        'type' => InvoiceType::Registration,
        'status' => InvoiceStatus::Unpaid,
        'subtotal' => 2000,
        'total' => 2000,
        'line_items' => [
            ['description' => 'Admission Fee', 'amount' => 500],
            ['description' => 'Membership Fee', 'amount' => 1500],
        ],
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.payments.store'), [
            'mode' => 'invoice',
            'invoice_id' => $invoice->id,
            'payment_method' => PaymentMethod::Cash->value,
            'amount_paid' => 2000,
        ])
        ->assertRedirect();

    $invoice->refresh();
    $payment = Payment::query()->where('invoice_id', $invoice->id)->first();

    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($payment)->not->toBeNull()
        ->and($payment->type)->toBe(PaymentType::AdmissionFee)
        ->and((float) $payment->amount)->toBe(2000.0)
        ->and($payment->status)->toBe(PaymentStatus::Completed);
});

it('applies discount when receiving invoice payment', function () {
    $member = Member::factory()->create(['membership_plan_id' => $this->plan->id]);

    $invoice = Invoice::factory()->create([
        'member_id' => $member->id,
        'membership_plan_id' => $this->plan->id,
        'status' => InvoiceStatus::Unpaid,
        'subtotal' => 2000,
        'total' => 2000,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.payments.store'), [
            'mode' => 'invoice',
            'invoice_id' => $invoice->id,
            'payment_method' => PaymentMethod::Card->value,
            'discount_amount' => 200,
            'amount_paid' => 1800,
        ])
        ->assertRedirect();

    $invoice->refresh();
    $payment = Payment::query()->where('invoice_id', $invoice->id)->first();

    expect((float) $invoice->discount_amount)->toBe(200.0)
        ->and((float) $invoice->total)->toBe(1800.0)
        ->and((float) $payment->discount_amount)->toBe(200.0)
        ->and((float) $payment->amount)->toBe(1800.0);
});

it('rejects payment when amount exceeds invoice total', function () {
    $member = Member::factory()->create(['membership_plan_id' => $this->plan->id]);

    $invoice = Invoice::factory()->create([
        'member_id' => $member->id,
        'membership_plan_id' => $this->plan->id,
        'status' => InvoiceStatus::Unpaid,
        'subtotal' => 2000,
        'total' => 2000,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.payments.store'), [
            'mode' => 'invoice',
            'invoice_id' => $invoice->id,
            'payment_method' => PaymentMethod::Cash->value,
            'amount_paid' => 2500,
        ])
        ->assertSessionHasErrors(['amount_paid']);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid)
        ->and(Payment::query()->count())->toBe(0);
});

it('records a pos sale with optional member', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.payments.store'), [
            'mode' => 'pos',
            'description' => 'Protein Shake',
            'item_amount' => 500,
            'type' => PaymentType::PosSale->value,
            'payment_method' => PaymentMethod::MobileBanking->value,
            'payment_reference' => 'MBK-12345',
            'amount_paid' => 500,
        ])
        ->assertRedirect();

    $payment = Payment::query()->first();
    $invoice = Invoice::query()->first();

    expect($payment)->not->toBeNull()
        ->and($payment->type)->toBe(PaymentType::PosSale)
        ->and($payment->member_id)->toBeNull()
        ->and($invoice->type)->toBe(InvoiceType::PosSale)
        ->and($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->line_items[0]['description'])->toBe('Protein Shake');
});

it('shows payment details and printable receipt', function () {
    $member = Member::factory()->create(['membership_plan_id' => $this->plan->id]);
    $invoice = Invoice::factory()->paid()->create([
        'member_id' => $member->id,
        'membership_plan_id' => $this->plan->id,
    ]);

    $payment = Payment::factory()->membershipFee()->create([
        'member_id' => $member->id,
        'invoice_id' => $invoice->id,
        'amount' => 1500,
        'receipt_number' => 'RCP-DETAIL-00001',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.payments.show', $payment))
        ->assertSuccessful()
        ->assertSee('RCP-DETAIL-00001')
        ->assertSee($member->name)
        ->assertSee($invoice->invoice_number);

    $this->actingAs($this->admin)
        ->get(route('admin.payments.receipt', $payment))
        ->assertRedirect(route('admin.invoices.thermal', $payment->invoice));

    $this->actingAs($this->admin)
        ->get(route('admin.invoices.thermal', $payment->invoice))
        ->assertSuccessful()
        ->assertSee('RCP-DETAIL-00001')
        ->assertSee('Print');
});

it('denies payment management without permission', function () {
    $trainer = User::factory()->create(['username' => 'traineruser', 'is_active' => true]);
    $trainer->assignRole('trainer');

    $this->actingAs($trainer)
        ->get(route('admin.payments.index'))
        ->assertForbidden();
});
