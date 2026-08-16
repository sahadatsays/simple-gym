<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);
    $this->seed(ExpenseCategorySeeder::class);

    $this->admin = User::factory()->create(['username' => 'adminuser', 'is_active' => true]);
    $this->admin->assignRole('super-admin');
});

it('lists expense categories for authorized users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.expense-categories.index'))
        ->assertSuccessful()
        ->assertSee('Expense Categories')
        ->assertSee('Rent')
        ->assertSee('Electricity');
});

it('seeds default expense categories', function () {
    expect(ExpenseCategory::query()->where('name', 'Office Supplies')->exists())->toBeTrue()
        ->and(ExpenseCategory::query()->count())->toBe(count(config('gym.expense_categories')));
});

it('filters expense categories by search and status', function () {
    ExpenseCategory::factory()->create([
        'name' => 'Custom Marketing',
        'description' => 'Promotional spend',
        'is_active' => true,
    ]);

    ExpenseCategory::factory()->inactive()->create([
        'name' => 'Archived Category',
        'description' => 'Old records',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.expense-categories.index', [
            'search' => 'Marketing',
            'status' => 'active',
        ]))
        ->assertSuccessful()
        ->assertSee('Custom Marketing')
        ->assertDontSee('Archived Category');
});

it('creates an expense category', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.expense-categories.store'), [
            'name' => 'Facility Upgrade',
            'description' => 'Building improvements',
            'is_active' => true,
            'sort_order' => 10,
        ])
        ->assertRedirect(route('admin.expense-categories.index'));

    $category = ExpenseCategory::query()->where('name', 'Facility Upgrade')->first();

    expect($category)->not->toBeNull()
        ->and($category->created_by)->toBe($this->admin->id);
});

it('updates an expense category', function () {
    $category = ExpenseCategory::factory()->create(['name' => 'Old Category']);

    $this->actingAs($this->admin)
        ->put(route('admin.expense-categories.update', $category), [
            'name' => 'Updated Category',
            'description' => 'Updated description',
            'is_active' => false,
            'sort_order' => 3,
        ])
        ->assertRedirect(route('admin.expense-categories.index'));

    expect($category->fresh())
        ->name->toBe('Updated Category')
        ->is_active->toBeFalse()
        ->sort_order->toBe(3);
});

it('prevents deleting a category used by expenses', function () {
    $category = ExpenseCategory::factory()->create();
    Expense::factory()->create(['expense_category_id' => $category->id]);

    $this->actingAs($this->admin)
        ->from(route('admin.expense-categories.index'))
        ->delete(route('admin.expense-categories.destroy', $category))
        ->assertRedirect(route('admin.expense-categories.index'))
        ->assertSessionHasErrors('category');

    expect(ExpenseCategory::query()->whereKey($category->id)->exists())->toBeTrue();
});

it('prevents deleting a category used by soft-deleted expenses', function () {
    $category = ExpenseCategory::factory()->create();
    $expense = Expense::factory()->create(['expense_category_id' => $category->id]);
    $expense->delete();

    $this->actingAs($this->admin)
        ->from(route('admin.expense-categories.index'))
        ->delete(route('admin.expense-categories.destroy', $category))
        ->assertRedirect(route('admin.expense-categories.index'))
        ->assertSessionHasErrors('category');

    expect(ExpenseCategory::query()->whereKey($category->id)->exists())->toBeTrue();
});

it('deletes an unused expense category', function () {
    $category = ExpenseCategory::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.expense-categories.destroy', $category))
        ->assertRedirect(route('admin.expense-categories.index'));

    expect(ExpenseCategory::query()->whereKey($category->id)->exists())->toBeFalse();
});

it('validates unique category names', function () {
    ExpenseCategory::factory()->create(['name' => 'Unique Category']);

    $this->actingAs($this->admin)
        ->from(route('admin.expense-categories.create'))
        ->post(route('admin.expense-categories.store'), [
            'name' => 'Unique Category',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.expense-categories.create'))
        ->assertSessionHasErrors('name');
});

it('forbids expense category management without permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $category = ExpenseCategory::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.expense-categories.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('admin.expense-categories.destroy', $category))
        ->assertForbidden();
});

it('shows expense categories in the sidebar for authorized users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Expense Categories', false);
});
