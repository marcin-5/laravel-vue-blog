<?php

namespace App\Http\Requests;

use App\Models\Comment;
use App\Models\Thread;
use App\Policies\CommentPolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $thread = $this->route('thread');

        return $thread instanceof Thread
            && $this->user() !== null
            && (new CommentPolicy)->create($this->user(), $thread);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            'content' => ['required', 'string', 'max:10000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $parentId = $this->input('parent_id');
            $thread = $this->route('thread');

            if ($parentId === null || !$thread instanceof Thread || $validator->errors()->has('parent_id')) {
                return;
            }

            $parent = Comment::query()->find($parentId);
            if ($parent === null || $parent->thread_id !== $thread->id) {
                $validator->errors()->add('parent_id', 'The selected parent comment is invalid.');

                return;
            }

            $thread->loadMissing('post');
            if ($thread->post->comments_max_depth > 0
                && $parent->depth >= $thread->post->comments_max_depth) {
                $validator->errors()->add('parent_id', 'The maximum comment depth has been reached.');
            }
        }];
    }
}
