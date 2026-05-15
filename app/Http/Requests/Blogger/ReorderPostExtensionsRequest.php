<?php

namespace App\Http\Requests\Blogger;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderPostExtensionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'extensions' => 'required|array',
            'extensions.*.id' => 'required|exists:posts,id',
            'extensions.*.display_order' => 'required|integer|min:0',
        ];
    }
}
