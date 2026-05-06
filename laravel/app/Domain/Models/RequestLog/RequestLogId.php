<?php

declare(strict_types=1);

namespace App\Domain\Models\RequestLog;

use App\Domain\Utility\Validator\NumberValidator;

readonly class RequestLogId
{
    public function __construct(
        private int $id
    ) {
        NumberValidator::validateNumber($id, [
            'label' => 'RequestLog ID',
        ]);
    }

    public function getValue(): int
    {
        return $this->id;
    }
}