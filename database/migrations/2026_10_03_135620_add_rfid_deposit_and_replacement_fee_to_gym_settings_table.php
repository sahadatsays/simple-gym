<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gym_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('gym_settings', 'rfid_card_deposit')) {
                $table->decimal('rfid_card_deposit', 12, 2)->default(0)->after('rfid_card_fee');
            }

            if (! Schema::hasColumn('gym_settings', 'rfid_replacement_card_fee')) {
                $table->decimal('rfid_replacement_card_fee', 12, 2)->default(0)->after('rfid_card_deposit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gym_settings', function (Blueprint $table) {
            $table->dropColumn([
                'rfid_card_deposit',
                'rfid_replacement_card_fee',
            ]);
        });
    }
};
