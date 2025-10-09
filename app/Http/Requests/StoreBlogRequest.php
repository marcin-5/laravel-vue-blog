<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Blog::class);
    }

    public function rules(): array
    {
        $config = config('blog');

        return [
            'description' => ['nullable', 'string'],
            'motto' => ['nullable', 'string'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'locale' => ['sometimes', 'string', 'in:' . implode(',', $config['supported_locales'])],
            'sidebar' => ['sometimes', 'integer', 'between:' . $config['limits']['sidebar']['min'] . ',' . $config['limits']['sidebar']['max']],
            'page_size' => ['sometimes', 'integer', 'min:' . $config['limits']['page_size']['min'], 'max:' . $config['limits']['page_size']['max']],
        ];
    }

    public function getBlogName(): string
    {
        return trim((string)($this->input('name') ?: 'New Blog'));
    }

    public function getBlogData(): array
    {
        $validated = $this->validated();
        $config = config('blog.defaults');

        return [
            'user_id' => $this->user()->id,
            'name' => $this->getBlogName(),
            'description' => $validated['description'] ?? null,
            'motto' => $validated['motto'] ?? null,
            'is_published' => false,
            'locale' => $validated['locale'] ?? app()->getLocale() ?? $config['locale'],
            'sidebar' => (int)($validated['sidebar'] ?? $config['sidebar']),
            'page_size' => (int)($validated['page_size'] ?? $config['page_size']),
        ];
    }

    public function getCategories(): array
    {
        return $this->validated()['categories'] ?? [];
    }
}