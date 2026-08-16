<?php

declare(strict_types=1);

namespace App\Http\Requests\Enneagram;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class ActionEnneagramTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'testId' => ['required', 'string', 'size:40'],
            'action' => ['required', 'string', 'in:answer,skip,back'],
            'answers' => ['sometimes', 'array', 'max:9'],
            'answers.*' => ['required'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $action = (string) $this->input('action', '');
                $answers = $this->input('answers', []);

                if (!is_array($answers)) {
                    return;
                }

                if ($action === 'answer' && $answers === []) {
                    $validator->errors()->add('answers', 'At least one answer is required.');
                }

                if ($action !== 'answer' && $answers !== []) {
                    $validator->errors()->add('answers', 'Only answer actions may contain answers.');
                }

                foreach ($answers as $index => $answer) {
                    if (is_string($answer) && $answer !== '') {
                        continue;
                    }

                    if (!is_array($answer)) {
                        $validator->errors()->add("answers.$index", 'Each answer must be a key or an answer object.');
                        continue;
                    }

                    $unknownKeys = array_diff(array_keys($answer), ['key', 'value', 'category']);

                    if ($unknownKeys !== [] || !is_string($answer['key'] ?? null) || $answer['key'] === '') {
                        $validator->errors()->add(
                            "answers.$index",
                            'Each answer object must contain only a valid key, value, and category.',
                        );
                    }
                }
            },
        ];
    }
}
