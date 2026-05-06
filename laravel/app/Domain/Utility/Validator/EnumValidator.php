<?php

namespace App\Domain\Utility\Validator;

use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class EnumValidator
{
    /**
     * 指定された値が allowed に含まれているか検証
     *
     * @param mixed $value 検証対象の値
     * @param array $options ['label' => ..., 'allowed' => [...]]
     * @return string 検証済みの値
     * @throws InvalidArgumentException
     */
    public static function validate(mixed $value, array $options): string
    {
        $label   = $options['label'] ?? '値';
        $allowed = $options['allowed'] ?? [];

        $rules = [
            'required',
            'in:' . implode(',', $allowed),
        ];

        $messages = [
            'value.required' => "{$label} は必須です。",
            'value.in' => "{$label} は " . implode('、', $allowed) . " のいずれかでなければなりません。",
        ];

        $validator = Validator::make(
            ['value' => $value],
            ['value' => $rules],
            $messages
        );

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first('value'));
        }

        return (string)$value;
    }
}