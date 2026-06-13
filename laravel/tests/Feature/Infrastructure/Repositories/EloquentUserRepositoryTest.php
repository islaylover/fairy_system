<?php

namespace Tests\Feature\Infrastructure\Repositories;

use App\Domain\Models\User\User;
use App\Domain\Models\User\UserEmail;
use App\Domain\Models\User\UserId;
use App\Domain\Models\User\UserName;
use App\Domain\Models\User\UserPassword;
use App\Infrastructure\Repositories\EloquentUserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class EloquentUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentUserRepository;
    }

    private function createDomainUser(
        string $name,
        string $email,
        string $password,
        ?int $id = null
    ): User {
        return new User(
            new UserName($name),
            new UserEmail($email),
            new UserPassword($password),
            $id === null ? null : new UserId($id),
        );
    }

    public function test_can_get_all_users(): void
    {
        $this->repository->create(
            $this->createDomainUser('Taro', 'taro@example.com', 'password-1')
        );
        $this->repository->create(
            $this->createDomainUser('Jiro', 'jiro@example.com', 'password-2')
        );

        $users = $this->repository->getAll();

        $this->assertCount(2, $users);
        $this->assertContainsOnlyInstancesOf(User::class, $users);

        $this->assertSame('Taro', $users[0]->getName()->getValue());
        $this->assertSame('taro@example.com', $users[0]->getEmail()->getValue());
        $this->assertSame('password-1', $users[0]->getPassword()->getValue());
        $this->assertSame('Jiro', $users[1]->getName()->getValue());
        $this->assertSame('jiro@example.com', $users[1]->getEmail()->getValue());
        $this->assertSame('password-2', $users[1]->getPassword()->getValue());
    }

    public function test_get_all_users_returns_empty_array_when_user_not_found(): void
    {
        $user = $this->repository->getAll();

        $this->assertEmpty($user);
    }

    public function test_can_find_by_id(): void
    {
        $this->repository->create(
            $this->createDomainUser('Taro', 'taro@example.com', 'password-1')
        );
        $this->repository->create(
            $this->createDomainUser('Jiro', 'jiro@example.com', 'password-2')
        );

        $createdUser = $this->repository->findByEmail(new UserEmail('taro@example.com'));
        $this->assertInstanceOf(User::class, $createdUser);

        $user = $this->repository->findById($createdUser->getId());

        $this->assertSame('Taro', $user->getName()->getValue());
        $this->assertSame('taro@example.com', $user->getEmail()->getValue());
        $this->assertSame('password-1', $user->getPassword()->getValue());
    }

    public function test_find_by_id_returns_null_when_user_not_found(): void
    {
        $this->repository->create(
            $this->createDomainUser('Taro', 'taro@example.com', 'password-1')
        );
        $this->repository->create(
            $this->createDomainUser('Jiro', 'jiro@example.com', 'password-2')
        );

        $user = $this->repository->findById(new UserId(999));

        $this->assertNull($user);
    }

    public function test_can_find_by_email(): void
    {
        $this->repository->create(
            $this->createDomainUser('Taro', 'taro@example.com', 'password-1')
        );
        $this->repository->create(
            $this->createDomainUser('Jiro', 'jiro@example.com', 'password-2')
        );

        $user = $this->repository->findByEmail(new UserEmail('jiro@example.com'));

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Jiro', $user->getName()->getValue());
        $this->assertSame('jiro@example.com', $user->getEmail()->getValue());
        $this->assertSame('password-2', $user->getPassword()->getValue());
    }

    public function test_find_by_email_returns_null_when_user_not_found(): void
    {
        $this->repository->create(
            $this->createDomainUser('Taro', 'taro@example.com', 'password-1')
        );
        $this->repository->create(
            $this->createDomainUser('Jiro', 'jiro@example.com', 'password-2')
        );

        $user = $this->repository->findByEmail(new UserEmail('missing@example.com'));

        $this->assertNull($user);
    }

    public function test_can_create_user(): void
    {
        $this->repository->create(
            $this->createDomainUser('Taro', 'taro@example.com', 'password')
        );

        $user = $this->repository->findByEmail(new UserEmail('taro@example.com'));

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Taro', $user->getName()->getValue());
        $this->assertSame('taro@example.com', $user->getEmail()->getValue());
        $this->assertSame('password', $user->getPassword()->getValue());
    }

    public function test_can_update_user(): void
    {
        $this->repository->create(
            $this->createDomainUser('Before', 'before@example.com', 'before-password')
        );

        $createdUser = $this->repository->findByEmail(new UserEmail('before@example.com'));

        $this->assertInstanceOf(User::class, $createdUser);

        $this->repository->update(
            $this->createDomainUser(
                'After',
                'after@example.com',
                'after-password',
                $createdUser->getId()->getValue()
            )
        );

        $updatedUser = $this->repository->findById($createdUser->getId());

        $this->assertInstanceOf(User::class, $updatedUser);
        $this->assertSame('After', $updatedUser->getName()->getValue());
        $this->assertSame('after@example.com', $updatedUser->getEmail()->getValue());
        $this->assertSame('after-password', $updatedUser->getPassword()->getValue());
    }

    public function test_update_throws_exception_when_user_not_found(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User not found');

        $this->repository->update(
            $this->createDomainUser(
                'Missing',
                'missing@example.com',
                'password',
                999,
            )
        );
    }

    public function test_can_delete_user(): void
    {
        $this->repository->create(
            $this->createDomainUser('Taro', 'taro@example.com', 'password')
        );

        $createdUser = $this->repository->findByEmail(new UserEmail('taro@example.com'));

        $this->assertInstanceOf(User::class, $createdUser);

        $this->repository->delete($createdUser->getId());

        $this->assertNull(
            $this->repository->findById($createdUser->getId())
        );
    }

    public function test_can_issue_api_token(): void
    {
        $this->repository->create(
            $this->createDomainUser('Taro', 'taro@example.com', 'password')
        );

        $oldToken = $this->repository->issueApiToken(
            new UserEmail('taro@example.com'),
            'old-token'
        );

        $newToken = $this->repository->issueApiToken(
            new UserEmail('taro@example.com'),
            'new-token'
        );

        $this->assertIsString($newToken);
        $this->assertNotSame($oldToken, $newToken);

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'new-token',
        ]);
    }

    public function test_can_check_exists_by_email(): void
    {
        $this->repository->create(
            $this->createDomainUser('Taro', 'taro@example.com', 'password')
        );

        $this->assertTrue(
            $this->repository->existsByEmail(new UserEmail('taro@example.com'))
        );

        $this->assertFalse(
            $this->repository->existsByEmail(new UserEmail('missing@example.com'))
        );
    }
}
