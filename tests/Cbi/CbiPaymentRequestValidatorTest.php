<?php

declare(strict_types=1);

namespace Iso20022\Pain\Test\Cbi;

use DateTimeImmutable;
use Iso20022\Pain\Bank;
use Iso20022\Pain\Cbi\CbiIssue;
use Iso20022\Pain\Cbi\CbiPaymentRequestValidator;
use Iso20022\Pain\ChargeBearer;
use Iso20022\Pain\CreditTransferOperationBuilder;
use Iso20022\Pain\CustomerCreditTransferInitiationVersion as Version;
use Iso20022\Pain\Transaction;
use PHPUnit\Framework\TestCase;

/**
 * CBI STIP-MO-001 §3.9.1.2 application checks, with the SEPA vs FAST differences.
 */
final class CbiPaymentRequestValidatorTest extends TestCase
{
    private const DEBTOR_IBAN = 'IT60X0542811101000000123456';
    private const CREDITOR_IBAN = 'IT35X0100503201000000012345';
    private const TODAY = '2026-10-14';

    private CbiPaymentRequestValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CbiPaymentRequestValidator();
    }

    /**
     * @param array<string> $remittance
     */
    private function transaction(
        string $amount,
        string $endToEndId = 'E2E-1',
        ?string $categoryPurpose = 'SALA',
        array $remittance = ['Stipendio'],
    ): Transaction {
        return Bank::simpleTransaction()
            ->party(Bank::simpleParty()->name('Dipendente')->build())
            ->account(Bank::simpleBankAccount()->iban(self::CREDITOR_IBAN)->build())
            ->amount($amount)
            ->currency('EUR')
            ->endToEndId($endToEndId)
            ->categoryPurposeCode($categoryPurpose)
            ->remittanceInformationUnstructured($remittance)
            ->build();
    }

    private function builder(string $executionDate = self::TODAY): CreditTransferOperationBuilder
    {
        return Bank::xmlCreditTransferSepa(Version::CBI_00_04_01)
            ->debtor(Bank::simpleParty()->name('Azienda')->otherId('ABCDE12F', 'CBI')->build())
            ->debtorAccount(Bank::simpleBankAccount()->iban(self::DEBTOR_IBAN)->build())
            ->id('MSG-1')
            ->requestedExecutionDate(new DateTimeImmutable($executionDate));
    }

    /**
     * @param array<\Iso20022\Pain\Cbi\CbiIssue> $issues
     * @return array<string>
     */
    private function codes(array $issues): array
    {
        return array_map(static fn (CbiIssue $i): string => $i->code, $issues);
    }

    private function today(): DateTimeImmutable
    {
        return new DateTimeImmutable(self::TODAY);
    }

    public function testValidSepaBatchHasNoIssues(): void
    {
        $transfer = $this->builder('2026-10-20')->transactions([$this->transaction('150000.00'), $this->transaction('0.01', 'E2E-2')])->build();

        self::assertSame([], $this->validator->validate($transfer, $this->today()));
    }

    public function testSepaAmountAboveLimitIsRejected(): void
    {
        $transfer = $this->builder()->transaction($this->transaction('1000000000.00'))->build();

        self::assertSame(['amount_over_limit'], $this->codes($this->validator->validate($transfer, $this->today())));
    }

    public function testFastAcceptsExactlyTheLimit(): void
    {
        $transfer = $this->builder()->instantPayment(true)->transaction($this->transaction('100000.00'))->build();

        self::assertSame([], $this->validator->validate($transfer, $this->today()));
    }

    public function testFastRejectsAmountAboveOneHundredThousand(): void
    {
        $transfer = $this->builder()->instantPayment(true)->transaction($this->transaction('100000.01'))->build();

        $issues = $this->validator->validate($transfer, $this->today());
        self::assertSame(['amount_over_limit'], $this->codes($issues));
        self::assertSame(0, $issues[0]->transactionIndex);
        self::assertSame('E2E-1', $issues[0]->endToEndId);
    }

    public function testFastCannotBeScheduledInTheFuture(): void
    {
        $transfer = $this->builder('2026-10-20')->instantPayment(true)->transaction($this->transaction('10'))->build();

        self::assertSame(['execution_date_future'], $this->codes($this->validator->validate($transfer, $this->today())));
    }

    public function testFastAllowsASingleRemittanceLine(): void
    {
        $transfer = $this->builder()->instantPayment(true)
            ->transaction($this->transaction('10', 'E2E-1', 'SALA', ['Riga 1', 'Riga 2']))
            ->build();

        self::assertSame(['remittance_lines'], $this->codes($this->validator->validate($transfer, $this->today())));
    }

    public function testSepaAllowsSeveralRemittanceLines(): void
    {
        $transfer = $this->builder()->transaction($this->transaction('10', 'E2E-1', 'SALA', ['Riga 1', 'Riga 2']))->build();

        self::assertSame([], $this->validator->validate($transfer, $this->today()));
    }

    public function testExecutionDateInThePastIsRejected(): void
    {
        $transfer = $this->builder('2026-10-13')->transaction($this->transaction('10'))->build();

        self::assertSame(['execution_date_past'], $this->codes($this->validator->validate($transfer, $this->today())));
    }

    public function testDuplicateEndToEndIdIsRejected(): void
    {
        $transfer = $this->builder()->transactions([$this->transaction('10'), $this->transaction('20')])->build();

        self::assertSame(['end_to_end_id_duplicate'], $this->codes($this->validator->validate($transfer, $this->today())));
    }

    public function testInvalidIdentifiersAreRejected(): void
    {
        $transfer = $this->builder()->id('/MSG//1')->transaction($this->transaction('10', 'E2E#1'))->build();

        self::assertSame(['message_id', 'end_to_end_id'], $this->codes($this->validator->validate($transfer, $this->today())));
    }

    public function testCategoryPurposeIsRequiredForItalianCreditors(): void
    {
        $transfer = $this->builder()->transaction($this->transaction('10', 'E2E-1', null))->build();

        self::assertSame(['category_purpose'], $this->codes($this->validator->validate($transfer, $this->today())));
    }

    public function testDebtorChecks(): void
    {
        $transfer = Bank::xmlCreditTransferSepa(Version::CBI_00_04_01)
            ->debtor(Bank::simpleParty()->name('Azienda')->otherId('SHORT', 'CBI')->build())
            ->debtorAccount(Bank::simpleBankAccount()->iban('FR7630001007941234567890185')->build())
            ->requestedExecutionDate($this->today())
            ->chargeBearer(ChargeBearer::SHAR)
            ->transaction($this->transaction('10'))
            ->build();

        self::assertSame(['charge_bearer', 'debtor_cuc', 'debtor_abi'], $this->codes($this->validator->validate($transfer, $this->today())));
    }

    public function testInvalidCreditorIbanAndRemittanceLength(): void
    {
        $transaction = Bank::simpleTransaction()
            ->party(Bank::simpleParty()->name('Dipendente')->build())
            ->account(Bank::simpleBankAccount()->iban('IT60X0542811101000000123457')->build())
            ->amount('10')
            ->currency('EUR')
            ->endToEndId('E2E-1')
            ->remittanceInformationUnstructured([str_repeat('x', 141)])
            ->build();
        $transfer = $this->builder()->transaction($transaction)->build();

        self::assertSame(['creditor_iban', 'remittance_too_long'], $this->codes($this->validator->validate($transfer, $this->today())));
    }
}
