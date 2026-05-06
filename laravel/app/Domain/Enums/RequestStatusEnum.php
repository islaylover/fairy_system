<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum RequestStatusEnum: int
{
    case Pending = 0;
    case Processing = 1;
    case Done = 2;
    case Failed = 9;

    public function label(): string
    {
        return match ($this) {
            self::Pending => '未処理',
            self::Processing => '処理中',
            self::Done => '完了',
            self::Failed => '失敗',
        };
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Done, self::Failed], true);
    }
}
