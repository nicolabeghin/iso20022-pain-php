<?php

declare(strict_types=1);

namespace Iso20022\Pain;

use DateTimeImmutable;

/**
 * A credit transfer initiation (one payment information block with its transactions).
 * PHP port of io.inisos.bank4j.CreditTransfer.
 */
interface CreditTransfer
{
    public function getInstructionPriority(): ?Priority;

    public function getServiceLevelCode(): ?string;

    public function getDebtor(): ?Party;

    public function getDebtorAccount(): BankAccount;

    public function getId(): string;

    public function getCreationDateTime(): DateTimeImmutable;

    public function getRequestedExecutionDate(): ?DateTimeImmutable;

    public function getRequestedExecutionDateTime(): ?DateTimeImmutable;

    /**
     * @return array<\Iso20022\Pain\Transaction>
     */
    public function getTransactions(): array;

    public function getChargeBearer(): ChargeBearer;

    public function isBatchBooking(): bool;

    /** <PmtMtd>: TRF (default) or TRA (CBI: with outcome report to the ordering party). */
    public function getPaymentMethodCode(): string;

    public function isInstantPayment(): bool;

    /** Sum of the transaction amounts, as a decimal string. */
    public function getControlSum(): string;
}
