<?php

declare(strict_types=1);

namespace App\Domain\Models\PreRegister;

use App\Domain\Utility\Validator\DateValidator;
use DateTime;
use DateTimeInterface;

readonly class PreRegisterExpiresAt
{
    private DateTime $expires_at;

    public function __construct(string|\DateTimeInterface $expires_at)
    {
        $this->expires_at = DateValidator::validateDatetime($expires_at, '仮登録有効期限');
    }

    public function getValue(): DateTime
    {
        return $this->expires_at;
    }

    public function __toString(): string
    {
        return $this->expires_at->format('Y-m-d H:i:s');
    }
}