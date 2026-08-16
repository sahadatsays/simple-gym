<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->date('maintained_at');
            $table->string('type');
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('service_provider')->nullable();
            $table->text('description')->nullable();
            $table->date('next_maintenance_at')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['asset_id', 'maintained_at']);
            $table->index('next_maintenance_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenances');
    }
};
