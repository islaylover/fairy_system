<?php

declare(strict_types=1);

namespace App\Domain\Models\User;

use App\Domain\Utility\Validator\StringValidator;

class UserName
{
    public function __construct(
        private string $name
    ) {
        StringValidator::validate($name, [
            'label' => 'ユーザー名',
            'max' => 255,
        ]);
    }

    public function getValue(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
