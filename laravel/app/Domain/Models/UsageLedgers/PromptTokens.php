<?php

declare(strict_types=1);

namespace App\Domain\Models\UsageLedgers;

use App\Domain\Utility\Validator\NumberValidator;

readonly class PromptTokens
{
    public function __construct(private int $value)
    {
        NumberValidator::validateNumber($value, [
            'label' => 'Prompt Tokens',
            'min' => 0,
        ]);
    }

    public function getValue(): int
    {
        return $this->value;
    }
}
