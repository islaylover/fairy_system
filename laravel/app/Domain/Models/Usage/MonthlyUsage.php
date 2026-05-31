<?php

declare(strict_types=1);

namespace App\Domain\Models\Usage;

use App\Domain\Models\User\UserId;

readonly class MonthlyUsage
{
    public function __construct(
        public UserId $userId,
        public YearMonth $yearMonth,
        public int $promptTokens,
        public int $completionTokens,
        public int $totalTokens,
        public MoneyUsd $estimatedCostUsd,
        public int $requestsDoneCount
    ) {}

    public static function zero(UserId $userId, YearMonth $yearMonth): self
    {
        return new self(
            userId: $userId,
            yearMonth: $yearMonth,
            promptTokens: 0,
            completionTokens: 0,
            totalTokens: 0,
            estimatedCostUsd: MoneyUsd::zero(),
            requestsDoneCount: 0
        );
    }

    public static function fromSumRow(array $sum, YearMonth $yearMonth): self
    {
        return new self(
            userId: new UserId(0), // ★全体集計用のダミーID（system扱い）
            yearMonth: $yearMonth,
            promptTokens: (int) ($sum['prompt_tokens'] ?? 0),
            completionTokens: (int) ($sum['completion_tokens'] ?? 0),
            totalTokens: (int) ($sum['total_tokens'] ?? 0),
            estimatedCostUsd: new MoneyUsd((string) ($sum['estimated_cost_usd'] ?? '0.00000')),
            requestsDoneCount: (int) ($sum['requests_done_count'] ?? 0),
        );
    }
}
