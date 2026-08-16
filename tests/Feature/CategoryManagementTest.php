<?php

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

    $this->admin = User::factory()->create(['username' => 'adminuser', 'is_active' => true]);
    $this->admin->assignRole('super-admin');
});

it('lists categories for authorized users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.categories.index'))
        ->assertSuccessful()
        ->assertSee('Product Categories')
        ->assertSee('Supplements');
});

it('creates a category', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), [
            'name' => 'Recovery Gear',
            'description' => 'Foam rollers and massage tools',
            'is_active' => true,
            'sort_order' => 2,
        ])
        ->assertRedirect(route('admin.categories.index'));

    expect(Category::query()->where('name', 'Recovery Gear')->exists())->toBeTrue();
});

it('updates a category', function () {
    $category = Category::factory()->create(['name' => 'Old Name']);

    $this->actingAs($this->admin)
        ->put(route('admin.categories.update', $category), [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'is_active' => false,
            'sort_order' => 5,
        ])
        ->assertRedirect(route('admin.categories.index'));

    expect($category->fresh())
        ->name->toBe('Updated Name')
        ->is_active->toBeFalse();
});

it('prevents deleting a category with products', function () {
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id]);

    $this->actingAs($this->admin)
        ->from(route('admin.categories.index'))
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect(route('admin.categories.index'))
        ->assertSessionHasErrors('category');

    expect(Category::query()->whereKey($category->id)->exists())->toBeTrue();
});

it('deletes an empty category', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect(route('admin.categories.index'));

    expect(Category::query()->whereKey($category->id)->exists())->toBeFalse();
});
