<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = config('seeding.admin');
        $password = $admin['password'];

        if ($password === null || $password === '') {
            if (app()->environment('production')) {
                throw new RuntimeException('ADMIN_PASSWORD must be set before seeding production data.');
            }

            $password = 'password';
        }

        $user = User::query()->firstOrCreate(
            ['email' => $admin['email']],
            [
                'name' => $admin['name'],
                'username' => $admin['username'],
                'phone' => $admin['phone'],
                'password' => Hash::make($password),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $user->forceFill([
            'name' => $admin['name'],
            'username' => $admin['username'],
            'phone' => $admin['phone'],
            'is_active' => true,
        ])->save();

        $user->assignRole('super-admin');
    }
}
