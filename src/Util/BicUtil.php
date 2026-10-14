<?php

declare(strict_types=1);

namespace Iso20022\Pain\Util;

/**
 * BIC validation (ISO 9362, BIC8 or BIC11). Counterpart of iban4j's BicUtil as used by bank4j.
 * Pattern taken from the ISO 20022 BICFIDec2014Identifier type.
 */
final class BicUtil
{
    private const PATTERN = '/^[A-Z0-9]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/';

    /**
     * @throws \Iso20022\Pain\Util\IbanException
     */
    public static function validate(string $bic): void
    {
        if (!preg_match(self::PATTERN, $bic)) {
            throw new IbanException(sprintf('Invalid BIC "%s".', $bic));
        }
    }

    public static function isValid(string $bic): bool
    {
        return (bool)preg_match(self::PATTERN, $bic);
    }
}
