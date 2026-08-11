<?php

namespace App\Http\Requests\Admin;

use App\Models\ZktecoDevice;
use Illuminate\Foundation\Http\FormRequest;

class DeleteZktecoDeviceUserRequest extends FormRequest
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
            'user_id' => ['required', 'string', 'max:50'],
        ];
    }
}
