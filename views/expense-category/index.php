<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Index View for Expense Categories
 *
 * Displays a hierarchical tree view of expense categories with drag-drop support,
 * inline actions, and statistics dashboard.
 * Uses global modals from _modals.php and centralized NEM JavaScript system.
 *
 * @var yii\web\View $this
 * @var app\models\ExpenseCategorySearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $treeData Tree structure for jsTree
 * @var array $stats Category statistics
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\Pjax;

$this->title = Yii::t('app', 'Expense Categories');

// Register jsTree CSS & JS (use CDN or local)
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.16/themes/default/style.min.css');
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.16/jstree.min.js', ['depends' => [\yii\web\JqueryAsset::class]]);
?>

<?php Pjax::begin([
    'id' => 'expense-categories-pjax',
    'timeout' => 10000,
    'enablePushState' => true,
    'clientOptions' => ['method' => 'GET'],
]); ?>

<div class="expense-categories-index">

    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">
                <?= Yii::t('app', 'Organize your expenses with hierarchical categories') ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?= Html::a(
                '<i class="bi bi-download me-1"></i>' . Yii::t('app', 'Export'),
                ['export'],
                [
                    'class' => 'btn btn-outline-secondary',
                    'data-pjax' => '0',
                ]
            ) ?>
            <?= Html::button(
                '<i class="bi bi-plus-lg me-1"></i>' . Yii::t('app', 'Add Category'),
                [
                    'class' => 'btn btn-danger btn-modal',
                    'data-url' => Url::to(['create']),
                    'data-title' => Yii::t('app', 'Add New Category'),
                    'data-icon' => '<i class="bi bi-folder-plus text-danger me-2"></i>',
                    'data-target' => '#nemModal',
                    'id' => 'btn-create-category',
                ]
            ) ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card stats-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bi bi-folder"></i>
                        </div>
                        <div>
                            <div class="text-muted small"><?= Yii::t('app', 'Total') ?></div>
                            <div class="h4 mb-0"><?= Yii::$app->formatter->asInteger($stats['total']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stats-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <div class="text-muted small"><?= Yii::t('app', 'Active') ?></div>
                            <div class="h4 mb-0"><?= Yii::$app->formatter->asInteger($stats['active']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stats-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                        <div>
                            <div class="text-muted small"><?= Yii::t('app', 'Root Categories') ?></div>
                            <div class="h4 mb-0"><?= Yii::$app->formatter->asInteger($stats['rootCount']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stats-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-layers"></i>
                        </div>
                        <div>
                            <div class="text-muted small"><?= Yii::t('app', 'Max Depth') ?></div>
                            <div class="h4 mb-0"><?= Yii::$app->formatter->asInteger($stats['maxDepth']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="card shadow-sm">
        <div class="card-header border-0 bg-transparent">
            <div class="row align-items-center g-3">
                <div class="col-md-6">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-diagram-3 me-2 text-danger"></i>
                        <?= Yii::t('app', 'Category Hierarchy') ?>
                    </h5>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <!-- Search Box -->
                        <div class="input-group" style="max-width: 250px;">
                            <span class="input-group-text bg-transparent border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text"
                                class="form-control border-start-0"
                                id="tree-search"
                                placeholder="<?= Yii::t('app', 'Search categories...') ?>">
                        </div>
                        <!-- View Toggle -->
                        <div class="btn-group view-toggle" role="group">
                            <button type="button" class="btn btn-outline-secondary active" data-view="tree" title="<?= Yii::t('app', 'Tree View') ?>">
                                <i class="bi bi-diagram-3"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-view="list" title="<?= Yii::t('app', 'List View') ?>">
                                <i class="bi bi-list-ul"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Tree Toolbar -->
            <div class="tree-toolbar">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-expand-all">
                    <i class="bi bi-arrows-expand me-1"></i><?= Yii::t('app', 'Expand All') ?>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-collapse-all">
                    <i class="bi bi-arrows-collapse me-1"></i><?= Yii::t('app', 'Collapse All') ?>
                </button>
                <div class="vr d-none d-sm-block"></div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="show-inactive" checked>
                    <label class="form-check-label small" for="show-inactive">
                        <?= Yii::t('app', 'Show inactive') ?>
                    </label>
                </div>
            </div>

            <!-- Tree Container -->
            <div class="tree-container" id="category-tree">
                <?php if (empty($treeData)): ?>
                    <div class="empty-tree">
                        <div class="empty-tree-icon">
                            <i class="bi bi-folder-plus text-danger" style="font-size: 2rem;"></i>
                        </div>
                        <h5><?= Yii::t('app', 'No categories yet') ?></h5>
                        <p class="text-muted mb-4">
                            <?= Yii::t('app', 'Create your first expense category to start organizing your expenses.') ?>
                        </p>
                        <?= Html::button(
                            '<i class="bi bi-plus-lg me-1"></i>' . Yii::t('app', 'Create Category'),
                            [
                                'class' => 'btn btn-danger btn-modal',
                                'data-url' => Url::to(['create']),
                                'data-title' => Yii::t('app', 'Add New Category'),
                                'data-icon' => '<i class="bi bi-folder-plus text-danger me-2"></i>',
                                'data-target' => '#nemModal',
                            ]
                        ) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- List View Container (hidden by default) -->
            <div class="list-container d-none" id="category-list">
                <?= $this->render('_list', [
                    'dataProvider' => $dataProvider,
                    'searchModel' => $searchModel,
                ]) ?>
            </div>
        </div>
    </div>
</div>

<?php Pjax::end(); ?>

<?php
// Prepare JavaScript data
$treeDataJson = Json::encode($treeData);
$createUrl = Url::to(['create']);
$updateUrl = Url::to(['update', 'id' => '']);
$viewUrl = Url::to(['view', 'id' => '']);
$deleteUrl = Url::to(['delete', 'id' => '']);
$moveUrl = Url::to(['move']);
$toggleStatusUrl = Url::to(['toggle-status', 'id' => '']);
$pjaxContainer = '#expense-categories-pjax';

$js = <<<JS
(function() {
    'use strict';

    var treeData = {$treeDataJson};
    var jstree = null;
    var pjaxContainer = '{$pjaxContainer}';

    // URLs
    var urls = {
        create: '{$createUrl}',
        update: '{$updateUrl}',
        view: '{$viewUrl}',
        delete: '{$deleteUrl}',
        move: '{$moveUrl}',
        toggleStatus: '{$toggleStatusUrl}'
    };

    /**
     * Initialize jsTree
     */
    function initTree() {
        if (treeData.length === 0) return;

        jQuery('#category-tree').jstree({
            'core': {
                'data': treeData,
                'themes': {
                    'name': 'default',
                    'responsive': true,
                    'dots': true,
                    'icons': true
                },
                'check_callback': true,
                'multiple': false
            },
            'plugins': ['dnd', 'search', 'wholerow', 'contextmenu'],
            'dnd': {
                'copy': false,
                'is_draggable': function(nodes) {
                    return true;
                }
            },
            'search': {
                'case_insensitive': true,
                'show_only_matches': true,
                'show_only_matches_children': true
            },
            'contextmenu': {
                'items': function(node) {
                    return {
                        'view': {
                            'label': 'View',
                            'icon': 'bi bi-eye',
                            'action': function() { viewCategory(node.id); }
                        },
                        'edit': {
                            'label': 'Edit',
                            'icon': 'bi bi-pencil',
                            'action': function() { editCategory(node.id); }
                        },
                        'add_child': {
                            'label': 'Add Subcategory',
                            'icon': 'bi bi-plus-circle',
                            'action': function() { createSubcategory(node.id); }
                        },
                        'toggle_status': {
                            'label': node.li_attr['data-status'] == 1 ? 'Deactivate' : 'Activate',
                            'icon': node.li_attr['data-status'] == 1 ? 'bi bi-x-circle' : 'bi bi-check-circle',
                            'action': function() { toggleStatus(node.id); }
                        },
                        'delete': {
                            'label': 'Delete',
                            'icon': 'bi bi-trash text-danger',
                            'action': function() { deleteCategory(node.id, node.text, node.children.length); },
                            'separator_before': true
                        }
                    };
                }
            }
        });

        jstree = jQuery('#category-tree').jstree(true);

        // Handle move (drag & drop)
        jQuery('#category-tree').on('move_node.jstree', function(e, data) {
            var nodeId = data.node.id;
            var newParentId = data.parent === '#' ? null : data.parent;
            moveCategory(nodeId, newParentId);
        });

        // Double-click to edit
        jQuery('#category-tree').on('dblclick.jstree', '.jstree-anchor', function(e) {
            var nodeId = jQuery(this).closest('li').attr('id');
            editCategory(nodeId);
        });
    }

    /**
     * Reload PJAX container to refresh stats and list
     */
    function reloadPjax() {
        if (typeof jQuery.pjax !== 'undefined') {
            jQuery.pjax.reload({container: pjaxContainer, async: false});
        } else {
            location.reload();
        }
    }

    /**
     * View category - Uses NEM Modal
     */
    function viewCategory(id) {
        NEM.Modal.open(
            urls.view + id,
            'Category Details',
            '#nemModal',
            { icon: '<i class="bi bi-folder text-danger me-2"></i>' }
        );
    }

    /**
     * Edit category - Uses NEM Modal
     */
    function editCategory(id) {
        NEM.Modal.open(
            urls.update + id,
            'Edit Category',
            '#nemModal',
            { icon: '<i class="bi bi-pencil text-primary me-2"></i>' }
        );
    }

    /**
     * Create subcategory - Uses NEM Modal
     */
    function createSubcategory(parentId) {
        NEM.Modal.open(
            urls.create + '?parent=' + parentId,
            'Add Subcategory',
            '#nemModal',
            { icon: '<i class="bi bi-folder-plus text-danger me-2"></i>' }
        );
    }

    /**
     * Toggle status - Uses NEM Ajax
     */
    function toggleStatus(id) {
        var csrf = NEM.Utils.getCsrf();

        NEM.Ajax.post(urls.toggleStatus.replace('id=', 'id=' + id), csrf.param + '=' + csrf.token, {
            contentType: 'application/x-www-form-urlencoded',
            processData: true,
            onSuccess: function(response) {
                // Reload PJAX to refresh stats
                reloadPjax();
            }
        });
    }

    /**
     * Move category - Uses NEM Ajax
     */
    function moveCategory(id, newParentId) {
        var csrf = NEM.Utils.getCsrf();
        var data = csrf.param + '=' + csrf.token + '&id=' + id + '&parent_id=' + (newParentId || '');

        NEM.Ajax.post(urls.move, data, {
            contentType: 'application/x-www-form-urlencoded',
            processData: true,
            onSuccess: function(response) {
                // Reload PJAX to refresh stats
                reloadPjax();
            },
            onError: function() {
                // Reload PJAX to restore original state
                reloadPjax();
            }
        });
    }

    /**
     * Delete category - Uses global deleteModal
     */
    function deleteCategory(id, name, childrenCount) {
        var message = 'Are you sure you want to delete <strong>"' + NEM.Utils.escapeHtml(name) + '"</strong>?';

        if (childrenCount > 0) {
            message += '<div class="alert alert-warning py-2 mt-3 mb-0">' +
                '<i class="bi bi-exclamation-triangle me-1"></i>' +
                'This category has ' + childrenCount + ' subcategory(s). They will be moved to the parent level.' +
                '</div>';
        }

        jQuery('.delete-message').html(message);

        jQuery('.delete-record')
            .data('url', urls.delete + id)
            .data('container', 'expense-categories-pjax')
            .data('category-id', id);

        NEM.Modal.show('#deleteModal');
    }

    /**
     * Handle delete confirmation
     */
    jQuery(document).off('click.categoryDelete', '.delete-record').on('click.categoryDelete', '.delete-record', function(e) {
        e.preventDefault();

        var btn = jQuery(this);
        var url = btn.data('url');
        var categoryId = btn.data('category-id');

        // Only handle if this is a category delete (has category-id)
        if (!categoryId) {
            return;
        }

        if (!url) {
            NEM.Utils.log('Delete URL not found', 'error');
            return;
        }

        var csrf = NEM.Utils.getCsrf();
        var originalHtml = btn.html();

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        jQuery.ajax({
            url: url,
            type: 'POST',
            data: csrf.param + '=' + csrf.token,
            dataType: 'json',
            success: function(response) {
                NEM.Modal.hide('#deleteModal');

                if (response.status === 'success') {
                    NEM.Toast.success(response.message || 'Category deleted successfully');
                    // Reload PJAX to refresh stats and tree
                    reloadPjax();
                } else {
                    NEM.Toast.error(response.message || 'Failed to delete category');
                }
            },
            error: function() {
                NEM.Modal.hide('#deleteModal');
                NEM.Toast.error('An error occurred');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
                btn.removeData('category-id');
            }
        });
    });

    /**
     * Search functionality
     */
    var searchTimeout;
    jQuery('#tree-search').on('input', function() {
        var input = jQuery(this);
        clearTimeout(searchTimeout);
        var query = input.val();
        searchTimeout = setTimeout(function() {
            if (jstree) {
                jstree.search(query);
            }
        }, 300);
    });

    /**
     * Expand/Collapse all
     */
    jQuery('#btn-expand-all').on('click', function() {
        if (jstree) jstree.open_all();
    });

    jQuery('#btn-collapse-all').on('click', function() {
        if (jstree) jstree.close_all();
    });

    /**
     * Show/Hide inactive
     */
    jQuery('#show-inactive').on('change', function() {
        if (jQuery(this).is(':checked')) {
            jQuery('.status-inactive').closest('li').show();
        } else {
            jQuery('.status-inactive').closest('li').hide();
        }
    });

    /**
     * View toggle (Tree/List)
     */
    jQuery('.view-toggle .btn').on('click', function() {
        var view = jQuery(this).data('view');
        jQuery('.view-toggle .btn').removeClass('active');
        jQuery(this).addClass('active');

        if (view === 'tree') {
            jQuery('#category-tree').removeClass('d-none');
            jQuery('#category-list').addClass('d-none');
        } else {
            jQuery('#category-tree').addClass('d-none');
            jQuery('#category-list').removeClass('d-none');
        }
    });

    /**
     * Re-initialize tree after PJAX reload
     */
    jQuery(document).on('pjax:complete', pjaxContainer, function() {
        // Re-fetch tree data after PJAX reload
        initTree();
    });

    // Initialize on document ready
    jQuery(document).ready(function() {
        initTree();
    });

    // Make functions globally accessible
    window.viewCategory = viewCategory;
    window.editCategory = editCategory;
    window.createSubcategory = createSubcategory;
    window.deleteCategory = deleteCategory;
    window.reloadCategoriesPjax = reloadPjax;

})();
JS;

$this->registerJs($js);
