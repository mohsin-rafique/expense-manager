<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
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

            Yii::$app->language = $this->resolveLanguage($settings->language ?? null);
            Yii::$app->formatter->currencyCode = $settings->currency;
            Yii::$app->formatter->thousandSeparator = $settings->thousand_separator;
            Yii::$app->formatter->decimalSeparator = $settings->decimal_separator;
            Yii::$app->formatter->dateFormat = 'php:' . $settings->date_format;
            Yii::$app->formatter->datetimeFormat = 'php:d/m/Y H:i:s';
        } else {
            Yii::$app->language = $this->resolveGuestLanguage();
            Yii::$app->formatter->currencyCode = 'PKR';
            Yii::$app->formatter->decimalSeparator = '.';
            Yii::$app->formatter->thousandSeparator = ',';
            Yii::$app->formatter->dateFormat = 'php:d/m/Y';
            Yii::$app->formatter->datetimeFormat = 'php:d/m/Y H:i:s';
        }
    }

    /**
     * Resolves the language for an authenticated user.
     *
     * Falls back to the default language when the stored preference is empty
     * or not part of the supported languages list.
     *
     * @param string|null $preferred The user's stored language preference
     * @return string A valid, supported language code
     */
    private function resolveLanguage(?string $preferred): string
    {
        if ($preferred !== null && in_array($preferred, $this->supportedLanguages, true)) {
            return $preferred;
        }

        return $this->defaultLanguage();
    }

    /**
     * Resolves the language for a guest visitor.
     *
     * Priority: language cookie (set via the switcher) → browser's preferred
     * language (Accept-Language header) → application default.
     *
     * @return string A valid, supported language code
     */
    private function resolveGuestLanguage(): string
    {
        $cookieLang = Yii::$app->request->cookies->getValue('language');
        if ($cookieLang !== null && in_array($cookieLang, $this->supportedLanguages, true)) {
            return $cookieLang;
        }

        return Yii::$app->request->getPreferredLanguage($this->supportedLanguages);
    }

    /**
     * Returns the configured default language, falling back to 'en'.
     *
     * @return string
     */
    private function defaultLanguage(): string
    {
        return Yii::$app->params['defaultLanguage'] ?? 'en';
    }
}
