<?php

namespace App\Http\Controllers;

use App\Services\ZktecoAdmsService;
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
