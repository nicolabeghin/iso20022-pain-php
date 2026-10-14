<?php

declare(strict_types=1);

namespace Iso20022\Pain;

/**
 * PHP port of io.inisos.bank4j.BankAccountBuilder.
 */
interface BankAccountBuilder
{
    public function iban(?string $iban): self;

    public function bic(?string $bic): self;

    public function name(?string $name): self;

    public function otherId(?string $otherId): self;

    public function build(): BankAccount;
}
