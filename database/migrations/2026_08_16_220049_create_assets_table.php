<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->foreignId('asset_category_id')->constrained()->restrictOnDelete();
            $table->date('purchased_at');
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('current_value', 12, 2)->nullable();
            $table->string('supplier')->nullable();
            $table->string('location')->nullable();
            $table->string('condition')->nullable();
            $table->string('status')->default('active');
            $table->date('warranty_expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('asset_category_id');
            $table->index('status');
            $table->index('purchased_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
