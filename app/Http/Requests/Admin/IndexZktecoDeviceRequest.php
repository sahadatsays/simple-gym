<?php

namespace App\Http\Requests\Admin;

use App\Enums\ZktecoDeviceStatus;
use App\Models\ZktecoDevice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexZktecoDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ZktecoDevice::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::enum(ZktecoDeviceStatus::class)],
        ];
    }
}
