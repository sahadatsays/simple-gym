<?php

namespace App\Http\Requests\Admin;

use App\Enums\Gender;
use App\Enums\PaymentMethod;
use App\Enums\PlanStatus;
use App\Models\Member;
use App\Models\MembershipPlan;
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
        $planId = $this->integer('membership_plan_id');
        $planTotal = 0.0;

        if ($planId) {
            $plan = MembershipPlan::query()->find($planId);
            $planTotal = $plan ? (float) $plan->admission_fee + (float) $plan->membership_fee : 0.0;
        }

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
            'payment_method' => ['required', 'string', Rule::enum(PaymentMethod::class)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'amount_received' => ['required', 'numeric', 'min:0'],
            'rfid_card_id' => ['nullable', 'integer', Rule::exists('rfid_cards', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount_received.min' => 'Payment amount must cover the full invoice total.',
            'membership_plan_id.required' => 'Please select a membership plan.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $plan = MembershipPlan::query()->find($this->integer('membership_plan_id'));

            if ($plan === null) {
                return;
            }

            $subtotal = (float) $plan->admission_fee + (float) $plan->membership_fee;
            $discountAmount = (float) ($this->input('discount_amount') ?? 0);
            $amountReceived = (float) $this->input('amount_received');
            $invoiceTotal = max(0, $subtotal - $discountAmount);

            if ($discountAmount > $subtotal) {
                $validator->errors()->add('discount_amount', 'Discount cannot exceed the invoice subtotal.');
            }

            if ($amountReceived > $invoiceTotal) {
                $validator->errors()->add('amount_received', 'Paid amount cannot exceed the invoice total.');
            }

            if ($amountReceived < $invoiceTotal) {
                $validator->errors()->add('amount_received', 'Payment amount must cover the full invoice total.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('rfid_card_id') && $this->input('rfid_card_id') === '') {
            $this->merge(['rfid_card_id' => null]);
        }
    }
}
