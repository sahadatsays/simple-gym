<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zkteco_attendance', function (Blueprint $table) {
            $table->id();
            $table->string('connection')->index();
            $table->unsignedInteger('uid')->nullable();
            $table->string('pim')->index();
            $table->timestamp('recorded_at');
            $table->string('verify_mode');
            $table->string('punch_state');
            $table->timestamps();

            $table->unique([
                'connection',
                'pim',
                'recorded_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zkteco_attendance');
    }
};
