<?php

namespace App\Http\Requests\Admin;

use App\Enums\LockerStatus;
use App\Models\Locker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateLockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $locker = $this->route('locker');

        return $locker instanceof Locker
            && ($this->user()?->can('update', $locker) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $locker = $this->route('locker');

        return [
            'locker_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('lockers', 'locker_number')->ignore($locker instanceof Locker ? $locker->id : null),
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'monthly_fee' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'status' => ['required', 'string', Rule::enum(LockerStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $locker = $this->route('locker');

            if (! $locker instanceof Locker) {
                return;
            }

            if ($this->input('status') === LockerStatus::Reserved->value && ! $locker->canBeReserved()) {
                $validator->errors()->add('status', 'Disabled or maintenance lockers cannot be reserved.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'locker_number' => 'locker number',
            'category' => 'type / category',
            'monthly_fee' => 'monthly fee',
        ];
    }
}
