<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('discount_amount', 12, 2)->default(0)->after('subtotal');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('discount_amount', 12, 2)->default(0)->after('amount');
        });

        DB::table('payments')->where('type', 'membership')->update(['type' => 'membership_fee']);
        DB::table('payments')->where('type', 'product')->update(['type' => 'pos_sale']);
        DB::table('payments')->where('type', 'other')->update(['type' => 'membership_fee']);
    }

    public function down(): void
    {
        DB::table('payments')->where('type', 'membership_fee')->update(['type' => 'membership']);
        DB::table('payments')->where('type', 'pos_sale')->update(['type' => 'product']);
        DB::table('payments')->where('type', 'admission_fee')->update(['type' => 'membership']);

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('discount_amount');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('discount_amount');
        });
    }
};
