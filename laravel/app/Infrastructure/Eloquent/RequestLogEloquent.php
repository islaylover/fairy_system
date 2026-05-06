<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Infrastructure\Eloquent\RequestEloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequestLogEloquent extends Model
{
    use HasFactory;

    protected $table = 'request_logs';
    protected $fillable = ['request_id', 'role', 'message'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(RequestEloquent::class, 'request_id', 'id');
    }
}