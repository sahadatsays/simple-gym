<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('rfid_card')->nullable()->unique()->after('member_code');
            $table->string('photo_path')->nullable()->after('rfid_card');
            $table->string('gender')->nullable()->after('phone');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->text('address')->nullable()->after('date_of_birth');
            $table->string('emergency_contact_name')->nullable()->after('address');
            $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');

            $table->unique('phone');
            $table->index('gender');
            $table->index('rfid_card');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropIndex(['gender']);
            $table->dropIndex(['rfid_card']);
            $table->dropColumn([
                'rfid_card',
                'photo_path',
                'gender',
                'date_of_birth',
                'address',
                'emergency_contact_name',
                'emergency_contact_phone',
            ]);
        });
    }
};
