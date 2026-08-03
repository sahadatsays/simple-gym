<?php

namespace App\Http\Requests\Admin;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

class SearchPosProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Payment::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
        ];
    }
}
