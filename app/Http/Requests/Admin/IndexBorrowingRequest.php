<?php

namespace App\Http\Requests\Admin;

use App\Enums\BorrowingStatus;
use App\Models\Borrowing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Borrowing::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::enum(BorrowingStatus::class)],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ];
    }
}
