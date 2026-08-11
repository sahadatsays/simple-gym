<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\MemberStatus;
use App\Enums\PaymentStatus;
use App\Enums\RfidCardStatus;
use App\Enums\ZktecoDeviceStatus;
use App\Models\GymSetting;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\MembershipRenewal;
use App\Models\Payment;
use App\Models\RfidCard;
use App\Models\User;
use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
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
        'name' => 'Monthly Plan',
        'duration_days' => 30,
        'admission_fee' => 500,
        'membership_fee' => 1500,
    ]);

    config(['queue.default' => 'sync']);
});

it('shows member search page for renewal', function () {
    $member = Member::factory()->create([
        'name' => 'Renewal Candidate',
        'phone' => '01710001111',
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addDays(5),
    ]);

    Member::factory()->create([
        'name' => 'Future Member',
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addDays(60),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.renew.create'))
        ->assertSuccessful()
        ->assertSee('Renewal Candidate')
        ->assertSee('Renew')
        ->assertDontSee('Future Member');
});

it('shows expired members in the renewal review queue', function () {
    Member::factory()->expired()->create([
        'name' => 'Expired Review Member',
        'membership_plan_id' => $this->plan->id,
        'membership_expires_at' => now()->subDays(3),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.renew.create'))
        ->assertSuccessful()
        ->assertSee('Expired Review Member')
        ->assertSee('Expired 3 days ago');
});

it('includes members expiring exactly on the reminder day boundary', function () {
    GymSetting::query()->first()?->update(['membership_reminder_days' => 7]);

    Member::factory()->create([
        'name' => 'Boundary Member',
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addDays(7),
    ]);

    Member::factory()->create([
        'name' => 'Outside Boundary Member',
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addDays(8),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.renew.create'))
        ->assertSuccessful()
        ->assertSee('Boundary Member')
        ->assertDontSee('Outside Boundary Member');
});

it('respects membership reminder days from settings', function () {
    GymSetting::query()->first()?->update(['membership_reminder_days' => 3]);

    Member::factory()->create([
        'name' => 'Inside Window',
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addDays(2),
    ]);

    Member::factory()->create([
        'name' => 'Outside Window',
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addDays(10),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.renew.create'))
        ->assertSuccessful()
        ->assertSee('Inside Window')
        ->assertDontSee('Outside Window');
});

it('paginates renewal review results', function () {
    Member::factory()->count(15)->create([
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addDays(2),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.renew.create', ['per_page' => 10]))
        ->assertSuccessful()
        ->assertSee('page=2', false);
});

it('preserves search query in renewal review filters', function () {
    Member::factory()->create([
        'name' => 'Searchable Renewal Member',
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addDays(1),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.renew.create', [
            'search' => 'Searchable',
            'per_page' => 10,
        ]))
        ->assertSuccessful()
        ->assertSee('Searchable Renewal Member')
        ->assertSee('value="Searchable"', false);
});

it('sorts renewal review members by expiry date descending', function () {
    $later = Member::factory()->create([
        'name' => 'Later Expiry Member',
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addDays(5),
    ]);

    $sooner = Member::factory()->create([
        'name' => 'Sooner Expiry Member',
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addDay(),
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.members.renew.create', ['direction' => 'desc']))
        ->assertSuccessful();

    expect(mb_strpos($response->getContent(), $later->name))
        ->toBeLessThan(mb_strpos($response->getContent(), $sooner->name));
});

it('filters renewal review members by search term', function () {
    Member::factory()->create([
        'name' => 'Renewal Candidate',
        'phone' => '01710001111',
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addDays(5),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.renew.create', ['search' => 'Renewal']))
        ->assertSuccessful()
        ->assertSee('Renewal Candidate')
        ->assertSee('Renew');
});

it('renews an active member from current expiry date', function () {
    $currentExpiry = now()->addDays(10);

    $member = Member::factory()->create([
        'name' => 'Active Member',
        'phone' => '01720002222',
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
        'membership_expires_at' => $currentExpiry,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.members.renew.store', $member), [
            'membership_plan_id' => $this->plan->id,
            'payment_method' => 'cash',
            'amount_received' => 1500,
        ])
        ->assertRedirect();

    $member->refresh();

    expect($member->status)->toBe(MemberStatus::Active)
        ->and($member->membership_expires_at?->toDateString())
        ->toBe($currentExpiry->copy()->addDays(30)->toDateString());

    $invoice = Invoice::query()->where('member_id', $member->id)->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->type)->toBe(InvoiceType::Renewal)
        ->and($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and((float) $invoice->total)->toBe(1500.0);

    $renewal = MembershipRenewal::query()->where('member_id', $member->id)->first();

    expect($renewal)->not->toBeNull()
        ->and($renewal->previous_expires_at?->toDateString())->toBe($currentExpiry->toDateString())
        ->and($renewal->new_expires_at->toDateString())->toBe($currentExpiry->copy()->addDays(30)->toDateString());

    $payment = Payment::query()->where('member_id', $member->id)->latest()->first();

    expect($payment)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Completed)
        ->and($payment->receipt_number)->toStartWith('RCP-');
});

it('renews an expired member from today', function () {
    $member = Member::factory()->expired()->create([
        'name' => 'Expired Member',
        'phone' => '01730003333',
        'membership_plan_id' => $this->plan->id,
        'membership_expires_at' => now()->subDays(15),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.members.renew.store', $member), [
            'membership_plan_id' => $this->plan->id,
            'payment_method' => 'card',
            'amount_received' => 1500,
        ])
        ->assertRedirect();

    $member->refresh();

    expect($member->status)->toBe(MemberStatus::Active)
        ->and($member->membership_expires_at?->toDateString())
        ->toBe(now()->addDays(30)->toDateString());

    $renewal = MembershipRenewal::query()->where('member_id', $member->id)->first();

    expect($renewal->previous_expires_at?->toDateString())
        ->toBe(now()->subDays(15)->toDateString())
        ->and($renewal->new_expires_at->toDateString())
        ->toBe(now()->addDays(30)->toDateString());
});

it('rolls back renewal when payment is insufficient', function () {
    $member = Member::factory()->create([
        'phone' => '01740004444',
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addDays(5),
    ]);

    $originalExpiry = $member->membership_expires_at?->toDateString();

    $this->actingAs($this->admin)
        ->post(route('admin.members.renew.store', $member), [
            'membership_plan_id' => $this->plan->id,
            'payment_method' => 'cash',
            'amount_received' => 100,
        ])
        ->assertSessionHasErrors(['amount_received']);

    expect($member->fresh()->membership_expires_at?->toDateString())->toBe($originalExpiry)
        ->and(Invoice::query()->where('type', InvoiceType::Renewal)->count())->toBe(0)
        ->and(MembershipRenewal::query()->count())->toBe(0);
});

it('shows renewal receipt', function () {
    $member = Member::factory()->create([
        'membership_plan_id' => $this->plan->id,
    ]);

    $invoice = Invoice::factory()->paid()->create([
        'member_id' => $member->id,
        'membership_plan_id' => $this->plan->id,
        'type' => InvoiceType::Renewal,
        'total' => 1500,
    ]);

    MembershipRenewal::factory()->create([
        'member_id' => $member->id,
        'membership_plan_id' => $this->plan->id,
        'invoice_id' => $invoice->id,
    ]);

    Payment::factory()->membershipFee()->create([
        'member_id' => $member->id,
        'invoice_id' => $invoice->id,
        'amount' => 1500,
        'receipt_number' => 'RCP-RENEW-00001',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.receipt', [$member, $invoice]))
        ->assertRedirect(route('admin.invoices.show', $invoice));

    $this->actingAs($this->admin)
        ->get(route('admin.invoices.show', $invoice))
        ->assertSuccessful()
        ->assertSee('Renewal invoice');

    $this->actingAs($this->admin)
        ->get(route('admin.invoices.print', $invoice))
        ->assertSuccessful()
        ->assertSee('RCP-RENEW-00001');
});

it('denies renewal for pending members', function () {
    $member = Member::factory()->create([
        'status' => MemberStatus::Pending,
        'membership_plan_id' => $this->plan->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.renew.edit', $member))
        ->assertNotFound();
});

it('allows staff with payments permission to renew', function () {
    $staff = User::factory()->create(['username' => 'staffuser', 'is_active' => true]);
    $staff->assignRole('staff');

    $member = Member::factory()->create([
        'phone' => '01750005555',
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addDays(3),
    ]);

    $this->actingAs($staff)
        ->post(route('admin.members.renew.store', $member), [
            'membership_plan_id' => $this->plan->id,
            'payment_method' => 'cash',
            'amount_received' => 1500,
        ])
        ->assertRedirect();

    expect(MembershipRenewal::query()->where('member_id', $member->id)->exists())->toBeTrue();
});

it('reactivates a disabled card and syncs the member to the device after renewal', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => ZktecoDeviceStatus::Active,
    ]);

    $member = Member::factory()->expired()->create([
        'member_code' => 'M30001',
        'name' => 'Renewed Member',
        'phone' => '01760006666',
        'membership_plan_id' => $this->plan->id,
        'membership_expires_at' => now()->subDays(10),
        'rfid_card' => null,
    ]);

    $card = RfidCard::factory()->create([
        'card_number' => '1233447',
        'status' => RfidCardStatus::Disabled,
        'member_id' => $member->id,
        'assigned_at' => now()->subMonths(2),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.members.renew.store', $member), [
            'membership_plan_id' => $this->plan->id,
            'payment_method' => 'cash',
            'amount_received' => 1500,
        ])
        ->assertRedirect();

    expect($member->fresh()->status)->toBe(MemberStatus::Active)
        ->and($member->fresh()->rfid_card)->toBe('1233447')
        ->and($card->fresh()->status)->toBe(RfidCardStatus::Active)
        ->and(ZktecoCommand::query()->count())->toBe(1)
        ->and(ZktecoCommand::query()->value('command'))
        ->toBe("DATA UPDATE user Pin=M30001\tName=Renewed Member\tCardNo=1233447\tPri=0\tGrp=1");
});

it('syncs an already active card to the device when a member renews early', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => ZktecoDeviceStatus::Active,
    ]);

    $member = Member::factory()->create([
        'member_code' => 'M30002',
        'name' => 'Early Renewer',
        'phone' => '01770007777',
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addDays(5),
        'rfid_card' => '7654321',
    ]);

    RfidCard::factory()->create([
        'card_number' => '7654321',
        'status' => RfidCardStatus::Active,
        'member_id' => $member->id,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.members.renew.store', $member), [
            'membership_plan_id' => $this->plan->id,
            'payment_method' => 'cash',
            'amount_received' => 1500,
        ])
        ->assertRedirect();

    expect(ZktecoCommand::query()->count())->toBe(1)
        ->and(ZktecoCommand::query()->value('command'))
        ->toContain('Pin=M30002')
        ->toContain('CardNo=7654321');
});
