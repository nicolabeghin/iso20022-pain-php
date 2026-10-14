<?php

declare(strict_types=1);

namespace Iso20022\Pain\Test;

use Iso20022\Pain\Bank;
use Iso20022\Pain\ChargeBearer;
use Iso20022\Pain\CustomerCreditTransferInitiationVersion;
use Iso20022\Pain\Priority;
use LogicException;

/**
 * Port of io.inisos.bank4j.CreditTransferV03Test: byte-for-byte match with bank4j's output and
 * validation against pain.001.001.03.xsd.
 */
final class CreditTransferV03Test extends CreditTransferTestCase
{
    protected function version(): CustomerCreditTransferInitiationVersion
    {
        return CustomerCreditTransferInitiationVersion::V03;
    }

    public function testWithAllFields(): void
    {
        $xml = Bank::xmlCreditTransferSepa()
            ->instructionPriority(Priority::HIGH)
            ->debtor($this->banqueDeFrance())
            ->debtorAccount($this->debtorAccountWithAllFields())
            ->transactions($this->transactionsWithAllFields())
            ->id('MYID')
            ->creationDateTime($this->newYear2021())
            ->requestedExecutionDate($this->nextDay())
            ->chargeBearer(ChargeBearer::DEBT)
            ->build()
            ->marshal();

        $this->assertMatchesFixture('v03_test_with_all_fields', $xml);
        $this->assertValid($xml);
    }

    public function testWithAllFieldsAndBatchBooking(): void
    {
        $xml = Bank::xmlCreditTransferSepa()
            ->debtor($this->banqueDeFrance())
            ->debtorAccount($this->debtorAccountWithAllFields())
            ->transactions($this->transactionsWithAllFields())
            ->id('MYID')
            ->creationDateTime($this->newYear2021())
            ->requestedExecutionDate($this->nextDay())
            ->chargeBearer(ChargeBearer::DEBT)
            ->batchBooking(true)
            ->build()
            ->marshal();

        $this->assertMatchesFixture('v03_test_with_all_fields_and_batchBooking', $xml);
        $this->assertValid($xml);
    }

    public function testWithAccountOtherId(): void
    {
        $xml = Bank::xmlCreditTransferSepa()
            ->debtor($this->banqueDeFrance())
            ->debtorAccount($this->debtorAccountWithAllFields())
            ->transaction($this->transactionWithAccountOtherId())
            ->id('MYID')
            ->creationDateTime($this->newYear2021())
            ->requestedExecutionDate($this->nextDay())
            ->build()
            ->marshal();

        $this->assertMatchesFixture('v03_test_with_account_other_id', $xml);
        $this->assertValid($xml);
    }

    public function testWithUnstructuredAddress(): void
    {
        $xml = Bank::xmlCreditTransferSepa()
            ->debtor($this->banqueDeFrance())
            ->debtorAccount(Bank::simpleBankAccount()->iban('FR7630001007941234567890185')->bic('BDFEFRPPXXX')->build())
            ->transactions($this->transactionsWithUnstructuredAddress())
            ->id('MYID')
            ->creationDateTime($this->newYear2021())
            ->requestedExecutionDate($this->nextDay())
            ->build()
            ->marshal();

        $this->assertMatchesFixture('v03_test_with_unstructured_address', $xml);
        $this->assertValid($xml);
    }

    public function testWithoutOptionalFields(): void
    {
        $xml = Bank::xmlCreditTransferSepa()
            ->debtor($this->banqueDeFrance())
            ->debtorAccount(Bank::simpleBankAccount()->iban('FR7630001007941234567890185')->build())
            ->transactions($this->transactionsWithoutOptionalFields())
            ->creationDateTime($this->newYear2021())
            ->requestedExecutionDate($this->nextDay())
            ->build()
            ->marshal();

        $this->assertMatchesFixture('v03_test_without_optional_fields', $xml);
        $this->assertValid($xml);
    }

    public function testInstantPaymentIsNotAllowedForV03(): void
    {
        $builder = Bank::xmlCreditTransferSepa();

        $this->expectException(LogicException::class);
        $builder->instantPayment(true);
    }

    public function testRequestedExecutionDateTimeIsNotAllowedForV03(): void
    {
        $builder = Bank::xmlCreditTransferSepa();

        $this->expectException(LogicException::class);
        $builder->requestedExecutionDateTime($this->nextDayMidnightUtc());
    }
}
