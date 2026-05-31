<?php

namespace App\Domain\Utility\Validator;

use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class DecimalValidator
{
    /**
     * 指定された値が precision/scale/範囲 に従うか検証します。
     *
     * @param  mixed  $value  入力値
     * @param  array  $options  [
     *                          'label' => ラベル名,
     *                          'precision' => 合計桁数（整数 + 小数）,
     *                          'scale' => 小数点以下の桁数,
     *                          'min' => 最小値（任意）,
     *                          'max' => 最大値（任意）
     *                          ]
     * @return float 検証済みの値
     *
     * @throws InvalidArgumentException バリデーション失敗時
     */
    public static function validate(mixed $value, array $options): float
    {
        $label = $options['label'] ?? '数値';
        $precision = $options['precision'] ?? 10;
        $scale = $options['scale'] ?? 2;
        $min = $options['min'] ?? null;
        $max = $options['max'] ?? null;

        // 正規表現で precision と scale を制限
        $intDigits = $precision - $scale;
        $regex = '/^-?\d{1,'.$intDigits.'}(?:\.\d{1,'.$scale.'})?$/';

        $rules = [
            'required',
            'numeric',
            "regex:$regex",
        ];

        $messages = [
            'value.required' => "{$label} は必須です。",
            'value.numeric' => "{$label} は数値である必要があります。",
            'value.regex' => "{$label} は最大 {$precision} 桁（うち小数 {$scale} 桁）までです。",
        ];

        if (! is_null($min) && ! is_null($max)) {
            $rules[] = "between:$min,$max";
            $messages['value.between'] = "{$label} は {$min} ～ {$max} の範囲で入力してください。";
        }

        $validator = Validator::make(
            ['value' => $value],
            ['value' => $rules],
            $messages
        );

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first('value'));
        }

        return (float) $value;
    }
}
