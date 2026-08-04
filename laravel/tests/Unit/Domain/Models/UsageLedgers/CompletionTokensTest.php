<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\UsageLedgers;

use App\Domain\Models\UsageLedgers\CompletionTokens;
use InvalidArgumentException;
use Tests\TestCase;
use TypeError;

class CompletionTokensTest extends TestCase
{
    public function test_can_create_completion_tokens_with_valid_value(): void
    {
        $tokens = new CompletionTokens(1);

        $this->assertSame(1, $tokens->getValue());
    }

    public function test_can_create_completion_tokens_with_zero(): void
    {
        $tokens = new CompletionTokens(0);

        $this->assertSame(0, $tokens->getValue());
    }

    public function test_can_create_completion_tokens_with_large_value(): void
    {
        $tokens = new CompletionTokens(999999);

        $this->assertSame(999999, $tokens->getValue());
    }

    public function test_throw_exception_when_negative_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CompletionTokens(-1);
    }

    public function test_throw_exception_when_null_value(): void
    {
        $this->expectException(TypeError::class);

        new CompletionTokens(null);
    }

    public function test_throw_exception_when_string_value(): void
    {
        $this->expectException(TypeError::class);

        new CompletionTokens('1');
    }
}
