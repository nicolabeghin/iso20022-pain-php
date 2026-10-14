<?php

declare(strict_types=1);

namespace Iso20022\Pain\Test;

use Iso20022\Pain\Bank;
use Iso20022\Pain\ChargeBearer;
use Iso20022\Pain\CustomerCreditTransferInitiationVersion;
use Iso20022\Pain\Priority;

/**
 * Port of io.inisos.bank4j.CreditTransferV09Test: the generated document must match bank4j's
 * output byte for byte and validate against pain.001.001.09.xsd.
 */
final class CreditTransferV09Test extends CreditTransferTestCase
{
    protected function version(): CustomerCreditTransferInitiationVersion
    {
        return CustomerCreditTransferInitiationVersion::V09;
    }

    public function testWithAllFields(): void
    {
        $xml = Bank::xmlCreditTransferSepa($this->version())
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

        $this->assertMatchesFixture('v09_test_with_all_fields', $xml);
        $this->assertValid($xml);
    }

    public function testWithAllFieldsAndBatchBooking(): void
    {
        $xml = Bank::xmlCreditTransferSepa($this->version())
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

        $this->assertMatchesFixture('v09_test_with_all_fields_and_batchBooking', $xml);
        $this->assertValid($xml);
    }

    public function testWithAccountOtherId(): void
    {
        $xml = Bank::xmlCreditTransferSepa($this->version())
            ->debtor($this->banqueDeFrance())
            ->debtorAccount($this->debtorAccountWithAllFields())
            ->transaction($this->transactionWithAccountOtherId())
            ->id('MYID')
            ->creationDateTime($this->newYear2021())
            ->requestedExecutionDate($this->nextDay())
            ->build()
            ->marshal();

        $this->assertMatchesFixture('v09_test_with_account_other_id', $xml);
        $this->assertValid($xml);
    }

    public function testWithUnstructuredAddress(): void
    {
        $xml = Bank::xmlCreditTransferSepa($this->version())
            ->debtor($this->banqueDeFrance())
            ->debtorAccount(Bank::simpleBankAccount()->iban('FR7630001007941234567890185')->bic('BDFEFRPPXXX')->build())
            ->transactions($this->transactionsWithUnstructuredAddress())
            ->id('MYID')
            ->creationDateTime($this->newYear2021())
            ->requestedExecutionDate($this->nextDay())
            ->build()
            ->marshal();

        $this->assertMatchesFixture('v09_test_with_unstructured_address', $xml);
        $this->assertValid($xml);
    }

    public function testWithoutOptionalFields(): void
    {
        $xml = Bank::xmlCreditTransferSepa($this->version())
            ->debtor($this->banqueDeFrance())
            ->debtorAccount(Bank::simpleBankAccount()->iban('FR7630001007941234567890185')->build())
            ->transactions($this->transactionsWithoutOptionalFields())
            ->creationDateTime($this->newYear2021())
            ->requestedExecutionDate($this->nextDay())
            ->build()
            ->marshal();

        $this->assertMatchesFixture('v09_test_without_optional_fields', $xml);
        $this->assertValid($xml);
    }

    public function testWithRequestedExecutionDateTime(): void
    {
        $xml = Bank::xmlCreditTransferSepa($this->version())
            ->debtor($this->banqueDeFrance())
            ->debtorAccount(Bank::simpleBankAccount()->iban('FR7630001007941234567890185')->build())
            ->transactions($this->transactionsWithoutOptionalFields())
            ->creationDateTime($this->newYear2021())
            ->requestedExecutionDateTime($this->nextDayMidnightUtc())
            ->build()
            ->marshal();

        $this->assertMatchesFixture('v09_test_with_requested_execution_date_time', $xml);
        $this->assertValid($xml);
    }

    public function testWithInstantPayment(): void
    {
        $xml = Bank::xmlCreditTransferSepa($this->version())
            ->debtor($this->banqueDeFrance())
            ->debtorAccount(Bank::simpleBankAccount()->iban('FR7630001007941234567890185')->build())
            ->transactions($this->transactionsWithoutOptionalFields())
            ->creationDateTime($this->newYear2021())
            ->requestedExecutionDateTime($this->nextDayMidnightUtc())
            ->instantPayment(true)
            ->build()
            ->marshal();

        $this->assertMatchesFixture('v09_test_with_instant_payment', $xml);
        $this->assertValid($xml);
    }
}
