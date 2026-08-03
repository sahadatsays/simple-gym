<?php

namespace App\Http\Requests\Admin;

use App\Enums\PlanStatus;
use App\Models\Member;
use App\Models\MembershipPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'payment_method' => ['required', 'string', Rule::in(['cash', 'card', 'mobile_banking'])],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'amount_received' => ['required', 'numeric', 'min:'.$planTotal],
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
}
