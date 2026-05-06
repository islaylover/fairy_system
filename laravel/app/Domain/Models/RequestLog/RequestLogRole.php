<?php

declare(strict_types=1);

namespace App\Domain\Models\RequestLog;

use App\Domain\Utility\Validator\EnumValidator;

readonly class RequestLogRole
{

    public function __construct(private string $value)
    {
        EnumValidator::validate($value, [
            'label' => 'ロール',
            'allowed' => config('chatgpt.roles'),
        ]);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
