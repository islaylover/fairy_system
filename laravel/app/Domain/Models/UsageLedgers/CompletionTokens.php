<?php

declare(strict_types=1);

namespace App\Domain\Models\UsageLedgers;

use App\Domain\Utility\Validator\NumberValidator;

readonly class CompletionTokens
{
    public function __construct(private int $value)
    {
        NumberValidator::validateNumber($value, [
            'label' => 'Completion Tokens',
            'min' => 0,
        ]);
    }

    public function getValue(): int
    {
        return $this->value;
    }
}
