<?php

namespace App\Http\Requests\Admin;

use App\Enums\MemberAccessRestrictionGroup;
use App\Enums\PaymentMethod;
use App\Support\CurrencyRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateGymSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'currency' => ['required', 'string', Rule::in(CurrencyRegistry::codes())],
            'receipt_footer' => ['nullable', 'string', 'max:1000'],
            'membership_reminder_days' => ['required', 'integer', 'min:1', 'max:365'],
            'default_admission_fee' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'rfid_card_fee' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'rfid_card_deposit' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'rfid_replacement_card_fee' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'enabled_payment_methods' => ['required', 'array', 'min:1'],
            'enabled_payment_methods.*' => ['required', 'string', Rule::enum(PaymentMethod::class)],
            'email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['required', 'string', 'max:64'],
            'opening_time' => ['nullable', 'date_format:H:i'],
            'closing_time' => ['nullable', 'date_format:H:i'],
            'is_open' => ['nullable', 'boolean'],
            'member_access_restriction_enabled' => ['nullable', 'boolean'],
            'member_access_restriction_start_time' => ['nullable', 'date_format:H:i'],
            'member_access_restriction_end_time' => ['nullable', 'date_format:H:i'],
            'member_access_restriction_group' => [
                'nullable',
                'string',
                Rule::enum(MemberAccessRestrictionGroup::class),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('member_access_restriction_enabled')) {
                return;
            }

            $start = $this->input('member_access_restriction_start_time');
            $end = $this->input('member_access_restriction_end_time');
            $hasStart = filled($start);
            $hasEnd = filled($end);

            if ($hasStart xor $hasEnd) {
                $validator->errors()->add(
                    $hasStart ? 'member_access_restriction_end_time' : 'member_access_restriction_start_time',
                    'Enter both restriction times, or leave both empty.',
                );
            }

            if ($hasStart && $hasEnd && $start === $end) {
                $validator->errors()->add('member_access_restriction_end_time', 'The restriction end time must be different from the start time.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'enabled_payment_methods' => 'payment methods',
            'membership_reminder_days' => 'membership reminder days',
            'default_admission_fee' => 'default admission fee',
            'rfid_card_fee' => 'card fee',
            'rfid_card_deposit' => 'card deposit',
            'rfid_replacement_card_fee' => 'replacement card fee',
            'receipt_footer' => 'receipt footer',
        ];
    }
}
