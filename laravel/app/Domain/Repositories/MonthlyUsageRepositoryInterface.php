<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Models\Usage\MonthlyUsage;
use App\Domain\Models\Usage\YearMonth;
use App\Domain\Models\User\UserId;

interface MonthlyUsageRepositoryInterface
{
    /**
     * 指定されたユーザーの指定された年月のOpenAIの総合使用情報を算出する
     */
    public function findByUserIdAndYearMonth(UserId $userId, YearMonth $yearMonth): ?MonthlyUsage;

    /**
     * 指定された年月のOpenAIの総合使用情報を算出する
     */
    public function sumAllUsersByYearMonth(YearMonth $yearMonth): array;

    /**
     * 指定ユーザー・年月のOpenAI利用量を加算する
     */
    public function addUsage(
        UserId $userId,
        YearMonth $yearMonth,
        int $promptTokens,
        int $completionTokens,
        int $totalTokens,
        string $estimatedCostUsd
    ): void;
}
