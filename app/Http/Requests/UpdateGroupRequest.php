<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('group'));
    }

    public function rules(): array
    {
        $config = config('blogger');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
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
        $data = [];

        $fields = [
            'name',
            'content',
            'footer',
            'is_published',
            'locale',
            'sidebar',
            'page_size',
            'theme'
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $validated)) {
                $data[$field] = $validated[$field];
                if ($field === 'is_published') {
                    $data[$field] = (bool)$validated[$field];
                }
                if (in_array($field, ['sidebar', 'page_size'])) {
                    $data[$field] = (int)$validated[$field];
                }
            }
        }

        return $data;
    }
}
