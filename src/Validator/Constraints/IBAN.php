<?php

declare(strict_types=1);

namespace Iso20022\Pain\Validator\Constraints;

use Attribute;
use Iso20022\Pain\Validator\Constraint;
use Iso20022\Pain\Validator\ConstraintValidators\IBANValidator;

/**
 * The property must hold a valid IBAN (null or empty is accepted, like bank4j's @IBAN).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class IBAN implements Constraint
{
    public function __construct(public readonly string $message = 'must be a valid IBAN')
    {
    }

    public function message(): string
    {
        return $this->message;
    }

    public function validatedBy(): string
    {
        return IBANValidator::class;
    }
}
