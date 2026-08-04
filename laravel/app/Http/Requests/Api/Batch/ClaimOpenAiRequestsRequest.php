<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Batch;

use Illuminate\Foundation\Http\FormRequest;

final class ClaimOpenAiRequestsRequest extends FormRequest
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
            'batch_size' => [
                'required',
                'integer',
                'min:1',
                'max:100',
                function (string $attribute, mixed $value, callable $fail): void {
                    if (! is_int($value)) {
                        $fail("The {$attribute} field must be an integer.");
                    }
                },
            ],
        ];
    }
}
