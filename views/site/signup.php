<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Sign Up View
 *
 * Renders the user registration form.
 * Uses the 'auth' layout with promotional panel.
 *
 * @var yii\web\View $this
 * @var yii\bootstrap5\ActiveForm $form
 * @var app\models\SignupForm $model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = Yii::t('app', 'Sign Up');

// Customize promo panel for signup page
$this->params['authPromoTitle'] = Yii::t('app', 'Already have an account?');
$this->params['authPromoText'] = Yii::t('app', 'Sign in to access your dashboard, view your expense history, and continue managing your finances.');
$this->params['authPromoButtonText'] = Yii::t('app', 'Sign In');
$this->params['authPromoButtonUrl'] = ['/site/login'];
$this->params['authPromoFeatures'] = [
    Yii::t('app', 'Access your personalized dashboard'),
    Yii::t('app', 'View expense history and reports'),
    Yii::t('app', 'Sync across all your devices'),
    Yii::t('app', 'Continue where you left off'),
];
?>

<!-- Header -->
<h1 class="mb-1"><?= Yii::t('app', 'Create Account') ?></h1>
<p class="text-body-secondary mb-4">
    <?= Yii::t('app', 'Start your journey to better financial management.') ?>
</p>

<!-- Signup Form -->
<?php $form = ActiveForm::begin([
    'id' => 'signup-form',
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
    'placeholder' => Yii::t('app', 'Choose a username'),
    'class' => 'form-control',
    'autocomplete' => 'username',
])->label(Yii::t('app', 'Username'), ['class' => 'form-label']) ?>

<!-- Email Field -->
<?= $form->field($model, 'email', [
    'options' => ['class' => 'mb-3'],
])->textInput([
    'type' => 'email',
    'placeholder' => Yii::t('app', 'Enter your email'),
    'class' => 'form-control',
    'autocomplete' => 'email',
])->label(Yii::t('app', 'Email'), ['class' => 'form-label']) ?>

<!-- Password Field -->
<?= $form->field($model, 'password', [
    'options' => ['class' => 'mb-3'],
])->passwordInput([
    'placeholder' => Yii::t('app', 'Create a password'),
    'class' => 'form-control',
    'autocomplete' => 'new-password',
])->label(Yii::t('app', 'Password'), ['class' => 'form-label']) ?>

<!-- Terms Checkbox -->
<?= $form->field($model, 'agreeTerms', [
    'options' => ['class' => 'form-check mb-4'],
    'template' => "{input}\n{label}\n{error}",
    'labelOptions' => ['class' => 'form-check-label'],
])->checkbox([
    'class' => 'form-check-input',
    'uncheck' => null,
    'label' => Yii::t('app', 'I agree to the {terms} and {privacy}', [
        'terms' => Html::a(Yii::t('app', 'Terms of Service'), ['/site/terms'], ['target' => '_blank']),
        'privacy' => Html::a(Yii::t('app', 'Privacy Policy'), ['/site/privacy'], ['target' => '_blank']),
    ]),
], false) ?>

<!-- Submit Button -->
<div class="d-grid gap-2">
    <?= Html::submitButton(
        Yii::t('app', 'Create Account'),
        ['class' => 'btn btn-primary btn-lg', 'name' => 'signup-button']
    ) ?>
</div>

<?php ActiveForm::end() ?>
