<?php

namespace App\Http\Requests;

use App\Models\PrivateMessage;
use App\Policies\PrivateMessagePolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePrivateMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $privateMessage = $this->route('privateMessage');

        return $privateMessage instanceof PrivateMessage
            && $this->user() !== null
            && (new PrivateMessagePolicy)->update($this->user(), $privateMessage);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:10000'],
        ];
    }
}
