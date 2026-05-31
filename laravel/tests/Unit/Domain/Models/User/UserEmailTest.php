<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\User;

use App\Domain\Models\User\UserEmail;
use InvalidArgumentException;
use Tests\TestCase;

class UserEmailTest extends TestCase
{
    public function test_can_create_email_with_valid_format(): void
    {
        $email = new UserEmail('test@example.com');

        $this->assertSame('test@example.com', $email->getValue());
        $this->assertSame('test@example.com', (string) $email);
    }

    public function test_throws_exception_when_email_format_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UserEmail('invalid-email');
    }

    public function test_throws_exception_when_email_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UserEmail('');
    }
}
