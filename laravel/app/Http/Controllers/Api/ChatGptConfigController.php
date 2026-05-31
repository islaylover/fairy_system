<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Enums\RequestStatusEnum;
use App\Domain\Enums\RequestTypeEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ChatGptConfigController extends Controller
{
    public function index(): JsonResponse
    {
        $cfg = config('chatgpt');

        // ChatGPT APIで使うmodel情報
        $models = collect($cfg['models'] ?? [])
            ->map(fn ($v, $key) => [
                'id' => $key,
                'name' => $v['label'] ?? $key,
            ])
            ->values();

        // ChatGPT APIで使うrequest type
        $requestTypes = collect(RequestTypeEnum::cases())
            ->map(fn (RequestTypeEnum $e) => [
                'id' => $e->value,
                'label' => $e->label(),
            ])->values();

        // ChatGPTへのリクエストのステータス
        $requestStatus = collect(RequestStatusEnum::cases())
            ->map(fn (RequestStatusEnum $e) => [
                'id' => $e->value,
                'label' => $e->label(),
                'is_finished' => $e->isFinished(),
            ])->values();

        return response()->json([
            'models' => $models,
            'request_types' => $requestTypes,
            'request_status' => $requestStatus,
        ]);
    }

    public function indexForBatch(): JsonResponse
    {
        $cfg = config('chatgpt');

        // ChatGPT APIで使うmodel情報
        $models = collect($cfg['models'] ?? [])
            ->map(fn ($v, $key) => [
                'id' => $key,
                'name' => $v['label'] ?? $key,
                'price_per_million_tokens' => $v['price_per_million_tokens'] ?? null,
            ])
            ->values();

        // ChatGPT APIで使うrequest type
        $prompts = $cfg['request_type_prompts'];
        $requestTypes = collect(RequestTypeEnum::cases())
            ->map(fn (RequestTypeEnum $e) => [
                'id' => $e->value,
                'label' => $e->label(),
                'prompt' => $prompts[$e->value]['system_prompt'] ?? null,
            ])->values();

        // CgatGPTへのリクエストのステータス
        $requestStatus = collect(RequestStatusEnum::cases())
            ->map(fn (RequestStatusEnum $e) => [
                'id' => $e->value,
                'label' => $e->label(),
                'is_finished' => $e->isFinished(),
            ])->values();

        $limits = $cfg['token_limits'] ?? [];

        return response()->json([
            'models' => $models,
            'request_types' => $requestTypes,
            'request_status' => $requestStatus,
            'token_limits' => [
                'daily_max_tokens' => (int) ($limits['daily_max_tokens'] ?? 0),
                'monthly_user_limit_usd' => (string) ($limits['monthly_user_limit_usd'] ?? '0'),
                'monthly_global_limit_usd' => (string) ($limits['monthly_global_limit_usd'] ?? '0'),
            ],
        ]);
    }
}
