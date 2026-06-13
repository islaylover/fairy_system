<?php

declare(strict_types=1);

namespace App\Domain\Models\Request;

use App\Domain\Utility\Validator\NumberValidator;

readonly class RequestPromptToken
{
    public function __construct(
        private int $prompt_tokens
    ) {
        NumberValidator::validateNumber($prompt_tokens, [
            'label' => 'Prompt Token',
            'min' => 1,
        ]);
    }

    public function getValue(): int
    {
        return $this->prompt_tokens;
    }
}
