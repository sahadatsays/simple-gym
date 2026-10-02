<?php

namespace App\Http\Requests\Admin;

use App\Enums\Gender;
use App\Enums\PaymentMethod;
use App\Enums\PlanStatus;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMemberRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Member::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('members', 'phone')->whereNull('deleted_at')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('members', 'email')->whereNull('deleted_at')],
            'gender' => ['nullable', 'string', Rule::enum(Gender::class)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:1000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'membership_plan_id' => [
                'required',
                'integer',
                Rule::exists('membership_plans', 'id')->where('status', PlanStatus::Active->value),
            ],
            'joined_at' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', Rule::enum(PaymentMethod::class)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'amount_received' => ['required', 'numeric', 'min:0'],
            'due_at' => ['nullable', 'date'],
            'rfid_card_id' => ['nullable', 'integer', Rule::exists('rfid_cards', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount_received.min' => 'Payment amount cannot be negative.',
            'due_at.date' => 'Choose a valid due date.',
            'membership_plan_id.required' => 'Please select a membership plan.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['membership_plan_id', 'amount_received', 'discount_amount'])) {
                return;
            }

            $plan = MembershipPlan::query()->find($this->integer('membership_plan_id'));

            if ($plan === null) {
                return;
            }

            $subtotal = Money::round((float) $plan->admission_fee + (float) $plan->membership_fee);
            $discountAmount = Money::round((float) ($this->input('discount_amount') ?? 0));
            $amountReceived = Money::round((float) $this->input('amount_received'));
            $invoiceTotal = Money::round(max(0, $subtotal - $discountAmount));

            if (Money::greaterThan($discountAmount, $subtotal)) {
                $validator->errors()->add('discount_amount', 'Discount cannot exceed the invoice subtotal.');
            }

            if (Money::greaterThan($amountReceived, $invoiceTotal)) {
                $validator->errors()->add('amount_received', 'Paid amount cannot exceed the invoice total.');
            }

            $balance = Money::round(max(0, $invoiceTotal - $amountReceived));

            if (Money::greaterThan($balance, 0)) {
                if (! $this->filled('due_at')) {
                    $validator->errors()->add('due_at', 'Choose a due date when the amount received is less than the total.');
                } elseif ($this->date('due_at')?->lt(now()->startOfDay())) {
                    $validator->errors()->add('due_at', 'The due date cannot be in the past.');
                }
            }

            if (($amountReceived > 0 || ! Money::greaterThan($invoiceTotal, 0)) && ! $this->filled('payment_method')) {
                $validator->errors()->add('payment_method', 'Choose a payment method for the amount received.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if ($this->filled('rfid_card_id') && $this->input('rfid_card_id') === '') {
            $normalized['rfid_card_id'] = null;
        }

        if (is_numeric($this->input('discount_amount'))) {
            $normalized['discount_amount'] = Money::round((float) $this->input('discount_amount'));
        }

        if (is_numeric($this->input('amount_received'))) {
            $normalized['amount_received'] = Money::round((float) $this->input('amount_received'));
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }
}
