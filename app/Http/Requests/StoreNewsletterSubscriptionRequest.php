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
            'blog_ids' => ['required', 'array', 'min:1'],
            'blog_ids.*' => ['exists:blogs,id'],
            'frequency' => ['required', 'in:daily,weekly'],
        ];
    }

    public function messages(): array
    {
        return [
            'blog_ids.required' => 'Musisz wybrać przynajmniej jeden blog.',
            'frequency.required' => 'Wybierz częstotliwość powiadomień.',
            'email.required' => 'Adres e-mail jest wymagany.',
            'email.email' => 'Podaj poprawny adres e-mail.',
        ];
    }
}
