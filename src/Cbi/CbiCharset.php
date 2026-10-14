<?php

declare(strict_types=1);

namespace Iso20022\Pain\Cbi;

/**
 * Restricts free text to the minimum Latin character set every CBI bank must accept.
 *
 * CBI STIP-MO-001 Appendix A ("Caratteri ammessi"), aligned with the EPC SCT Implementation Guidelines:
 * a-z A-Z 0-9 / - ? : ( ) . , ' + and space. Other UTF-8 characters may be refused or converted by the
 * receiving bank (EPC217-08 SEPA Conversion Table), so accents are transliterated (è → e) and whatever is
 * left is dropped. Identifiers (MsgId, EndToEndId, ...) must additionally not start or end with "/" and
 * must not contain "//" (EPC230-15).
 */
final class CbiCharset
{
    private const ALLOWED_PATTERN = "/[^A-Za-z0-9\\/\\-?:().,'+ ]/";

    /**
     * Sanitise a text field (names, remittance information) and cap it at $maxLength characters.
     */
    public static function text(string $value, int $maxLength = PHP_INT_MAX): string
    {
        $value = self::transliterate($value);
        $value = (string)preg_replace(self::ALLOWED_PATTERN, ' ', $value);
        $value = trim((string)preg_replace('/\s+/', ' ', $value));

        return $maxLength === PHP_INT_MAX ? $value : substr($value, 0, $maxLength);
    }

    /**
     * Sanitise an identifier: like text() but spaces become "-", no leading/trailing "/" and no "//".
     */
    public static function identifier(string $value, int $maxLength = 35): string
    {
        $value = str_replace(' ', '-', self::text($value));
        while (str_contains($value, '//')) {
            $value = str_replace('//', '/', $value);
        }

        return trim(substr(trim($value, '/'), 0, $maxLength), '/');
    }

    public static function isAllowed(string $value): bool
    {
        return preg_match(self::ALLOWED_PATTERN, $value) !== 1;
    }

    private static function transliterate(string $value): string
    {
        if (function_exists('transliterator_transliterate')) {
            $result = transliterator_transliterate('Any-Latin; Latin-ASCII', $value);
            if ($result !== false) {
                return $result;
            }
        }
        $result = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $result === false ? $value : $result;
    }

    private function __construct()
    {
    }
}
