<?php

declare(strict_types=1);

namespace Iso20022\Pain\Impl;

use InvalidArgumentException;
use Iso20022\Pain\BankAccount;
use Iso20022\Pain\ChargeBearer;
use Iso20022\Pain\Party;
use Iso20022\Pain\Transaction;
use Iso20022\Pain\TransactionBuilder;

/**
 * PHP port of io.inisos.bank4j.impl.SimpleTransactionBuilder.
 */
final class SimpleTransactionBuilder implements TransactionBuilder
{
    private ?Party $party = null;
    private ?BankAccount $account = null;
    private string|int|float|null $amount = null;
    private ?string $currencyCode = null;
    private ?string $endToEndId = null;
    private ?string $id = null;
    private ?ChargeBearer $chargeBearer = null;
    /** @var array<\Iso20022\Pain\BankAccount> */
    private array $intermediaryAgents = [];
    /** @var array<string> */
    private array $remittanceInformationUnstructured = [];
    private ?string $categoryPurposeCode = null;
    private ?string $purposeCode = null;

    public function party(?Party $party): self
    {
        $this->party = $party;

        return $this;
    }

    public function account(BankAccount $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function amount(string|int|float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function currency(string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function endToEndId(string $endToEndId): self
    {
        $this->endToEndId = $endToEndId;

        return $this;
    }

    public function id(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function chargeBearerCode(?ChargeBearer $chargeBearer): self
    {
        $this->chargeBearer = $chargeBearer;

        return $this;
    }

    public function intermediaryAgents(iterable $intermediaryAgents): self
    {
        foreach ($intermediaryAgents as $agent) {
            $this->intermediaryAgents[] = $agent;
        }

        return $this;
    }

    public function intermediaryAgent(BankAccount $intermediaryAgent): self
    {
        $this->intermediaryAgents[] = $intermediaryAgent;

        return $this;
    }

    public function remittanceInformationUnstructured(iterable $remittanceInformationUnstructured): self
    {
        foreach ($remittanceInformationUnstructured as $line) {
            $this->remittanceInformationUnstructured[] = $line;
        }

        return $this;
    }

    public function categoryPurposeCode(?string $categoryPurposeCode): self
    {
        $this->categoryPurposeCode = $categoryPurposeCode;

        return $this;
    }

    public function purposeCode(?string $purposeCode): self
    {
        $this->purposeCode = $purposeCode;

        return $this;
    }

    public function build(): Transaction
    {
        if ($this->account === null) {
            throw new InvalidArgumentException('Account cannot be null');
        }
        if ($this->amount === null) {
            throw new InvalidArgumentException('Amount cannot be null');
        }
        if ($this->currencyCode === null) {
            throw new InvalidArgumentException('Currency cannot be null');
        }
        if ($this->endToEndId === null) {
            throw new InvalidArgumentException('End to end id cannot be null');
        }

        return new SimpleTransaction(
            $this->party,
            $this->account,
            $this->amount,
            $this->currencyCode,
            $this->endToEndId,
            $this->id,
            $this->chargeBearer,
            $this->intermediaryAgents,
            $this->remittanceInformationUnstructured,
            $this->categoryPurposeCode,
            $this->purposeCode,
        );
    }
}
