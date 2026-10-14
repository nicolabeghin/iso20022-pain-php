<?php

declare(strict_types=1);

namespace Iso20022\Pain\Test\Cbi;

use InvalidArgumentException;
use Iso20022\Pain\Cbi\ItalianIban;
use Iso20022\Pain\Util\IbanUtil;
use PHPUnit\Framework\TestCase;

final class ItalianIbanTest extends TestCase
{
    public function testComponents(): void
    {
        self::assertSame('X', ItalianIban::cin('IT60X0542811101000000123456'));
        self::assertSame('05428', ItalianIban::abi('IT60X0542811101000000123456'));
        self::assertSame('11101', ItalianIban::cab('IT60X0542811101000000123456'));
        self::assertSame('01005', ItalianIban::abi('IT35X0100503201000000012345'));
        self::assertSame('03069', ItalianIban::abi('IT65Y0306909606100000012345'));
    }

    public function testForeignIbanIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ItalianIban::abi('DE89370400440532013000');
    }

    public function testWrongCheckDigitsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ItalianIban::abi('IT61X0542811101000000123456');
    }

    public function testNormalizeAcceptsUserInput(): void
    {
        self::assertSame('IT60X0542811101000000123456', IbanUtil::normalize(' it60 x054 2811 1010 0000 0123 456 '));
        self::assertTrue(IbanUtil::isValid(IbanUtil::normalize('IT60 X054 2811 1010 0000 0123 456')));
        self::assertFalse(IbanUtil::isValid('IT60 X054 2811 1010 0000 0123 456'), 'validation itself is strict like iban4j');
    }
}
