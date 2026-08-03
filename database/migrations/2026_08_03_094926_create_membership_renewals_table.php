<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('previous_expires_at')->nullable();
            $table->date('new_expires_at');
            $table->timestamp('renewed_at');
            $table->timestamps();

            $table->index(['member_id', 'renewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_renewals');
    }
};
