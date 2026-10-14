<?php

declare(strict_types=1);

namespace Iso20022\Pain\Test\Cbi;

use Iso20022\Pain\Cbi\CbiCharset;
use PHPUnit\Framework\TestCase;

/**
 * CBI STIP-MO-001 Appendix A character set.
 */
final class CbiCharsetTest extends TestCase
{
    public function testTextTransliteratesAndStripsForbiddenCharacters(): void
    {
        self::assertSame("D'ANGELO Niccolo Jose", CbiCharset::text("D'ANGELO Niccolò José"));
        self::assertSame('Retribuzione 09/2026 (netto)', CbiCharset::text('Retribuzione 09/2026 (netto)'));
        self::assertSame('Mail test example.com', CbiCharset::text('Mail test@example.com'));
        self::assertSame('a b c', CbiCharset::text('  a€b§c  '));
        self::assertSame('Chiaravalle', CbiCharset::text('Chiaravalle S.r.l.', 11));
    }

    public function testIdentifierHasNoSpacesOrSlashIssues(): void
    {
        self::assertSame('CED-2026-09/1', CbiCharset::identifier('/CED 2026 09//1/'));
        self::assertSame('ABCDEFGHIJKLMNOPQRSTUVWXYZ012345678', CbiCharset::identifier(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 2)));
    }

    public function testIsAllowed(): void
    {
        self::assertTrue(CbiCharset::isAllowed("Rossi Mario - Via Po 1, Roma (RM)? ok: +39 'x'."));
        self::assertFalse(CbiCharset::isAllowed('Rossi Mario & C.'));
        self::assertFalse(CbiCharset::isAllowed('Niccolò'));
    }
}
