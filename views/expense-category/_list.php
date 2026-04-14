<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * List View Partial for Expense Categories
 *
 * Displays expense categories in a flat list/table view with hierarchy indication.
 *
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\ExpenseCategorySearch $searchModel
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;

?>

<?php Pjax::begin(['id' => 'expense-categories-pjax', 'timeout' => 5000]); ?>

<div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'layout' => "{items}\n<div class='d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 mt-4'><div class='text-muted small'>{summary}</div><div>{pager}</div></div>",
        'tableOptions' => [
            'class' => 'table table-hover align-middle mb-0',
        ],
        'pager' => [
            'class' => \yii\bootstrap5\LinkPager::class,
            'options' => ['class' => 'pagination pagination-sm mb-0'],
            'linkContainerOptions' => ['class' => 'page-item'],
            'linkOptions' => ['class' => 'page-link'],
            'maxButtonCount' => 5,
        ],
        'columns' => [
            // Category Name with Hierarchy
            [
                'attribute' => 'name',
                'format' => 'raw',
                'headerOptions' => ['style' => 'min-width: 300px;'],
                'value' => function ($model) {
                    $depth = $model->getDepth();
                    $indent = str_repeat('<span class="ms-3"></span>', $depth);

                    $icon = $model->icon ?? 'bi-folder';
                    $color = $model->color ?? '#dc2626';

                    $iconHtml = '<span class="category-icon-small me-2" style="color: ' . Html::encode($color) . ';">'
                        . '<i class="bi ' . Html::encode($icon) . '"></i>'
                        . '</span>';

                    $nameHtml = '<span class="fw-medium">' . Html::encode($model->name) . '</span>';

                    if ($model->hasChildren()) {
                        $childCount = count($model->children);
                        $nameHtml .= ' <span class="badge bg-light text-muted ms-1">' . $childCount . '</span>';
                    }

                    if ($depth > 0) {
                        $pathHtml = '<br><small class="text-muted">' . Html::encode($model->getFullPath(' › ')) . '</small>';
                    } else {
                        $pathHtml = '';
                    }

                    return $indent . $iconHtml . $nameHtml . $pathHtml;
                },
            ],

            // Parent Category
            [
                'attribute' => 'parent_id',
                'label' => Yii::t('app', 'Parent'),
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 150px;'],
                'value' => function ($model) {
                    if ($model->parent) {
                        return '<span class="text-muted">' . Html::encode($model->parent->name) . '</span>';
                    }
                    return '<span class="text-muted">—</span>';
                },
            ],

            // Status
            [
                'attribute' => 'status',
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 100px;'],
                'value' => function ($model) {
                    return $model->getStatusBadge();
                },
            ],

            // Expense Count
            [
                'label' => Yii::t('app', 'Expenses'),
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 100px;', 'class' => 'text-center'],
                'contentOptions' => ['class' => 'text-center'],
                'value' => function ($model) {
                    $count = $model->getExpenses()->count();
                    $class = $count > 0 ? 'text-danger' : 'text-muted';
                    return '<span class="' . $class . ' fw-medium">' . Yii::$app->formatter->asInteger($count) . '</span>';
                },
            ],

            // Created Date
            [
                'attribute' => 'created_at',
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 130px;'],
                'value' => function ($model) {
                    return '<span class="text-muted small">'
                        . Yii::$app->formatter->asDate($model->created_at, 'medium')
                        . '</span>';
                },
            ],

            // Actions
            [
                'class' => 'yii\grid\ActionColumn',
                'headerOptions' => ['style' => 'width: 180px;', 'class' => 'text-center'],
                'contentOptions' => ['class' => 'text-center'],
                'template' => '{view} {update} {add-child} {delete}',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a(
                            '<i class="bi bi-eye"></i>',
                            '#',
                            [
                                'class' => 'btn btn-sm btn-light me-1',
                                'onclick' => 'viewCategory(' . $model->id . '); return false;',
                                'title' => Yii::t('app', 'View'),
                            ]
                        );
                    },
                    'update' => function ($url, $model) {
                        return Html::a(
                            '<i class="bi bi-pencil"></i>',
                            '#',
                            [
                                'class' => 'btn btn-sm btn-light me-1',
                                'onclick' => 'editCategory(' . $model->id . '); return false;',
                                'title' => Yii::t('app', 'Edit'),
                            ]
                        );
                    },
                    'add-child' => function ($url, $model) {
                        return Html::a(
                            '<i class="bi bi-plus-circle"></i>',
                            '#',
                            [
                                'class' => 'btn btn-sm btn-light me-1',
                                'onclick' => 'createSubcategory(' . $model->id . '); return false;',
                                'title' => Yii::t('app', 'Add Subcategory'),
                            ]
                        );
                    },
                    'delete' => function ($url, $model) {
                        $childCount = count($model->children);
                        return Html::a(
                            '<i class="bi bi-trash"></i>',
                            '#',
                            [
                                'class' => 'btn btn-sm btn-light text-danger',
                                'onclick' => 'deleteCategory(' . $model->id . ', "' . Html::encode(addslashes($model->name)) . '", ' . $childCount . '); return false;',
                                'title' => Yii::t('app', 'Delete'),
                            ]
                        );
                    },
                ],
            ],
        ],
    ]); ?>
</div>

<?php Pjax::end(); ?>

<?php
$this->registerCss(<<<CSS
    .category-icon-small {
        font-size: 1rem;
    }
CSS);
