<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Models\GymSetting;
use App\Models\RfidCard;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CollectRfidCardFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $card = $this->route('rfid_card');

        return $card instanceof RfidCard
            && ($this->user()?->can('assign', $card) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['nullable', 'string', Rule::enum(PaymentMethod::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $cardFee = (float) (GymSetting::query()->value('rfid_card_fee') ?? 0);

            if (Money::greaterThan($cardFee, 0) && ! $this->filled('payment_method')) {
                $validator->errors()->add('payment_method', 'Choose a payment method for the card fee.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'payment_method' => 'payment method',
        ];
    }
}
