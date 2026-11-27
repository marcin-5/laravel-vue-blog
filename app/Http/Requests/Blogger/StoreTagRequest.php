<?php

namespace App\Http\Requests\Blogger;

use App\Models\Blog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $blog = $this->route('blog');

        return $blog instanceof Blog && ($this->user()?->can('update', $blog) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $slug = $this->input('slug');

        if (!is_string($slug) || trim($slug) === '') {
            $slug = is_string($name) ? $name : '';
        }

        $this->merge(['slug' => Str::slug($slug)]);
    }

    public function rules(): array
    {
        $blog = $this->route('blog');

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:120',
                Rule::unique('tags', 'slug')->where('blog_id', $blog instanceof Blog ? $blog->id : 0),
            ],
        ];
    }
}
