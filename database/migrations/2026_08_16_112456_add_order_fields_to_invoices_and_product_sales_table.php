<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('due_at')->nullable()->after('paid_at');
        });

        Schema::table('product_sales', function (Blueprint $table) {
            $table->foreignId('payment_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('due_at');
        });

        Schema::table('product_sales', function (Blueprint $table) {
            $table->foreignId('payment_id')->nullable(false)->change();
        });
    }
};
