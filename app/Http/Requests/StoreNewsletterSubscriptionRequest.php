<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Blog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidatorContract;

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
            'subscriptions.*' => ['required', 'array'],
            'subscriptions.*.blog_id' => [
                'required',
                Rule::exists((new Blog)->getTable(), 'id')->where(
                    fn($query) => $query->where('is_published', true),
                ),
            ],
            'subscriptions.*.frequency' => ['required', 'in:daily,weekly'],
            'subscriptions.*.send_time' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'subscriptions.*.send_time_weekend' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'subscriptions.*.send_day' => ['nullable', 'integer', 'min:1', 'max:7'],
        ];
    }

    /**
     * Normalize fields that do not apply to the selected frequency.
     */
    protected function prepareForValidation(): void
    {
        $subscriptions = $this->input('subscriptions');
        if (!is_array($subscriptions)) {
            return;
        }

        $this->merge([
            'subscriptions' => array_map(function (mixed $subscription): mixed {
                if (!is_array($subscription)) {
                    return $subscription;
                }

                if (($subscription['frequency'] ?? null) === 'daily') {
                    $subscription['send_day'] = null;
                }

                if (($subscription['frequency'] ?? null) === 'weekly') {
                    $subscription['send_time_weekend'] = null;
                }

                return $subscription;
            }, $subscriptions),
        ]);
    }

    /**
     * @return array<int, callable(ValidatorContract): void>
     */
    public function after(): array
    {
        return [function (ValidatorContract $validator): void {
            $blogIds = collect($this->input('subscriptions', []))
                ->filter(fn(mixed $subscription): bool => is_array($subscription))
                ->pluck('blog_id')
                ->filter();

            if ($blogIds->duplicates()->isNotEmpty()) {
                $validator->errors()->add('subscriptions', 'Każdy blog może wystąpić tylko raz.');
            }
        }];
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
