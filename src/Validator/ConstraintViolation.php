<?php

declare(strict_types=1);

namespace Iso20022\Pain\Validator;

/**
 * One failed constraint on one property (counterpart of jakarta.validation.ConstraintViolation).
 */
final class ConstraintViolation
{
    public function __construct(
        private readonly string $message,
        private readonly string $propertyPath,
        private readonly Constraint $constraint,
        private readonly mixed $invalidValue,
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getPropertyPath(): string
    {
        return $this->propertyPath;
    }

    public function getConstraint(): Constraint
    {
        return $this->constraint;
    }

    public function getInvalidValue(): mixed
    {
        return $this->invalidValue;
    }
}
