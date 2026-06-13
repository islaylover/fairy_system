<?php

declare(strict_types=1);

namespace App\Domain\Models\User;

use App\Domain\Utility\Validator\NumberValidator;

readonly class UserId
{
    public function __construct(
        private int $id
    ) {
        NumberValidator::validateNumber($id, [
            'label' => 'ユーザーID',
            'min' => 1,
        ]);
    }

    public function getValue(): int
    {
        return $this->id;
    }
}
