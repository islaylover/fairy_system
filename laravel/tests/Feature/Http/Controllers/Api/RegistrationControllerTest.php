<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Infrastructure\Eloquent\PreRegisterEloquent;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_token_returns_email_when_email_is_valid(): void
    {
        // mail処理モック化
        Mail::fake();

        // DIコンテナからRegistrationServiceを取得(Repositoryなどの依存も解決)
        $service = app(RegistrationService::class);

        // 仮登録
        $service->createNewPreRegister('confirm@example.com');

        // 仮登録ユーザー情報取得
        $preRegister = PreRegisterEloquent::where('email', 'confirm@example.com')->first();

        // 仮登録ユーザーに発行された正規のtokenを使ってtoken確認APIをコールするとその仮登録ユーザーのメールアドレスを返すか確認
        $response = $this->getJson('/api/register/confirm?token='.$preRegister->token);

        $response->assertOk()
            ->assertJson([
                'email' => 'confirm@example.com',
            ]);
    }

    public function test_confirm_token_return_422_when_token_is_missing(): void
    {
        // tokenなしでtoken確認APIをコール
        $response = $this->getJson('/api/register/confirm');

        // token不正扱いになるか確認
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'tokenが不正です',
            ]);
    }

    public function test_confirm_token_return_422_when_token_is_invalid(): void
    {
        // 不正なtokenでtoken確認APIをコール
        $response = $this->getJson('/api/register/confirm?token=invalid-token');

        // token不正扱いになるか確認
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'tokenが不正です',
            ]);
    }

    public function test_register_return_success_when_token_is_valid(): void
    {
        Mail::fake();

        // DIコンテナからRegistrationServiceを取得(Repositoryなどの依存も解決)
        $service = app(RegistrationService::class);

        // 仮登録
        $service->createNewPreRegister('register@example.com');

        // 仮登録ユーザー情報取得
        $preRegister = PreRegisterEloquent::where('email', 'register@example.com')->first();

        // 会員登録APIをコール
        $response = $this->postJson('/api/register', [
            'token' => $preRegister->token,
            'name' => 'test user',
            'password' => 'password1234',
        ]);

        // 会員登録が正常に行われるか確認
        $response->assertOk()
            ->assertJson([
                'message' => '登録完了しました',
            ]);

        // 会員情報テーブル(users)にユーザーのメールアドレスが登録されているか確認
        $this->assertDatabaseHas('users', [
            'email' => 'register@example.com',
        ]);

        // 仮登録テーブル(pre_registers)から会員登録したユーザーのメールアドレスが削除されているか確認
        $this->assertDatabaseMissing('pre_registers', [
            'email' => 'register@example.com',
        ]);
    }

    public function test_register_returns_422_when_required_fields_are_missing(): void
    {
        // 必須項目を満たさないで会員登録APIをコール
        $response = $this->postJson('/api/register', [
            'password' => '123',
        ]);

        // 必須項目で値がないフィールドがerrorが出るか確認　
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'token',
                'name',
            ]);
    }
}
