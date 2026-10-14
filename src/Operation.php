<?php

declare(strict_types=1);

namespace Iso20022\Pain;

/**
 * Something that can be serialised to XML. PHP port of io.inisos.bank4j.Operation.
 */
interface Operation
{
    /**
     * @param bool $formatted true to indent the output
     */
    public function marshal(bool $formatted = false): string;
}
