<?php

namespace App\Http\Requests\Admin;

use App\Models\ZktecoDevice;
use Illuminate\Foundation\Http\FormRequest;

class StoreZktecoDeviceUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $device = $this->route('device');

        return $device instanceof ZktecoDevice
            && $this->user()?->can('manage', $device) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'uid' => ['nullable', 'integer', 'min:0'],
            'user_id' => ['required', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:255'],
            'privilege' => ['nullable', 'integer', 'min:0', 'max:14'],
            'card_number' => ['nullable', 'string', 'max:50'],
        ];
    }
}
