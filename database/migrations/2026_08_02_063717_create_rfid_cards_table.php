<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid_cards', function (Blueprint $table) {
            $table->id();
            $table->string('card_number')->unique();
            $table->string('status')->default('unassigned');
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'member_id']);
            $table->index('assigned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_cards');
    }
};
