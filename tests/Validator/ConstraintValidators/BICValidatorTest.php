<?php

declare(strict_types=1);

namespace Iso20022\Pain\Test\Validator\ConstraintValidators;

use Iso20022\Pain\Impl\SimpleBankAccountBuilder;
use Iso20022\Pain\Validator\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Port of io.inisos.bank4j.validator.constraintvalidators.BICValidatorTest.
 */
final class BICValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testIsValidPassWithValidBic8(): void
    {
        $account = (new SimpleBankAccountBuilder())->iban('FR7630001007941234567890185')->bic('BDFEFRPP')->build();

        self::assertCount(0, $this->validator->validate($account));
    }

    public function testIsValidPassWithValidBic11(): void
    {
        $account = (new SimpleBankAccountBuilder())->iban('FR7630001007941234567890185')->bic('BDFEFRPPXXX')->build();

        self::assertCount(0, $this->validator->validate($account));
    }

    public function testIsValidFailWithInvalidBic(): void
    {
        $account = (new SimpleBankAccountBuilder())->iban('FR7630001007941234567890185')->bic('x')->build();

        $violations = $this->validator->validate($account);
        self::assertCount(1, $violations);
        self::assertSame('must be a valid BIC', $violations[0]->getMessage());
        self::assertSame('bic', $violations[0]->getPropertyPath());
    }
}
