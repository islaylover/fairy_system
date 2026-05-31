<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\PreRegister;

use App\Domain\Models\PreRegister\PreRegisterId;
use Tests\TestCase;

class PreRegisterIdTest extends TestCase
{
    public function test_can_create_id_with_valid_value(): void
    {
        $id = new PreRegisterId(1);

        $this->assertSame(1, $id->getValue());
    }
}
