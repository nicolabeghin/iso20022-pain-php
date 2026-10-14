<?php

declare(strict_types=1);

namespace Iso20022\Pain\Test\Util;

use Iso20022\Pain\Util\Iso20022ReferenceElementValidator;
use PHPUnit\Framework\TestCase;

/**
 * Port of io.inisos.bank4j.util.Iso20022ReferenceElementValidatorTest.
 */
final class Iso20022ReferenceElementValidatorTest extends TestCase
{
    public function testCharactersetWithValidInput(): void
    {
        self::assertTrue(Iso20022ReferenceElementValidator::isValidCharacterSet('ABCDEF1234'));
    }

    public function testCharactersetWithInvalidContent(): void
    {
        self::assertFalse(Iso20022ReferenceElementValidator::isValidCharacterSet('ABC#DEF1234'));
    }

    public function testCharactersetWithInvalidStart(): void
    {
        self::assertFalse(Iso20022ReferenceElementValidator::isValidCharacterSet('/ABCDEF1234'));
    }

    public function testCharactersetWithInvalidEnd(): void
    {
        self::assertFalse(Iso20022ReferenceElementValidator::isValidCharacterSet('ABCDEF1234/'));
    }

    public function testCharactersetWithNullOrEmptyInput(): void
    {
        self::assertFalse(Iso20022ReferenceElementValidator::isValidCharacterSet(null));
        self::assertFalse(Iso20022ReferenceElementValidator::isValidCharacterSet(''));
    }

    public function testCharactersetWithInvalidInputSanitized(): void
    {
        self::assertSame('AB.CDE F.123', Iso20022ReferenceElementValidator::sanitizeToCharacterSet('AB#CDE F\\123'));
    }

    public function testCharactersetWithInvalidInputSanitizedWithCustomReplacements(): void
    {
        $customReplacements = ['#' => 'l', 'ø' => 'o', 'l' => 'i', '!' => '?', '\\' => ' '];

        self::assertSame(
            'Hello World?',
            Iso20022ReferenceElementValidator::sanitizeToCharacterSet('/He##ø\\Wørld!/', $customReplacements),
        );
    }
}
