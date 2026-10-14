<?php

declare(strict_types=1);

namespace Iso20022\Pain\Impl;

use DateTimeImmutable;
use Iso20022\Pain\BankAccount;
use Iso20022\Pain\ChargeBearer;
use Iso20022\Pain\CreditTransferOperation;
use Iso20022\Pain\CreditTransferOperationBuilder;
use Iso20022\Pain\CustomerCreditTransferInitiationVersion;
use Iso20022\Pain\Party;
use Iso20022\Pain\Priority;
use Iso20022\Pain\Transaction;
use LogicException;

/**
 * PHP port of io.inisos.bank4j.impl.JAXBCreditTransferBuilder.
 */
final class XmlCreditTransferBuilder implements CreditTransferOperationBuilder
{
    private ?CustomerCreditTransferInitiationVersion $version = null;
    private ?Priority $instructionPriority = null;
    private ?string $serviceLevelCode = null;
    private ?Party $debtor = null;
    private ?BankAccount $debtorAccount = null;
    /** @var array<\Iso20022\Pain\Transaction> */
    private array $transactions = [];
    private ?string $id = null;
    private ?DateTimeImmutable $creationDateTime = null;
    private ?DateTimeImmutable $requestedExecutionDate = null;
    private ?DateTimeImmutable $requestedExecutionDateTime = null;
    private ?ChargeBearer $chargeBearer = null;
    private ?bool $batchBooking = null;
    private ?bool $instantPayment = null;
    private ?string $paymentMethodCode = null;

    public function version(CustomerCreditTransferInitiationVersion $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function sepa(): self
    {
        return $this->serviceLevelCode('SEPA');
    }

    public function instructionPriority(?Priority $priority): self
    {
        $this->instructionPriority = $priority;

        return $this;
    }

    public function serviceLevelCode(?string $serviceLevelCode): self
    {
        $this->serviceLevelCode = $serviceLevelCode;

        return $this;
    }

    public function debtor(?Party $debtor): self
    {
        $this->debtor = $debtor;

        return $this;
    }

    public function debtorAccount(BankAccount $debtorAccount): self
    {
        $this->debtorAccount = $debtorAccount;

        return $this;
    }

    public function transactions(iterable $transactions): self
    {
        foreach ($transactions as $transaction) {
            $this->transactions[] = $transaction;
        }

        return $this;
    }

    public function transaction(Transaction $transaction): self
    {
        $this->transactions[] = $transaction;

        return $this;
    }

    public function id(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function creationDateTime(?DateTimeImmutable $creationDateTime): self
    {
        $this->creationDateTime = $creationDateTime;

        return $this;
    }

    public function requestedExecutionDate(?DateTimeImmutable $requestedExecutionDate): self
    {
        if ($this->requestedExecutionDateTime !== null) {
            throw new LogicException('Only one of requestedExecutionDate and requestedExecutionDateTime can be set, not both');
        }
        $this->requestedExecutionDate = $requestedExecutionDate;

        return $this;
    }

    public function requestedExecutionDateTime(?DateTimeImmutable $requestedExecutionDateTime): self
    {
        if ($this->requestedExecutionDate !== null) {
            throw new LogicException('Only one of requestedExecutionDate and requestedExecutionDateTime can be set, not both');
        }
        if ($this->version === null || !$this->version->supportsRequestedExecutionDateTime()) {
            throw new LogicException('Only version 09 (and CBI 00.04.01) supports requestedExecutionDateTime');
        }
        $this->requestedExecutionDateTime = $requestedExecutionDateTime;

        return $this;
    }

    public function chargeBearer(?ChargeBearer $chargeBearer): self
    {
        $this->chargeBearer = $chargeBearer;

        return $this;
    }

    public function batchBooking(?bool $batchBooking): self
    {
        $this->batchBooking = $batchBooking;

        return $this;
    }

    public function instantPayment(?bool $instantPayment): self
    {
        if ($this->version === null || !$this->version->supportsInstantPayment()) {
            throw new LogicException('Only version 09 (and CBI 00.04.01) supports instantPayments');
        }
        $this->instantPayment = $instantPayment;

        return $this;
    }

    public function paymentMethodCode(?string $paymentMethodCode): self
    {
        $this->paymentMethodCode = $paymentMethodCode;

        return $this;
    }

    public function build(): CreditTransferOperation
    {
        if ($this->version === null) {
            throw new LogicException('Version must be set');
        }
        if ($this->debtorAccount === null) {
            throw new LogicException('Debtor account cannot be null');
        }
        $class = match ($this->version) {
            CustomerCreditTransferInitiationVersion::V03 => XmlCreditTransferV03::class,
            CustomerCreditTransferInitiationVersion::V09 => XmlCreditTransferV09::class,
            CustomerCreditTransferInitiationVersion::CBI_00_04_01 => XmlCreditTransferCbi000401::class,
        };

        return new $class(
            $this->version,
            $this->instructionPriority,
            $this->serviceLevelCode,
            $this->debtor,
            $this->debtorAccount,
            $this->transactions,
            $this->id,
            $this->creationDateTime,
            $this->requestedExecutionDate,
            $this->requestedExecutionDateTime,
            $this->chargeBearer,
            $this->batchBooking,
            $this->instantPayment,
            $this->paymentMethodCode,
        );
    }
}
