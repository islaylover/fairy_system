<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Models\Request;

use App\Domain\Models\Request\RequestResultText;
use InvalidArgumentException;
use Tests\TestCase;

class RequestResultTextTest extends TestCase
{
    public function test_can_create_result_text(): void
    {
        $requestResultText = new RequestResultText('アメリカとイスラエルがイランとの停戦合意を結ぶ上で進展しない主な原因は、相互の信頼不足と安全保障に関する懸念です。特に、イランの核開発活動と地域での軍事的影響力がアメリカとイスラエルにとって重大な脅威と見なされており、これに対する具体的な措置が合意されない限り、停戦の実現は難航しています。さらに、両国間の外交交渉における意見の相違も障害となっています。');

        $this->assertSame('アメリカとイスラエルがイランとの停戦合意を結ぶ上で進展しない主な原因は、相互の信頼不足と安全保障に関する懸念です。特に、イランの核開発活動と地域での軍事的影響力がアメリカとイスラエルにとって重大な脅威と見なされており、これに対する具体的な措置が合意されない限り、停戦の実現は難航しています。さらに、両国間の外交交渉における意見の相違も障害となっています。', $requestResultText->getValue());
    }

    public function test_can_convert_result_text_to_string(): void
    {
        $requestSourceText = new RequestResultText('アメリカとイスラエルがイランとの停戦合意を結ぶ上で進展しない主な原因は、相互の信頼不足と安全保障に関する懸念です。特に、イランの核開発活動と地域での軍事的影響力がアメリカとイスラエルにとって重大な脅威と見なされており、これに対する具体的な措置が合意されない限り、停戦の実現は難航しています。さらに、両国間の外交交渉における意見の相違も障害となっています。');

        $this->assertSame('アメリカとイスラエルがイランとの停戦合意を結ぶ上で進展しない主な原因は、相互の信頼不足と安全保障に関する懸念です。特に、イランの核開発活動と地域での軍事的影響力がアメリカとイスラエルにとって重大な脅威と見なされており、これに対する具体的な措置が合意されない限り、停戦の実現は難航しています。さらに、両国間の外交交渉における意見の相違も障害となっています。', (string) $requestSourceText);
    }

    public function test_throws_exception_when_result_text_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RequestResultText('');
    }

    public function test_throws_exception_when_result_text_is_too_long(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RequestResultText(str_repeat('a', 50001));
    }
}
