<?php

declare(strict_types=1);

namespace Iso20022\Pain\Test\Validator\ConstraintValidators;

use Iso20022\Pain\Bank;
use Iso20022\Pain\TransactionBuilder;
use Iso20022\Pain\Util\Iso20022ReferenceElementValidator;
use Iso20022\Pain\Validator\Constraints\Iso20022CharacterSet;
use Iso20022\Pain\Validator\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Port of io.inisos.bank4j.validator.constraintvalidators.Iso20022CharacterSetValidatorTest.
 */
final class Iso20022CharacterSetValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    private function defaultTransaction(): TransactionBuilder
    {
        return Bank::simpleTransaction()
            ->account(Bank::simpleBankAccount()->iban('FR7630001007941234567890185')->build())
            ->amount('200')
            ->currency('EUR');
    }

    public function testIsValidPassWithValidIso20022CharacterSet(): void
    {
        $transaction = $this->defaultTransaction()->endToEndId('MyEndToEndId')->build();

        self::assertSame([], $this->validator->validate($transaction));
    }

    public function testIsValidFailWithInvalidIso20022CharacterSet(): void
    {
        $transaction = $this->defaultTransaction()->endToEndId('/MyEndToEnd#Id')->build();

        $violations = $this->validator->validate($transaction);
        self::assertNotEmpty($violations);
        self::assertInstanceOf(Iso20022CharacterSet::class, $violations[0]->getConstraint());
    }

    public function testIsValidPassWithSanitizedInvalidIso20022CharacterSet(): void
    {
        $transaction = $this->defaultTransaction()
            ->endToEndId(Iso20022ReferenceElementValidator::sanitizeToCharacterSet('/MyEndToEnd#Id'))
            ->build();

        self::assertSame([], $this->validator->validate($transaction));
    }
}
