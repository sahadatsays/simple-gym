<?php

namespace App\Repositories;

use App\Contracts\Repositories\ActivityLogRepositoryInterface;
use App\Models\ActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ActivityLogRepository extends BaseRepository implements ActivityLogRepositoryInterface
{
    public function __construct(ActivityLog $model)
    {
        parent::__construct($model);
    }

    public function paginateLatest(int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with('user')
            ->latest()
            ->paginate($perPage);
    }
}
