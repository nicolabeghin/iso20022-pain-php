<?php

declare(strict_types=1);

namespace Iso20022\Pain\Validator;

interface ConstraintValidator
{
    public function isValid(mixed $value, Constraint $constraint): bool;
}
