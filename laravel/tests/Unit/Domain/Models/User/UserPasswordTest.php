<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\User;

use App\Domain\Models\User\UserPassword;
use InvalidArgumentException;
use Tests\TestCase;

class UserPasswordTest extends TestCase
{
    public function test_can_create_user_password(): void
    {
        $hashedPassword = '$2y$12$abcdefghijklmnopqrstuv';

        $userPassword = new UserPassword($hashedPassword);

        $this->assertSame(
            $hashedPassword,
            $userPassword->getValue()
        );
    }

    public function test_can_convert_user_password_to_string(): void
    {
        $hashedPassword = '$2y$12$abcdefghijklmnopqrstuv';

        $userPassword = new UserPassword($hashedPassword);

        $this->assertSame(
            $hashedPassword,
            (string) $userPassword
        );
    }

    public function test_throws_exception_when_password_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UserPassword('');
    }

    public function test_throws_exception_when_password_is_too_long(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UserPassword(
            str_repeat('a', 256)
        );
    }
}
