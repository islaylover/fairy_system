<?php

namespace App\Domain\Utility\Validator;

use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class EmailValidator
{
    /**
     * Emailの形式を検証する
     *
     * @param  mixed  $email
     * @param  array  $options  ['label' => string, 'max' => int|null]
     * @return string 検証済みのEmail
     *
     * @throws InvalidArgumentException 無効な形式の場合
     */
    public static function validateEmail($email, array $options = []): string
    {
        $label = $options['label'] ?? 'メールアドレス';
        $max = $options['max'] ?? 255;

        $rules = ['required', 'string', 'email', "max:$max"];
        $messages = [
            'email.required' => "$label は必須です。",
            'email.string' => "$label は文字列である必要があります。",
            'email.email' => "$label の形式が正しくありません。",
            'email.max' => "$label は {$max} 文字以内で入力してください。",
        ];

        $validator = Validator::make(
            ['email' => $email],
            ['email' => $rules],
            $messages
        );

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first('email'));
        }

        return (string) $email;
    }
}
