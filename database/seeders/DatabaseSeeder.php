<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            GymSettingSeeder::class,
            MembershipPlanSeeder::class,
            DashboardSeeder::class,
        ]);

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@simplegym.test'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'phone' => null,
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $admin->assignRole('super-admin');
    }
}
