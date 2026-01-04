<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('blog'));
    }

    public function rules(): array
    {
        $config = config('blogger');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
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
        $data = [];

        if (array_key_exists('name', $validated)) {
            $data['name'] = $validated['name'];
        }

        if (array_key_exists('description', $validated)) {
            $data['description'] = $validated['description'];
        }

        if (array_key_exists('footer', $validated)) {
            $data['footer'] = $validated['footer'];
        }

        if (array_key_exists('motto', $validated)) {
            $data['motto'] = $validated['motto'];
        }

        if (array_key_exists('is_published', $validated)) {
            $data['is_published'] = (bool)$validated['is_published'];
        }

        if (array_key_exists('locale', $validated)) {
            $data['locale'] = $validated['locale'];
        }

        if (array_key_exists('sidebar', $validated)) {
            $data['sidebar'] = (int)$validated['sidebar'];
        }

        if (array_key_exists('page_size', $validated)) {
            $data['page_size'] = (int)$validated['page_size'];
        }

        if (array_key_exists('theme', $validated)) {
            $data['theme'] = $validated['theme'];
        }

        return $data;
    }

    public function hasCategories(): bool
    {
        return array_key_exists('categories', $this->validated());
    }

    public function getCategories(): array
    {
        return $this->validated()['categories'] ?? [];
    }
}
