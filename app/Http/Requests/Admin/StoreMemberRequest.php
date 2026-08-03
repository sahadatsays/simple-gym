<?php

namespace App\Http\Requests\Admin;

use App\Enums\Gender;
use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
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
            'membership_plan_id' => ['nullable', 'integer', Rule::exists('membership_plans', 'id')],
            'joined_at' => ['required', 'date'],
            'membership_expires_at' => ['nullable', 'date', 'after_or_equal:joined_at'],
            'status' => ['required', 'string', Rule::enum(MemberStatus::class)],
        ];
    }
}
