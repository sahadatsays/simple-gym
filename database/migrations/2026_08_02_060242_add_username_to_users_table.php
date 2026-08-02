<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        DB::table('users')
            ->whereNull('username')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $user): void {
                $base = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', strstr($user->email, '@', true) ?: 'user'));
                $username = trim($base, '_') ?: 'user';
                $suffix = 1;

                while (DB::table('users')->where('username', $username)->exists()) {
                    $username = ($base ?: 'user').'_'.$suffix;
                    $suffix++;
                }

                DB::table('users')->where('id', $user->id)->update(['username' => $username]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
