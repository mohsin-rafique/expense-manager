<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Dashboard Asset Bundle
 *
 * Provides JavaScript and CSS assets specifically for the dashboard page.
 * Includes ApexCharts library and custom chart initialization scripts.
 *
 * ## Included Assets
 *
 * | Asset                  | Type | Description                    |
 * |------------------------|------|--------------------------------|
 * | apexcharts.min.js      | JS   | ApexCharts charting library    |
 * | dashboard-charts.js    | JS   | Custom chart configurations    |
 * | dashboard.css          | CSS  | Dashboard-specific styles      |
 *
 * ## Usage
 *
 * Register in your dashboard view or controller:
 *
 * ```php
 * use app\assets\DashboardAsset;
 * DashboardAsset::register($this);
 * ```
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class DashboardAsset extends AssetBundle
{
    /**
     * @var string Base path for assets
     */
    public $basePath = '@webroot';

    /**
     * @var string Base URL for assets
     */
    public $baseUrl = '@web';

    /**
     * @var array CSS files for dashboard
     */
    public $css = [
        'css/dashboard.css',
    ];

    /**
     * @var array JavaScript files for dashboard
     */
    public $js = [
        'libs/apexcharts/apexcharts.min.js',
        'js/dashboard-charts.js',
    ];

    /**
     * @var array Asset bundle dependencies
     */
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'app\assets\AppAsset',
    ];

    /**
     * @var array JavaScript options
     */
    public $jsOptions = [
        'position' => \yii\web\View::POS_END,
    ];
}
