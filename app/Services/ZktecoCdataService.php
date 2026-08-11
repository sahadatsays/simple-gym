<?php

namespace App\Services;

use App\Models\ZktecoDevice;
use App\Services\Zkteco\ZktecoCdataIngestService;
use App\Support\ZktecoAdmsResponseBuilder;
use Illuminate\Http\Request;

class ZktecoCdataService extends BaseService
{
    public function __construct(
        private ZktecoDeviceService $devices,
        private ZktecoCdataIngestService $ingest,
    ) {}

    public function handshake(Request $request): string
    {
        $device = $this->devices->registerFromCdata($request);

        if ($device === null) {
            return ZktecoAdmsResponseBuilder::ok();
        }

        return ZktecoAdmsResponseBuilder::cdataConfig(
            $device->serial_number,
            $device->stamps ?? [],
        );
    }

    /**
     * @return array{content: string, status: int}
     */
    public function receiveData(Request $request): array
    {
        $serialNumber = $this->devices->resolveSerialNumber($request);

        if ($serialNumber === null) {
            return [
                'content' => ZktecoAdmsResponseBuilder::ok(),
                'status' => 200,
            ];
        }

        $device = ZktecoDevice::query()
            ->where('serial_number', $serialNumber)
            ->first()
            ?? $this->devices->registerFromCdata($request);

        if ($device !== null) {
            $this->devices->markSeen($device);
        }

        if ($device === null || ! $device->isApproved()) {
            return [
                'content' => ZktecoAdmsResponseBuilder::unavailable('Device pending approval.'),
                'status' => 503,
            ];
        }

        $table = strtoupper((string) ($request->query('table') ?? $request->input('table') ?? ''));
        $outcome = $this->ingest->ingest($table, $request, $serialNumber);

        if (! $outcome->handled) {
            return [
                'content' => ZktecoAdmsResponseBuilder::ok(),
                'status' => 200,
            ];
        }

        $this->advanceStamp($device, $table, $request);

        return [
            'content' => ZktecoAdmsResponseBuilder::ok('OK: '.$outcome->count),
            'status' => 200,
        ];
    }

    private function advanceStamp(ZktecoDevice $device, string $table, Request $request): void
    {
        $stamp = $request->query('Stamp') ?? $request->input('Stamp');

        if (! is_string($stamp) || trim($stamp) === '') {
            return;
        }

        $this->devices->updateStamp($device, $table, trim($stamp));
    }
}
