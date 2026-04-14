<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Grid Columns Configuration for Income Categories
 *
 * This configuration file defines the column structure for the CustomGridView
 * widget used in the income categories index page. It returns an array of
 * column definitions following Yii2's GridView column specification.
 *
 * ## Column Structure
 *
 * | Column      | Width | Type         | Description                        |
 * |-------------|-------|--------------|-----------------------------------|
 * | Name        | 20%   | DataColumn   | Category name with bold styling   |
 * | Description | 60%   | DataColumn   | Category description (nullable)   |
 * | Actions     | 20%   | ActionColumn | Dropdown menu (View/Edit/Delete)  |
 *
 * ## Action Buttons
 *
 * The Actions column provides a Bootstrap dropdown menu with:
 * - **View**: Opens detail modal (read-only)
 * - **Edit**: Opens update form in modal
 * - **Delete**: Shows confirmation dialog, then deletes
 *
 * ## Data Attributes for JavaScript
 *
 * Each action link includes data attributes for JavaScript handlers:
 * - `data-pjax`: Prevents PJAX from handling the click
 * - `data-title`: Modal/dialog title
 * - `data-url`: AJAX endpoint URL
 * - `data-target`: Target modal selector
 * - `data-message`: Confirmation message (for delete)
 * - `data-container`: PJAX container to refresh after action
 *
 * ## Usage
 *
 * This file is included in the GridView widget configuration:
 *
 * ```php
 * <?= CustomGridView::widget([
 *     'dataProvider' => $dataProvider,
 *     'columns' => require __DIR__ . '/_columns.php',
 * ]) ?>
 * ```
 *
 * ## Customization
 *
 * To add new columns, append to the returned array:
 *
 * ```php
 * [
 *     'attribute' => 'created_at',
 *     'format' => 'datetime',
 *     'label' => Yii::t('app', 'Created'),
 * ],
 * ```
 *
 * @return array Column configuration array for GridView widget
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 * @see views/income-category/index.php Parent view using this configuration
 * @see \yii\grid\GridView::$columns
 * @see \yii\grid\DataColumn
 * @see \yii\grid\ActionColumn
 */

use yii\helpers\Html;
use yii\helpers\Url;

return [

    // ============================================================== //
    // Name Column
    // ============================================================== //

    /**
     * Category Name Column
     *
     * Displays the income category name with medium font weight styling.
     * This is the primary identifier for each category in the grid.
     *
     * @see \app\models\IncomeCategory::$name
     */
    [
        'attribute' => 'name',
        'label' => Yii::t('app', 'Category Name'),
        'headerOptions' => [
            'style' => 'width: 20%;',
        ],
        'contentOptions' => [
            'class' => 'fw-medium',
        ],
    ],

    // ============================================================== //
    // Description Column
    // ============================================================== //

    /**
     * Category Description Column
     *
     * Displays the optional category description with text wrapping enabled.
     * Shows a muted placeholder text when description is empty/null.
     *
     * Features:
     * - Uses 'ntext' format to preserve line breaks
     * - White-space normal allows text to wrap within cell
     * - Fallback to italicized "No description" when empty
     *
     * @see \app\models\IncomeCategory::$description
     */
    [
        'attribute' => 'description',
        'headerOptions' => [
            'style' => 'width: 60%;',
        ],
        'contentOptions' => [
            'style' => 'white-space: normal;',
        ],
        'format' => 'ntext',
        'value' => function ($model) {
            /** @var \app\models\IncomeCategory $model */
            return $model->description ?: Html::tag('span', Yii::t('app', 'No description'), [
                'class' => 'text-muted fst-italic',
            ]);
        },
    ],

    // ============================================================== //
    // Actions Column
    // ============================================================== //

    /**
     * Actions Dropdown Column
     *
     * Renders a Bootstrap dropdown menu with View, Edit, and Delete actions.
     * Uses custom button template instead of default icon buttons.
     *
     * Action Configuration:
     * - view: Opens modal with category details (btn-modal class)
     * - update: Opens modal with edit form (btn-modal class)
     * - delete: Shows confirmation, refreshes PJAX on success (nemDeleteLink class)
     *
     * @see \yii\grid\ActionColumn
     */
    [
        'class' => 'yii\grid\ActionColumn',
        'template' => '{dropdown}',
        'header' => Yii::t('app', 'Action'),
        'headerOptions' => [
            'class' => 'text-center',
            'style' => 'width: 20%;',
        ],
        'buttons' => [
            /**
             * Dropdown Button Renderer
             *
             * Generates a Bootstrap 5 dropdown menu containing all row actions.
             * Each action is configured with appropriate data attributes for
             * JavaScript event handling.
             *
             * @param string $url The URL generated by ActionColumn (not used here)
             * @param \app\models\IncomeCategory $model The current row model
             * @param mixed $key The key associated with the data model
             * @return string HTML markup for the dropdown menu
             */
            'dropdown' => function ($url, $model, $key) {

                /**
                 * @var string $pjaxContainerId PJAX container to refresh after delete
                 */
                $pjaxContainerId = 'income-category-pjax';

                /**
                 * Action Configurations
                 *
                 * Each action defines:
                 * - label: Display text with icon
                 * - class: CSS classes for styling and JS binding
                 * - url: Target endpoint URL
                 * - target: Modal selector to load content into
                 * - message: Confirmation message (delete only)
                 * - container: PJAX container ID for refresh (delete only)
                 */
                $actions = [
                    // View Action - Opens read-only detail modal
                    'view' => [
                        'label' => '<i class="ri-eye-fill me-2"></i>' . Yii::t('app', 'View'),
                        'class' => 'btn-modal',
                        'url' => Url::to(['/income-category/view', 'id' => $model->id]),
                        'target' => '#nemModal',
                        'message' => '',
                        'container' => '',
                    ],
                    // Update Action - Opens edit form modal
                    'update' => [
                        'label' => '<i class="ri-pencil-fill me-2"></i>' . Yii::t('app', 'Edit'),
                        'class' => 'btn-modal',
                        'url' => Url::to(['/income-category/update', 'id' => $model->id]),
                        'target' => '#nemModal',
                        'message' => '',
                        'container' => '',
                    ],
                    // Delete Action - Shows confirmation, then deletes
                    'delete' => [
                        'label' => '<i class="ri-delete-bin-fill me-2"></i>' . Yii::t('app', 'Delete'),
                        'class' => 'nemDeleteLink text-danger',
                        'url' => Url::to(['/income-category/delete', 'id' => $model->id]),
                        'target' => '#deleteModal',
                        'message' => Yii::t('app', 'Are you sure you want to delete this category?'),
                        'container' => $pjaxContainerId,
                    ],
                ];

                /**
                 * Build Dropdown Menu Items
                 *
                 * Iterates through actions and creates list items.
                 * Adds a divider before the delete action for visual separation.
                 */
                $items = [];
                foreach ($actions as $action => $config) {
                    $link = Html::a($config['label'], 'javascript:void(0);', [
                        'class' => 'dropdown-item ' . $config['class'],
                        'data-pjax' => 0,
                        'data-title' => strip_tags($config['label']),
                        'data-message' => $config['message'],
                        'data-url' => $config['url'],
                        'data-target' => $config['target'],
                        'data-container' => $config['container'],
                    ]);

                    // Add divider before delete action
                    if ($action === 'delete') {
                        $items[] = '<li><hr class="dropdown-divider"></li>';
                    }
                    $items[] = '<li>' . $link . '</li>';
                }

                /**
                 * Return Complete Dropdown HTML
                 *
                 * Bootstrap 5 dropdown structure with:
                 * - Soft secondary button style
                 * - Three-dot vertical icon
                 * - End-aligned menu
                 */
                return '
                    <div class="dropdown text-center">
                        <a class="btn btn-soft-secondary btn-sm dropdown-toggle"
                           href="javascript:void(0);"
                           role="button"
                           data-bs-toggle="dropdown"
                           aria-expanded="false"
                           title="' . Yii::t('app', 'Actions') . '">
                            <i class="ri-more-fill"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">' .
                    implode("\n", $items) .
                    '</ul>
                    </div>';
            },
        ],
    ],
];
