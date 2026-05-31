<?php

namespace Tests\Feature\Infrastructure\Repositories;

use App\Domain\Models\PreRegister\PreRegister;
use App\Domain\Models\PreRegister\PreRegisterEmail;
use App\Domain\Models\PreRegister\PreRegisterExpiresAt;
use App\Domain\Models\PreRegister\PreRegisterToken;
use App\Infrastructure\Repositories\EloquentPreRegisterRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentPreRegisterRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentPreRegisterRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentPreRegisterRepository;
    }

    // test findById metohd
    public function test_find_by_id_return_entity(): void
    {
        // 有効期間内の仮登録データ作成
        $preRegister = new PreRegister(
            new PreRegisterEmail('id-test@example.com'),
            new PreRegisterToken('id-token'),
            new PreRegisterExpiresAt(now()->addHour()->format('Y-m-d H:i:s')),
        );

        $this->repository->create($preRegister);

        $createdPreRegister = $this->repository->findByToken(
            new PreRegisterToken('id-token')
        );

        $result = $this->repository->findById($createdPreRegister->getId());

        $this->assertNotNull($result);
        $this->assertSame('id-test@example.com', $result->getEmail()->getValue());
    }

    // test findByToken method [now() >= pre_registers.expired_at]
    public function test_can_find_by_token_when_not_expired(): void
    {
        // 有効期間内の仮登録データ作成
        $preRegister = new PreRegister(
            new PreRegisterEmail('token-test@example.com'),
            new PreRegisterToken('valid-token'),
            new PreRegisterExpiresAt(now()->addHour()->format('Y-m-d H:i:s')),
        );

        $this->repository->create($preRegister);

        $result = $this->repository->findByToken(
            new PreRegisterToken('valid-token')
        );

        $this->assertNotNull($result);
        $this->assertSame('token-test@example.com', $result->getEmail()->getValue());
        $this->assertSame('valid-token', $result->getToken()->getValue());
    }

    // test findByToken method [now() < pre_registers.expired_at]
    public function test_can_find_by_token_when_expired(): void
    {
        // 有効期間 +1 Hourの仮登録データ作成
        $preRegister = new PreRegister(
            new PreRegisterEmail('token-test@example.com'),
            new PreRegisterToken('valid-token'),
            new PreRegisterExpiresAt(now()->subHour()->format('Y-m-d H:i:s')),
        );

        $this->repository->create($preRegister);

        $result = $this->repository->findByToken(
            new PreRegisterToken('valid-token')
        );

        $this->assertNull($result);
    }

    // test create method
    public function test_can_create_pre_register(): void
    {
        $preRegister = new PreRegister(
            new PreRegisterEmail('test@example.com'),
            new PreRegisterToken('token12345'),
            new PreRegisterExpiresAt(now()->addHour()->format('Y-m-d H:i:s')),
        );

        $this->repository->create($preRegister);

        $this->assertDatabaseHas('pre_registers', [
            'email' => 'test@example.com',
            'token' => 'token12345',
        ]);
    }

    // test delete method
    public function test_can_delete_pre_register(): void
    {
        // 有効期間 +1 Hourの仮登録データ作成
        $preRegister = new PreRegister(
            new PreRegisterEmail('delete-test@example.com'),
            new PreRegisterToken('delete-token'),
            new PreRegisterExpiresAt(now()->addHour()->format('Y-m-d H:i:s')),
        );

        $this->repository->create($preRegister);

        $createdPreRegister = $this->repository->findByToken(
            new PreRegisterToken('delete-token')
        );

        $this->assertNotNull($createdPreRegister);

        $this->repository->delete($createdPreRegister->getId());

        $this->assertDatabaseMissing('pre_registers', [
            'email' => 'delete-test@example.com',
            'token' => 'delete-token',
        ]);
    }
}
