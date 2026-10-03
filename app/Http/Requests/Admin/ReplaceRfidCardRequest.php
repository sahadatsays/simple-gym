<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Models\GymSetting;
use App\Models\Member;
use App\Models\RfidCard;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReplaceRfidCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('replace', RfidCard::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')->whereNull('deleted_at')],
            'card_number' => ['required', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', Rule::enum(PaymentMethod::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $member = Member::query()->find($this->integer('member_id'));
            $cardFee = (float) (GymSetting::query()->value('rfid_card_fee') ?? 0);

            if ($member !== null && ! $member->hasPaidRfidCardFee() && Money::greaterThan($cardFee, 0) && ! $this->filled('payment_method')) {
                $validator->errors()->add('payment_method', 'Choose a payment method for the card fee.');
            }
        });
    }

    public function member(): Member
    {
        return Member::query()->findOrFail($this->validated('member_id'));
    }
}
