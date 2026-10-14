<?php

declare(strict_types=1);

namespace Iso20022\Pain\Impl;

use InvalidArgumentException;
use Iso20022\Pain\BankAccount;
use Iso20022\Pain\Cbi\ItalianIban;
use Iso20022\Pain\ChargeBearer;
use Iso20022\Pain\Transaction;
use Iso20022\Pain\Util\BicUtil;
use Iso20022\Pain\Util\IbanUtil;
use XMLWriter;

/**
 * Italian CBI "CBIPaymentRequest" 00.04.01: the CBI profile of pain.001.001.09 used for
 * "Disposizioni di pagamento XML" (bonifici SEPA, FAST/istantanei, urgenti) uploaded to corporate
 * banking portals. Element order and cardinalities follow CBIPaymentRequest.00.04.01.xsd (shipped in
 * resources/xsd/, in force since 17/03/2024); the numbered comments cite the application checks of
 * CBI STIP-MO-001 §3.9.1.2 that each element satisfies.
 *
 * Differences from the plain ISO message that callers must know:
 *  - root is <CBIPaymentRequest> (no <Document>/<CstmrCdtTrfInitn>), exactly one <PmtInf>;
 *  - the initiating party MUST carry the CUC (Codice Univoco CBI) as Party::getOtherId() with issuer "CBI";
 *  - the debtor agent is identified by the 5-digit ABI derived from the (Italian) debtor IBAN;
 *  - instantPayment(true) maps to service level "FAST" (CBI does not use LclInstrm/INST);
 *  - accounts must be IBANs, charge bearer is SLEV only, per-transaction charge bearer and
 *    intermediary agents are not part of the CBI schema and are rejected;
 *  - <InstrId> is mandatory: the transaction id or, when absent, the 1-based position.
 *
 * References followed:
 *  - CBI XSD CBIPaymentRequest.00.04.01.xsd (copy published by OCA):
 *    https://github.com/OCA/l10n-italy/blob/16.0/l10n_it_sct_cbi/data/standards/CBIPaymentRequest.00.04.01.xsd
 *  - CBI STIP-MO-001 "Nuova Architettura CBI – Area Pagamenti" v00.04.00 (§3.2.1, §3.9.1.2, Appendix A):
 *    https://www.protezionecivile.gov.it/static/068269bfd70e89353e4723e45cec7b65/tracciato-bonifci.pdf
 *  - CBI Scpa standards portal: https://www.cbi-org.eu/
 *  - ISO 20022 pain.001.001.09 and External Code Sets (SEPA, SLEV, SALA):
 *    https://www.iso20022.org/iso-20022-message-definitions
 *    https://www.iso20022.org/catalogue-messages/additional-content-messages/external-code-sets
 *  - EPC SCT / SCT Inst Customer-to-PSP Implementation Guidelines, EPC230-15 (slashes in identifiers):
 *    https://www.europeanpaymentscouncil.eu/document-library
 */
final class XmlCreditTransferCbi000401 extends AbstractXmlCreditTransfer
{
    public const SERVICE_LEVEL_FAST = 'FAST';
    public const CUC_ISSUER = 'CBI';

    protected function bicElementName(): string
    {
        return 'BICFI';
    }

    protected function addressTypeIsChoice(): bool
    {
        return true;
    }

    /**
     * Effective <SvcLvl>/<Cd>: "FAST" when instantPayment is set, otherwise the configured code.
     */
    public function getEffectiveServiceLevelCode(): string
    {
        if ($this->instantPayment) {
            return self::SERVICE_LEVEL_FAST;
        }
        if ($this->serviceLevelCode === null) {
            // Check 10: <PmtTpInf>/<SvcLvl> is mandatory for SEPA/FAST/urgent transfers.
            throw new InvalidArgumentException(
                'CBI requires a service level code (e.g. "SEPA" via ->sepa(), or instantPayment(true) for FAST).',
            );
        }

        return $this->serviceLevelCode;
    }

    protected function writeDocument(XMLWriter $w): void
    {
        $w->startElementNs(null, 'CBIPaymentRequest', $this->version->namespace());
        $this->writeGroupHeader($w);
        $this->writePaymentInformation($w);
        $w->endElement();
    }

    private function writeGroupHeader(XMLWriter $w): void
    {
        $w->startElement('GrpHdr');
        // Check 1: MsgId unique per sender; Appendix A character set.
        $w->writeElement('MsgId', $this->id);
        $w->writeElement('CreDtTm', $this->formatOffsetDateTime($this->creationDateTime));
        // Checks 2 and 3: NbOfTxs = number of <CdtTrfTxInf>, CtrlSum = sum of <InstdAmt>.
        $w->writeElement('NbOfTxs', (string)count($this->transactions));
        $w->writeElement('CtrlSum', $this->getControlSum());
        // Checks 4 and 5: InitgPty identified by its CUC, issuer "CBI" (CBIPartyIdentification1: Id mandatory).
        $cuc = $this->debtor?->getOtherId();
        if ($cuc === null || $cuc === '') {
            throw new InvalidArgumentException(
                'CBI requires the initiating party CUC: set it with PartyBuilder::otherId($cuc, "CBI") on the debtor.',
            );
        }
        $w->startElement('InitgPty');
        $this->writeOptional($w, 'Nm', $this->debtor?->getName());
        $this->writeOrganisationOtherId($w, $cuc, $this->debtor?->getOtherIdIssuer() ?? self::CUC_ISSUER);
        $w->endElement();
        $w->endElement();
    }

    private function writePaymentInformation(XMLWriter $w): void
    {
        $w->startElement('PmtInf');
        $w->writeElement('PmtInfId', $this->id);
        // Check 9: TRF or TRA (TRA = with outcome report to the ordering party).
        $w->writeElement('PmtMtd', $this->paymentMethodCode);
        $w->writeElement('BtchBookg', $this->batchBooking ? 'true' : 'false');
        // Check 10: PmtTpInf/SvcLvl mandatory; "SEPA" = Bonifico SEPA, "FAST" = Bonifico FAST (STIP-MO-001 §3.2.1).
        $w->startElement('PmtTpInf');
        if ($this->instructionPriority !== null) {
            $w->writeElement('InstrPrty', $this->instructionPriority->value);
        }
        $w->startElement('SvcLvl');
        $w->writeElement('Cd', $this->getEffectiveServiceLevelCode());
        $w->endElement();
        $w->endElement();
        // pain.001.001.09 migration: DateAndDateTime2Choice, <Dt> or <DtTm>.
        $w->startElement('ReqdExctnDt');
        if ($this->requestedExecutionDateTime !== null) {
            $w->writeElement('DtTm', $this->formatOffsetDateTime($this->requestedExecutionDateTime));
        } else {
            $w->writeElement('Dt', (string)$this->requestedExecutionDate?->format('Y-m-d'));
        }
        $w->endElement();
        // CBIPartyIdentification4: <Nm> mandatory (Max70Text). The CUC is not repeated under <Dbtr>.
        if ($this->debtor === null || $this->debtor->getName() === null) {
            throw new InvalidArgumentException('CBI requires the debtor name.');
        }
        $w->startElement('Dbtr');
        $w->writeElement('Nm', $this->debtor->getName());
        if ($this->debtor->getPostalAddress() !== null) {
            $this->writePostalAddress($w, $this->debtor->getPostalAddress());
        }
        $w->endElement();
        $this->writeIbanAccount($w, 'DbtrAcct', $this->debtorAccount);
        // Check 13: Debtor Agent identified by the 5-digit ABI (clearing system member id) of the debtor bank.
        $w->startElement('DbtrAgt');
        $w->startElement('FinInstnId');
        $this->writeBic($w, $this->debtorAccount);
        $w->startElement('ClrSysMmbId');
        $w->writeElement('MmbId', ItalianIban::abi((string)$this->debtorAccount->getIban()));
        $w->endElement();
        $w->endElement();
        $w->endElement();
        // Check 16: Charge Bearer mandatory; CBIChargeBearerTypeCode only allows SLEV.
        if ($this->chargeBearer !== ChargeBearer::SLEV) {
            throw new InvalidArgumentException('CBI only accepts charge bearer SLEV.');
        }
        $w->writeElement('ChrgBr', ChargeBearer::SLEV->value);
        foreach ($this->transactions as $index => $transaction) {
            $this->writeTransaction($w, $transaction, $index + 1);
        }
        $w->endElement();
    }

    private function writeTransaction(XMLWriter $w, Transaction $transaction, int $position): void
    {
        if ($transaction->getChargeBearer() !== null) {
            throw new InvalidArgumentException('CBI does not allow a per-transaction charge bearer (transaction ' . $position . ').');
        }
        if ($transaction->getIntermediaryAgents() !== []) {
            throw new InvalidArgumentException('CBI does not allow intermediary agents (transaction ' . $position . ').');
        }
        $w->startElement('CdtTrfTxInf');
        // PaymentIdentification1: InstrId mandatory; check 19: EndToEndId unique within the batch.
        $w->startElement('PmtId');
        $w->writeElement('InstrId', $transaction->getId() ?? (string)$position);
        $w->writeElement('EndToEndId', $transaction->getEndToEndId());
        $w->endElement();
        // Checks 20 and 40: PmtTpInf/CtgyPurp mandatory when the creditor IBAN is Italian (e.g. SALA = salary).
        if ($transaction->getCategoryPurposeCode() !== null) {
            $w->startElement('PmtTpInf');
            $w->startElement('CtgyPurp');
            $w->writeElement('Cd', $transaction->getCategoryPurposeCode());
            $w->endElement();
            $w->endElement();
        }
        // Check 23: EUR only, amount within the service-level range (see Cbi\CbiPaymentRequestValidator).
        $w->startElement('Amt');
        $w->startElement('InstdAmt');
        $w->writeAttribute('Ccy', $transaction->getCurrencyCode());
        $w->text($transaction->getAmount());
        $w->endElement();
        $w->endElement();
        // CdtrAgt (CBIFinancialInstitutionIdentification2): BICFI only, written when the creditor account has a BIC.
        $creditorBic = $transaction->getAccount()->getBic();
        if ($creditorBic !== null && $creditorBic !== '') {
            $w->startElement('CdtrAgt');
            $w->startElement('FinInstnId');
            $this->writeBic($w, $transaction->getAccount());
            $w->endElement();
            $w->endElement();
        }
        // CBIPartyIdentification3: <Nm> mandatory.
        $party = $transaction->getParty();
        if ($party === null || $party->getName() === null) {
            throw new InvalidArgumentException('CBI requires the creditor name (transaction ' . $position . ').');
        }
        $w->startElement('Cdtr');
        $w->writeElement('Nm', $party->getName());
        if ($party->getPostalAddress() !== null) {
            $this->writePostalAddress($w, $party->getPostalAddress());
        }
        if ($party->getOtherId() !== null) {
            $this->writeOrganisationOtherId($w, $party->getOtherId(), $party->getOtherIdIssuer());
        }
        $w->endElement();
        // Checks 31 and 33: Creditor Account mandatory, IBAN with valid check digits.
        $this->writeIbanAccount($w, 'CdtrAcct', $transaction->getAccount());
        // Check 41: Purpose from the ISO External Purpose Code list.
        if ($transaction->getPurposeCode() !== null) {
            $w->startElement('Purp');
            $w->writeElement('Cd', $transaction->getPurposeCode());
            $w->endElement();
        }
        // Checks 45 and 46: unstructured remittance only (Max140Text each; at most one line for FAST).
        if ($transaction->getRemittanceInformationUnstructured() !== []) {
            $w->startElement('RmtInf');
            foreach ($transaction->getRemittanceInformationUnstructured() as $line) {
                $w->writeElement('Ustrd', $line);
            }
            $w->endElement();
        }
        $w->endElement();
    }

    /**
     * CBICashAccount2/3: IBAN only (no generic identifier, no account name).
     */
    private function writeIbanAccount(XMLWriter $w, string $element, BankAccount $account): void
    {
        $iban = $account->getIban();
        if ($iban === null || $iban === '') {
            throw new InvalidArgumentException(sprintf('CBI requires an IBAN for %s (otherId is not supported).', $element));
        }
        IbanUtil::validate($iban);
        $w->startElement($element);
        $w->startElement('Id');
        $w->writeElement('IBAN', $iban);
        $w->endElement();
        $w->endElement();
    }

    private function writeBic(XMLWriter $w, BankAccount $account): void
    {
        $bic = $account->getBic();
        if ($bic !== null && $bic !== '') {
            BicUtil::validate($bic);
            $w->writeElement('BICFI', $bic);
        }
    }
}
