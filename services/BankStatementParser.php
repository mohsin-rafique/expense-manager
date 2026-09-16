<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\services;

use Yii;

/**
 * BankStatementParser reads a bank's own monthly e-statement into normalized
 * transaction rows, ready for the bulk importer.
 *
 * This is the import-side counterpart to the reconciliation screen and is
 * deliberately separate from it: reconciliation compares a whole period's
 * debits against existing records and never writes, while this parser feeds
 * {@see ImportService} and must preserve every column the importer persists
 * (debit vs credit direction, document number, running balance).
 *
 * Input is the column-aligned text of a statement PDF, as produced by
 * `pdftotext -table` (see {@see \app\helpers\PdfText}). The column alignment is
 * what makes the table readable: the Debit and Credit columns are told apart by
 * position, not by guessing from the description.
 *
 * Adding a bank means adding one entry to {@see formats()}; the row-walking
 * logic below is shared by all of them.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.3.0
 */
class BankStatementParser
{
    /** Meezan Bank monthly e-statement (Date / Value Date / Doc No / Particular / Debit / Credit / Balance). */
    public const FORMAT_MEEZAN_ESTATEMENT = 'meezan_estatement';

    /** Standard Chartered statement (Date / Description / Withdrawal / Deposit / Balance). */
    public const FORMAT_SC_STATEMENT = 'sc_statement';

    /**
     * Read the amount straight out of the Debit and Credit columns. Only safe
     * when the bank prints those columns in reliable positions on every row.
     */
    private const STRATEGY_COLUMNS = 'columns';

    /**
     * Derive the amount from the movement in the running Balance column. Slower
     * to reason about but self-checking, and the only workable option when a
     * bank's amount columns do not line up row-for-row in the extracted text.
     */
    private const STRATEGY_BALANCE = 'balance';

    /** @var int Hard cap on transaction rows read from one statement */
    public int $maxRows = 5000;

    /**
     * The statement layouts this parser recognizes.
     *
     * Each entry describes one bank's table:
     *   label      - human name, shown in the import wizard
     *   bank       - default bank name suggested for the imported expenses
     *   strategy   - STRATEGY_COLUMNS or STRATEGY_BALANCE, see the constants
     *   header     - regex identifying the column header row
     *   probe      - regex proving at least one transaction row exists
     *   rowDate    - regex matching the leading date cell of a transaction row
     *   end        - regex for a line that closes the table (page footers,
     *                totals), or null when the table runs to the end
     *   money      - regex for an amount / running-balance cell
     *   blank      - regex for the placeholder printed in an empty amount column
     *   dateFormat - `DateTime::createFromFormat` pattern for the date cell
     *   carryOver  - regex for a balance carry-forward line, or null
     *
     * @return array<string, array<string, string|null>>
     */
    public static function formats(): array
    {
        return [
            self::FORMAT_MEEZAN_ESTATEMENT => [
                'label' => 'Meezan Bank - monthly e-statement (PDF)',
                'bank' => 'Meezan Bank',
                // Debit and Credit print in fixed columns with a placeholder in
                // the empty one, so the columns can be read directly.
                'strategy' => self::STRATEGY_COLUMNS,
                // The Particular column is what sets this apart from the
                // internet-banking export, which names the column Description.
                'header' => '/\bDate\b.*\bValue\s*Date\b.*\bParticular\b.*\bDebit\b.*\bCredit\b.*\bBalance\b/i',
                'probe' => '/^\s*\d{2}-[A-Za-z]{3}-\d{4}\s/m',
                'rowDate' => '/^\d{2}-[A-Za-z]{3}-\d{4}$/',
                // Every page ends with `PG 02 / 04`, then repeats the account
                // header block before the next table. Without closing the table
                // there, those header lines would be glued onto the last
                // transaction as description continuations.
                'end' => '/^\s*PG\s*\d+\s*\/\s*\d+\s*$|<=\s*Closing Balance\s*=>|^\s*Total No\.\s*of\b/i',
                'money' => '/^\d[\d,]*\.\d{2}$/',
                'blank' => '/^-$/',
                'dateFormat' => 'd-M-Y',
                'carryOver' => null,
            ],
            self::FORMAT_SC_STATEMENT => [
                'label' => 'Standard Chartered - statement (PDF)',
                'bank' => 'Standard Chartered',
                // This statement splits date, description and amount into text
                // runs that do not line up row-for-row, so the printed
                // Withdrawal value cannot be trusted by position. The running
                // balance is the ground truth: a row is money out exactly when
                // the balance falls, by the amount it falls.
                'strategy' => self::STRATEGY_BALANCE,
                'header' => '/\bDate\b.*\bDescription\b.*\bWithdrawal\b.*\bBalance\b/i',
                'probe' => '/^\s*\d{2}[A-Za-z]{3}\d{2}\s/m',
                'rowDate' => '/^\d{2}[A-Za-z]{3}\d{2}$/',
                'end' => '/This is an electronic statement|Deposits Statement/i',
                'money' => '/^\d[\d,]*\.\d{2}$/',
                'blank' => '/^-$/',
                'dateFormat' => 'dMy',
                'carryOver' => '/BALANCE\s+B\/F/i',
            ],
        ];
    }

    /**
     * Returns the format identifiers with their labels, for a dropdown.
     *
     * @return array<string, string>
     */
    public static function formatList(): array
    {
        return array_map(fn (array $f) => $f['label'], self::formats());
    }

    /**
     * Returns the bank name a format's statements normally belong to.
     *
     * @param string $format
     * @return string|null
     */
    public static function bankNameFor(string $format): ?string
    {
        return self::formats()[$format]['bank'] ?? null;
    }

    /**
     * Identifies which known statement format the text is, or null for none.
     *
     * Both the column header and a real transaction row must be present, so a
     * document that merely mentions the word "Debit" is not misread as a
     * statement.
     *
     * @param string $raw
     * @return string|null One of the FORMAT_* constants
     */
    public function detect(string $raw): ?string
    {
        foreach (self::formats() as $key => $format) {
            if (preg_match($format['header'], $raw) && preg_match($format['probe'], $raw)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Parses statement text into normalized transaction rows.
     *
     * Each returned row is:
     *   line        - 1-based line number in the extracted text
     *   date        - transaction date as Y-m-d
     *   description - the full Particular text, wrapped lines joined
     *   docNo       - the bank's document number, when the row carries one
     *   debit       - amount taken out, or null
     *   credit      - amount paid in, or null
     *   balance     - running balance after the row
     *   mismatch    - true when the balance movement contradicts the printed
     *                 debit/credit, i.e. the row did not parse cleanly
     *
     * @param string $raw Column-aligned statement text
     * @param string|null $expected Force a specific format instead of detecting
     *   one. The text must actually match it, so picking the wrong bank is
     *   reported rather than silently parsed as something else.
     * @return array{format: string|null, rows: array<int, array>, skipped: int, error: string|null}
     */
    public function parse(string $raw, ?string $expected = null): array
    {
        $detected = $this->detect($raw);

        if ($expected !== null && $expected !== '') {
            $known = self::formats();
            if (!isset($known[$expected])) {
                return $this->failure(Yii::t('app', 'Unknown statement format selected.'));
            }
            if ($detected !== $expected) {
                return $this->failure($detected === null
                    ? Yii::t('app', 'This file does not look like a {format}.', [
                        'format' => $known[$expected]['label'],
                    ])
                    : Yii::t('app', 'You chose {chosen}, but this file looks like a {detected}.', [
                        'chosen' => $known[$expected]['label'],
                        'detected' => $known[$detected]['label'],
                    ]));
            }
        }

        $format = $expected !== null && $expected !== '' ? $expected : $detected;
        if ($format === null) {
            return $this->failure(Yii::t('app', 'This does not look like a supported bank statement. Supported: {list}.', [
                'list' => implode(', ', self::formatList()),
            ]));
        }

        $layout = self::formats()[$format];
        $byBalance = $layout['strategy'] === self::STRATEGY_BALANCE;

        // `pdftotext` marks page breaks with a form feed, which trim() does not
        // strip; left in place it glues the first row of every page onto the
        // previous one.
        $normalized = str_replace(["\r\n", "\r", "\f"], "\n", $raw);
        $lines = explode("\n", $normalized);

        $rows = [];
        $skipped = 0;
        $inTable = false;
        $prevBalance = null;
        $cur = null;

        $flush = function () use (&$cur, &$rows, &$prevBalance, &$skipped, $byBalance): void {
            if ($cur === null) {
                return;
            }
            $row = $cur;
            $cur = null;
            $row['mismatch'] = false;

            if ($byBalance) {
                // No trustworthy amount columns: the balance movement is the
                // amount. The first row only establishes the opening baseline.
                if ($row['balance'] === null) {
                    $skipped++;
                    return;
                }
                if ($prevBalance === null) {
                    $prevBalance = $row['balance'];
                    $skipped++;
                    return;
                }

                $delta = round($prevBalance - $row['balance'], 2);
                $prevBalance = $row['balance'];

                if ($delta > 0.0) {
                    $row['debit'] = $delta;
                } elseif ($delta < 0.0) {
                    $row['credit'] = -$delta;
                } else {
                    $skipped++;   // no balance movement, so nothing happened
                    return;
                }
            } else {
                $debit = $row['debit'];
                $credit = $row['credit'];
                if (($debit === null || $debit <= 0.0) && ($credit === null || $credit <= 0.0)) {
                    $skipped++;   // a row with no money movement in either column
                    return;
                }

                // The running balance is an independent check on the columns we
                // read: money out minus money in must equal the drop in
                // balance. A mismatch means the row did not line up, so flag it
                // rather than importing an amount we are not sure of.
                if ($prevBalance !== null && $row['balance'] !== null) {
                    $stated = round(($debit ?? 0.0) - ($credit ?? 0.0), 2);
                    $actual = round($prevBalance - $row['balance'], 2);
                    $row['mismatch'] = abs($stated - $actual) > 0.01;
                }
                if ($row['balance'] !== null) {
                    $prevBalance = $row['balance'];
                }
            }

            $row['description'] = trim(preg_replace('/\s+/', ' ', (string) $row['description']));

            $rows[] = $row;
        };

        foreach ($lines as $i => $line) {
            if (trim($line) === '') {
                continue;
            }

            if (preg_match($layout['header'], $line)) {
                $flush();
                $inTable = true;
                continue;
            }
            if ($inTable && preg_match($layout['end'], $line)) {
                $flush();
                $inTable = false;
                continue;
            }
            if (!$inTable) {
                continue;
            }
            if (count($rows) >= $this->maxRows) {
                break;
            }

            // A carry-forward line restates the balance at the top of a page.
            // It is not a transaction, but it does reset the baseline.
            if ($layout['carryOver'] !== null && preg_match($layout['carryOver'], $line)) {
                $flush();
                $carried = $this->lastMoney(preg_split('/\s{2,}/', trim($line)), $layout['money']);
                if ($carried !== null) {
                    $prevBalance = $carried;
                }
                continue;
            }

            $cells = preg_split('/\s{2,}/', trim($line));

            if (preg_match($layout['rowDate'], $cells[0] ?? '')) {
                $flush();

                // `<=Opening Balance=>` and friends carry a date and a balance
                // but no transaction: they only reset the running balance.
                if ($this->isBalanceMarker($cells)) {
                    $marker = $this->lastMoney($cells, $layout['money']);
                    if ($marker !== null) {
                        $prevBalance = $marker;
                    }
                    continue;
                }

                $parsed = $this->parseRow($cells, $layout);
                if ($parsed === null) {
                    $skipped++;
                    continue;
                }
                $parsed['line'] = $i + 1;
                $cur = $parsed;
                continue;
            }

            if ($cur !== null) {
                // A wrapped Particular. The document number wraps too, in its
                // own column ahead of the text, so a leading run of digits with
                // text after it belongs to the doc number, not the description.
                if (count($cells) > 1 && preg_match('/^\d+$/', $cells[0])) {
                    $cur['docNo'] = (string) $cur['docNo'] . array_shift($cells);
                }
                $cur['description'] .= ' ' . implode(' ', $cells);
            }
        }
        $flush();

        return ['format' => $format, 'rows' => $rows, 'skipped' => $skipped, 'error' => null];
    }

    /**
     * Parses one transaction line's cells into a row, or null when the cells do
     * not end in the expected Debit / Credit / Balance triple.
     *
     * @param array<int, string> $cells Whitespace-split cells, date first
     * @param array<string, string> $layout
     * @return array<string, mixed>|null
     */
    private function parseRow(array $cells, array $layout): ?array
    {
        $date = \DateTime::createFromFormat($layout['dateFormat'], $cells[0]);
        if ($date === false) {
            return null;
        }

        $rest = array_slice($cells, 1);

        // The value date repeats the transaction date in its own column.
        if (isset($rest[0]) && preg_match($layout['rowDate'], $rest[0])) {
            array_shift($rest);
        }

        // Balance-driven layouts have no dependable amount columns: split the
        // cells into text and numbers, and take the last number as the running
        // balance. The caller derives the amount from how that balance moved.
        if ($layout['strategy'] === self::STRATEGY_BALANCE) {
            [$description, $balance] = $this->splitTextAndBalance($rest, $layout['money']);

            return [
                'line' => 0,
                'date' => $date->format('Y-m-d'),
                'description' => $description,
                'docNo' => null,
                'debit' => null,
                'credit' => null,
                'balance' => $balance,
                'mismatch' => false,
            ];
        }

        // Debit, Credit and Balance are the last three cells. The empty one of
        // the debit/credit pair prints as a placeholder rather than a blank, so
        // the triple is always present on a real transaction row.
        $n = count($rest);
        if ($n < 3) {
            return null;
        }
        $balance = $rest[$n - 1];
        $credit = $rest[$n - 2];
        $debit = $rest[$n - 3];

        if (!preg_match($layout['money'], $balance)
            || !$this->isAmountCell($debit, $layout)
            || !$this->isAmountCell($credit, $layout)) {
            return null;
        }

        $particular = array_slice($rest, 0, $n - 3);

        // A document number sits in its own column before the Particular text.
        $docNo = null;
        if (count($particular) > 1 && preg_match('/^[A-Z0-9]{8,}$/', $particular[0])) {
            $docNo = array_shift($particular);
        }

        return [
            'line' => 0,
            'date' => $date->format('Y-m-d'),
            'description' => implode(' ', $particular),
            'docNo' => $docNo,
            'debit' => $this->toAmount($debit, $layout),
            'credit' => $this->toAmount($credit, $layout),
            'balance' => (float) str_replace(',', '', $balance),
            'mismatch' => false,
        ];
    }

    /**
     * Splits a balance-driven row's cells into description text and the running
     * balance. Numeric cells are the amount and the balance; the last of them
     * is the balance, and the text cells form the description.
     *
     * @param array<int, string> $cells
     * @param string $money Regex for a numeric cell
     * @return array{0: string, 1: float|null} [description, balance]
     */
    private function splitTextAndBalance(array $cells, string $money): array
    {
        $text = [];
        $balance = null;

        foreach ($cells as $cell) {
            $cell = trim($cell);
            if ($cell === '') {
                continue;
            }
            if (preg_match($money, $cell)) {
                $balance = (float) str_replace(',', '', $cell);   // last one wins
            } else {
                $text[] = $cell;
            }
        }

        return [trim(implode(' ', $text)), $balance];
    }

    /**
     * Builds an empty result carrying an error message.
     *
     * @param string $message
     * @return array{format: null, rows: array, skipped: int, error: string}
     */
    private function failure(string $message): array
    {
        return ['format' => null, 'rows' => [], 'skipped' => 0, 'error' => $message];
    }

    /**
     * Whether a cell is an amount column: either a number or the placeholder
     * the bank prints when that column is empty.
     *
     * @param string $cell
     * @param array<string, string> $layout
     * @return bool
     */
    private function isAmountCell(string $cell, array $layout): bool
    {
        return (bool) (preg_match($layout['money'], $cell) || preg_match($layout['blank'], $cell));
    }

    /**
     * Converts an amount cell to a float, or null when the column is empty.
     *
     * @param string $cell
     * @param array<string, string> $layout
     * @return float|null
     */
    private function toAmount(string $cell, array $layout): ?float
    {
        if (!preg_match($layout['money'], $cell)) {
            return null;
        }

        return (float) str_replace(',', '', $cell);
    }

    /**
     * Whether the cells are an opening/closing balance marker rather than a
     * transaction.
     *
     * @param array<int, string> $cells
     * @return bool
     */
    private function isBalanceMarker(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (preg_match('/<=.*Balance.*=>/i', $cell)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the last money-looking cell on a line, or null.
     *
     * @param array<int, string> $cells
     * @param string $money
     * @return float|null
     */
    private function lastMoney(array $cells, string $money): ?float
    {
        $found = null;
        foreach ($cells as $cell) {
            if (preg_match($money, $cell)) {
                $found = (float) str_replace(',', '', $cell);
            }
        }

        return $found;
    }
}
