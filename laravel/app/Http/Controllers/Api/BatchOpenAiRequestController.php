<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Batch\ClaimOpenAiRequestsRequest;
use App\Http\Requests\Api\Batch\CompleteOpenAiRequestRequest;
use App\Http\Requests\Api\Batch\FailOpenAiRequestRequest;
use App\Services\BatchOpenAiRequestService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class BatchOpenAiRequestController extends Controller
{
    public function __construct(
        private readonly BatchOpenAiRequestService $batchOpenAiRequestService
    ) {}

    public function claim(ClaimOpenAiRequestsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return response()->json([
            'requests' => $this->batchOpenAiRequestService->claim((int) $validated['batch_size']),
        ]);
    }

    public function globalLimit(): JsonResponse
    {
        return response()->json($this->batchOpenAiRequestService->checkGlobalLimits());
    }

    public function requestLimit(int $id): JsonResponse
    {
        try {
            return response()->json($this->batchOpenAiRequestService->checkRequestLimits($id));
        } catch (HttpExceptionInterface $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function complete(CompleteOpenAiRequestRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            return response()->json($this->batchOpenAiRequestService->complete(
                requestId: $id,
                resultText: (string) $validated['result_text'],
                promptTokens: (int) $validated['prompt_tokens'],
                completionTokens: (int) $validated['completion_tokens'],
                totalTokens: (int) $validated['total_tokens'],
            ));
        } catch (HttpExceptionInterface $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function fail(FailOpenAiRequestRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            return response()->json($this->batchOpenAiRequestService->fail(
                requestId: $id,
                errorText: (string) $validated['error_text'],
            ));
        } catch (HttpExceptionInterface $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
    }
}
