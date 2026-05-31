<?php

declare(strict_types=1);

namespace App\Domain\Models\Request;

use App\Domain\Utility\Validator\StringValidator;

class RequestResultText
{
    public function __construct(
        private string $result_text
    ) {
        StringValidator::validate($result_text, [
            'label' => '結果テキスト',
            'max' => 100000000,
        ]);
    }

    public function getValue(): string
    {
        return $this->result_text;
    }

    public function __toString(): string
    {
        return $this->result_text;
    }
}
