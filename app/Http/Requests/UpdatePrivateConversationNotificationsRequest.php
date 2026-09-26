<?php

namespace App\Http\Requests;

use App\Models\PrivateConversation;
use App\Policies\PrivateConversationPolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePrivateConversationNotificationsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $conversation = $this->route('privateConversation');

        return $conversation instanceof PrivateConversation
            && $this->user() !== null
            && (new PrivateConversationPolicy)->update($this->user(), $conversation);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email_notifications' => ['required', 'boolean'],
        ];
    }
}
