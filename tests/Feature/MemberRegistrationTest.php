<?php

use App\Enums\InvoiceStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\RfidCardStatus;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\RfidCard;
use App\Models\User;
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
        'status' => RfidCardStatus::Unassigned,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.register.create'))
        ->assertSuccessful()
        ->assertSee('Register Member')
        ->assertSee('Monthly Plan')
        ->assertSee('CARD001');
});

it('completes the full registration workflow', function () {
    Storage::fake('public');

    $card = RfidCard::factory()->create([
        'card_number' => 'CARD999',
        'status' => RfidCardStatus::Unassigned,
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

    expect($card->status)->toBe(RfidCardStatus::Active)
        ->and($card->member_id)->toBe($member->id)
        ->and($member->fresh()->rfid_card)->toBe('CARD999');
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
        ->assertSuccessful()
        ->assertSee('RCP-TEST-00001')
        ->assertSee($member->name)
        ->assertSee($invoice->invoice_number);
});

it('rolls back registration when payment is insufficient', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.members.register.store'), [
            'name' => 'Failed Member',
            'phone' => '01777778888',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => now()->toDateString(),
            'payment_method' => 'cash',
            'amount_received' => 100,
        ])
        ->assertSessionHasErrors(['amount_received']);

    expect(Member::query()->where('phone', '01777778888')->exists())->toBeFalse()
        ->and(Invoice::query()->count())->toBe(0)
        ->and(Payment::query()->count())->toBe(0);
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
