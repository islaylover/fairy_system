<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Domain\Exceptions\UsageLimitExceededException;
use App\Domain\Models\Usage\MoneyUsd;
use App\Domain\Models\Usage\YearMonth;

final class UsageLimitPolicyService
{
    /**
     * 制限超えなら UsageLimitExceededException を投げる
     *
     * ルール：
     * - limitUsd <= 0 は「無制限」
     * - usedUsd > limitUsd なら超過
     */
    public function assertWithinLimits(
        YearMonth $ym,
        MoneyUsd $userLimitUsd,
        MoneyUsd $userUsedUsd,
        MoneyUsd $globalLimitUsd,
        MoneyUsd $globalUsedUsd
    ): void {
        $this->assertOneScope('user', $ym, $userLimitUsd, $userUsedUsd);
        $this->assertOneScope('global', $ym, $globalLimitUsd, $globalUsedUsd);
    }

    private function assertOneScope(
        string $scope,   // 'user' | 'global'
        YearMonth $ym,
        MoneyUsd $limitUsd,
        MoneyUsd $usedUsd
    ): void {
        // limit <= 0 => 無制限
        if (bccomp($limitUsd->getValue(), '0', 5) !== 1) {
            return;
        }

        // used > limit ?
        if (bccomp($usedUsd->getValue(), $limitUsd->getValue(), 5) === 1) {
            $remaining = bcsub($limitUsd->getValue(), $usedUsd->getValue(), 5);

            throw new UsageLimitExceededException(
                scope: $scope,
                yearMonth: $ym->getValue(),
                limitUsd: $limitUsd->getValue(),
                usedUsd: $usedUsd->getValue(),
                remainingUsd: $remaining
            );
        }
    }
}
