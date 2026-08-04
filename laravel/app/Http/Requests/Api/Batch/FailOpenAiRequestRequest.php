<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Batch;

use Illuminate\Foundation\Http\FormRequest;

final class FailOpenAiRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'error_text' => ['required', 'string'],
        ];
    }
}
