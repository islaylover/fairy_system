<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\Request;

use App\Domain\Models\Request\RequestType;
use InvalidArgumentException;
use Tests\TestCase;

class RequestTypeTest extends TestCase
{
    public function test_can_create_request_type(): void
    {
        $requestType = new RequestType('summary');

        $this->assertSame('summary', $requestType->getValue());
    }

    public function test_can_convert_request_type_to_string(): void
    {
        $requestType = new RequestType('translate');

        $this->assertSame('translate', (string) $requestType);
    }

    public function test_throws_exception_when_request_type_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RequestType('');
    }

    public function test_throws_exception_when_request_type_is_too_long(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RequestType(str_repeat('a', 256));
    }
}
