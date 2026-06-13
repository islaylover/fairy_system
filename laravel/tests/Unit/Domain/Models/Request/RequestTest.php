<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\Request;

use App\Domain\Enums\RequestStatusEnum;
use App\Domain\Models\Request\Request;
use App\Domain\Models\Request\RequestConversationId;
use App\Domain\Models\Request\RequestModel;
use App\Domain\Models\Request\RequestSourceText;
use App\Domain\Models\Request\RequestStatus;
use App\Domain\Models\Request\RequestType;
use App\Domain\Models\User\UserId;
use Tests\TestCase;

class RequestTest extends TestCase
{
    public function test_can_create_request_entity(): void
    {
        $request = new Request(
            new UserId(1),
            new RequestConversationId(12),
            new RequestModel('gpt-4o'),
            new RequestType('summary'),
            new RequestSourceText('2026年5月31日時点でのアメリカ＆イスラエル VS イランの停戦合意がなかなか進まない原因は何？'),
            null,
            new RequestStatus(RequestStatusEnum::Pending),
            null,
            null,
            null,
            null
        );

        $this->assertNull($request->getId());
        $this->assertSame(1, $request->getUserId()->getValue());
        $this->assertSame(12, $request->getConversationId()->getValue());
        $this->assertSame('gpt-4o', $request->getModel()->getValue());
        $this->assertSame('summary', $request->getType()->getValue());
        $this->assertSame(
            '2026年5月31日時点でのアメリカ＆イスラエル VS イランの停戦合意がなかなか進まない原因は何？',
            $request->getSourceText()->getValue(),
        );
        $this->assertNull($request->getResultText());
        $this->assertSame(RequestStatusEnum::Pending->value, $request->getStatus()->getValue());
        $this->assertNull($request->getPromptToken());
        $this->assertNull($request->getCompletionToken());
        $this->assertNull($request->getTotalToken());
        $this->assertNull($request->getEstimatedCostUsd());
        $this->assertNull($request->getId());
    }
}
