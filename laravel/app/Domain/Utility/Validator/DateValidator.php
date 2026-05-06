<?php

namespace App\Domain\Utility\Validator;

use DateTime;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class DateValidator
{
    /**
     * 日時文字列を検証し、DateTime オブジェクトとして返す
     *
     * @param mixed $datetime
     * @param string $column ラベル（例: "開催日時"）
     * @return DateTime
     * @throws InvalidArgumentException
     */
    public static function validateDatetime(mixed $datetime, string $column): DateTime
    {
        if ($datetime instanceof \DateTimeInterface) {
            return new DateTime($datetime->format('Y-m-d H:i:s'));
        }
        $validator = Validator::make(
            ['datetime' => $datetime],
            ['datetime' => ['required', 'date_format:Y-m-d H:i:s']],
            [
                'datetime.required' => "{$column} は必須です。",
                'datetime.date_format' => "{$column} は YYYY-MM-DD HH:MM:SS の形式で入力してください。",
            ]
        );
    
        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first('datetime'));
        }
    
        try {
            return new DateTime($datetime);
        } catch (\Exception $e) {
            throw new InvalidArgumentException("{$column} の日時形式が不正です。");
        }
    }

    /** 
     * 'YYYY-MM' 形式の年月を検証し、正規化した文字列（YYYY-MM）を返す
     *
     * @param mixed $yearMonth
     * @param string $label ラベル（例: "対象年月"）
     * @param int $minYear 最小年（任意）
     * @param int $maxYear 最大年（任意）
     * @return string 正規化済み 'YYYY-MM'
     * @throws InvalidArgumentException
     */
    public static function validateYearMonth(
        mixed $yearMonth,
        string $label = '年月',
        int $minYear = 1970,
        int $maxYear = 2100
    ): string {
        // まず Laravel Validator で形式だけ落とす（メッセージ統一のため）
        $validator = Validator::make(
            ['year_month' => $yearMonth],
            ['year_month' => ['required', 'regex:/^\d{4}-\d{2}$/']],
            [
                'year_month.required' => "{$label} は必須です。",
                'year_month.regex' => "{$label} は YYYY-MM の形式で入力してください。",
            ]
        );

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first('year_month'));
        }

        $value = trim((string)$yearMonth);
        [$y, $m] = array_map('intval', explode('-', $value, 2));

        if ($y < $minYear || $y > $maxYear) {
            throw new InvalidArgumentException("{$label} の年が不正です: {$y}");
        }
        if ($m < 1 || $m > 12) {
            throw new InvalidArgumentException("{$label} の月が不正です: {$m}");
        }

        return sprintf('%04d-%02d', $y, $m);
    }    

}
