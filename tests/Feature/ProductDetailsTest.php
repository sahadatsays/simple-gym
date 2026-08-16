<?php

use App\Enums\InvoiceType;
use App\Models\Invoice;
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
});

it('shows product details with sales reports', function () {
    $product = Product::factory()->create([
        'name' => 'Whey Protein',
        'sku' => 'SKU-PROTEIN',
        'selling_price' => 100,
        'purchase_price' => 60,
        'stock' => 15,
    ]);

    $invoice = Invoice::factory()->paid()->create([
        'type' => InvoiceType::PosSale,
        'member_id' => null,
        'membership_plan_id' => null,
    ]);

    $payment = Payment::factory()->posSale()->create([
        'invoice_id' => $invoice->id,
        'amount' => 200,
        'receipt_number' => 'RCP-PROD-00001',
    ]);

    ProductSale::factory()->create([
        'product_id' => $product->id,
        'invoice_id' => $invoice->id,
        'payment_id' => $payment->id,
        'quantity' => 2,
        'unit_price' => 100,
        'unit_cost' => 60,
        'line_total' => 200,
        'sold_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.products.show', $product))
        ->assertSuccessful()
        ->assertSee('Whey Protein')
        ->assertSee('SKU-PROTEIN')
        ->assertSee('Total Revenue')
        ->assertSee('Sales History')
        ->assertSee('Monthly Performance')
        ->assertSee('RCP-PROD-00001')
        ->assertSee('Stock Activity');
});

it('filters product sales history by date range', function () {
    $product = Product::factory()->create(['name' => 'Filtered Product']);

    $invoice = Invoice::factory()->paid()->create([
        'type' => InvoiceType::PosSale,
        'member_id' => null,
        'membership_plan_id' => null,
    ]);

    $payment = Payment::factory()->posSale()->create([
        'invoice_id' => $invoice->id,
        'receipt_number' => 'RCP-OLD-00001',
    ]);

    ProductSale::factory()->create([
        'product_id' => $product->id,
        'invoice_id' => $invoice->id,
        'payment_id' => $payment->id,
        'sold_at' => now()->subMonths(2),
    ]);

    $recentInvoice = Invoice::factory()->paid()->create([
        'type' => InvoiceType::PosSale,
        'member_id' => null,
        'membership_plan_id' => null,
    ]);

    $recentPayment = Payment::factory()->posSale()->create([
        'invoice_id' => $recentInvoice->id,
        'receipt_number' => 'RCP-NEW-00001',
    ]);

    ProductSale::factory()->create([
        'product_id' => $product->id,
        'invoice_id' => $recentInvoice->id,
        'payment_id' => $recentPayment->id,
        'sold_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.products.show', [
            'product' => $product,
            'from_date' => now()->subDays(7)->toDateString(),
            'to_date' => now()->toDateString(),
        ]))
        ->assertSuccessful()
        ->assertSee('RCP-NEW-00001')
        ->assertDontSee('RCP-OLD-00001');
});

it('records product sale and decrements stock from pos payment', function () {
    $product = Product::factory()->create([
        'name' => 'Energy Bar',
        'selling_price' => 50,
        'purchase_price' => 30,
        'stock' => 10,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.pos.store'), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
            'amount_paid' => 150,
            'payment_method' => 'cash',
        ])
        ->assertRedirect();

    expect(ProductSale::query()->where('product_id', $product->id)->exists())->toBeTrue()
        ->and($product->fresh()->stock)->toBe(7);

    $sale = ProductSale::query()->where('product_id', $product->id)->first();

    expect($sale->quantity)->toBe(3)
        ->and((float) $sale->line_total)->toBe(150.0);
});

it('denies product details without permission', function () {
    $product = Product::factory()->create();
    $trainer = User::factory()->create(['username' => 'traineruser', 'is_active' => true]);
    $trainer->assignRole('trainer');

    $this->actingAs($trainer)
        ->get(route('admin.products.show', $product))
        ->assertForbidden();
});
