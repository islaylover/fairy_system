<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Domain\Enums\MessageRoleEnum;
use App\Domain\Enums\RequestStatusEnum;
use App\Domain\Enums\RequestTypeEnum;
use App\Models\User;
use App\Services\RequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class RequestServiceTest extends TestCase
{
    use RefreshDatabase;

    private RequestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RequestService::class);
    }

    // test app/Services/RequestService #createNewRequest()
    // 新規リクエスト作成時に、今回のユーザー入力をrequest_logsへ保存することを確認する
    public function test_create_new_request_saves_user_message_log(): void
    {
        $user = User::factory()->create();

        $request = $this->service->createNewRequest($user->id, [
            'model' => 'gpt-4o',
            'request_type' => RequestTypeEnum::Summary->value,
            'source_text' => 'first user message',
        ]);

        $this->assertDatabaseHas('request_logs', [
            'request_id' => $request->getId()?->getValue(),
            'role' => MessageRoleEnum::User->value,
            'message' => 'first user message',
        ]);
    }

    // test app/Services/RequestService #createNewRequest()
    // 既存会話への追加リクエストでも、追加分のユーザー入力をrequest_logsへ保存することを確認する
    public function test_create_additional_request_saves_user_message_log(): void
    {
        $user = User::factory()->create();
        $conversationId = 3;

        DB::table('requests')->insert([
            'user_id' => $user->id,
            'conversation_id' => $conversationId,
            'model' => 'gpt-4o',
            'request_type' => RequestTypeEnum::Summary->value,
            'source_text' => 'previous user message',
            'result_text' => 'previous assistant message',
            'status' => RequestStatusEnum::Done->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = $this->service->createNewRequest($user->id, [
            'model' => 'gpt-4o',
            'request_type' => RequestTypeEnum::Summary->value,
            'source_text' => 'next user message',
            'conversation_id' => $conversationId,
        ]);

        $this->assertDatabaseHas('request_logs', [
            'request_id' => $request->getId()?->getValue(),
            'role' => MessageRoleEnum::User->value,
            'message' => 'next user message',
        ]);
    }
}
