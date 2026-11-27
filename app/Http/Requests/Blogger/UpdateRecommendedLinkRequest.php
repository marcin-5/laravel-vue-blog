<?php

namespace App\Http\Requests\Blogger;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecommendedLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $blog = $this->route('blog');

        return $blog && $this->user()->can('update', $blog);
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('recommended_link_categories', 'id')
                    ->where('blog_id', $this->route('blog')->id),
            ],
        ];
    }
}
