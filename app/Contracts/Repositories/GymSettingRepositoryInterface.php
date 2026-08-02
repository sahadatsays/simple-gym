<?php

namespace App\Contracts\Repositories;

use App\Models\GymSetting;

interface GymSettingRepositoryInterface extends RepositoryInterface
{
    public function get(): GymSetting;
}
