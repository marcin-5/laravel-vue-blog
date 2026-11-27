<?php

namespace App\Http\Requests\Blogger;

use App\Models\Blog;
use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $blog = $this->route('blog');
        $tag = $this->route('tag');

        return $blog instanceof Blog
            && $tag instanceof Tag
            && $tag->blog_id === $blog->id
            && ($this->user()?->can('update', $blog) ?? false);
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
        $tag = $this->route('tag');

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:120',
                Rule::unique('tags', 'slug')
                    ->where('blog_id', $blog instanceof Blog ? $blog->id : 0)
                    ->ignore($tag instanceof Tag ? $tag->id : 0),
            ],
        ];
    }
}
