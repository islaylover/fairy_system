<?php

declare(strict_types=1);

namespace App\Domain\Models\UsageLedgers;

use App\Domain\Utility\Validator\DecimalValidator;

readonly class EstimatedCostUsd
{
    public function __construct(private string $value)
    {
        DecimalValidator::validate($value, [
            'label' => '利用コスト(米ドル)',
            'precision' => 8,
            'scale' => 5,
            'min' => 0,
            'max' => 999.99999,
        ]);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
