<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreRegisterEloquent extends Model
{
    use HasFactory;

    protected $table = 'pre_registers';

    protected $fillable = ['email', 'token', 'expires_at'];
}
