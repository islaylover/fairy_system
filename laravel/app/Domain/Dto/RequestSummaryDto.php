<?php

declare(strict_types=1);

namespace App\Domain\Dto;

use App\Domain\Models\Request\Request;

final class RequestSummaryDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $conversationId,
        public readonly string $model,
        public readonly string $requestType,
        public readonly int $status,
        public readonly string $sourceText,
        public readonly ?string $resultText,
        public readonly ?int $promptTokens,
        public readonly ?int $completionTokens,
        public readonly ?int $totalTokens,
        public readonly ?string $estimatedCostUsd,
    ) {}

    /**
     * Entity -> API返却形式へ変換
     */
    public static function fromEntity(Request $request): self
    {
        return new self(
            id: $request->getId()->getValue(),
            userId: $request->getUserId()->getValue(),
            conversationId: $request->getConversationId()->getValue(),
            model: $request->getModel()->getValue(),
            requestType: $request->getType()->getValue(),
            status: $request->getStatus()->getValue(),
            sourceText: $request->getSourceText()->getValue(),
            resultText: $request->getResultText()?->getValue(),
            promptTokens: $request->getPromptToken()?->getValue(),
            completionTokens: $request->getCompletionToken()?->getValue(),
            totalTokens: $request->getTotalToken()?->getValue(),
            estimatedCostUsd: $request->getEstimatedCostUsd()?->getValue(),
        );
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'conversation_id' => $this->conversationId,
            'model' => $this->model,
            'request_type' => $this->requestType,
            'source_text' => $this->sourceText,
            'result_text' => $this->resultText,
            'status' => $this->status,
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens' => $this->totalTokens,
            'estimated_cost_usd' => $this->estimatedCostUsd,
        ];
    }
}
