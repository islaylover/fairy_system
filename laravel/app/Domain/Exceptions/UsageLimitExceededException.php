<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use RuntimeException;

final class UsageLimitExceededException extends RuntimeException
{
    public function __construct(
        private readonly string $scope,          // 'user' | 'global'
        private readonly string $yearMonth,       // 'YYYY-MM'
        private readonly string $limitUsd,        // string(decimal)
        private readonly string $usedUsd,         // string(decimal)
        private readonly string $remainingUsd     // string(decimal)
    ) {
        parent::__construct('今月のOpenAI利用上限に達したため、リクエストを登録できません。');
    }

    public function scope(): string
    {
        return $this->scope;
    }

    public function yearMonth(): string
    {
        return $this->yearMonth;
    }

    public function limitUsd(): string
    {
        return $this->limitUsd;
    }

    public function usedUsd(): string
    {
        return $this->usedUsd;
    }

    public function remainingUsd(): string
    {
        return $this->remainingUsd;
    }
}
