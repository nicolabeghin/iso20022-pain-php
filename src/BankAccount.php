<?php

declare(strict_types=1);

namespace Iso20022\Pain;

/**
 * A bank account identified by IBAN or by another identifier.
 *
 * PHP port of io.inisos.bank4j.BankAccount (https://github.com/inisos/bank4j, MIT).
 */
interface BankAccount
{
    public function getIban(): ?string;

    public function getBic(): ?string;

    public function getName(): ?string;

    public function getOtherId(): ?string;
}
