<?php

declare(strict_types=1);

namespace Iso20022\Pain;

use DateTimeImmutable;

/**
 * PHP port of io.inisos.bank4j.CreditTransferOperationBuilder (plus paymentMethodCode extension).
 */
interface CreditTransferOperationBuilder
{
    /** Shortcut for serviceLevelCode("SEPA"). */
    public function sepa(): self;

    public function instructionPriority(?Priority $priority): self;

    public function serviceLevelCode(?string $serviceLevelCode): self;

    public function debtor(?Party $debtor): self;

    public function debtorAccount(BankAccount $debtorAccount): self;

    /**
     * @param iterable<\Iso20022\Pain\Transaction> $transactions
     */
    public function transactions(iterable $transactions): self;

    public function transaction(Transaction $transaction): self;

    public function id(?string $id): self;

    public function creationDateTime(?DateTimeImmutable $creationDateTime): self;

    public function requestedExecutionDate(?DateTimeImmutable $requestedExecutionDate): self;

    public function requestedExecutionDateTime(?DateTimeImmutable $requestedExecutionDateTime): self;

    public function chargeBearer(?ChargeBearer $chargeBearer): self;

    public function batchBooking(?bool $batchBooking): self;

    public function instantPayment(?bool $instantPayment): self;

    public function paymentMethodCode(?string $paymentMethodCode): self;

    public function build(): CreditTransferOperation;
}
