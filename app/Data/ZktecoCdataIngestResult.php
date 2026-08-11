<?php

namespace App\Data;

class ZktecoCdataIngestResult
{
    public function __construct(
        public bool $handled,
        public int $count,
    ) {}
}
