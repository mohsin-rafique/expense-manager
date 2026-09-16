<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\services;

/**
 * FbrReturnParser reads an FBR (Federal Board of Revenue) income tax return PDF
 * and extracts the Personal Expenses block of its Wealth Statement.
 *
 * The returns produced by IRIS - e.g. "114(1) (Return of Income filed
 * voluntarily for complete year)" - print every figure as a
 * `Description  <code>  <amount>` row. Under "Reconciliation of Net Assets" the
 * Outflows carry a "Personal Expenses" total (code 7089) followed by the
 * individual expense lines that make it up:
 *
 * ```
 * Personal Expenses                        7089        4,005,861
 * Medical                                  7070           26,645
 * Educational                              7071          303,004
 * ...
 * Telephone                                7061          106,550
 * Unreconciled Amount                    703000                0
 * ```
 *
 * Those detail lines are what the app reconciles against its own expenses, so
 * the parser keeps the rows between the 7089 total and the "Unreconciled Amount"
 * line (703000) that closes the block. Labels are taken from the PDF itself, so
 * a code this app has never seen still shows up with the wording FBR used.
 *
 * Nothing here writes to the database - the return is only read.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.3.0
 */
class FbrReturnParser
{
    /** Wealth statement code for the Personal Expenses total. */
    public const CODE_PERSONAL_TOTAL = '7089';

    /** Wealth statement code that closes the reconciliation block. */
    public const CODE_UNRECONCILED = '703000';

    /**
     * Parses an FBR return's extracted text.
     *
     * @param string $text Text of the return PDF (see {@see \app\helpers\PdfText})
     * @return array{
     *     isReturn: bool,
     *     title: string|null,
     *     taxpayer: string|null,
     *     registrationNo: string|null,
     *     taxYear: string|null,
     *     periodStart: string|null,
     *     periodEnd: string|null,
     *     personalTotal: float|null,
     *     unreconciled: float|null,
     *     lines: array<string, array{code: string, label: string, amount: float}>
     * }
     */
    public function parse(string $text): array
    {
        // `pdftotext` separates pages with a form feed, which PHP's trim() leaves
        // in place; treat it as a line break so the first row of every page is
        // read on its own.
        $text = str_replace(["\r\n", "\r", "\f"], "\n", $text);
        $lines = explode("\n", $text);

        $entries = $this->codedRows($lines);

        return [
            'isReturn' => $this->looksLikeReturn($text, $entries),
            'title' => $this->firstNonEmpty($lines),
            'taxpayer' => $this->field($text, 'Name'),
            'registrationNo' => $this->field($text, 'Registration No'),
            'taxYear' => $this->field($text, 'Tax Year'),
            'periodStart' => $this->period($text)[0],
            'periodEnd' => $this->period($text)[1],
            'personalTotal' => $this->amountOf($entries, self::CODE_PERSONAL_TOTAL),
            'unreconciled' => $this->amountOf($entries, self::CODE_UNRECONCILED),
            'lines' => $this->personalExpenseLines($entries),
        ];
    }

    /**
     * Collects every `Description  <code>  <amount>` row of the return, in the
     * order it is printed.
     *
     * Rows whose amount column is split in two (the Withholding Taxes tables
     * print a taxable amount and a tax deducted) keep only the trailing figure;
     * they carry 8-digit codes and are filtered out downstream anyway.
     *
     * @param array<int, string> $lines
     * @return array<int, array{code: string, label: string, amount: float}>
     */
    private function codedRows(array $lines): array
    {
        $rows = [];

        foreach ($lines as $line) {
            $line = rtrim($line);
            if (!preg_match('/^\s*(?<label>\S.*?)\s{2,}(?<code>\d{4,8})\s+(?<amount>-?[\d,]+(?:\.\d+)?)\s*$/', $line, $m)) {
                continue;
            }

            $rows[] = [
                'code' => $m['code'],
                'label' => trim(preg_replace('/\s+/', ' ', $m['label'])),
                'amount' => (float) str_replace(',', '', $m['amount']),
            ];
        }

        return $rows;
    }

    /**
     * Returns the Personal Expenses detail lines, keyed by their FBR code.
     *
     * The block runs from the "Personal Expenses" total (7089) to the
     * "Unreconciled Amount" row (703000) and holds only four-digit 70xx codes;
     * that range keeps the wealth statement's other 70xx figures (Inflows,
     * Income Declared, Net Assets) out of the result. When a return prints the
     * detail lines without the total, the known personal-expense codes are
     * picked up wherever they appear.
     *
     * A code repeated across pages is summed, matching how IRIS splits a long
     * block over a page break.
     *
     * @param array<int, array{code: string, label: string, amount: float}> $rows
     * @return array<string, array{code: string, label: string, amount: float}>
     */
    private function personalExpenseLines(array $rows): array
    {
        $start = null;
        foreach ($rows as $i => $row) {
            if ($row['code'] === self::CODE_PERSONAL_TOTAL) {
                $start = $i + 1;
                break;
            }
        }

        $known = FbrReconciliationService::categoryMap();
        $lines = [];

        foreach ($rows as $i => $row) {
            $inBlock = $start !== null && $i >= $start;
            if (!$inBlock && !isset($known[$row['code']])) {
                continue;
            }
            if ($inBlock && $row['code'] === self::CODE_UNRECONCILED) {
                break;
            }
            if (!preg_match('/^70\d{2}$/', $row['code']) || $row['code'] === self::CODE_PERSONAL_TOTAL) {
                continue;
            }

            if (isset($lines[$row['code']])) {
                $lines[$row['code']]['amount'] += $row['amount'];
                continue;
            }
            $lines[$row['code']] = $row;
        }

        return $lines;
    }

    /**
     * Whether the text reads as an FBR return rather than some other PDF.
     *
     * @param string $text
     * @param array<int, array{code: string, label: string, amount: float}> $rows
     * @return bool
     */
    private function looksLikeReturn(string $text, array $rows): bool
    {
        foreach ($rows as $row) {
            if ($row['code'] === self::CODE_PERSONAL_TOTAL) {
                return true;
            }
        }

        return (bool) preg_match('/Wealth\s+Statement|Reconciliation\s+of\s+Net\s+Assets/i', $text);
    }

    /**
     * Reads a `Label : value` header field (Tax Year, Registration No, Name).
     *
     * The header is a two-column table, so a line carries the wanted field and
     * the start of a second one: `Name: MOHSIN  RAFIQUE    Registration No : ...`.
     * The value therefore runs to the point where the next `Label :` pair
     * begins, not merely to the next column gap - the extractor pads the name's
     * own columns just as widely.
     *
     * @param string $text
     * @param string $label
     * @return string|null
     */
    private function field(string $text, string $label): ?string
    {
        $pattern = '/\b' . preg_quote($label, '/') . '\s*:\s*(?<value>\S[^\r\n]*)/i';
        if (!preg_match($pattern, $text, $m)) {
            return null;
        }

        // Cut at the second column's own label, if the line carries one. The
        // label's words are separated by single spaces, so a wide gap inside the
        // value (`MOHSIN    RAFIQUE`) cannot be mistaken for the column break.
        $parts = preg_split('/\s{2,}(?=[^\s:]+(?: [^\s:]+)* *:)/', $m['value']);
        $value = trim(preg_replace('/\s+/', ' ', $parts[0]));

        return $value === '' ? null : $value;
    }

    /**
     * Reads the return's period, e.g. `Period : 01-Jul-2025 - 30-Jun-2026`.
     *
     * @param string $text
     * @return array{0: string|null, 1: string|null} [startDate, endDate] as Y-m-d
     */
    private function period(string $text): array
    {
        $date = '(\d{1,2}-[A-Za-z]{3}-\d{4})';
        if (!preg_match('/Period\s*:\s*' . $date . '\s*-\s*' . $date . '/i', $text, $m)) {
            return [null, null];
        }

        return [$this->toIsoDate($m[1]), $this->toIsoDate($m[2])];
    }

    /**
     * Converts a `DD-Mon-YYYY` date to `YYYY-MM-DD`, or null when unparseable.
     *
     * @param string $date
     * @return string|null
     */
    private function toIsoDate(string $date): ?string
    {
        $parsed = \DateTime::createFromFormat('!j-M-Y', trim($date));

        return $parsed === false ? null : $parsed->format('Y-m-d');
    }

    /**
     * Returns the amount printed against a code, or null when it is absent.
     *
     * @param array<int, array{code: string, label: string, amount: float}> $rows
     * @param string $code
     * @return float|null
     */
    private function amountOf(array $rows, string $code): ?float
    {
        foreach ($rows as $row) {
            if ($row['code'] === $code) {
                return $row['amount'];
            }
        }

        return null;
    }

    /**
     * Returns the first non-blank line of the return (its form title).
     *
     * @param array<int, string> $lines
     * @return string|null
     */
    private function firstNonEmpty(array $lines): ?string
    {
        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line));
            if ($line !== '') {
                return $line;
            }
        }

        return null;
    }
}
