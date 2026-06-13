<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\Request;

use App\Domain\Models\Request\RequestSourceText;
use InvalidArgumentException;
use Tests\TestCase;

class RequestSourceTextTest extends TestCase
{
    public function test_can_create_source_text(): void
    {
        $requestSourceText = new RequestSourceText('2026年5月31日時点でのアメリカ＆イスラエル VS イランの停戦合意がなかなか進まない原因は何？');

        $this->assertSame('2026年5月31日時点でのアメリカ＆イスラエル VS イランの停戦合意がなかなか進まない原因は何？', $requestSourceText->getValue());
    }

    public function test_can_convert_source_text_to_string(): void
    {
        $requestSourceText = new RequestSourceText('translate');

        $this->assertSame('translate', (string) $requestSourceText);
    }

    public function test_throws_exception_when_source_text_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RequestSourceText('');
    }

    public function test_throws_exception_when_source_text_is_too_long(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RequestSourceText(str_repeat('a', 50001));
    }
}
