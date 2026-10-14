<?php

declare(strict_types=1);

namespace Iso20022\Pain\Test\Validator\ConstraintValidators;

use Iso20022\Pain\Impl\SimpleBankAccountBuilder;
use Iso20022\Pain\Validator\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Port of io.inisos.bank4j.validator.constraintvalidators.IBANValidatorTest.
 */
final class IBANValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testIsValidPassWithValidIban(): void
    {
        $account = (new SimpleBankAccountBuilder())->iban('FR7630001007941234567890185')->build();

        self::assertCount(0, $this->validator->validate($account));
    }

    public function testIsValidFailWithInvalidIban(): void
    {
        $account = (new SimpleBankAccountBuilder())->iban('x')->build();

        $violations = $this->validator->validate($account);
        self::assertCount(1, $violations);
        self::assertSame('must be a valid IBAN', $violations[0]->getMessage());
    }

    public function testIsValidFailWithWrongCheckDigits(): void
    {
        $account = (new SimpleBankAccountBuilder())->iban('FR7630001007941234567890186')->build();

        self::assertCount(1, $this->validator->validate($account));
    }
}
