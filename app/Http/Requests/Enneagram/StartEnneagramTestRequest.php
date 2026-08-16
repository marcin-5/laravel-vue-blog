<?php

declare(strict_types=1);

namespace App\Http\Requests\Enneagram;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StartEnneagramTestRequest extends FormRequest
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
            'extended' => ['sometimes', 'boolean'],
        ];
    }
}
