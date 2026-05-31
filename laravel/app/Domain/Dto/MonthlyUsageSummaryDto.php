<?php

declare(strict_types=1);

namespace App\Domain\Dto;

use App\Domain\Models\Usage\MoneyUsd;
use App\Domain\Models\Usage\MonthlyUsage;

final class MonthlyUsageSummaryDto
{
    public function __construct(
        public readonly string $yearMonth,
        public readonly string $estimatedCostUsd,
        public readonly int $totalTokens,
        public readonly int $requestsDoneCount,
        public readonly string $limitUsd,
        public readonly string $remainingUsd,
        public readonly bool $isOverLimit,
    ) {}

    public static function fromMonthlyUsage(
        MonthlyUsage $usage,
        MoneyUsd $limitUsd,
        string $remainingUsd,
        bool $isOverLimit
    ): self {
        return new self(
            yearMonth: $usage->yearMonth->getValue(),
            estimatedCostUsd: $usage->estimatedCostUsd->getValue(),
            totalTokens: $usage->totalTokens,
            requestsDoneCount: $usage->requestsDoneCount,
            limitUsd: $limitUsd->getValue(),
            remainingUsd: $remainingUsd,
            isOverLimit: $isOverLimit
        );
    }

    public function toArray(): array
    {
        return [
            'year_month' => $this->yearMonth,
            'estimated_cost_usd' => $this->estimatedCostUsd,
            'total_tokens' => $this->totalTokens,
            'requests_done_count' => $this->requestsDoneCount,
            'limit_usd' => $this->limitUsd,
            'remaining_usd' => $this->remainingUsd,
            'is_over_limit' => $this->isOverLimit,
        ];
    }
}
