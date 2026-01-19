<?php

namespace App\Http\Requests;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;

class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Group::class);
    }

    public function rules(): array
    {
        $config = config('blogger');

        return [
            'name' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'footer' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
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

    public function getGroupData(): array
    {
        $validated = $this->validated();
        $config = config('blogger.defaults');

        return [
            'user_id' => $this->user()->id,
            'name' => $validated['name'],
            'content' => $validated['content'] ?? null,
            'footer' => $validated['footer'] ?? null,
            'is_published' => (bool)($validated['is_published'] ?? false),
            'locale' => $validated['locale'] ?? app()->getLocale() ?? $config['locale'],
            'sidebar' => (int)($validated['sidebar'] ?? $config['sidebar']),
            'page_size' => (int)($validated['page_size'] ?? $config['page_size']),
            'theme' => $validated['theme'] ?? null,
        ];
    }
}
