<?php

namespace Tests\Feature\Services;

use App\Infrastructure\Eloquent\PreRegisterEloquent;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    // test app/Services/RegistrationService #createNewPreRegister()
    public function test_can_create_new_pre_register(): void
    {
        // メール送信処理をモック化
        Mail::fake();

        // DIコンテナからRegistrationServiceを取得(Repositoryなどの依存も解決される)
        $service = app(RegistrationService::class);

        $service->createNewPreRegister('pre_register@example.com');

        $this->assertDatabaseHas('pre_registers', [
            'email' => 'pre_register@example.com',
        ]);

        $preRegister = PreRegisterEloquent::where('email', 'pre_register@example.com')->first();

        $this->assertNotNull($preRegister);
        $this->assertNotEmpty($preRegister->token);
        $this->assertNotNull($preRegister->expires_at);
    }

    // test app/Services/RegistrationService #getByToken()
    public function test_can_get_pre_register_by_token(): void
    {
        // メール送信処理をモック化
        Mail::fake();

        // DIコンテナからRegistrationServiceを取得(Repositoryなどの依存も解決される)
        $service = app(RegistrationService::class);

        $service->createNewPreRegister('token@example.com');

        $preRegister = PreRegisterEloquent::where('email', 'token@example.com')->first();

        $result = $service->getByToken($preRegister->token);

        $this->assertNotNull($result);
        $this->assertSame('token@example.com', $result->getEmail()->getValue());
    }

    // test app/Services/RegistrationService #completeRegister() OK Case
    public function test_can_complete_register_and_delete_pre_register(): void
    {

        // メール送信処理をモック化
        Mail::fake();

        // DIコンテナからRegistrationServiceを取得(Repositoryなどの依存も解決される)
        $service = app(RegistrationService::class);

        // 仮登録データ作成
        $service->createNewPreRegister('register@example.com');

        // 仮登録データ取得
        $preRegister = PreRegisterEloquent::where('email', 'register@example.com')->first();

        // 会員登録処理
        $service->completeRegister([
            'token' => $preRegister->token,
            'name' => 'test user',
            'password' => 'password1234',
        ]);

        // 会員登録確認
        $this->assertDatabaseHas('users', [
            'email' => 'register@example.com',
        ]);

        // 仮登録データ削除確認
        $this->assertDatabaseMissing('pre_registers', [
            'email' => 'register@example.com',
        ]);
    }

    // test app/Services/RegistrationService #completeRegister() OK Case
    public function test_complete_register_throws_exception_when_toen_is_invalid(): void
    {
        // DIコンテナからRegistrationServiceを取得(Repositoryなどの依存も解決される)
        $service = app(RegistrationService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('トークンが不正です');

        $service->completeRegister([
            'token' => 'Invalid-tOken',
            'name' => 'test user',
            'password' => 'password1234',
        ]);
    }
}
