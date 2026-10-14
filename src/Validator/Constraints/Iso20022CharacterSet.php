<?php

declare(strict_types=1);

namespace Iso20022\Pain\Validator\Constraints;

use Attribute;
use Iso20022\Pain\Validator\Constraint;
use Iso20022\Pain\Validator\ConstraintValidators\Iso20022CharacterSetValidator;

/**
 * The property must only use characters valid for ISO 20022 reference elements (null is accepted).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Iso20022CharacterSet implements Constraint
{
    public function __construct(
        public readonly string $message = 'must only use characters that are valid according to ISO20022',
    ) {
    }

    public function message(): string
    {
        return $this->message;
    }

    public function validatedBy(): string
    {
        return Iso20022CharacterSetValidator::class;
    }
}
