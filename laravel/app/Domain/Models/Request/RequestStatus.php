<?php

declare(strict_types=1);

namespace App\Domain\Models\Request;

use App\Domain\Enums\RequestStatusEnum;
use InvalidArgumentException;

class RequestStatus
{
    private RequestStatusEnum $status;

    public function __construct(int|RequestStatusEnum $status)
    {
        if ($status instanceof RequestStatusEnum) {
            $this->status = $status;

            return;
        }

        $enum = RequestStatusEnum::tryFrom($status);
        if ($enum === null) {
            throw new InvalidArgumentException("不正なリクエストステータス: {$status}");
        }

        $this->status = $enum;
    }

    public function getValue(): int
    {
        return $this->status->value;
    }

    public function __toString(): string
    {
        return (string) $this->status->value;
    }
}
