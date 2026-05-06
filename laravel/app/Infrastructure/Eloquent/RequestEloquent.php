<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Infrastructure\Eloquent\RequestLogEloquent;
use App\Models\User;

class RequestEloquent extends Model
{
    use HasFactory;

    protected $table = 'requests';
    protected $fillable = [
        'user_id', 
        'conversation_id', 
        'model', 
        'request_type', 
        'source_text', 
        'result_text', 
        'status', 
        'prompt_tokens', 
        'completion_tokens', 
        'total_tokens', 
        'estimated_cost_usd'
    ];

    public function requestLogs(): HasMany
    {
        return $this->hasMany(RequestLogEloquent::class, 'request_id', 'id');   
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}