<?php

namespace App\Services;

use App\Contracts\Repositories\GymSettingRepositoryInterface;
use App\Enums\PaymentMethod;
use App\Models\GymSetting;
use App\Support\ActivityLogger;
use App\Support\GymLogoStorage;
use Illuminate\Http\UploadedFile;

class GymSettingService extends BaseService
{
    private ?GymSetting $cachedSettings = null;

    public function __construct(
        private GymSettingRepositoryInterface $settings,
        private GymLogoStorage $logoStorage,
        private ActivityLogger $activityLogger,
    ) {}

    public function get(): GymSetting
    {
        return $this->cachedSettings ??= $this->settings->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, ?UploadedFile $logo = null, bool $removeLogo = false): GymSetting
    {
        return $this->transaction(function () use ($data, $logo, $removeLogo): GymSetting {
            $settings = $this->settings->get();

            if ($removeLogo) {
                $this->logoStorage->delete($settings->logo_path);
                $data['logo_path'] = null;
            }

            if ($logo !== null) {
                $this->logoStorage->delete($settings->logo_path);
                $data['logo_path'] = $this->logoStorage->store($logo);
            }

            if (isset($data['enabled_payment_methods'])) {
                $data['enabled_payment_methods'] = array_values(array_unique($data['enabled_payment_methods']));
            }

            $updated = $this->settings->update($settings, $data);

            $this->cachedSettings = $updated;

            $this->activityLogger->log('gym_settings.updated', $updated, 'Gym settings updated');

            return $updated;
        });
    }

    /**
     * @return array<string, string>
     */
    public function paymentMethodOptions(): array
    {
        return $this->get()->paymentMethodOptions();
    }

    /**
     * @return array<int, string>
     */
    public function defaultEnabledPaymentMethods(): array
    {
        return array_column(PaymentMethod::cases(), 'value');
    }
}
