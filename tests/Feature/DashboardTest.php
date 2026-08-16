<?php

use App\Models\Invoice;
use App\Models\Member;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DashboardSeeder;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->user = User::factory()->create([
        'is_active' => true,
    ]);

    $this->user->assignRole('super-admin');
    $this->seed(DashboardSeeder::class);
});

it('displays dashboard widgets for authorized users', function () {
    $this->actingAs($this->user)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Business Dashboard')
        ->assertSee('Register')
        ->assertSee('RFID Card')
        ->assertSee('POS')
        ->assertSee('Orders')
        ->assertSee('Payment')
        ->assertSee('Renew')
        ->assertSee('Date Range')
        ->assertSee('New Registrations')
        ->assertSee('Active Members')
        ->assertSee('Period Revenue')
        ->assertSee('Product Sales')
        ->assertSee('Low Stock Items')
        ->assertSee('Revenue Trend')
        ->assertSee('Registration Trend')
        ->assertSee('Recent Payments')
        ->assertSee('Recent Registrations')
        ->assertSee('Low Stock Products')
        ->assertSee('Upcoming Due Orders');
});

it('shows upcoming due pos orders on the dashboard', function () {
    $member = Member::factory()->create();
    $product = Product::factory()->create(['selling_price' => 120, 'stock' => 5]);

    $this->actingAs($this->user)
        ->post(route('admin.pos.store'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'member_id' => $member->id,
            'amount_paid' => 0,
            'due_at' => now()->addDays(3)->toDateString(),
        ])
        ->assertRedirect();

    $invoice = Invoice::query()->latest('id')->first();

    $this->actingAs($this->user)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Upcoming Due Orders')
        ->assertSee($invoice->invoice_number)
        ->assertSee($member->name);
});

it('filters dashboard metrics by date range preset', function () {
    $this->actingAs($this->user)
        ->get(route('admin.dashboard', ['preset' => 'today']))
        ->assertSuccessful()
        ->assertSee('Today')
        ->assertSee('Business Dashboard');
});

it('filters dashboard metrics by custom date range', function () {
    $from = now()->subDays(10)->toDateString();
    $to = now()->toDateString();

    $this->actingAs($this->user)
        ->get(route('admin.dashboard', [
            'preset' => 'custom',
            'from_date' => $from,
            'to_date' => $to,
        ]))
        ->assertSuccessful()
        ->assertSee(now()->parse($from)->format('M j, Y'));
});

it('forbids dashboard access without permission', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});
