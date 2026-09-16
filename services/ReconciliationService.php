<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\services;

use yii\db\Query;

/**
 * ReconciliationService compares the debit (expense) lines of a bank statement
 * against the `reference` column of the workspace's `expenses` table and
 * reports which statement entries are missing from the database.
 *
 * Matching keys, in priority order (tolerant of tail-truncated references that
 * exist in the data):
 *   1. STAN number       e.g. "... STAN (944332)"    -> S:944332
 *   2. AMEZNPKKA code     e.g. "... AMEZNPKKA0265..." -> A:0265...
 *   3. description prefix + amount  (tokenless bank items)
 *
 * Unmatched rows are grouped into content-based buckets so the caller can tell
 * genuine omissions apart from items that are typically not tracked (cash
 * withdrawals, bank fees, reversal washes).
 *
 * This is the web counterpart of .claude/scripts/reconcile.php.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.2.0
 */
class ReconciliationService
{
    /** Statement-line category identifiers (fine-grained; mapped to the SC Schedule of Charges). */
    public const CAT_MISSING = 'missing';
    public const CAT_SERVICE_CHARGE = 'service_charge';
    public const CAT_SMS = 'sms_alert';
    public const CAT_CARD_ANNUAL = 'card_annual';
    public const CAT_FED_PST = 'fed_pst';
    public const CAT_STWH = 'stwh';
    public const CAT_TAX_FILER = 'tax_filer';
    public const CAT_OTHER_FEE = 'other_fee';
    public const CAT_CASH = 'cash';

    /** @var string Expenses table name */
    public $expenseTable = '{{%expenses}}';

    /**
     * Parses raw statement text into normalized debit rows.
     *
     * Three input formats are auto-detected:
     *   1. A column-aligned statement table, as produced by `pdftotext -table`
     *      over a bank's PDF statement. See {@see statementLayouts()} for the
     *      banks recognized.
     *   2. A native bank CSV export whose header row names Date, Description and
     *      Debit/Credit columns. Only rows with a Debit value are kept (credits,
     *      opening/closing balances and other non-expense lines are skipped).
     *   3. A simple TAB-separated list: `date <TAB> amount <TAB> description`.
     *
     * Dates may be `YYYY-MM-DD` or `DD/MM/YYYY`; amounts may contain thousands
     * separators.
     *
     * @param string $raw Raw pasted/uploaded statement text
     * @return array{rows: array<int, array{date: string, amount: string, description: string}>, skipped: int, reversed: int}
     */
    public function parseStatement(string $raw): array
    {
        // `pdftotext` marks page breaks with a form feed, which PHP's trim()
        // does not strip; left in place it glues the first transaction of every
        // page onto the previous row. Treat it as a line break of its own.
        $raw = str_replace(["\r\n", "\r", "\f"], "\n", $raw);
        $lines = array_values(array_filter(
            explode("\n", $raw),
            fn ($l) => trim($l) !== ''
        ));

        if (empty($lines)) {
            return ['rows' => [], 'skipped' => 0, 'reversed' => 0];
        }

        // Detect a column-aligned bank statement table (e.g. `pdftotext -table`
        // output) by its column header and date format.
        $layout = $this->detectStatementLayout($raw);
        if ($layout !== null) {
            [$rows, $skipped, $reversed] = $this->parseStatementTable($lines, $layout);

            return ['rows' => $rows, 'skipped' => $skipped, 'reversed' => $reversed];
        }

        // Detect a native bank CSV export by its header row.
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), str_getcsv($lines[0], ',', '"', ''));
        $idx = [];
        foreach ($header as $i => $h) {
            if ($h === '') {
                continue;
            }
            if (!isset($idx['date']) && str_contains($h, 'date')) {
                $idx['date'] = $i;
            }
            if (!isset($idx['desc']) && str_contains($h, 'description')) {
                $idx['desc'] = $i;
            }
            if (!isset($idx['debit']) && str_contains($h, 'debit')) {
                $idx['debit'] = $i;
            }
            if (!isset($idx['credit']) && str_contains($h, 'credit')) {
                $idx['credit'] = $i;
            }
        }

        if (isset($idx['date'], $idx['desc'], $idx['debit'])) {
            [$rows, $cancellers, $skipped] = $this->parseBankCsv(array_slice($lines, 1), $idx);
        } else {
            [$rows, $cancellers, $skipped] = $this->parseTsv($lines);
        }

        // Drop debits that were reversed/refunded so they are not treated as
        // missing expenses during reconciliation.
        [$rows, $reversed] = $this->dropReversed($rows, $cancellers);

        return ['rows' => $rows, 'skipped' => $skipped, 'reversed' => $reversed];
    }

    /**
     * Parses data rows of a native bank CSV export.
     *
     * Returns debit rows plus "canceller" credits (a credit may be the reversal
     * or refund of a debit; it is used later to drop the reversed debit).
     *
     * @param array<int, string> $lines Data lines (header already removed)
     * @param array{date: int, desc: int, debit: int, credit?: int} $idx Column indices
     * @return array{0: array, 1: array, 2: int} [rows, cancellers, skipped]
     */
    private function parseBankCsv(array $lines, array $idx): array
    {
        $rows = [];
        $cancellers = [];
        $skipped = 0;
        $max = max($idx);
        $creditIdx = $idx['credit'] ?? null;

        foreach ($lines as $line) {
            $cols = array_map(fn ($c) => trim((string) $c), str_getcsv($line, ',', '"', ''));
            if (count($cols) <= $max) {
                $skipped++;
                continue;
            }

            $description = $cols[$idx['desc']];
            $debit = str_replace(',', '', $cols[$idx['debit']]);
            $credit = $creditIdx !== null ? str_replace(',', '', $cols[$creditIdx]) : '';

            if ($debit !== '' && (float) $debit != 0.0) {
                if ($description === '') {
                    $skipped++;
                    continue;
                }
                $rows[] = [
                    'date' => $this->normalizeDate($cols[$idx['date']]),
                    'amount' => $this->normAmount($debit),
                    'description' => $description,
                ];
            } elseif ($credit !== '' && (float) $credit != 0.0 && $description !== '') {
                $cancellers[] = [
                    'amount' => $this->normAmount($credit),
                    'tokens' => $this->reversalTokens($description),
                ];
            }
        }

        return [$rows, $cancellers, $skipped];
    }

    /**
     * Parses a TAB-separated `date <TAB> amount <TAB> description` list.
     *
     * @param array<int, string> $lines Non-empty lines
     * @return array{0: array, 1: array, 2: int} [rows, cancellers, skipped]
     */
    private function parseTsv(array $lines): array
    {
        $rows = [];
        $skipped = 0;

        foreach ($lines as $line) {
            $parts = array_map('trim', explode("\t", $line, 3));
            if (count($parts) < 3 || $parts[1] === '' || $parts[2] === '') {
                $skipped++;
                continue;
            }

            [$date, $amount, $description] = $parts;
            $rows[] = [
                'date' => $this->normalizeDate($date),
                'amount' => $this->normAmount($amount),
                'description' => $description,
            ];
        }

        return [$rows, [], $skipped];
    }

    /**
     * The column-aligned statement table layouts we recognize in `pdftotext
     * -table` output. Each entry describes one bank's table:
     *
     *   header  - regex identifying the column header row
     *   probe   - regex proving at least one transaction row exists in the text
     *   row     - regex matching the leading date cell of a transaction row
     *   end     - regex for a line that closes the table, or null if none
     *   numeric - regex for an amount / running-balance cell
     *   bareAmount - whether debits may print without decimals, so a trailing
     *                integer cell is the amount rather than description text
     *
     * @return array<string, array{header: string, probe: string, row: string, end: string|null, numeric: string, bareAmount: bool}>
     */
    private static function statementLayouts(): array
    {
        return [
            // Standard Chartered: Date / Description / Withdrawal / Deposit /
            // Balance, dates as `01Jul25`, balances always to 2 decimals.
            'sc' => [
                'header' => '/\bDate\b.*\bDescription\b.*\bWithdrawal\b.*\bBalance\b/i',
                'probe' => '/^\s*\d{2}[A-Za-z]{3}\d{2}\s/m',
                'row' => '/^\d{2}[A-Za-z]{3}\d{2}$/',
                'end' => '/This is an electronic statement|Deposits Statement/i',
                'numeric' => '/^\d[\d,]*\.\d{2}$/',
                'bareAmount' => false,
            ],
            // Meezan Bank: Transaction Date / Description / Debit / Credit /
            // Available Balance, dates as `01/07/2025`. Balances print with one
            // or two decimals (e.g. `2639065.3`) and debit amounts often carry
            // none at all (e.g. `5,866`), so the numeric cell is looser here.
            'meezan' => [
                'header' => '/\bDate\b.*\bDescription\b.*\bDebit\b.*\bCredit\b.*\bBalance\b/i',
                'probe' => '#^\s*\d{2}/\d{2}/\d{4}\s#m',
                'row' => '#^\d{2}/\d{2}/\d{4}$#',
                'end' => null,
                'numeric' => '/^\d[\d,]*\.\d{1,2}$/',
                'bareAmount' => true,
            ],
        ];
    }

    /**
     * Returns the layout of the column-aligned statement table in $raw, or null
     * when the text is not one (a CSV or TSV paste, say).
     *
     * Both the column header and a real transaction row are required, so a
     * comma-CSV that merely names a Withdrawal or Debit column is still routed
     * to the CSV parser rather than here.
     *
     * @param string $raw
     * @return array{header: string, probe: string, row: string, end: string|null, numeric: string, bareAmount: bool}|null
     */
    private function detectStatementLayout(string $raw): ?array
    {
        foreach (self::statementLayouts() as $layout) {
            if (preg_match($layout['header'], $raw) && preg_match($layout['probe'], $raw)) {
                return $layout;
            }
        }

        return null;
    }

    /**
     * Parses a column-aligned statement table into normalized debit rows.
     *
     * Bank statement PDFs place the date, description and amount columns in
     * separate text runs that do not line up row-for-row, so the printed
     * "withdrawal" value is unreliable. Instead the running Balance column is
     * the ground truth: a row is a debit (expense) exactly when the balance
     * decreases, and the amount is that decrease. Deposits (balance increases)
     * and carry-forward lines are ignored.
     *
     * Rows begin with the layout's date cell (e.g. `01Jul25` or `01/07/2025`);
     * indented lines without a date continue the previous row's description.
     *
     * @param array<int, string> $lines Non-empty statement lines
     * @param array{header: string, row: string, end: string|null, numeric: string, bareAmount: bool} $layout
     * @return array{0: array, 1: int, 2: int} [rows, skipped, reversed]
     */
    private function parseStatementTable(array $lines, array $layout): array
    {
        $rows = [];
        $cancellers = [];   // credits/deposits: not expenses, and may reverse a debit
        $skipped = 0;
        $inTable = false;
        $prevBalance = null;
        $cur = null;

        $flush = function () use (&$cur, &$rows, &$cancellers, &$prevBalance, &$skipped): void {
            if ($cur === null) {
                return;
            }
            $balance = $cur['balance'];
            if ($balance === null) {
                $cur = null;
                return;
            }
            if ($prevBalance === null) {
                $prevBalance = $balance;     // establish the opening baseline
                $cur = null;
                return;
            }

            $delta = round($prevBalance - $balance, 2);
            $prevBalance = $balance;
            $desc = trim(preg_replace('/\s+/', ' ', (string) $cur['desc']));

            if ($delta > 0.0) {
                $rows[] = [
                    'date' => $cur['date'],
                    'amount' => $this->normAmount($delta),
                    'description' => $desc,
                ];
            } elseif ($delta < 0.0) {
                $cancellers[] = [
                    'amount' => $this->normAmount(-$delta),
                    'tokens' => $this->reversalTokens($desc),
                ];
            } else {
                $skipped++;                  // no balance movement
            }
            $cur = null;
        };

        foreach ($lines as $line) {
            if (preg_match($layout['header'], $line)) {
                $flush();
                $inTable = true;
                continue;
            }
            if ($layout['end'] !== null && preg_match($layout['end'], $line)) {
                $flush();
                $inTable = false;
                continue;
            }
            if (!$inTable) {
                continue;
            }

            // Carry-forward line: reset the running balance, not a transaction.
            if (preg_match('/BALANCE\s+B\/F/i', $line)) {
                $flush();
                $bal = $this->trailingAmount($line);
                if ($bal !== null) {
                    $prevBalance = $bal;
                }
                continue;
            }

            $cells = preg_split('/\s{2,}/', trim($line));
            if (preg_match($layout['row'], $cells[0] ?? '')) {
                // New transaction row: date, then description and amount cells.
                $flush();
                $date = $this->normStatementDate($cells[0]);
                [$desc, $balance] = $this->splitTableCells(array_slice($cells, 1), $layout['numeric'], $layout['bareAmount']);
                $cur = ['date' => $date, 'desc' => $desc, 'balance' => $balance];
            } elseif ($cur !== null) {
                // Continuation of the current row's description (drop numbers).
                [$desc] = $this->splitTableCells($cells, $layout['numeric'], $layout['bareAmount']);
                if ($desc !== '') {
                    $cur['desc'] .= ' ' . $desc;
                }
            }
        }
        $flush();

        // A credit is a deposit rather than a missing expense, but when it
        // reverses a debit on the same statement the debit must go too. The
        // reversal is already explicit as a credit line here, so the
        // description-prefix rule used for CSV exports is not applied.
        [$rows, $washed] = $this->dropReversed($rows, $cancellers, false);

        return [$rows, $skipped, count($cancellers) + $washed];
    }

    /**
     * Splits a row's non-date cells into a description and the trailing balance.
     * Numeric cells (amounts/balance) are separated from text; the last numeric
     * cell is the running balance, and text cells form the description.
     *
     * Some banks print whole-rupee debits without decimals (Meezan's `5,866`),
     * which no balance pattern can match. For those ($bareAmount), a bare
     * integer cell sitting at the end of the row is dropped as the transaction
     * amount rather than kept as description text. Integers earlier in the row
     * (account and reference numbers) are part of the description and are left
     * alone either way.
     *
     * @param array<int, string> $cells
     * @param string $numeric Regex matching an amount / balance cell
     * @param bool $bareAmount Whether a trailing integer cell is an amount
     * @return array{0: string, 1: float|null} [description, balance]
     */
    private function splitTableCells(array $cells, string $numeric, bool $bareAmount): array
    {
        $descParts = [];
        $balance = null;
        foreach ($cells as $cell) {
            $cell = trim($cell);
            if ($cell === '') {
                continue;
            }
            if (preg_match($numeric, $cell)) {
                $balance = (float) str_replace(',', '', $cell);   // last numeric wins
            } else {
                $descParts[] = $cell;
            }
        }

        if ($bareAmount && $balance !== null && !empty($descParts) && preg_match('/^\d[\d,]*$/', end($descParts))) {
            array_pop($descParts);
        }

        return [trim(implode(' ', $descParts)), $balance];
    }

    /** Returns the last amount-looking number on a line, or null. */
    private function trailingAmount(string $line): ?float
    {
        if (preg_match_all('/\d[\d,]*\.\d{2}/', $line, $m) && !empty($m[0])) {
            return (float) str_replace(',', '', end($m[0]));
        }
        return null;
    }

    /**
     * Normalizes a statement date to `YYYY-MM-DD`, accepting `DDMonYY`
     * (e.g. `01Jul25`) as well as the `DD/MM/YYYY` form handled by
     * {@see normalizeDate()}. Returns the input unchanged if it is neither.
     *
     * @param string $date
     * @return string
     */
    private function normStatementDate(string $date): string
    {
        static $months = [
            'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
            'may' => '05', 'jun' => '06', 'jul' => '07', 'aug' => '08',
            'sep' => '09', 'oct' => '10', 'nov' => '11', 'dec' => '12',
        ];
        if (preg_match('/^(\d{2})([A-Za-z]{3})(\d{2})$/', trim($date), $m)) {
            $mon = $months[strtolower($m[2])] ?? null;
            if ($mon !== null) {
                return '20' . $m[3] . '-' . $mon . '-' . $m[1];
            }
        }
        return $this->normalizeDate($date);
    }

    /**
     * Removes debit rows that were reversed or refunded, so they are not
     * reported as missing expenses. A debit is dropped when it is an explicit
     * reversal line, or when a credit exists with the same amount and a shared
     * transfer/STAN token (its reversal/refund).
     *
     * @param array $rows Debit rows
     * @param array $cancellers Credit rows [amount, tokens]
     * @param bool $dropExplicit Whether a debit whose description reads as a
     *     reversal is a wash on its own. True for CSV exports, where the
     *     matching credit is not always present; false for statement tables,
     *     where it always is and the pairing above handles it.
     * @return array{0: array, 1: int} [keptRows, reversedCount]
     */
    private function dropReversed(array $rows, array $cancellers, bool $dropExplicit = true): array
    {
        $kept = [];
        $reversed = 0;

        foreach ($rows as $row) {
            if ($dropExplicit && $this->isExplicitReversal($row['description'])) {
                $reversed++;
                continue;
            }

            $tokens = $this->reversalTokens($row['description']);
            $hit = null;
            if (!empty($tokens)) {
                foreach ($cancellers as $ci => $c) {
                    if ($c['amount'] === $row['amount'] && array_intersect($c['tokens'], $tokens)) {
                        $hit = $ci;
                        break;
                    }
                }
            }

            if ($hit !== null) {
                unset($cancellers[$hit]);   // each credit reverses at most one debit
                $reversed++;
                continue;
            }

            $kept[] = $row;
        }

        return [$kept, $reversed];
    }

    /** Whether a debit line is itself a reversal wash (money returned). */
    private function isExplicitReversal(string $desc): bool
    {
        $n = $this->normDesc($desc);
        return str_starts_with($n, 'REVERSAL FOR') || str_starts_with($n, 'REV ');
    }

    /**
     * Extracts the significant tokens (transfer code / STAN number) used to pair
     * a reversal credit with its original debit.
     *
     * @param string $desc
     * @return array<int, string>
     */
    private function reversalTokens(string $desc): array
    {
        $n = $this->normDesc($desc);
        $tokens = [];
        if (preg_match('/AMEZNPKKA(\w{6,})/', $n, $m)) {
            $tokens[] = 'A' . $m[1];
        }
        if (preg_match_all('/\b\d{5,6}\b/', $n, $mm)) {
            foreach ($mm[0] as $num) {
                $tokens[] = 'N' . $num;
            }
        }
        return array_values(array_unique($tokens));
    }

    /**
     * Normalizes a date to `YYYY-MM-DD` (accepts `DD/MM/YYYY` too).
     *
     * @param string $date
     * @return string
     */
    private function normalizeDate(string $date): string
    {
        $date = trim($date);
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $date, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        return $date;
    }

    /**
     * Reconciles parsed statement rows against a workspace's expenses.
     *
     * @param array<int, array{date: string, amount: string, description: string}> $rows
     * @param int $workspaceId Owning workspace ID
     * @return array Summary, per-bucket missing rows, and totals
     */
    public function reconcile(array $rows, int $workspaceId): array
    {
        $dbKeys = [];        // key => count (non-blank references)
        $dbStan = [];        // bare STAN digits present anywhere
        $dbByDateAmt = [];   // "date|amt" => count (non-blank rows)
        $dbBlank = [];       // "date|amt" => count (blank-reference rows)
        $latestDate = null;

        $records = (new Query())
            ->select(['reference', 'amount', 'expense_date'])
            ->from($this->expenseTable)
            ->where(['workspace_id' => $workspaceId])
            ->all();

        foreach ($records as $row) {
            $date = (string) $row['expense_date'];
            if ($latestDate === null || $date > $latestDate) {
                $latestDate = $date;
            }
            $amt = $this->normAmount($row['amount']);
            $damt = $date . '|' . $amt;
            $ref = (string) ($row['reference'] ?? '');

            if (trim($ref) === '') {
                $dbBlank[$damt] = ($dbBlank[$damt] ?? 0) + 1;
                continue;
            }

            $k = $this->keyOf($ref, $row['amount']);
            $dbKeys[$k] = ($dbKeys[$k] ?? 0) + 1;
            if (preg_match('/STAN\s*\(\s*(\d+)/', $this->normDesc($ref), $m)) {
                $dbStan[$m[1]] = true;
            }
            $dbByDateAmt[$damt] = ($dbByDateAmt[$damt] ?? 0) + 1;
        }

        $missing = [];
        $matched = 0;
        $remaining = $dbKeys;

        foreach ($rows as $row) {
            $k = $this->keyOf($row['description'], $row['amount']);
            if (($remaining[$k] ?? 0) > 0) {
                $remaining[$k]--;
                $matched++;
                continue;
            }

            $flags = [];
            if (preg_match('/STAN\s*\(\s*(\d+)/', $this->normDesc($row['description']), $m) && isset($dbStan[$m[1]])) {
                $flags[] = 'STAN #' . $m[1] . ' exists in DB (different amount?)';
            }
            $damt = $row['date'] . '|' . $row['amount'];
            if (isset($dbBlank[$damt])) {
                $flags[] = 'A blank-reference expense exists on this date & amount';
            } elseif (isset($dbByDateAmt[$damt])) {
                $flags[] = 'Another expense exists on this date & amount';
            }

            $missing[] = [
                'date' => $row['date'],
                'amount' => $row['amount'],
                'description' => $row['description'],
                'flags' => $flags,
                'category' => $this->categoryOf($row['description']),
            ];
        }

        // Group unmatched rows by fine category, preserving the metadata order.
        $categories = array_fill_keys(array_keys(self::statementCategories()), []);
        foreach ($missing as $row) {
            $categories[$row['category']][] = $row;
        }

        return [
            'totalRows' => count($rows),
            'matched' => $matched,
            'missingCount' => count($missing),
            'latestDbDate' => $latestDate,
            'categories' => $categories,
            'categoryTotals' => array_map(
                fn (array $c) => array_sum(array_map(fn ($r) => (float) $r['amount'], $c)),
                $categories
            ),
        ];
    }

    // ─── Matching internals ──────────────────────────────────────────

    /** Builds the strongest available match key for a (description, amount) pair. */
    private function keyOf(string $desc, $amt): string
    {
        $n = $this->normDesc($desc);
        if (preg_match('/STAN\s*\(\s*(\d+)/', $n, $m)) {
            return 'S:' . $m[1];
        }
        if (preg_match('/AMEZNPKKA(\w{6,})/', $n, $m)) {
            return 'A:' . $m[1];
        }
        return 'F:' . substr($n, 0, 22) . ':' . $this->normAmount($amt);
    }

    /**
     * Classifies an unmatched statement description into a fine-grained
     * category, keyed to the Standard Chartered Schedule of Charges and to the
     * equivalent Meezan Bank fee lines. Order matters: the tax lines are
     * checked before the fee they are levied on (e.g. "FED/PST AMOUNT ON ATM
     * ANNUAL FEE" is a tax, not the card fee).
     *
     * @param string $desc
     * @return string One of the CAT_* constants
     */
    private function categoryOf(string $desc): string
    {
        $u = strtoupper($desc);
        if (preg_match('/ATM CASH WITHDRAWA/', $u)) {
            return self::CAT_CASH;
        }
        // Government levies / taxes first (FED = federal excise, PST = provincial
        // sales tax, STWH = sales-tax withholding, TAX FILER = advance income tax).
        if (preg_match('/FED\/PST|FED ON DUP/', $u)) {
            return self::CAT_FED_PST;
        }
        if (preg_match('/STWH/', $u)) {
            return self::CAT_STWH;
        }
        if (preg_match('/TAX FILER/', $u)) {
            return self::CAT_TAX_FILER;
        }
        // Then the underlying bank fees.
        if (preg_match('/SERVICE CHG/', $u)) {
            return self::CAT_SERVICE_CHARGE;
        }
        if (preg_match('/SMS CHARGES/', $u)) {
            return self::CAT_SMS;
        }
        if (preg_match('/ATM ANNUAL FEE/', $u)) {
            return self::CAT_CARD_ANNUAL;
        }
        // `CHG:PKR... FBRTax:PKR...` is Meezan's combined transaction charge and
        // FBR tax on international card purchases: one line, charge inclusive.
        if (preg_match('/BANK CHARGES|EXP ADV PAY|DUPLICATE STATEMENT|LOCKER ASSIGNED|CHG:PKR|FBRTAX/', $u)) {
            return self::CAT_OTHER_FEE;
        }
        return self::CAT_MISSING;
    }

    /** Normalizes an amount to a fixed 2-decimal string (strips thousands separators). */
    private function normAmount($a): string
    {
        return number_format((float) str_replace(',', '', trim((string) $a)), 2, '.', '');
    }

    /** Uppercases and collapses whitespace in a description. */
    private function normDesc($d): string
    {
        return preg_replace('/\s+/', ' ', strtoupper(trim((string) $d)));
    }

    /**
     * Ordered metadata for each statement-line category: a short tab label, an
     * icon, a colour tone, whether rows are actionable (addable as expenses),
     * the matching FBR tax category code (or null), and the matching entry in
     * the Standard Chartered Schedule of Charges.
     *
     * @return array<string, array{label: string, icon: string, tone: string, actionable: bool, fbr: string|null, soc: string}>
     */
    public static function statementCategories(): array
    {
        return [
            self::CAT_MISSING => [
                'label' => \Yii::t('app', 'Missing Expenses'),
                'icon' => 'bi-exclamation-diamond',
                'tone' => 'danger',
                'actionable' => true,
                'fbr' => null,
                'soc' => \Yii::t('app', 'Genuine purchases on the statement that are not yet recorded as expenses. Add the ones you want to track.'),
            ],
            self::CAT_SERVICE_CHARGE => [
                'label' => \Yii::t('app', 'Service Charge'),
                'icon' => 'bi-bank2',
                'tone' => 'secondary',
                'actionable' => true,
                'fbr' => 'SERVICE_CHARGE',
                'soc' => \Yii::t('app', 'Current-account service / minimum-balance charge, inclusive of Federal Excise Duty. SC Schedule of Charges: Account Services.'),
            ],
            self::CAT_SMS => [
                'label' => \Yii::t('app', 'SMS Alert Fee'),
                'icon' => 'bi-chat-dots',
                'tone' => 'secondary',
                'actionable' => true,
                'fbr' => 'SMS_ALERT_FEE',
                'soc' => \Yii::t('app', 'SMS alert fee on account transactions. SC Schedule of Charges: Account Services.'),
            ],
            self::CAT_CARD_ANNUAL => [
                'label' => \Yii::t('app', 'Card Annual Fee'),
                'icon' => 'bi-credit-card-2-front',
                'tone' => 'secondary',
                'actionable' => true,
                'fbr' => 'CARD_ANNUAL_FEE',
                'soc' => \Yii::t('app', 'Debit card annual / issuance fee. SC Schedule of Charges: Debit Cards.'),
            ],
            self::CAT_FED_PST => [
                'label' => \Yii::t('app', 'FED / Sales Tax'),
                'icon' => 'bi-percent',
                'tone' => 'secondary',
                'actionable' => true,
                'fbr' => 'FED_SALES_TAX',
                'soc' => \Yii::t('app', 'Federal Excise Duty / Provincial Sales Tax charged on bank fees and card transactions. Schedule of Charges, general note (a).'),
            ],
            self::CAT_STWH => [
                'label' => \Yii::t('app', 'Sales Tax Withholding'),
                'icon' => 'bi-percent',
                'tone' => 'secondary',
                'actionable' => true,
                'fbr' => 'SALES_TAX_WHT',
                'soc' => \Yii::t('app', 'Provincial sales-tax withholding on services (e.g. IT services).'),
            ],
            self::CAT_TAX_FILER => [
                'label' => \Yii::t('app', 'Advance Income Tax'),
                'icon' => 'bi-receipt',
                'tone' => 'secondary',
                'actionable' => true,
                'fbr' => 'ADV_INCOME_TAX',
                'soc' => \Yii::t('app', 'Adjustable advance income tax on card transactions (Income Tax Ordinance) - reclaimable when you file your return.'),
            ],
            self::CAT_OTHER_FEE => [
                'label' => \Yii::t('app', 'Other Fees'),
                'icon' => 'bi-cash-stack',
                'tone' => 'secondary',
                'actionable' => true,
                'fbr' => 'OTHER_FEES',
                'soc' => \Yii::t('app', 'Other bank fees and charges.'),
            ],
            self::CAT_CASH => [
                'label' => \Yii::t('app', 'Cash Withdrawals'),
                'icon' => 'bi-cash-coin',
                'tone' => 'secondary',
                'actionable' => true,
                'fbr' => null,
                'soc' => \Yii::t('app', 'ATM cash withdrawals - money moved to cash, usually not tracked as an expense.'),
            ],
        ];
    }
}
