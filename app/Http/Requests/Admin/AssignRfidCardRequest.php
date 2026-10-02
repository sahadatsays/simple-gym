<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Models\Member;
use App\Models\RfidCard;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignRfidCardRequest extends FormRequest
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
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')->whereNull('deleted_at')],
            'payment_method' => ['nullable', 'string', Rule::enum(PaymentMethod::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $card = $this->route('rfid_card');

            if (! $card instanceof RfidCard) {
                return;
            }

            $total = Money::round((float) $card->card_fee + (float) $card->deposit_amount);

            if (Money::greaterThan($total, 0) && ! $this->filled('payment_method')) {
                $validator->errors()->add('payment_method', 'Choose a payment method for the card fee and deposit.');
            }
        });
    }

    public function member(): Member
    {
        return Member::query()->findOrFail($this->validated('member_id'));
    }
}
