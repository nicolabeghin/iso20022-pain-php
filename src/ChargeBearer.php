<?php

declare(strict_types=1);

namespace Iso20022\Pain;

/**
 * ISO 20022 ChargeBearerType1Code. PHP port of io.inisos.bank4j.ChargeBearer.
 * CBI only accepts SLEV (CBIChargeBearerTypeCode in CBIPaymentRequest.00.04.01.xsd).
 */
enum ChargeBearer: string
{
    case DEBT = 'DEBT';
    case CRED = 'CRED';
    case SHAR = 'SHAR';
    case SLEV = 'SLEV';
}
