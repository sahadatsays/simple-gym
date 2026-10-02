<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfid_cards', function (Blueprint $table) {
            $table->decimal('card_fee', 12, 2)->default(0)->after('card_number');
            $table->decimal('deposit_amount', 12, 2)->default(0)->after('card_fee');
            $table->foreignId('created_by')->nullable()->after('assigned_at')->constrained('users')->nullOnDelete();
        });

        DB::table('rfid_cards')->where('status', 'unassigned')->update(['status' => 'available']);
        DB::table('rfid_cards')->where('status', 'active')->update(['status' => 'assigned']);
        DB::table('rfid_cards')->where('status', 'disabled')->update(['status' => 'blocked']);
    }

    public function down(): void
    {
        DB::table('rfid_cards')->where('status', 'available')->update(['status' => 'unassigned']);
        DB::table('rfid_cards')->where('status', 'assigned')->update(['status' => 'active']);
        DB::table('rfid_cards')->where('status', 'blocked')->update(['status' => 'disabled']);

        Schema::table('rfid_cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['card_fee', 'deposit_amount']);
        });
    }
};
