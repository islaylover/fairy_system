<?php

declare(strict_types=1);

namespace App\Infrastructure\Locks;

use App\Domain\Repositories\ConversationLockInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class MySqlConversationLock implements ConversationLockInterface
{
    public function withUserLock(int $userId, int $timeoutSeconds, callable $fn)
    {
        $lockName = "request_conv_{$userId}";

        $locked = DB::selectOne(
            'SELECT GET_LOCK(?, ?) AS l',
            [$lockName, $timeoutSeconds]
        );

        if (! $locked || (int) ($locked->l ?? 0) !== 1) {
            throw new RuntimeException('会話ID採番ロックの取得に失敗しました。もう一度お試しください。');
        }

        try {
            return $fn();
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS r', [$lockName]);
        }
    }
}
