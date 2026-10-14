<?php

declare(strict_types=1);

namespace Iso20022\Pain\Impl;

use XMLWriter;

/**
 * ISO 20022 pain.001.001.09 credit transfer. PHP port of io.inisos.bank4j.impl.JAXBCreditTransferV09.
 */
final class XmlCreditTransferV09 extends AbstractIsoCreditTransfer
{
    protected function bicElementName(): string
    {
        return 'BICFI';
    }

    protected function addressTypeIsChoice(): bool
    {
        return true;
    }

    protected function writeRequestedExecutionDate(XMLWriter $w): void
    {
        // pain.001.001.09: DateAndDateTime2Choice, either <Dt> or <DtTm>.
        $w->startElement('ReqdExctnDt');
        if ($this->requestedExecutionDateTime !== null) {
            $w->writeElement('DtTm', $this->formatOffsetDateTime($this->requestedExecutionDateTime));
        } else {
            $w->writeElement('Dt', (string)$this->requestedExecutionDate?->format('Y-m-d'));
        }
        $w->endElement();
    }
}
