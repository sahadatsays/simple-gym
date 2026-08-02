<?php

namespace App\Http\Requests\Admin;

use App\Models\Member;
use App\Models\RfidCard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRfidCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $card = $this->route('rfid_card');

        return $card instanceof RfidCard
            && ($this->user()?->can('assign', $card) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')->whereNull('deleted_at')],
        ];
    }

    public function member(): Member
    {
        return Member::query()->findOrFail($this->validated('member_id'));
    }
}
