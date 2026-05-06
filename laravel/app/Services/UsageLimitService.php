<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Models\User\UserId;
use App\Domain\Models\Usage\MoneyUsd;
use App\Domain\Models\Usage\YearMonth;
use App\Domain\Models\Usage\MonthlyUsage;
use App\Domain\Services\UsageLimitPolicyService;
use App\Domain\Repositories\MonthlyUsageRepositoryInterface;
use Illuminate\Support\Facades\Config;

final readonly class UsageLimitService
{
    public function __construct(
        private MonthlyUsageRepositoryInterface $monthlyUsageRepository,
        private UsageLimitPolicyService $usageLimitPolicy
    ) {}

    /**
     * 当月の user / global の上限チェック（超過なら例外）
     */
    public function assertCanCreateRequest(int $userId, ?string $yearMonthString = null): void
    {
        $ym = new YearMonth($yearMonthString ?? now()->format('Y-m'));

        // ---- user usage（monthly_usages から）----
        $userUsage = $this->monthlyUsageRepository->findByUserIdAndYearMonth(new UserId($userId), $ym);
        if ($userUsage === null) {
            $userUsage = MonthlyUsage::zero(userId: new UserId($userId), yearMonth: $ym);
        }
        $userUsedUsd  = $userUsage->estimatedCostUsd;
        $userLimitUsd = new MoneyUsd((string) Config::get('chatgpt.token_limits.monthly_user_limit_usd', '0'));

        // ---- global usage（monthly_usages の当月SUM）----
        $sum = $this->monthlyUsageRepository->sumAllUsersByYearMonth($ym);
        $globalUsage = MonthlyUsage::fromSumRow($sum, $ym);

        $globalUsedUsd  = $globalUsage->estimatedCostUsd;
        $globalLimitUsd = new MoneyUsd((string) Config::get('chatgpt.token_limits.monthly_global_limit_usd', '0'));

        // ---- Domain Policy に判定させる（超過なら例外）----
        $this->usageLimitPolicy->assertWithinLimits(
            ym: $ym,
            userLimitUsd: $userLimitUsd,
            userUsedUsd: $userUsedUsd,
            globalLimitUsd: $globalLimitUsd,
            globalUsedUsd: $globalUsedUsd
        );
    }
}
