<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('duration_days');
            $table->decimal('admission_fee', 10, 2)->default(0);
            $table->decimal('membership_fee', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->json('features')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_plans');
    }
};
