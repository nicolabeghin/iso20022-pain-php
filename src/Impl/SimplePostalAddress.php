<?php

declare(strict_types=1);

namespace Iso20022\Pain\Impl;

use Iso20022\Pain\PostalAddress;
use Iso20022\Pain\Validator\Constraints\Size;

/**
 * PHP port of io.inisos.bank4j.impl.SimplePostalAddress.
 */
final class SimplePostalAddress implements PostalAddress
{
    /** @var array<string> */
    #[Size(max: 70, each: true)]
    private readonly array $addressLines;

    /**
     * @param array<string> $addressLines
     */
    public function __construct(
        #[Size(max: 4)]
        private readonly ?string $type,
        #[Size(max: 70)]
        private readonly ?string $department,
        #[Size(max: 70)]
        private readonly ?string $subDepartment,
        #[Size(max: 70)]
        private readonly ?string $streetName,
        #[Size(max: 16)]
        private readonly ?string $buildingNumber,
        #[Size(max: 16)]
        private readonly ?string $postCode,
        #[Size(max: 35)]
        private readonly ?string $townName,
        #[Size(max: 35)]
        private readonly ?string $countrySubDivision,
        #[Size(max: 2)]
        private readonly ?string $country,
        array $addressLines = [],
    ) {
        $this->addressLines = array_values($addressLines);
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function getSubDepartment(): ?string
    {
        return $this->subDepartment;
    }

    public function getStreetName(): ?string
    {
        return $this->streetName;
    }

    public function getBuildingNumber(): ?string
    {
        return $this->buildingNumber;
    }

    public function getPostCode(): ?string
    {
        return $this->postCode;
    }

    public function getTownName(): ?string
    {
        return $this->townName;
    }

    public function getCountrySubDivision(): ?string
    {
        return $this->countrySubDivision;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getAddressLines(): array
    {
        return $this->addressLines;
    }
}
