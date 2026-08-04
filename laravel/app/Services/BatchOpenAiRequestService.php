<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Enums\BatchOpenAiRequestResultEnum;
use App\Domain\Enums\MessageRoleEnum;
use App\Domain\Enums\RequestStatusEnum;
use App\Domain\Models\Usage\YearMonth;
use App\Domain\Models\User\UserId;
use App\Domain\Repositories\MonthlyUsageRepositoryInterface;
use App\Infrastructure\Eloquent\RequestEloquent;
use App\Infrastructure\Eloquent\RequestLogEloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Python Batch向けOpenAIリクエスト取得処理を行う
 *
 * `Pending` 状態のリクエストを排他ロック付きで取得し、有効なリクエストを`Processing`状態
 * に遷移させた上で、Open AI APIへ渡すmessage形式に変換する
 */
final readonly class BatchOpenAiRequestService
{
    public function __construct(
        private UsageAccountingService $usageAccountingService,
        private MonthlyUsageRepositoryInterface $monthlyUsageRepository,
        private OpenAiCostEstimationService $costEstimationService
    ) {}

    /**
     * 未処理(`Pending`)のOpen AIへのリクエスト情報を取得して処理中(`Processing`)状態へ変更する
     *
     * 複数のBatchが同時にclaimしても同じリクエストを取得しないよう、`FOR UPDATE SKIP LOCKED`を
     * 使用して排他制御を行う。
     *
     * system promptが定義されていないrequest_typeは処理対象外とし、Failed状態へ更新する
     *
     * message組み立ては、DBロック時間を短くするためtransaction完了後に行う
     *
     * @param  int  $batchSize  一度に取得するリクスト件数
     * @return array<int, array{
     *     request_id: int,
     *     model: string,
     *     messages: array<int, array{role: string, content: string}>
     * }>
     */
    public function claim(int $batchSize): array
    {
        $requests = DB::transaction(function () use ($batchSize): Collection {
            // 未処理リクエストを`id`順に取得する。
            // 他のBatchがロック中のレコードは`SKIP LOCKED`で読み飛ばす。
            $requests = RequestEloquent::query()
                ->where('status', RequestStatusEnum::Pending->value)
                ->orderBy('id')
                ->limit($batchSize)
                ->lock('FOR UPDATE SKIP LOCKED')
                ->get();

            if ($requests->isEmpty()) {
                return $requests;
            }

            // system promptの有無によって処理可能・処理不能に分類する。
            [$validRequests, $invalidRequests] = $requests->partition(
                fn (RequestEloquent $request): bool => $this->hasSystemPrompt((string) $request->request_type)
            );

            if ($invalidRequests->isNotEmpty()) {
                // 未定義request_typeは後続の正常なPendingを詰まらせないよう、このclaim内でFailedに退避する。
                RequestEloquent::query()
                    ->whereIn('id', $invalidRequests->pluck('id')->all())
                    ->where('status', RequestStatusEnum::Pending->value)
                    ->update([
                        'status' => RequestStatusEnum::Failed->value,
                        'result_text' => 'Unknown request_type: '.$invalidRequests
                            ->pluck('request_type') //  サポート対象外のrequest_typeを抽出
                            ->unique() // 同じrequest_typeは1つにまとめる
                            ->values() // Collectionの添字を振り直す
                            ->implode(', '),
                        'updated_at' => now(),
                    ]);
            }

            if ($validRequests->isNotEmpty()) {
                // ロック取得後もPendingのままの行だけをProcessingへ進める。
                RequestEloquent::query()
                    ->whereIn('id', $validRequests->pluck('id')->all())
                    ->where('status', RequestStatusEnum::Pending->value)
                    ->update([
                        'status' => RequestStatusEnum::Processing->value,
                        'updated_at' => now(),
                    ]);
            }

            return $validRequests->values();
        });

        return $requests
            ->map(fn (RequestEloquent $request): array => [
                'request_id' => (int) $request->id,
                'model' => (string) $request->model,
                'messages' => $this->buildMessages($request), // Open AI　APIへ渡すmessage形式を追加
            ])
            ->values()
            ->all();
    }

    /**
     * OpenAI API成功結果を保存し、利用量を一度だけ計上する
     *
     * 対象requestを行ロックしてから状態を確認し、Batchの再送や同時実行で同じrequestを
     * 二重に完了処理しないようにする。
     *
     * @return array{status: string, usage_recorded: bool}
     */
    public function complete(
        int $requestId,
        string $resultText,
        int $promptTokens,
        int $completionTokens,
        int $totalTokens
    ): array {
        return DB::transaction(function () use (
            $requestId,
            $resultText,
            $promptTokens,
            $completionTokens,
            $totalTokens
        ): array {
            $request = $this->findRequestForUpdate($requestId);

            if ((int) $request->status === RequestStatusEnum::Done->value) {
                // complete APIの再送は成功扱いにするが、usageは二重計上しない。
                return ['status' => BatchOpenAiRequestResultEnum::AlreadyCompleted->value, 'usage_recorded' => false];
            }

            $this->assertRequestStatus($request, RequestStatusEnum::Processing, 'complete');
            try {
                $estimatedCostUsd = $this->costEstimationService->estimateUsd(
                    model: (string) $request->model,
                    promptTokens: $promptTokens,
                    completionTokens: $completionTokens,
                );
            } catch (InvalidArgumentException $e) {
                throw new HttpException(409, $e->getMessage(), $e);
            }

            RequestLogEloquent::query()->create([
                'request_id' => (int) $request->id,
                'role' => MessageRoleEnum::Assistant->value,
                'message' => $resultText,
            ]);

            $request->update([
                'status' => RequestStatusEnum::Done->value,
                'result_text' => $resultText,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'estimated_cost_usd' => $estimatedCostUsd,
            ]);

            // usage_ledgersの一意制約を使い、初回完了時だけmonthly_usagesへ反映する。
            $usageRecorded = $this->usageAccountingService->recordCompletedRequestUsage(
                requestId: (int) $request->id,
                userId: (int) $request->user_id,
                promptTokens: $promptTokens,
                completionTokens: $completionTokens,
                totalTokens: $totalTokens,
                estimatedCostUsd: $estimatedCostUsd,
            );

            return ['status' => BatchOpenAiRequestResultEnum::Completed->value, 'usage_recorded' => $usageRecorded];
        });
    }

    /**
     * OpenAI API失敗結果を保存する
     *
     * completeと同じく対象requestを行ロックし、Processing中のrequestだけをFailedへ更新する。
     *
     * @return array{status: string}
     */
    public function fail(int $requestId, string $errorText): array
    {
        return DB::transaction(function () use ($requestId, $errorText): array {
            $request = $this->findRequestForUpdate($requestId);

            if ((int) $request->status === RequestStatusEnum::Failed->value) {
                // fail APIの再送は冪等に扱い、追加ログは作らない。
                return ['status' => BatchOpenAiRequestResultEnum::AlreadyFailed->value];
            }

            $this->assertRequestStatus($request, RequestStatusEnum::Processing, 'fail');

            RequestLogEloquent::query()->create([
                'request_id' => (int) $request->id,
                'role' => MessageRoleEnum::Assistant->value,
                'message' => $errorText,
            ]);

            $request->update([
                'status' => RequestStatusEnum::Failed->value,
                'result_text' => $errorText,
            ]);

            return ['status' => BatchOpenAiRequestResultEnum::Failed->value];
        });
    }

    /**
     * Batch全体の実行上限を確認する。
     *
     * daily/global上限に達している場合、Python batchはclaimせずに停止する。
     *
     * @return array{allowed: bool, scope: ?string, message: ?string}
     */
    public function checkGlobalLimits(): array
    {
        $limits = config('chatgpt.token_limits', []);

        $dailyMaxTokens = (int) ($limits['daily_max_tokens'] ?? 0);
        if ($dailyMaxTokens > 0) {
            $usedToday = (int) RequestEloquent::query()
                ->where('status', RequestStatusEnum::Done->value)
                ->whereDate('updated_at', now()->toDateString())
                ->sum('total_tokens');

            if ($usedToday >= $dailyMaxTokens) {
                return $this->limitExceeded('daily', [
                    'used_tokens' => (string) $usedToday,
                    'limit_tokens' => (string) $dailyMaxTokens,
                ]);
            }
        }

        $yearMonth = new YearMonth(now()->format('Y-m'));
        $globalUsed = $this->monthlyUsageRepository->sumAllUsersByYearMonth($yearMonth);
        $globalLimitUsd = (string) ($limits['monthly_global_limit_usd'] ?? '0');

        if ($this->isOverUsdLimit((string) $globalUsed['estimated_cost_usd'], $globalLimitUsd)) {
            return $this->limitExceeded('global', [
                'year_month' => $yearMonth->getValue(),
                'used_usd' => (string) $globalUsed['estimated_cost_usd'],
                'limit_usd' => $globalLimitUsd,
            ]);
        }

        return $this->limitAllowed();
    }

    /**
     * claim済みrequest単位の実行上限を確認する。
     *
     * user_idはAPIレスポンスに含めず、Laravel側でrequest_idから解決する。
     *
     * @return array{allowed: bool, scope: ?string, message: ?string}
     */
    public function checkRequestLimits(int $requestId): array
    {
        $request = RequestEloquent::query()->find($requestId);
        if ($request === null) {
            throw new NotFoundHttpException('リクエストが存在しません。');
        }

        $limits = config('chatgpt.token_limits', []);
        $userLimitUsd = (string) ($limits['monthly_user_limit_usd'] ?? '0');
        $yearMonth = new YearMonth(now()->format('Y-m'));
        $usage = $this->monthlyUsageRepository->findByUserIdAndYearMonth(
            new UserId((int) $request->user_id),
            $yearMonth
        );
        $usedUsd = $usage?->estimatedCostUsd->getValue() ?? '0.00000';

        if ($this->isOverUsdLimit($usedUsd, $userLimitUsd)) {
            return $this->limitExceeded('user', [
                'year_month' => $yearMonth->getValue(),
                'used_usd' => $usedUsd,
                'limit_usd' => $userLimitUsd,
            ]);
        }

        return $this->limitAllowed();
    }

    /**
     * Open AI APIに送信するmessageを組み立てる
     *
     * messagesは以下の順序で構成する
     *
     * 1. request_typeに対応するsystem prompt
     * 2. 過去の会話履歴
     * 3. 今回のユーザーメッセージ
     *
     * @param  RequestEloquent  $request  対象リクエスト
     * @return array<int, array{role: string, content: string}>
     */
    private function buildMessages(RequestEloquent $request): array
    {
        $messages = [
            [
                'role' => MessageRoleEnum::System->value,
                'content' => $this->systemPrompt((string) $request->request_type),
            ],
        ];

        // 過去の会話履歴を時系列順に追加する
        foreach ($this->conversationHistory($request) as $history) {
            $messages[] = [
                'role' => (string) $history['role'],
                'content' => (string) $history['message'],
            ];
        }

        // 今回処理するユーザー入力を最後に追加する
        $messages[] = [
            'role' => MessageRoleEnum::User->value,
            'content' => (string) $request->source_text,
        ];

        return $messages;
    }

    /**
     * `request_type`に対応するsystem promptをconfigから取得する
     *
     * @param  string  $requestType  リクエスト種別
     * @return string system prompt 未定義の場合は空文字列
     */
    private function systemPrompt(string $requestType): string
    {
        return (string) config("chatgpt.request_type_prompts.{$requestType}.system_prompt");
    }

    /**
     * 対象リクエストと同一ユーザー・同一会話の過去の会話履歴を取得する
     *
     * `完了済み`リクエストの`user`・`assistant`ログのみを対象とし、設定された最大件数分の最新会話履歴を
     * 時系列で返す
     *
     * @param  RequestEloquent  $request  対象リクエスト
     * @return array<int, array{role: string, message: string}>
     */
    private function conversationHistory(RequestEloquent $request): array
    {
        $maxMessages = max((int) config('chatgpt.history_max_messages', 6), 0);
        if ($maxMessages === 0) {
            return [];
        }

        return RequestLogEloquent::query()
            // 最新N件を取得してからreverseし、OpenAIへは古い順で渡す。
            ->select('request_logs.role', 'request_logs.message')
            ->join('requests', 'requests.id', '=', 'request_logs.request_id')
            ->where('requests.user_id', (int) $request->user_id)
            ->where('requests.conversation_id', (int) $request->conversation_id)
            ->where('requests.status', RequestStatusEnum::Done->value)
            ->where('requests.id', '<', (int) $request->id) // 現在の`リクエストID`よりも小さい`リクエストID`であること
            ->whereIn('request_logs.role', [MessageRoleEnum::User->value, MessageRoleEnum::Assistant->value])
            ->orderByDesc('request_logs.id')
            ->limit($maxMessages)
            ->get()
            ->reverse()
            ->map(fn (RequestLogEloquent $log): array => [
                'role' => (string) $log->role,
                'message' => (string) $log->message,
            ])
            ->values()
            ->all();
    }

    /**
     * `request_type`に対応するsystem promptが定義されているか判定して返す
     *
     * @param  string  $requestType  リクエスト種別
     */
    private function hasSystemPrompt(string $requestType): bool
    {
        return trim($this->systemPrompt($requestType)) !== '';
    }

    private function findRequestForUpdate(int $requestId): RequestEloquent
    {
        // complete/failの処理中に同じrequestが別Batchから更新されないよう行ロックする。
        $request = RequestEloquent::query()
            ->where('id', $requestId)
            ->lockForUpdate()
            ->first();

        if ($request === null) {
            throw new NotFoundHttpException('リクエストが存在しません。');
        }

        return $request;
    }

    private function assertRequestStatus(RequestEloquent $request, RequestStatusEnum $expected, string $action): void
    {
        if ((int) $request->status === $expected->value) {
            return;
        }

        throw new HttpException(
            409,
            "Cannot {$action} request because status is {$request->status}."
        );
    }

    private function isOverUsdLimit(string $usedUsd, string $limitUsd): bool
    {
        $limit = (float) $limitUsd;
        if ($limit <= 0.0) {
            return false;
        }

        return (float) $usedUsd >= $limit;
    }

    /**
     * @return array{allowed: bool, scope: null, message: null}
     */
    private function limitAllowed(): array
    {
        return [
            'allowed' => true,
            'scope' => null,
            'message' => null,
        ];
    }

    /**
     * @param  array<string, string>  $values
     * @return array{allowed: bool, scope: string, message: string}
     */
    private function limitExceeded(string $scope, array $values): array
    {
        $message = match ($scope) {
            'daily' => "USAGE_LIMIT_EXCEEDED scope=daily used_tokens={$values['used_tokens']} limit_tokens={$values['limit_tokens']}",
            default => "USAGE_LIMIT_EXCEEDED scope={$scope} year_month={$values['year_month']} used_usd={$values['used_usd']} limit_usd={$values['limit_usd']}",
        };

        return [
            'allowed' => false,
            'scope' => $scope,
            'message' => $message,
        ];
    }
}
