<?php

declare(strict_types=1);

namespace App\Domain\Models\UsageLedgers;

use App\Domain\Models\Request\RequestId;
use App\Domain\Models\Usage\YearMonth;
use App\Domain\Models\User\UserId;

readonly class UsageLedgers
{
    public function __construct(
        public RequestId $requestId,
        public UserId $userId,
        public YearMonth $yearMonth,
        public PromptTokens $promptTokens,
        public CompletionTokens $completionTokens,
        public TotalTokens $totalTokens,
        public EstimatedCostUsd $estimatedCostUsd,
        public ?UsageLedgersId $id = null,
    ) {}

    public function getId(): ?UsageLedgersId
    {
        return $this->id;
    }

    public function getRequestId(): RequestId
    {
        return $this->requestId;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getYearMonth(): YearMonth
    {
        return $this->yearMonth;
    }

    public function getPromptTokens(): PromptTokens
    {
        return $this->promptTokens;
    }

    public function getCompletionTokens(): CompletionTokens
    {
        return $this->completionTokens;
    }

    public function getTotalTokens(): TotalTokens
    {
        return $this->totalTokens;
    }

    public function getEstimatedCostUsd(): EstimatedCostUsd
    {
        return $this->estimatedCostUsd;
    }
}
