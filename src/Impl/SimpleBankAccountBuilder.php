<?php

declare(strict_types=1);

namespace Iso20022\Pain\Impl;

use Iso20022\Pain\BankAccount;
use Iso20022\Pain\BankAccountBuilder;

/**
 * PHP port of io.inisos.bank4j.impl.SimpleBankAccountBuilder.
 */
final class SimpleBankAccountBuilder implements BankAccountBuilder
{
    private ?string $iban = null;
    private ?string $bic = null;
    private ?string $name = null;
    private ?string $otherId = null;

    public function iban(?string $iban): self
    {
        $this->iban = $iban;

        return $this;
    }

    public function bic(?string $bic): self
    {
        $this->bic = $bic;

        return $this;
    }

    public function name(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function otherId(?string $otherId): self
    {
        $this->otherId = $otherId;

        return $this;
    }

    public function build(): BankAccount
    {
        return new SimpleBankAccount($this->iban, $this->bic, $this->name, $this->otherId);
    }
}
