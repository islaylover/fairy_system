<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\Request;

use App\Domain\Models\Request\RequestPromptToken;
use InvalidArgumentException;
use Tests\TestCase;
use TypeError;

class RequestPromptTokenTest extends TestCase
{
    public function test_can_create_prompt_token_with_valid_value(): void
    {
        $id = new RequestPromptToken(1);

        $this->assertSame(1, $id->getValue());
    }

    public function test_can_careate_prompt_token_with_large_value(): void
    {
        $id = new RequestPromptToken(999999);

        $this->assertSame(999999, $id->getValue());
    }

    public function test_throw_exception_when_zero_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RequestPromptToken(0);
    }

    public function test_throw_exception_when_negative_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RequestPromptToken(-1);
    }

    public function test_throw_exception_when_null_value(): void
    {
        $this->expectException(TypeError::class);

        new RequestPromptToken(null);
    }

    public function test_throw_exception_when_string_value(): void
    {
        $this->expectException(TypeError::class);

        new RequestPromptToken('1');
    }
}
