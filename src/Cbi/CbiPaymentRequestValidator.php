<?php

declare(strict_types=1);

namespace Iso20022\Pain\Cbi;

use DateTimeImmutable;
use InvalidArgumentException;
use Iso20022\Pain\ChargeBearer;
use Iso20022\Pain\CreditTransfer;
use Iso20022\Pain\Util\Decimal;
use Iso20022\Pain\Util\IbanUtil;

/**
 * Application-level checks a CBI "Banca Passiva" runs on a CBIPaymentRequest, applied before
 * generating the XML so callers get actionable errors instead of a bank rejection (and can e.g. drop
 * the offending transactions). The numbered comments refer to CBI STIP-MO-001 "Nuova Architettura CBI –
 * Area Pagamenti" v00.04.00, §3.9.1.2 "Controlli applicativi" (04/11/2022 edition); service-level
 * specific limits live in CbiServiceLevel.
 *
 * References followed:
 *  - CBI STIP-MO-001 v00.04.00 §3.2.1, §3.9.1.2, Appendix A:
 *    https://www.protezionecivile.gov.it/static/068269bfd70e89353e4723e45cec7b65/tracciato-bonifci.pdf
 *  - CBI XSD CBIPaymentRequest.00.04.01.xsd (Max35Text, Max70Text, Max140Text, IBAN2007Identifier):
 *    https://github.com/OCA/l10n-italy/blob/16.0/l10n_it_sct_cbi/data/standards/CBIPaymentRequest.00.04.01.xsd
 *  - EPC230-15 Clarification Paper on the Use of Slashes in References, Identifications and Identifiers:
 *    https://www.europeanpaymentscouncil.eu/document-library
 *  - CBI standards portal: https://www.cbi-org.eu/
 */
final class CbiPaymentRequestValidator
{
    private const IDENTIFIER_PATTERN = "/^[A-Za-z0-9\\/\\-?:().,'+]{1,35}$/";

    /**
     * @param \DateTimeImmutable|null $today reference date for execution-date checks (defaults to today)
     * @return array<\Iso20022\Pain\Cbi\CbiIssue> empty when the request is acceptable
     */
    public function validate(CreditTransfer $transfer, ?DateTimeImmutable $today = null): array
    {
        $today = ($today ?? new DateTimeImmutable('today'))->setTime(0, 0);
        $issues = [];

        // Check 10: SvcLvl mandatory; FAST when instant.
        $levelCode = $transfer->isInstantPayment() ? CbiServiceLevel::FAST->value : $transfer->getServiceLevelCode();
        $level = CbiServiceLevel::tryFrom((string)$levelCode);
        if ($level === null) {
            $issues[] = new CbiIssue(
                'service_level',
                sprintf('Service level "%s" is not a supported CBI service level (SEPA, FAST, URGP).', (string)$levelCode),
            );
        }
        // Check 9: payment method TRF/TRA (TRA only for urgent transfers).
        if ($level !== null && !in_array($transfer->getPaymentMethodCode(), $level->allowedPaymentMethods(), true)) {
            $issues[] = new CbiIssue(
                'payment_method',
                sprintf('Payment method "%s" is not allowed for %s transfers.', $transfer->getPaymentMethodCode(), $level->value),
            );
        }
        // Check 16: charge bearer SLEV.
        if ($transfer->getChargeBearer() !== ChargeBearer::SLEV) {
            $issues[] = new CbiIssue('charge_bearer', 'CBI only accepts charge bearer SLEV.');
        }

        // Checks 4 and 5: initiating party CUC (8 alphanumeric characters) with issuer "CBI".
        $debtor = $transfer->getDebtor();
        $cuc = $debtor?->getOtherId();
        if ($cuc === null || !preg_match('/^[A-Z0-9]{8}$/', $cuc)) {
            $issues[] = new CbiIssue('debtor_cuc', 'The initiating party CUC code is missing or not 8 alphanumeric characters.');
        }
        if ($debtor?->getName() === null || CbiCharset::text($debtor->getName()) === '') {
            $issues[] = new CbiIssue('debtor_name', 'The debtor name is missing.');
        } elseif (mb_strlen($debtor->getName()) > 70) {
            $issues[] = new CbiIssue('debtor_name_length', 'The debtor name exceeds 70 characters.');
        }
        // Check 13: debtor agent identified by a valid ABI, derived from an Italian IBAN.
        $debtorIban = $transfer->getDebtorAccount()->getIban();
        if ($debtorIban === null || !IbanUtil::isValid($debtorIban)) {
            $issues[] = new CbiIssue('debtor_iban', 'The debtor IBAN is missing or invalid.');
        } else {
            try {
                ItalianIban::abi($debtorIban);
            } catch (InvalidArgumentException) {
                $issues[] = new CbiIssue('debtor_abi', 'The debtor IBAN must be an Italian IBAN (CBI needs the ABI code).');
            }
        }

        // Check 1 and Appendix A: identifiers within Max35Text, Latin charset, no leading/trailing or double slash.
        if (!$this->isValidIdentifier($transfer->getId())) {
            $issues[] = new CbiIssue('message_id', sprintf('Message identifier "%s" is not a valid CBI identifier.', $transfer->getId()));
        }

        // Execution date: not in the past; today only for FAST/URGP.
        $execution = ($transfer->getRequestedExecutionDateTime() ?? $transfer->getRequestedExecutionDate())?->setTime(0, 0);
        if ($execution !== null) {
            if ($execution < $today) {
                $issues[] = new CbiIssue('execution_date_past', 'The requested execution date cannot be in the past.');
            } elseif ($execution > $today && $level !== null && !$level->allowsFutureExecutionDate()) {
                $issues[] = new CbiIssue(
                    'execution_date_future',
                    sprintf('%s transfers cannot be scheduled: the execution date must be today.', $level->value),
                );
            }
        }

        // Checks 2 and 18: at least one transaction, at most one for urgent transfers.
        $transactions = $transfer->getTransactions();
        if ($transactions === []) {
            $issues[] = new CbiIssue('no_transactions', 'At least one transaction is required.');
        } elseif ($level !== null && count($transactions) > $level->maxTransactions()) {
            $issues[] = new CbiIssue('too_many_transactions', sprintf('%s transfers allow a single transaction.', $level->value));
        }

        $seen = [];
        foreach (array_values($transactions) as $index => $transaction) {
            $e2e = $transaction->getEndToEndId();
            $party = $transaction->getParty();
            if ($party?->getName() === null || CbiCharset::text($party->getName()) === '') {
                $issues[] = new CbiIssue('creditor_name', 'The creditor name is missing.', $index, $e2e);
            } elseif (mb_strlen($party->getName()) > 70) {
                $issues[] = new CbiIssue('creditor_name_length', 'The creditor name exceeds 70 characters.', $index, $e2e);
            }
            // Checks 31 and 33: creditor account with a valid IBAN.
            $iban = $transaction->getAccount()->getIban();
            if ($iban === null || !IbanUtil::isValid($iban)) {
                $issues[] = new CbiIssue('creditor_iban', 'The creditor IBAN is missing or invalid.', $index, $e2e);
            } elseif (ItalianIban::isItalian($iban) && $transaction->getCategoryPurposeCode() === null) {
                // Checks 20 and 40: category purpose mandatory for Italian creditor IBANs.
                $issues[] = new CbiIssue(
                    'category_purpose',
                    'A category purpose code is required for Italian creditor IBANs.',
                    $index,
                    $e2e,
                );
            }
            // Check 23: EUR only, amount within the service-level range.
            if ($transaction->getCurrencyCode() !== 'EUR') {
                $issues[] = new CbiIssue('currency', 'Only EUR amounts are allowed.', $index, $e2e);
            }
            if (Decimal::compare($transaction->getAmount(), '0.01') < 0) {
                $issues[] = new CbiIssue('amount_zero', 'The amount must be at least 0.01 EUR.', $index, $e2e);
            } elseif ($level !== null && !$level->isAmountAllowed($transaction->getAmount())) {
                $issues[] = new CbiIssue(
                    'amount_over_limit',
                    sprintf('The amount exceeds the %s limit of %s EUR.', $level->value, $level->maxAmount()),
                    $index,
                    $e2e,
                );
            }
            // Check 19: EndToEndId unique within the batch; Appendix A charset.
            if (!$this->isValidIdentifier($e2e)) {
                $issues[] = new CbiIssue(
                    'end_to_end_id',
                    sprintf('EndToEndId "%s" is not a valid CBI identifier.', $e2e),
                    $index,
                    $e2e,
                );
            } elseif (isset($seen[$e2e])) {
                $issues[] = new CbiIssue('end_to_end_id_duplicate', sprintf('Duplicate EndToEndId "%s".', $e2e), $index, $e2e);
            }
            $seen[$e2e] = true;
            if ($transaction->getId() !== null && !$this->isValidIdentifier($transaction->getId())) {
                $issues[] = new CbiIssue(
                    'instruction_id',
                    sprintf('InstrId "%s" is not a valid CBI identifier.', $transaction->getId()),
                    $index,
                    $e2e,
                );
            }
            // Checks 45 and Max140Text: unstructured remittance lines.
            $lines = $transaction->getRemittanceInformationUnstructured();
            if ($level !== null && count($lines) > $level->maxUnstructuredRemittanceLines()) {
                $issues[] = new CbiIssue(
                    'remittance_lines',
                    sprintf('%s transfers allow a single unstructured remittance line.', $level->value),
                    $index,
                    $e2e,
                );
            }
            foreach ($lines as $line) {
                if (mb_strlen($line) > 140) {
                    $issues[] = new CbiIssue('remittance_too_long', 'Remittance information exceeds 140 characters.', $index, $e2e);
                    break;
                }
            }
            // Not in the CBI schema at transaction level.
            if ($transaction->getChargeBearer() !== null) {
                $issues[] = new CbiIssue('transaction_charge_bearer', 'CBI does not allow a per-transaction charge bearer.', $index, $e2e);
            }
            if ($transaction->getIntermediaryAgents() !== []) {
                $issues[] = new CbiIssue('intermediary_agents', 'CBI does not allow intermediary agents.', $index, $e2e);
            }
        }

        return $issues;
    }

    private function isValidIdentifier(string $id): bool
    {
        return preg_match(self::IDENTIFIER_PATTERN, $id) === 1
            && !str_starts_with($id, '/')
            && !str_ends_with($id, '/')
            && !str_contains($id, '//');
    }
}
