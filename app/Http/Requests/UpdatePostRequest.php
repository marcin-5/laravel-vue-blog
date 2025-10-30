<?php

namespace App\Http\Requests;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');
        if (!$post instanceof Post) {
            return false;
        }

        return $post->blog->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        $config = config('blogger.posts', []);

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
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
        $data = [];

        if (array_key_exists('title', $validated)) {
            $data['title'] = $validated['title'];
        }

        if (array_key_exists('excerpt', $validated)) {
            $data['excerpt'] = $validated['excerpt'];
        }

        if (array_key_exists('content', $validated)) {
            $data['content'] = $validated['content'];
        }

        if (array_key_exists('is_published', $validated)) {
            $data['is_published'] = (bool)$validated['is_published'];
        }

        if (array_key_exists('visibility', $validated)) {
            $data['visibility'] = $validated['visibility'];
        }

        return $data;
    }

    public function hasPublishingChanges(): bool
    {
        return array_key_exists('is_published', $this->validated());
    }
}
