<?php

namespace App\Domain\Utility\Validator;

use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class NumberValidator
{
    /**
     * 指定された数値を検証します。
     *
     * @param  mixed  $number  数値
     * @return int 検証済みの数値オブジェクト
     *
     * @throws InvalidArgumentException 無効な数値形式の場合
     */
    public static function validateNumber(mixed $number, array $options): int
    {
        $label = $options['label'] ?? 'テキスト';
        $max = $options['max'] ?? null;
        $min = $options['min'] ?? null;
        $rules = ['required', 'numeric'];

        $messages = [
            'number.required' => "{$label} は必須です。",
            'number.numeric' => "{$label} は数値である必要があります。",
        ];

        if (! is_null($min)) {
            $rules[] = "min:{$min}";
            $messages['number.min'] = "{$label} は {$min} 以上である必要があります。";
        }

        if (! is_null($max)) {
            $rules[] = "max:{$max}";
            $messages['number.max'] = "{$label} は {$max} 以下である必要があります。";
        }

        $validator = Validator::make(
            ['number' => $number],
            ['number' => $rules],
            $messages
        );

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first('number'));
        }

        return (int) $number;
    }
}
