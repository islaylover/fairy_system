<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Models\Request\Request;
use App\Domain\Models\Request\RequestId;
use Illuminate\Pagination\LengthAwarePaginator;

interface RequestRepositoryInterface
{
    /**
     * 全リクエストを取得して返す
     * 
     * @return  Request[] リクエストエンティティの配列
     */
    public function getAll() :array;

    /**
     * IDでリクエストを取得する
     * 
     * @return Request|null 見つからない場合はnull
     */
    public function findById(RequestId $requestId): ?Request;

    /**
     * リクエストを新規作成する
     */
    public function create(Request $Request): Request;

    /**
     * リクエストを更新する
     */
    public function update(Request $Request): Request;

    /**
     * IDを指定してリクエストを削除する
     */
    public function delete(RequestId $requestId) :void;
    
    /**
     * ユーザーIDを指定して、ページングされたリクエスト一覧を取得する
     */
    public function getPaginateByUser(int $userId, int $page, int $perPage): LengthAwarePaginator;

    /**
     * ユーザーIDと会話IDの組み合わせが存在するか判定
     */
    public function existByUserIdAndConversationId(int $userId, int $conversationId): bool;

    /**
     * ユーザーに紐づく最大の会話ID値を取得する
     */
    public function getMaxConversationIdByUserId(int $userId): int;

    /**
     * ユーザーIDと会話IDとステータスの組み合わせが存在するか判定
     */
    public function existsByUserIdConversationIdAndStatus(int $userId, int $conversationId, int $status): bool;
}