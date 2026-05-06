<?php

declare(strict_types=1);

namespace App\Domain\Models\PreRegister;

use App\Domain\Utility\Validator\StringValidator;

class PreRegisterToken
{
    public function __construct(
        private string $token
    ) {
        StringValidator::validate($token, [
            'label' => '仮登録トークン',
            'max' => 255
        ]);
    }

    public function getValue(): string
    {
        return $this->token;
    }

    public function __toString(): string
    {
        return $this->token;
    }
}