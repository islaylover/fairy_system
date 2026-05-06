<?php

declare(strict_types=1);

namespace App\Domain\Models\Request;

use App\Domain\Utility\Validator\NumberValidator;

readonly class RequestCompletionToken
{
    public function __construct(
        private int $completion_tokens
    ) {
        NumberValidator::validateNumber($completion_tokens, [
            'label' => 'Completion Token',
        ]);
    }

    public function getValue(): int
    {
        return $this->completion_tokens;
    }
}