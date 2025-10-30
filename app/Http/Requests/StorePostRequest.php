<?php

namespace App\Http\Requests;

use App\Models\Blog;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $blogId = $this->input('blog_id');
        if (!$blogId) {
            return false;
        }

        $blog = Blog::find($blogId);
        if (!$blog) {
            return false;
        }

        return $blog->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        $config = config('blogger.posts', []);

        return [
            'blog_id' => ['required', 'integer', 'exists:blogs,id'],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:' . ($config['excerpt_max_length'] ?? 500)],
            'content' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'visibility' => [
                'sometimes',
                'string',
                'in:' . implode(',', $config['allowed_visibility'] ?? ['public', 'registered'])
            ],
        ];
    }

    public function getPostData(): array
    {
        $validated = $this->validated();
        $config = config('blogger.posts.defaults', []);

        return [
            'blog_id' => $validated['blog_id'],
            'title' => $validated['title'],
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,
            'is_published' => (bool)($validated['is_published'] ?? $config['is_published'] ?? false),
            'visibility' => $validated['visibility'] ?? $config['visibility'] ?? 'public',
        ];
    }

    public function getBlog(): Blog
    {
        return Blog::findOrFail($this->input('blog_id'));
    }
}
