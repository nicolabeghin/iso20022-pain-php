<?php

declare(strict_types=1);

namespace Iso20022\Pain;

/**
 * Supported Customer Credit Transfer Initiation message flavours.
 *
 * PHP port of io.inisos.bank4j.CustomerCreditTransferInitiationVersion. The ISO versions V03 and V09
 * are ported 1:1 from bank4j (and verified against its test expectations); CBI_00_04_01 is the Italian
 * CBI "CBIPaymentRequest" profile built on pain.001.001.09, added by this library.
 * bank4j's V03_CH_02 (Swiss) and V003_03 (German) flavours are not ported.
 */
enum CustomerCreditTransferInitiationVersion: string
{
    /** ISO 20022 pain.001.001.03 */
    case V03 = 'pain.001.001.03';

    /** ISO 20022 pain.001.001.09 */
    case V09 = 'pain.001.001.09';

    /**
     * CBI "CBIPaymentRequest" 00.04.01 (pain.001.001.09 based), see
     * https://github.com/OCA/l10n-italy/blob/16.0/l10n_it_sct_cbi/data/standards/CBIPaymentRequest.00.04.01.xsd
     */
    case CBI_00_04_01 = 'CBIPaymentRequest.00.04.01';

    public function namespace(): string
    {
        return match ($this) {
            self::V03 => 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03',
            self::V09 => 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.09',
            self::CBI_00_04_01 => 'urn:CBI:xsd:CBIPaymentRequest.00.04.01',
        };
    }

    /**
     * Local copy of the official XSD shipped with the library.
     */
    public function schemaPath(): string
    {
        return dirname(__DIR__) . '/resources/xsd/' . $this->value . '.xsd';
    }

    public function supportsInstantPayment(): bool
    {
        return $this !== self::V03;
    }

    public function supportsRequestedExecutionDateTime(): bool
    {
        return $this !== self::V03;
    }
}
