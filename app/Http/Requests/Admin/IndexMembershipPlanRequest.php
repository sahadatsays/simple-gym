<?php

namespace App\Http\Requests\Admin;

use App\Models\MembershipPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMembershipPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', MembershipPlan::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ];
    }
}
