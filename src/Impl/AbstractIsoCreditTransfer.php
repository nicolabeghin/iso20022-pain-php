<?php

declare(strict_types=1);

namespace Iso20022\Pain\Impl;

use Iso20022\Pain\Transaction;
use XMLWriter;

/**
 * ISO 20022 "Document/CstmrCdtTrfInitn" layout shared by pain.001.001.03 and pain.001.001.09.
 * Element order and emptiness rules mirror bank4j's JAXB output (e.g. an empty <InitgPty/>, <Dbtr/>
 * or <PmtTpInf/> is still written) so the ported tests compare byte for byte.
 */
abstract class AbstractIsoCreditTransfer extends AbstractXmlCreditTransfer
{
    protected function writeDocument(XMLWriter $w): void
    {
        $w->startElementNs(null, 'Document', $this->version->namespace());
        $w->startElement('CstmrCdtTrfInitn');
        $this->writeGroupHeader($w);
        $this->writePaymentInformation($w);
        $w->endElement();
        $w->endElement();
    }

    protected function writeGroupHeader(XMLWriter $w): void
    {
        $w->startElement('GrpHdr');
        $w->writeElement('MsgId', $this->id);
        $w->writeElement('CreDtTm', $this->formatLocalDateTime($this->creationDateTime));
        $w->writeElement('NbOfTxs', (string)count($this->transactions));
        $w->writeElement('CtrlSum', $this->getControlSum());
        $this->writeParty($w, 'InitgPty', $this->debtor);
        $w->endElement();
    }

    protected function writePaymentInformation(XMLWriter $w): void
    {
        $w->startElement('PmtInf');
        $w->writeElement('PmtInfId', $this->id);
        $w->writeElement('PmtMtd', $this->paymentMethodCode);
        $w->writeElement('BtchBookg', $this->batchBooking ? 'true' : 'false');
        $w->writeElement('NbOfTxs', (string)count($this->transactions));
        $w->writeElement('CtrlSum', $this->getControlSum());
        $w->startElement('PmtTpInf');
        if ($this->instructionPriority !== null) {
            $w->writeElement('InstrPrty', $this->instructionPriority->value);
        }
        if ($this->serviceLevelCode !== null) {
            $w->startElement('SvcLvl');
            $w->writeElement('Cd', $this->serviceLevelCode);
            $w->endElement();
        }
        if ($this->instantPayment) {
            $w->startElement('LclInstrm');
            $w->writeElement('Cd', 'INST');
            $w->endElement();
        }
        $w->endElement();
        $this->writeRequestedExecutionDate($w);
        $this->writeParty($w, 'Dbtr', $this->debtor);
        $this->writeCashAccount($w, 'DbtrAcct', $this->debtorAccount);
        $this->writeAgent($w, 'DbtrAgt', $this->debtorAccount, true);
        $w->writeElement('ChrgBr', $this->chargeBearer->value);
        foreach ($this->transactions as $transaction) {
            $this->writeTransaction($w, $transaction);
        }
        $w->endElement();
    }

    abstract protected function writeRequestedExecutionDate(XMLWriter $w): void;

    protected function writeTransaction(XMLWriter $w, Transaction $transaction): void
    {
        $w->startElement('CdtTrfTxInf');
        $w->startElement('PmtId');
        $this->writeOptional($w, 'InstrId', $transaction->getId());
        $w->writeElement('EndToEndId', $transaction->getEndToEndId());
        $w->endElement();
        if ($transaction->getCategoryPurposeCode() !== null) {
            $w->startElement('PmtTpInf');
            $w->startElement('CtgyPurp');
            $w->writeElement('Cd', $transaction->getCategoryPurposeCode());
            $w->endElement();
            $w->endElement();
        }
        $w->startElement('Amt');
        $w->startElement('InstdAmt');
        $w->writeAttribute('Ccy', $transaction->getCurrencyCode());
        $w->text($transaction->getAmount());
        $w->endElement();
        $w->endElement();
        if ($transaction->getChargeBearer() !== null) {
            $w->writeElement('ChrgBr', $transaction->getChargeBearer()->value);
        }
        foreach ($transaction->getIntermediaryAgents() as $index => $agent) {
            $this->writeAgent($w, 'IntrmyAgt' . ($index + 1), $agent, false);
            $this->writeCashAccount($w, 'IntrmyAgt' . ($index + 1) . 'Acct', $agent);
        }
        $this->writeAgent($w, 'CdtrAgt', $transaction->getAccount(), false);
        $this->writeParty($w, 'Cdtr', $transaction->getParty());
        $this->writeCashAccount($w, 'CdtrAcct', $transaction->getAccount());
        if ($transaction->getPurposeCode() !== null) {
            $w->startElement('Purp');
            $w->writeElement('Cd', $transaction->getPurposeCode());
            $w->endElement();
        }
        if ($transaction->getRemittanceInformationUnstructured() !== []) {
            $w->startElement('RmtInf');
            foreach ($transaction->getRemittanceInformationUnstructured() as $line) {
                $w->writeElement('Ustrd', $line);
            }
            $w->endElement();
        }
        $w->endElement();
    }
}
