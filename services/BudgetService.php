<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\services;

use Yii;
use app\models\Budget;
use app\models\Expense;
use app\models\Income;

/**
 * BudgetService centralizes budget progress aggregation and alerting.
 *
 * Responsibilities:
 * - Summaries and progress lists for the budgets page and dashboard widget.
 * - Evaluating a freshly saved transaction against matching budgets to decide
 *   whether an in-app toast and/or an email alert should be raised.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class BudgetService
{
    /**
     * Returns all active budgets for a user with their progress computed,
     * ordered by how close they are to (or over) their limit.
     *
     * @param int $userId
     * @return Budget[]
     */
    public function getActiveBudgetsWithProgress(int $userId): array
    {
        $budgets = Budget::find()
            ->where(['workspace_id' => $userId, 'status' => Budget::STATUS_ACTIVE])
            ->all();

        usort($budgets, static function (Budget $a, Budget $b) {
            return $b->getPercentage() <=> $a->getPercentage();
        });

        return $budgets;
    }

    /**
     * Returns active budgets that have reached or exceeded their alert
     * threshold (expense budgets only - those represent risk).
     *
     * @param int $userId
     * @param int $limit Maximum number to return (0 = no limit)
     * @return Budget[]
     */
    public function getAlertingBudgets(int $userId, int $limit = 0): array
    {
        $alerting = array_filter(
            $this->getActiveBudgetsWithProgress($userId),
            static function (Budget $b) {
                return $b->isExpense() && $b->isAlerting();
            }
        );

        $alerting = array_values($alerting);

        return $limit > 0 ? array_slice($alerting, 0, $limit) : $alerting;
    }

    /**
     * Builds summary statistics for the budgets page header.
     *
     * @param int $userId
     * @return array{total:int, active:int, over:int, warning:int, totalBudget:float, totalSpent:float}
     */
    public function getSummary(int $userId): array
    {
        $budgets = $this->getActiveBudgetsWithProgress($userId);

        $over = 0;
        $warning = 0;
        $totalBudget = 0.0;
        $totalSpent = 0.0;

        foreach ($budgets as $budget) {
            if ($budget->isExpense()) {
                $totalBudget += (float) $budget->amount;
                $totalSpent += $budget->getSpentAmount();

                $level = $budget->getStatusLevel();
                if ($level === Budget::LEVEL_OVER) {
                    $over++;
                } elseif ($level === Budget::LEVEL_WARNING) {
                    $warning++;
                }
            }
        }

        return [
            'total' => Budget::find()->where(['workspace_id' => $userId])->count(),
            'active' => count($budgets),
            'over' => $over,
            'warning' => $warning,
            'totalBudget' => $totalBudget,
            'totalSpent' => $totalSpent,
        ];
    }

    /**
     * Evaluates the impact of a saved expense against the user's expense
     * budgets and raises alerts when a budget *crosses* into a more severe
     * level because of this transaction.
     *
     * Returns an alert envelope suitable for attaching to an AJAX response, or
     * null when nothing crossed a threshold:
     * ```php
     * ['level' => 'warning'|'error', 'message' => '...']
     * ```
     *
     * @param Expense $expense The freshly saved expense
     * @return array|null
     */
    public function evaluateExpense(Expense $expense): ?array
    {
        $amountAdded = (float) $expense->amount;
        if ($amountAdded <= 0) {
            return null;
        }

        $budgets = Budget::find()
            ->where([
                'workspace_id' => $expense->workspace_id,
                'category_type' => Budget::TYPE_EXPENSE,
                'status' => Budget::STATUS_ACTIVE,
            ])
            ->all();

        $alert = null;
        $alertSeverity = 0; // 1 = warning, 2 = over

        foreach ($budgets as $budget) {
            // Budget must cover this expense's category (incl. descendants) and date
            if (!in_array((int) $expense->expense_category_id, $budget->getCategoryIds(), true)) {
                continue;
            }
            if (!$budget->coversDate($expense->expense_date)) {
                continue;
            }

            $spentNow = $budget->getSpentAmount(true);
            $spentBefore = max(0.0, $spentNow - $amountAdded);

            $levelNow = $this->levelForAmount($budget, $spentNow);
            $levelBefore = $this->levelForAmount($budget, $spentBefore);

            // Only act when this expense pushed the budget into a worse level
            if ($this->severity($levelNow) <= $this->severity($levelBefore)) {
                continue;
            }

            // Send email alert if enabled
            if ($budget->email_alerts) {
                $this->sendAlertEmail($budget, $spentNow, $levelNow);
            }

            // Keep the most severe alert for the toast
            if ($this->severity($levelNow) > $alertSeverity) {
                $alertSeverity = $this->severity($levelNow);
                $alert = $this->buildToast($budget, $levelNow);
            }
        }

        return $alert;
    }

    /**
     * Determines the alert level for a hypothetical spent amount on a budget.
     *
     * @param Budget $budget
     * @param float $spent
     * @return string One of Budget::LEVEL_*
     */
    private function levelForAmount(Budget $budget, float $spent): string
    {
        $amount = (float) $budget->amount;
        if ($amount <= 0) {
            return Budget::LEVEL_SAFE;
        }

        $pct = ($spent / $amount) * 100;

        if ($pct >= 100) {
            return Budget::LEVEL_OVER;
        }
        if ($pct >= $budget->alert_threshold) {
            return Budget::LEVEL_WARNING;
        }

        return Budget::LEVEL_SAFE;
    }

    /**
     * Maps a level to a numeric severity for comparison.
     *
     * @param string $level
     * @return int
     */
    private function severity(string $level): int
    {
        return [
            Budget::LEVEL_SAFE => 0,
            Budget::LEVEL_WARNING => 1,
            Budget::LEVEL_OVER => 2,
        ][$level] ?? 0;
    }

    /**
     * Builds the toast envelope for a crossed budget.
     *
     * @param Budget $budget
     * @param string $level
     * @return array
     */
    private function buildToast(Budget $budget, string $level): array
    {
        $category = $budget->getCategoryName();

        if ($level === Budget::LEVEL_OVER) {
            return [
                'level' => 'error',
                'message' => Yii::t('app', 'Budget exceeded for "{category}" ({spent} of {amount}).', [
                    'category' => $category,
                    'spent' => Yii::$app->currency->format($budget->getSpentAmount()),
                    'amount' => $budget->getFormattedAmount(),
                ]),
            ];
        }

        return [
            'level' => 'warning',
            'message' => Yii::t('app', 'Budget alert: "{category}" is at {percent}% of its limit.', [
                'category' => $category,
                'percent' => (int) round($budget->getPercentage()),
            ]),
        ];
    }

    /**
     * Sends a budget alert email to the budget owner. Failures are logged and
     * never interrupt the calling request.
     *
     * @param Budget $budget
     * @param float $spent
     * @param string $level
     * @return bool Whether the message was handed to the mailer successfully
     */
    public function sendAlertEmail(Budget $budget, float $spent, string $level): bool
    {
        $user = $budget->user;
        if ($user === null || empty($user->email)) {
            return false;
        }

        $period = $budget->getCurrentPeriod();

        try {
            $subject = $level === Budget::LEVEL_OVER
                ? Yii::t('app', 'Budget exceeded: {category}', ['category' => $budget->getCategoryName()])
                : Yii::t('app', 'Budget alert: {category}', ['category' => $budget->getCategoryName()]);

            return Yii::$app->mailer
                ->compose(
                    ['html' => 'budgetAlert-html', 'text' => 'budgetAlert-text'],
                    [
                        'user' => $user,
                        'budget' => $budget,
                        'spent' => $spent,
                        'level' => $level,
                        'period' => $period,
                    ]
                )
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                ->setTo($user->email)
                ->setSubject($subject)
                ->send();
        } catch (\Throwable $e) {
            Yii::error('Failed to send budget alert email: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }
}
