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
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'about_seo_description' => ['nullable', 'string', 'max:500'],
            'contact_seo_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'footer' => ['nullable', 'string'],
            'motto' => ['nullable', 'string'],
            'is_multi_author' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'locale' => ['sometimes', 'string', 'in:' . implode(',', $config['supported_locales'])],
            'sidebar' => [
                'sometimes',
                'integer',
                'between:' . $config['limits']['sidebar']['min'] . ',' . $config['limits']['sidebar']['max'],
            ],
            'page_size' => [
                'sometimes',
                'integer',
                'min:' . $config['limits']['page_size']['min'],
                'max:' . $config['limits']['page_size']['max'],
            ],
            'theme' => ['nullable', 'array'],
            'theme.light' => ['nullable', 'array'],
            'theme.dark' => ['nullable', 'array'],
            'landing_content' => ['nullable', 'string'],
            'about' => ['nullable', 'string'],
        ];
    }

    public function getBlogData(): array
    {
        $validated = $this->validated();
        $data = [];

        if (array_key_exists('name', $validated)) {
            $data['name'] = $validated['name'];
        }

        if (array_key_exists('seo_title', $validated)) {
            $data['seo_title'] = $validated['seo_title'];
        }

        if (array_key_exists('seo_description', $validated)) {
            $data['seo_description'] = $validated['seo_description'];
        }

        if (array_key_exists('about_seo_description', $validated)) {
            $data['about_seo_description'] = $validated['about_seo_description'];
        }

        if (array_key_exists('contact_seo_description', $validated)) {
            $data['contact_seo_description'] = $validated['contact_seo_description'];
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

        if (array_key_exists('is_multi_author', $validated)) {
            $data['is_multi_author'] = (bool) $validated['is_multi_author'];
        }

        if (array_key_exists('is_published', $validated)) {
            $data['is_published'] = (bool) $validated['is_published'];
        }

        if (array_key_exists('locale', $validated)) {
            $data['locale'] = $validated['locale'];
        }

        if (array_key_exists('sidebar', $validated)) {
            $data['sidebar'] = (int) $validated['sidebar'];
        }

        if (array_key_exists('page_size', $validated)) {
            $data['page_size'] = (int) $validated['page_size'];
        }

        if (array_key_exists('theme', $validated)) {
            $data['theme'] = $validated['theme'];
        }

        if (array_key_exists('landing_content', $validated)) {
            $data['landing_content'] = $validated['landing_content'];
        }

        if (array_key_exists('about', $validated)) {
            $data['about'] = $validated['about'];
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
