<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Domain\Enums\BatchOpenAiRequestResultEnum;
use App\Domain\Enums\MessageRoleEnum;
use App\Domain\Enums\RequestStatusEnum;
use App\Domain\Enums\RequestTypeEnum;
use App\Models\User;
use App\Services\BatchOpenAiRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class BatchOpenAiRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    private BatchOpenAiRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['chatgpt.history_max_messages' => 6]);

        $this->service = app(BatchOpenAiRequestService::class);
    }

    // test app/Services/BatchOpenAiRequestService #claim()
    // Pendingのリクエストを取得し、Processingへ更新できることを確認する
    public function test_claim_returns_pending_request_and_updates_it_to_processing(): void
    {
        $requestId = $this->createRequest(sourceText: 'current user message');

        $requests = $this->service->claim(1);

        $this->assertSame($requestId, $requests[0]['request_id']);
        $this->assertSame('gpt-4o', $requests[0]['model']);
        $this->assertDatabaseHas('requests', [
            'id' => $requestId,
            'status' => RequestStatusEnum::Processing->value,
        ]);
    }

    // test app/Services/BatchOpenAiRequestService #claim()
    // Pendingのリクエストが存在しない場合、空配列を返すことを確認する
    public function test_claim_returns_empty_array_when_pending_request_does_not_exist(): void
    {
        $this->createRequest(status: RequestStatusEnum::Done);

        $requests = $this->service->claim(1);

        $this->assertSame([], $requests);
    }

    // test app/Services/BatchOpenAiRequestService #claim()
    // system prompt、過去の会話履歴、今回のユーザーメッセージをmessagesに組み立てることを確認する
    public function test_claim_builds_messages_with_system_prompt_history_and_current_user_message(): void
    {
        $user = User::factory()->create();

        $doneRequest = $this->createRequest(
            userId: $user->id,
            conversationId: 10,
            status: RequestStatusEnum::Done,
        );
        $this->createLog($doneRequest, MessageRoleEnum::User, 'previous user message');
        $this->createLog($doneRequest, MessageRoleEnum::Assistant, 'previous assistant message');

        $pendingRequest = $this->createRequest(
            userId: $user->id,
            conversationId: 10,
            sourceText: 'current user message',
        );

        $requests = $this->service->claim(1);

        $this->assertSame($pendingRequest, $requests[0]['request_id']);
        $this->assertSame([
            [
                'role' => MessageRoleEnum::System->value,
                'content' => config('chatgpt.request_type_prompts.summary.system_prompt'),
            ],
            [
                'role' => MessageRoleEnum::User->value,
                'content' => 'previous user message',
            ],
            [
                'role' => MessageRoleEnum::Assistant->value,
                'content' => 'previous assistant message',
            ],
            [
                'role' => MessageRoleEnum::User->value,
                'content' => 'current user message',
            ],
        ], $requests[0]['messages']);
    }

    // test app/Services/BatchOpenAiRequestService #claim()
    // 会話履歴は最新N件だけを取得し、OpenAIへ渡す順序は古い順になることを確認する
    public function test_claim_limits_history_to_latest_messages_and_returns_them_in_chronological_order(): void
    {
        config(['chatgpt.history_max_messages' => 3]);

        $user = User::factory()->create();
        $doneRequest = $this->createRequest(
            userId: $user->id,
            conversationId: 10,
            status: RequestStatusEnum::Done,
        );

        $this->createLog($doneRequest, MessageRoleEnum::User, 'history 1');
        $this->createLog($doneRequest, MessageRoleEnum::Assistant, 'history 2');
        $this->createLog($doneRequest, MessageRoleEnum::User, 'history 3');
        $this->createLog($doneRequest, MessageRoleEnum::Assistant, 'history 4');
        $this->createLog($doneRequest, MessageRoleEnum::User, 'history 5');

        $this->createRequest(
            userId: $user->id,
            conversationId: 10,
            sourceText: 'current user message',
        );

        $requests = $this->service->claim(1);

        $this->assertSame([
            [
                'role' => MessageRoleEnum::System->value,
                'content' => config('chatgpt.request_type_prompts.summary.system_prompt'),
            ],
            [
                'role' => MessageRoleEnum::User->value,
                'content' => 'history 3',
            ],
            [
                'role' => MessageRoleEnum::Assistant->value,
                'content' => 'history 4',
            ],
            [
                'role' => MessageRoleEnum::User->value,
                'content' => 'history 5',
            ],
            [
                'role' => MessageRoleEnum::User->value,
                'content' => 'current user message',
            ],
        ], $requests[0]['messages']);
    }

    // test app/Services/BatchOpenAiRequestService #claim()
    // 未定義request_typeはFailedへ更新し、有効なリクエストだけをclaim結果に含めることを確認する
    public function test_claim_marks_invalid_request_type_as_failed_and_returns_only_valid_requests(): void
    {
        $invalid = $this->createRequest(requestType: 'unknown_type');
        $valid = $this->createRequest(requestType: RequestTypeEnum::Summary);

        $requests = $this->service->claim(2);

        $this->assertCount(1, $requests);
        $this->assertSame($valid, $requests[0]['request_id']);
        $this->assertDatabaseHas('requests', [
            'id' => $invalid,
            'status' => RequestStatusEnum::Failed->value,
            'result_text' => 'Unknown request_type: unknown_type',
        ]);
        $this->assertDatabaseHas('requests', [
            'id' => $valid,
            'status' => RequestStatusEnum::Processing->value,
        ]);
    }

    // test app/Services/BatchOpenAiRequestService #complete()
    // OpenAI成功結果を保存し、request_logs・usage_ledgers・monthly_usagesを更新できることを確認する
    public function test_complete_saves_result_log_and_usage(): void
    {
        $requestId = $this->createRequest(status: RequestStatusEnum::Processing);

        $result = $this->service->complete(
            requestId: $requestId,
            resultText: 'OpenAI result text',
            promptTokens: 10,
            completionTokens: 20,
            totalTokens: 30,
        );

        $this->assertSame([
            'status' => BatchOpenAiRequestResultEnum::Completed->value,
            'usage_recorded' => true,
        ], $result);
        $this->assertDatabaseHas('requests', [
            'id' => $requestId,
            'status' => RequestStatusEnum::Done->value,
            'result_text' => 'OpenAI result text',
        ]);
        $this->assertDatabaseHas('request_logs', [
            'request_id' => $requestId,
            'role' => MessageRoleEnum::Assistant->value,
            'message' => 'OpenAI result text',
        ]);
        $this->assertDatabaseHas('usage_ledgers', [
            'request_id' => $requestId,
            'estimated_cost_usd' => '0.00023',
        ]);
        $this->assertDatabaseHas('monthly_usages', [
            'total_tokens' => 30,
            'estimated_cost_usd' => '0.00023',
            'requests_done_count' => 1,
        ]);
    }

    // test app/Services/BatchOpenAiRequestService #complete()
    // Done済みリクエストへの再送では利用量を二重計上しないことを確認する
    public function test_complete_is_idempotent_after_request_is_done(): void
    {
        $requestId = $this->createRequest(status: RequestStatusEnum::Processing);

        $this->service->complete($requestId, 'OpenAI result text', 10, 20, 30);
        $result = $this->service->complete($requestId, 'OpenAI result text', 10, 20, 30);

        $this->assertSame([
            'status' => BatchOpenAiRequestResultEnum::AlreadyCompleted->value,
            'usage_recorded' => false,
        ], $result);
        $this->assertSame(1, DB::table('usage_ledgers')->where('request_id', $requestId)->count());
        $this->assertDatabaseHas('monthly_usages', [
            'total_tokens' => 30,
            'estimated_cost_usd' => '0.00023',
            'requests_done_count' => 1,
        ]);
    }

    // test app/Services/BatchOpenAiRequestService #fail()
    // OpenAI失敗結果を保存し、request_logsへエラーを記録できることを確認する
    public function test_fail_saves_error_log_and_marks_request_failed(): void
    {
        $requestId = $this->createRequest(status: RequestStatusEnum::Processing);

        $result = $this->service->fail($requestId, 'OpenAI error text');
        $this->assertSame(['status' => BatchOpenAiRequestResultEnum::Failed->value], $result);
        $this->assertDatabaseHas('requests', [
            'id' => $requestId,
            'status' => RequestStatusEnum::Failed->value,
            'result_text' => 'OpenAI error text',
        ]);
        $this->assertDatabaseHas('request_logs', [
            'request_id' => $requestId,
            'role' => MessageRoleEnum::Assistant->value,
            'message' => 'OpenAI error text',
        ]);
        $this->assertDatabaseMissing('usage_ledgers', [
            'request_id' => $requestId,
        ]);
    }

    // requestsテーブルへテスト用リクエストを作成する
    private function createRequest(
        ?int $userId = null,
        int $conversationId = 1,
        RequestStatusEnum $status = RequestStatusEnum::Pending,
        string $model = 'gpt-4o',
        string|RequestTypeEnum $requestType = RequestTypeEnum::Summary,
        string $sourceText = 'source text',
    ): int {
        $userId ??= User::factory()->create()->id;

        return (int) DB::table('requests')->insertGetId([
            'user_id' => $userId,
            'conversation_id' => $conversationId,
            'model' => $model,
            'request_type' => $requestType instanceof RequestTypeEnum ? $requestType->value : $requestType,
            'source_text' => $sourceText,
            'status' => $status->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // request_logsテーブルへテスト用会話ログを作成する
    private function createLog(int $requestId, MessageRoleEnum $role, string $message): void
    {
        DB::table('request_logs')->insert([
            'request_id' => $requestId,
            'role' => $role->value,
            'message' => $message,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
