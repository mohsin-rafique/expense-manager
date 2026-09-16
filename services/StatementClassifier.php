<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\services;

use app\models\Expense;

/**
 * StatementClassifier turns a bank statement line into the fields an expense
 * needs: a category name, an FBR tax category, and a payment method.
 *
 * Statement lines carry no category of their own, so the importer derives one
 * from the description. Rules are ordered and the first match wins, which
 * matters in two places:
 *
 *  - money movement is tested first, so a transfer is never mistaken for a
 *    purchase at the merchant named in its description;
 *  - taxes and levies are tested before the thing they are charged on, so
 *    "CHARGES TAXES PLUS FED ... ANTHROPIC* CLAUDE SUB" is a tax line rather
 *    than a subscription.
 *
 * Anything that matches nothing is left for the caller to place in the fallback
 * category chosen in the import wizard.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.3.0
 */
class StatementClassifier
{
    /** Money-movement kinds: not spending, just funds changing hands. */
    public const MOVE_CASH = 'cash_withdrawal';
    public const MOVE_TRANSFER = 'account_transfer';
    public const MOVE_CARD_BILL = 'card_bill_payment';

    /**
     * Money-movement rules, tested first. These lines move money rather than
     * spend it, so importing them as expenses would double-count: the cash is
     * spent later, the transfer is the other party's income, and a card bill
     * settles purchases that are already on the statement in their own right.
     *
     * @return array<string, array{pattern: string, label: string}>
     */
    private static function movementRules(): array
    {
        return [
            self::MOVE_CASH => [
                'pattern' => '/ATM CASH WITHDRAWA|CASH WITHDRAWAL/',
                'label' => 'Cash withdrawal',
            ],
            self::MOVE_CARD_BILL => [
                'pattern' => '/1BILL CREDIT CARD|CREDIT CARD PAYMENT/',
                'label' => 'Credit card bill payment',
            ],
            self::MOVE_TRANSFER => [
                // An IBAN as the destination is the clearest sign of an
                // account-to-account transfer; the named forms cover the rest.
                'pattern' => '/\bPK\d{2}[A-Z]{4}\d|FUNDS TRANSFER|\bIBFT\b(?! FROM)|\bTRANSFER TO\b/',
                'label' => 'Account transfer',
            ],
        ];
    }

    /**
     * Category rules in match order: `pattern => [category name, FBR code]`.
     *
     * The FBR codes are the ones offered by
     * {@see \app\models\ExpenseCategory::getFbrCategories()}, so an imported
     * expense lands in the right box on the tax summary.
     *
     * @return array<int, array{pattern: string, category: string, fbr: string|null}>
     */
    private static function categoryRules(): array
    {
        return [
            // Government levies and taxes, ahead of the fee they are levied on.
            ['pattern' => '/CHARGES TAXES PLUS FED|FED\/PST|FED ON DUP|FBRTAX/', 'category' => 'FED / Sales Tax', 'fbr' => 'FED_SALES_TAX'],
            ['pattern' => '/\bSTWH\b/', 'category' => 'Sales Tax Withholding', 'fbr' => 'SALES_TAX_WHT'],
            ['pattern' => '/TAX FILER|ADVANCE TAX/', 'category' => 'Advance Income Tax', 'fbr' => 'ADV_INCOME_TAX'],

            // Bank fees.
            ['pattern' => '/SERVICE CHG|SERVICE CHARGE/', 'category' => 'Service Charge', 'fbr' => 'SERVICE_CHARGE'],
            ['pattern' => '/SMS CHARGES|SMS ALERT/', 'category' => 'SMS Alert Fee', 'fbr' => 'SMS_ALERT_FEE'],
            ['pattern' => '/ATM ANNUAL FEE|CARD ANNUAL FEE|CARD ISSUANCE/', 'category' => 'Card Annual Fee', 'fbr' => 'CARD_ANNUAL_FEE'],
            ['pattern' => '/BANK CHARGES|EXP ADV PAY|DUPLICATE STATEMENT|LOCKER ASSIGNED|CHG:PKR|CHEQUE BOOK/', 'category' => 'Bank Charges', 'fbr' => 'OTHER_FEES'],

            // Utilities and telecom billers.
            ['pattern' => '/SNGPL|\bSSGC\b/', 'category' => 'Gas', 'fbr' => 'GAS'],
            ['pattern' => '/LESCO|IESCO|MEPCO|GEPCO|FESCO|HESCO|PESCO|QESCO|K-ELECTRIC|KELECTRIC|WAPDA/', 'category' => 'Electricity', 'fbr' => 'ELECTRICITY'],
            ['pattern' => '/\bWASA\b|WATER BOARD/', 'category' => 'Water', 'fbr' => 'WATER'],
            ['pattern' => '/\bPTCL\b|LANDLINE|\bZONG\b|JAZZ(?!CASH)|TELENOR|UFONE|NAYATEL|STORMFIBER|TRANSWORLD|WORLDCALL/', 'category' => 'Telephone & Internet', 'fbr' => 'TELEPHONE'],

            // Fuel and vehicle running.
            ['pattern' => '/ARAMCO|\bPSO\b|SHELL|TOTAL PARCO|ATTOCK|HASCOL|PETROL|FILLING STATION|SERVICE STATION/', 'category' => 'Fuel', 'fbr' => 'VEHICLE_MAINT'],

            // Everyday retail.
            ['pattern' => '/RAINBOW CASH AND|AL FATAH|IMTIAZ|METRO CASH|CARREFOUR|CHASE UP|GREEN VALLEY|JALAL SONS|\bSPAR\b/', 'category' => 'Groceries', 'fbr' => 'OTHER_PERS'],

            // Online subscriptions and software.
            ['pattern' => '/ANTHROPIC|OPENAI|GOOGLE|MICROSOFT|NETFLIX|SPOTIFY|GITHUB|AMAZON WEB|DIGITALOCEAN|ADOBE|APPLE\.COM/', 'category' => 'Subscriptions', 'fbr' => 'OTHER_PERS'],
        ];
    }

    /**
     * Classifies one statement description.
     *
     * @param string $description The statement's Particular text
     * @return array{movement: string|null, movementLabel: string|null, category: string|null, fbr: string|null, payment: string}
     *   `movement` is non-null when the line moves money rather than spends it;
     *   `category` is null when no rule matched and the caller's fallback
     *   category should be used.
     */
    public static function classify(string $description): array
    {
        $text = self::normalize($description);

        $result = [
            'movement' => null,
            'movementLabel' => null,
            'category' => null,
            'fbr' => null,
            'payment' => self::paymentMethod($text),
        ];

        foreach (self::movementRules() as $kind => $rule) {
            if (preg_match($rule['pattern'], $text)) {
                $result['movement'] = $kind;
                $result['movementLabel'] = $rule['label'];

                return $result;
            }
        }

        foreach (self::categoryRules() as $rule) {
            if (preg_match($rule['pattern'], $text)) {
                $result['category'] = $rule['category'];
                $result['fbr'] = $rule['fbr'];

                return $result;
            }
        }

        return $result;
    }

    /**
     * Derives the payment method from how the transaction reached the account.
     *
     * @param string $text Normalized description
     * @return string One of the Expense::PAYMENT_* values
     */
    private static function paymentMethod(string $text): string
    {
        if (preg_match('/ATM CASH WITHDRAWA|CASH WITHDRAWAL/', $text)) {
            return Expense::PAYMENT_CASH;
        }
        if (preg_match('/POS PURCHASE|ONLINE PURCHASE|CARD PURCHASE|\bECOM\b/', $text)) {
            return Expense::PAYMENT_CARD;
        }

        return Expense::PAYMENT_BANK;
    }

    /**
     * Uppercases and collapses whitespace so the rules can be written flat.
     *
     * @param string $description
     * @return string
     */
    private static function normalize(string $description): string
    {
        return preg_replace('/\s+/', ' ', strtoupper(trim($description)));
    }
}
