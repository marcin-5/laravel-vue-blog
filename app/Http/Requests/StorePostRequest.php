<?php

namespace App\Http\Requests;

use App\Models\Blog;
use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $groupId = $this->input('group_id');
        if ($groupId) {
            $group = Group::find($groupId);
            if (!$group) {
                return false;
            }

            if ($group->user_id === $this->user()->id) {
                return true;
            }

            return $group->members()->where('users.id', $this->user()->id)->wherePivot('role', 'contributor')->exists();
        }

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
            'blog_id' => ['nullable', 'integer', 'exists:blogs,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'title' => ['required', 'string', 'max:255'],
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
        $config = config('blogger.posts.defaults', []);

        return [
            'blog_id' => $validated['blog_id'] ?? null,
            'group_id' => $validated['group_id'] ?? null,
            'title' => $validated['title'],
            'seo_title' => $validated['seo_title'] ?? null,
            'excerpt' => $validated['excerpt'] ?? null,
            'summary' => $validated['summary'] ?? null,
            'content' => $validated['content'] ?? null,
            'is_published' => (bool) ($validated['is_published'] ?? $config['is_published'] ?? false),
            'visibility' => $validated['visibility'] ?? $config['visibility'] ?? 'public',
            'related_posts' => $validated['related_posts'] ?? [],
            'external_links' => $validated['external_links'] ?? [],
        ];
    }

    public function getBlog(): ?Blog
    {
        $blogId = $this->input('blog_id');

        return $blogId ? Blog::findOrFail($blogId) : null;
    }

    public function getGroup(): ?Group
    {
        $groupId = $this->input('group_id');

        return $groupId ? Group::findOrFail($groupId) : null;
    }
}
