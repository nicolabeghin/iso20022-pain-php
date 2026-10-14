<?php

declare(strict_types=1);

namespace Iso20022\Pain\Validator\Constraints;

use Attribute;
use Iso20022\Pain\Validator\Constraint;
use Iso20022\Pain\Validator\ConstraintValidators\BICValidator;

/**
 * The property must hold a valid BIC8/BIC11 (null or empty is accepted, like bank4j's @BIC).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class BIC implements Constraint
{
    public function __construct(public readonly string $message = 'must be a valid BIC')
    {
    }

    public function message(): string
    {
        return $this->message;
    }

    public function validatedBy(): string
    {
        return BICValidator::class;
    }
}
