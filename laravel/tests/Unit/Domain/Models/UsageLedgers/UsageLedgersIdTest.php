<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\UsageLedgers;

use App\Domain\Models\UsageLedgers\UsageLedgersId;
use InvalidArgumentException;
use Tests\TestCase;
use TypeError;

class UsageLedgersIdTest extends TestCase
{
    public function test_can_create_usage_ledgers_id_with_valid_value(): void
    {
        $id = new UsageLedgersId(1);

        $this->assertSame(1, $id->getValue());
    }

    public function test_can_create_usage_ledgers_id_with_large_value(): void
    {
        $id = new UsageLedgersId(999999);

        $this->assertSame(999999, $id->getValue());
    }

    public function test_throw_exception_when_zero_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UsageLedgersId(0);
    }

    public function test_throw_exception_when_negative_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UsageLedgersId(-1);
    }

    public function test_throw_exception_when_null_value(): void
    {
        $this->expectException(TypeError::class);

        new UsageLedgersId(null);
    }

    public function test_throw_exception_when_string_value(): void
    {
        $this->expectException(TypeError::class);

        new UsageLedgersId('1');
    }
}
