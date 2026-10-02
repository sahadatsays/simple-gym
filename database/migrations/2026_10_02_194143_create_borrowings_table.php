<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            $table->string('borrowing_no')->unique();
            $table->string('lender_name');
            $table->string('lender_phone', 20)->nullable();
            $table->date('borrowing_date');
            $table->decimal('amount', 12, 2);
            $table->string('purpose')->nullable();
            $table->date('due_date')->nullable();
            $table->string('payment_method')->default('cash');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('borrowing_date');
            $table->index('due_date');
            $table->index('payment_method');
            $table->index('status');
            $table->index('lender_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowings');
    }
};
