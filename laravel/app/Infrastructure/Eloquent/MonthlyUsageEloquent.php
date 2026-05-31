<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MonthlyUsageEloquent extends Model
{
    use HasFactory;

    protected $table = 'monthly_usages';

    protected $fillable = [
        'user_id',
        'year_month',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost_usd',
        'requests_done_count',
    ];

    protected $casts = [
        'prompt_tokens' => 'int',
        'completion_tokens' => 'int',
        'total_tokens' => 'int',
        'requests_done_count' => 'int',
    ];
}
