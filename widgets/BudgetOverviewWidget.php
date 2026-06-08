<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\widgets;

use Yii;
use yii\base\Widget;
use app\models\Budget;
use app\services\BudgetService;

/**
 * BudgetOverviewWidget surfaces budgets that are nearing or over their limit.
 *
 * Rendered on the dashboard to give an at-a-glance view of spending risk, with
 * a compact progress bar per at-risk budget. Renders nothing when the user has
 * no active expense budgets.
 *
 * ## Usage
 *
 * ```php
 * <?= BudgetOverviewWidget::widget() ?>
 * <?= BudgetOverviewWidget::widget(['maxItems' => 5, 'onlyAlerting' => false]) ?>
 * ```
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class BudgetOverviewWidget extends Widget
{
    /** @var int|null User ID (defaults to current user) */
    public ?int $userId = null;

    /** @var int Maximum number of budgets to display */
    public int $maxItems = 5;

    /** @var bool When true, only show budgets at/over their alert threshold */
    public bool $onlyAlerting = true;

    /** @var string|null Custom container CSS class */
    public ?string $containerClass = null;

    /**
     * {@inheritdoc}
     */
    public function run(): string
    {
        if ($this->userId === null) {
            $this->userId = Yii::$app->workspace->getId();
        }

        $service = new BudgetService();

        // Only expense budgets carry overspend risk
        $all = array_filter(
            $service->getActiveBudgetsWithProgress($this->userId),
            static fn (Budget $b) => $b->isExpense()
        );
        $all = array_values($all);

        $hasBudgets = count($all) > 0;

        $budgets = $this->onlyAlerting
            ? array_values(array_filter($all, static fn (Budget $b) => $b->isAlerting()))
            : $all;

        $budgets = array_slice($budgets, 0, $this->maxItems);

        return $this->render('budget-overview', [
            'budgets' => $budgets,
            'hasBudgets' => $hasBudgets,
            'containerClass' => $this->containerClass,
        ]);
    }
}
