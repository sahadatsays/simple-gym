<?php

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);
    $this->seed(CategorySeeder::class);

    $this->admin = User::factory()->create([
        'username' => 'adminuser',
        'is_active' => true,
    ]);
    $this->admin->assignRole('super-admin');
});

it('lists products with search and filters', function () {
    $category = Category::query()->where('name', 'Supplements')->first();

    Product::factory()->create([
        'name' => 'Protein Shake',
        'sku' => 'SKU-1001',
        'barcode' => '1234567890123',
        'category_id' => $category->id,
        'status' => ProductStatus::Active,
        'stock' => 20,
    ]);

    Product::factory()->inactive()->create([
        'name' => 'Old Towel',
        'sku' => 'SKU-2002',
        'category_id' => Category::query()->where('name', 'Apparel')->value('id'),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.products.index', ['search' => 'Protein', 'status' => 'active']))
        ->assertSuccessful()
        ->assertSee('Protein Shake')
        ->assertSee('SKU-1001')
        ->assertDontSee('Old Towel');
});

it('creates a product', function () {
    $category = Category::query()->where('name', 'Beverages')->first();

    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [
            'sku' => 'SKU-NEW-01',
            'barcode' => '9876543210987',
            'name' => 'Energy Drink',
            'category_id' => $category->id,
            'purchase_price' => 50,
            'selling_price' => 80,
            'stock' => 25,
            'minimum_stock' => 5,
            'status' => 'active',
        ])
        ->assertRedirect(route('admin.products.index'));

    $product = Product::query()->where('sku', 'SKU-NEW-01')->first();

    expect($product)->not->toBeNull()
        ->and($product->name)->toBe('Energy Drink')
        ->and($product->category_id)->toBe($category->id)
        ->and((float) $product->selling_price)->toBe(80.0)
        ->and($product->stock)->toBe(25);
});

it('auto generates sku when creating a product without one', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [
            'name' => 'Auto SKU Product',
            'purchase_price' => 10,
            'selling_price' => 20,
            'stock' => 5,
            'minimum_stock' => 1,
            'status' => 'active',
        ])
        ->assertRedirect(route('admin.products.index'));

    $product = Product::query()->where('name', 'Auto SKU Product')->first();

    expect($product)->not->toBeNull()
        ->and($product->sku)->toStartWith('PRD-');
});

it('validates required product fields and unique sku', function () {
    Product::factory()->create(['sku' => 'SKU-DUP']);

    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [])
        ->assertSessionHasErrors(['name', 'purchase_price', 'selling_price', 'stock', 'minimum_stock', 'status']);

    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [
            'sku' => 'SKU-DUP',
            'name' => 'Duplicate SKU Product',
            'purchase_price' => 10,
            'selling_price' => 20,
            'stock' => 1,
            'minimum_stock' => 1,
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['sku']);
});

it('updates a product', function () {
    $category = Category::query()->where('name', 'Accessories')->first();
    $product = Product::factory()->create([
        'sku' => 'SKU-EDIT',
        'name' => 'Old Name',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.products.update', $product), [
            'sku' => 'SKU-EDIT',
            'barcode' => '1112223334445',
            'name' => 'Updated Name',
            'category_id' => $category->id,
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock' => 12,
            'minimum_stock' => 3,
            'status' => 'inactive',
        ])
        ->assertRedirect(route('admin.products.index'));

    $product->refresh();

    expect($product->name)->toBe('Updated Name')
        ->and($product->category_id)->toBe($category->id)
        ->and($product->status)->toBe(ProductStatus::Inactive)
        ->and($product->barcode)->toBe('1112223334445');
});

it('adjusts product stock', function () {
    $product = Product::factory()->create([
        'stock' => 10,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.products.adjust-stock', $product), [
            'adjustment' => -4,
            'notes' => 'Sold items',
        ])
        ->assertRedirect();

    expect($product->fresh()->stock)->toBe(6);
});

it('prevents stock adjustment below zero', function () {
    $product = Product::factory()->create([
        'stock' => 3,
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.products.index'))
        ->patch(route('admin.products.adjust-stock', $product), [
            'adjustment' => -10,
        ])
        ->assertRedirect(route('admin.products.index'));

    expect($product->fresh()->stock)->toBe(3);
});

it('looks up a product by barcode', function () {
    $product = Product::factory()->create([
        'barcode' => '5556667778889',
        'sku' => 'SKU-LOOKUP',
        'name' => 'Lookup Product',
        'selling_price' => 99.50,
        'stock' => 7,
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.products.lookup', ['barcode' => '5556667778889']))
        ->assertSuccessful()
        ->assertJsonPath('data.sku', 'SKU-LOOKUP')
        ->assertJsonPath('data.name', 'Lookup Product')
        ->assertJsonPath('data.stock', 7);
});

it('deletes a product', function () {
    $product = Product::factory()->create(['sku' => 'SKU-DELETE']);

    $this->actingAs($this->admin)
        ->delete(route('admin.products.destroy', $product))
        ->assertRedirect(route('admin.products.index'));

    expect(Product::query()->whereKey($product->id)->exists())->toBeFalse()
        ->and(Product::withTrashed()->whereKey($product->id)->exists())->toBeTrue();
});

it('denies access without permission', function () {
    $trainer = User::factory()->create(['username' => 'traineruser', 'is_active' => true]);
    $trainer->assignRole('trainer');

    $this->actingAs($trainer)
        ->get(route('admin.products.index'))
        ->assertForbidden();
});
