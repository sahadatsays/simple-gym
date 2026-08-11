<?php

namespace App\Services\Zkteco;

use App\Data\ZktecoCdataIngestResult;
use Illuminate\Http\Request;

class ZktecoCdataIngestService
{
    public function ingest(string $table, Request $request, string $serialNumber): ZktecoCdataIngestResult
    {
        return new ZktecoCdataIngestResult(handled: false, count: 0);
    }
}
