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

        $user = $this->user();

        if ($post->blog && $post->blog->user_id === $user->id) {
            return true;
        }

        if ($post->group && $post->group->user_id === $user->id) {
            return true;
        }
        if ($post->group && $post->group->members()->where('users.id', $user->id)->wherePivot(
            'role',
            'contributor',
        )->exists()) {
            return true;
        }
        return false;
    }

    public function rules(): array
    {
        $config = config('blogger.posts', []);

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:' . ($config['limits']['excerpt_max_length'] ?? 500)],
            'summary' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'visibility' => [
                'sometimes',
                'string',
                'in:' . implode(',', $config['allowed_visibility'] ?? ['public', 'registered']),
            ],
            'related_posts' => ['nullable', 'array'],
            'related_posts.*.blog_id' => ['required', 'integer', 'exists:blogs,id'],
            'related_posts.*.related_post_id' => ['required', 'integer', 'exists:posts,id'],
            'related_posts.*.reason' => ['nullable', 'string', 'max:500'],
            'related_posts.*.display_order' => ['nullable', 'integer', 'min:0'],
            'external_links' => ['nullable', 'array'],
            'external_links.*.title' => ['required', 'string', 'max:255'],
            'external_links.*.url' => ['required', 'url', 'max:255'],
            'external_links.*.description' => ['nullable', 'string', 'max:500'],
            'external_links.*.reason' => ['nullable', 'string', 'max:500'],
            'external_links.*.display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function getPostData(): array
    {
        $validated = $this->validated();
        $data = [];

        if (array_key_exists('title', $validated)) {
            $data['title'] = $validated['title'];
        }

        if (array_key_exists('seo_title', $validated)) {
            $data['seo_title'] = $validated['seo_title'];
        }

        if (array_key_exists('excerpt', $validated)) {
            $data['excerpt'] = $validated['excerpt'];
        }

        if (array_key_exists('summary', $validated)) {
            $data['summary'] = $validated['summary'];
        }

        if (array_key_exists('content', $validated)) {
            $data['content'] = $validated['content'];
        }

        if (array_key_exists('is_published', $validated)) {
            $data['is_published'] = (bool) $validated['is_published'];
        }

        if (array_key_exists('visibility', $validated)) {
            $data['visibility'] = $validated['visibility'];
        }

        if (array_key_exists('related_posts', $validated)) {
            $data['related_posts'] = $validated['related_posts'];
        }

        if (array_key_exists('external_links', $validated)) {
            $data['external_links'] = $validated['external_links'];
        }

        return $data;
    }

    public function hasPublishingChanges(): bool
    {
        return array_key_exists('is_published', $this->validated());
    }
}
