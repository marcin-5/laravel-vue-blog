<?php

namespace App\Http\Requests;

use App\Models\Post;
use App\Models\Group;
use App\Policies\PrivateConversationPolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePrivateConversationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'subject' => trim((string) $this->input('subject', '')),
            'content' => trim((string) $this->input('content', '')),
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->user() === null) {
            return false;
        }

        $post = $this->filled('post_id') ? Post::find($this->integer('post_id')) : null;
        $group = $this->filled('group_id') ? Group::find($this->integer('group_id')) : null;

        return $post instanceof Post
            ? (new PrivateConversationPolicy)->create($this->user(), $post)
            : ($group instanceof Group && (new PrivateConversationPolicy)->createForGroup($this->user(), $group));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'post_id' => ['nullable', 'integer', 'exists:posts,id', 'required_without:group_id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id', 'required_without:post_id'],
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'email_notifications' => ['sometimes', 'boolean'],
        ];
    }
}
