<?php

declare(strict_types=1);

namespace App\Infrastructure\Mappers;

use App\Domain\Models\Request\Request;
use App\Domain\Models\Request\RequestCompletionToken;
use App\Domain\Models\Request\RequestEstimatedCostUsd;
use App\Domain\Models\Request\RequestId;
use App\Domain\Models\Request\RequestModel;
use App\Domain\Models\Request\RequestPromptToken;
use App\Domain\Models\Request\RequestResultText;
use App\Domain\Models\Request\RequestSourceText;
use App\Domain\Models\Request\RequestStatus;
use App\Domain\Models\Request\RequestTotalToken;
use App\Domain\Models\Request\RequestType;
use App\Domain\Models\Request\RequestConversationId;
use App\Domain\Models\User\UserId;
use App\Infrastructure\Eloquent\RequestEloquent;

final class RequestMapper
{
    /**
     * Eloquent -> Domain
     */
    public static function toDomain(RequestEloquent $e): Request
    {
        return new Request(
            new UserId((int) $e->user_id),
            new RequestConversationId((int) $e->conversation_id),
            new RequestModel((string) $e->model),
            new RequestType((string) $e->request_type),
            new RequestSourceText((string) $e->source_text),
            $e->result_text !== null ? new RequestResultText((string) $e->result_text) : null,
            new RequestStatus((int) $e->status),
            $e->prompt_tokens !== null ? new RequestPromptToken((int) $e->prompt_tokens) : null,
            $e->completion_tokens !== null ? new RequestCompletionToken((int) $e->completion_tokens) : null,
            $e->total_tokens !== null ? new RequestTotalToken((int) $e->total_tokens) : null,
            $e->estimated_cost_usd !== null ? new RequestEstimatedCostUsd((string) $e->estimated_cost_usd) : null,
            new RequestId((int) $e->id),
        );
    }

    /**
     * Domain -> Eloquent
     */
    public static function fillEloquentFromDomain(RequestEloquent $e, Request $d): RequestEloquent
    {
        $e->user_id = $d->getUserId()->getValue();
        $e->conversation_id = $d->getConversationId()->getValue();
        $e->model = $d->getModel()->getValue();
        $e->request_type = $d->getType()->getValue();
        $e->source_text = $d->getSourceText()->getValue();
        $e->result_text = $d->getResultText()?->getValue();
        $e->status = $d->getStatus()->getValue();
        $e->prompt_tokens = $d->getPromptToken()?->getValue();
        $e->completion_tokens = $d->getCompletionToken()?->getValue();
        $e->total_tokens = $d->getTotalToken()?->getValue();
        $e->estimated_cost_usd = $d->getEstimatedCostUsd()?->getValue();

        return $e;
    }
}
