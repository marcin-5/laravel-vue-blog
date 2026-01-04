<?php

namespace App\Http\Requests;

use App\Models\Blog;
use Illuminate\Foundation\Http\FormRequest;

class StoreBlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Blog::class);
    }

    public function rules(): array
    {
        $config = config('blogger');

        return [
            'description' => ['nullable', 'string'],
            'footer' => ['nullable', 'string'],
            'motto' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'locale' => ['sometimes', 'string', 'in:' . implode(',', $config['supported_locales'])],
            'sidebar' => [
                'sometimes',
                'integer',
                'between:' . $config['limits']['sidebar']['min'] . ',' . $config['limits']['sidebar']['max']
            ],
            'page_size' => [
                'sometimes',
                'integer',
                'min:' . $config['limits']['page_size']['min'],
                'max:' . $config['limits']['page_size']['max']
            ],
            'theme' => ['nullable', 'array'],
            'theme.light' => ['nullable', 'array'],
            'theme.dark' => ['nullable', 'array'],
        ];
    }

    public function getBlogData(): array
    {
        $validated = $this->validated();
        $config = config('blogger.defaults');

        return [
            'user_id' => $this->user()->id,
            'name' => $this->getBlogName(),
            'description' => $validated['description'] ?? null,
            'footer' => $validated['footer'] ?? null,
            'motto' => $validated['motto'] ?? null,
            'is_published' => (bool)($validated['is_published'] ?? false),
            'locale' => $validated['locale'] ?? app()->getLocale() ?? $config['locale'],
            'sidebar' => (int)($validated['sidebar'] ?? $config['sidebar']),
            'page_size' => (int)($validated['page_size'] ?? $config['page_size']),
            'theme' => $validated['theme'] ?? null,
        ];
    }

    public function getBlogName(): string
    {
        return trim((string)($this->input('name') ?: 'New Blog'));
    }

    public function getCategories(): array
    {
        return $this->validated()['categories'] ?? [];
    }
}
