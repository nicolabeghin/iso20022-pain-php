<?php

declare(strict_types=1);

namespace Iso20022\Pain\Impl;

use Iso20022\Pain\Party;
use Iso20022\Pain\PostalAddress;

/**
 * PHP port of io.inisos.bank4j.impl.SimpleParty (plus the otherId/issuer extension).
 */
final class SimpleParty implements Party
{
    public function __construct(
        private readonly ?string $name,
        private readonly ?PostalAddress $postalAddress,
        private readonly ?string $otherId = null,
        private readonly ?string $otherIdIssuer = null,
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPostalAddress(): ?PostalAddress
    {
        return $this->postalAddress;
    }

    public function getOtherId(): ?string
    {
        return $this->otherId;
    }

    public function getOtherIdIssuer(): ?string
    {
        return $this->otherIdIssuer;
    }

    public function __toString(): string
    {
        return sprintf("SimpleParty[name='%s', otherId='%s']", $this->name ?? 'null', $this->otherId ?? 'null');
    }
}
