<?php

use App\Enums\PaymentMethod;
use App\Models\Investment;
use App\Models\InvestmentCategory;
use App\Models\User;
use App\Repositories\InvestmentRepository;
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

    $this->admin = User::factory()->create(['username' => 'adminuser', 'is_active' => true]);
    $this->admin->assignRole('super-admin');

    $this->category = InvestmentCategory::factory()->create(['name' => 'Equipment']);
});

it('lists investments for authorized users', function () {
    Investment::factory()->create([
        'investment_category_id' => $this->category->id,
        'description' => 'New treadmill purchase',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.investments.index'))
        ->assertSuccessful()
        ->assertSee('Investments')
        ->assertSee('New treadmill purchase');
});

it('filters investments by search, category, method, and date range', function () {
    $otherCategory = InvestmentCategory::factory()->create(['name' => 'Renovation']);

    Investment::factory()->create([
        'investment_number' => 'INV-20260816-00001',
        'invested_at' => '2026-08-10',
        'investment_category_id' => $this->category->id,
        'payment_method' => PaymentMethod::Cash,
        'description' => 'Visible investment',
    ]);

    Investment::factory()->create([
        'investment_number' => 'INV-20260816-00002',
        'invested_at' => '2026-07-01',
        'investment_category_id' => $otherCategory->id,
        'payment_method' => PaymentMethod::Bank,
        'description' => 'Hidden investment',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.investments.index', [
            'search' => 'Visible',
            'investment_category_id' => $this->category->id,
            'payment_method' => PaymentMethod::Cash->value,
            'from_date' => '2026-08-01',
            'to_date' => '2026-08-31',
        ]))
        ->assertSuccessful()
        ->assertSee('Visible investment')
        ->assertDontSee('Hidden investment');
});

it('creates an investment with an auto-generated number and attachment', function () {
    $attachment = UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf');

    $response = $this->actingAs($this->admin)
        ->post(route('admin.investments.store'), [
            'invested_at' => '2026-08-16',
            'investment_category_id' => $this->category->id,
            'amount' => 15000,
            'payment_method' => PaymentMethod::Bank->value,
            'description' => 'Cardio equipment upgrade',
            'attachment' => $attachment,
        ]);

    $investment = Investment::query()->first();

    expect($investment)->not->toBeNull()
        ->and($investment->investment_number)->toStartWith('INV-'.now()->format('Ymd'))
        ->and((float) $investment->amount)->toBe(15000.0)
        ->and($investment->created_by)->toBe($this->admin->id)
        ->and($investment->attachment_path)->not->toBeNull();

    Storage::disk('public')->assertExists($investment->attachment_path);

    $response->assertRedirect(route('admin.investments.show', $investment));
});

it('shows an investment detail page', function () {
    $investment = Investment::factory()->create([
        'investment_category_id' => $this->category->id,
        'created_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.investments.show', $investment))
        ->assertSuccessful()
        ->assertSee($investment->investment_number)
        ->assertSee($this->category->name)
        ->assertSee($this->admin->name);
});

it('updates an investment and replaces its attachment', function () {
    $investment = Investment::factory()->create([
        'investment_category_id' => $this->category->id,
        'attachment_path' => 'investments/attachments/old.pdf',
    ]);

    Storage::disk('public')->put('investments/attachments/old.pdf', 'old');

    $newCategory = InvestmentCategory::factory()->create(['name' => 'Facility']);

    $response = $this->actingAs($this->admin)
        ->put(route('admin.investments.update', $investment), [
            'invested_at' => '2026-08-17',
            'investment_category_id' => $newCategory->id,
            'amount' => 20000,
            'payment_method' => PaymentMethod::MobileBanking->value,
            'description' => 'Updated investment',
            'attachment' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);

    $investment->refresh();

    expect((float) $investment->amount)->toBe(20000.0)
        ->and($investment->investment_category_id)->toBe($newCategory->id)
        ->and($investment->description)->toBe('Updated investment')
        ->and($investment->attachment_path)->not->toBe('investments/attachments/old.pdf');

    Storage::disk('public')->assertMissing('investments/attachments/old.pdf');
    Storage::disk('public')->assertExists($investment->attachment_path);

    $response->assertRedirect(route('admin.investments.show', $investment));
});

it('deletes an investment and its attachment', function () {
    $investment = Investment::factory()->create([
        'investment_category_id' => $this->category->id,
        'attachment_path' => 'investments/attachments/delete-me.pdf',
    ]);

    Storage::disk('public')->put('investments/attachments/delete-me.pdf', 'delete');

    $this->actingAs($this->admin)
        ->delete(route('admin.investments.destroy', $investment))
        ->assertRedirect(route('admin.investments.index'));

    expect(Investment::query()->count())->toBe(0)
        ->and(Investment::withTrashed()->count())->toBe(1);

    Storage::disk('public')->assertMissing('investments/attachments/delete-me.pdf');
});

it('validates investment amount must be greater than zero', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.investments.create'))
        ->post(route('admin.investments.store'), [
            'invested_at' => '2026-08-16',
            'investment_category_id' => $this->category->id,
            'amount' => 0,
            'payment_method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect(route('admin.investments.create'))
        ->assertSessionHasErrors('amount');
});

it('forbids investment management without permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $investment = Investment::factory()->create([
        'investment_category_id' => $this->category->id,
    ]);

    $this->actingAs($user)
        ->get(route('admin.investments.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.investments.create'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.investments.show', $investment))
        ->assertForbidden();
});

it('shows investments in the sidebar for authorized users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Investments', false);
});

it('generates unique investment numbers for same-day records', function () {
    $first = app(InvestmentRepository::class)->nextInvestmentNumber();
    Investment::factory()->create(['investment_number' => $first]);
    $second = app(InvestmentRepository::class)->nextInvestmentNumber();

    expect($first)->not->toBe($second)
        ->and($first)->toStartWith('INV-'.now()->format('Ymd'))
        ->and($second)->toStartWith('INV-'.now()->format('Ymd'));
});
