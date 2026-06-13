<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\Request;

use App\Domain\Models\Request\RequestEstimatedCostUsd;
use InvalidArgumentException;
use Tests\TestCase;
use TypeError;

class RequestEstimatedCostUsdTest extends TestCase
{
    public function test_can_create_estimated_cost_usd_with_valid_value(): void
    {
        $cost = new RequestEstimatedCostUsd('0.12345');

        $this->assertSame('0.12345', $cost->getValue());
    }

    public function test_can_create_estimated_cost_usd_with_zero(): void
    {
        $cost = new RequestEstimatedCostUsd('0');

        $this->assertSame('0', $cost->getValue());
    }

    public function test_can_create_estimated_cost_usd_with_max_value(): void
    {
        $cost = new RequestEstimatedCostUsd('999.99999');

        $this->assertSame('999.99999', $cost->getValue());
    }

    public function test_throw_exception_with_negative_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('想定コスト(米ドル) は 0 ～ 999.99999 の範囲で入力してください。');

        new RequestEstimatedCostUsd('-0.00001');
    }

    public function test_throw_exception_when_over_max_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RequestEstimatedCostUsd('1000.00000');
    }

    public function test_throw_exception_when_scale_too_long(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('想定コスト(米ドル) は最大 8 桁（うち小数 5 桁）までです。');

        new RequestEstimatedCostUsd('0.123456');
    }

    public function test_throw_exception_when_precision_too_long(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RequestEstimatedCostUsd('1234.12345');
    }

    public function test_throw_exception_when_not_numeric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('想定コスト(米ドル) は数値である必要があります。');

        new RequestEstimatedCostUsd('abc');
    }

    public function test_throw_type_error_when_null(): void
    {
        $this->expectException(TypeError::class);

        new RequestEstimatedCostUsd(null);
    }

    public function test_is_greater_than_returns_true(): void
    {
        $cost = new RequestEstimatedCostUsd('0.12346');

        $this->assertTrue($cost->isGreaterThan('0.12345'));
    }

    public function test_is_greater_than_returns_false_when_less(): void
    {
        $cost = new RequestEstimatedCostUsd('0.12344');

        $this->assertFalse($cost->isGreaterThan('0.12345'));
    }
}
