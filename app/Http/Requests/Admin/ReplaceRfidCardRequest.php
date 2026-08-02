<?php

namespace App\Http\Requests\Admin;

use App\Models\Member;
use App\Models\RfidCard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReplaceRfidCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('replace', RfidCard::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')->whereNull('deleted_at')],
            'card_number' => ['required', 'string', 'max:50'],
        ];
    }

    public function member(): Member
    {
        return Member::query()->findOrFail($this->validated('member_id'));
    }
}
