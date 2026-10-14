<?php

declare(strict_types=1);

namespace Iso20022\Pain\Validator\Constraints;

use Attribute;
use Iso20022\Pain\Validator\Constraint;
use Iso20022\Pain\Validator\ConstraintValidators\SizeValidator;

/**
 * String length (or element count) bounds, counterpart of jakarta.validation.constraints.Size.
 * With $each = true the bounds apply to every element of an iterable property
 * (bank4j: Set<@Valid @Size(max = 140) String>).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Size implements Constraint
{
    public function __construct(
        public readonly int $min = 0,
        public readonly int $max = PHP_INT_MAX,
        public readonly bool $each = false,
        public readonly ?string $message = null,
    ) {
    }

    public function message(): string
    {
        return $this->message ?? sprintf('size must be between %d and %d', $this->min, $this->max);
    }

    public function validatedBy(): string
    {
        return SizeValidator::class;
    }
}
