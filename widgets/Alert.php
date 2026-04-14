<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\widgets;

use Yii;
use yii\bootstrap5\Widget;

/**
 * Alert widget renders Bootstrap flash messages from the session.
 *
 * Flash messages are displayed in the order they were set using `setFlash()`.
 *
 * ```php
 * Yii::$app->session->setFlash('error', 'Something went wrong.');
 * Yii::$app->session->setFlash('success', 'Saved successfully.');
 * Yii::$app->session->setFlash('info', 'For your information.');
 * Yii::$app->session->setFlash('warning', 'Please double-check.');
 *
 * // Multiple messages:
 * Yii::$app->session->setFlash('error', ['Error 1', 'Error 2']);
 * ```
 *
 * @package app\widgets
 */
class Alert extends Widget
{
    /**
     * @var array Mapping of flash type keys to Bootstrap alert CSS classes.
     */
    public array $alertTypes = [
        'error'   => 'alert-danger',
        'danger'  => 'alert-danger',
        'success' => 'alert-success',
        'info'    => 'alert-info',
        'warning' => 'alert-warning',
    ];

    /**
     * @var array Options passed to the close button of each alert.
     */
    public array $closeButton = [];

    /**
     * Renders all pending flash messages and clears them from the session.
     *
     * @return void
     */
    public function run(): void
    {
        $session = Yii::$app->session;
        $appendClass = isset($this->options['class']) ? ' ' . $this->options['class'] : '';

        foreach (array_keys($this->alertTypes) as $type) {
            $flash = $session->getFlash($type);

            foreach ((array) $flash as $i => $message) {
                echo \yii\bootstrap5\Alert::widget([
                    'body' => $message,
                    'closeButton' => $this->closeButton,
                    'options' => array_merge($this->options, [
                        'id' => $this->getId() . '-' . $type . '-' . $i,
                        'class' => $this->alertTypes[$type] . $appendClass,
                    ]),
                ]);
            }

            $session->removeFlash($type);
        }
    }
}
