<?php

namespace App\Http\Requests\Blogger;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled in the controller for now,
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'role' => ['nullable', 'string'],
        ];
    }
}
