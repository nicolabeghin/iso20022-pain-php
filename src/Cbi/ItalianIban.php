<?php

declare(strict_types=1);

namespace Iso20022\Pain\Cbi;

use InvalidArgumentException;
use Iso20022\Pain\Util\IbanUtil;

/**
 * Italian IBAN layout (27 characters): IT + 2 check digits + 1 CIN + 5 ABI + 5 CAB + 12 account number.
 * CBI identifies the debtor agent by its ABI in <DbtrAgt>/<FinInstnId>/<ClrSysMmbId>/<MmbId>
 * (CBI STIP-MO-001 §3.9.1.2 check 13: "codice ABI valido espresso in forma di cinque caratteri numerici").
 */
final class ItalianIban
{
    public static function isItalian(string $iban): bool
    {
        return str_starts_with($iban, 'IT');
    }

    /**
     * @throws \InvalidArgumentException when the IBAN is not a valid Italian IBAN
     */
    public static function cin(string $iban): string
    {
        return substr(self::validated($iban), 4, 1);
    }

    /**
     * 5-digit bank code (ABI).
     *
     * @throws \InvalidArgumentException when the IBAN is not a valid Italian IBAN
     */
    public static function abi(string $iban): string
    {
        return substr(self::validated($iban), 5, 5);
    }

    /**
     * 5-digit branch code (CAB).
     *
     * @throws \InvalidArgumentException when the IBAN is not a valid Italian IBAN
     */
    public static function cab(string $iban): string
    {
        return substr(self::validated($iban), 10, 5);
    }

    private static function validated(string $iban): string
    {
        if (!self::isItalian($iban) || !IbanUtil::isValid($iban)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid Italian IBAN.', $iban));
        }
        if (!ctype_digit(substr($iban, 5, 10))) {
            throw new InvalidArgumentException(sprintf('"%s" has a non numeric ABI/CAB.', $iban));
        }

        return $iban;
    }

    private function __construct()
    {
    }
}
