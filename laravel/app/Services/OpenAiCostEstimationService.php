<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * OpenAI APIのtoken使用量から概算コスト(USD)を算出するService。
 *
 * 価格定義はLaravel configを正とし、Python batchから料金計算の責務を外す。
 */
final readonly class OpenAiCostEstimationService
{
    public function estimateUsd(
        string $model,
        int $promptTokens,
        int $completionTokens
    ): string {
        $pricing = config("chatgpt.models.{$model}.price_per_million_tokens");
        if (! is_array($pricing) || ! isset($pricing['input'], $pricing['output'])) {
            throw new InvalidArgumentException("OpenAI model pricing is not defined: {$model}");
        }

        $inputPrice = (float) $pricing['input'];
        $outputPrice = (float) $pricing['output'];

        $cost = ($promptTokens / 1_000_000) * $inputPrice
            + ($completionTokens / 1_000_000) * $outputPrice;

        return number_format($cost, 5, '.', '');
    }
}
