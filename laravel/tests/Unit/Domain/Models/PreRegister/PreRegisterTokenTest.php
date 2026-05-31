<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\PreRegister;

use App\Domain\Models\PreRegister\PreRegisterToken;
use InvalidArgumentException;
use Tests\TestCase;

class PreRegisterTokenTest extends TestCase
{
    public function test_can_create_token_with_valid_value(): void
    {
        $token = new PreRegisterToken('abc123');

        $this->assertSame('abc123', $token->getValue());
        $this->assertSame('abc123', (string) $token);
    }

    public function test_throws_exception_when_token_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PreRegisterToken('');
    }

    public function test_throws_exception_when_token_exceeds_max_length(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PreRegisterToken(str_repeat('a', 256));
    }
}
