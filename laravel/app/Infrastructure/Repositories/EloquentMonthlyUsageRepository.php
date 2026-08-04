<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Models\Usage\MonthlyUsage;
use App\Domain\Models\Usage\YearMonth;
use App\Domain\Models\User\UserId;
use App\Domain\Repositories\MonthlyUsageRepositoryInterface;
use App\Infrastructure\Eloquent\MonthlyUsageEloquent;
use App\Infrastructure\Mappers\MonthlyUsageMapper;
use Illuminate\Support\Facades\DB;

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
            'prompt_tokens' => (string) ($row->prompt_tokens ?? 0),
            'completion_tokens' => (string) ($row->completion_tokens ?? 0),
            'total_tokens' => (string) ($row->total_tokens ?? 0),
            'estimated_cost_usd' => (string) ($row->estimated_cost_usd ?? '0.00000'),
            'requests_done_count' => (int) ($row->requests_done_count ?? 0),
        ];
    }

    public function addUsage(
        UserId $userId,
        YearMonth $yearMonth,
        int $promptTokens,
        int $completionTokens,
        int $totalTokens,
        string $estimatedCostUsd
    ): void {
        MonthlyUsageEloquent::query()->upsert(
            [[
                'user_id' => $userId->getValue(),
                'year_month' => $yearMonth->getValue(),
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'estimated_cost_usd' => $estimatedCostUsd,
                'requests_done_count' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['user_id', 'year_month'],
            [
                'prompt_tokens' => DB::raw('prompt_tokens + VALUES(prompt_tokens)'),
                'completion_tokens' => DB::raw('completion_tokens + VALUES(completion_tokens)'),
                'total_tokens' => DB::raw('total_tokens + VALUES(total_tokens)'),
                'estimated_cost_usd' => DB::raw('estimated_cost_usd + VALUES(estimated_cost_usd)'),
                'requests_done_count' => DB::raw('requests_done_count + 1'),
                'updated_at' => DB::raw('VALUES(updated_at)'),
            ]
        );
    }
}
