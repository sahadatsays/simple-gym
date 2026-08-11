<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gym_settings', function (Blueprint $table) {
            $table->boolean('member_access_restriction_enabled')->default(false)->after('is_open');
            $table->time('member_access_restriction_start_time')->nullable()->after('member_access_restriction_enabled');
            $table->time('member_access_restriction_end_time')->nullable()->after('member_access_restriction_start_time');
            $table->string('member_access_restriction_group')->default('male')->after('member_access_restriction_end_time');
        });
    }

    public function down(): void
    {
        Schema::table('gym_settings', function (Blueprint $table) {
            $table->dropColumn([
                'member_access_restriction_enabled',
                'member_access_restriction_start_time',
                'member_access_restriction_end_time',
                'member_access_restriction_group',
            ]);
        });
    }
};
