<?php

declare(strict_types=1);

namespace App\Domain\Models\User;

use App\Domain\Utility\Validator\StringValidator;

class UserPassword
{
    public function __construct(
        private string $hashedPassword
    ) {
        StringValidator::validate($hashedPassword, [
            'label' => 'ユーザーパスワード(ハッシュ)',
            'max' => 255
        ]);
    }

    public function getValue(): string
    {
        return $this->hashedPassword;
    }

    public function __toString(): string
    {
        return $this->hashedPassword;
    }
}