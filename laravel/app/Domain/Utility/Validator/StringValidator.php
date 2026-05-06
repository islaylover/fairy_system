<?php

namespace App\Domain\Utility\Validator;

use InvalidArgumentException;
use Illuminate\Support\Facades\Validator;
use Exception;

class StringValidator
{
    /**
     * 指定された文字列を検証します。
     *
     * @param mixed   $text 文字列
     * @param array   バリデーションオプション(label, min, max, kana)    
     * @return String 検証済みの文字列オブジェクト
     * @throws InvalidArgumentException バリデーション失敗時
     */
    public static function validate(mixed $text, array $options): string
    {
        $label = $options['label'] ?? 'テキスト';
        $min   = $options['min'] ?? null;
        $max   = $options['max'] ?? null;
        $kana  = $options['kana'] ?? false;
    
        $rules = ['required', 'string'];
        $messages = [
            'text.required' => "{$label} は必須です。",
            'text.string'   => "{$label} は文字列である必要があります。",
        ];
    
        if (!is_null($min)) {
            $rules[] = "min:$min";
            $messages['text.min'] = "$label は {$min} 文字以上で入力してください。";
        }

        if (!is_null($max)) {
            $rules[] = "max:{$max}";
            $messages['text.max'] = "{$label} は {$max} 文字以内で入力してください。";
        }
    
        if ($kana) {
            $rules[] = 'regex:/^[ぁ-ゟ\u30A0-\u30FF\u30FC]+$/u';
            $messages['text.regex'] = "{$label} はひらがなまたはカタカナで入力してください。";
        }

        $validator = Validator::make(
            ['text' => $text], 
            ['text' => $rules], 
            $messages
        );
    
        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first('text'));
        }
    
        return (string) $text;
    }
}