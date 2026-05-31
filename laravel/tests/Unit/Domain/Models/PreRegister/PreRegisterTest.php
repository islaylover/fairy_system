<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\PreRegister;

use App\Domain\Models\PreRegister\PreRegister;
use App\Domain\Models\PreRegister\PreRegisterEmail;
use App\Domain\Models\PreRegister\PreRegisterExpiresAt;
use App\Domain\Models\PreRegister\PreRegisterId;
use App\Domain\Models\PreRegister\PreRegisterToken;
use Tests\TestCase;

class PreRegisterTest extends TestCase
{
    public function test_can_create_pre_register_entity(): void
    {
        $preRegister = new PreRegister(
            new PreRegisterEmail('test@example.com'),
            new PreRegisterToken('token123'),
            new PreRegisterExpiresAt('2026-05-10 12:00:00'),
            new PreRegisterId(1)
        );

        $this->assertSame(1, $preRegister->getId()?->getValue());
        $this->assertSame('test@example.com', $preRegister->getEmail()->getValue());
        $this->assertSame('token123', $preRegister->getToken()->getValue());
        $this->assertSame('2026-05-10 12:00:00', (string) $preRegister->getExpiresAt());
    }

    public function test_can_create_pre_register_entity_without_id(): void
    {
        $preRegister = new PreRegister(
            new PreRegisterEmail('test@example.com'),
            new PreRegisterToken('token123'),
            new PreRegisterExpiresAt('2026-05-10 12:00:00')
        );

        $this->assertNull($preRegister->getId());
    }
}
