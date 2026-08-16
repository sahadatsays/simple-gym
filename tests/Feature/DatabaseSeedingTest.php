<?php

use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Support\SeedingMode;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DevelopmentSeeder;
use Database\Seeders\ProductionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds only required production data', function () {
    config([
        'seeding.admin.password' => 'password',
    ]);

    $this->seed(ProductionSeeder::class);

    expect(User::query()->where('email', config('seeding.admin.email'))->exists())->toBeTrue()
        ->and(MembershipPlan::query()->count())->toBe(0)
        ->and(Member::query()->count())->toBe(0);
});

it('seeds demo data through the development seeder', function () {
    config([
        'seeding.admin.password' => 'password',
    ]);

    $this->seed(ProductionSeeder::class);
    $this->seed(DevelopmentSeeder::class);

    expect(MembershipPlan::query()->count())->toBeGreaterThan(0)
        ->and(Member::query()->count())->toBeGreaterThan(0)
        ->and(User::query()->where('email', 'manager@simplegym.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'staff@simplegym.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'trainer@simplegym.test')->exists())->toBeTrue();
});

it('requires an admin password in production', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'seeding.admin.password' => null,
    ]);

    (new RolePermissionSeeder())->run();

    expect(fn () => (new AdminUserSeeder())->run())
        ->toThrow(RuntimeException::class, 'ADMIN_PASSWORD must be set before seeding production data.');
});

it('seeds demo data in local through the database seeder', function () {
    app()->detectEnvironment(fn () => 'local');

    config([
        'seeding.admin.password' => 'password',
        'seeding.seed_demo_data' => false,
    ]);

    $this->seed(DatabaseSeeder::class);

    expect(Member::query()->count())->toBeGreaterThan(0);
});

it('does not seed demo data in production mode', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'seeding.seed_demo_data' => false,
    ]);

    expect(SeedingMode::shouldSeedDemoData())->toBeFalse();
});

it('can force demo seeding with an environment flag', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'seeding.seed_demo_data' => true,
    ]);

    expect(SeedingMode::shouldSeedDemoData())->toBeTrue();
});
