<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\UsageLedgers;

use App\Domain\Models\UsageLedgers\EstimatedCostUsd;
use InvalidArgumentException;
use Tests\TestCase;
use TypeError;

class EstimatedCostUsdTest extends TestCase
{
    public function test_can_create_estimated_cost_usd_with_valid_value(): void
    {
        $cost = new EstimatedCostUsd('0.12345');

        $this->assertSame('0.12345', $cost->getValue());
    }

    public function test_can_create_estimated_cost_usd_with_zero(): void
    {
        $cost = new EstimatedCostUsd('0');

        $this->assertSame('0', $cost->getValue());
    }

    public function test_can_create_estimated_cost_usd_with_max_value(): void
    {
        $cost = new EstimatedCostUsd('999.99999');

        $this->assertSame('999.99999', $cost->getValue());
    }

    public function test_throw_exception_with_negative_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EstimatedCostUsd('-0.00001');
    }

    public function test_throw_exception_when_over_max_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EstimatedCostUsd('1000.00000');
    }

    public function test_throw_exception_when_scale_too_long(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EstimatedCostUsd('0.123456');
    }

    public function test_throw_exception_when_precision_too_long(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EstimatedCostUsd('1234.12345');
    }

    public function test_throw_exception_when_not_numeric(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EstimatedCostUsd('abc');
    }

    public function test_throw_type_error_when_null(): void
    {
        $this->expectException(TypeError::class);

        new EstimatedCostUsd(null);
    }
}
