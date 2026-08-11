<?php

namespace App\Services\Zkteco;

use App\Data\ZktecoCdataIngestResult;
use App\Models\ZktecoAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ZktecoCdataIngestService
{
    public function ingest(string $table, Request $request, string $serialNumber): ZktecoCdataIngestResult
    {
        return match (strtoupper($table)) {
            'ATTLOG' => $this->ingestAttlog($request, $serialNumber),
            default => new ZktecoCdataIngestResult(handled: false, count: 0),
        };
    }

    private function ingestAttlog(Request $request, string $serialNumber): ZktecoCdataIngestResult
    {
        $body = trim($request->getContent());

        if ($body === '') {
            return new ZktecoCdataIngestResult(handled: true, count: 0);
        }

        $count = 0;

        foreach (preg_split('/\r\n|\n|\r/', $body) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $fields = explode("\t", $line);

            if (count($fields) < 2) {
                Log::warning('Skipping malformed ZKTeco ATTLOG line', [
                    'serial_number' => $serialNumber,
                    'line' => $line,
                ]);

                continue;
            }

            try {
                $recordedAt = Carbon::createFromFormat('Y-m-d H:i:s', trim($fields[1]));
            } catch (\Throwable) {
                Log::warning('Skipping ZKTeco ATTLOG line with invalid timestamp', [
                    'serial_number' => $serialNumber,
                    'line' => $line,
                ]);

                continue;
            }

            if (! $recordedAt instanceof Carbon) {
                continue;
            }

            $userId = trim($fields[0]);

            ZktecoAttendance::query()->updateOrCreate(
                [
                    'connection' => $serialNumber,
                    'user_id' => $userId,
                    'recorded_at' => $recordedAt,
                ],
                [
                    'uid' => is_numeric($userId) ? (int) $userId : null,
                    'verify_mode' => trim($fields[3] ?? '') !== '' ? trim($fields[3]) : 'unknown',
                    'punch_state' => trim($fields[2] ?? '') !== '' ? trim($fields[2]) : 'unknown',
                ],
            );

            $count++;
        }

        return new ZktecoCdataIngestResult(handled: true, count: $count);
    }
}
