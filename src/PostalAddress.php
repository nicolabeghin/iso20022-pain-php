<?php

declare(strict_types=1);

namespace Iso20022\Pain;

/**
 * ISO 20022 PostalAddress24. PHP port of io.inisos.bank4j.PostalAddress.
 */
interface PostalAddress
{
    public function getType(): ?string;

    public function getDepartment(): ?string;

    public function getSubDepartment(): ?string;

    public function getStreetName(): ?string;

    public function getBuildingNumber(): ?string;

    public function getPostCode(): ?string;

    public function getTownName(): ?string;

    public function getCountrySubDivision(): ?string;

    public function getCountry(): ?string;

    /**
     * @return array<string>
     */
    public function getAddressLines(): array;
}
