<?php

declare(strict_types=1);

namespace Iso20022\Pain\Impl;

use InvalidArgumentException;
use Iso20022\Pain\BankAccount;
use Iso20022\Pain\Validator\Constraints\BIC;
use Iso20022\Pain\Validator\Constraints\IBAN;

/**
 * PHP port of io.inisos.bank4j.impl.SimpleBankAccount.
 */
final class SimpleBankAccount implements BankAccount
{
    public function __construct(
        #[IBAN]
        private readonly ?string $iban,
        #[BIC]
        private readonly ?string $bic,
        private readonly ?string $name,
        private readonly ?string $otherId,
    ) {
        $this->requireIdentification();
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getOtherId(): ?string
    {
        return $this->otherId;
    }

    private function requireIdentification(): void
    {
        if (($this->iban === null || $this->iban === '') && ($this->otherId === null || $this->otherId === '')) {
            throw new InvalidArgumentException('IBAN or otherId must be provided');
        }
    }

    public function equals(BankAccount $other): bool
    {
        return $this->iban === $other->getIban() && $this->otherId === $other->getOtherId();
    }

    public function __toString(): string
    {
        return sprintf(
            "SimpleBankAccount[iban='%s', bic='%s', name='%s', otherId='%s']",
            $this->iban ?? 'null',
            $this->bic ?? 'null',
            $this->name ?? 'null',
            $this->otherId ?? 'null',
        );
    }
}
