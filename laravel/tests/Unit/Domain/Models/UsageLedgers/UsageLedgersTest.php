<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\UsageLedgers;

use App\Domain\Models\Request\RequestId;
use App\Domain\Models\Usage\YearMonth;
use App\Domain\Models\UsageLedgers\CompletionTokens;
use App\Domain\Models\UsageLedgers\EstimatedCostUsd;
use App\Domain\Models\UsageLedgers\PromptTokens;
use App\Domain\Models\UsageLedgers\TotalTokens;
use App\Domain\Models\UsageLedgers\UsageLedgers;
use App\Domain\Models\UsageLedgers\UsageLedgersId;
use App\Domain\Models\User\UserId;
use Tests\TestCase;

class UsageLedgersTest extends TestCase
{
    public function test_can_create_usage_ledgers_entity(): void
    {
        $usageLedger = new UsageLedgers(
            requestId: new RequestId(10),
            userId: new UserId(20),
            yearMonth: new YearMonth('2026-08'),
            promptTokens: new PromptTokens(100),
            completionTokens: new CompletionTokens(50),
            totalTokens: new TotalTokens(150),
            estimatedCostUsd: new EstimatedCostUsd('0.01234'),
            id: new UsageLedgersId(1),
        );

        $this->assertSame(1, $usageLedger->getId()?->getValue());
        $this->assertSame(10, $usageLedger->getRequestId()->getValue());
        $this->assertSame(20, $usageLedger->getUserId()->getValue());
        $this->assertSame('2026-08', $usageLedger->getYearMonth()->getValue());
        $this->assertSame(100, $usageLedger->getPromptTokens()->getValue());
        $this->assertSame(50, $usageLedger->getCompletionTokens()->getValue());
        $this->assertSame(150, $usageLedger->getTotalTokens()->getValue());
        $this->assertSame('0.01234', $usageLedger->getEstimatedCostUsd()->getValue());
    }

    public function test_can_create_usage_ledgers_entity_without_id(): void
    {
        $usageLedger = new UsageLedgers(
            requestId: new RequestId(10),
            userId: new UserId(20),
            yearMonth: new YearMonth('2026-08'),
            promptTokens: new PromptTokens(100),
            completionTokens: new CompletionTokens(50),
            totalTokens: new TotalTokens(150),
            estimatedCostUsd: new EstimatedCostUsd('0.01234'),
        );

        $this->assertNull($usageLedger->getId());
    }
}
