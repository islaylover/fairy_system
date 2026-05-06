<?php

declare(strict_types=1);

namespace App\Infrastructure\Mappers;

use App\Domain\Models\User\UserId;
use App\Domain\Models\Usage\YearMonth;
use App\Domain\Models\Usage\MoneyUsd;
use App\Domain\Models\Usage\MonthlyUsage;
use App\Infrastructure\Eloquent\MonthlyUsageEloquent;

final class MonthlyUsageMapper
{
    /**
     * Eloquent -> Domain
     */
    public static function toDomain(MonthlyUsageEloquent $e): MonthlyUsage
    {
        return new MonthlyUsage(
            userId: new UserId((int) $e->user_id),
            yearMonth: new YearMonth((string) $e->year_month),
            promptTokens: (int) $e->prompt_tokens,
            completionTokens: (int) $e->completion_tokens,
            totalTokens: (int) $e->total_tokens,
            estimatedCostUsd: new MoneyUsd((string) $e->estimated_cost_usd),
            requestsDoneCount: (int) $e->requests_done_count
        );
    }
}
