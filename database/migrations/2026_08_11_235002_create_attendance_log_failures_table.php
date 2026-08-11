<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_log_failures', function (Blueprint $table) {
            $table->id();
            $table->string('sn')->index();
            $table->string('user_id')->index();
            $table->timestamp('timestamp');
            $table->string('punch_status')->nullable();
            $table->string('verify_mode')->nullable();
            $table->string('card_number')->nullable();
            $table->timestamps();

            $table->unique([
                'sn',
                'user_id',
                'timestamp',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_log_failures');
    }
};
