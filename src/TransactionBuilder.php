<?php

declare(strict_types=1);

namespace Iso20022\Pain;

/**
 * PHP port of io.inisos.bank4j.TransactionBuilder (plus categoryPurposeCode/purposeCode extensions).
 */
interface TransactionBuilder
{
    public function party(?Party $party): self;

    public function account(BankAccount $account): self;

    /**
     * @param string|int|float $amount decimal amount; strings keep their scale ("12.34", "200")
     */
    public function amount(string|int|float $amount): self;

    public function currency(string $currencyCode): self;

    public function endToEndId(string $endToEndId): self;

    public function id(?string $id): self;

    public function chargeBearerCode(?ChargeBearer $chargeBearer): self;

    /**
     * @param iterable<\Iso20022\Pain\BankAccount> $intermediaryAgents
     */
    public function intermediaryAgents(iterable $intermediaryAgents): self;

    public function intermediaryAgent(BankAccount $intermediaryAgent): self;

    /**
     * @param iterable<string> $remittanceInformationUnstructured
     */
    public function remittanceInformationUnstructured(iterable $remittanceInformationUnstructured): self;

    public function categoryPurposeCode(?string $categoryPurposeCode): self;

    public function purposeCode(?string $purposeCode): self;

    public function build(): Transaction;
}
