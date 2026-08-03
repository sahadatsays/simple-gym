<?php

use App\Enums\InvoiceType;
use App\Enums\PaymentType;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSale;
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

    $this->plan = MembershipPlan::factory()->create();
});

it('shows the pos terminal for authorized users', function () {
    $product = Product::factory()->create([
        'name' => 'Protein Shake',
        'stock' => 12,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.pos.index'))
        ->assertSuccessful()
        ->assertSee('Point of Sale')
        ->assertSee('Barcode Scanner')
        ->assertSee($product->name);
});

it('searches products for pos', function () {
    Product::factory()->create([
        'name' => 'Yoga Mat',
        'sku' => 'SKU-YOGA-001',
        'stock' => 5,
    ]);

    Product::factory()->outOfStock()->create([
        'name' => 'Sold Out Item',
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.pos.products.search', ['search' => 'Yoga']))
        ->assertSuccessful()
        ->assertJsonPath('data.0.name', 'Yoga Mat')
        ->assertJsonMissing(['name' => 'Sold Out Item']);
});

it('scans a product by barcode or sku', function () {
    $product = Product::factory()->create([
        'name' => 'Energy Drink',
        'barcode' => '123456789012',
        'sku' => 'SKU-ENERGY-001',
        'stock' => 8,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.pos.products.scan'), ['code' => '123456789012'])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $product->id);

    $this->actingAs($this->admin)
        ->postJson(route('admin.pos.products.scan'), ['code' => 'SKU-ENERGY-001'])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $product->id);
});

it('returns not found when scanning unavailable product', function () {
    Product::factory()->outOfStock()->create([
        'barcode' => '000000000000',
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.pos.products.scan'), ['code' => '000000000000'])
        ->assertNotFound();
});

it('completes a pos sale and reduces stock after payment', function () {
    $member = Member::factory()->create(['membership_plan_id' => $this->plan->id]);

    $protein = Product::factory()->create([
        'name' => 'Whey Protein',
        'selling_price' => 100,
        'stock' => 10,
    ]);

    $shake = Product::factory()->create([
        'name' => 'Protein Shake',
        'selling_price' => 50,
        'stock' => 6,
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('admin.pos.store'), [
            'items' => [
                ['product_id' => $protein->id, 'quantity' => 2],
                ['product_id' => $shake->id, 'quantity' => 1],
            ],
            'member_id' => $member->id,
            'discount_amount' => 20,
            'payment_method' => 'cash',
            'payment_reference' => 'POS-001',
        ]);

    $payment = Payment::query()->latest('id')->first();

    $response
        ->assertRedirect(route('admin.payments.receipt', ['payment' => $payment, 'pos' => 1]));

    expect($payment)->not->toBeNull()
        ->and($payment->type)->toBe(PaymentType::PosSale)
        ->and((float) $payment->amount)->toBe(230.0)
        ->and($payment->member_id)->toBe($member->id);

    expect($protein->fresh()->stock)->toBe(8)
        ->and($shake->fresh()->stock)->toBe(5);

    expect(ProductSale::query()->count())->toBe(2);

    $invoice = $payment->invoice;

    expect($invoice)->not->toBeNull()
        ->and($invoice->type)->toBe(InvoiceType::PosSale)
        ->and((float) $invoice->discount_amount)->toBe(20.0);
});

it('rejects pos checkout when stock is insufficient', function () {
    $product = Product::factory()->create([
        'stock' => 1,
        'selling_price' => 25,
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.pos.index'))
        ->post(route('admin.pos.store'), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
            'payment_method' => 'cash',
        ])
        ->assertRedirect(route('admin.pos.index'))
        ->assertSessionHasErrors('items.0.quantity');

    expect($product->fresh()->stock)->toBe(1)
        ->and(ProductSale::query()->count())->toBe(0)
        ->and(Payment::query()->count())->toBe(0);
});

it('completes pos checkout with prices that cause floating point drift', function () {
    $first = Product::factory()->create([
        'selling_price' => 6.81,
        'stock' => 5,
    ]);

    $second = Product::factory()->create([
        'selling_price' => 116.79,
        'stock' => 5,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.pos.store'), [
            'items' => [
                ['product_id' => $first->id, 'quantity' => 1],
                ['product_id' => $second->id, 'quantity' => 1],
            ],
            'payment_method' => 'cash',
        ])
        ->assertRedirect();

    $payment = Payment::query()->latest('id')->first();

    expect($payment)->not->toBeNull()
        ->and((float) $payment->amount)->toBe(123.6);
});

it('denies pos access without permission', function () {
    $trainer = User::factory()->create(['username' => 'traineruser', 'is_active' => true]);
    $trainer->assignRole('trainer');

    $this->actingAs($trainer)
        ->get(route('admin.pos.index'))
        ->assertForbidden();
});
