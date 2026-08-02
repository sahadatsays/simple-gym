<?php

namespace Database\Seeders;

use App\Models\GymSetting;
use Illuminate\Database\Seeder;

class GymSettingSeeder extends Seeder
{
    public function run(): void
    {
        GymSetting::query()->firstOrCreate(
            ['name' => config('gym.defaults.name')],
            config('gym.defaults'),
        );
    }
}
