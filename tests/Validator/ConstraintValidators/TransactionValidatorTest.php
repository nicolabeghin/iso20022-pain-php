<?php

declare(strict_types=1);

namespace Iso20022\Pain\Test\Validator\ConstraintValidators;

use Iso20022\Pain\Bank;
use Iso20022\Pain\TransactionBuilder;
use Iso20022\Pain\Validator\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Port of io.inisos.bank4j.validator.constraintvalidators.TransactionValidatorTest.
 */
final class TransactionValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    private static function buildDefaultTransaction(): TransactionBuilder
    {
        return Bank::simpleTransaction()
            ->account(Bank::simpleBankAccount()->iban('FR7630001007941234567890185')->build())
            ->amount('200')
            ->currency('EUR')
            ->endToEndId('MyEndToEndId');
    }

    public function testIsValidPassWithValidRemittanceInformation(): void
    {
        $transaction = self::buildDefaultTransaction()
            ->remittanceInformationUnstructured(['My unstructured remittance information'])
            ->build();

        self::assertSame([], $this->validator->validate($transaction));
    }

    public function testIsValidFailWithInvalidRemittanceInformation(): void
    {
        $transaction = self::buildDefaultTransaction()
            ->remittanceInformationUnstructured([str_repeat('k', 141)])
            ->build();

        $violations = $this->validator->validate($transaction);
        self::assertNotEmpty($violations);
        self::assertSame('size must be between 0 and 140', $violations[0]->getMessage());
    }

    public function testIsValidFailWithErefExceedingMaxLength(): void
    {
        $transaction = self::buildDefaultTransaction()
            ->endToEndId('abcdefghijklmnopqrstuvwxyzabcdefghij') // 36 characters, max is 35
            ->build();

        self::assertNotEmpty($this->validator->validate($transaction));
    }
}
