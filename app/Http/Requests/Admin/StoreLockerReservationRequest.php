<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Models\Locker;
use App\Models\LockerReservation;
use App\Models\Member;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLockerReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LockerReservation::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')->whereNull('deleted_at')],
            'locker_id' => ['required', 'integer', Rule::exists('lockers', 'id')->whereNull('deleted_at')],
            'start_month' => ['required', 'date_format:Y-m'],
            'payment_method' => ['nullable', 'string', Rule::enum(PaymentMethod::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $locker = Locker::query()->find($this->integer('locker_id'));

            if ($locker === null) {
                return;
            }

            if (! $locker->canBeReserved()) {
                $validator->errors()->add('locker_id', 'Disabled or maintenance lockers cannot be reserved.');
            }

            if (Money::greaterThan((float) $locker->monthly_fee, 0) && ! $this->filled('payment_method')) {
                $validator->errors()->add('payment_method', 'Choose a payment method for the locker fee.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'member_id' => 'member',
            'locker_id' => 'locker',
            'start_month' => 'start month',
            'payment_method' => 'payment method',
        ];
    }

    public function member(): Member
    {
        return Member::query()->findOrFail($this->validated('member_id'));
    }

    public function locker(): Locker
    {
        return Locker::query()->findOrFail($this->validated('locker_id'));
    }
}
