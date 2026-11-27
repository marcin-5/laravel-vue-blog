<?php

namespace App\Http\Requests\Blogger;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReorderPostExtensionsRequest extends FormRequest
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
        $attachedExtensionRule = Rule::exists('post_extensions', 'extension_post_id')
            ->where('post_id', $post instanceof Post ? $post->id : 0);

        return [
            'extensions' => ['required', 'array'],
            'extensions.*.id' => ['required', 'integer', $attachedExtensionRule],
            'extensions.*.display_order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $post = $this->route('post');

                if (!$post instanceof Post || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $submittedIds = collect($this->input('extensions'))
                    ->pluck('id')
                    ->map(fn(int|string $id): int => (int) $id);
                $attachedIds = $post->extensions()
                    ->pluck('post_extensions.extension_post_id')
                    ->map(fn(int|string $id): int => (int) $id);

                if (
                    $submittedIds->unique()->count() !== $submittedIds->count()
                    || $submittedIds->sort()->values()->all() !== $attachedIds->sort()->values()->all()
                ) {
                    $validator->errors()->add('extensions', __('blogger.extensions.invalid_order'));
                }
            },
        ];
    }
}
