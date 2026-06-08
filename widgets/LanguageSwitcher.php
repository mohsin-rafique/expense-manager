<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Url;
use yii\helpers\Html;

/**
 * LanguageSwitcher Widget
 *
 * Displays a language switcher dropdown to allow users to change
 * their application language preference.
 *
 * Usage:
 * ```php
 * <?= LanguageSwitcher::widget([
 *     'style' => 'dropdown', // or 'inline'
 * ]) ?>
 * ```
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class LanguageSwitcher extends Widget
{
    /** @var string Display style: 'dropdown' or 'inline' */
    public string $style = 'dropdown';

    /** @var string CSS class for the container */
    public string $containerClass = '';

    /** @var array HTML attributes for the select element */
    public array $options = [];

    public function run(): string
    {
        $params = Yii::$app->params;
        $supportedLanguages = $params['supportedLanguages'] ?? [];
        $currentLanguage = Yii::$app->language;

        if (empty($supportedLanguages)) {
            return '';
        }

        // Register the JavaScript for language switching
        $this->registerJs();

        if ($this->style === 'dropdown') {
            return $this->renderDropdown($supportedLanguages, $currentLanguage);
        } else {
            return $this->renderInline($supportedLanguages, $currentLanguage);
        }
    }

    /**
     * Render dropdown style language switcher
     *
     * @param array $languages
     * @param string $currentLanguage
     * @return string
     */
    protected function renderDropdown(array $languages, string $currentLanguage): string
    {
        // Navbar-native dropdown: the toggle + menu are direct children of the
        // surrounding <li class="nav-item dropdown">, matching the user menu and
        // workspace switcher for consistent vertical alignment.
        $toggle = Html::a(
            Html::tag('i', '', ['class' => 'bi bi-globe2 nav-icon'])
                . Html::tag('span', $languages[$currentLanguage] ?? $currentLanguage, ['class' => 'd-none d-lg-inline']),
            '#',
            [
                'class' => 'nav-link dropdown-toggle',
                'id' => 'languageDropdown',
                'role' => 'button',
                'data-bs-toggle' => 'dropdown',
                'aria-expanded' => 'false',
            ]
        );

        $items = '';
        foreach ($languages as $code => $name) {
            $isActive = ($code === $currentLanguage);
            $label = Html::tag('span', $name)
                . ($isActive ? Html::tag('i', '', ['class' => 'bi bi-check-lg ms-auto text-primary']) : '');
            $items .= Html::tag(
                'li',
                Html::a(
                    $label,
                    ['site/change-language', 'lang' => $code],
                    ['class' => 'dropdown-item d-flex align-items-center' . ($isActive ? ' active' : ''), 'data-pjax' => '0']
                )
            );
        }

        $menu = Html::tag('ul', $items, [
            'class' => 'dropdown-menu dropdown-menu-end shadow-sm',
            'aria-labelledby' => 'languageDropdown',
        ]);

        return $toggle . $menu;
    }

    /**
     * Render inline style language switcher
     *
     * @param array $languages
     * @param string $currentLanguage
     * @return string
     */
    protected function renderInline(array $languages, string $currentLanguage): string
    {
        $html = '';
        $containerClass = $this->containerClass ?: 'language-switcher-inline';

        $html .= Html::beginTag('div', ['class' => $containerClass]);
        $html .= Html::beginTag('div', ['class' => 'd-flex gap-2']);

        foreach ($languages as $code => $name) {
            $active = ($code === $currentLanguage) ? 'active' : '';
            $html .= Html::a(
                $name,
                ['site/change-language', 'lang' => $code],
                ['class' => "btn btn-sm btn-outline-secondary {$active}"]
            );
        }

        $html .= Html::endTag('div');
        $html .= Html::endTag('div');

        return $html;
    }

    /**
     * Register JavaScript for language switching
     *
     * @return void
     */
    protected function registerJs(): void
    {
        // JavaScript can be added here if needed for additional functionality
    }
}
