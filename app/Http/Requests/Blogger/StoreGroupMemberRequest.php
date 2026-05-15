<?php

namespace App\Http\Requests\Blogger;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = $this->route('group');

        return $group && $this->user()->can('update', $group);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['nullable', 'string', \Illuminate\Validation\Rule::enum(\App\Enums\GroupRole::class)],
        ];
    }
}
