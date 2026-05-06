<?php

declare(strict_types=1);

namespace App\Domain\Models\Usage;

readonly class GlobalMonthlyUsage
{
    public function __construct(
        public YearMonth $yearMonth,
        public int $promptTokens,
        public int $completionTokens,
        public int $totalTokens,
        public MoneyUsd $estimatedCostUsd,
        public int $requestsDoneCount,
    ) {}
}
