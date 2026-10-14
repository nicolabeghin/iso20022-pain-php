<?php

declare(strict_types=1);

namespace Iso20022\Pain\Test\Cbi;

use DateTimeImmutable;
use DateTimeZone;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use Iso20022\Pain\Bank;
use Iso20022\Pain\BankAccount;
use Iso20022\Pain\ChargeBearer;
use Iso20022\Pain\CustomerCreditTransferInitiationVersion as Version;
use Iso20022\Pain\Impl\XmlException;
use Iso20022\Pain\Party;
use Iso20022\Pain\SchemaValidator;
use Iso20022\Pain\Transaction;
use PHPUnit\Framework\TestCase;

/**
 * CBI "CBIPaymentRequest" 00.04.01 generation: schema validity against the official XSD and the
 * CBI-specific mapping (CUC in InitgPty, ABI in DbtrAgt, FAST service level, mandatory InstrId).
 */
final class CreditTransferCbi000401Test extends TestCase
{
    private const DEBTOR_IBAN = 'IT60X0542811101000000123456'; // ABI 05428
    private const CREDITOR_IBAN = 'IT35X0100503201000000012345';

    private function debtor(): Party
    {
        return Bank::simpleParty()
            ->name('Chiaravalle S.r.l.')
            ->postalAddress(Bank::simplePostalAddress()
                ->streetName('Via Roma 1')
                ->postCode('85100')
                ->townName('Potenza')
                ->country('IT')
                ->build())
            ->otherId('ABCDE12F', 'CBI')
            ->build();
    }

    private function debtorAccount(): BankAccount
    {
        return Bank::simpleBankAccount()->iban(self::DEBTOR_IBAN)->build();
    }

    /**
     * @return array<\Iso20022\Pain\Transaction>
     */
    private function salaryTransactions(int $count = 3): array
    {
        $transactions = [];
        for ($i = 1; $i <= $count; $i++) {
            $transactions[] = Bank::simpleTransaction()
                ->party(Bank::simpleParty()->name('Dipendente ' . $i)->build())
                ->account(Bank::simpleBankAccount()->iban(self::CREDITOR_IBAN)->build())
                ->amount($i . '250.50')
                ->currency('EUR')
                ->endToEndId('CED202609-' . $i)
                ->categoryPurposeCode('SALA')
                ->purposeCode('SALA')
                ->remittanceInformationUnstructured(['Retribuzione Settembre 2026'])
                ->build();
        }

        return $transactions;
    }

    private function creationDateTime(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-14 09:30:00', new DateTimeZone('Europe/Rome'));
    }

    public function testSepaTransferValidatesAgainstTheCbiSchema(): void
    {
        $xml = Bank::xmlCreditTransferSepa(Version::CBI_00_04_01)
            ->debtor($this->debtor())
            ->debtorAccount($this->debtorAccount())
            ->transactions($this->salaryTransactions())
            ->id('CED202609-1-20261014093000')
            ->creationDateTime($this->creationDateTime())
            ->requestedExecutionDate(new DateTimeImmutable('2026-10-27'))
            ->batchBooking(true)
            ->build()
            ->marshal(true);

        SchemaValidator::validateCreditTransfer(Version::CBI_00_04_01, $xml);
        $x = $this->xpath($xml);
        $root = $x->document->documentElement;
        self::assertNotNull($root);
        self::assertSame('CBIPaymentRequest', $root->localName);
        self::assertSame('urn:CBI:xsd:CBIPaymentRequest.00.04.01', $root->namespaceURI);
        self::assertSame('ABCDE12F', $x->evaluate('string(//c:GrpHdr/c:InitgPty/c:Id/c:OrgId/c:Othr/c:Id)'));
        self::assertSame('CBI', $x->evaluate('string(//c:GrpHdr/c:InitgPty/c:Id/c:OrgId/c:Othr/c:Issr)'));
        self::assertSame('3', $x->evaluate('string(//c:GrpHdr/c:NbOfTxs)'));
        self::assertSame('6751.50', $x->evaluate('string(//c:GrpHdr/c:CtrlSum)'));
        self::assertSame('2026-10-14T09:30:00+02:00', $x->evaluate('string(//c:GrpHdr/c:CreDtTm)'));
        self::assertSame('SEPA', $x->evaluate('string(//c:PmtInf/c:PmtTpInf/c:SvcLvl/c:Cd)'));
        self::assertSame('TRF', $x->evaluate('string(//c:PmtInf/c:PmtMtd)'));
        self::assertSame('true', $x->evaluate('string(//c:PmtInf/c:BtchBookg)'));
        self::assertSame('2026-10-27', $x->evaluate('string(//c:PmtInf/c:ReqdExctnDt/c:Dt)'));
        self::assertSame('05428', $x->evaluate('string(//c:PmtInf/c:DbtrAgt/c:FinInstnId/c:ClrSysMmbId/c:MmbId)'));
        self::assertSame('SLEV', $x->evaluate('string(//c:PmtInf/c:ChrgBr)'));
        self::assertSame('Potenza', $x->evaluate('string(//c:PmtInf/c:Dbtr/c:PstlAdr/c:TwnNm)'));
        self::assertSame('1', $x->evaluate('string(//c:CdtTrfTxInf[1]/c:PmtId/c:InstrId)'));
        self::assertSame('3', $x->evaluate('string(//c:CdtTrfTxInf[3]/c:PmtId/c:InstrId)'));
        self::assertSame('SALA', $x->evaluate('string(//c:CdtTrfTxInf[1]/c:PmtTpInf/c:CtgyPurp/c:Cd)'));
        self::assertSame('SALA', $x->evaluate('string(//c:CdtTrfTxInf[1]/c:Purp/c:Cd)'));
        self::assertSame('1250.50', $x->evaluate('string(//c:CdtTrfTxInf[1]/c:Amt/c:InstdAmt)'));
        self::assertSame('EUR', $x->evaluate('string(//c:CdtTrfTxInf[1]/c:Amt/c:InstdAmt/@Ccy)'));
        self::assertSame('Retribuzione Settembre 2026', $x->evaluate('string(//c:CdtTrfTxInf[1]/c:RmtInf/c:Ustrd)'));
        self::assertSame(0.0, $x->evaluate('count(//c:PmtInf/c:NbOfTxs)'), 'CBI has no NbOfTxs in PmtInf');
    }

    public function testInstantPaymentBecomesServiceLevelFast(): void
    {
        $xml = Bank::xmlCreditTransferSepa(Version::CBI_00_04_01)
            ->debtor($this->debtor())
            ->debtorAccount($this->debtorAccount())
            ->transactions($this->salaryTransactions(1))
            ->creationDateTime($this->creationDateTime())
            ->requestedExecutionDate(new DateTimeImmutable('2026-10-14'))
            ->instantPayment(true)
            ->build()
            ->marshal();

        SchemaValidator::validateCreditTransfer(Version::CBI_00_04_01, $xml);
        $x = $this->xpath($xml);
        self::assertSame('FAST', $x->evaluate('string(//c:PmtInf/c:PmtTpInf/c:SvcLvl/c:Cd)'));
        self::assertSame(0.0, $x->evaluate('count(//c:LclInstrm)'), 'CBI does not use LclInstrm/INST');
    }

    public function testTransactionIdIsUsedAsInstrId(): void
    {
        $transaction = Bank::simpleTransaction()
            ->party(Bank::simpleParty()->name('Dipendente')->build())
            ->account(Bank::simpleBankAccount()->iban(self::CREDITOR_IBAN)->bic('BCITITMM')->build())
            ->amount('100')
            ->currency('EUR')
            ->endToEndId('E2E-1')
            ->id('INSTR-1')
            ->categoryPurposeCode('SALA')
            ->build();

        $xml = $this->transfer([$transaction])->marshal();

        SchemaValidator::validateCreditTransfer(Version::CBI_00_04_01, $xml);
        $x = $this->xpath($xml);
        self::assertSame('INSTR-1', $x->evaluate('string(//c:CdtTrfTxInf/c:PmtId/c:InstrId)'));
        self::assertSame('BCITITMM', $x->evaluate('string(//c:CdtTrfTxInf/c:CdtrAgt/c:FinInstnId/c:BICFI)'));
        self::assertSame(0.0, $x->evaluate('count(//c:CdtTrfTxInf/c:CdtrAgt//c:ClrSysMmbId)'), 'creditor agent carries only the BIC');
    }

    public function testFormattedAndUnformattedOutputAreEquivalent(): void
    {
        $transfer = $this->transfer($this->salaryTransactions(1));

        self::assertStringStartsWith('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><CBIPaymentRequest', $transfer->marshal());
        self::assertStringContainsString("\n    <GrpHdr>", $transfer->marshal(true));
        SchemaValidator::validateCreditTransfer(Version::CBI_00_04_01, $transfer->marshal(true));
    }

    public function testMissingCucIsRejected(): void
    {
        $debtor = Bank::simpleParty()->name('Chiaravalle S.r.l.')->build();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CUC');
        $this->transfer($this->salaryTransactions(1), $debtor)->marshal();
    }

    public function testMissingServiceLevelIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('service level');
        Bank::xmlCreditTransfer(Version::CBI_00_04_01)
            ->debtor($this->debtor())
            ->debtorAccount($this->debtorAccount())
            ->transactions($this->salaryTransactions(1))
            ->build()
            ->marshal();
    }

    public function testNonItalianDebtorIbanIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Italian IBAN');
        $this->transfer($this->salaryTransactions(1), null, Bank::simpleBankAccount()->iban('FR7630001007941234567890185')->build())->marshal();
    }

    public function testAccountOtherIdIsRejected(): void
    {
        $transaction = Bank::simpleTransaction()
            ->party(Bank::simpleParty()->name('Dipendente')->build())
            ->account(Bank::simpleBankAccount()->otherId('1234567890')->build())
            ->amount('100')
            ->currency('EUR')
            ->endToEndId('E2E-1')
            ->build();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IBAN');
        $this->transfer([$transaction])->marshal();
    }

    public function testPerTransactionChargeBearerIsRejected(): void
    {
        $transaction = Bank::simpleTransaction()
            ->party(Bank::simpleParty()->name('Dipendente')->build())
            ->account(Bank::simpleBankAccount()->iban(self::CREDITOR_IBAN)->build())
            ->amount('100')
            ->currency('EUR')
            ->endToEndId('E2E-1')
            ->chargeBearerCode(ChargeBearer::CRED)
            ->build();

        $this->expectException(InvalidArgumentException::class);
        $this->transfer([$transaction])->marshal();
    }

    public function testNonSlevChargeBearerIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SLEV');
        Bank::xmlCreditTransferSepa(Version::CBI_00_04_01)
            ->debtor($this->debtor())
            ->debtorAccount($this->debtorAccount())
            ->transactions($this->salaryTransactions(1))
            ->chargeBearer(ChargeBearer::SHAR)
            ->build()
            ->marshal();
    }

    public function testSchemaValidatorRejectsAForeignDocument(): void
    {
        $xml = Bank::xmlCreditTransferSepa(Version::V09)
            ->debtor($this->debtor())
            ->debtorAccount($this->debtorAccount())
            ->transactions($this->salaryTransactions(1))
            ->build()
            ->marshal();

        self::assertFalse(SchemaValidator::isValid(Version::CBI_00_04_01, $xml));
        $this->expectException(XmlException::class);
        SchemaValidator::validateCreditTransfer(Version::CBI_00_04_01, $xml);
    }

    /**
     * @param array<\Iso20022\Pain\Transaction> $transactions
     */
    private function transfer(array $transactions, ?Party $debtor = null, ?BankAccount $debtorAccount = null): \Iso20022\Pain\CreditTransferOperation
    {
        return Bank::xmlCreditTransferSepa(Version::CBI_00_04_01)
            ->debtor($debtor ?? $this->debtor())
            ->debtorAccount($debtorAccount ?? $this->debtorAccount())
            ->transactions($transactions)
            ->creationDateTime($this->creationDateTime())
            ->requestedExecutionDate(new DateTimeImmutable('2026-10-27'))
            ->build();
    }

    private function xpath(string $xml): DOMXPath
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('c', Version::CBI_00_04_01->namespace());

        return $xpath;
    }
}
