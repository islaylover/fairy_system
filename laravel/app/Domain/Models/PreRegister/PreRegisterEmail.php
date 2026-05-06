<?php

declare(strict_types=1);

namespace App\Domain\Models\PreRegister;

use App\Domain\Utility\Validator\EmailValidator;

readonly class PreRegisterEmail
{
    public function __construct(
        private string $email
    ) {
        EmailValidator::validateEmail($email, [
            'label' => '仮登録メールアドレス'
        ]);
    }

    public function getValue(): string
    {
        return $this->email;
    }

    public function __toString(): string
    {
        return $this->email;
    }
}