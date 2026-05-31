<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Dto\RequestSummaryDto;
use App\Domain\Enums\RequestStatusEnum;
use App\Domain\Models\Request\Request;
use App\Domain\Models\Request\RequestConversationId;
use App\Domain\Models\Request\RequestId;
use App\Domain\Models\Request\RequestModel;
use App\Domain\Models\Request\RequestSourceText;
use App\Domain\Models\Request\RequestStatus;
use App\Domain\Models\Request\RequestType;
use App\Domain\Models\User\UserId;
use App\Domain\Repositories\ConversationLockInterface;
use App\Domain\Repositories\RequestRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

readonly class RequestService
{
    public function __construct(
        private RequestRepositoryInterface $requestRepository,
        private ConversationLockInterface $conversationLock,
        private readonly UsageLimitService $usageLimitService
    ) {}

    // ページング考慮でお願い事一覧取得
    public function getRequestsList(int $userId, int $page, int $perPage): array
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->requestRepository->getPaginateByUser($userId, $page, $perPage);

        $items = [];

        /** @var Request $request */
        foreach ($paginator->items() as $request) {
            $items[] = RequestSummaryDto::fromEntity($request)->toArray();
        }

        return [
            'requests' => $items,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    // リクストを登録する
    public function createNewRequest(int $userId, array $data): Request
    {
        // check if user or all usrs exceed limit amount
        $this->usageLimitService->assertCanCreateRequest($userId);

        $conversationId = isset($data['conversation_id']) ? (int) $data['conversation_id'] : null;

        // Case 02 既存のリクストに追加リクエスト[リクエスト＆OpenAIの回答後にさらに続けてリクエストするケース]
        if ($conversationId !== null) {
            // request data exists check
            if (! $this->requestRepository->existByUserIdAndConversationId($userId, $conversationId)) {
                throw new InvalidArgumentException('指定された会話IDが存在しないか、権限がありません。');
            }

            // request data's status check
            if (! $this->requestRepository->existsByUserIdConversationIdAndStatus(
                $userId,
                $conversationId,
                RequestStatusEnum::Done->value
            )) {
                throw new InvalidArgumentException('この会話はまだ'.RequestStatusEnum::Done->label().'していないため追加できません。');
            }

            $request = new Request(
                new UserId($userId),
                new RequestConversationId($conversationId),
                new RequestModel($data['model']),
                new RequestType($data['request_type']),
                new RequestSourceText($data['source_text']),
                null,
                new RequestStatus(RequestStatusEnum::Pending),
                null, null, null, null
            );

            return $this->requestRepository->create($request);
        }

        // Case 01 新規リクエスト
        return DB::transaction(function () use ($userId, $data) {

            return $this->conversationLock->withUserLock($userId, 5, function () use ($userId, $data) {

                // user_id 単位で max(conversation_id)+1取得
                $max = $this->requestRepository->getMaxConversationIdByUserId($userId);
                $nextConversationId = $max + 1;

                $request = new Request(
                    new UserId($userId),
                    new RequestConversationId($nextConversationId),
                    new RequestModel($data['model']),
                    new RequestType($data['request_type']),
                    new RequestSourceText($data['source_text']),
                    null,
                    new RequestStatus(RequestStatusEnum::Pending),
                    null,
                    null,
                    null,
                    null
                );

                return $this->requestRepository->create($request);
            });
        });
    }

    // リクストを編集する
    public function updateRequest(int $userId, int $requestId, array $data): Request
    {
        // check if user or all usrs exceed limit amount
        $this->usageLimitService->assertCanCreateRequest($userId);

        // check if request data exist
        $existing = $this->requestRepository->findById(new RequestId($requestId));
        if (! $existing) {
            throw new InvalidArgumentException('データが存在しません。');
        }

        // check if use has right to update data
        if ($existing->getUserId()->getValue() !== $userId) {
            throw new InvalidArgumentException('更新する権限がありません。');
        }

        $updatedRequest = new Request(
            new UserId($userId),
            new RequestConversationId((int) $data['conversation_id']),
            new RequestModel($data['model']),
            new RequestType($data['request_type']),
            new RequestSourceText($data['source_text']),
            $existing->getResultText(),
            new RequestStatus((int) $data['status']),
            $existing->getPromptToken(),
            $existing->getCompletionToken(),
            $existing->getTotalToken(),
            $existing->getEstimatedCostUsd(),
            new RequestId($requestId)
        );

        return $this->requestRepository->update($updatedRequest);
    }

    // リクエストを削除する
    public function deleteRequest(int $userId, int $requestId): void
    {

        // check if request data exist
        $existing = $this->requestRepository->findById(new RequestId($requestId));
        if (! $existing) {
            throw new InvalidArgumentException('データが存在しません。');
        }

        // check if use has right to update data
        if ($existing->getUserId()->getValue() !== $userId) {
            throw new InvalidArgumentException('更新する権限がありません。');
        }

        $this->requestRepository->delete(new RequestId($requestId));
    }
}
