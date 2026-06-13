<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\User;

use App\Domain\Models\User\UserId;
use InvalidArgumentException;
use Tests\TestCase;
use TypeError;

class UserIdTest extends TestCase
{
    public function test_can_create_user_id_with_valid_value(): void
    {
        $id = new UserId(1);

        $this->assertSame(1, $id->getValue());
    }

    public function test_can_create_user_id_with_large_value(): void
    {
        $id = new UserId(999999);

        $this->assertSame(999999, $id->getValue());
    }

    public function test_throw_exception_when_zero_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UserId(0);
    }

    public function test_throw_exception_when_negative_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UserId(-1);
    }

    public function test_throw_exception_when_null_value(): void
    {
        $this->expectException(TypeError::class);

        new UserId(null);
    }

    public function test_throw_exception_when_string_value(): void
    {
        $this->expectException(TypeError::class);

        new UserId('1');
    }
}
