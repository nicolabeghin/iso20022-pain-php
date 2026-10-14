<?php

declare(strict_types=1);

namespace Iso20022\Pain\Validator;

use Iso20022\Pain\Validator\Constraints\Size;
use ReflectionClass;
use ReflectionProperty;

/**
 * Reflection-based validator: evaluates every Constraint attribute declared on the properties of an
 * object and returns the violations. Counterpart of jakarta.validation.Validator#validate as used in
 * bank4j's tests (IBANValidatorTest, BICValidatorTest, TransactionValidatorTest, ...).
 */
final class Validator
{
    /**
     * @return array<\Iso20022\Pain\Validator\ConstraintViolation>
     */
    public function validate(object $object): array
    {
        $violations = [];
        $class = new ReflectionClass($object);
        do {
            foreach ($class->getProperties() as $property) {
                array_push($violations, ...$this->validateProperty($object, $property));
            }
            $class = $class->getParentClass();
        } while ($class !== false);

        return $violations;
    }

    /**
     * @return array<\Iso20022\Pain\Validator\ConstraintViolation>
     */
    private function validateProperty(object $object, ReflectionProperty $property): array
    {
        $violations = [];
        $value = $property->isInitialized($object) ? $property->getValue($object) : null;
        foreach ($property->getAttributes() as $attribute) {
            $constraint = $attribute->newInstance();
            if (!$constraint instanceof Constraint) {
                continue;
            }
            $validatorClass = $constraint->validatedBy();
            $validator = new $validatorClass();
            $values = $constraint instanceof Size && $constraint->each && is_iterable($value) ? $value : [$value];
            foreach ($values as $item) {
                if (!$validator->isValid($item, $constraint)) {
                    $violations[] = new ConstraintViolation($constraint->message(), $property->getName(), $constraint, $item);
                }
            }
        }

        return $violations;
    }
}
