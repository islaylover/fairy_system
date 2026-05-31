<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BatchKeyAuth
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-BATCH-KEY');
        $expected = config('app.batch_api_key');

        if (! $expected || $key !== $expected) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
