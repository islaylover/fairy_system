<?php

declare(strict_types=1);

namespace App\Domain\Models\Request;

use App\Domain\Utility\Validator\NumberValidator;

readonly class RequestTotalToken
{
    public function __construct(
        private int $total_tokens
    ) {
        NumberValidator::validateNumber($total_tokens, [
            'label' => 'Total Token',
        ]);
    }

    public function getValue(): int
    {
        return $this->total_tokens;
    }
}