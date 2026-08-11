<?php

namespace App\Http\Controllers;

use App\Models\ZktecoDevice;
use App\Services\ZktecoCdataService;
use App\Services\ZktecoDeviceService;
use App\Support\ZktecoAdmsResponseBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class ZktecoController extends Controller
{
    public function __construct(
        private ZktecoDeviceService $devices,
        private ZktecoCdataService $cdata,
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
            $serialNumber = $this->devices->resolveSerialNumber($request);

            if ($serialNumber === null) {
                Log::warning('ZKTeco push request missing serial number', [
                    'method' => $request->method(),
                    'query' => $request->query(),
                ]);

                return $this->rawAdmsResponse(ZktecoAdmsResponseBuilder::push());
            }

            $device = ZktecoDevice::query()->firstOrCreate(
                ['serial_number' => $serialNumber],
                ['status' => 'pending'],
            );

            $this->devices->touchDevice($device);

            if ($request->isMethod('post')) {
                if ($request->hasAny(['ID', 'id', 'Return', 'return'])) {
                    $this->devices->acknowledgeCommandFromRequest($request);
                } elseif (! $request->filled('table')) {
                    $this->devices->logUnexpectedPushRequest($request);
                }
            }

            $content = ZktecoAdmsResponseBuilder::push();

            if ($command = $this->devices->pullNextPendingCommand($device)) {
                $content .= ZktecoAdmsResponseBuilder::command($command->id, $command->command);
            }

            return $this->rawAdmsResponse($content);
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
            return $this->rawAdmsResponse($this->devices->getRequestResponse($request));
        } catch (Throwable $exception) {
            Log::error('ZKTeco getrequest failed', [
                'message' => $exception->getMessage(),
            ]);

            return $this->rawAdmsResponse(ZktecoAdmsResponseBuilder::ok());
        }
    }

    public function cdata(Request $request): Response
    {
        try {
            if ($request->isMethod('get')) {
                return $this->rawAdmsResponse($this->cdata->handshake($request));
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

    private function rawAdmsResponse(string $content): Response
    {
        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
