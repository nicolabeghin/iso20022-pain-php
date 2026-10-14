<?php

declare(strict_types=1);

namespace Iso20022\Pain\Cbi;

/**
 * A CBI application-level rule violated by a credit transfer. $transactionIndex (0-based) and
 * $endToEndId are set when the issue concerns a single transaction.
 */
final class CbiIssue
{
    public function __construct(
        public readonly string $code,
        public readonly string $message,
        public readonly ?int $transactionIndex = null,
        public readonly ?string $endToEndId = null,
    ) {
    }
}
