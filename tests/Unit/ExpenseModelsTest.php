<?php

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates expense tables with expected columns', function () {
    expect(Schema::hasTable('expense_categories'))->toBeTrue()
        ->and(Schema::hasColumns('expense_categories', [
            'name',
            'description',
            'is_active',
            'sort_order',
            'created_by',
        ]))->toBeTrue()
        ->and(Schema::hasTable('expenses'))->toBeTrue()
        ->and(Schema::hasColumns('expenses', [
            'expense_number',
            'expensed_at',
            'expense_category_id',
            'amount',
            'payment_method',
            'paid_to',
            'description',
            'attachment_path',
            'status',
            'created_by',
            'deleted_at',
        ]))->toBeTrue();
});

it('persists expense records with relationships', function () {
    $user = User::factory()->create();

    $category = ExpenseCategory::query()->create([
        'name' => 'Utilities',
        'is_active' => true,
        'sort_order' => 1,
        'created_by' => $user->id,
    ]);

    $expense = Expense::query()->create([
        'expense_number' => 'EXP-20260816-00001',
        'expensed_at' => '2026-08-16',
        'expense_category_id' => $category->id,
        'amount' => 3500,
        'payment_method' => PaymentMethod::Bank,
        'paid_to' => 'City Power Co',
        'description' => 'Monthly electricity bill',
        'status' => ExpenseStatus::Paid,
        'created_by' => $user->id,
    ]);

    expect($expense->category->name)->toBe('Utilities')
        ->and($expense->creator->is($user))->toBeTrue()
        ->and($expense->payment_method)->toBe(PaymentMethod::Bank)
        ->and($expense->status)->toBe(ExpenseStatus::Paid)
        ->and($category->creator->is($user))->toBeTrue()
        ->and($category->expenses)->toHaveCount(1);
});

it('soft deletes expenses but not expense categories', function () {
    $category = ExpenseCategory::factory()->create();
    $expense = Expense::factory()->create([
        'expense_category_id' => $category->id,
    ]);

    $expense->delete();

    expect(Expense::query()->count())->toBe(0)
        ->and(Expense::withTrashed()->count())->toBe(1)
        ->and(ExpenseCategory::query()->count())->toBe(1);
});

it('enforces unique expense numbers', function () {
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->create([
        'expense_number' => 'EXP-20260816-00001',
        'expense_category_id' => $category->id,
    ]);

    expect(fn () => Expense::factory()->create([
        'expense_number' => 'EXP-20260816-00001',
        'expense_category_id' => $category->id,
    ]))->toThrow(QueryException::class);
});
