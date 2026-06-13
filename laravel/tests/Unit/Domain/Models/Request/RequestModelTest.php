<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\Request;

use App\Domain\Models\Request\RequestModel;
use InvalidArgumentException;
use Tests\TestCase;

class RequestModelTest extends TestCase
{
    public function test_can_create_request_model(): void
    {
        $modelName = new RequestModel('gpt-4');

        $this->assertSame('gpt-4', $modelName->getValue());
    }

    public function test_can_convert_model_name_to_string(): void
    {
        $modelName = new RequestModel('gpt-4o');

        $this->assertSame('gpt-4o', (string) $modelName);
    }

    public function test_throws_exception_when_model_name_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RequestModel('');
    }

    public function test_throws_exception_when_model_name_is_too_long(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RequestModel(str_repeat('a', 256));
    }
}
