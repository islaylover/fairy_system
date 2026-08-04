<?php

declare(strict_types=1);

namespace App\Domain\Models\UsageLedgers;

use App\Domain\Utility\Validator\NumberValidator;

readonly class UsageLedgersId
{
    public function __construct(private int $id)
    {
        NumberValidator::validateNumber($id, [
            'label' => 'Usage Ledger ID',
            'min' => 1,
        ]);
    }

    public function getValue(): int
    {
        return $this->id;
    }
}
