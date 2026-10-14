<?php

declare(strict_types=1);

namespace Iso20022\Pain\Util;

use InvalidArgumentException;

/**
 * Minimal decimal-string arithmetic standing in for java.math.BigDecimal: amounts keep the scale
 * they were given ("200" stays "200", "12.34" stays "12.34") and sums use the largest scale.
 * No bcmath dependency: values are handled as scaled integers (PHP_INT_MAX cents is far beyond
 * any ISO 20022 amount, whose maximum is 18 digits).
 */
final class Decimal
{
    private const PATTERN = '/^[+-]?\d+(\.\d+)?$/';

    /**
     * @param string|int|float $value a decimal literal; floats are rendered with 2 decimals
     */
    public static function normalize(string|int|float $value): string
    {
        if (is_int($value)) {
            return (string)$value;
        }
        if (is_float($value)) {
            return number_format($value, 2, '.', '');
        }
        $value = trim($value);
        if (!preg_match(self::PATTERN, $value)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a decimal number.', $value));
        }

        return ltrim($value, '+');
    }

    public static function scale(string $value): int
    {
        $dot = strpos($value, '.');

        return $dot === false ? 0 : strlen($value) - $dot - 1;
    }

    /**
     * @param iterable<string> $values normalised decimal strings
     */
    public static function sum(iterable $values): string
    {
        $scale = 0;
        $list = [];
        foreach ($values as $value) {
            $list[] = $value;
            $scale = max($scale, self::scale($value));
        }
        $total = 0;
        foreach ($list as $value) {
            $total += self::toScaled($value, $scale);
        }

        return self::fromScaled($total, $scale);
    }

    public static function compare(string $a, string $b): int
    {
        $scale = max(self::scale($a), self::scale($b));

        return self::toScaled($a, $scale) <=> self::toScaled($b, $scale);
    }

    public static function toScaled(string $value, int $scale): int
    {
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '+-');
        [$int, $frac] = array_pad(explode('.', $value, 2), 2, '');
        $frac = substr(str_pad($frac, $scale, '0'), 0, $scale);
        $result = (int)($int . $frac);

        return $negative ? -$result : $result;
    }

    public static function fromScaled(int $scaled, int $scale): string
    {
        $sign = $scaled < 0 ? '-' : '';
        $digits = (string)abs($scaled);
        if ($scale === 0) {
            return $sign . $digits;
        }
        $digits = str_pad($digits, $scale + 1, '0', STR_PAD_LEFT);

        return $sign . substr($digits, 0, -$scale) . '.' . substr($digits, -$scale);
    }
}
