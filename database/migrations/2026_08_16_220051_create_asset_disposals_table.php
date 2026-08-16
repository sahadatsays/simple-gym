<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('disposed_at');
            $table->string('disposal_type');
            $table->decimal('sale_amount', 12, 2)->nullable();
            $table->string('buyer')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('disposed_at');
            $table->index('disposal_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
    }
};
