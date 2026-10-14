<?php

declare(strict_types=1);

namespace Iso20022\Pain\Impl;

use InvalidArgumentException;
use Iso20022\Pain\BankAccount;
use Iso20022\Pain\ChargeBearer;
use Iso20022\Pain\Party;
use Iso20022\Pain\Transaction;
use Iso20022\Pain\Util\Decimal;
use Iso20022\Pain\Validator\Constraints\Iso20022CharacterSet;
use Iso20022\Pain\Validator\Constraints\Size;

/**
 * PHP port of io.inisos.bank4j.impl.SimpleTransaction (plus categoryPurposeCode/purposeCode).
 */
final class SimpleTransaction implements Transaction
{
    private readonly string $amount;
    private readonly string $currencyCode;
    /** @var array<\Iso20022\Pain\BankAccount> */
    private readonly array $intermediaryAgents;
    /** @var array<string> */
    #[Size(max: 140, each: true)]
    private readonly array $remittanceInformationUnstructured;

    /**
     * @param array<\Iso20022\Pain\BankAccount> $intermediaryAgents at most 3
     * @param array<string> $remittanceInformationUnstructured unstructured remittance lines (duplicates dropped)
     */
    public function __construct(
        private readonly ?Party $party,
        private readonly BankAccount $account,
        string|int|float $amount,
        string $currencyCode,
        #[Iso20022CharacterSet]
        #[Size(max: 35)]
        private readonly string $endToEndId,
        #[Iso20022CharacterSet]
        private readonly ?string $id = null,
        private readonly ?ChargeBearer $chargeBearer = null,
        array $intermediaryAgents = [],
        array $remittanceInformationUnstructured = [],
        #[Size(max: 4)]
        private readonly ?string $categoryPurposeCode = null,
        #[Size(max: 4)]
        private readonly ?string $purposeCode = null,
    ) {
        $this->amount = Decimal::normalize($amount);
        if (!preg_match('/^[A-Z]{3}$/', $currencyCode)) {
            throw new InvalidArgumentException(sprintf('"%s" is not an ISO 4217 currency code.', $currencyCode));
        }
        $this->currencyCode = $currencyCode;
        if (count($intermediaryAgents) > 3) {
            throw new InvalidArgumentException('Intermediary agents cannot be more than 3');
        }
        $this->intermediaryAgents = array_values($intermediaryAgents);
        $this->remittanceInformationUnstructured = array_values(array_unique($remittanceInformationUnstructured));
    }

    public function getParty(): ?Party
    {
        return $this->party;
    }

    public function getAccount(): BankAccount
    {
        return $this->account;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function getEndToEndId(): string
    {
        return $this->endToEndId;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getChargeBearer(): ?ChargeBearer
    {
        return $this->chargeBearer;
    }

    public function getIntermediaryAgents(): array
    {
        return $this->intermediaryAgents;
    }

    public function getRemittanceInformationUnstructured(): array
    {
        return $this->remittanceInformationUnstructured;
    }

    public function getCategoryPurposeCode(): ?string
    {
        return $this->categoryPurposeCode;
    }

    public function getPurposeCode(): ?string
    {
        return $this->purposeCode;
    }
}
