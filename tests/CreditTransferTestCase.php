<?php

declare(strict_types=1);

namespace Iso20022\Pain\Test;

use DateTimeImmutable;
use DateTimeZone;
use Iso20022\Pain\Bank;
use Iso20022\Pain\BankAccount;
use Iso20022\Pain\ChargeBearer;
use Iso20022\Pain\CustomerCreditTransferInitiationVersion;
use Iso20022\Pain\Party;
use Iso20022\Pain\SchemaValidator;
use Iso20022\Pain\Transaction;
use PHPUnit\Framework\TestCase;

/**
 * Shared fixtures for the credit transfer tests ported from bank4j (CreditTransferV03Test /
 * CreditTransferV09Test). Expected documents live in tests/fixtures as extracted from the Java tests.
 */
abstract class CreditTransferTestCase extends TestCase
{
    abstract protected function version(): CustomerCreditTransferInitiationVersion;

    protected function newYear2021(): DateTimeImmutable
    {
        return new DateTimeImmutable('2021-01-01 00:00:00');
    }

    protected function nextDay(): DateTimeImmutable
    {
        return new DateTimeImmutable('2021-01-02');
    }

    protected function nextDayMidnightUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('2021-01-02 00:00:00', new DateTimeZone('UTC'));
    }

    protected function banqueDeFrance(): Party
    {
        return Bank::simpleParty()->name('Banque de France')->build();
    }

    protected function debtorAccountWithAllFields(): BankAccount
    {
        return Bank::simpleBankAccount()
            ->iban('FR7630001007941234567890185')
            ->bic('BDFEFRPPXXX')
            ->name('Compte de la Banque de France')
            ->build();
    }

    /**
     * @return array<\Iso20022\Pain\Transaction>
     */
    protected function transactionsWithAllFields(): array
    {
        $transactions = [];
        for ($i = 1; $i < 10; $i++) {
            $transactions[] = Bank::simpleTransaction()
                ->party(Bank::simpleParty()
                    ->name('Debtor ' . $i)
                    ->postalAddress(Bank::simplePostalAddress()
                        ->type('ADDR')
                        ->department('Dept')
                        ->subDepartment('SubDept')
                        ->streetName('115 rue de Sèvres')
                        ->postCode('75006')
                        ->townName('Paris')
                        ->countrySubDivision('IDF')
                        ->country('FR')
                        ->build())
                    ->build())
                ->account(Bank::simpleBankAccount()
                    ->iban('FR7610011000201234567890188')
                    ->bic('PSSTFRPP')
                    ->name('Debtor Account ' . $i)
                    ->build())
                ->amount($i . '12.34')
                ->currency('EUR')
                ->endToEndId('ENDTOEND' . $i)
                ->id('ID' . $i)
                ->chargeBearerCode(ChargeBearer::CRED)
                ->remittanceInformationUnstructured(['My unstructured remittance information'])
                ->build();
        }

        return $transactions;
    }

    protected function transactionWithAccountOtherId(): Transaction
    {
        return Bank::simpleTransaction()
            ->party(Bank::simpleParty()->name('Debtor')->build())
            ->account(Bank::simpleBankAccount()
                ->otherId('1234567890')
                ->bic('PSSTFRPP')
                ->name('Debtor Account')
                ->build())
            ->amount('12.34')
            ->currency('EUR')
            ->endToEndId('ENDTOEND1')
            ->id('ID1')
            ->build();
    }

    /**
     * @return array<\Iso20022\Pain\Transaction>
     */
    protected function transactionsWithUnstructuredAddress(): array
    {
        $transactions = [];
        for ($i = 1; $i < 10; $i++) {
            $transactions[] = Bank::simpleTransaction()
                ->party(Bank::simpleParty()
                    ->name('Beneficiary ' . $i)
                    ->postalAddress(Bank::simplePostalAddress()
                        ->addressLine('115 rue de Sèvres')
                        ->addressLine('75006 Paris')
                        ->country('FR')
                        ->build())
                    ->build())
                ->account(Bank::simpleBankAccount()
                    ->iban('FR7610011000201234567890188')
                    ->bic('PSSTFRPP')
                    ->build())
                ->amount($i . '12.34')
                ->currency('EUR')
                ->endToEndId('ENDTOEND' . $i)
                ->id('ID' . $i)
                ->build();
        }

        return $transactions;
    }

    /**
     * @return array<\Iso20022\Pain\Transaction>
     */
    protected function transactionsWithoutOptionalFields(): array
    {
        $transactions = [];
        for ($i = 1; $i < 10; $i++) {
            $transactions[] = Bank::simpleTransaction()
                ->party(Bank::simpleParty()->name('Beneficiary ' . $i)->build())
                ->account(Bank::simpleBankAccount()->iban('FR7610011000201234567890188')->build())
                ->amount($i . '12.34')
                ->currency('EUR')
                ->endToEndId('ENDTOEND' . $i)
                ->build();
        }

        return $transactions;
    }

    protected function assertMatchesFixture(string $fixture, string $xml): void
    {
        $expected = file_get_contents(__DIR__ . '/fixtures/' . $fixture . '.xml');
        self::assertNotFalse($expected, 'fixture ' . $fixture . ' missing');
        self::assertSame($expected, $xml);
    }

    protected function assertValid(string $xml): void
    {
        SchemaValidator::validateCreditTransfer($this->version(), $xml);
        $this->addToAssertionCount(1);
    }
}
