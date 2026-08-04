<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Models\UsageLedgers\UsageLedgers;
use App\Domain\Repositories\UsageLedgerRepositoryInterface;
use App\Infrastructure\Eloquent\UsageLedgerEloquent;
use Illuminate\Database\QueryException;

final class EloquentUsageLedgerRepository implements UsageLedgerRepositoryInterface
{
    public function createOnce(UsageLedgers $usageLedger): bool
    {
        try {
            UsageLedgerEloquent::query()->create([
                'request_id' => $usageLedger->getRequestId()->getValue(),
                'user_id' => $usageLedger->getUserId()->getValue(),
                'year_month' => $usageLedger->getYearMonth()->getValue(),
                'prompt_tokens' => $usageLedger->getPromptTokens()->getValue(),
                'completion_tokens' => $usageLedger->getCompletionTokens()->getValue(),
                'total_tokens' => $usageLedger->getTotalTokens()->getValue(),
                'estimated_cost_usd' => $usageLedger->getEstimatedCostUsd()->getValue(),
            ]);

            return true;
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return false;
            }

            throw $e;
        }
    }
}
