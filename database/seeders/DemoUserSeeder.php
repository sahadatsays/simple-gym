<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('seeding.demo_users', []) as $demoUser) {
            $user = User::query()->firstOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'username' => $demoUser['username'],
                    'phone' => null,
                    'password' => Hash::make($demoUser['password']),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            $user->forceFill([
                'name' => $demoUser['name'],
                'username' => $demoUser['username'],
                'is_active' => true,
            ])->save();

            $user->syncRoles([$demoUser['role']]);
        }
    }
}
