<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Models\User\UserId;
use App\Domain\Models\Usage\YearMonth;
use App\Domain\Models\Usage\MonthlyUsage;
use App\Domain\Repositories\MonthlyUsageRepositoryInterface;
use App\Infrastructure\Eloquent\MonthlyUsageEloquent;
use App\Infrastructure\Mappers\MonthlyUsageMapper;

final class EloquentMonthlyUsageRepository implements MonthlyUsageRepositoryInterface
{
    public function findByUserIdAndYearMonth(UserId $userId, YearMonth $yearMonth): ?MonthlyUsage 
    {
        $eloquent = MonthlyUsageEloquent::query()
            ->where('user_id', $userId->getValue())
            ->where('year_month', $yearMonth->getValue())
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return MonthlyUsageMapper::toDomain($eloquent);
    }

    public function sumAllUsersByYearMonth(YearMonth $yearMonth): array
    {
        $row = MonthlyUsageEloquent::query()
            ->where('year_month', $yearMonth->getValue())
            ->selectRaw('
                COALESCE(SUM(prompt_tokens), 0) as prompt_tokens,
                COALESCE(SUM(completion_tokens), 0) as completion_tokens,
                COALESCE(SUM(total_tokens), 0) as total_tokens,
                COALESCE(SUM(estimated_cost_usd), 0) as estimated_cost_usd,
                COALESCE(SUM(requests_done_count), 0) as requests_done_count
            ')
            ->first();

        return [
            'year_month' => $yearMonth->getValue(),
            'prompt_tokens' => (string)($row->prompt_tokens ?? 0),
            'completion_tokens' => (string)($row->completion_tokens ?? 0),
            'total_tokens' => (string)($row->total_tokens ?? 0),
            'estimated_cost_usd' => (string)($row->estimated_cost_usd ?? '0.00000'),
            'requests_done_count' => (int)($row->requests_done_count ?? 0),
        ];
    }

}
