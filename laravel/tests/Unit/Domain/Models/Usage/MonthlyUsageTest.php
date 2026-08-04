<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\Usage;

use App\Domain\Models\Usage\MonthlyUsage;
use App\Domain\Models\Usage\YearMonth;
use App\Domain\Models\User\UserId;
use Tests\TestCase;

class MonthlyUsageTest extends TestCase
{
    public function test_can_create_zero_monthly_usage_for_user(): void
    {
        $usage = MonthlyUsage::zero(new UserId(1), new YearMonth('2026-08'));

        $this->assertSame(1, $usage->userId?->getValue());
        $this->assertSame('2026-08', $usage->yearMonth->getValue());
        $this->assertSame(0, $usage->totalTokens);
        $this->assertSame('0.00000', $usage->estimatedCostUsd->getValue());
    }

    public function test_can_create_global_monthly_usage_from_sum_row_without_user_id(): void
    {
        $usage = MonthlyUsage::fromSumRow([
            'prompt_tokens' => 10,
            'completion_tokens' => 20,
            'total_tokens' => 30,
            'estimated_cost_usd' => '0.12345',
            'requests_done_count' => 2,
        ], new YearMonth('2026-08'));

        $this->assertNull($usage->userId);
        $this->assertSame('2026-08', $usage->yearMonth->getValue());
        $this->assertSame(10, $usage->promptTokens);
        $this->assertSame(20, $usage->completionTokens);
        $this->assertSame(30, $usage->totalTokens);
        $this->assertSame('0.12345', $usage->estimatedCostUsd->getValue());
        $this->assertSame(2, $usage->requestsDoneCount);
    }
}
