<?php

namespace App\Http\Requests\Blogger;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderRecommendedLinkCategoriesRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_ids' => ['present', 'array'],
            'category_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('recommended_link_categories', 'id')
                    ->where('blog_id', $this->route('blog')->id),
            ],
        ];
    }
}
