<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum RequestTypeEnum: string
{
    case Summary = 'summary';
    case Translate = 'translate';
    case Rewrite = 'rewrite';
    case FreeForm = 'freeform';
    case FormatTable = 'format_table';


    public function label(): string
    {
        return match($this) {
            self::Summary => '要約',
            self::Translate => '翻訳',
            self::Rewrite => 'リライト',
            self::FreeForm => '自由入力',
            self::FormatTable => '表に整形',
        };
    }

}