<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // $middleware->api(prepend: [
        //     \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,

        // ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'temp_token'  => \App\Http\Middleware\VerifyTempToken::class,
            'batch.key' => \App\Http\Middleware\BatchKeyAuth::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,

        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Domain\Exceptions\UsageLimitExceededException $e, $request) {
            if (!$request->expectsJson()) return null;

            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'USAGE_LIMIT_EXCEEDED',
                'scope' => $e->scope(), // 'user' | 'global'
                'year_month' => $e->yearMonth(),
                'limit_usd' => $e->limitUsd(),
                'used_usd' => $e->usedUsd(),
                'remaining_usd' => $e->remainingUsd(),
            ], 429);
        });

        // 422: Laravelのバリデーション（$request->validate / FormRequest）
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if (!$request->expectsJson()) {
                return null; // web画面はLaravel標準に任せる
            }
            return response()->json([
                'message' => '入力内容に誤りがあります。',
                'errors'  => $e->errors(),
            ], 422);
        });

        // 422: 値オブジェクトなどの不正（YearMonth等）
        $exceptions->render(function (\InvalidArgumentException $e, $request) {
            if (!$request->expectsJson()) {
                return null;
            }
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        });

        $exceptions->render(function (\RuntimeException $e, $request) {
            if (!$request->expectsJson()) return null;
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        });

        // 401
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if (!$request->expectsJson()) {
                return null;
            }
            return response()->json(['message' => '認証が必要です。'], 401);
        });

        // 403
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if (!$request->expectsJson()) {
                return null;
            }
            return response()->json(['message' => '権限がありません。'], 403);
        });

        // 500: 想定外（HTTP例外 404/405 は Laravel に任せる）
        $exceptions->render(function (\Throwable $e, $request) {
            if (!$request->expectsJson()) {
                return null;
            }

            // 404/405などはここで握りつぶさず、Laravel標準に任せたい
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                return null;
            }

            \Illuminate\Support\Facades\Log::error($e);

            return response()->json([
                'message' => 'サーバーエラーが発生しました',
            ], 500);
        });
    })->create();
