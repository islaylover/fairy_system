<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface ConversationLockInterface
{
    /**
     * ユーザー単位の排他ロックを取得して処理を実行する
     *
     * @template T
     * @param int $userId
     * @param int $timeoutSeconds
     * @param callable(): T $fn
     * @return T
     */
    public function withUserLock(int $userId, int $timeoutSeconds, callable $fn);
}
