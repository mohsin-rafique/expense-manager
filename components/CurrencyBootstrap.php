<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\components;

use Yii;
use yii\base\BootstrapInterface;

/**
 * Currency Bootstrap Component
 *
 * Automatically loads and applies user currency preferences when the application starts.
 * This component runs during bootstrap and applies user settings to Yii::$app->currency.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class CurrencyBootstrap implements BootstrapInterface
{
    /**
     * Bootstrap method called during application initialization
     *
     * @param \yii\base\Application $app The application instance
     */
    public function bootstrap($app): void
    {
        // Skip for console applications
        if ($app instanceof \yii\console\Application) {
            return;
        }

        // Apply settings after request is ready (user identity is available)
        $app->on(\yii\web\Application::EVENT_BEFORE_REQUEST, function () use ($app) {
            $this->applyCurrencySettings($app);
        });
    }

    /**
     * Applies currency settings from user preferences
     *
     * @param \yii\base\Application $app The application instance
     */
    protected function applyCurrencySettings($app): void
    {
        // Skip if user component not available or user is guest
        if (!$app->has('user') || Yii::$app->user->isGuest) {
            return;
        }

        // Get user's currency settings
        $settings = $this->getUserCurrencySettings();

        if (empty($settings)) {
            return;
        }

        // Apply to CurrencyFormatter component
        if ($app->has('currency')) {
            $app->currency->applyUserSettings($settings);
        }

        // Sync with Yii's formatter for consistency
        $this->syncWithYiiFormatter($settings);
    }

    /**
     * Retrieves user's currency settings from the settings table
     *
     * @return array The user's currency settings
     */
    protected function getUserCurrencySettings(): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            return [];
        }

        // Fetch from settings table via User relation
        // Requires User model to have: getSettings() relation
        $userSettings = $user->settings ?? null;

        if ($userSettings === null) {
            return [];
        }

        $settings = [
            'currency' => $userSettings->currency ?? null,
            'currency_position' => $userSettings->currency_position ?? null,
            'thousand_separator' => $userSettings->thousand_separator ?? null,
            'decimal_separator' => $userSettings->decimal_separator ?? null,
            'decimal_places' => $userSettings->decimal_places ?? null,
        ];

        // Filter out null/empty values
        return array_filter($settings, fn($value) => $value !== null && $value !== '');
    }

    /**
     * Syncs settings with Yii's built-in formatter
     *
     * @param array $settings The currency settings
     */
    protected function syncWithYiiFormatter(array $settings): void
    {
        $formatter = Yii::$app->formatter;

        if (!empty($settings['currency'])) {
            $formatter->currencyCode = $settings['currency'];
        }

        if (!empty($settings['thousand_separator'])) {
            $formatter->thousandSeparator = $settings['thousand_separator'];
        }

        if (!empty($settings['decimal_separator'])) {
            $formatter->decimalSeparator = $settings['decimal_separator'];
        }
    }
}
