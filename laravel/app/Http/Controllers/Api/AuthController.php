<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    // LOGIN処理[成功時はtokenを返す]
    public function login(Request $request): JsonResponse
    {
        Log::info('LOGIN step01');

        // バリデーション（不正なら ValidationException → Handlerで422）
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        Log::info('LOGIN step02');

        // ここで投げられる例外はすべて Handler に委譲
        $result = $this->authService->login($data['email'], $data['password']);

        Log::info('LOGIN step03 ok');

        return response()->json($result);
    }

    // LOGOUT処理[token削除]
    public function logout(Request $request): JsonResponse
    {
        // 現在のトークンだけ無効化
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'ログアウトしました。']);
    }
}
