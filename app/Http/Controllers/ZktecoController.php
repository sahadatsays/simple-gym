<?php

namespace App\Http\Controllers;

use App\Models\ZktecoDevice;
use App\Services\ZktecoAdmsService;
use App\Services\ZktecoCdataService;
use App\Services\ZktecoDeviceService;
use App\Support\ZktecoAdmsResponseBuilder;
use App\Support\ZktecoAttendanceVerifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ZktecoController extends Controller
{
    public function __construct(
        private ZktecoDeviceService $devices,
        private ZktecoCdataService $cdata,
        private ZktecoAdmsService $adms,
    ) {}

    public function registry(Request $request): Response
    {
        try {
            $this->devices->registerDevice($request);
        } catch (Throwable $exception) {
            Log::error('ZKTeco registry failed', [
                'message' => $exception->getMessage(),
            ]);
        }

        return $this->rawAdmsResponse(ZktecoAdmsResponseBuilder::registry());
    }

    public function push(Request $request): Response
    {
        try {
            return $this->rawAdmsResponse($this->adms->handlePush($request));
        } catch (Throwable $exception) {
            Log::error('ZKTeco push failed', [
                'message' => $exception->getMessage(),
            ]);

            return $this->rawAdmsResponse(ZktecoAdmsResponseBuilder::push());
        }
    }

    public function getRequest(Request $request): Response
    {
        try {
            return $this->rawAdmsResponse($this->adms->handleGetRequest($request));
        } catch (Throwable $exception) {
            Log::error('ZKTeco getrequest failed', [
                'message' => $exception->getMessage(),
            ]);

            return $this->rawAdmsResponse(ZktecoAdmsResponseBuilder::ok());
        }
    }

    public function deviceCmd(Request $request): Response
    {
        try {
            $this->adms->handleDeviceCmd($request);
        } catch (Throwable $exception) {
            Log::error('ZKTeco devicecmd failed', [
                'message' => $exception->getMessage(),
                'content_type' => $request->header('Content-Type'),
                'raw_content' => $request->getContent(),
            ]);
        }

        return response("OK\n", 200)->header('Content-Type', 'text/plain');
    }

    public function cdata(Request $request): Response
    {
        try {
            if ($request->isMethod('get')) {
                return $this->rawAdmsResponse($this->cdata->handshake($request));
            }

            $body = $request->getContent();
            $table = strtoupper((string) ($request->query('table') ?? $request->input('table') ?? ''));
            $isExplicitAttlog = $table === 'ATTLOG';
            $isLooseAttlog = $this->looksLikeAttlogPayload($body);

            if ($isExplicitAttlog || $isLooseAttlog) {
                if ($isLooseAttlog) {
                    Log::info('[ATT_DUMP]', [
                        'serial_number' => $this->devices->resolveSerialNumber($request),
                        'table' => $table !== '' ? $table : null,
                        'query' => $request->query(),
                        'content' => $body,
                    ]);
                }

                return $this->handleAttlogCdata($request, $body);
            }

            $result = $this->cdata->receiveData($request);

            return response($result['content'], $result['status'], [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        } catch (Throwable $exception) {
            Log::error('ZKTeco cdata failed', [
                'message' => $exception->getMessage(),
            ]);

            return $this->rawAdmsResponse(ZktecoAdmsResponseBuilder::ok());
        }
    }

    private function handleAttlogCdata(Request $request, string $body): Response
    {
        $serialNumber = $this->devices->resolveSerialNumber($request);

        if ($serialNumber === null) {
            return $this->attlogOkResponse();
        }

        $device = ZktecoDevice::query()
            ->where('serial_number', $serialNumber)
            ->first()
            ?? $this->devices->registerFromCdata($request);

        if ($device !== null) {
            $this->devices->markSeen($device);
        }

        if ($device === null || ! $device->isApproved()) {
            return response(
                ZktecoAdmsResponseBuilder::unavailable('Device pending approval.'),
                503,
                ['Content-Type' => 'text/plain; charset=utf-8'],
            );
        }

        $this->ingestAttlogPayload($body, $serialNumber);

        $stamp = $request->query('Stamp') ?? $request->input('Stamp');

        if (is_string($stamp) && trim($stamp) !== '') {
            $this->devices->updateStamp($device, 'ATTLOG', trim($stamp));
        }

        return $this->attlogOkResponse();
    }

    private function looksLikeAttlogPayload(string $body): bool
    {
        if ($body === '') {
            return false;
        }

        if (preg_match('/\d{4}-\d{2}-\d{2}/', $body) === 1) {
            return true;
        }

        return preg_match('/^\d+\t/m', $body) === 1
            || preg_match('/(?:^|\t)(?:time|pin)=/m', $body) === 1;
    }

    /**
     * @return array{pim: string, timestamp: Carbon, punch_status: string, verify_mode: string, card_number: string|null}|null
     */
    private function parseAttlogLine(string $line): ?array
    {
        if (str_contains($line, '=')) {
            return $this->parseKeyValueAttlogLine($line);
        }

        return $this->parsePositionalAttlogLine($line);
    }

    /**
     * @return array{pim: string, timestamp: Carbon, punch_status: string, verify_mode: string, card_number: string|null}|null
     */
    private function parseKeyValueAttlogLine(string $line): ?array
    {
        $fields = [];

        foreach (explode("\t", $line) as $segment) {
            if (! str_contains($segment, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $segment, 2);
            $fields[strtolower(trim($key))] = trim($value);
        }

        $pim = $fields['pin']
            ?? $fields['userid']
            ?? $fields['user_id']
            ?? null;

        $timestampValue = $fields['time']
            ?? $fields['timestamp']
            ?? $fields['checktime']
            ?? null;

        if ($pim === null || $pim === '' || $timestampValue === null || $timestampValue === '') {
            return null;
        }

        $timestamp = $this->parseAttlogTimestamp($timestampValue);

        if ($timestamp === null) {
            return null;
        }

        $punchStatus = $fields['inoutstatus']
            ?? $fields['event']
            ?? $fields['punch']
            ?? $fields['status']
            ?? '';

        $verifyMode = $fields['verifytype']
            ?? $fields['verifymode']
            ?? $fields['verify']
            ?? '';

        return [
            'pim' => $pim,
            'timestamp' => $timestamp,
            'punch_status' => $punchStatus,
            'verify_mode' => $verifyMode,
            'card_number' => $fields['cardno']
                ?? $fields['card']
                ?? null,
        ];
    }

    /**
     * @return array{pim: string, timestamp: Carbon, punch_status: string, verify_mode: string, card_number: string|null}|null
     */
    private function parsePositionalAttlogLine(string $line): ?array
    {
        $fields = explode("\t", $line);

        if (count($fields) < 2) {
            return null;
        }

        $timestamp = $this->parseAttlogTimestamp(trim($fields[1]));

        if ($timestamp === null) {
            return null;
        }

        return [
            'pim' => trim($fields[0]),
            'timestamp' => $timestamp,
            'punch_status' => trim($fields[2] ?? ''),
            'verify_mode' => trim($fields[3] ?? ''),
            'card_number' => null,
        ];
    }

    private function parseAttlogTimestamp(string $value): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i:s'] as $format) {
            try {
                $timestamp = Carbon::createFromFormat($format, $value);
            } catch (Throwable) {
                continue;
            }

            if ($timestamp instanceof Carbon) {
                return $timestamp;
            }
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function ingestAttlogPayload(string $body, string $serialNumber): void
    {
        if ($body === '') {
            return;
        }

        $rows = [];
        $failedRows = [];
        $now = now();

        foreach (preg_split('/\r\n|\n|\r/', $body) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parsed = $this->parseAttlogLine($line);

            if ($parsed === null) {
                Log::warning('Skipping malformed ZKTeco ATTLOG line', [
                    'serial_number' => $serialNumber,
                    'line' => $line,
                ]);

                continue;
            }

            $record = [
                'sn' => $serialNumber,
                'pim' => $parsed['pim'],
                'timestamp' => $parsed['timestamp']->toDateTimeString(),
                'punch_status' => $parsed['punch_status'],
                'verify_mode' => $parsed['verify_mode'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (ZktecoAttendanceVerifier::isVerified($parsed)) {
                $rows[] = $record;

                continue;
            }

            Log::info('[ATT_REJECT]', [
                'serial_number' => $serialNumber,
                'line' => $line,
                'parsed' => $parsed,
            ]);

            $failedRows[] = array_merge($record, [
                'card_number' => $parsed['card_number'],
            ]);
        }

        if ($rows !== []) {
            DB::table('attendance_logs')->upsert(
                $rows,
                ['sn', 'pim', 'timestamp'],
                ['punch_status', 'verify_mode', 'updated_at'],
            );
        }

        if ($failedRows !== []) {
            DB::table('attendance_log_failures')->upsert(
                $failedRows,
                ['sn', 'pim', 'timestamp'],
                ['punch_status', 'verify_mode', 'card_number', 'updated_at'],
            );
        }
    }

    private function attlogOkResponse(): Response
    {
        return response("OK\n", 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    private function rawAdmsResponse(string $content): Response
    {
        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
