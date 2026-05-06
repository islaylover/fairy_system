<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class TokenController extends Controller
{
    public function issue(Request $request): JsonResponse
    {
        $token = Str::uuid()->toString();
        $expiresAt = config('fairy.api.token.expires_at', 10);
        Cache::put("temp_token:{$token}", true, now()->addMinutes($expiresAt)); // N分限定トークン
        return response()->json(['token' => $token]);
    }
}