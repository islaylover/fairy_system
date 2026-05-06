<?php 

declare(strict_types=1);

namespace App\Services;

use App\Domain\Models\User\UserEmail;
use App\Domain\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Log;

readonly class AuthService
{
    public function __construct(
        private UserRepositoryInterface $userRepositoryInterface
    ) {}

    // login処理
    public function login(string $email, string $plainPassword): array
    {
        $user = $this->userRepositoryInterface->findByEmail(new UserEmail($email));
        Log::info("get user by email:". $email);
        Log::info("-- resukt --");
        Log::info(var_export($user, true));
        if (!$user) {
            throw new InvalidArgumentException('メールアドレスまたはパスワードが正しくありません。');
        }
        Log::info("get user step2");
        $hashedPassword = $user->getPassword()->getValue();
        
        if (!Hash::check($plainPassword, $hashedPassword)) {
            throw new InvalidArgumentException('メールアドレスまたはパスワードが正しくありません。');
        }
        Log::info("get user step3");
        $apiToken = $this->userRepositoryInterface->issueApiToken(new UserEmail($email));
        Log::info("get user step4");
        return [
            'token' => $apiToken,
            'user'  => [
                'id'    => $user->getId()->getValue(), 
                'name'  => $user->getName()->getValue(),
                'email' => $user->getEmail()->getValue(),
            ],
        ];
    }
}