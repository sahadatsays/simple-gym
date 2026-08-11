<?php

namespace App\Http\Requests\Admin;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRenewalReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Member::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', Rule::in(config('gym.pagination.per_page_options', [15]))],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * @return array{
     *     search?: string|null,
     *     per_page?: int|null,
     *     direction?: string|null
     * }
     */
    public function validated($key = null, $default = null): array
    {
        /** @var array{search?: string|null, per_page?: int|null, direction?: string|null} $validated */
        $validated = parent::validated($key, $default);

        return $validated;
    }
}
