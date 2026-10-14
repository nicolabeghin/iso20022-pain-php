<?php

declare(strict_types=1);

namespace Iso20022\Pain\Validator\ConstraintValidators;

use Countable;
use Iso20022\Pain\Validator\Constraint;
use Iso20022\Pain\Validator\ConstraintValidator;
use Iso20022\Pain\Validator\Constraints\Size;

/**
 * Counterpart of Hibernate Validator's SizeValidator: null passes, strings are measured in
 * characters, arrays/Countable in elements.
 */
final class SizeValidator implements ConstraintValidator
{
    public function isValid(mixed $value, Constraint $constraint): bool
    {
        if (!$constraint instanceof Size || $value === null) {
            return true;
        }
        if (is_string($value)) {
            $size = mb_strlen($value);
        } elseif (is_array($value) || $value instanceof Countable) {
            $size = count($value);
        } else {
            return true;
        }

        return $size >= $constraint->min && $size <= $constraint->max;
    }
}
