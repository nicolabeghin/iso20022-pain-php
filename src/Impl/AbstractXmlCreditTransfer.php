<?php

declare(strict_types=1);

namespace Iso20022\Pain\Impl;

use DateTimeImmutable;
use InvalidArgumentException;
use Iso20022\Pain\BankAccount;
use Iso20022\Pain\ChargeBearer;
use Iso20022\Pain\CreditTransferOperation;
use Iso20022\Pain\CustomerCreditTransferInitiationVersion;
use Iso20022\Pain\Party;
use Iso20022\Pain\PostalAddress;
use Iso20022\Pain\Priority;
use Iso20022\Pain\Transaction;
use Iso20022\Pain\Util\BicUtil;
use Iso20022\Pain\Util\Decimal;
use Iso20022\Pain\Util\IbanUtil;
use XMLWriter;

/**
 * Common state, defaults and XMLWriter helpers shared by every message flavour.
 *
 * Defaults follow bank4j's JAXBCreditTransferV03/V09 constructors: id = creation date-time formatted
 * as yyyyMMddHHmmss, creation date-time = now, requested execution date = tomorrow, charge bearer = SLEV,
 * batch booking = false, payment method = TRF.
 */
abstract class AbstractXmlCreditTransfer implements CreditTransferOperation
{
    protected const FORMAT_AS_ID = 'YmdHis';

    protected readonly DateTimeImmutable $creationDateTime;
    protected readonly ?DateTimeImmutable $requestedExecutionDate;
    protected readonly string $id;
    protected readonly ChargeBearer $chargeBearer;
    protected readonly bool $batchBooking;
    protected readonly bool $instantPayment;
    protected readonly string $paymentMethodCode;
    /** @var array<\Iso20022\Pain\Transaction> */
    protected readonly array $transactions;

    /**
     * @param array<\Iso20022\Pain\Transaction> $transactions at least one
     */
    public function __construct(
        protected readonly CustomerCreditTransferInitiationVersion $version,
        protected readonly ?Priority $instructionPriority,
        protected readonly ?string $serviceLevelCode,
        protected readonly ?Party $debtor,
        protected readonly BankAccount $debtorAccount,
        array $transactions,
        ?string $id,
        ?DateTimeImmutable $creationDateTime,
        ?DateTimeImmutable $requestedExecutionDate,
        protected readonly ?DateTimeImmutable $requestedExecutionDateTime,
        ?ChargeBearer $chargeBearer,
        ?bool $batchBooking,
        ?bool $instantPayment,
        ?string $paymentMethodCode,
    ) {
        if ($transactions === []) {
            throw new InvalidArgumentException('At least 1 transaction is required');
        }
        if ($requestedExecutionDate !== null && $requestedExecutionDateTime !== null) {
            throw new InvalidArgumentException('You cannot specify both requestedExecutionDate and requestedExecutionDateTime');
        }
        $this->transactions = array_values($transactions);
        $this->creationDateTime = $creationDateTime ?? new DateTimeImmutable();
        $this->requestedExecutionDate = $requestedExecutionDateTime === null
            ? ($requestedExecutionDate ?? (new DateTimeImmutable('today'))->modify('+1 day'))
            : null;
        $this->id = $id ?? $this->creationDateTime->format(self::FORMAT_AS_ID);
        $this->chargeBearer = $chargeBearer ?? ChargeBearer::SLEV;
        $this->batchBooking = $batchBooking ?? false;
        $this->instantPayment = $instantPayment ?? false;
        $this->paymentMethodCode = $paymentMethodCode ?? 'TRF';
    }

    public function marshal(bool $formatted = false): string
    {
        $w = new XMLWriter();
        $w->openMemory();
        $w->setIndent($formatted);
        $w->setIndentString('    ');
        $w->startDocument('1.0', 'UTF-8', 'yes');
        $this->writeDocument($w);
        $w->endDocument();
        $xml = $w->outputMemory();

        // XMLWriter breaks the line after the declaration and after the document; bank4j's unformatted output does not.
        return $formatted ? $xml : rtrim((string)preg_replace('/\?>\n/', '?>', $xml, 1), "\n");
    }

    abstract protected function writeDocument(XMLWriter $w): void;

    /**
     * Element name carrying the BIC inside <FinInstnId>: "BIC" (2009 schemas) or "BICFI" (2019 schemas).
     */
    abstract protected function bicElementName(): string;

    /**
     * Whether <AdrTp> wraps its code in <Cd> (PostalAddress24) or carries it directly (PostalAddress6).
     */
    abstract protected function addressTypeIsChoice(): bool;

    protected function writeParty(XMLWriter $w, string $element, ?Party $party): void
    {
        $w->startElement($element);
        if ($party !== null) {
            if ($party->getName() !== null) {
                $w->writeElement('Nm', $party->getName());
            }
            if ($party->getPostalAddress() !== null) {
                $this->writePostalAddress($w, $party->getPostalAddress());
            }
            if ($party->getOtherId() !== null) {
                $this->writeOrganisationOtherId($w, $party->getOtherId(), $party->getOtherIdIssuer());
            }
        }
        $w->endElement();
    }

    protected function writeOrganisationOtherId(XMLWriter $w, string $id, ?string $issuer): void
    {
        $w->startElement('Id');
        $w->startElement('OrgId');
        $w->startElement('Othr');
        $w->writeElement('Id', $id);
        if ($issuer !== null) {
            $w->writeElement('Issr', $issuer);
        }
        $w->endElement();
        $w->endElement();
        $w->endElement();
    }

    protected function writePostalAddress(XMLWriter $w, PostalAddress $address): void
    {
        $w->startElement('PstlAdr');
        if ($address->getType() !== null) {
            if ($this->addressTypeIsChoice()) {
                $w->startElement('AdrTp');
                $w->writeElement('Cd', $address->getType());
                $w->endElement();
            } else {
                $w->writeElement('AdrTp', $address->getType());
            }
        }
        $this->writeOptional($w, 'Dept', $address->getDepartment());
        $this->writeOptional($w, 'SubDept', $address->getSubDepartment());
        $this->writeOptional($w, 'StrtNm', $address->getStreetName());
        $this->writeOptional($w, 'BldgNb', $address->getBuildingNumber());
        $this->writeOptional($w, 'PstCd', $address->getPostCode());
        $this->writeOptional($w, 'TwnNm', $address->getTownName());
        $this->writeOptional($w, 'CtrySubDvsn', $address->getCountrySubDivision());
        $this->writeOptional($w, 'Ctry', $address->getCountry());
        foreach ($address->getAddressLines() as $line) {
            $w->writeElement('AdrLine', $line);
        }
        $w->endElement();
    }

    /**
     * <CashAccount>: IBAN or generic identifier, optional name. IBANs are validated (iban4j semantics).
     */
    protected function writeCashAccount(XMLWriter $w, string $element, BankAccount $account): void
    {
        $w->startElement($element);
        $w->startElement('Id');
        $iban = $account->getIban();
        if ($iban !== null && $iban !== '') {
            IbanUtil::validate($iban);
            $w->writeElement('IBAN', $iban);
        } elseif ($account->getOtherId() !== null && $account->getOtherId() !== '') {
            $w->startElement('Othr');
            $w->writeElement('Id', $account->getOtherId());
            $w->endElement();
        } else {
            throw new InvalidArgumentException('IBAN or otherId must be provided');
        }
        $w->endElement();
        $this->writeOptional($w, 'Nm', $account->getName());
        $w->endElement();
    }

    /**
     * <...Agt><FinInstnId>[<BIC(FI)>]</FinInstnId></...Agt>; with $mandatory = false nothing is written
     * when the account has no BIC.
     */
    protected function writeAgent(XMLWriter $w, string $element, BankAccount $account, bool $mandatory): void
    {
        $bic = $account->getBic();
        if (($bic === null || $bic === '') && !$mandatory) {
            return;
        }
        $w->startElement($element);
        $w->startElement('FinInstnId');
        if ($bic !== null && $bic !== '') {
            BicUtil::validate($bic);
            $w->writeElement($this->bicElementName(), $bic);
        }
        $w->endElement();
        $w->endElement();
    }

    protected function writeOptional(XMLWriter $w, string $element, ?string $value): void
    {
        if ($value !== null) {
            $w->writeElement($element, $value);
        }
    }

    /** java.time.LocalDateTime ISO format, no offset. */
    protected function formatLocalDateTime(DateTimeImmutable $dateTime): string
    {
        return $dateTime->format('Y-m-d\TH:i:s');
    }

    /** java.time ISO_OFFSET_DATE_TIME: "Z" for UTC, "+01:00" otherwise. */
    protected function formatOffsetDateTime(DateTimeImmutable $dateTime): string
    {
        $offset = $dateTime->getOffset() === 0 ? 'Z' : $dateTime->format('P');

        return $dateTime->format('Y-m-d\TH:i:s') . $offset;
    }

    public function getInstructionPriority(): ?Priority
    {
        return $this->instructionPriority;
    }

    public function getServiceLevelCode(): ?string
    {
        return $this->serviceLevelCode;
    }

    public function getDebtor(): ?Party
    {
        return $this->debtor;
    }

    public function getDebtorAccount(): BankAccount
    {
        return $this->debtorAccount;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCreationDateTime(): DateTimeImmutable
    {
        return $this->creationDateTime;
    }

    public function getRequestedExecutionDate(): ?DateTimeImmutable
    {
        return $this->requestedExecutionDate;
    }

    public function getRequestedExecutionDateTime(): ?DateTimeImmutable
    {
        return $this->requestedExecutionDateTime;
    }

    public function getTransactions(): array
    {
        return $this->transactions;
    }

    public function getChargeBearer(): ChargeBearer
    {
        return $this->chargeBearer;
    }

    public function isBatchBooking(): bool
    {
        return $this->batchBooking;
    }

    public function getPaymentMethodCode(): string
    {
        return $this->paymentMethodCode;
    }

    public function isInstantPayment(): bool
    {
        return $this->instantPayment;
    }

    public function getControlSum(): string
    {
        return Decimal::sum(array_map(static fn (Transaction $t): string => $t->getAmount(), $this->transactions));
    }

    public function getVersion(): CustomerCreditTransferInitiationVersion
    {
        return $this->version;
    }
}
