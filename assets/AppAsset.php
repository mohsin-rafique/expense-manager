<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Main Application Asset Bundle
 *
 * This asset bundle defines the CSS and JavaScript files required for the
 * Expense Manager application. It uses the default Yii2 Bootstrap 5 theme
 * with optional custom styling overrides.
 *
 * ## Asset Dependencies
 *
 * | Bundle                    | Description                          |
 * |---------------------------|--------------------------------------|
 * | YiiAsset                  | Core Yii2 JavaScript functionality   |
 * | BootstrapAsset            | Bootstrap 5 CSS framework            |
 * | BootstrapPluginAsset      | Bootstrap 5 JavaScript plugins       |
 *
 * ## Custom Styles
 *
 * The `css/site.css` file can be used to add custom styling on top of
 * the default Bootstrap 5 theme without modifying core framework files.
 *
 * ## Usage
 *
 * This bundle is automatically registered in the main layout file:
 *
 * ```php
 * // In views/layouts/main.php
 * use app\assets\AppAsset;
 * AppAsset::register($this);
 * ```
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 * @see https://www.yiiframework.com/doc/api/2.0/yii-web-assetbundle
 */
class AppAsset extends AssetBundle
{
    /**
     * @var string The directory that contains the source asset files for this asset bundle.
     */
    public $basePath = '@webroot';

    /**
     * @var string The base URL for the relative asset files listed in [[css]] and [[js]].
     */
    public $baseUrl = '@web';

    /**
     * @var array List of CSS files that this bundle contains.
     *
     * Custom styles are loaded after Bootstrap to allow overrides.
     */
    public $css = [
        '//cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css',
        '/libs/choices.js/public/assets/styles/choices.min.css',
        'css/site.css',
    ];

    /**
     * @var array List of JavaScript files that this bundle contains.
     *
     * Add custom JavaScript files here as needed.
     */
    public $js = [
        '/libs/choices.js/public/assets/scripts/choices.min.js',
        '/js/nem.js',
    ];

    /**
     * @var array List of bundle class names that this bundle depends on.
     *
     * Dependencies:
     * - YiiAsset: Provides yii.js with core functionality (AJAX, validation, etc.)
     * - BootstrapAsset: Bootstrap 5 CSS
     * - BootstrapPluginAsset: Bootstrap 5 JavaScript (modals, dropdowns, etc.)
     */
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];
}
