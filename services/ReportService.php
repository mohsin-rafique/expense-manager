<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\services;

use Yii;
use yii\db\Query;
use app\models\Budget;

/**
 * ReportService aggregates the financial figures used by the PDF reports.
 *
 * It resolves the reporting period (month / fiscal year / custom range /
 * lifetime) and produces summary totals, category breakdowns, period trends,
 * and budget status - all scoped to a single user.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class ReportService
{
    public const PERIOD_MONTH = 'month';
    public const PERIOD_FISCAL = 'fiscal';
    public const PERIOD_CUSTOM = 'custom';
    public const PERIOD_LIFETIME = 'lifetime';

    /**
     * Resolves a reporting period into start/end dates and a human label.
     *
     * @param string $type self::PERIOD_*
     * @param array $params ['year' => int, 'month' => int, 'start' => 'Y-m-d', 'end' => 'Y-m-d']
     * @param int $userId Used to derive the earliest date for lifetime reports
     * @return array{start:string, end:string, label:string, type:string}
     */
    public function resolvePeriod(string $type, array $params, int $userId): array
    {
        switch ($type) {
            case self::PERIOD_FISCAL:
                $service = new FiscalYearService();
                $label = $params['fy'] ?? null;
                $fy = ($label !== null ? $service->getFiscalYearByLabel($label) : null) ?? $service->getCurrentFiscalYear();
                return ['start' => $fy['startDate'], 'end' => $fy['endDate'], 'label' => $fy['label'], 'type' => $type];

            case self::PERIOD_CUSTOM:
                $start = $this->validDate($params['start'] ?? null) ?? date('Y-m-01');
                $end = $this->validDate($params['end'] ?? null) ?? date('Y-m-t');
                if ($end < $start) {
                    [$start, $end] = [$end, $start];
                }
                $label = Yii::$app->formatter->asDate($start) . ' - ' . Yii::$app->formatter->asDate($end);
                return ['start' => $start, 'end' => $end, 'label' => $label, 'type' => $type];

            case self::PERIOD_LIFETIME:
                $start = $this->earliestDate($userId) ?? date('Y-01-01');
                $end = date('Y-m-d');
                return ['start' => $start, 'end' => $end, 'label' => Yii::t('app', 'All Time'), 'type' => $type];

            case self::PERIOD_MONTH:
            default:
                $year = (int) ($params['year'] ?? date('Y'));
                $month = (int) ($params['month'] ?? date('n'));
                $month = max(1, min(12, $month));
                $start = sprintf('%04d-%02d-01', $year, $month);
                $end = date('Y-m-t', strtotime($start));
                $label = Yii::$app->formatter->asDate($start, 'MMMM yyyy');
                return ['start' => $start, 'end' => $end, 'label' => $label, 'type' => self::PERIOD_MONTH];
        }
    }

    /**
     * Income/expense totals and derived metrics for a period.
     *
     * @return array{income:float, expense:float, net:float, savingsRate:float,
     *   incomeCount:int, expenseCount:int, avgExpense:float, avgIncome:float}
     */
    public function summary(int $userId, string $start, string $end): array
    {
        $income = $this->sum('{{%incomes}}', 'entry_date', $userId, $start, $end);
        $expense = $this->sum('{{%expenses}}', 'expense_date', $userId, $start, $end);
        $incomeCount = $this->count('{{%incomes}}', 'entry_date', $userId, $start, $end);
        $expenseCount = $this->count('{{%expenses}}', 'expense_date', $userId, $start, $end);

        $net = $income - $expense;
        $savingsRate = $income > 0 ? round(($net / $income) * 100, 1) : 0.0;

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => $net,
            'savingsRate' => $savingsRate,
            'incomeCount' => $incomeCount,
            'expenseCount' => $expenseCount,
            'avgExpense' => $expenseCount > 0 ? round($expense / $expenseCount, 2) : 0.0,
            'avgIncome' => $incomeCount > 0 ? round($income / $incomeCount, 2) : 0.0,
        ];
    }

    /**
     * Category breakdown for a period, rolling child categories up to their
     * top-level parent (expenses) and sorted by amount descending.
     *
     * @param string $kind 'expense' | 'income'
     * @return array<int,array{name:string, total:float, percent:float}>
     */
    public function categoryBreakdown(int $userId, string $start, string $end, string $kind): array
    {
        if ($kind === 'income') {
            $rows = (new Query())
                ->select(['name' => 'c.name', 'total' => 'COALESCE(SUM(t.amount), 0)'])
                ->from('{{%income_categories}} c')
                ->leftJoin('{{%incomes}} t', [
                    'and',
                    't.income_category_id = c.id',
                    ['t.workspace_id' => $userId],
                    ['between', 't.entry_date', $start, $end],
                ])
                ->where(['c.workspace_id' => $userId])
                ->groupBy(['c.id', 'c.name'])
                ->having(['>', 'COALESCE(SUM(t.amount), 0)', 0])
                ->orderBy(['total' => SORT_DESC])
                ->all();
        } else {
            // Roll children up to their parent (or themselves if root)
            $rows = (new Query())
                ->select([
                    'name' => 'COALESCE(parent.name, c.name)',
                    'total' => 'COALESCE(SUM(t.amount), 0)',
                ])
                ->from('{{%expense_categories}} c')
                ->leftJoin('{{%expense_categories}} parent', 'parent.id = c.parent_id')
                ->leftJoin('{{%expenses}} t', [
                    'and',
                    't.expense_category_id = c.id',
                    ['t.workspace_id' => $userId],
                    ['between', 't.expense_date', $start, $end],
                ])
                ->where(['c.workspace_id' => $userId])
                ->groupBy(['COALESCE(parent.id, c.id)', 'COALESCE(parent.name, c.name)'])
                ->having(['>', 'COALESCE(SUM(t.amount), 0)', 0])
                ->orderBy(['total' => SORT_DESC])
                ->all();
        }

        $grand = array_sum(array_map(static fn ($r) => (float) $r['total'], $rows));

        return array_map(static function ($r) use ($grand) {
            $total = (float) $r['total'];
            return [
                'name' => $r['name'],
                'total' => $total,
                'percent' => $grand > 0 ? round(($total / $grand) * 100, 1) : 0.0,
            ];
        }, $rows);
    }

    /**
     * Income vs expense broken down by period bucket (month, or year when the
     * span is large) within the date range.
     *
     * @return array<int,array{label:string, income:float, expense:float, net:float}>
     */
    public function trend(int $userId, string $start, string $end): array
    {
        $startTs = strtotime($start);
        $endTs = strtotime($end);
        $months = (int) ((date('Y', $endTs) - date('Y', $startTs)) * 12 + (date('n', $endTs) - date('n', $startTs))) + 1;

        $byYear = $months > 36;

        $incomeRows = $this->bucketSums('{{%incomes}}', 'entry_date', $userId, $start, $end, $byYear);
        $expenseRows = $this->bucketSums('{{%expenses}}', 'expense_date', $userId, $start, $end, $byYear);

        $buckets = array_unique(array_merge(array_keys($incomeRows), array_keys($expenseRows)));
        sort($buckets);

        $result = [];
        foreach ($buckets as $bucket) {
            $income = $incomeRows[$bucket] ?? 0.0;
            $expense = $expenseRows[$bucket] ?? 0.0;
            $result[] = [
                'label' => $byYear ? $bucket : Yii::$app->formatter->asDate($bucket . '-01', 'MMM yyyy'),
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
            ];
        }

        return $result;
    }

    /**
     * Active expense budgets with current-period progress (for the budget report).
     *
     * @return array<int,array{category:string, period:string, amount:float, spent:float, percent:float, status:string}>
     */
    public function budgetStatus(int $userId): array
    {
        $budgets = (new BudgetService())->getActiveBudgetsWithProgress($userId);

        $out = [];
        foreach ($budgets as $budget) {
            if (!$budget->isExpense()) {
                continue;
            }
            $out[] = [
                'category' => $budget->getCategoryName(),
                'period' => $budget->getPeriodTypeLabel() . ' · ' . $budget->getPeriodLabel(),
                'amount' => (float) $budget->amount,
                'spent' => $budget->getSpentAmount(),
                'percent' => $budget->getPercentage(),
                'status' => $budget->getStatusLabel(),
                'level' => $budget->getStatusLevel(),
            ];
        }

        return $out;
    }

    // ─── Internal helpers ────────────────────────────────────────────

    /**
     * @return float
     */
    private function sum(string $table, string $dateCol, int $userId, string $start, string $end): float
    {
        return (float) ((new Query())
            ->from($table)
            ->where(['workspace_id' => $userId])
            ->andWhere(['between', $dateCol, $start, $end])
            ->sum('amount') ?? 0);
    }

    /**
     * @return int
     */
    private function count(string $table, string $dateCol, int $userId, string $start, string $end): int
    {
        return (int) (new Query())
            ->from($table)
            ->where(['workspace_id' => $userId])
            ->andWhere(['between', $dateCol, $start, $end])
            ->count();
    }

    /**
     * Returns [bucketKey => total] grouped by month (YYYY-MM) or year (YYYY).
     *
     * @return array<string,float>
     */
    private function bucketSums(string $table, string $dateCol, int $userId, string $start, string $end, bool $byYear): array
    {
        $format = $byYear ? '%Y' : '%Y-%m';

        $rows = (new Query())
            ->select(['bucket' => "DATE_FORMAT($dateCol, '$format')", 'total' => 'SUM(amount)'])
            ->from($table)
            ->where(['workspace_id' => $userId])
            ->andWhere(['between', $dateCol, $start, $end])
            ->groupBy(['bucket'])
            ->all();

        $map = [];
        foreach ($rows as $r) {
            $map[$r['bucket']] = (float) $r['total'];
        }

        return $map;
    }

    /**
     * Earliest transaction date for a user across incomes and expenses.
     *
     * @return string|null Y-m-d or null when the user has no data
     */
    private function earliestDate(int $userId): ?string
    {
        $minExpense = (new Query())->from('{{%expenses}}')->where(['workspace_id' => $userId])->min('expense_date');
        $minIncome = (new Query())->from('{{%incomes}}')->where(['workspace_id' => $userId])->min('entry_date');

        $dates = array_filter([$minExpense, $minIncome]);
        return empty($dates) ? null : min($dates);
    }

    /**
     * Validates a Y-m-d date string.
     *
     * @return string|null
     */
    private function validDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        $dt = \DateTime::createFromFormat('Y-m-d', $value);
        return ($dt && $dt->format('Y-m-d') === $value) ? $value : null;
    }
}
