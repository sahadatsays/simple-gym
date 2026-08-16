<?php

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Repositories\ExpenseRepository;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);
    $this->seed(ExpenseCategorySeeder::class);

    $this->admin = User::factory()->create(['username' => 'adminuser', 'is_active' => true]);
    $this->admin->assignRole('super-admin');

    $this->category = ExpenseCategory::factory()->create(['name' => 'Utilities']);
});

it('lists expenses for authorized users', function () {
    Expense::factory()->create([
        'expense_category_id' => $this->category->id,
        'paid_to' => 'City Power Co',
        'description' => 'Monthly electricity bill',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.expenses.index'))
        ->assertSuccessful()
        ->assertSee('Expenses')
        ->assertSee('City Power Co')
        ->assertSee('Monthly electricity bill');
});

it('filters expenses by search, category, method, status, and date range', function () {
    $otherCategory = ExpenseCategory::factory()->create(['name' => 'Custom Marketing']);

    Expense::factory()->create([
        'expense_number' => 'EXP-20260816-00001',
        'expensed_at' => '2026-08-10',
        'expense_category_id' => $this->category->id,
        'payment_method' => PaymentMethod::Cash,
        'status' => ExpenseStatus::Paid,
        'paid_to' => 'Visible Vendor',
    ]);

    Expense::factory()->cancelled()->create([
        'expense_number' => 'EXP-20260816-00002',
        'expensed_at' => '2026-07-01',
        'expense_category_id' => $otherCategory->id,
        'payment_method' => PaymentMethod::Bank,
        'paid_to' => 'Hidden Vendor',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.expenses.index', [
            'search' => 'Visible',
            'expense_category_id' => $this->category->id,
            'payment_method' => PaymentMethod::Cash->value,
            'status' => ExpenseStatus::Paid->value,
            'from_date' => '2026-08-01',
            'to_date' => '2026-08-31',
        ]))
        ->assertSuccessful()
        ->assertSee('Visible Vendor')
        ->assertDontSee('Hidden Vendor');
});

it('creates an expense with an auto-generated number and attachment', function () {
    $attachment = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

    $response = $this->actingAs($this->admin)
        ->post(route('admin.expenses.store'), [
            'expensed_at' => '2026-08-16',
            'expense_category_id' => $this->category->id,
            'amount' => 3500,
            'payment_method' => PaymentMethod::Bank->value,
            'paid_to' => 'City Power Co',
            'description' => 'Electricity bill',
            'status' => ExpenseStatus::Paid->value,
            'attachment' => $attachment,
        ]);

    $expense = Expense::query()->first();

    expect($expense)->not->toBeNull()
        ->and($expense->expense_number)->toStartWith('EXP-'.now()->format('Ymd'))
        ->and((float) $expense->amount)->toBe(3500.0)
        ->and($expense->created_by)->toBe($this->admin->id)
        ->and($expense->status)->toBe(ExpenseStatus::Paid)
        ->and($expense->attachment_path)->not->toBeNull();

    Storage::disk('public')->assertExists($expense->attachment_path);

    $response->assertRedirect(route('admin.expenses.show', $expense));
});

it('shows an expense detail page', function () {
    $expense = Expense::factory()->create([
        'expense_category_id' => $this->category->id,
        'paid_to' => 'Office Depot',
        'created_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.expenses.show', $expense))
        ->assertSuccessful()
        ->assertSee($expense->expense_number)
        ->assertSee($this->category->name)
        ->assertSee('Office Depot')
        ->assertSee($this->admin->name);
});

it('updates an expense and replaces its attachment', function () {
    $expense = Expense::factory()->create([
        'expense_category_id' => $this->category->id,
        'attachment_path' => 'expenses/attachments/old.pdf',
    ]);

    Storage::disk('public')->put('expenses/attachments/old.pdf', 'old');

    $newCategory = ExpenseCategory::factory()->create(['name' => 'Custom Office Supplies']);

    $response = $this->actingAs($this->admin)
        ->put(route('admin.expenses.update', $expense), [
            'expensed_at' => '2026-08-17',
            'expense_category_id' => $newCategory->id,
            'amount' => 1200,
            'payment_method' => PaymentMethod::MobileBanking->value,
            'paid_to' => 'Updated Vendor',
            'description' => 'Updated expense',
            'status' => ExpenseStatus::Cancelled->value,
            'attachment' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);

    $expense->refresh();

    expect((float) $expense->amount)->toBe(1200.0)
        ->and($expense->expense_category_id)->toBe($newCategory->id)
        ->and($expense->paid_to)->toBe('Updated Vendor')
        ->and($expense->status)->toBe(ExpenseStatus::Cancelled)
        ->and($expense->attachment_path)->not->toBe('expenses/attachments/old.pdf');

    Storage::disk('public')->assertMissing('expenses/attachments/old.pdf');
    Storage::disk('public')->assertExists($expense->attachment_path);

    $response->assertRedirect(route('admin.expenses.show', $expense));
});

it('soft deletes an expense and preserves history', function () {
    $expense = Expense::factory()->create([
        'expense_category_id' => $this->category->id,
        'attachment_path' => 'expenses/attachments/delete-me.pdf',
    ]);

    Storage::disk('public')->put('expenses/attachments/delete-me.pdf', 'delete');

    $this->actingAs($this->admin)
        ->delete(route('admin.expenses.destroy', $expense))
        ->assertRedirect(route('admin.expenses.index'));

    expect(Expense::query()->count())->toBe(0)
        ->and(Expense::withTrashed()->count())->toBe(1);

    Storage::disk('public')->assertMissing('expenses/attachments/delete-me.pdf');
});

it('validates expense amount must be greater than zero', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.expenses.create'))
        ->post(route('admin.expenses.store'), [
            'expensed_at' => '2026-08-16',
            'expense_category_id' => $this->category->id,
            'amount' => 0,
            'payment_method' => PaymentMethod::Cash->value,
            'status' => ExpenseStatus::Paid->value,
        ])
        ->assertRedirect(route('admin.expenses.create'))
        ->assertSessionHasErrors('amount');
});

it('rejects negative expense amounts', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.expenses.create'))
        ->post(route('admin.expenses.store'), [
            'expensed_at' => '2026-08-16',
            'expense_category_id' => $this->category->id,
            'amount' => -100,
            'payment_method' => PaymentMethod::Cash->value,
            'status' => ExpenseStatus::Paid->value,
        ])
        ->assertRedirect(route('admin.expenses.create'))
        ->assertSessionHasErrors('amount');
});

it('forbids expense management without permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $expense = Expense::factory()->create([
        'expense_category_id' => $this->category->id,
    ]);

    $this->actingAs($user)
        ->get(route('admin.expenses.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.expenses.create'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.expenses.show', $expense))
        ->assertForbidden();
});

it('shows expenses in the sidebar for authorized users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Expenses', false);
});

it('generates unique expense numbers for same-day records', function () {
    $first = app(ExpenseRepository::class)->nextExpenseNumber();
    Expense::factory()->create(['expense_number' => $first, 'expense_category_id' => $this->category->id]);
    $second = app(ExpenseRepository::class)->nextExpenseNumber();

    expect($first)->not->toBe($second)
        ->and($first)->toStartWith('EXP-'.now()->format('Ymd'))
        ->and($second)->toStartWith('EXP-'.now()->format('Ymd'));
});
