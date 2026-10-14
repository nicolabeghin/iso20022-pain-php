<?php

declare(strict_types=1);

namespace Iso20022\Pain\Util;

/**
 * Character set rules for ISO 20022 reference elements (MsgId, EndToEndId, InstrId, ...), as set by the
 * EPC SEPA implementation guidelines: Latin letters, digits, "/ - ? : ( ) . , ' + space"; no leading or
 * trailing "/" and no "//" (EPC230-15 Clarification Paper on the Use of Slashes).
 *
 * PHP port of io.inisos.bank4j.util.Iso20022ReferenceElementValidator (same accepted set: it does
 * not include "?" and "'", matching bank4j's behaviour and the tests ported from it).
 */
final class Iso20022ReferenceElementValidator
{
    private const VALID_CHARS_PATTERN = '/^[A-Za-z0-9()+,.\/:\s-]+$/';
    private const INVALID_START_OR_END_PATTERN = '/(^[\s\/]+)|([\s\/]+$)/';
    private const INVALID_POSITION = '//';
    private const DEFAULT_INVALID_CHAR_REPLACEMENT = '.';
    private const DEFAULT_REPLACEMENTS = ['/' => '-'];

    public static function isValidCharacterSet(?string $input): bool
    {
        if ($input === null || $input === '') {
            return false;
        }

        return preg_match(self::VALID_CHARS_PATTERN, $input) === 1
            && preg_match(self::INVALID_START_OR_END_PATTERN, $input) !== 1
            && !str_contains($input, self::INVALID_POSITION);
    }

    /**
     * Replace characters outside the reference-element set (default replacement "."; "/" becomes "-"
     * unless $replacements says otherwise), then strip leading/trailing "/" or spaces and any "//".
     *
     * @param array<string, string> $replacements map of invalid character => replacement character
     */
    public static function sanitizeToCharacterSet(string $input, ?array $replacements = null): string
    {
        $replacements ??= self::DEFAULT_REPLACEMENTS;
        $sanitized = '';
        foreach (mb_str_split($input) as $char) {
            $sanitized .= preg_match(self::VALID_CHARS_PATTERN, $char) === 1
                ? $char
                : ($replacements[$char] ?? self::DEFAULT_INVALID_CHAR_REPLACEMENT);
        }
        $result = (string)preg_replace(self::INVALID_START_OR_END_PATTERN, '', $sanitized);

        return str_replace(self::INVALID_POSITION, '', $result);
    }

    private function __construct()
    {
    }
}
