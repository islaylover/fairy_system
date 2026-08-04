<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum MessageRoleEnum: string
{
    case System = 'system';
    case User = 'user';
    case Assistant = 'assistant';
}
