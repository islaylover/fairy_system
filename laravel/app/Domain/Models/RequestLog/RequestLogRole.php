<?php

declare(strict_types=1);

namespace App\Domain\Models\RequestLog;

use App\Domain\Enums\MessageRoleEnum;
use App\Domain\Utility\Validator\EnumValidator;

readonly class RequestLogRole
{
    public function __construct(private string $value)
    {
        EnumValidator::validate($value, [
            'label' => 'ロール',
            'allowed' => array_column(MessageRoleEnum::cases(), 'value'),
        ]);
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
