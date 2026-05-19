<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HandlesPostValidation;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    use HandlesPostValidation;

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
        return array_merge([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
        ], $this->postRules());
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

        if (array_key_exists('tags', $validated)) {
            $data['tags'] = $validated['tags'];
        }

        $visibility = $data['visibility'] ?? $this->route('post')->visibility;

        $this->applyExtensionVisibility($data, $visibility);

        return $data;
    }

    public function hasPublishingChanges(): bool
    {
        return array_key_exists('is_published', $this->validated());
    }
}
