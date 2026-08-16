<?php

use App\Models\Investment;
use App\Models\InvestmentCategory;
use App\Models\User;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\InvestmentCategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);
    $this->seed(InvestmentCategorySeeder::class);

    $this->admin = User::factory()->create(['username' => 'adminuser', 'is_active' => true]);
    $this->admin->assignRole('super-admin');
});

it('lists investment categories for authorized users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.investment-categories.index'))
        ->assertSuccessful()
        ->assertSee('Investment Categories')
        ->assertSee('Equipment')
        ->assertSee('Initial Capital');
});

it('seeds default investment categories', function () {
    expect(InvestmentCategory::query()->where('name', 'Working Capital')->exists())->toBeTrue()
        ->and(InvestmentCategory::query()->count())->toBe(count(config('gym.investment_categories')));
});

it('filters investment categories by search and status', function () {
    InvestmentCategory::factory()->create([
        'name' => 'Custom Marketing',
        'description' => 'Promotional spend',
        'is_active' => true,
    ]);

    InvestmentCategory::factory()->inactive()->create([
        'name' => 'Archived Category',
        'description' => 'Old records',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.investment-categories.index', [
            'search' => 'Marketing',
            'status' => 'active',
        ]))
        ->assertSuccessful()
        ->assertSee('Custom Marketing')
        ->assertDontSee('Archived Category');
});

it('creates an investment category', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.investment-categories.store'), [
            'name' => 'Facility Upgrade',
            'description' => 'Building improvements',
            'is_active' => true,
            'sort_order' => 10,
        ])
        ->assertRedirect(route('admin.investment-categories.index'));

    expect(InvestmentCategory::query()->where('name', 'Facility Upgrade')->exists())->toBeTrue();
});

it('updates an investment category', function () {
    $category = InvestmentCategory::factory()->create(['name' => 'Old Category']);

    $this->actingAs($this->admin)
        ->put(route('admin.investment-categories.update', $category), [
            'name' => 'Updated Category',
            'description' => 'Updated description',
            'is_active' => false,
            'sort_order' => 3,
        ])
        ->assertRedirect(route('admin.investment-categories.index'));

    expect($category->fresh())
        ->name->toBe('Updated Category')
        ->is_active->toBeFalse()
        ->sort_order->toBe(3);
});

it('prevents deleting a category used by investments', function () {
    $category = InvestmentCategory::factory()->create();
    Investment::factory()->create(['investment_category_id' => $category->id]);

    $this->actingAs($this->admin)
        ->from(route('admin.investment-categories.index'))
        ->delete(route('admin.investment-categories.destroy', $category))
        ->assertRedirect(route('admin.investment-categories.index'))
        ->assertSessionHasErrors('category');

    expect(InvestmentCategory::query()->whereKey($category->id)->exists())->toBeTrue();
});

it('prevents deleting a category used by soft-deleted investments', function () {
    $category = InvestmentCategory::factory()->create();
    $investment = Investment::factory()->create(['investment_category_id' => $category->id]);
    $investment->delete();

    $this->actingAs($this->admin)
        ->from(route('admin.investment-categories.index'))
        ->delete(route('admin.investment-categories.destroy', $category))
        ->assertRedirect(route('admin.investment-categories.index'))
        ->assertSessionHasErrors('category');

    expect(InvestmentCategory::query()->whereKey($category->id)->exists())->toBeTrue();
});

it('deletes an unused investment category', function () {
    $category = InvestmentCategory::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.investment-categories.destroy', $category))
        ->assertRedirect(route('admin.investment-categories.index'));

    expect(InvestmentCategory::query()->whereKey($category->id)->exists())->toBeFalse();
});

it('validates unique category names', function () {
    $category = InvestmentCategory::factory()->create(['name' => 'Unique Category']);

    $this->actingAs($this->admin)
        ->from(route('admin.investment-categories.create'))
        ->post(route('admin.investment-categories.store'), [
            'name' => 'Unique Category',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.investment-categories.create'))
        ->assertSessionHasErrors('name');
});

it('forbids investment category management without permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $category = InvestmentCategory::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.investment-categories.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('admin.investment-categories.destroy', $category))
        ->assertForbidden();
});

it('shows investment categories in the sidebar for authorized users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Investment Categories', false);
});
