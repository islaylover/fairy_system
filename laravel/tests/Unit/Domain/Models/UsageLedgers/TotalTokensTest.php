<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\UsageLedgers;

use App\Domain\Models\UsageLedgers\TotalTokens;
use InvalidArgumentException;
use Tests\TestCase;
use TypeError;

class TotalTokensTest extends TestCase
{
    public function test_can_create_total_tokens_with_valid_value(): void
    {
        $tokens = new TotalTokens(1);

        $this->assertSame(1, $tokens->getValue());
    }

    public function test_can_create_total_tokens_with_zero(): void
    {
        $tokens = new TotalTokens(0);

        $this->assertSame(0, $tokens->getValue());
    }

    public function test_can_create_total_tokens_with_large_value(): void
    {
        $tokens = new TotalTokens(999999);

        $this->assertSame(999999, $tokens->getValue());
    }

    public function test_throw_exception_when_negative_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TotalTokens(-1);
    }

    public function test_throw_exception_when_null_value(): void
    {
        $this->expectException(TypeError::class);

        new TotalTokens(null);
    }

    public function test_throw_exception_when_string_value(): void
    {
        $this->expectException(TypeError::class);

        new TotalTokens('1');
    }
}
