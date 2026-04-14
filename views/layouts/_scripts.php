<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Global Scripts Partial View
 *
 * Registers global JavaScript utilities:
 * - Toast notification helper function
 * - Modal reset handler
 *
 * @var yii\web\View $this The view object
 *
 * @see views/layouts/main.php Parent layout
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

$js = <<<JS
(function() {
    'use strict';

    /**
     * Display a toast notification
     *
     * @param {string} type - Notification type: 'success', 'error', or 'warning'
     * @param {string} message - Message to display
     */
    window.showToast = function(type, message) {
        var container = document.getElementById('toast-container');
        var bgClass = type === 'success' ? 'bg-success' : (type === 'error' ? 'bg-danger' : 'bg-warning');
        var icon = type === 'success' ? 'check-circle' : (type === 'error' ? 'x-circle' : 'exclamation-circle');

        var toast = document.createElement('div');
        toast.className = 'toast align-items-center text-white ' + bgClass + ' border-0';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML =
            '<div class="d-flex">' +
                '<div class="toast-body">' +
                    '<i class="bi bi-' + icon + ' me-2"></i>' + message +
                '</div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
            '</div>';

        container.appendChild(toast);

        var bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 4000
        });
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    };

    /**
     * Reset modal content when closed
     */
    var nemModal = document.getElementById('nemModal');
    if (nemModal) {
        nemModal.addEventListener('hidden.bs.modal', function() {
            var modalBody = this.querySelector('.modal-body');
            if (modalBody) {
                modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            }
        });
    }

})();
JS;

$this->registerJs($js);
