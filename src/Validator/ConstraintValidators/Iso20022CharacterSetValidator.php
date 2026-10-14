<?php

declare(strict_types=1);

namespace Iso20022\Pain\Validator\ConstraintValidators;

use Iso20022\Pain\Util\Iso20022ReferenceElementValidator;
use Iso20022\Pain\Validator\Constraint;
use Iso20022\Pain\Validator\ConstraintValidator;

/**
 * PHP port of io.inisos.bank4j.validator.constraintvalidators.Iso20022CharacterSetValidator.
 */
final class Iso20022CharacterSetValidator implements ConstraintValidator
{
    public function isValid(mixed $value, Constraint $constraint): bool
    {
        if ($value === null) {
            return true;
        }

        return is_string($value) && Iso20022ReferenceElementValidator::isValidCharacterSet($value);
    }
}
