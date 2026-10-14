<?php

declare(strict_types=1);

namespace Iso20022\Pain\Validator\ConstraintValidators;

use Iso20022\Pain\Util\IbanUtil;
use Iso20022\Pain\Validator\Constraint;
use Iso20022\Pain\Validator\ConstraintValidator;

/**
 * PHP port of io.inisos.bank4j.validator.constraintvalidators.IBANValidator.
 */
final class IBANValidator implements ConstraintValidator
{
    public function isValid(mixed $value, Constraint $constraint): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return is_string($value) && IbanUtil::isValid($value);
    }
}
