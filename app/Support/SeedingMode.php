<?php

namespace App\Support;

class SeedingMode
{
    public static function shouldSeedDemoData(): bool
    {
        if (filter_var(config('seeding.seed_demo_data'), FILTER_VALIDATE_BOOL)) {
            return true;
        }

        return app()->environment(['local', 'development', 'testing']);
    }
}
