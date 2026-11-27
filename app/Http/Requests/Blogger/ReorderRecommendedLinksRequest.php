<?php

namespace App\Http\Requests\Blogger;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderRecommendedLinksRequest extends FormRequest
{
    public function authorize(): bool
    {
        $blog = $this->route('blog');

        return $blog && $this->user()->can('update', $blog);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $blogId = $this->route('blog')->id;

        return [
            'category_id' => [
                'present',
                'nullable',
                'integer',
                Rule::exists('recommended_link_categories', 'id')->where('blog_id', $blogId),
            ],
            'link_ids' => ['present', 'array'],
            'link_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('recommended_links', 'id')->where('blog_id', $blogId),
            ],
        ];
    }
}
