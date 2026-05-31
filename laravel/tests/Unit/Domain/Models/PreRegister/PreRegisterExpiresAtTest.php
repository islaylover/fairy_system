<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\PreRegister;

use App\Domain\Models\PreRegister\PreRegisterExpiresAt;
use DateTime;
use InvalidArgumentException;
use Tests\TestCase;

class PreRegisterExpiresAtTest extends TestCase
{
    public function test_can_create_expires_at_with_valid_datetime_string(): void
    {
        $expiresAt = new PreRegisterExpiresAt('2026-05-10 12:00:00');

        $this->assertInstanceOf(DateTime::class, $expiresAt->getValue());
        $this->assertSame('2026-05-10 12:00:00', (string) $expiresAt);
    }

    public function test_can_create_expires_at_with_datetime_interface(): void
    {
        $expiresAt = new PreRegisterExpiresAt(new DateTime('2026-05-10 12:00:00'));

        $this->assertSame('2026-05-10 12:00:00', (string) $expiresAt);
    }

    public function test_throws_exception_when_datetime_format_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PreRegisterExpiresAt('2026-05-10');
    }
}
