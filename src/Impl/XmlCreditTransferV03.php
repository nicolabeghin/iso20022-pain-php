<?php

declare(strict_types=1);

namespace Iso20022\Pain\Impl;

use XMLWriter;

/**
 * ISO 20022 pain.001.001.03 credit transfer. PHP port of io.inisos.bank4j.impl.JAXBCreditTransferV03.
 */
final class XmlCreditTransferV03 extends AbstractIsoCreditTransfer
{
    protected function bicElementName(): string
    {
        return 'BIC';
    }

    protected function addressTypeIsChoice(): bool
    {
        return false;
    }

    protected function writeRequestedExecutionDate(XMLWriter $w): void
    {
        // pain.001.001.03: ReqdExctnDt is a plain ISODate.
        $w->writeElement('ReqdExctnDt', (string)$this->requestedExecutionDate?->format('Y-m-d'));
    }
}
