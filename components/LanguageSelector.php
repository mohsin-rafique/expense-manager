<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\components;

use Yii;
use yii\base\BootstrapInterface;
use app\models\Settings;

/**
 * LanguageSelector bootstraps the application language and formatter settings
 * based on the authenticated user's preferences stored in the settings table.
 *
 * For guests, it falls back to the browser's preferred language and default
 * currency/date format settings.
 *
 * @package app\components
 */
class LanguageSelector implements BootstrapInterface
{
    /** @var array List of supported language codes (e.g. ['en', 'fr']) */
    public array $supportedLanguages = [];

    /**
     * Bootstraps the application language and formatter settings.
     *
     * @param \yii\base\Application $app the application currently running
     * @return void
     */
    public function bootstrap($app): void
    {
        if (!Yii::$app->user->isGuest) {
            $userId = Yii::$app->user->identity->id;

            $settings = Settings::findOne(['user_id' => $userId]);

            Yii::$app->language = 'en';
            Yii::$app->formatter->currencyCode = $settings->currency;
            Yii::$app->formatter->thousandSeparator = $settings->thousand_separator;
            Yii::$app->formatter->decimalSeparator = $settings->decimal_separator;
            Yii::$app->formatter->dateFormat = 'php:' . $settings->date_format;
            Yii::$app->formatter->datetimeFormat = 'php:d/m/Y H:i:s';
        } else {
            Yii::$app->language = Yii::$app->request->getPreferredLanguage($this->supportedLanguages);
            Yii::$app->formatter->currencyCode = 'PKR';
            Yii::$app->formatter->decimalSeparator = '.';
            Yii::$app->formatter->thousandSeparator = ',';
            Yii::$app->formatter->dateFormat = 'php:d/m/Y';
            Yii::$app->formatter->datetimeFormat = 'php:d/m/Y H:i:s';
        }
    }
}
