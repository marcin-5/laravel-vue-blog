<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HandlesPostValidation;
use App\Models\Blog;
use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    use HandlesPostValidation;

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
        return array_merge([
            'blog_id' => ['nullable', 'integer', 'exists:blogs,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'title' => ['required', 'string', 'max:255'],
        ], $this->postRules());
    }

    public function getPostData(): array
    {
        $validated = $this->validated();
        $config = config('blogger.posts.defaults', []);

        $data = [
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
            'tags' => $validated['tags'] ?? [],
        ];

        $this->applyExtensionVisibility($data, $data['visibility']);

        return $data;
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
