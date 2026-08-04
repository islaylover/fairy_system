<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Batch;

use Illuminate\Foundation\Http\FormRequest;

final class CompleteOpenAiRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'result_text' => ['required', 'string'],
            'prompt_tokens' => ['required', 'integer', 'min:0'],
            'completion_tokens' => ['required', 'integer', 'min:0'],
            'total_tokens' => ['required', 'integer', 'min:0'],
        ];
    }
}
