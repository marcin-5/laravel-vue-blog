<?php

namespace App\Http\Requests\Concerns;

trait HandlesPostValidation
{
    /**
     * Prepare the data for validation.
     * Accept `tags` sent as JSON string (from hidden input) and normalize to array of strings.
     */
    protected function prepareForValidation(): void
    {
        $tags = $this->input('tags');
        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge([
                    'tags' => array_values(array_filter($decoded, static fn($v) => is_string($v) || is_int($v))),
                ]);
            } elseif ($tags === '' || $tags === 'null') {
                $this->merge(['tags' => []]);
            }
        }
    }

    /**
     * Get the common validation rules for posts.
     */
    protected function postRules(): array
    {
        $config = config('blogger.posts', []);

        return [
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
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
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

    /**
     * Apply default values for extension visibility.
     */
    protected function applyExtensionVisibility(array &$data, string $visibility): void
    {
        if ($visibility === 'extension') {
            $data['seo_title'] = null;
            $data['excerpt'] = null;
            $data['summary'] = null;
            $data['related_posts'] = [];
            $data['external_links'] = [];
        }
    }
}
