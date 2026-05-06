<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Models\PreRegister\PreRegister;
use App\Domain\Models\PreRegister\PreRegisterId;
use App\Domain\Models\PreRegister\PreRegisterEmail;
use App\Domain\Models\PreRegister\PreRegisterExpiresAt;
use App\Domain\Models\PreRegister\PreRegisterToken;
use App\Domain\Repositories\PreRegisterRepositoryInterface;
use App\Domain\Models\User\User;
use App\Domain\Models\User\UserName;
use App\Domain\Models\User\UserEmail;
use App\Domain\Models\User\UserPassword;
use App\Domain\Repositories\UserRepositoryInterface;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\PreRegisterMail;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Log;

readonly class RegistrationService
{
    public function __construct(
        private PreRegisterRepositoryInterface $preRegisterRepository,
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * 仮登録レコードを作成
     */
    public function createNewPreRegister(string $email)
    {
        if ($this->userRepository->existsByEmail(new UserEmail($email))) {
            throw new RuntimeException('このメールアドレスはすでに使用中です');
        }
        $token = Str::random(64);
        $expiresAt = Carbon::now()->addMinutes(config('fairy.pre_register.expires_at'));

        $preRegister = new PreRegister(
            new PreRegisterEmail($email),
            new PreRegisterToken($token),
            new PreRegisterExpiresAt($expiresAt)
        );

        try {
            DB::transaction(function () use ($email, $preRegister) {
            
                $this->preRegisterRepository->create($preRegister);

                $url = $this->generateRegisterUrl($preRegister->getToken());
                Mail::to($email)->send(new PreRegisterMail($url));
            });

        } catch (QueryException $e) {
            throw $e; // エラー 
        }
    }

    /**
     * トークンから仮登録情報を取得
     */
    public function getByToken(string $token): ?PreRegister
    {
        return $this->preRegisterRepository->findByToken(new PreRegisterToken($token));
    }


    /**
     * 会員登録処理
     */
    public function completeRegister(array $data): void
    {
        // 1. PreRegister を token から取得（有効期限チェックなど）
        $preRegister = $this->preRegisterRepository->findByToken(new PreRegisterToken($data['token']));
        if (! $preRegister) {
            throw new RuntimeException('トークンが不正です');
        }

        // 2.パスワードハッシュ化
        $hashedPassword = $data['password'] ? Hash::make($data['password']) : '';
        $user = new User(
            new UserName($data['name']),
            new UserEmail($preRegister->getEmail()->getValue()),
            new UserPassword($hashedPassword)
        );

        try {
            DB::transaction(function () use ($user, $preRegister) {
                // 3. UserRepository 経由で保存
                $this->userRepository->create($user);

                // 4. PreRegister を削除
                $this->preRegisterRepository->delete($preRegister->getId());
            });
        } catch (QueryException $e) {
            $sqlState = $e->errorInfo[0] ?? null;
            $driverCode = $e->errorInfo[1] ?? null;

            if ($sqlState === '23000' && (int)$driverCode === 1062) {
                throw new RuntimeException('このメールアドレスはすでに使われています。');
            }

            throw $e; // そのほかのエラー 
        }

    }

    /**
     * 会員登録用URL生成
     */
    private function generateRegisterUrl(PreRegisterToken $token): string
    {
        return url('/register/confirm?token=' . $token->getValue());
    }
}