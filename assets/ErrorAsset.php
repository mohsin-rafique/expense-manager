<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Error Pages Asset Bundle
 *
 * This asset bundle provides styling and optional animations for error pages
 * (404, 403, 500, etc.). It uses the default Bootstrap 5 theme with minimal
 * custom styling to ensure error pages load quickly and reliably.
 *
 * ## Design Philosophy
 *
 * Error pages should:
 * - Load quickly (minimal dependencies)
 * - Work even when other assets fail
 * - Provide clear navigation back to safety
 * - Maintain brand consistency
 *
 * ## Usage
 *
 * This bundle is registered in the error layout:
 *
 * ```php
 * // In views/layouts/error.php
 * use app\assets\ErrorAsset;
 * ErrorAsset::register($this);
 * ```
 *
 * ## Supported Error Pages
 *
 * | Error Code | Description              |
 * |------------|--------------------------|
 * | 400        | Bad Request              |
 * | 401        | Unauthorized             |
 * | 403        | Forbidden                |
 * | 404        | Not Found                |
 * | 405        | Method Not Allowed       |
 * | 500        | Internal Server Error    |
 * | 502        | Bad Gateway              |
 * | 503        | Service Unavailable      |
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 * @see views/layouts/error.php Error layout template
 * @see views/site/error.php Error view template
 */
class ErrorAsset extends AssetBundle
{
    /**
     * @var string The directory that contains the source asset files.
     */
    public $basePath = '@webroot';

    /**
     * @var string The base URL for the relative asset files.
     */
    public $baseUrl = '@web';

    /**
     * @var array List of CSS files for error pages.
     *
     * Uses minimal styling to ensure fast loading and reliability.
     * Custom error styles can be added to css/error.css
     */
    public $css = [
        'css/error.css',
    ];

    /**
     * @var array List of JavaScript files for error pages.
     *
     * Kept minimal to ensure error pages work even when JS fails.
     * Add animation scripts here if needed (e.g., particles.js)
     */
    public $js = [];

    /**
     * @var array List of bundle class names that this bundle depends on.
     *
     * Dependencies:
     * - BootstrapAsset: Bootstrap 5 CSS for consistent styling
     *
     * Note: YiiAsset and BootstrapPluginAsset are omitted intentionally
     * to reduce dependencies and ensure error pages load reliably.
     */
    public $depends = [
        'yii\bootstrap5\BootstrapAsset',
    ];
}
