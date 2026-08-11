<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sn')->index();
            $table->string('pim')->index();
            $table->timestamp('timestamp');
            $table->string('punch_status');
            $table->string('verify_mode');
            $table->timestamps();

            $table->unique([
                'sn',
                'pim',
                'timestamp',
            ]);
        });

        if (Schema::hasTable('zkteco_attendance')) {
            DB::table('zkteco_attendance')
                ->orderBy('id')
                ->chunk(500, function ($records): void {
                    $rows = [];

                    foreach ($records as $record) {
                        $rows[] = [
                            'sn' => $record->connection,
                            'pim' => $record->pim,
                            'timestamp' => $record->recorded_at,
                            'punch_status' => $record->punch_state,
                            'verify_mode' => $record->verify_mode,
                            'created_at' => $record->created_at,
                            'updated_at' => $record->updated_at,
                        ];
                    }

                    if ($rows !== []) {
                        DB::table('attendance_logs')->insertOrIgnore($rows);
                    }
                });

            Schema::dropIfExists('zkteco_attendance');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
