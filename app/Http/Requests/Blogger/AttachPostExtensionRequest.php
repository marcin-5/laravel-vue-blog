<?php

namespace App\Http\Requests\Blogger;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AttachPostExtensionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post instanceof Post && ($this->user()?->can('update', $post) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $post = $this->route('post');
        $extensionPostRule = Rule::exists('posts', 'id')->where('visibility', Post::VIS_EXTENSION);

        if ($post instanceof Post) {
            $extensionPostRule->where($post->group_id ? 'group_id' : 'blog_id', $post->group_id ?? $post->blog_id);
        }

        return [
            'extension_post_id' => ['required', 'integer', $extensionPostRule],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $post = $this->route('post');
                $extensionPostId = (int) $this->input('extension_post_id');

                if (!$post instanceof Post || $validator->errors()->has('extension_post_id')) {
                    return;
                }

                if ($extensionPostId === $post->id || $post->extensions()->whereKey($extensionPostId)->exists()) {
                    $validator->errors()->add('extension_post_id', __('blogger.extensions.invalid_extension'));
                }
            },
        ];
    }
}
