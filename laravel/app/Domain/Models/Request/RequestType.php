<?php

declare(strict_types=1);

namespace App\Domain\Models\Request;

use App\Domain\Enums\RequestTypeEnum;
use InvalidArgumentException;

class RequestType
{
    private RequestTypeEnum $request_type;

    public function __construct(
        string|RequestTypeEnum $request_type
    ) {
        if ($request_type instanceof RequestTypeEnum) {
            $this->request_type = $request_type;

            return;
        }

        $enum = RequestTypeEnum::tryFrom($request_type);
        if ($enum === null) {
            throw new InvalidArgumentException("不正なリクスト種別: {$request_type}");
        }

        $this->request_type = $enum;
    }

    public function getValue(): string
    {
        return $this->request_type->value;
    }

    public function __toString(): string
    {
        return $this->request_type->value;
    }
}
