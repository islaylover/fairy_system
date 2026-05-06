<?php

declare(strict_types=1);

namespace App\Domain\Models\RequestLog;

use App\Domain\Utility\Validator\StringValidator;

class RequestLogMessage
{
    public function __construct(
        private string $message
    ) {
        StringValidator::validate($message, [
            'label' => '質問/回答テキスト',
            'max' => 100000000
        ]);
    }

    public function getValue(): string
    {
        return $this->message;
    }

    public function __toString(): string
    {
        return $this->message;
    }
}