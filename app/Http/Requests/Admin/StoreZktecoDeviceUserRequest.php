<?php

namespace App\Http\Requests\Admin;

use App\Models\ZktecoDevice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'pim' => [
                'required',
                'integer',
                Rule::exists('rfid_cards', 'id')->whereNotNull('member_id'),
            ],
            'privilege' => ['nullable', 'integer', 'min:0', 'max:14'],
        ];
    }
}
