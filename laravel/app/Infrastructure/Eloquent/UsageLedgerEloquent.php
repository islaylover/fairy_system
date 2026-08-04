<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UsageLedgerEloquent extends Model
{
    use HasFactory;

    protected $table = 'usage_ledgers';

    protected $fillable = [
        'request_id',
        'user_id',
        'year_month',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost_usd',
    ];

    protected $casts = [
        'prompt_tokens' => 'int',
        'completion_tokens' => 'int',
        'total_tokens' => 'int',
    ];
}
