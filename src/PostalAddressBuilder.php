<?php

declare(strict_types=1);

namespace Iso20022\Pain;

/**
 * PHP port of io.inisos.bank4j.PostalAddressBuilder.
 */
interface PostalAddressBuilder
{
    public function type(?string $type): self;

    public function department(?string $department): self;

    public function subDepartment(?string $subDepartment): self;

    public function streetName(?string $streetName): self;

    public function buildingNumber(?string $buildingNumber): self;

    public function postCode(?string $postCode): self;

    public function townName(?string $townName): self;

    public function countrySubDivision(?string $countrySubDivision): self;

    public function country(?string $country): self;

    /**
     * @param iterable<string> $addressLines
     */
    public function addressLines(iterable $addressLines): self;

    public function addressLine(string $addressLine): self;

    public function build(): PostalAddress;
}
