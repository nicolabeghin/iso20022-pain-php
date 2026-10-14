<?php

declare(strict_types=1);

namespace Iso20022\Pain\Impl;

use Iso20022\Pain\Party;
use Iso20022\Pain\PartyBuilder;
use Iso20022\Pain\PostalAddress;

/**
 * PHP port of io.inisos.bank4j.impl.SimplePartyBuilder.
 */
final class SimplePartyBuilder implements PartyBuilder
{
    private ?string $name = null;
    private ?PostalAddress $postalAddress = null;
    private ?string $otherId = null;
    private ?string $otherIdIssuer = null;

    public function name(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function postalAddress(?PostalAddress $postalAddress): self
    {
        $this->postalAddress = $postalAddress;

        return $this;
    }

    public function otherId(?string $otherId, ?string $issuer = null): self
    {
        $this->otherId = $otherId;
        $this->otherIdIssuer = $issuer;

        return $this;
    }

    public function build(): Party
    {
        return new SimpleParty($this->name, $this->postalAddress, $this->otherId, $this->otherIdIssuer);
    }
}
