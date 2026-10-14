# iso20022-pain-php

Generate **ISO 20022 Payments Initiation** (`pain.001`) XML credit transfers in PHP, including the
**Italian CBI `CBIPaymentRequest` 00.04.01** profile used to upload "distinte di bonifico" (SEPA and
FAST/instant transfers) to Italian corporate-banking portals.

> **This library is a PHP port of [bank4j](https://github.com/inisos/bank4j)** by Patrice Blanchardie
> (Java, MIT License). The public model (`Party`, `BankAccount`, `Transaction`, `PostalAddress`, their
> builders, the `CreditTransfer` operation and the IBAN / BIC / ISO 20022 character-set validators) is
> mirrored one to one, and bank4j's test suite has been ported: the `pain.001.001.03` and
> `pain.001.001.09` generators reproduce bank4j's output **byte for byte** (see `tests/fixtures`,
> extracted from the original Java tests). The CBI profile is an addition of this port.
> Original copyright and license: see [LICENSE.bank4j](LICENSE.bank4j).

Supported messages:

| `CustomerCreditTransferInitiationVersion` | Namespace | Notes |
|---|---|---|
| `V03` | `urn:iso:std:iso:20022:tech:xsd:pain.001.001.03` | ported from bank4j |
| `V09` | `urn:iso:std:iso:20022:tech:xsd:pain.001.001.09` | ported from bank4j, incl. `requestedExecutionDateTime` and `instantPayment` (`LclInstrm/INST`) |
| `CBI_00_04_01` | `urn:CBI:xsd:CBIPaymentRequest.00.04.01` | Italian CBI profile of pain.001.001.09 (added here) |

bank4j's Swiss (`pain.001.001.03.ch.02`) and German (`pain.001.003.03`) flavours are not ported.

Requirements: PHP >= 8.2 with `dom`, `libxml`, `xmlwriter`, `mbstring` (`intl` recommended for
`CbiCharset` transliteration). No runtime Composer dependencies.

## Installation

```bash
composer require nicolabeghin/iso20022-pain-php
```

## Usage

### Validation

Constraints are PHP attributes evaluated by the reflection-based `Validator`
(the counterpart of bank4j's Bean Validation annotations):

```php
use Iso20022\Pain\Validator\Constraints\{BIC, IBAN, Iso20022CharacterSet, Size};
use Iso20022\Pain\Validator\Validator;

final class MyRecord
{
    public function __construct(
        #[IBAN] public readonly ?string $iban,
        #[BIC] public readonly ?string $bic,
        #[Iso20022CharacterSet] #[Size(max: 35)] public readonly string $reference,
    ) {}
}

$violations = (new Validator())->validate(new MyRecord('x', 'BDFEFRPP', '/bad//ref'));
foreach ($violations as $v) {
    echo $v->getPropertyPath(), ': ', $v->getMessage(), PHP_EOL; // iban: must be a valid IBAN ...
}
```

Standalone helpers: `Util\IbanUtil::validate()/isValid()/normalize()`, `Util\BicUtil`,
`Util\Iso20022ReferenceElementValidator::isValidCharacterSet()/sanitizeToCharacterSet()`.

### Generating a pain.001 message (minimal, V09)

```php
use Iso20022\Pain\Bank;
use Iso20022\Pain\CustomerCreditTransferInitiationVersion as Version;

$debtorAccount = Bank::simpleBankAccount()->iban('FR7610011000201234567890188')->build();

$transaction = Bank::simpleTransaction()
    ->account(Bank::simpleBankAccount()->iban('FR7630001007941234567890185')->build())
    ->amount('12.34')
    ->currency('EUR')
    ->endToEndId('Transfer reference 1')
    ->build();

$creditTransfer = Bank::xmlCreditTransferSepa(Version::V09)
    ->debtorAccount($debtorAccount)
    ->transaction($transaction)
    ->requestedExecutionDate(new DateTimeImmutable('tomorrow'))
    ->build();

$xml = $creditTransfer->marshal(true); // true = indented output
```

Optional fields mirror bank4j: `debtor(Party)` with `postalAddress`, `bic`/`name` on accounts,
`id`, `creationDateTime`, `instructionPriority`, `chargeBearer` (default `SLEV`), `batchBooking`,
`requestedExecutionDateTime` and `instantPayment` (V09), per-transaction `id`, `chargeBearerCode`,
`intermediaryAgents` and `remittanceInformationUnstructured`. Extensions over bank4j:
`Party::otherId($id, $issuer)` (organisation identifier), `Transaction::categoryPurposeCode()` /
`purposeCode()` and `CreditTransferOperationBuilder::paymentMethodCode()` (`TRF`/`TRA`).

### Italian CBI CBIPaymentRequest 00.04.01 (salary payments example)

```php
use Iso20022\Pain\Bank;
use Iso20022\Pain\Cbi\CbiPaymentRequestValidator;
use Iso20022\Pain\CustomerCreditTransferInitiationVersion as Version;
use Iso20022\Pain\SchemaValidator;

$company = Bank::simpleParty()
    ->name('Azienda S.r.l.')
    ->otherId('ABCDE12F', 'CBI')      // CUC assigned by the bank: mandatory
    ->build();

$salary = Bank::simpleTransaction()
    ->party(Bank::simpleParty()->name('Mario Rossi')->build())
    ->account(Bank::simpleBankAccount()->iban('IT35X0100503201000000012345')->build())
    ->amount('1850.00')
    ->currency('EUR')
    ->endToEndId('CED202609-1')
    ->categoryPurposeCode('SALA')     // mandatory for Italian creditor IBANs
    ->purposeCode('SALA')
    ->remittanceInformationUnstructured(['Retribuzione Settembre 2026'])
    ->build();

$transfer = Bank::xmlCreditTransferSepa(Version::CBI_00_04_01) // SvcLvl SEPA; ->instantPayment(true) gives FAST
    ->debtor($company)
    ->debtorAccount(Bank::simpleBankAccount()->iban('IT60X0542811101000000123456')->build())
    ->transaction($salary)
    ->requestedExecutionDate(new DateTimeImmutable('2026-10-27'))
    ->batchBooking(true)
    ->build();

$issues = (new CbiPaymentRequestValidator())->validate($transfer); // CBI application checks (STIP-MO-001 §3.9.1.2)
$xml = $transfer->marshal(true);
SchemaValidator::validateCreditTransfer(Version::CBI_00_04_01, $xml); // throws XmlException if not schema-valid
```

What the CBI generator does differently from the plain ISO message (see the class docblock of
`Impl\XmlCreditTransferCbi000401` for the references):

- root `<CBIPaymentRequest>`, exactly one `<PmtInf>`; `<InitgPty>` carries the **CUC** with issuer `CBI`;
- `<DbtrAgt>` is identified by the 5-digit **ABI** derived from the Italian debtor IBAN;
- `instantPayment(true)` maps to service level **`FAST`** (CBI does not use `LclInstrm/INST`);
- `<InstrId>` is mandatory (transaction id, or its 1-based position);
- accounts must be IBANs, charge bearer must be `SLEV`, per-transaction charge bearer and intermediary
  agents are rejected.

`Cbi\CbiServiceLevel` holds the per-service-level limits (SEPA vs FAST vs URGP: amount caps, single
remittance line for FAST, no future execution date for FAST) and `Cbi\CbiCharset` restricts free text
to the CBI/EPC minimum character set (Appendix A).

## Development

Everything runs inside Docker, no local PHP needed:

```bash
make docker-build   # once
make install        # composer install
make test           # PHPUnit
make phpstan        # static analysis (level 8)
make cs             # PSR-12
make check          # all of the above
```

## References

- bank4j (original implementation): https://github.com/inisos/bank4j
- ISO 20022 message definitions and External Code Sets: https://www.iso20022.org/
- EPC SEPA Credit Transfer / SCT Inst Implementation Guidelines, EPC230-15: https://www.europeanpaymentscouncil.eu/document-library
- CBI XSD `CBIPaymentRequest.00.04.01.xsd` (copy published by OCA): https://github.com/OCA/l10n-italy/tree/16.0/l10n_it_sct_cbi/data/standards
- CBI STIP-MO-001 "Nuova Architettura CBI – Area Pagamenti" v00.04.00: https://www.protezionecivile.gov.it/static/068269bfd70e89353e4723e45cec7b65/tracciato-bonifci.pdf
- CBI Scpa: https://www.cbi-org.eu/

## License

MIT, see [LICENSE](LICENSE). Portions derived from bank4j, Copyright (c) 2023 Patrice Blanchardie, MIT
License ([LICENSE.bank4j](LICENSE.bank4j)).
