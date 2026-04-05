<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\components;

use yii\bootstrap5\Modal as BootstrapModal;

/**
 * CustomModal extends Bootstrap5 Modal to allow rendering without a header.
 *
 * Set `headerOptions = false` to suppress the header entirely.
 *
 * @package app\components
 */
class CustomModal extends BootstrapModal
{
    /**
     * Initializes the widget.
     *
     * @return void
     */
    public function init(): void
    {
        parent::init();

        $this->headerOptions = false;
    }

    /**
     * Renders the header HTML markup of the modal.
     *
     * Returns an empty string when `headerOptions` is set to false.
     *
     * @return string the rendering result
     */
    protected function renderHeader(): string
    {
        if ($this->headerOptions === false) {
            return '';
        }

        return parent::renderHeader();
    }
}
