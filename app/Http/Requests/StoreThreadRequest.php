<?php

namespace App\Http\Requests;

use App\Models\Post;
use App\Models\Thread;
use App\Policies\ThreadPolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post instanceof Post
            && $this->user() !== null
            && (new ThreadPolicy)->create($this->user(), $post);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'visibility' => ['required', 'string', Rule::in([
                Thread::VIS_PUBLIC,
                Thread::VIS_REGISTERED,
            ])],
            'content' => ['required', 'string', 'max:10000'],
        ];
    }
}
