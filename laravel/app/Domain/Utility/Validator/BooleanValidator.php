<?php

namespace App\Domain\Utility\Validator;

use InvalidArgumentException;
use Illuminate\Support\Facades\Validator;
use Exception;

class BooleanValidator
{
    /**
     * 指定された値がbooleanか検証します。
     *
     * @param  mixed  $bool_value
     * @return String 検証済みのbolean値
     * @throws InvalidArgumentException 無効な場合の場合
     */
    public static function validateBoolean(mixed $bool_value): bool
    {
        $validator = Validator::make(
            ['bool_value' => $bool_value],
            ['bool_value' => ['required', 'in:0,1']],
            [
                'bool_value.required' => '値は必須です。',
                'bool_value.in' => '値は 0 または 1 である必要があります。',
            ]
        );
        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first('bool_value'));
        }

        return (int) $bool_value === 1;
    }
}
