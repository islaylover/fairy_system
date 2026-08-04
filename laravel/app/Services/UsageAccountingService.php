<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Models\Request\RequestId;
use App\Domain\Models\Usage\YearMonth;
use App\Domain\Models\UsageLedgers\CompletionTokens;
use App\Domain\Models\UsageLedgers\EstimatedCostUsd;
use App\Domain\Models\UsageLedgers\PromptTokens;
use App\Domain\Models\UsageLedgers\TotalTokens;
use App\Domain\Models\UsageLedgers\UsageLedgers;
use App\Domain\Models\User\UserId;
use App\Domain\Repositories\MonthlyUsageRepositoryInterface;
use App\Domain\Repositories\UsageLedgerRepositoryInterface;

/**
 * OpenAI利用量の会計処理を担当するService。
 *
 * `usage_ledgers`をリクエスト単位の正本として一度だけ作成し、作成できた場合だけ
 * `monthly_usages`へ加算することで、complete APIの再送による二重計上を防ぐ。
 */
final readonly class UsageAccountingService
{
    public function __construct(
        private UsageLedgerRepositoryInterface $usageLedgerRepository,
        private MonthlyUsageRepositoryInterface $monthlyUsageRepository
    ) {}

    /**
     * 完了済みリクエストの利用量を一度だけ記録し、初回記録時のみ月次利用量へ加算する。
     *
     * @return bool true=今回新規に計上 / false=既に計上済み
     */
    public function recordCompletedRequestUsage(
        int $requestId,
        int $userId,
        int $promptTokens,
        int $completionTokens,
        int $totalTokens,
        string $estimatedCostUsd,
        ?string $yearMonth = null
    ): bool {
        // APIから渡された数値・文字列をDomainの値オブジェクトへ変換し、値の妥当性を保証してから永続化する。
        $ym = new YearMonth($yearMonth ?? now()->format('Y-m'));
        $ledger = new UsageLedgers(
            requestId: new RequestId($requestId),
            userId: new UserId($userId),
            yearMonth: $ym,
            promptTokens: new PromptTokens($promptTokens),
            completionTokens: new CompletionTokens($completionTokens),
            totalTokens: new TotalTokens($totalTokens),
            estimatedCostUsd: new EstimatedCostUsd($estimatedCostUsd),
        );

        // request_idの一意制約により、同じrequestのusage ledgerは1回だけ作成される。
        $inserted = $this->usageLedgerRepository->createOnce($ledger);
        if (! $inserted) {
            return false;
        }

        // ledger作成に成功した初回だけ、月次集計へ加算する。
        $this->monthlyUsageRepository->addUsage(
            userId: new UserId($userId),
            yearMonth: $ym,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            totalTokens: $totalTokens,
            estimatedCostUsd: $estimatedCostUsd,
        );

        return true;
    }
}
