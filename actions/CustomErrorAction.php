<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\actions;

use yii\web\ErrorAction;

/**
 * CustomErrorAction extends Yii2's ErrorAction to support a custom layout
 * and optional asset registration for error pages.
 *
 * @package app\actions
 */
class CustomErrorAction extends ErrorAction
{
    /** @var string The layout to use for error pages */
    public $layout = 'error';

    /** @var string|null Asset bundle class to register on error pages */
    public $errorAssets = null;

    /**
     * Registers custom assets, applies the custom layout, then runs
     * the default error action behavior.
     *
     * @return string the rendering result
     */
    public function run(): string
    {
        if ($this->errorAssets !== null) {
            $this->errorAssets::register(\Yii::$app->view);
        }

        \Yii::$app->controller->layout = $this->layout;

        return parent::run();
    }
}
