<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\Request;

use App\Domain\Enums\RequestStatusEnum;
use App\Domain\Models\Request\RequestStatus;
use InvalidArgumentException;
use Tests\TestCase;

class RequestStatusTest extends TestCase
{
    public function test_can_create_status_from_int(): void
    {
        $status = new RequestStatus(0);

        $this->assertSame(0, $status->getValue());
        $this->assertSame('0', (string) $status->getValue());
    }

    public function test_can_create_from_enum(): void
    {
        $status = new RequestStatus(RequestStatusEnum::Done);

        $this->assertSame(2, $status->getValue());
        $this->assertSame('2', (string) $status->getValue());
    }

    public function test_create_pending_status(): void
    {
        $status = new RequestStatus(RequestStatusEnum::Pending);

        $this->assertSame(RequestStatusEnum::Pending->value, $status->getValue());
    }

    public function test_create_processing_status(): void
    {
        $status = new RequestStatus(RequestStatusEnum::Processing);

        $this->assertSame(RequestStatusEnum::Processing->value, $status->getValue());
    }

    public function test_create_done_status(): void
    {
        $status = new RequestStatus(RequestStatusEnum::Done);

        $this->assertSame(RequestStatusEnum::Done->value, $status->getValue());
    }

    public function test_create_failed_status(): void
    {
        $status = new RequestStatus(RequestStatusEnum::Failed);

        $this->assertSame(RequestStatusEnum::Failed->value, $status->getValue());
    }

    public function test_throws_invalid_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('不正なリクエストステータス: 3');

        $sattus = new RequestStatus(3);
    }
}
