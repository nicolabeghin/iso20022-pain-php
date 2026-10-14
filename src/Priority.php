<?php

declare(strict_types=1);

namespace Iso20022\Pain;

/**
 * ISO 20022 Priority2Code (<InstrPrty>). PHP port of io.inisos.bank4j.Priority.
 */
enum Priority: string
{
    case HIGH = 'HIGH';
    case NORM = 'NORM';
}
