<?php

namespace App\Services;

use App\Contracts\Repositories\GymSettingRepositoryInterface;
use App\Models\GymSetting;
use App\Support\ActivityLogger;

class GymSettingService extends BaseService
{
    public function __construct(
        private GymSettingRepositoryInterface $settings,
        private ActivityLogger $activityLogger,
    ) {}

    public function get(): GymSetting
    {
        return $this->settings->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): GymSetting
    {
        return $this->transaction(function () use ($data): GymSetting {
            $settings = $this->settings->get();
            $updated = $this->settings->update($settings, $data);

            $this->activityLogger->log('gym_settings.updated', $updated, 'Gym settings updated');

            return $updated;
        });
    }
}
