<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PlanStatus;
use App\Models\Member;
use App\Models\MembershipPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMemberRenewalRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Member $member */
        $member = $this->route('member');

        return $this->user()?->can('renew', $member) ?? false;
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
            $planTotal = $plan ? (float) $plan->membership_fee : 0.0;
        }

        return [
            'membership_plan_id' => [
                'required',
                'integer',
                Rule::exists('membership_plans', 'id')->where('status', PlanStatus::Active->value),
            ],
            'payment_method' => ['required', 'string', Rule::enum(PaymentMethod::class)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'amount_received' => ['required', 'numeric', 'min:0'],
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

            $subtotal = (float) $plan->membership_fee;
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
}
