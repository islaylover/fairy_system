<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\RequestService;
use Symfony\Component\HttpFoundation\JsonResponse;

class RequestController extends Controller
{
    public function __construct(
        private readonly RequestService $requestService
    ) {}

    // Requests一覧
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 10);

        $userId = (int) $request->user()->id;

        $result = $this->requestService->getRequestsList($userId, $page, $perPage);

        return response()->json($result);
    }

    // Requestを新規登録
    public function store(Request $request): JsonResponse
    {
        // 不正なら ValidationException → Handlerで422
        $data = $request->validate([
            'model'          => ['required', 'string'],
            'request_type'   => ['required', 'string'],
            'source_text'    => ['required', 'string'],
            'conversation_id'=> ['nullable', 'integer'],
        ]);

        $userId = (int) $request->user()->id;

        // InvalidArgumentException / RuntimeException / Throwable は Handler に委譲
        $newRequest = $this->requestService->createNewRequest($userId, $data);

        return response()->json([
            'message' => 'リクストを登録しました。',
            'request' => [
                'id'           => $newRequest->getId()->getValue(),
                'model'        => $newRequest->getModel()->getValue(),
                'request_type' => $newRequest->getType()->getValue(),
                'status'       => $newRequest->getStatus()->getValue(),
            ],
        ], 200);
    }

    // Requestを更新
    public function update(Request $request, int $id): JsonResponse
    {
        // 不正なら ValidationException → Handlerで422
        $data = $request->validate([
            'model'        => ['required', 'string'],
            'conversation_id' => ['required', 'integer'],
            'request_type' => ['required', 'string'],
            'source_text'  => ['required', 'string'],
            'status'       => ['required', 'integer'],
        ]);

        $userId = (int) $request->user()->id;

        $updated = $this->requestService->updateRequest($userId, $id, $data);

        return response()->json([
            'message' => 'リクストを更新しました。',
            'request' => [
                'id'           => $updated->getId()->getValue(),
                'model'        => $updated->getModel()->getValue(),
                'request_type' => $updated->getType()->getValue(),
                'status'       => $updated->getStatus()->getValue(),
            ],
        ], 200);
    }

    // リクエスト削除
    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $this->requestService->deleteRequest($userId, $id);

        return response()->json([
            'message' => 'リクストを削除しました。',
        ], 200);
    }
}