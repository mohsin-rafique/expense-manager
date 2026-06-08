<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\services;

use Yii;
use app\models\Expense;
use app\models\Income;
use app\models\ExpenseCategory;
use app\models\IncomeCategory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsxDate;

/**
 * ImportService parses CSV/XLSX/XLS spreadsheets and bulk-imports transactions.
 *
 * Supports both expense and income imports. It is tolerant of the application's
 * own exported report layout (title rows + a header row further down) as well as
 * clean templates whose header is the first row. Column mapping is by header
 * name, so column order does not matter.
 *
 * Workflow:
 *  1. {@see parse()} reads a file into normalized associative rows.
 *  2. {@see validateRows()} produces a per-row preview (valid/invalid, duplicate,
 *     category-to-be-created) without touching the database.
 *  3. {@see import()} persists the valid rows inside a transaction.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class ImportService
{
    public const TYPE_EXPENSE = 'expense';
    public const TYPE_INCOME = 'income';

    /** @var int Hard cap on rows processed from a single file */
    public int $maxRows = 5000;

    /**
     * Canonical field => list of accepted header aliases (lower-case).
     */
    private const HEADER_ALIASES = [
        'date' => ['date', 'transaction date', 'expense date', 'income date', 'entry date'],
        'category' => ['category', 'category name'],
        'payment_method' => ['payment method', 'payment', 'method'],
        'reference' => ['reference', 'ref', 'invoice', 'receipt'],
        'description' => ['description', 'desc', 'note', 'notes', 'details'],
        'amount' => ['amount', 'value', 'total'],
    ];

    /**
     * Returns the ordered column list for a given import type (used by the
     * downloadable template and the preview table).
     *
     * @param string $type
     * @return string[]
     */
    public static function columnsFor(string $type): array
    {
        if ($type === self::TYPE_INCOME) {
            return ['Date', 'Category', 'Reference', 'Description', 'Amount'];
        }

        return ['Date', 'Category', 'Payment Method', 'Reference', 'Description', 'Amount'];
    }

    /**
     * Reads a spreadsheet file into normalized associative rows.
     *
     * @param string $path Absolute path to the uploaded file
     * @return array{rows: array<int,array>, error: string|null}
     *   Each row: ['_line' => int, 'date' => ?string, 'category' => ?string,
     *   'payment_method' => ?string, 'reference' => ?string,
     *   'description' => ?string, 'amount' => ?string]
     */
    public function parse(string $path): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(false); // keep formatting so dates resolve
            // For CSV, pin the delimiter to a comma so title rows above the
            // header don't throw off PhpSpreadsheet's delimiter auto-detection
            // (the app's own template and exports are comma-separated UTF-8).
            if ($reader instanceof \PhpOffice\PhpSpreadsheet\Reader\Csv) {
                $reader->setDelimiter(',');
                $reader->setInputEncoding('UTF-8');
            }
            $spreadsheet = $reader->load($path);
        } catch (\Throwable $e) {
            Yii::error('Import parse failed: ' . $e->getMessage(), __METHOD__);
            return ['rows' => [], 'error' => Yii::t('app', 'The file could not be read. Please upload a valid CSV or Excel file.')];
        }

        $sheet = $spreadsheet->getActiveSheet();
        $matrix = $sheet->toArray(null, true, true, false); // 0-indexed columns

        if (empty($matrix)) {
            return ['rows' => [], 'error' => Yii::t('app', 'The file appears to be empty.')];
        }

        // Locate the header row (first row containing both a Date and an Amount header)
        $headerIndex = $this->findHeaderRow($matrix);
        if ($headerIndex === null) {
            return ['rows' => [], 'error' => Yii::t('app', 'No header row found. Make sure the sheet has "Date" and "Amount" columns.')];
        }

        $map = $this->mapColumns($matrix[$headerIndex]);

        $rows = [];
        $total = count($matrix);
        for ($i = $headerIndex + 1; $i < $total; $i++) {
            if (count($rows) >= $this->maxRows) {
                break;
            }

            $raw = $matrix[$i];
            $row = [
                '_line' => $i + 1, // 1-based spreadsheet line number
                'date' => $this->cellAt($raw, $map, 'date', $sheet, $i),
                'category' => $this->cellAt($raw, $map, 'category'),
                'payment_method' => $this->cellAt($raw, $map, 'payment_method'),
                'reference' => $this->cellAt($raw, $map, 'reference'),
                'description' => $this->cellAt($raw, $map, 'description'),
                'amount' => $this->cellAt($raw, $map, 'amount'),
            ];

            // Skip fully blank rows
            if ($this->isBlankRow($row)) {
                continue;
            }

            $rows[] = $row;
        }

        return ['rows' => $rows, 'error' => null];
    }

    /**
     * Validates parsed rows and annotates each with preview metadata.
     *
     * @param array $rows Output of {@see parse()}
     * @param string $type self::TYPE_*
     * @param int $userId
     * @param array $options ['autoCreateCategories' => bool, 'skipDuplicates' => bool]
     * @return array{rows: array, summary: array}
     */
    public function validateRows(array $rows, string $type, int $userId, array $options): array
    {
        $autoCreate = !empty($options['autoCreateCategories']);
        $skipDuplicates = !empty($options['skipDuplicates']);

        $categoryMap = $this->categoryNameMap($type, $userId); // lower-name => id

        $result = [];
        $valid = 0;
        $invalid = 0;
        $duplicates = 0;
        $newCategories = [];

        foreach ($rows as $row) {
            $errors = [];

            // Date
            $date = $this->normalizeDate($row['date']);
            if ($date === null) {
                $errors[] = Yii::t('app', 'Invalid or missing date.');
            }

            // Amount
            $amount = $this->normalizeAmount($row['amount']);
            if ($amount === null || $amount <= 0) {
                $errors[] = Yii::t('app', 'Invalid or missing amount.');
            }

            // Category
            $categoryName = trim((string) $row['category']);
            $categoryId = null;
            $willCreate = false;
            if ($categoryName === '') {
                $errors[] = Yii::t('app', 'Category is required.');
            } else {
                $key = mb_strtolower($categoryName);
                if (isset($categoryMap[$key])) {
                    $categoryId = $categoryMap[$key];
                } elseif ($autoCreate) {
                    $willCreate = true;
                    if (!in_array($key, array_map('mb_strtolower', $newCategories), true)) {
                        $newCategories[] = $categoryName;
                    }
                } else {
                    $errors[] = Yii::t('app', 'Category "{name}" does not exist.', ['name' => $categoryName]);
                }
            }

            // Duplicate detection (only meaningful when core fields are valid)
            $isDuplicate = false;
            if (empty($errors) && $skipDuplicates && $categoryId !== null) {
                $isDuplicate = $this->isDuplicate($type, $userId, $categoryId, $date, $amount, (string) $row['reference']);
            }

            $isValid = empty($errors);
            if (!$isValid) {
                $invalid++;
            } elseif ($isDuplicate) {
                $duplicates++;
            } else {
                $valid++;
            }

            $result[] = [
                'line' => $row['_line'],
                'date' => $date ?? $row['date'],
                'category' => $categoryName,
                'payment_method' => $this->normalizePaymentMethod($row['payment_method']),
                'reference' => $row['reference'],
                'description' => $row['description'],
                'amount' => $amount,
                'valid' => $isValid,
                'duplicate' => $isDuplicate,
                'willCreateCategory' => $willCreate,
                'errors' => $errors,
            ];
        }

        return [
            'rows' => $result,
            'summary' => [
                'total' => count($result),
                'valid' => $valid,
                'invalid' => $invalid,
                'duplicates' => $duplicates,
                'newCategories' => $newCategories,
                'importable' => $valid, // rows that will actually be inserted
            ],
        ];
    }

    /**
     * Imports the valid (non-duplicate) rows from a parsed file.
     *
     * @param array $rows Output of {@see parse()}
     * @param string $type self::TYPE_*
     * @param int $userId
     * @param array $options ['autoCreateCategories' => bool, 'skipDuplicates' => bool]
     * @return array{imported:int, skipped:int, failed:int, createdCategories:int, errors:array}
     */
    public function import(array $rows, string $type, int $userId, array $options): array
    {
        $autoCreate = !empty($options['autoCreateCategories']);
        $skipDuplicates = !empty($options['skipDuplicates']);

        $categoryMap = $this->categoryNameMap($type, $userId);

        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $createdCategories = 0;
        $errors = [];

        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($rows as $row) {
                $date = $this->normalizeDate($row['date']);
                $amount = $this->normalizeAmount($row['amount']);
                $categoryName = trim((string) $row['category']);

                if ($date === null || $amount === null || $amount <= 0 || $categoryName === '') {
                    $skipped++;
                    continue;
                }

                $key = mb_strtolower($categoryName);
                $categoryId = $categoryMap[$key] ?? null;

                if ($categoryId === null) {
                    if (!$autoCreate) {
                        $skipped++;
                        continue;
                    }
                    $categoryId = $this->createCategory($type, $userId, $categoryName);
                    if ($categoryId === null) {
                        $failed++;
                        $errors[] = Yii::t('app', 'Line {line}: failed to create category.', ['line' => $row['_line']]);
                        continue;
                    }
                    $categoryMap[$key] = $categoryId;
                    $createdCategories++;
                }

                if ($skipDuplicates && $this->isDuplicate($type, $userId, $categoryId, $date, $amount, (string) $row['reference'])) {
                    $skipped++;
                    continue;
                }

                $model = $this->buildModel($type, $userId, $categoryId, $date, $amount, $row);

                if ($model->save()) {
                    $imported++;
                } else {
                    $failed++;
                    $errors[] = Yii::t('app', 'Line {line}: {error}', [
                        'line' => $row['_line'],
                        'error' => implode('; ', $model->getFirstErrors()),
                    ]);
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error('Import failed: ' . $e->getMessage(), __METHOD__);
            return [
                'imported' => 0,
                'skipped' => 0,
                'failed' => count($rows),
                'createdCategories' => 0,
                'errors' => [Yii::t('app', 'Import aborted: {error}', ['error' => $e->getMessage()])],
            ];
        }

        return compact('imported', 'skipped', 'failed', 'createdCategories', 'errors');
    }

    // ─── Internal helpers ────────────────────────────────────────────

    /**
     * Builds an unsaved Expense/Income model from a row.
     *
     * @return Expense|Income
     */
    private function buildModel(string $type, int $userId, int $categoryId, string $date, float $amount, array $row)
    {
        $reference = $this->clean($row['reference']);
        $description = $this->clean($row['description']);
        $amountStr = number_format($amount, 2, '.', '');

        if ($type === self::TYPE_INCOME) {
            $model = new Income();
            $model->user_id = $userId;
            $model->income_category_id = $categoryId;
            $model->entry_date = $date;
            $model->amount = $amountStr;
            $model->reference = $reference;
            $model->description = $description;
        } else {
            $model = new Expense();
            $model->user_id = $userId;
            $model->expense_category_id = $categoryId;
            $model->expense_date = $date;
            $model->amount = $amountStr;
            $model->reference = $reference;
            $model->description = $description;
            $model->payment_method = $this->normalizePaymentMethod($row['payment_method']);
        }

        // Blameable is web-only; set explicitly so console/import works too
        $model->created_by = $userId;
        $model->updated_by = $userId;

        return $model;
    }

    /**
     * Finds the header row index by scanning for Date + Amount headers.
     *
     * @param array $matrix
     * @return int|null
     */
    private function findHeaderRow(array $matrix): ?int
    {
        $limit = min(count($matrix), 25);
        for ($i = 0; $i < $limit; $i++) {
            $cells = array_map(fn ($c) => mb_strtolower(trim((string) $c)), $matrix[$i]);
            $hasDate = (bool) array_intersect($cells, self::HEADER_ALIASES['date']);
            $hasAmount = (bool) array_intersect($cells, self::HEADER_ALIASES['amount']);
            if ($hasDate && $hasAmount) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Maps canonical field names to column indexes using the header row.
     *
     * @param array $headerCells
     * @return array<string,int>
     */
    private function mapColumns(array $headerCells): array
    {
        $map = [];
        foreach ($headerCells as $idx => $cell) {
            $name = mb_strtolower(trim((string) $cell));
            if ($name === '') {
                continue;
            }
            foreach (self::HEADER_ALIASES as $field => $aliases) {
                if (!isset($map[$field]) && in_array($name, $aliases, true)) {
                    $map[$field] = $idx;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Returns the value for a mapped field from a raw row.
     *
     * For the date field, when a worksheet is supplied and the cell holds an
     * Excel date serial, it is converted to Y-m-d.
     *
     * @param array $raw
     * @param array $map
     * @param string $field
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet|null $sheet
     * @param int|null $rowIndex 0-based row index in the matrix
     * @return string|null
     */
    private function cellAt(array $raw, array $map, string $field, $sheet = null, ?int $rowIndex = null): ?string
    {
        if (!isset($map[$field])) {
            return null;
        }

        $col = $map[$field];
        $value = $raw[$col] ?? null;

        // Resolve Excel date serials to a readable date string
        if ($field === 'date' && $sheet !== null && $rowIndex !== null && is_numeric($value)) {
            try {
                $coordinate = Coordinate::stringFromColumnIndex($col + 1) . ($rowIndex + 1);
                $cell = $sheet->getCell($coordinate);
                if (XlsxDate::isDateTime($cell)) {
                    return XlsxDate::excelToDateTimeObject((float) $value, new \DateTimeZone('UTC'))->format('Y-m-d');
                }
            } catch (\Throwable) {
                // fall through to raw value
            }
        }

        return $value === null ? null : trim((string) $value);
    }

    /**
     * Builds a lower-cased category-name => id map for the user.
     *
     * @param string $type
     * @param int $userId
     * @return array<string,int>
     */
    private function categoryNameMap(string $type, int $userId): array
    {
        $class = $type === self::TYPE_INCOME ? IncomeCategory::class : ExpenseCategory::class;

        $rows = $class::find()
            ->select(['id', 'name'])
            ->where(['workspace_id' => Yii::$app->workspace->getId()])
            ->asArray()
            ->all();

        $map = [];
        foreach ($rows as $r) {
            // First match wins for duplicate names
            $key = mb_strtolower($r['name']);
            if (!isset($map[$key])) {
                $map[$key] = (int) $r['id'];
            }
        }

        return $map;
    }

    /**
     * Creates a category for the given type and returns its id.
     *
     * @return int|null
     */
    private function createCategory(string $type, int $userId, string $name): ?int
    {
        $model = $type === self::TYPE_INCOME ? new IncomeCategory() : new ExpenseCategory();
        $model->user_id = $userId;
        $model->name = $name;
        if ($model->hasAttribute('status')) {
            $model->status = 1;
        }
        $model->created_by = $userId;
        $model->updated_by = $userId;

        return $model->save() ? (int) $model->id : null;
    }

    /**
     * Checks whether a matching transaction already exists.
     *
     * @return bool
     */
    private function isDuplicate(string $type, int $userId, int $categoryId, string $date, float $amount, string $reference): bool
    {
        $amountStr = number_format($amount, 2, '.', '');

        $workspaceId = Yii::$app->workspace->getId();

        if ($type === self::TYPE_INCOME) {
            $query = Income::find()->where([
                'workspace_id' => $workspaceId,
                'income_category_id' => $categoryId,
                'entry_date' => $date,
                'amount' => $amountStr,
            ]);
        } else {
            $query = Expense::find()->where([
                'workspace_id' => $workspaceId,
                'expense_category_id' => $categoryId,
                'expense_date' => $date,
                'amount' => $amountStr,
            ]);
        }

        $reference = trim($reference);
        if ($reference !== '') {
            $query->andWhere(['reference' => $reference]);
        }

        return $query->exists();
    }

    /**
     * Normalizes a variety of date inputs to Y-m-d, or null if unparseable.
     *
     * @param string|null $value
     * @return string|null
     */
    private function normalizeDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d', 'd.m.Y', 'M d, Y', 'M j, Y', 'd M Y', 'd F Y'];
        foreach ($formats as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $value);
            if ($dt !== false && $dt->format($fmt) === $value) {
                return $dt->format('Y-m-d');
            }
        }

        // Last resort: let PHP try
        $ts = strtotime($value);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        return null;
    }

    /**
     * Parses an amount that may contain currency symbols/separators.
     *
     * @param string|null $value
     * @return float|null
     */
    private function normalizeAmount(?string $value): ?float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Strip everything except digits, separators, and sign
        $clean = preg_replace('/[^0-9.,\-]/', '', $value);
        if ($clean === '' || $clean === '-') {
            return null;
        }

        // If both separators present, assume comma = thousands
        if (strpos($clean, ',') !== false && strpos($clean, '.') !== false) {
            $clean = str_replace(',', '', $clean);
        } else {
            // Treat a lone comma as a decimal separator
            $clean = str_replace(',', '.', $clean);
        }

        if (!is_numeric($clean)) {
            return null;
        }

        return round((float) $clean, 2);
    }

    /**
     * Maps a free-text payment method to a valid Expense payment key.
     *
     * @param string|null $value
     * @return string
     */
    private function normalizePaymentMethod(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        $map = [
            'cash' => Expense::PAYMENT_CASH,
            'card' => Expense::PAYMENT_CARD,
            'credit card' => Expense::PAYMENT_CARD,
            'debit card' => Expense::PAYMENT_CARD,
            'bank' => Expense::PAYMENT_BANK,
            'bank transfer' => Expense::PAYMENT_BANK,
            'transfer' => Expense::PAYMENT_BANK,
        ];

        return $map[$value] ?? Expense::PAYMENT_CARD;
    }

    /**
     * Trims a value and converts blanks to null.
     *
     * @param string|null $value
     * @return string|null
     */
    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    /**
     * Returns true when every meaningful field of a row is blank.
     *
     * @param array $row
     * @return bool
     */
    private function isBlankRow(array $row): bool
    {
        foreach (['date', 'category', 'reference', 'description', 'amount'] as $f) {
            if (trim((string) ($row[$f] ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }
}
