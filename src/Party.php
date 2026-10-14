<?php

declare(strict_types=1);

namespace Iso20022\Pain;

/**
 * A party (debtor, creditor, initiating party). PHP port of io.inisos.bank4j.Party.
 *
 * Extension over bank4j: an optional organisation identifier (<Id>/<OrgId>/<Othr>/<Id> + <Issr>),
 * required by CBI for the initiating party (CUC code issued by "CBI").
 */
interface Party
{
    public function getName(): ?string;

    public function getPostalAddress(): ?PostalAddress;

    public function getOtherId(): ?string;

    public function getOtherIdIssuer(): ?string;
}
