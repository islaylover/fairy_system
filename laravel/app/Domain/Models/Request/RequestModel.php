<?php

declare(strict_types=1);

namespace App\Domain\Models\Request;

use App\Domain\Utility\Validator\StringValidator;

class RequestModel
{
    public function __construct(
        private string $model
    ) {
        StringValidator::validate($model, [
            'label' => 'ChatGPT Model',
            'max' => 255,
        ]);
    }

    public function getValue(): string
    {
        return $this->model;
    }

    public function __toString(): string
    {
        return $this->model;
    }
}
