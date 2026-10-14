<?php

declare(strict_types=1);

namespace Iso20022\Pain;

use DOMDocument;
use Iso20022\Pain\Impl\XmlException;
use LibXMLError;

/**
 * Validates a generated message against the official XSD of its version.
 *
 * PHP port of bank4j's test helper SchemaValidator, promoted to the library so applications can
 * refuse to hand a file to the bank when it does not validate.
 */
final class SchemaValidator
{
    /**
     * @throws \Iso20022\Pain\Impl\XmlException when the document is malformed or does not validate
     */
    public static function validateCreditTransfer(CustomerCreditTransferInitiationVersion $version, string $xml): void
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        try {
            $doc = new DOMDocument();
            $valid = $doc->loadXML($xml) && $doc->schemaValidate($version->schemaPath());
            $errors = libxml_get_errors();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (!$valid) {
            $messages = array_map(
                static fn (LibXMLError $e): string => sprintf('line %d: %s', $e->line, trim($e->message)),
                $errors,
            );
            throw new XmlException(sprintf(
                'Document does not validate against %s: %s',
                basename($version->schemaPath()),
                implode('; ', $messages === [] ? ['unknown libxml error'] : $messages),
            ));
        }
    }

    public static function isValid(CustomerCreditTransferInitiationVersion $version, string $xml): bool
    {
        try {
            self::validateCreditTransfer($version, $xml);

            return true;
        } catch (XmlException) {
            return false;
        }
    }
}
