<?php

namespace App\Http\Requests\Blogger;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecommendedLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $blog = $this->route('blog');

        return $blog && $this->user()->can('update', $blog);
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
