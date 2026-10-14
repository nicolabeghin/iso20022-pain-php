<?php

declare(strict_types=1);

namespace Iso20022\Pain\Util;

/**
 * IBAN validation (ISO 13616): country code, registered national length and mod-97 check digits.
 * Counterpart of iban4j's IbanUtil as used by bank4j. Validation is strict like iban4j (no spaces);
 * use normalize() first for user input.
 */
final class IbanUtil
{
    /** IBAN length per country (SWIFT IBAN Registry). */
    private const LENGTHS = [
        'AD' => 24, 'AE' => 23, 'AL' => 28, 'AT' => 20, 'AZ' => 28, 'BA' => 20, 'BE' => 16, 'BG' => 22,
        'BH' => 22, 'BI' => 27, 'BR' => 29, 'BY' => 28, 'CH' => 21, 'CR' => 22, 'CY' => 28, 'CZ' => 24,
        'DE' => 22, 'DJ' => 27, 'DK' => 18, 'DO' => 28, 'EE' => 20, 'EG' => 29, 'ES' => 24, 'FI' => 18,
        'FK' => 18, 'FO' => 18, 'FR' => 27, 'GB' => 22, 'GE' => 22, 'GI' => 23, 'GL' => 18, 'GR' => 27,
        'GT' => 28, 'HR' => 21, 'HU' => 28, 'IE' => 22, 'IL' => 23, 'IQ' => 23, 'IS' => 26, 'IT' => 27,
        'JO' => 30, 'KW' => 30, 'KZ' => 20, 'LB' => 28, 'LC' => 32, 'LI' => 21, 'LT' => 20, 'LU' => 20,
        'LV' => 21, 'LY' => 25, 'MC' => 27, 'MD' => 24, 'ME' => 22, 'MK' => 19, 'MN' => 20, 'MR' => 27,
        'MT' => 31, 'MU' => 30, 'NI' => 28, 'NL' => 18, 'NO' => 15, 'OM' => 23, 'PK' => 24, 'PL' => 28,
        'PS' => 29, 'PT' => 25, 'QA' => 29, 'RO' => 24, 'RS' => 22, 'RU' => 33, 'SA' => 24, 'SC' => 31,
        'SD' => 18, 'SE' => 24, 'SI' => 19, 'SK' => 24, 'SM' => 27, 'SO' => 23, 'ST' => 25, 'SV' => 28,
        'TL' => 23, 'TN' => 24, 'TR' => 26, 'UA' => 29, 'VA' => 22, 'VG' => 24, 'XK' => 20, 'YE' => 30,
    ];

    /**
     * Upper-case and strip every non alphanumeric character (spaces, dots, dashes typed by users).
     */
    public static function normalize(string $iban): string
    {
        return (string)preg_replace('/[^A-Z0-9]/', '', strtoupper($iban));
    }

    /**
     * @throws \Iso20022\Pain\Util\IbanException with the reason the IBAN is invalid
     */
    public static function validate(string $iban): void
    {
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $iban)) {
            throw new IbanException(sprintf('Invalid IBAN format "%s".', $iban));
        }
        $country = substr($iban, 0, 2);
        if (!isset(self::LENGTHS[$country])) {
            throw new IbanException(sprintf('Unsupported IBAN country "%s".', $country));
        }
        if (strlen($iban) !== self::LENGTHS[$country]) {
            throw new IbanException(sprintf('IBAN for %s must be %d characters long.', $country, self::LENGTHS[$country]));
        }
        if (self::mod97(substr($iban, 4) . substr($iban, 0, 4)) !== 1) {
            throw new IbanException(sprintf('Invalid IBAN check digits in "%s".', $iban));
        }
    }

    public static function isValid(string $iban): bool
    {
        try {
            self::validate($iban);

            return true;
        } catch (IbanException) {
            return false;
        }
    }

    public static function getCountryCode(string $iban): string
    {
        return substr($iban, 0, 2);
    }

    /**
     * Piece-wise mod 97 over the numeric expansion (A=10 ... Z=35), avoiding big integers.
     */
    private static function mod97(string $rearranged): int
    {
        $remainder = 0;
        foreach (str_split($rearranged) as $char) {
            $digits = ctype_alpha($char) ? (string)(ord($char) - 55) : $char;
            foreach (str_split($digits) as $digit) {
                $remainder = ($remainder * 10 + (int)$digit) % 97;
            }
        }

        return $remainder;
    }
}
