<?php

declare(strict_types=1);

namespace App\Domain\Models\Request;

use App\Domain\Models\User\UserId;

readonly class Request {

    public function __construct(
        public UserId $userId,
        public RequestConversationId $conversationId,
        public RequestModel $model,
        public RequestType $requestType,
        public RequestSourceText $sourceText,
        public ?RequestResultText $resultText,
        public RequestStatus $status,
        public ?RequestPromptToken $promptTokens,
        public ?RequestCompletionToken $completionTokens,
        public ?RequestTotalToken $totalTokens,
        public ?RequestEstimatedCostUsd $estimatedCostUsd,
        public ?RequestId $id = null
    ) {}

    public function getId(): ?RequestId
    {
        return $this->id;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getConversationId(): RequestConversationId
    {
        return $this->conversationId;
    }

    public function getModel(): RequestModel
    {
        return $this->model;
    }

    public function getType(): RequestType
    {
        return $this->requestType;
    }

    public function getSourceText(): RequestSourceText
    {
        return $this->sourceText;
    }

    public function getResultText(): ?RequestResultText
    {
        return $this->resultText;
    }

    public function getStatus(): RequestStatus
    {
        return $this->status;
    }

    public function getPromptToken(): ?RequestPromptToken
    {
        return $this->promptTokens;
    }

    public function getCompletionToken(): ?RequestCompletionToken
    {
        return $this->completionTokens;
    }

    public function getTotalToken(): ?RequestTotalToken
    {
        return $this->totalTokens;
    }

    public function getEstimatedCostUsd(): ?RequestEstimatedCostUsd
    {
        return $this->estimatedCostUsd;
    }
}