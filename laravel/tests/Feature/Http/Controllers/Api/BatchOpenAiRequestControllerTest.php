<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Domain\Enums\BatchOpenAiRequestResultEnum;
use App\Domain\Enums\MessageRoleEnum;
use App\Domain\Enums\RequestStatusEnum;
use App\Domain\Enums\RequestTypeEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class BatchOpenAiRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    private const BATCH_KEY = 'test-batch-key';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.batch_api_key' => self::BATCH_KEY]);
        config(['chatgpt.history_max_messages' => 6]);
    }

    public function test_can_claim_when_batch_api_key_is_valid(): void
    {
        $requestId = $this->createRequest();

        $response = $this->claim();

        $response
            ->assertOk()
            ->assertJsonPath('requests.0.request_id', $requestId)
            ->assertJsonPath('requests.0.model', 'gpt-4o');
    }

    public function test_rejects_when_batch_api_key_is_invalid(): void
    {
        $this->createRequest();

        $response = $this->postJson('/api/batch/openai-requests/claim', [
            'batch_size' => 1,
        ], [
            'X-BATCH-KEY' => 'wrong-key',
        ]);

        $response->assertUnauthorized();
    }

    public function test_claim_updates_pending_to_processing(): void
    {
        $requestId = $this->createRequest();

        $this->claim()->assertOk();

        $this->assertDatabaseHas('requests', [
            'id' => $requestId,
            'status' => RequestStatusEnum::Processing->value,
        ]);
    }

    public function test_claims_requests_in_id_ascending_order(): void
    {
        $third = $this->createRequest();
        $first = $this->createRequest();
        $second = $this->createRequest();

        DB::table('requests')->where('id', $third)->update(['id' => 30]);
        DB::table('requests')->where('id', $first)->update(['id' => 10]);
        DB::table('requests')->where('id', $second)->update(['id' => 20]);

        $response = $this->claim(3);

        $response->assertJsonPath('requests.0.request_id', 10);
        $response->assertJsonPath('requests.1.request_id', 20);
        $response->assertJsonPath('requests.2.request_id', 30);
    }

    public function test_claims_up_to_batch_size(): void
    {
        $this->createRequest();
        $this->createRequest();
        $this->createRequest();

        $response = $this->claim(2);

        $response->assertOk();
        $this->assertCount(2, $response->json('requests'));
    }

    public function test_returns_empty_array_when_pending_request_does_not_exist(): void
    {
        $this->createRequest(status: RequestStatusEnum::Done);

        $response = $this->claim();

        $response
            ->assertOk()
            ->assertExactJson(['requests' => []]);
    }

    public function test_second_claim_does_not_return_first_claimed_request(): void
    {
        $first = $this->createRequest();
        $second = $this->createRequest();

        $firstResponse = $this->claim(1);
        $secondResponse = $this->claim(1);

        $firstResponse->assertJsonPath('requests.0.request_id', $first);
        $secondResponse->assertJsonPath('requests.0.request_id', $second);
    }

    public function test_messages_include_system_prompt_and_conversation_history_in_order(): void
    {
        $user = User::factory()->create();

        $doneRequest = $this->createRequest(
            userId: $user->id,
            conversationId: 77,
            status: RequestStatusEnum::Done,
            sourceText: 'old source'
        );
        $this->createLog($doneRequest, MessageRoleEnum::User, 'previous user message');
        $this->createLog($doneRequest, MessageRoleEnum::Assistant, 'previous assistant message');

        $pendingRequest = $this->createRequest(
            userId: $user->id,
            conversationId: 77,
            sourceText: 'current user message'
        );

        $response = $this->claim();

        $expectedSystemPrompt = config('chatgpt.request_type_prompts.summary.system_prompt');

        $response->assertJsonPath('requests.0.request_id', $pendingRequest);
        $this->assertSame([
            ['role' => MessageRoleEnum::System->value, 'content' => $expectedSystemPrompt],
            ['role' => MessageRoleEnum::User->value, 'content' => 'previous user message'],
            ['role' => MessageRoleEnum::Assistant->value, 'content' => 'previous assistant message'],
            ['role' => MessageRoleEnum::User->value, 'content' => 'current user message'],
        ], $response->json('requests.0.messages'));
    }

    public function test_batch_size_is_required(): void
    {
        $response = $this->postJson('/api/batch/openai-requests/claim', [], [
            'X-BATCH-KEY' => self::BATCH_KEY,
        ]);

        $response->assertUnprocessable();
    }

    public function test_batch_size_must_be_at_least_one(): void
    {
        $this->claim(0)->assertUnprocessable();
    }

    public function test_batch_size_must_be_at_most_one_hundred(): void
    {
        $this->claim(101)->assertUnprocessable();
    }

    public function test_batch_size_must_be_an_integer(): void
    {
        $this->claim('1')->assertUnprocessable();
    }

    public function test_logs_from_other_conversation_are_not_included(): void
    {
        $user = User::factory()->create();

        $included = $this->createRequest(userId: $user->id, conversationId: 10, status: RequestStatusEnum::Done);
        $this->createLog($included, MessageRoleEnum::User, 'included history');

        $otherConversation = $this->createRequest(userId: $user->id, conversationId: 20, status: RequestStatusEnum::Done);
        $this->createLog($otherConversation, MessageRoleEnum::User, 'other conversation history');

        $current = $this->createRequest(userId: $user->id, conversationId: 10, sourceText: 'current user message');

        $messages = $this->messagesForClaimedRequest($this->claim(2)->json('requests'), $current);

        $this->assertContainsMessageContent('included history', $messages);
        $this->assertNotContainsMessageContent('other conversation history', $messages);
    }

    public function test_logs_from_other_user_are_not_included(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $included = $this->createRequest(userId: $user->id, conversationId: 10, status: RequestStatusEnum::Done);
        $this->createLog($included, MessageRoleEnum::User, 'included history');

        $otherUserRequest = $this->createRequest(userId: $otherUser->id, conversationId: 10, status: RequestStatusEnum::Done);
        $this->createLog($otherUserRequest, MessageRoleEnum::User, 'other user history');

        $this->createRequest(userId: $user->id, conversationId: 10, sourceText: 'current user message');

        $messages = $this->claim()->json('requests.0.messages');

        $this->assertContainsMessageContent('included history', $messages);
        $this->assertNotContainsMessageContent('other user history', $messages);
    }

    public function test_logs_from_pending_processing_and_failed_requests_are_not_included(): void
    {
        $user = User::factory()->create();

        $done = $this->createRequest(userId: $user->id, conversationId: 10, status: RequestStatusEnum::Done);
        $this->createLog($done, MessageRoleEnum::User, 'done history');

        $pending = $this->createRequest(userId: $user->id, conversationId: 10, status: RequestStatusEnum::Pending);
        $this->createLog($pending, MessageRoleEnum::User, 'pending history');

        $processing = $this->createRequest(userId: $user->id, conversationId: 10, status: RequestStatusEnum::Processing);
        $this->createLog($processing, MessageRoleEnum::User, 'processing history');

        $failed = $this->createRequest(userId: $user->id, conversationId: 10, status: RequestStatusEnum::Failed);
        $this->createLog($failed, MessageRoleEnum::User, 'failed history');

        $this->createRequest(userId: $user->id, conversationId: 10, sourceText: 'current user message');

        $messages = $this->claim()->json('requests.0.messages');

        $this->assertContainsMessageContent('done history', $messages);
        $this->assertNotContainsMessageContent('pending history', $messages);
        $this->assertNotContainsMessageContent('processing history', $messages);
        $this->assertNotContainsMessageContent('failed history', $messages);
    }

    public function test_current_user_message_is_included_only_once(): void
    {
        $user = User::factory()->create();
        $currentMessage = 'current user message';

        $done = $this->createRequest(userId: $user->id, conversationId: 10, status: RequestStatusEnum::Done);
        $this->createLog($done, MessageRoleEnum::User, 'previous history');

        $current = $this->createRequest(userId: $user->id, conversationId: 10, sourceText: $currentMessage);
        $this->createLog($current, MessageRoleEnum::User, $currentMessage);

        $messages = $this->claim()->json('requests.0.messages');

        $this->assertSame(1, $this->countMessageContent($currentMessage, $messages));
    }

    public function test_history_is_limited_to_latest_messages_and_returned_in_chronological_order(): void
    {
        config(['chatgpt.history_max_messages' => 3]);

        $user = User::factory()->create();
        $done = $this->createRequest(userId: $user->id, conversationId: 10, status: RequestStatusEnum::Done);

        $this->createLog($done, MessageRoleEnum::User, 'history 1');
        $this->createLog($done, MessageRoleEnum::Assistant, 'history 2');
        $this->createLog($done, MessageRoleEnum::User, 'history 3');
        $this->createLog($done, MessageRoleEnum::Assistant, 'history 4');
        $this->createLog($done, MessageRoleEnum::User, 'history 5');

        $this->createRequest(userId: $user->id, conversationId: 10, sourceText: 'current user message');

        $messages = $this->claim()->json('requests.0.messages');

        $this->assertSame([
            ['role' => MessageRoleEnum::System->value, 'content' => config('chatgpt.request_type_prompts.summary.system_prompt')],
            ['role' => MessageRoleEnum::User->value, 'content' => 'history 3'],
            ['role' => MessageRoleEnum::Assistant->value, 'content' => 'history 4'],
            ['role' => MessageRoleEnum::User->value, 'content' => 'history 5'],
            ['role' => MessageRoleEnum::User->value, 'content' => 'current user message'],
        ], $messages);
    }

    public function test_system_prompt_is_included_once_at_the_beginning(): void
    {
        $done = $this->createRequest(status: RequestStatusEnum::Done);
        $this->createLog($done, MessageRoleEnum::User, 'previous user message');

        $this->createRequest(sourceText: 'current user message');

        $messages = $this->claim()->json('requests.0.messages');

        $this->assertSame(MessageRoleEnum::System->value, $messages[0]['role']);
        $this->assertSame(1, $this->countMessageRole(MessageRoleEnum::System, $messages));
    }

    public function test_invalid_request_type_is_failed_and_valid_request_is_claimed(): void
    {
        $invalid = $this->createRequest(requestType: 'unknown_type');
        $valid = $this->createRequest(requestType: RequestTypeEnum::Summary);

        $response = $this->claim(2);

        $response
            ->assertOk()
            ->assertJsonPath('requests.0.request_id', $valid);

        $this->assertCount(1, $response->json('requests'));
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

    public function test_returns_empty_array_when_only_invalid_request_type_is_claimed_and_marks_it_failed(): void
    {
        $invalid = $this->createRequest(requestType: 'unknown_type');

        $response = $this->claim();

        $response
            ->assertOk()
            ->assertExactJson(['requests' => []]);

        $this->assertDatabaseHas('requests', [
            'id' => $invalid,
            'status' => RequestStatusEnum::Failed->value,
            'result_text' => 'Unknown request_type: unknown_type',
        ]);
    }

    public function test_failed_invalid_request_type_is_not_claimed_again(): void
    {
        $invalid = $this->createRequest(requestType: 'unknown_type');
        $valid = $this->createRequest(requestType: RequestTypeEnum::Summary);

        $this->claim(1)
            ->assertOk()
            ->assertExactJson(['requests' => []]);

        $secondResponse = $this->claim(2);

        $secondResponse
            ->assertOk()
            ->assertJsonPath('requests.0.request_id', $valid);

        $this->assertDatabaseHas('requests', [
            'id' => $invalid,
            'status' => RequestStatusEnum::Failed->value,
        ]);
    }

    public function test_invalid_request_type_does_not_rollback_entire_transaction(): void
    {
        $invalid = $this->createRequest(requestType: 'unknown_type');
        $valid = $this->createRequest(requestType: RequestTypeEnum::Summary);

        $response = $this->claim(2);

        $response
            ->assertOk()
            ->assertJsonPath('requests.0.request_id', $valid);

        $this->assertDatabaseHas('requests', [
            'id' => $invalid,
            'status' => RequestStatusEnum::Failed->value,
        ]);
        $this->assertDatabaseHas('requests', [
            'id' => $valid,
            'status' => RequestStatusEnum::Processing->value,
        ]);
    }

    public function test_complete_saves_openai_success_result_and_usage(): void
    {
        $requestId = $this->createRequest(status: RequestStatusEnum::Processing);

        $response = $this->complete($requestId);

        $response
            ->assertOk()
            ->assertJson([
                'status' => BatchOpenAiRequestResultEnum::Completed->value,
                'usage_recorded' => true,
            ]);

        $this->assertDatabaseHas('requests', [
            'id' => $requestId,
            'status' => RequestStatusEnum::Done->value,
            'result_text' => 'OpenAI result text',
            'prompt_tokens' => 10,
            'completion_tokens' => 20,
            'total_tokens' => 30,
            'estimated_cost_usd' => '0.00023',
        ]);
        $this->assertDatabaseHas('request_logs', [
            'request_id' => $requestId,
            'role' => MessageRoleEnum::Assistant->value,
            'message' => 'OpenAI result text',
        ]);
        $this->assertDatabaseHas('usage_ledgers', [
            'request_id' => $requestId,
            'prompt_tokens' => 10,
            'completion_tokens' => 20,
            'total_tokens' => 30,
            'estimated_cost_usd' => '0.00023',
        ]);
        $this->assertDatabaseHas('monthly_usages', [
            'prompt_tokens' => 10,
            'completion_tokens' => 20,
            'total_tokens' => 30,
            'estimated_cost_usd' => '0.00023',
            'requests_done_count' => 1,
        ]);
    }

    public function test_complete_does_not_count_usage_twice_when_retried_after_done(): void
    {
        $requestId = $this->createRequest(status: RequestStatusEnum::Processing);

        $this->complete($requestId)->assertOk();
        $secondResponse = $this->complete($requestId);

        $secondResponse
            ->assertOk()
            ->assertJson([
                'status' => BatchOpenAiRequestResultEnum::AlreadyCompleted->value,
                'usage_recorded' => false,
            ]);

        $this->assertSame(1, DB::table('usage_ledgers')->where('request_id', $requestId)->count());
        $this->assertDatabaseHas('monthly_usages', [
            'prompt_tokens' => 10,
            'completion_tokens' => 20,
            'total_tokens' => 30,
            'estimated_cost_usd' => '0.00023',
            'requests_done_count' => 1,
        ]);
    }

    public function test_complete_returns_conflict_when_request_is_not_processing_or_done(): void
    {
        $requestId = $this->createRequest(status: RequestStatusEnum::Pending);

        $this->complete($requestId)->assertStatus(409);
    }

    public function test_fail_saves_openai_failure_result(): void
    {
        $requestId = $this->createRequest(status: RequestStatusEnum::Processing);

        $response = $this->failRequest($requestId);

        $response
            ->assertOk()
            ->assertJson(['status' => BatchOpenAiRequestResultEnum::Failed->value]);

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

    public function test_fail_is_idempotent_when_request_is_already_failed(): void
    {
        $requestId = $this->createRequest(status: RequestStatusEnum::Processing);

        $this->failRequest($requestId)->assertOk();
        $secondResponse = $this->failRequest($requestId);

        $secondResponse
            ->assertOk()
            ->assertJson(['status' => BatchOpenAiRequestResultEnum::AlreadyFailed->value]);
    }

    public function test_global_limit_allows_when_limits_are_not_exceeded(): void
    {
        config([
            'chatgpt.token_limits.daily_max_tokens' => 100,
            'chatgpt.token_limits.monthly_global_limit_usd' => '1.00000',
        ]);

        $this->createRequest(status: RequestStatusEnum::Done);

        $response = $this->globalLimit();

        $response
            ->assertOk()
            ->assertJson([
                'allowed' => true,
                'scope' => null,
                'message' => null,
            ]);
    }

    public function test_global_limit_denies_when_daily_tokens_are_exceeded(): void
    {
        config(['chatgpt.token_limits.daily_max_tokens' => 30]);

        $this->createRequest(status: RequestStatusEnum::Done);
        DB::table('requests')->update([
            'total_tokens' => 30,
            'updated_at' => now(),
        ]);

        $response = $this->globalLimit();

        $response
            ->assertOk()
            ->assertJsonPath('allowed', false)
            ->assertJsonPath('scope', 'daily');
        $this->assertStringContainsString('USAGE_LIMIT_EXCEEDED scope=daily', $response->json('message'));
    }

    public function test_global_limit_denies_when_monthly_global_cost_is_exceeded(): void
    {
        config([
            'chatgpt.token_limits.daily_max_tokens' => 0,
            'chatgpt.token_limits.monthly_global_limit_usd' => '1.00000',
        ]);

        $this->createMonthlyUsage(User::factory()->create()->id, '1.00000');

        $response = $this->globalLimit();

        $response
            ->assertOk()
            ->assertJsonPath('allowed', false)
            ->assertJsonPath('scope', 'global');
        $this->assertStringContainsString('USAGE_LIMIT_EXCEEDED scope=global', $response->json('message'));
    }

    public function test_request_limit_denies_when_monthly_user_cost_is_exceeded(): void
    {
        config(['chatgpt.token_limits.monthly_user_limit_usd' => '1.00000']);

        $user = User::factory()->create();
        $requestId = $this->createRequest(userId: $user->id, status: RequestStatusEnum::Processing);
        $this->createMonthlyUsage($user->id, '1.00000');

        $response = $this->requestLimit($requestId);

        $response
            ->assertOk()
            ->assertJsonPath('allowed', false)
            ->assertJsonPath('scope', 'user');
        $this->assertStringContainsString('USAGE_LIMIT_EXCEEDED scope=user', $response->json('message'));
    }

    public function test_request_limit_returns_not_found_when_request_does_not_exist(): void
    {
        $this->requestLimit(999999)->assertNotFound();
    }

    private function claim($batchSize = 1)
    {
        return $this->postJson('/api/batch/openai-requests/claim', [
            'batch_size' => $batchSize,
        ], [
            'X-BATCH-KEY' => self::BATCH_KEY,
        ]);
    }

    private function complete(int $requestId, array $payload = [])
    {
        return $this->postJson(
            "/api/batch/openai-requests/{$requestId}/complete",
            array_merge([
                'result_text' => 'OpenAI result text',
                'prompt_tokens' => 10,
                'completion_tokens' => 20,
                'total_tokens' => 30,
            ], $payload),
            ['X-BATCH-KEY' => self::BATCH_KEY]
        );
    }

    private function failRequest(int $requestId, array $payload = [])
    {
        return $this->postJson(
            "/api/batch/openai-requests/{$requestId}/fail",
            array_merge([
                'error_text' => 'OpenAI error text',
            ], $payload),
            ['X-BATCH-KEY' => self::BATCH_KEY]
        );
    }

    private function globalLimit()
    {
        return $this->getJson('/api/batch/openai-requests/limits/global', [
            'X-BATCH-KEY' => self::BATCH_KEY,
        ]);
    }

    private function requestLimit(int $requestId)
    {
        return $this->getJson("/api/batch/openai-requests/{$requestId}/limits/request", [
            'X-BATCH-KEY' => self::BATCH_KEY,
        ]);
    }

    private function createRequest(
        ?int $userId = null,
        int $conversationId = 1,
        RequestStatusEnum $status = RequestStatusEnum::Pending,
        string $model = 'gpt-4o',
        string|RequestTypeEnum $requestType = RequestTypeEnum::Summary,
        string $sourceText = 'source text'
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

    private function createMonthlyUsage(int $userId, string $estimatedCostUsd): void
    {
        DB::table('monthly_usages')->insert([
            'user_id' => $userId,
            'year_month' => now()->format('Y-m'),
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
            'estimated_cost_usd' => $estimatedCostUsd,
            'requests_done_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    private function assertContainsMessageContent(string $content, array $messages): void
    {
        $this->assertGreaterThan(0, $this->countMessageContent($content, $messages));
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    private function assertNotContainsMessageContent(string $content, array $messages): void
    {
        $this->assertSame(0, $this->countMessageContent($content, $messages));
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    private function countMessageContent(string $content, array $messages): int
    {
        return count(array_filter(
            $messages,
            fn (array $message): bool => ($message['content'] ?? null) === $content
        ));
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    private function countMessageRole(MessageRoleEnum $role, array $messages): int
    {
        return count(array_filter(
            $messages,
            fn (array $message): bool => ($message['role'] ?? null) === $role->value
        ));
    }

    /**
     * @param  array<int, array{request_id: int, model: string, messages: array<int, array{role: string, content: string}>}>  $requests
     * @return array<int, array{role: string, content: string}>
     */
    private function messagesForClaimedRequest(array $requests, int $requestId): array
    {
        foreach ($requests as $request) {
            if ($request['request_id'] === $requestId) {
                return $request['messages'];
            }
        }

        $this->fail("Claimed request {$requestId} was not returned.");
    }
}
