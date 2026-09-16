<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\services;

use app\models\ExpenseCategory;
use Yii;
use yii\db\Query;

/**
 * FbrReconciliationService compares the Personal Expenses declared on an FBR
 * income tax return against the workspace's own expenses for the same fiscal
 * year, grouped by FBR tax category.
 *
 * It is the return-side counterpart of the Fiscal Year Expense Summary by FBR
 * Tax Category widget: the widget shows what was spent, this shows whether what
 * was filed agrees with it.
 *
 * ## How the two sides line up
 *
 * The app's FBR categories are finer-grained than the return's wealth statement
 * lines, so several of them roll up into one declared figure. The grouping in
 * {@see categoryMap()} follows the IRIS wealth statement (form 116(2)): bank
 * charges, federal excise and the various withholding taxes are all "Rates /
 * Taxes / Charge / Cess" (7052), and donation, zakat, annuity, profit on debt
 * and life insurance premium share a single line (7076).
 *
 * Two app categories are deliberately left out of the comparison: car and plot
 * installments are asset purchases or liability repayments, and the return
 * accounts for them in the wealth statement's assets and liabilities rather than
 * in personal expenses. They are reported separately so the omission is visible
 * instead of silent.
 *
 * Nothing is written to the database - the return is only compared.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.3.0
 */
class FbrReconciliationService
{
    /** Row statuses. */
    public const STATUS_MATCH = 'match';
    public const STATUS_OVER = 'over';
    public const STATUS_UNDER = 'under';
    public const STATUS_NOT_DECLARED = 'not_declared';
    public const STATUS_NO_EXPENSES = 'no_expenses';

    /**
     * Rupees by which the two sides may differ and still count as a match. IRIS
     * prints the wealth statement in whole rupees, so a category holding a few
     * hundred expenses is routinely off by the sum of their rounded fractions.
     */
    public const TOLERANCE = 1.0;

    /** @var string Expenses table name */
    public $expenseTable = '{{%expenses}}';

    /**
     * Maps each wealth statement personal-expense code to the app FBR
     * categories that roll up into it, in the order IRIS prints them.
     *
     * Codes the current return does not carry (Rent, Asset Insurance / Security,
     * Club, Functions / Gatherings) are listed so expenses recorded against them
     * still have somewhere to land; a code absent from the return simply shows a
     * declared amount of zero.
     *
     * @return array<string, array{label: string, categories: array<int, string>}>
     */
    public static function categoryMap(): array
    {
        return [
            '7051' => [
                'label' => Yii::t('app', 'Rent'),
                'categories' => [],
            ],
            '7052' => [
                'label' => Yii::t('app', 'Rates / Taxes / Charge / Cess'),
                'categories' => [
                    'RENT_RATE',
                    'SERVICE_CHARGE',
                    'SMS_ALERT_FEE',
                    'CARD_ANNUAL_FEE',
                    'OTHER_FEES',
                    'BANK_INDIRECT',
                    'FED_SALES_TAX',
                    'SALES_TAX_WHT',
                    'ADV_INCOME_TAX',
                    'ADV_TAX_CARD_REMIT',
                ],
            ],
            '7055' => [
                'label' => Yii::t('app', 'Vehicle Running / Maintenance'),
                'categories' => ['VEHICLE_MAINT'],
            ],
            '7056' => [
                'label' => Yii::t('app', 'Travelling'),
                'categories' => ['TRAVELLING'],
            ],
            '7058' => [
                'label' => Yii::t('app', 'Electricity'),
                'categories' => ['ELECTRICITY'],
            ],
            '7059' => [
                'label' => Yii::t('app', 'Water'),
                'categories' => ['WATER'],
            ],
            '7060' => [
                'label' => Yii::t('app', 'Gas'),
                'categories' => ['GAS'],
            ],
            '7061' => [
                'label' => Yii::t('app', 'Telephone'),
                'categories' => ['TELEPHONE'],
            ],
            '7062' => [
                'label' => Yii::t('app', 'Asset Insurance / Security'),
                'categories' => ['ASSET_INS'],
            ],
            '7070' => [
                'label' => Yii::t('app', 'Medical'),
                'categories' => ['MEDICAL'],
            ],
            '7071' => [
                'label' => Yii::t('app', 'Educational'),
                'categories' => ['EDUCATIONAL'],
            ],
            '7073' => [
                'label' => Yii::t('app', 'Functions / Gatherings'),
                'categories' => ['FUNCTIONS'],
            ],
            '7076' => [
                'label' => Yii::t('app', 'Donation, Zakat, Annuity, Profit on Debt, Life Insurance Premium, etc.'),
                'categories' => ['DONATION', 'ZAKAT', 'ANNUITY', 'PROFIT_ON_DEBT', 'LIFE_INS_PREMIUM'],
            ],
            '7087' => [
                'label' => Yii::t('app', 'Other Personal / Household Expenses'),
                'categories' => ['OTHER_PERS'],
            ],
        ];
    }

    /**
     * App FBR categories that are not personal expenses on the return.
     *
     * Installments buy an asset or repay a liability, so the wealth statement
     * carries them under assets and liabilities. Including them in the
     * comparison would make every return look badly under-declared.
     *
     * @return array<int, string>
     */
    public static function excludedCategories(): array
    {
        return ['CAR_INSTALL', 'PLOT_INSTALL'];
    }

    /**
     * Sums a workspace's expenses per FBR tax category over a date range.
     *
     * Mirrors the query behind the Fiscal Year Expense Summary by FBR Tax
     * Category widget, so both screens report the same figures.
     *
     * @param int $workspaceId Owning workspace ID
     * @param string $startDate Range start (Y-m-d, inclusive)
     * @param string $endDate Range end (Y-m-d, inclusive)
     * @return array<string, float> fbr_category => total
     */
    public function expenseTotals(int $workspaceId, string $startDate, string $endDate): array
    {
        $rows = (new Query())
            ->select([
                'fbr_category',
                'total' => 'SUM(CAST(amount AS DECIMAL(14,2)))',
            ])
            ->from($this->expenseTable)
            ->where([
                'and',
                ['workspace_id' => $workspaceId],
                ['BETWEEN', 'expense_date', $startDate, $endDate],
                ['not', ['fbr_category' => null]],
                ['<>', 'fbr_category', ''],
            ])
            ->groupBy(['fbr_category'])
            ->all();

        $totals = [];
        foreach ($rows as $row) {
            $totals[(string) $row['fbr_category']] = (float) $row['total'];
        }

        return $totals;
    }

    /**
     * Compares the return's personal expense lines against recorded expenses.
     *
     * Every mapped wealth statement line that carries an amount on either side
     * produces a row; lines that are zero on both are dropped so the table shows
     * only what is actually in play. Lines the return carries under a code this
     * app does not map are kept with the wording FBR printed, flagged as having
     * no matching expenses.
     *
     * @param array $parsed Result of {@see FbrReturnParser::parse()}
     * @param array<string, float> $expenseTotals fbr_category => total
     * @return array{
     *     rows: array<int, array>,
     *     declaredTotal: float,
     *     recordedTotal: float,
     *     difference: float,
     *     returnedTotal: float|null,
     *     matchedCount: int,
     *     differingCount: int,
     *     excluded: array<int, array{code: string, label: string, amount: float}>,
     *     excludedTotal: float,
     *     unmapped: array<int, array{code: string, label: string, amount: float}>,
     *     unmappedTotal: float
     * }
     */
    public function reconcile(array $parsed, array $expenseTotals): array
    {
        $appLabels = ExpenseCategory::getFbrCategories();
        $returnLines = $parsed['lines'] ?? [];
        $rows = [];
        $declaredTotal = 0.0;
        $recordedTotal = 0.0;
        $matched = 0;
        $differing = 0;

        foreach (self::categoryMap() as $code => $meta) {
            $declared = (float) ($returnLines[$code]['amount'] ?? 0.0);
            $breakdown = [];
            $recorded = 0.0;

            foreach ($meta['categories'] as $catCode) {
                $amount = (float) ($expenseTotals[$catCode] ?? 0.0);
                if ($amount == 0.0) {
                    continue;
                }
                $breakdown[] = [
                    'code' => $catCode,
                    'label' => $appLabels[$catCode] ?? $catCode,
                    'amount' => $amount,
                ];
                $recorded += $amount;
            }

            // Nothing declared and nothing recorded: not worth a row.
            if (!isset($returnLines[$code]) && $recorded == 0.0) {
                continue;
            }

            $difference = round($recorded - $declared, 2);
            $status = $this->statusOf($declared, $recorded, $difference);

            if ($status === self::STATUS_MATCH) {
                $matched++;
            } else {
                $differing++;
            }

            $rows[] = [
                'code' => $code,
                // Prefer the return's own wording, so the row reads exactly as
                // it does on the filed document.
                'label' => $returnLines[$code]['label'] ?? $meta['label'],
                'declared' => $declared,
                'recorded' => $recorded,
                'difference' => $difference,
                'status' => $status,
                'breakdown' => $breakdown,
                'onReturn' => isset($returnLines[$code]),
            ];

            $declaredTotal += $declared;
            $recordedTotal += $recorded;
        }

        // Lines the return carries under a code this app does not know about.
        $unmapped = [];
        $unmappedTotal = 0.0;
        foreach ($returnLines as $code => $line) {
            if (isset(self::categoryMap()[$code])) {
                continue;
            }
            $unmapped[] = $line;
            $unmappedTotal += $line['amount'];
            $declaredTotal += $line['amount'];
        }

        // Installments and anything else outside the personal expenses block.
        $excluded = [];
        $excludedTotal = 0.0;
        foreach (self::excludedCategories() as $catCode) {
            $amount = (float) ($expenseTotals[$catCode] ?? 0.0);
            if ($amount == 0.0) {
                continue;
            }
            $excluded[] = [
                'code' => $catCode,
                'label' => $appLabels[$catCode] ?? $catCode,
                'amount' => $amount,
            ];
            $excludedTotal += $amount;
        }

        return [
            'rows' => $rows,
            'declaredTotal' => round($declaredTotal, 2),
            'recordedTotal' => round($recordedTotal, 2),
            'difference' => round($recordedTotal - $declaredTotal, 2),
            'returnedTotal' => $parsed['personalTotal'] ?? null,
            'matchedCount' => $matched,
            'differingCount' => $differing,
            'excluded' => $excluded,
            'excludedTotal' => round($excludedTotal, 2),
            'unmapped' => $unmapped,
            'unmappedTotal' => round($unmappedTotal, 2),
        ];
    }

    /**
     * Classifies one reconciled line.
     *
     * @param float $declared Amount on the return
     * @param float $recorded Amount recorded as expenses
     * @param float $difference recorded - declared
     * @return string One of the STATUS_* constants
     */
    private function statusOf(float $declared, float $recorded, float $difference): string
    {
        if (abs($difference) <= self::TOLERANCE) {
            return self::STATUS_MATCH;
        }
        if ($declared == 0.0) {
            return self::STATUS_NOT_DECLARED;
        }
        if ($recorded == 0.0) {
            return self::STATUS_NO_EXPENSES;
        }

        return $difference > 0 ? self::STATUS_OVER : self::STATUS_UNDER;
    }

    /**
     * Presentation metadata for each row status: a short label, an icon, a
     * Bootstrap tone, and what the status means for the filing.
     *
     * @return array<string, array{label: string, icon: string, tone: string, hint: string}>
     */
    public static function statusMeta(): array
    {
        return [
            self::STATUS_MATCH => [
                'label' => Yii::t('app', 'Matches'),
                'icon' => 'bi-check-circle',
                'tone' => 'success',
                'hint' => Yii::t('app', 'The declared figure agrees with your recorded expenses.'),
            ],
            self::STATUS_OVER => [
                'label' => Yii::t('app', 'Under-declared'),
                'icon' => 'bi-arrow-up-circle',
                'tone' => 'warning',
                'hint' => Yii::t('app', 'You recorded more than the return declares. Check whether the return needs revising.'),
            ],
            self::STATUS_UNDER => [
                'label' => Yii::t('app', 'Over-declared'),
                'icon' => 'bi-arrow-down-circle',
                'tone' => 'warning',
                'hint' => Yii::t('app', 'The return declares more than you recorded. Expenses may be missing from the app.'),
            ],
            self::STATUS_NOT_DECLARED => [
                'label' => Yii::t('app', 'Not on return'),
                'icon' => 'bi-exclamation-triangle',
                'tone' => 'danger',
                'hint' => Yii::t('app', 'You recorded expenses here but the return declares nothing against this line.'),
            ],
            self::STATUS_NO_EXPENSES => [
                'label' => Yii::t('app', 'No expenses'),
                'icon' => 'bi-question-circle',
                'tone' => 'danger',
                'hint' => Yii::t('app', 'The return declares an amount you have no expenses for.'),
            ],
        ];
    }
}
