<?php

declare(strict_types=1);

namespace Iso20022\Pain\Cbi;

use Iso20022\Pain\Util\Decimal;

/**
 * CBI "distinta" types driven by <PmtInf>/<PmtTpInf>/<SvcLvl>/<Cd> (CBIServiceLevel1Code in
 * CBIPaymentRequest.00.04.01.xsd, CBI STIP-MO-001 §3.2.1) and the application limits each one carries
 * (STIP-MO-001 §3.9.1.2). pagoPA levels (PGPA, PGSP) are out of scope for this library.
 */
enum CbiServiceLevel: string
{
    /** Bonifico SEPA (SCT). */
    case SEPA = 'SEPA';

    /** Bonifico FAST: settled on the EPC SCT Inst scheme (instant). */
    case FAST = 'FAST';

    /** Bonifico urgente (non-SEPA, same-day). */
    case URGP = 'URGP';

    /**
     * Check 23: maximum <InstdAmt> per transaction (EUR): 999999999.99 for SEPA, 99999999999.99 for
     * urgent transfers, 100000.00 for FAST (raised from 15000 on 01/07/2020 with the EPC SCT Inst rulebook).
     */
    public function maxAmount(): string
    {
        return match ($this) {
            self::SEPA => '999999999.99',
            self::URGP => '99999999999.99',
            self::FAST => '100000.00',
        };
    }

    public function isAmountAllowed(string $amount): bool
    {
        return Decimal::compare($amount, '0.01') >= 0 && Decimal::compare($amount, $this->maxAmount()) <= 0;
    }

    /**
     * Check 18: a single <CdtTrfTxInf> for urgent transfers.
     */
    public function maxTransactions(): int
    {
        return $this === self::URGP ? 1 : PHP_INT_MAX;
    }

    /**
     * Check 45: at most one unstructured remittance line for FAST (check 46 also forbids <Strd>).
     */
    public function maxUnstructuredRemittanceLines(): int
    {
        return $this === self::FAST ? 1 : PHP_INT_MAX;
    }

    /**
     * Instant and urgent transfers are executed on receipt, so a future requested execution date is not
     * meaningful (library rule: STIP-MO-001 does not state it explicitly, banks reject scheduled FAST batches).
     */
    public function allowsFutureExecutionDate(): bool
    {
        return $this === self::SEPA;
    }

    /**
     * Check 9: payment methods allowed ("TRA" = with outcome report); urgent transfers require TRA.
     *
     * @return array<string>
     */
    public function allowedPaymentMethods(): array
    {
        return $this === self::URGP ? ['TRA'] : ['TRF', 'TRA'];
    }
}
