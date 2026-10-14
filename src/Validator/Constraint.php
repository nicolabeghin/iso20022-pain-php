<?php

declare(strict_types=1);

namespace Iso20022\Pain\Validator;

/**
 * A validation constraint declared as a PHP attribute on a property (counterpart of a Bean
 * Validation annotation in bank4j).
 */
interface Constraint
{
    public function message(): string;

    /**
     * @return class-string<\Iso20022\Pain\Validator\ConstraintValidator>
     */
    public function validatedBy(): string;
}
