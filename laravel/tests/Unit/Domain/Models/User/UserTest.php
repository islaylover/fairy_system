<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\User;

use App\Domain\Models\User\User;
use App\Domain\Models\User\UserEmail;
use App\Domain\Models\User\UserId;
use App\Domain\Models\User\UserName;
use App\Domain\Models\User\UserPassword;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_can_create_pre_register_entity(): void
    {
        $user = new User(
            new UserName('山田太郎'),
            new UserEmail('taro_yamada@example.com'),
            new UserPassword('password7890'),
            new UserId(1),
        );

        $this->assertSame(1, $user->getId()?->getValue());
        $this->assertSame('山田太郎', $user->getName()->getValue());
        $this->assertSame('taro_yamada@example.com', $user->getEmail()->getValue());
        $this->assertSame('password7890', $user->getPassword()->getValue());
    }

    public function test_can_create_user_entity_without_id(): void
    {
        $user = new User(
            new UserName('山田太郎'),
            new UserEmail('taro_yamada@example.com'),
            new UserPassword('password7890'),
        );

        $this->assertNull($user->getId());
    }
}
