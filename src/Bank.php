<?php

declare(strict_types=1);

namespace Iso20022\Pain;

use Iso20022\Pain\Impl\SimpleBankAccountBuilder;
use Iso20022\Pain\Impl\SimplePartyBuilder;
use Iso20022\Pain\Impl\SimplePostalAddressBuilder;
use Iso20022\Pain\Impl\SimpleTransactionBuilder;
use Iso20022\Pain\Impl\XmlCreditTransferBuilder;

/**
 * Entry point: factories for the model builders and the XML credit transfer builder.
 *
 * PHP port of io.inisos.bank4j.Bank (jaxbCreditTransfer* became xmlCreditTransfer*).
 */
final class Bank
{
    public static function simpleParty(): PartyBuilder
    {
        return new SimplePartyBuilder();
    }

    public static function simplePostalAddress(): PostalAddressBuilder
    {
        return new SimplePostalAddressBuilder();
    }

    public static function simpleBankAccount(): BankAccountBuilder
    {
        return new SimpleBankAccountBuilder();
    }

    public static function simpleTransaction(): TransactionBuilder
    {
        return new SimpleTransactionBuilder();
    }

    /**
     * Credit transfer builder with service level "SEPA" preset.
     */
    public static function xmlCreditTransferSepa(
        CustomerCreditTransferInitiationVersion $version = CustomerCreditTransferInitiationVersion::V03,
    ): CreditTransferOperationBuilder {
        return self::xmlCreditTransfer($version)->sepa();
    }

    public static function xmlCreditTransfer(
        CustomerCreditTransferInitiationVersion $version = CustomerCreditTransferInitiationVersion::V03,
    ): CreditTransferOperationBuilder {
        return (new XmlCreditTransferBuilder())->version($version);
    }

    private function __construct()
    {
    }
}
