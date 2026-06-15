<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Category Breakdown PDF report (full expense + income category lists).
 *
 * @var yii\web\View $this
 * @var array $period
 * @var array $meta
 * @var array $summary
 * @var array $expenseRows
 * @var array $incomeRows
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

echo $this->render('_header', [
    'title' => Yii::t('app', 'Category Breakdown'),
    'period' => $period,
    'meta' => $meta,
]);

echo $this->render('_categoryTable', [
    'rows' => $expenseRows,
    'heading' => Yii::t('app', 'Expenses by Category'),
    'color' => '#dc3545',
    'limitNote' => null,
]);

echo $this->render('_categoryTable', [
    'rows' => $incomeRows,
    'heading' => Yii::t('app', 'Income by Category'),
    'color' => '#16a34a',
    'limitNote' => null,
]);
