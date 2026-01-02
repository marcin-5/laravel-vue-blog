<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsletterSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'subscriptions' => ['required', 'array', 'min:1'],
            'subscriptions.*.blog_id' => ['required', 'exists:blogs,id'],
            'subscriptions.*.frequency' => ['required', 'in:daily,weekly'],
            'subscriptions.*.send_time' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'subscriptions.*.send_day' => ['nullable', 'integer', 'min:1', 'max:7'],
        ];
    }

    public function messages(): array
    {
        return [
            'subscriptions.required' => 'Musisz wybrać przynajmniej jeden blog.',
            'email.required' => 'Adres e-mail jest wymagany.',
            'email.email' => 'Podaj poprawny adres e-mail.',
        ];
    }
}
