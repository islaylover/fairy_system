<?php

declare(strict_types=1);

namespace App\Domain\Models\User;

use App\Domain\Utility\Validator\EmailValidator;

readonly class UserEmail
{
    public function __construct(
        private string $email
    ) {
        EmailValidator::validateEmail($email, [
            'label' => 'ユーザーのメールアドレス'
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