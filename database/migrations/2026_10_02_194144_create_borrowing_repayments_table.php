<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowing_repayments', function (Blueprint $table) {
            $table->id();
            $table->string('repayment_no')->unique();
            $table->foreignId('borrowing_id')->constrained()->restrictOnDelete();
            $table->date('repayment_date');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('cash');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('repayment_date');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowing_repayments');
    }
};
