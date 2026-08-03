<?php

use App\Models\GymSetting;
use App\Models\User;
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

    $this->admin = User::factory()->create([
        'username' => 'adminuser',
        'is_active' => true,
    ]);
    $this->admin->assignRole('super-admin');

    $this->settings = GymSetting::query()->firstOrFail();
});

it('shows gym settings for authorized users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.settings.edit'))
        ->assertSuccessful()
        ->assertSee('Gym Settings')
        ->assertSee('Receipt footer')
        ->assertSee('Payment methods')
        ->assertSee('Default admission fee');
});

it('updates gym settings for authorized users', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), [
            'name' => 'FitZone Gym',
            'phone' => '01700000000',
            'address' => '123 Main Street',
            'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka',
            'receipt_footer' => 'Thanks for training with us!',
            'membership_reminder_days' => 14,
            'default_admission_fee' => 750,
            'enabled_payment_methods' => ['cash', 'mobile_banking'],
            'is_open' => true,
        ])
        ->assertRedirect(route('admin.settings.edit'));

    $this->settings->refresh();

    expect($this->settings->name)->toBe('FitZone Gym')
        ->and($this->settings->phone)->toBe('01700000000')
        ->and($this->settings->receipt_footer)->toBe('Thanks for training with us!')
        ->and($this->settings->membership_reminder_days)->toBe(14)
        ->and((float) $this->settings->default_admission_fee)->toBe(750.0)
        ->and($this->settings->enabled_payment_methods)->toBe(['cash', 'mobile_banking']);
});

it('uploads a gym logo', function () {
    $logo = UploadedFile::fake()->image('logo.png', 300, 300);

    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), [
            'name' => $this->settings->name,
            'currency' => $this->settings->currency,
            'timezone' => $this->settings->timezone,
            'membership_reminder_days' => 7,
            'default_admission_fee' => 500,
            'enabled_payment_methods' => ['cash', 'card'],
            'logo' => $logo,
            'is_open' => true,
        ])
        ->assertRedirect(route('admin.settings.edit'));

    $this->settings->refresh();

    expect($this->settings->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($this->settings->logo_path);
});

it('requires at least one enabled payment method', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), [
            'name' => $this->settings->name,
            'currency' => $this->settings->currency,
            'timezone' => $this->settings->timezone,
            'membership_reminder_days' => 7,
            'default_admission_fee' => 500,
            'enabled_payment_methods' => [],
            'is_open' => true,
        ])
        ->assertSessionHasErrors('enabled_payment_methods');
});

it('allows view-only access without update permission', function () {
    $viewer = User::factory()->create(['username' => 'vieweruser', 'is_active' => true]);
    $viewer->givePermissionTo('settings.view');

    $this->actingAs($viewer)
        ->get(route('admin.settings.edit'))
        ->assertSuccessful()
        ->assertSee('You can view these settings')
        ->assertDontSee('Save Settings');
});

it('denies settings updates without update permission', function () {
    $viewer = User::factory()->create(['username' => 'vieweruser2', 'is_active' => true]);
    $viewer->givePermissionTo('settings.view');

    $this->actingAs($viewer)
        ->put(route('admin.settings.update'), [
            'name' => 'Blocked Update',
            'currency' => 'BDT',
            'timezone' => 'UTC',
            'membership_reminder_days' => 7,
            'default_admission_fee' => 500,
            'enabled_payment_methods' => ['cash'],
            'is_open' => true,
        ])
        ->assertForbidden();
});

it('denies settings access without view permission', function () {
    $user = User::factory()->create(['username' => 'basicuser', 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('admin.settings.edit'))
        ->assertForbidden();
});

it('exposes enabled payment methods to payment forms', function () {
    $this->settings->update([
        'enabled_payment_methods' => ['cash'],
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.payments.create'))
        ->assertSuccessful()
        ->assertSee('Cash')
        ->assertDontSee('Mobile Banking');
});
