<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_zkteco_access_removals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('serial_number')->index();
            $table->foreignId('zkteco_command_id')->nullable()->constrained('zkteco_commands')->nullOnDelete();
            $table->timestamp('revoked_at');
            $table->timestamps();

            $table->unique(['member_id', 'serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_zkteco_access_removals');
    }
};
