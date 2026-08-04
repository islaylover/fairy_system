<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\OpenAiCostEstimationService;
use InvalidArgumentException;
use Tests\TestCase;

final class OpenAiCostEstimationServiceTest extends TestCase
{
    // test app/Services/OpenAiCostEstimationService #estimateUsd()
    // config/chatgpt.phpの価格定義から、token利用量に応じたUSD概算コストを算出できることを確認する
    public function test_estimates_cost_from_model_pricing(): void
    {
        config([
            'chatgpt.models.test-model.price_per_million_tokens' => [
                'input' => 2.5,
                'output' => 10.0,
            ],
        ]);

        $service = app(OpenAiCostEstimationService::class);

        $this->assertSame('0.00187', $service->estimateUsd('test-model', 317, 108));
        $this->assertSame('0.00194', $service->estimateUsd('test-model', 182, 148));
    }

    // test app/Services/OpenAiCostEstimationService #estimateUsd()
    // 価格定義がないmodelでは、DBへ誤ったコストを保存しないよう例外にする
    public function test_throws_exception_when_model_pricing_is_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(OpenAiCostEstimationService::class)->estimateUsd('missing-model', 10, 20);
    }
}
