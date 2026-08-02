<?php

namespace App\Http\Requests\Admin;

use App\Enums\PlanStatus;
use App\Models\MembershipPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMembershipPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $plan = $this->route('membership_plan');

        return $plan instanceof MembershipPlan
            && ($this->user()?->can('update', $plan) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var MembershipPlan $plan */
        $plan = $this->route('membership_plan');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('membership_plans', 'name')->ignore($plan->id)],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'admission_fee' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'membership_fee' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'string', Rule::enum(PlanStatus::class)],
            'features' => ['nullable', 'array'],
            'features.*' => ['nullable', 'string', 'max:255'],
            'features_text' => ['nullable', 'string', 'max:10000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('features_text')) {
            $this->merge([
                'features' => collect(preg_split('/\r\n|\r|\n/', (string) $this->input('features_text', '')))
                    ->map(fn (string $line): string => trim($line))
                    ->filter()
                    ->values()
                    ->all(),
            ]);
        }
    }
}
