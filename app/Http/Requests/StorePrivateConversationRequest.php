<?php

namespace App\Http\Requests;

use App\Models\Post;
use App\Policies\PrivateConversationPolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePrivateConversationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->user() === null) {
            return false;
        }

        $post = Post::find($this->integer('post_id'));

        return !$post instanceof Post
            || (new PrivateConversationPolicy)->create($this->user(), $post);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'email_notifications' => ['sometimes', 'boolean'],
        ];
    }
}
