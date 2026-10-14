<?php

declare(strict_types=1);

namespace Iso20022\Pain\Impl;

use Iso20022\Pain\PostalAddress;
use Iso20022\Pain\PostalAddressBuilder;

/**
 * PHP port of io.inisos.bank4j.impl.SimplePostalAddressBuilder.
 */
final class SimplePostalAddressBuilder implements PostalAddressBuilder
{
    private ?string $type = null;
    private ?string $department = null;
    private ?string $subDepartment = null;
    private ?string $streetName = null;
    private ?string $buildingNumber = null;
    private ?string $postCode = null;
    private ?string $townName = null;
    private ?string $countrySubDivision = null;
    private ?string $country = null;
    /** @var array<string> */
    private array $addressLines = [];

    public function type(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function department(?string $department): self
    {
        $this->department = $department;

        return $this;
    }

    public function subDepartment(?string $subDepartment): self
    {
        $this->subDepartment = $subDepartment;

        return $this;
    }

    public function streetName(?string $streetName): self
    {
        $this->streetName = $streetName;

        return $this;
    }

    public function buildingNumber(?string $buildingNumber): self
    {
        $this->buildingNumber = $buildingNumber;

        return $this;
    }

    public function postCode(?string $postCode): self
    {
        $this->postCode = $postCode;

        return $this;
    }

    public function townName(?string $townName): self
    {
        $this->townName = $townName;

        return $this;
    }

    public function countrySubDivision(?string $countrySubDivision): self
    {
        $this->countrySubDivision = $countrySubDivision;

        return $this;
    }

    public function country(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function addressLines(iterable $addressLines): self
    {
        foreach ($addressLines as $line) {
            $this->addressLines[] = $line;
        }

        return $this;
    }

    public function addressLine(string $addressLine): self
    {
        $this->addressLines[] = $addressLine;

        return $this;
    }

    public function build(): PostalAddress
    {
        return new SimplePostalAddress(
            $this->type,
            $this->department,
            $this->subDepartment,
            $this->streetName,
            $this->buildingNumber,
            $this->postCode,
            $this->townName,
            $this->countrySubDivision,
            $this->country,
            $this->addressLines,
        );
    }
}
