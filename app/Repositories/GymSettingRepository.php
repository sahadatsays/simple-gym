<?php

namespace App\Repositories;

use App\Contracts\Repositories\GymSettingRepositoryInterface;
use App\Models\GymSetting;

class GymSettingRepository extends BaseRepository implements GymSettingRepositoryInterface
{
    public function __construct(GymSetting $model)
    {
        parent::__construct($model);
    }

    public function get(): GymSetting
    {
        return $this->newQuery()->firstOrFail();
    }
}
