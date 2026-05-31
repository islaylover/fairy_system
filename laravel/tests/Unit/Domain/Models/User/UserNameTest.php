<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\User;

use App\Domain\Models\User\UserName;
use InvalidArgumentException;
use Tests\TestCase;

class UserNameTest extends TestCase
{
    public function test_can_create_user_name(): void
    {
        $userName = new UserName('test user');

        $this->assertSame('test user', $userName->getValue());
    }

    public function test_can_convert_user_name_to_string(): void
    {
        $userName = new UserName('test user');

        $this->assertSame('test user', (string) $userName);
    }

    public function test_throws_exception_when_user_name_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UserName('');
    }

    public function test_throws_exception_when_user_name_is_too_long(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UserName(str_repeat('a', 256));
    }
}
