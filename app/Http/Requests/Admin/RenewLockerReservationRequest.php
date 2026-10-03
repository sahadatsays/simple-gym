<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Models\LockerReservation;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RenewLockerReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reservation = $this->route('locker_reservation');

        return $reservation instanceof LockerReservation
            && ($this->user()?->can('renew', $reservation) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'start_month' => ['required', 'date_format:Y-m'],
            'payment_method' => ['nullable', 'string', Rule::enum(PaymentMethod::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $reservation = $this->route('locker_reservation');

            if (! $reservation instanceof LockerReservation) {
                return;
            }

            $reservation->loadMissing('locker');
            $fee = (float) ($reservation->locker->monthly_fee ?? 0);

            if (Money::greaterThan($fee, 0) && ! $this->filled('payment_method')) {
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
            'start_month' => 'start month',
            'payment_method' => 'payment method',
        ];
    }
}
