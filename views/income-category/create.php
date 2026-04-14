<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Create View for Income Categories
 *
 * Wrapper view for creating a new income category.
 * Redirects to index after successful creation.
 *
 * @var yii\web\View $this
 * @var app\models\IncomeCategory $model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\bootstrap5\Breadcrumbs;

$this->title = Yii::t('app', 'Create Income Category');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Income Categories'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="income-category-create">

    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1"><?= Html::encode($this->title) ?></h1>
            <nav aria-label="breadcrumb">
                <?= Breadcrumbs::widget([
                    'links' => $this->params['breadcrumbs'],
                    'options' => ['class' => 'breadcrumb mb-0'],
                ]) ?>
            </nav>
        </div>
        <div>
            <?= Html::a(
                '<i class="bi bi-arrow-left me-1"></i>' . Yii::t('app', 'Back to List'),
                ['index'],
                ['class' => 'btn btn-outline-secondary']
            ) ?>
        </div>
    </div>

    <!-- Form Card -->
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-folder-plus me-2 text-primary"></i>
                        <?= Yii::t('app', 'Category Details') ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?= $this->render('_form', [
                        'model' => $model,
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

</div>
