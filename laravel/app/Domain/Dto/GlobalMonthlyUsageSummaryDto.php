<?php

declare(strict_types=1);

namespace App\Domain\Dto;

use App\Domain\Models\Usage\GlobalMonthlyUsage;
use App\Domain\Models\Usage\MoneyUsd;

final readonly class GlobalMonthlyUsageSummaryDto
{
    public function __construct(
        private string $yearMonth,
        private int $requestsDoneCount,
        private int $promptTokens,
        private int $completionTokens,
        private int $totalTokens,
        private string $estimatedCostUsd,
        private string $limitUsd,
        private string $remainingUsd,
        private bool $isOverLimit,
    ) {}

    public static function fromGlobalMonthlyUsage(
        GlobalMonthlyUsage $usage,
        MoneyUsd $limitUsd,
        string $remainingUsd,
        bool $isOverLimit
    ): self {
        return new self(
            yearMonth: $usage->yearMonth->getValue(),
            requestsDoneCount: $usage->requestsDoneCount,
            promptTokens: $usage->promptTokens,
            completionTokens: $usage->completionTokens,
            totalTokens: $usage->totalTokens,
            estimatedCostUsd: $usage->estimatedCostUsd->getValue(),
            limitUsd: $limitUsd->getValue(),
            remainingUsd: $remainingUsd,
            isOverLimit: $isOverLimit
        );
    }

    public function toArray(): array
    {
        return [
            'year_month' => $this->yearMonth,
            'requests_done_count' => $this->requestsDoneCount,
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens' => $this->totalTokens,
            'estimated_cost_usd' => $this->estimatedCostUsd,
            'limit_usd' => $this->limitUsd,
            'remaining_usd' => $this->remainingUsd,
            'is_over_limit' => $this->isOverLimit,
        ];
    }
}
