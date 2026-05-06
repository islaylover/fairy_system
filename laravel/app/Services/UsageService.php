<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Dto\MonthlyUsageSummaryDto;
use App\Domain\Models\User\UserId;
use App\Domain\Models\Usage\MoneyUsd;
use App\Domain\Models\Usage\YearMonth;
use App\Domain\Models\Usage\MonthlyUsage;
use App\Domain\Repositories\MonthlyUsageRepositoryInterface;
use Illuminate\Support\Facades\Config;

readonly class UsageService
{
    public function __construct(
        private MonthlyUsageRepositoryInterface $monthlyUsageRepository
    ) {}

    public function getMonthlySummaryBoth(int $userId, ?string $yearMonthString = null): array
    {
        $targetYearMonth = new YearMonth($yearMonthString ?? now()->format('Y-m'));
      
        $user =  $this->getUserMonthlySummary($userId, $targetYearMonth);
        $userAll = $this->getGlobalMonthlySummary($targetYearMonth);

        return [
            'user' => $user,
            'user_all' => $userAll,
        ];
    }

    /**
     * 任意ユーザーの指定年月のOpenAIの利用料金情報を返す
     */
    private function getUserMonthlySummary(int $userId, YearMonth $yearMonth): array
    {
        $usage = $this->monthlyUsageRepository->findByUserIdAndYearMonth(new UserId($userId), $yearMonth)
                ?? MonthlyUsage::zero(userId: new UserId($userId), yearMonth: $yearMonth);

        $limitUsd = new MoneyUsd((string) Config::get('chatgpt.token_limits.monthly_user_limit_usd', '0'));
        $remaining = bcsub($limitUsd->getValue(), $usage->estimatedCostUsd->getValue(), 5);
        $isOverLimit = bccomp($usage->estimatedCostUsd->getValue(), $limitUsd->getValue(), 5) === 1;

        return MonthlyUsageSummaryDto::fromMonthlyUsage($usage, $limitUsd, $remaining, $isOverLimit)->toArray();
    }

    /**
    * 全会員の指定年月のOpenAIの利用料金情報を返す
    */
    private function getGlobalMonthlySummary(YearMonth $ym): array
    {
        $sum = $this->monthlyUsageRepository->sumAllUsersByYearMonth($ym);

        // sumRowByYearMonth の戻り値から “全体用 MonthlyUsage” を作る（id は不要）
        $usage = MonthlyUsage::fromSumRow($sum, $ym); // factory追加するのが一番きれい

        $limitUsd = new MoneyUsd((string) Config::get('chatgpt.token_limits.monthly_global_limit_usd', '0'));

        $remaining = bcsub($limitUsd->getValue(), $usage->estimatedCostUsd->getValue(), 5);
        $isOverLimit = bccomp($usage->estimatedCostUsd->getValue(), $limitUsd->getValue(), 5) === 1;

        return MonthlyUsageSummaryDto::fromMonthlyUsage($usage, $limitUsd, $remaining, $isOverLimit)->toArray();
    }
}