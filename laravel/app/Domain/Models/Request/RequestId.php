<?php

declare(strict_types=1);

namespace App\Domain\Models\Request;

use App\Domain\Utility\Validator\NumberValidator;

readonly class RequestId
{
    public function __construct(
        private int $id
    ) {
        NumberValidator::validateNumber($id, [
            'label' => 'リクエストID',
        ]);
    }

    public function getValue(): int
    {
        return $this->id;
    }
}
