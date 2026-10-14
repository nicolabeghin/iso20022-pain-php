<?php

declare(strict_types=1);

namespace Iso20022\Pain;

/**
 * PHP port of io.inisos.bank4j.PartyBuilder (plus otherId extension, see Party).
 */
interface PartyBuilder
{
    public function name(?string $name): self;

    public function postalAddress(?PostalAddress $postalAddress): self;

    public function otherId(?string $otherId, ?string $issuer = null): self;

    public function build(): Party;
}
