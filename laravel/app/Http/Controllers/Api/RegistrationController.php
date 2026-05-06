<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\RegistrationService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class RegistrationController extends Controller
{
    public function __construct(
        private RegistrationService $registrationService
    ) {}

    // token埋め込み仮登録メール送信
    public function sendPreRegisterMail(Request $request): JsonResponse
    {
        // 不正なら ValidationException → Handlerで422
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        // InvalidArgumentException / RuntimeException / Throwable は全部 Handler に委譲
        $this->registrationService->createNewPreRegister($data['email']);

        return response()->json(['message' => '仮登録メールを送信しました']);
    }

    // token整合性チェック
    public function confirmToken(Request $request): JsonResponse
    {
        // tokenはqueryなので文字列チェックだけ（不正は業務分岐として返す）
        $token = $request->query('token');

        if (!is_string($token) || $token === '') {
            // 入力不正として422にしたいならここ（またはvalidateに寄せてもOK）
            return response()->json(['message' => 'tokenが不正です'], 422);
        }

        $result = $this->registrationService->getByToken($token);

        if (!$result) {
            // ここは「例外」ではなく「存在しない」なのでControllerで返すのが自然
            return response()->json(['message' => 'tokenが不正です'], 422);
        }

        return response()->json([
            'email' => $result->getEmail()->getValue(),
        ]);
    }

    // 本登録
    public function register(Request $request): JsonResponse
    {
        // 本来ここは必須項目を validate した方が安全
        // ※必要な項目名はあなたの completeRegister の仕様に合わせて調整してね
        $data = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string'],
            'name' => ['required', 'string'],
        ]);

        $this->registrationService->completeRegister($data);

        return response()->json(['message' => '登録完了しました']);
    }
}