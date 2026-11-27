<?php

namespace App\Http\Requests\Blogger;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DetachPostExtensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post instanceof Post && ($this->user()?->can('update', $post) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['extension_post_id' => $this->route('extensionPostId')]);
    }

    public function rules(): array
    {
        $post = $this->route('post');

        return [
            'extension_post_id' => [
                'required',
                'integer',
                Rule::exists('post_extensions', 'extension_post_id')
                    ->where('post_id', $post instanceof Post ? $post->id : 0),
            ],
        ];
    }
}
