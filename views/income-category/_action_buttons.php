<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Action Buttons Partial for Income Categories
 *
 * This partial view renders the header action buttons for the income categories
 * index page. It provides quick access to common operations like creating new
 * categories, exporting data, and bulk deletion.
 *
 * ## Buttons Overview
 *
 * | Button       | Class              | Action                              |
 * |--------------|--------------------|-------------------------------------|
 * | Add New      | `btn-success`      | Opens create form in modal          |
 * | Export       | `btn-info`         | Triggers data export (CSV/Excel)    |
 * | Bulk Delete  | `btn-soft-danger`  | Deletes selected rows               |
 *
 * ## JavaScript Integration
 *
 * This partial relies on the following JavaScript handlers:
 * - `.btn-modal`: Loads content via AJAX into target modal
 * - `#export-btn`: Triggers export functionality
 * - `deleteMultiple()`: Handles bulk deletion of selected rows
 *
 * ## Data Attributes
 *
 * The "Add New" button uses data attributes for modal integration:
 * - `data-title`: Modal header title
 * - `data-url`: AJAX endpoint to load form content
 * - `data-target`: CSS selector of the target modal
 *
 * ## Usage
 *
 * ```php
 * <?= $this->render('_action_buttons', [
 *     'modalTargetId' => '#nemModal',
 * ]) ?>
 * ```
 *
 * @var yii\web\View $this The view object that renders this template
 * @var string $modalTargetId CSS selector for the target modal (e.g., '#nemModal')
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 * @see views/income-category/index.php Parent view that renders this partial
 * @see \app\controllers\IncomeCategoryController::actionCreate()
 * @see \app\controllers\IncomeCategoryController::actionExport()
 */

use yii\helpers\Html;
use yii\helpers\Url;
?>

<!-- ============================================================== -->
<!-- Header Action Buttons Group                                    -->
<!-- Provides quick actions: Add New, Export, Bulk Delete           -->
<!-- ============================================================== -->
<div class="d-flex gap-1 flex-wrap">

    <?php
    /**
     * Add New Category Button
     *
     * Opens a modal dialog with the create form.
     * Uses the `.btn-modal` class which is handled by JavaScript
     * to load the form content via AJAX.
     *
     * Data attributes:
     * - data-title: Sets the modal header title
     * - data-url: AJAX endpoint for loading the create form
     * - data-target: Target modal element selector
     */
    ?>
    <?= Html::button(
        '<i class="ri-add-line align-bottom me-1"></i>' . Yii::t('app', 'Add New'),
        [
            'class' => 'btn btn-success add-btn btn-modal',
            'title' => Yii::t('app', 'Create new income category'),
            'data' => [
                'title' => Yii::t('app', 'Add Income Category'),
                'url' => Url::to(['/income-category/create']),
                'target' => $modalTargetId,
            ],
        ]
    ) ?>

    <?php
    /**
     * Export Button
     *
     * Triggers the export functionality to download category data.
     * The actual export logic is handled by JavaScript bound to `#export-btn`.
     *
     * Supported formats (implementation dependent):
     * - CSV (Comma-Separated Values)
     * - Excel (XLSX)
     * - PDF
     *
     * @todo Implement export action in controller
     */
    ?>
    <?= Html::button(
        '<i class="ri-file-download-line align-bottom me-1"></i>' . Yii::t('app', 'Export'),
        [
            'class' => 'btn btn-info',
            'id' => 'export-btn',
            'title' => Yii::t('app', 'Export categories to file'),
        ]
    ) ?>

    <?php
    /**
     * Bulk Delete Button
     *
     * Deletes multiple selected categories at once.
     * Requires rows to be selected via checkboxes in the grid.
     *
     * The `deleteMultiple()` JavaScript function:
     * 1. Collects IDs of selected rows
     * 2. Shows confirmation dialog
     * 3. Sends DELETE request to server
     * 4. Refreshes PJAX container on success
     *
     * @see assets/js/custom.js deleteMultiple() function
     */
    ?>
    <?= Html::button(
        '<i class="ri-delete-bin-2-line"></i>',
        [
            'class' => 'btn btn-soft-danger',
            'id' => 'bulk-delete-btn',
            'title' => Yii::t('app', 'Delete selected categories'),
            'onclick' => 'deleteMultiple()',
        ]
    ) ?>

</div>
<!-- End Action Buttons -->
