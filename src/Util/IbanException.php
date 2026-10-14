<?php

declare(strict_types=1);

namespace Iso20022\Pain\Util;

use InvalidArgumentException;

/**
 * Thrown by IbanUtil::validate() and BicUtil::validate() (counterpart of iban4j's Iban4jException).
 */
class IbanException extends InvalidArgumentException
{
}
