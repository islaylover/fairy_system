<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class VerifyTempToken
{
    public function handle($request, Closure $next)
    {
        $token = $request->header('X-TEMP-TOKEN');

        if (! $token || ! Cache::pull("temp_token:{$token}")) {
            return response()->json(['error' => 'Invalid or expired token'], 403);
        }

        return $next($request);
    }
}
