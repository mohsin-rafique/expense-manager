<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Styles shared by the reconciliation screens (bank statement and FBR return).
 *
 * Returns the CSS as a string rather than rendering anything, so each view
 * registers it once alongside its own page-specific rules:
 *
 * ```php
 * $this->registerCss(require __DIR__ . '/_styles.php');
 * ```
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.3.0
 */

return <<<'CSS'
/* Page header */
.recon-header { display: flex; align-items: center; gap: 1rem; }
.recon-header__icon {
    width: 56px; height: 56px; flex-shrink: 0;
    border-radius: var(--em-radius-lg);
    background: var(--em-primary-light); color: var(--em-primary);
    display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
}
.recon-help {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.625rem 1rem; text-decoration: none;
    border: 1px solid var(--em-gray-200); border-radius: var(--em-radius-lg);
    background: var(--em-white); transition: all var(--em-transition);
}
.recon-help:hover { box-shadow: var(--em-shadow-md); border-color: var(--em-primary); }
.recon-help__icon {
    width: 40px; height: 40px; flex-shrink: 0;
    border-radius: var(--em-radius-full);
    background: var(--em-primary-light); color: var(--em-primary);
    display: flex; align-items: center; justify-content: center;
}

/* Cards */
.recon-card { border: none; border-radius: var(--em-radius-lg); box-shadow: var(--em-shadow); }
.recon-card__header {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 1rem 1.25rem; border-bottom: 1px solid var(--em-gray-100);
}
.recon-card__icon {
    width: 40px; height: 40px; flex-shrink: 0;
    border-radius: var(--em-radius-md);
    background: var(--em-primary-light); color: var(--em-primary);
    display: flex; align-items: center; justify-content: center; font-size: 1.125rem;
}

/* OR divider */
.recon-or { display: flex; align-items: center; text-align: center; color: var(--em-gray-400); font-size: 0.8125rem; margin: 1rem 0; }
.recon-or::before, .recon-or::after { content: ""; flex: 1; height: 1px; background: var(--em-gray-200); }
.recon-or span { padding: 0 0.75rem; }

/* File dropzone */
.recon-dropzone {
    border: 2px dashed var(--em-gray-300); border-radius: var(--em-radius-lg);
    background: var(--em-gray-50); cursor: pointer; transition: all var(--em-transition);
}
.recon-dropzone:hover, .recon-dropzone.dragover { border-color: var(--em-primary); background: var(--em-primary-light); }

/* Submit button */
.recon-submit {
    background: linear-gradient(135deg, var(--em-primary) 0%, var(--em-primary-dark) 100%);
    border: none; color: var(--em-white); font-weight: 600; padding: 0.75rem;
    border-radius: var(--em-radius-md); transition: all var(--em-transition);
}
.recon-submit:hover { color: var(--em-white); filter: brightness(1.06); box-shadow: var(--em-shadow-md); }

/* Empty state */
.recon-empty { text-align: center; padding: 3rem 1rem; }
.recon-empty__badge {
    width: 96px; height: 96px; margin: 0 auto 1.25rem;
    border-radius: var(--em-radius-full); background: var(--em-primary-light);
    color: var(--em-primary); font-size: 2.5rem;
    display: flex; align-items: center; justify-content: center;
}
CSS;
