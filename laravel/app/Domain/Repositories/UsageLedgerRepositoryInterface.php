<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Models\UsageLedgers\UsageLedgers;

interface UsageLedgerRepositoryInterface
{
    /**
     * request_id の一意制約を使って利用量を一度だけ記録する。
     *
     * @return bool true=新規記録 / false=既に記録済み
     */
    public function createOnce(UsageLedgers $usageLedger): bool;
}
