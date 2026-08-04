<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum BatchOpenAiRequestResultEnum: string
{
    case Completed = 'completed';
    case AlreadyCompleted = 'already_completed';
    case Failed = 'failed';
    case AlreadyFailed = 'already_failed';
}
