<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\PreRegister;

use App\Domain\Models\PreRegister\PreRegisterEmail;
use InvalidArgumentException;
use Tests\TestCase;

class PreRegisterEmailTest extends TestCase
{
    public function test_can_create_email_with_valid_format(): void
    {
        $email = new PreRegisterEmail('test@example.com');

        $this->assertSame('test@example.com', $email->getValue());
        $this->assertSame('test@example.com', (string) $email);
    }

    public function test_throws_exception_when_email_format_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PreRegisterEmail('invalid-email');
    }

    public function test_throws_exception_when_email_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PreRegisterEmail('');
    }
}
