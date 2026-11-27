<?php

namespace App\Http\Requests\Blogger;

use App\Models\Blog;
use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;

class DestroyTagRequest extends FormRequest
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

    public function rules(): array
    {
        return [];
    }
}
