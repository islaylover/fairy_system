<?php

declare(strict_types=1);

namespace App\Domain\Models\Request;

use App\Domain\Utility\Validator\DecimalValidator;

readonly class RequestEstimatedCostUsd
{
    public function __construct(
        private string $estimated_cost_usd
    ) {
        DecimalValidator::validate($estimated_cost_usd, [
            'label' => '想定コスト(米ドル)',
            'precision' => 8,
            'scale' => 5,
            'min' => 0,
            'max' => 999.99999,
        ]);
    }

    public function getValue(): string
    {
        return $this->estimated_cost_usd;
    }

    /**
     * 閾値超過判定
     */
    public function isGreaterThan(string $threshold): bool
    {
        // 小数5桁で比較
        return bccomp($this->estimated_cost_usd, $threshold, 5) === 1;
    }
}