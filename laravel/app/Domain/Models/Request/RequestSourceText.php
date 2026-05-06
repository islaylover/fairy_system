<?php

declare(strict_types=1);

namespace App\Domain\Models\Request;

use App\Domain\Utility\Validator\StringValidator;

class RequestSourceText
{
    public function __construct(
        private string $source_text
    ) {
        StringValidator::validate($source_text, [
            'label' => 'ソーステキスト',
            'max' => 100000000
        ]);
    }

    public function getValue(): string
    {
        return $this->source_text;
    }

    public function __toString(): string
    {
        return $this->source_text;
    }
}