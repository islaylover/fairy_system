<?php

declare(strict_types=1);

namespace App\Domain\Models\Usage;

use App\Domain\Utility\Validator\DateValidator;

readonly class YearMonth
{
    private string $yearMonth;

    public function __construct(string $yearMonth)
    {
        $this->yearMonth = DateValidator::validateYearMonth(
            yearMonth: $yearMonth,
            label: '年月'
        );
    }

    public function getValue(): string
    {
        return $this->yearMonth;
    }

    public function __toString(): string
    {
        return $this->yearMonth;
    }
}
