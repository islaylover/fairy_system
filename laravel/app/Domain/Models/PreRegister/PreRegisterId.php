<?php

declare(strict_types=1);

namespace App\Domain\Models\PreRegister;

use App\Domain\Utility\Validator\NumberValidator;

readonly class PreRegisterId
{
    public function __construct(
        private int $id
    ) {
        NumberValidator::validateNumber($id, [
            'label' => '仮登録ID',
        ]);
    }

    public function getValue(): int
    {
        return $this->id;
    }
}