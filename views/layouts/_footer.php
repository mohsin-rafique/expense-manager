<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Footer Partial View
 *
 * Renders the page footer with:
 * - Copyright notice
 * - Footer navigation links (About, Contact, Privacy)
 *
 * @var yii\web\View $this The view object
 *
 * @see views/layouts/main.php Parent layout
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\bootstrap5\Html;
use yii\helpers\Url;
?>

<footer class="footer-main mt-auto">
    <div class="container-xxl">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="footer-text mb-0">
                    &copy; <?= date('Y') ?> <?= Html::encode(Yii::$app->name) ?>.
                    <?= Yii::t('app', 'Open Source under MIT License.') ?>
                </p>
            </div>
            <div class="col-md-6 text-md-end mt-2 mt-md-0">
                <a href="https://github.com/mohsin-rafique/expense-manager" class="footer-link me-3" target="_blank">
                    <i class="bi bi-github"></i> GitHub
                </a>
                <a href="<?= Url::to(['/site/about']) ?>" class="footer-link">
                    <?= Yii::t('app', 'About') ?>
                </a>
            </div>
        </div>
    </div>
</footer>
