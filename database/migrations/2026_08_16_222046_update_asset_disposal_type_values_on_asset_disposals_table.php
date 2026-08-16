<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('asset_disposals')
            ->where('disposal_type', 'sale')
            ->update(['disposal_type' => 'sold']);

        DB::table('asset_disposals')
            ->whereIn('disposal_type', ['scrap', 'donation', 'write_off', 'other'])
            ->update(['disposal_type' => 'disposed']);
    }

    public function down(): void
    {
        DB::table('asset_disposals')
            ->where('disposal_type', 'sold')
            ->update(['disposal_type' => 'sale']);

        DB::table('asset_disposals')
            ->where('disposal_type', 'disposed')
            ->update(['disposal_type' => 'write_off']);
    }
};
