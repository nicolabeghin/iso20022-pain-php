<?php

declare(strict_types=1);

namespace Iso20022\Pain;

/**
 * One credit transfer transaction (<CdtTrfTxInf>). PHP port of io.inisos.bank4j.Transaction.
 *
 * Extensions over bank4j: optional category purpose and purpose codes (ISO External Code Sets,
 * e.g. "SALA" for salary payments), required by CBI for Italian creditor IBANs.
 */
interface Transaction
{
    public function getParty(): ?Party;

    public function getAccount(): BankAccount;

    /** Decimal amount as a string, e.g. "12.34" (scale preserved like Java BigDecimal). */
    public function getAmount(): string;

    public function getCurrencyCode(): string;

    public function getEndToEndId(): string;

    public function getId(): ?string;

    public function getChargeBearer(): ?ChargeBearer;

    /**
     * @return array<\Iso20022\Pain\BankAccount>
     */
    public function getIntermediaryAgents(): array;

    /**
     * @return array<string> unique unstructured remittance lines
     */
    public function getRemittanceInformationUnstructured(): array;

    public function getCategoryPurposeCode(): ?string;

    public function getPurposeCode(): ?string;
}
