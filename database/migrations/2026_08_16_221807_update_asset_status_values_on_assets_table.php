<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('assets')
            ->where('status', 'inactive')
            ->update(['status' => 'damaged']);
    }

    public function down(): void
    {
        DB::table('assets')
            ->where('status', 'damaged')
            ->update(['status' => 'inactive']);
    }
};
