<?php

declare(strict_types=1);

namespace App\Domain\Models\Request;

use App\Domain\Utility\Validator\NumberValidator;

readonly class RequestConversationId
{
    public function __construct(
        private int $conversation_id
    ) {
        NumberValidator::validateNumber($conversation_id, [
            'label' => '会話ID',
        ]);
    }

    public function getValue(): int
    {
        return $this->conversation_id;
    }
}