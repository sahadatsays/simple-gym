<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\DB;

abstract class BaseService
{
    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    protected function transaction(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
