<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Product;
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

    $this->plan = MembershipPlan::factory()->create();
});

it('lists pos orders for authorized users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.orders.index'))
        ->assertSuccessful()
        ->assertSee('Manage Orders');
});

it('creates a due pos order without upfront payment', function () {
    $member = Member::factory()->create(['membership_plan_id' => $this->plan->id]);
    $product = Product::factory()->create(['selling_price' => 100, 'stock' => 5]);

    $this->actingAs($this->admin)
        ->post(route('admin.pos.store'), [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'member_id' => $member->id,
            'amount_paid' => 0,
            'due_at' => now()->addDays(5)->toDateString(),
        ])
        ->assertRedirect();

    $invoice = Invoice::query()->latest('id')->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->type)->toBe(InvoiceType::PosSale)
        ->and($invoice->status)->toBe(InvoiceStatus::Unpaid)
        ->and((float) $invoice->total)->toBe(200.0)
        ->and($invoice->outstandingBalance())->toBe(200.0)
        ->and(Payment::query()->count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(3);
});

it('creates a partial pos order and allows follow up payment', function () {
    $member = Member::factory()->create(['membership_plan_id' => $this->plan->id]);
    $product = Product::factory()->create(['selling_price' => 100, 'stock' => 4]);

    $this->actingAs($this->admin)
        ->post(route('admin.pos.store'), [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'member_id' => $member->id,
            'amount_paid' => 80,
            'payment_method' => 'cash',
        ])
        ->assertRedirect();

    $invoice = Invoice::query()->latest('id')->first();

    expect($invoice->status)->toBe(InvoiceStatus::Partial)
        ->and($invoice->amountPaid())->toBe(80.0)
        ->and($invoice->outstandingBalance())->toBe(120.0);

    $this->actingAs($this->admin)
        ->post(route('admin.orders.payments.store', $invoice), [
            'amount_paid' => 120,
            'payment_method' => 'cash',
        ])
        ->assertRedirect(route('admin.orders.show', $invoice));

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->fresh()->outstandingBalance())->toBe(0.0)
        ->and(Payment::query()->count())->toBe(2);
});

it('requires a member for due sales', function () {
    $product = Product::factory()->create(['selling_price' => 50, 'stock' => 3]);

    $this->actingAs($this->admin)
        ->from(route('admin.pos.index'))
        ->post(route('admin.pos.store'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'amount_paid' => 0,
        ])
        ->assertRedirect(route('admin.pos.index'))
        ->assertSessionHasErrors('member_id');
});

it('shows pos order history on member profile', function () {
    $member = Member::factory()->create(['membership_plan_id' => $this->plan->id]);
    $product = Product::factory()->create(['selling_price' => 75, 'stock' => 2]);

    $this->actingAs($this->admin)
        ->post(route('admin.pos.store'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'member_id' => $member->id,
            'amount_paid' => 0,
        ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.show', $member))
        ->assertSuccessful()
        ->assertSeeText('POS Orders & Due Balances')
        ->assertSee('INV-');
});
