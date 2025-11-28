<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit-user-blog-quota');
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'in:admin,blogger,user'],
            'blog_quota' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
