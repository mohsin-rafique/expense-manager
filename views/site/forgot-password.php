<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Forgot Password View
 *
 * Renders the password reset request form.
 * Uses the 'auth' layout with promotional panel.
 *
 * @var yii\web\View $this
 * @var yii\bootstrap5\ActiveForm $form
 * @var app\models\ResetPasswordForm $model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = Yii::t('app', 'Forgot Password');

// Customize promo panel for forgot password page
$this->params['authPromoTitle'] = Yii::t('app', 'Remember your password?');
$this->params['authPromoText'] = Yii::t('app', 'If you remember your password, you can sign in directly to access your account.');
$this->params['authPromoButtonText'] = Yii::t('app', 'Back to Sign In');
$this->params['authPromoButtonUrl'] = ['/site/login'];
$this->params['authPromoFeatures'] = [];
?>

<!-- Header -->
<h1 class="mb-1"><?= Yii::t('app', 'Forgot Password?') ?></h1>
<p class="text-body-secondary mb-4">
    <?= Yii::t('app', "No worries! Enter your email address and we'll send you instructions to reset your password.") ?>
</p>

<!-- Info Alert -->
<div class="alert alert-info d-flex align-items-start mb-4">
    <i class="bi bi-info-circle me-2 mt-1"></i>
    <div class="small">
        <?= Yii::t('app', 'Enter the email address associated with your account. You will receive a link to create a new password.') ?>
    </div>
</div>

<!-- Forgot Password Form -->
<?php $form = ActiveForm::begin([
    'id' => 'forgot-password-form',
    'fieldConfig' => [
        'template' => "{label}\n{input}\n{error}",
        'errorOptions' => ['class' => 'invalid-feedback'],
    ],
]) ?>

<!-- Email Field -->
<?= $form->field($model, 'email', [
    'options' => ['class' => 'mb-4'],
])->textInput([
    'type' => 'email',
    'autofocus' => true,
    'placeholder' => Yii::t('app', 'Enter your email address'),
    'class' => 'form-control',
    'autocomplete' => 'email',
])->label(Yii::t('app', 'Email Address'), ['class' => 'form-label']) ?>

<!-- Submit Button -->
<div class="d-grid gap-2 mb-3">
    <?= Html::submitButton(
        '<i class="bi bi-envelope me-1"></i>' . Yii::t('app', 'Send Reset Link'),
        ['class' => 'btn btn-primary btn-lg', 'name' => 'reset-button']
    ) ?>
</div>

<!-- Back to Login Link -->
<p class="text-center mb-0">
    <i class="bi bi-arrow-left me-1"></i>
    <?= Html::a(
        Yii::t('app', 'Back to Sign In'),
        ['/site/login'],
        ['class' => 'text-decoration-none']
    ) ?>
</p>

<?php ActiveForm::end() ?>
