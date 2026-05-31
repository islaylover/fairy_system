<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\User;

use App\Domain\Models\User\UserId;
use Tests\TestCase;

class UserIdTest extends TestCase
{
    public function test_can_create_id_with_valid_value(): void
    {
        $id = new UserId(1);

        $this->assertSame(1, $id->getValue());
    }
}
