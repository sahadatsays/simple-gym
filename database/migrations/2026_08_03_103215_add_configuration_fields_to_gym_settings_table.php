<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gym_settings', function (Blueprint $table) {
            $table->text('receipt_footer')->nullable()->after('currency');
            $table->unsignedSmallInteger('membership_reminder_days')->default(7)->after('receipt_footer');
            $table->decimal('default_admission_fee', 10, 2)->default(0)->after('membership_reminder_days');
            $table->json('enabled_payment_methods')->nullable()->after('default_admission_fee');
        });
    }

    public function down(): void
    {
        Schema::table('gym_settings', function (Blueprint $table) {
            $table->dropColumn([
                'receipt_footer',
                'membership_reminder_days',
                'default_admission_fee',
                'enabled_payment_methods',
            ]);
        });
    }
};
