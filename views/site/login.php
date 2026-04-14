<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Login View
 *
 * Renders the user authentication form.
 * Uses the 'auth' layout with promotional panel.
 *
 * @var yii\web\View $this
 * @var yii\bootstrap5\ActiveForm $form
 * @var app\models\LoginForm $model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = Yii::t('app', 'Sign In');

// Customize promo panel for login page
$this->params['authPromoTitle'] = Yii::t('app', 'New Here?');
$this->params['authPromoButtonText'] = Yii::t('app', 'Create an Account');
$this->params['authPromoButtonUrl'] = ['/site/signup'];
?>

<!-- Header -->
<h1 class="mb-1"><?= Yii::t('app', 'Welcome Back') ?></h1>
<p class="text-body-secondary mb-4">
    <?= Yii::t('app', 'Sign in to manage your finances and track your expenses.') ?>
</p>

<!-- Login Form -->
<?php $form = ActiveForm::begin([
    'id' => 'login-form',
    'fieldConfig' => [
        'template' => "{label}\n{input}\n{error}",
        'errorOptions' => ['class' => 'invalid-feedback'],
    ],
]) ?>

<!-- Username Field -->
<?= $form->field($model, 'username', [
    'options' => ['class' => 'mb-3'],
])->textInput([
    'autofocus' => true,
    'placeholder' => Yii::t('app', 'Enter your username'),
    'class' => 'form-control',
    'autocomplete' => 'username',
])->label(Yii::t('app', 'Username'), ['class' => 'form-label']) ?>

<!-- Password Field -->
<?= $form->field($model, 'password', [
    'options' => ['class' => 'mb-3'],
])->passwordInput([
    'placeholder' => Yii::t('app', 'Enter your password'),
    'class' => 'form-control',
    'autocomplete' => 'current-password',
])->label(Yii::t('app', 'Password'), ['class' => 'form-label']) ?>

<!-- Remember Me & Forgot Password Row -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <?= $form->field($model, 'rememberMe', [
        'options' => ['class' => 'form-check mb-0'],
        'template' => "{input}\n{label}\n{error}",
        'labelOptions' => ['class' => 'form-check-label'],
    ])->checkbox([
        'class' => 'form-check-input',
        'uncheck' => null,
    ], false) ?>

    <?= Html::a(
        Yii::t('app', 'Forgot Password?'),
        ['/site/forgot-password'],
        ['class' => 'text-primary text-decoration-none']
    ) ?>
</div>

<!-- Submit Button -->
<div class="d-grid gap-2 mb-3">
    <?= Html::submitButton(
        Yii::t('app', 'Sign In'),
        ['class' => 'btn btn-primary btn-lg', 'name' => 'login-button']
    ) ?>
</div>

<!-- Terms Notice -->
<p class="text-muted small text-center mb-0">
    <?= Yii::t('app', 'By signing in, you agree to our {terms} and {privacy}.', [
        'terms' => Html::a(Yii::t('app', 'Terms of Service'), ['/site/terms'], ['class' => 'text-decoration-none']),
        'privacy' => Html::a(Yii::t('app', 'Privacy Policy'), ['/site/privacy'], ['class' => 'text-decoration-none']),
    ]) ?>
</p>

<?php ActiveForm::end() ?>
