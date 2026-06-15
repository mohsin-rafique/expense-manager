/**
 * NEM (Expense Manager) - Core JavaScript Module
 *
 * Centralized JavaScript functionality for the Expense Manager application.
 * Handles modals, AJAX operations, form submissions, deletions, and UI utilities.
 *
 * @author Mohsin Rafique
 * @version 2.0.0
 * @github https://github.com/mohsin-rafique
 * @website https://mohsinrafique.com
 * @contact mohsin.rafique@gmail.com
 *
 * @requires jQuery 3.6+
 * @requires Bootstrap 5.3+
 * @requires yii2-pjax
 */

var NEM = (function ($) {
    ("use strict");

    /**
     * =========================================================================
     * CONFIGURATION
     * =========================================================================
     */
    var Config = {
        // Timeouts
        pjaxTimeout: 5000,
        toastDuration: 4000,

        // Selectors - Modals
        modalSelector: "#nemModal",
        modalIconSelector: ".nem-modal-icon",
        modalTitleSelector: ".nem-modal-title",
        modalBodySelector: ".modal-body",
        deleteModalSelector: "#deleteModal",

        // Selectors - Buttons & Forms
        btnModalSelector: ".btn-modal",
        btnDeleteSelector: ".nemDeleteLink",
        formSelector: ".data-form",
        deleteMessageSelector: ".delete-message",
        deleteConfirmSelector: ".delete-record",

        // Selectors - UI Components
        toastContainerSelector: "#toast-container",
        pageSizeDropdownSelector: "#pageSizeDropdown",
        pageSizeFormSelector: "#pageSizeForm",

        // Loading states
        loadingHtml:
            '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
        loadingText: "Loading...",

        // Button states
        savingHtml: '<span class="spinner-border spinner-border-sm me-1"></span>Saving...',

        // Debug mode
        debug: false,
    };

    /**
     * =========================================================================
     * UTILITIES
     * =========================================================================
     */
    var Utils = {
        /**
         * Log message to console (only in debug mode)
         * @param {string} message - Message to log
         * @param {string} type - Log type: 'log', 'warn', 'error'
         * @param {*} data - Additional data to log
         */
        log: function (message, type, data) {
            if (!Config.debug) return;

            var logMethod = console[type] || console.log;
            if (data !== undefined) {
                logMethod("[NEM]", message, data);
            } else {
                logMethod("[NEM]", message);
            }
        },

        /**
         * Check if element exists
         * @param {string} selector - jQuery selector
         * @returns {boolean}
         */
        exists: function (selector) {
            return $(selector).length > 0;
        },

        /**
         * Get CSRF token from meta tag
         * @returns {object} - {param: string, token: string}
         */
        getCsrf: function () {
            return {
                param: $('meta[name="csrf-param"]').attr("content") || "_csrf",
                token: $('meta[name="csrf-token"]').attr("content") || "",
            };
        },

        /**
         * Escape HTML to prevent XSS
         * @param {string} text - Text to escape
         * @returns {string}
         */
        escapeHtml: function (text) {
            if (!text) return "";
            var div = document.createElement("div");
            div.textContent = text;
            return div.innerHTML;
        },
    };

    /**
     * =========================================================================
     * TOAST NOTIFICATIONS
     * =========================================================================
     */
    var Toast = {
        /**
         * Show toast notification
         * @param {string} type - 'success', 'error', 'warning', 'info'
         * @param {string} message - Message to display
         * @param {object} options - Optional settings
         */
        show: function (type, message, options) {
            options = $.extend(
                {
                    duration: Config.toastDuration,
                    position: "top-end",
                },
                options,
            );

            var container = $(Config.toastContainerSelector);
            if (!container.length) {
                container = $('<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;"></div>');
                $("body").append(container);
            }

            var typeConfig = {
                success: { bg: "bg-success", icon: "bi-check-circle-fill" },
                error: { bg: "bg-danger", icon: "bi-x-circle-fill" },
                warning: { bg: "bg-warning", icon: "bi-exclamation-triangle-fill" },
                info: { bg: "bg-info", icon: "bi-info-circle-fill" },
            };

            var config = typeConfig[type] || typeConfig.info;

            var toastHtml =
                '<div class="toast align-items-center text-white ' +
                config.bg +
                ' border-0" role="alert" aria-live="assertive" aria-atomic="true">' +
                '<div class="d-flex">' +
                '<div class="toast-body">' +
                '<i class="bi ' +
                config.icon +
                ' me-2"></i>' +
                Utils.escapeHtml(message) +
                "</div>" +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
                "</div>" +
                "</div>";

            var $toast = $(toastHtml);
            container.append($toast);

            var bsToast = new bootstrap.Toast($toast[0], {
                autohide: true,
                delay: options.duration,
            });

            bsToast.show();

            $toast.on("hidden.bs.toast", function () {
                $(this).remove();
            });

            Utils.log("Toast shown: " + type + " - " + message, "log");
        },

        /**
         * Shorthand methods
         */
        success: function (message, options) {
            this.show("success", message, options);
        },
        error: function (message, options) {
            this.show("error", message, options);
        },
        warning: function (message, options) {
            this.show("warning", message, options);
        },
        info: function (message, options) {
            this.show("info", message, options);
        },
    };

    /**
     * =========================================================================
     * AJAX HANDLER
     * =========================================================================
     */
    var Ajax = {
        /**
         * Perform AJAX request
         * @param {object} options - Request options
         * @returns {jqXHR}
         */
        request: function (options) {
            var defaults = {
                url: "",
                type: "POST",
                data: null,
                dataType: "JSON",
                processData: false,
                contentType: false,
                cache: false,
                modalSelector: null,
                pjaxContainer: null,
                onSuccess: null,
                onError: null,
                onComplete: null,
            };

            var settings = $.extend({}, defaults, options);

            Utils.log("AJAX Request: " + settings.url, "log", settings);

            return $.ajax({
                url: settings.url,
                type: settings.type,
                data: settings.data,
                dataType: settings.dataType,
                processData: settings.processData,
                contentType: settings.contentType,
                cache: settings.cache,
                success: function (response) {
                    Utils.log("AJAX Success", "log", response);

                    if (response.success || response.status === "success") {
                        // Hide modal if specified
                        if (settings.modalSelector) {
                            Modal.hide(settings.modalSelector);
                        }

                        // Show success message
                        if (response.message) {
                            Toast.success(response.message);
                        }

                        // Show a secondary budget/overspend alert if present
                        if (response.alert && response.alert.message) {
                            Toast.show(
                                response.alert.level === "error" ? "error" : "warning",
                                response.alert.message,
                                { duration: 8000 }
                            );
                        }

                        // Reload PJAX container
                        if (settings.pjaxContainer) {
                            Pjax.reload(settings.pjaxContainer);
                        }

                        // Custom success callback
                        if (typeof settings.onSuccess === "function") {
                            settings.onSuccess(response);
                        }
                    } else {
                        // Show error message
                        Toast.error(response.message || "An error occurred");

                        // Handle validation errors
                        if (response.errors && settings.form) {
                            Form.showErrors(settings.form, response.errors);
                        }

                        // Custom error callback
                        if (typeof settings.onError === "function") {
                            settings.onError(response);
                        }
                    }
                },
                error: function (xhr, status, error) {
                    Utils.log("AJAX Error: " + error, "error", xhr);
                    Toast.error("Request failed. Please try again.");

                    if (typeof settings.onError === "function") {
                        settings.onError({ xhr: xhr, status: status, error: error });
                    }
                },
                complete: function (xhr, status) {
                    if (typeof settings.onComplete === "function") {
                        settings.onComplete(xhr, status);
                    }
                },
            });
        },

        /**
         * POST request shorthand
         */
        post: function (url, data, options) {
            return this.request(
                $.extend({}, options, {
                    url: url,
                    type: "POST",
                    data: data,
                }),
            );
        },

        /**
         * GET request shorthand
         */
        get: function (url, options) {
            return this.request(
                $.extend({}, options, {
                    url: url,
                    type: "GET",
                }),
            );
        },
    };

    /**
     * =========================================================================
     * PJAX HANDLER
     * =========================================================================
     */
    var Pjax = {
        /**
         * Reload PJAX container
         * @param {string} container - Container selector (with or without #)
         * @param {object} options - Additional options
         */
        reload: function (container, options) {
            if (typeof $.pjax === "undefined") {
                Utils.log("PJAX not available, reloading page", "warn");
                location.reload();
                return;
            }

            // Ensure container has # prefix
            if (container && !container.startsWith("#")) {
                container = "#" + container;
            }

            var defaults = {
                container: container || "#pjax-container",
                async: false,
                timeout: Config.pjaxTimeout,
            };

            var settings = $.extend({}, defaults, options);

            Utils.log("PJAX Reload: " + settings.container, "log");

            try {
                $.pjax.reload(settings);
            } catch (e) {
                Utils.log("PJAX Reload failed, reloading page", "error", e);
                location.reload();
            }
        },
    };

    /**
     * =========================================================================
     * MODAL HANDLER
     * =========================================================================
     */
    var Modal = {
        /**
         * Open modal and load content
         * @param {string} url - URL to load content from
         * @param {string} title - Modal title
         * @param {string} modalSelector - Modal selector (optional)
         * @param {object} options - Additional options
         */
        open: function (url, title, modalSelector, options) {
            modalSelector = modalSelector || Config.modalSelector;
            options = options || {};

            var $modal = $(modalSelector);
            if (!$modal.length) {
                Utils.log("Modal not found: " + modalSelector, "error");
                return;
            }

            var $icon = $modal.find(Config.modalIconSelector);
            var $title = $modal.find(Config.modalTitleSelector);
            var $body = $modal.find(Config.modalBodySelector);

            Utils.log("Opening modal", "log", { url: url, title: title, icon: options.icon });

            // Set icon (if provided)
            if ($icon.length) {
                $icon.html(options.icon || "");
            }

            // Set title
            if ($title.length) {
                $title.html(title || "");
            }

            // Show loading state
            if ($body.length) {
                $body.html(Config.loadingHtml);
            }

            // Show modal first
            this.show(modalSelector);

            // Then load content via AJAX
            $.ajax({
                url: url,
                type: "GET",
                dataType: "html",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
                success: function (response) {
                    Utils.log("Modal content loaded: " + url, "log");

                    $body.html(response);

                    // Trigger custom event
                    $modal.trigger("nem:modal:loaded", { url: url, title: title });

                    // Initialize any forms in the modal
                    Form.init($body);

                    // Custom callback
                    if (typeof options.onLoad === "function") {
                        options.onLoad($body);
                    }
                },
                error: function (xhr, status, error) {
                    Utils.log("Modal content load failed: " + error, "error", xhr);
                    $body.html(
                        '<div class="alert alert-danger m-3">' +
                            '<i class="bi bi-exclamation-triangle me-2"></i>' +
                            "Failed to load content. Please try again." +
                            "</div>",
                    );
                },
            });
        },

        /**
         * Show modal
         * @param {string} modalSelector - Modal selector
         */
        show: function (modalSelector) {
            modalSelector = modalSelector || Config.modalSelector;
            var $modal = $(modalSelector);

            if (!$modal.length) return;

            var bsModal = bootstrap.Modal.getOrCreateInstance($modal[0]);
            bsModal.show();
        },

        /**
         * Hide modal
         * @param {string} modalSelector - Modal selector
         */
        hide: function (modalSelector) {
            modalSelector = modalSelector || Config.modalSelector;
            var $modal = $(modalSelector);

            if (!$modal.length) return;

            var bsModal = bootstrap.Modal.getInstance($modal[0]);
            if (bsModal) {
                bsModal.hide();
            }
        },

        /**
         * Reset modal content
         * @param {string} modalSelector - Modal selector
         */
        reset: function (modalSelector) {
            modalSelector = modalSelector || Config.modalSelector;
            var $modal = $(modalSelector);

            if (!$modal.length) return;

            $modal.find(Config.modalIconSelector).html("");
            $modal.find(Config.modalTitleSelector).html("");
            $modal.find(Config.modalBodySelector).html(Config.loadingHtml);
        },

        /**
         * Initialize modal events
         */
        init: function () {
            var self = this;

            // Reset modal on close
            $(document).on("hidden.bs.modal", Config.modalSelector, function () {
                self.reset(Config.modalSelector);
            });

            // Handle modal trigger buttons
            $(document).on("click", Config.btnModalSelector, function (e) {
                e.preventDefault();

                var $btn = $(this);
                var url = $btn.data("url");
                var title = $btn.data("title") || "";
                var icon = $btn.data("icon") || "";
                var target = $btn.data("target") || Config.modalSelector;

                Utils.log("Modal button clicked", "log", { url: url, title: title, icon: icon });

                if (!url) {
                    Utils.log("Modal button missing data-url", "warn", $btn);
                    return;
                }

                self.open(url, title, target, { icon: icon });
            });

            Utils.log("Modal handler initialized", "log");
        },
    };

    /**
     * =========================================================================
     * FORM HANDLER
     * =========================================================================
     */
    var Form = {
        /**
         * Initialize forms
         * @param {jQuery} $container - Container to search for forms
         */
        init: function ($container) {
            $container = $container || $(document);

            // Enhance searchable selects within this scope (e.g. modal forms).
            Select.init($container);

            // Find and initialize data-forms
            $container.find(Config.formSelector).each(function () {
                var $form = $(this);

                // Prevent double initialization
                if ($form.data("nem-initialized")) return;
                $form.data("nem-initialized", true);

                Utils.log("Form initialized: " + ($form.attr("id") || "unnamed"), "log");
            });
        },

        /**
         * Submit form via AJAX
         * @param {jQuery} $form - Form element
         * @param {object} options - Additional options
         */
        submit: function ($form, options) {
            options = options || {};

            var self = this;
            var url = $form.attr("action");
            var method = $form.attr("method") || "POST";

            // Clean amount fields before creating FormData
            $form.find("#expense-amount, #income-amount, .amount-input").each(function () {
                var $input = $(this);
                var value = $input.val();
                if (value) {
                    $input.val(value.replace(/,/g, ""));
                }
            });

            var formData = new FormData($form[0]);
            var pjaxContainer = $form.data("container");
            var $modal = $form.closest(".modal");
            var modalSelector = $modal.length ? "#" + $modal.attr("id") : null;

            // Get submit button and show loading state
            var $submitBtn = $form.find('[type="submit"]');
            var originalBtnHtml = $submitBtn.html();
            $submitBtn.prop("disabled", true).html(Config.savingHtml);

            Utils.log("Form submitting to: " + url, "log");

            $.ajax({
                url: url,
                type: method,
                data: formData,
                dataType: "JSON",
                processData: false,
                contentType: false,
                cache: false,
                success: function (response) {
                    Utils.log("Form response", "log", response);

                    // Support both 'success: true' and 'status: "success"'
                    if (response.success || response.status === "success") {
                        // Hide modal if in modal
                        if (modalSelector) {
                            Modal.hide(modalSelector);
                        }

                        // Show success message
                        if (response.message) {
                            Toast.success(response.message);
                        }

                        // Show a secondary budget/overspend alert if present
                        if (response.alert && response.alert.message) {
                            Toast.show(
                                response.alert.level === "error" ? "error" : "warning",
                                response.alert.message,
                                { duration: 8000 }
                            );
                        }

                        // Reload PJAX container
                        if (pjaxContainer) {
                            Pjax.reload(pjaxContainer);
                        }

                        // Custom success callback
                        if (typeof options.onSuccess === "function") {
                            options.onSuccess(response);
                        }
                    } else {
                        // Show error message
                        Toast.error(response.message || "An error occurred");

                        // Handle validation errors
                        if (response.errors) {
                            self.showErrors($form, response.errors);
                        }

                        // Custom error callback
                        if (typeof options.onError === "function") {
                            options.onError(response);
                        }
                    }
                },
                error: function (xhr, status, error) {
                    Utils.log("Form error: " + error, "error", xhr);
                    Toast.error("Request failed. Please try again.");

                    if (typeof options.onError === "function") {
                        options.onError({ xhr: xhr, status: status, error: error });
                    }
                },
                complete: function () {
                    // Restore button state
                    $submitBtn.prop("disabled", false).html(originalBtnHtml);

                    if (typeof options.onComplete === "function") {
                        options.onComplete();
                    }
                },
            });
        },

        /**
         * Show validation errors on form
         * @param {jQuery} $form - Form element
         * @param {object} errors - Validation errors object
         */
        showErrors: function ($form, errors) {
            // Clear existing errors
            this.clearErrors($form);

            // Add new errors
            for (var field in errors) {
                if (errors.hasOwnProperty(field)) {
                    var $input = $form.find('[name*="[' + field + ']"]');
                    if ($input.length) {
                        $input.addClass("is-invalid");

                        var errorMsg = Array.isArray(errors[field]) ? errors[field][0] : errors[field];

                        var $feedback = $('<div class="invalid-feedback d-block"></div>').text(errorMsg);

                        // Insert after input or input group
                        var $parent = $input.closest(".input-group");
                        if ($parent.length) {
                            $parent.after($feedback);
                        } else {
                            $input.after($feedback);
                        }
                    }
                }
            }
        },

        /**
         * Clear validation errors from form
         * @param {jQuery} $form - Form element
         */
        clearErrors: function ($form) {
            $form.find(".is-invalid").removeClass("is-invalid");
            $form.find(".invalid-feedback").remove();
        },

        /**
         * Initialize form submission handler
         */
        initSubmitHandler: function () {
            var self = this;

            $(document).on("submit", Config.formSelector, function (e) {
                e.preventDefault();
                e.stopPropagation();

                self.submit($(this));

                return false;
            });

            Utils.log("Form submit handler initialized", "log");
        },
    };

    /**
     * =========================================================================
     * DELETE HANDLER
     * =========================================================================
     */
    var Delete = {
        currentUrl: null,
        currentContainer: null,

        /**
         * Initialize delete handler
         */
        init: function () {
            var self = this;

            // Handle delete button click
            $(document).on("click", Config.btnDeleteSelector, function (e) {
                e.preventDefault();

                var $btn = $(this);
                self.currentUrl = $btn.data("url");
                self.currentContainer = $btn.data("container");

                var message = $btn.data("message") || "Are you sure you want to delete this item?";

                // Update delete modal message
                $(Config.deleteMessageSelector).html(message);

                // Store data in confirm button
                $(Config.deleteConfirmSelector).data("url", self.currentUrl).data("container", self.currentContainer);

                // Show delete modal
                Modal.show(Config.deleteModalSelector);
            });

            // Handle confirm delete button
            $(document).on("click", Config.deleteConfirmSelector, function (e) {
                e.preventDefault();

                var $btn = $(this);
                var url = $btn.data("url");
                var container = $btn.data("container");

                if (!url) {
                    Utils.log("Delete URL not found", "error");
                    return;
                }

                self.execute(url, container);
            });

            Utils.log("Delete handler initialized", "log");
        },

        /**
         * Execute delete action
         * @param {string} url - Delete URL
         * @param {string} container - PJAX container
         */
        execute: function (url, container) {
            var csrf = Utils.getCsrf();

            Ajax.request({
                url: url,
                type: "POST",
                data: csrf.param + "=" + csrf.token,
                contentType: "application/x-www-form-urlencoded",
                processData: true,
                modalSelector: Config.deleteModalSelector,
                pjaxContainer: container,
            });
        },
    };

    /**
     * =========================================================================
     * UI UTILITIES
     * =========================================================================
     */
    var UI = {
        /**
         * Initialize page size dropdown
         */
        initPageSize: function () {
            $(document).on("change", Config.pageSizeDropdownSelector, function (e) {
                e.preventDefault();
                $(Config.pageSizeFormSelector).submit();
            });

            Utils.log("Page size handler initialized", "log");
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            Utils.log("Tooltips initialized", "log");
        },

        /**
         * Initialize popovers
         */
        initPopovers: function () {
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });

            Utils.log("Popovers initialized", "log");
        },

        /**
         * Scroll to element
         * @param {string} selector - Element selector
         * @param {number} offset - Offset from top
         */
        scrollTo: function (selector, offset) {
            offset = offset || 100;
            var $element = $(selector);

            if ($element.length) {
                $("html, body").animate(
                    {
                        scrollTop: $element.offset().top - offset,
                    },
                    300,
                );
            }
        },

        /**
         * Confirm action
         * @param {string} message - Confirmation message
         * @param {function} onConfirm - Callback on confirm
         * @param {function} onCancel - Callback on cancel
         */
        confirm: function (message, onConfirm, onCancel) {
            if (confirm(message)) {
                if (typeof onConfirm === "function") {
                    onConfirm();
                }
            } else {
                if (typeof onCancel === "function") {
                    onCancel();
                }
            }
        },
    };

    /**
     * =========================================================================
     * DATE UTILITIES
     * =========================================================================
     */
    var DateUtils = {
        /**
         * Format date as YYYY-MM-DD
         * @param {Date} date - Date object
         * @returns {string}
         */
        format: function (date) {
            return date.getFullYear() + "-" + String(date.getMonth() + 1).padStart(2, "0") + "-" + String(date.getDate()).padStart(2, "0");
        },

        /**
         * Get date range for quick filters
         * @param {string} range - 'today', 'week', 'month', 'last_month', 'year'
         * @returns {object} - {start: string, end: string}
         */
        getRange: function (range) {
            var today = new Date();
            var startDate, endDate;

            switch (range) {
                case "today":
                    startDate = endDate = today;
                    break;
                case "week":
                    var day = today.getDay();
                    startDate = new Date(today);
                    startDate.setDate(today.getDate() - day);
                    endDate = new Date(startDate);
                    endDate.setDate(startDate.getDate() + 6);
                    break;
                case "month":
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    break;
                case "last_month":
                    startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    endDate = new Date(today.getFullYear(), today.getMonth(), 0);
                    break;
                case "year":
                    startDate = new Date(today.getFullYear(), 0, 1);
                    endDate = new Date(today.getFullYear(), 11, 31);
                    break;
                default:
                    startDate = endDate = today;
            }

            return {
                start: this.format(startDate),
                end: this.format(endDate),
            };
        },
    };

    /**
     * =========================================================================
     * QUICK DATE FILTER
     * =========================================================================
     */
    var QuickDateFilter = {
        /**
         * Initialize quick date filter buttons
         * @param {object} options - Configuration options
         */
        init: function (options) {
            var defaults = {
                containerSelector: ".quick-date",
                startDateSelector: null, // Will be set based on searchModel
                endDateSelector: null,
                activeClass: "active",
                autoSubmit: true,
            };

            var settings = $.extend({}, defaults, options);

            $(document).on("click", settings.containerSelector, function (e) {
                e.preventDefault();

                var $btn = $(this);
                var range = $btn.data("range");

                if (!range) {
                    Utils.log("Quick date button missing data-range", "warn");
                    return;
                }

                var dates = DateUtils.getRange(range);

                // Find date inputs - try to detect from form context
                var $form = $btn.closest("form");
                var $startInput = settings.startDateSelector ? $(settings.startDateSelector) : $form.find('input[name*="[start_date]"]');
                var $endInput = settings.endDateSelector ? $(settings.endDateSelector) : $form.find('input[name*="[end_date]"]');

                if ($startInput.length) $startInput.val(dates.start);
                if ($endInput.length) $endInput.val(dates.end);

                // Update active state
                $(settings.containerSelector).removeClass(settings.activeClass);
                $btn.addClass(settings.activeClass);

                // Auto-submit form
                if (settings.autoSubmit && $form.length) {
                    $form.submit();
                }

                Utils.log("Quick date filter applied: " + range, "log", dates);
            });

            Utils.log("Quick date filter initialized", "log");
        },
    };

    /**
     * =========================================================================
     * FILE UPLOAD HANDLER
     * =========================================================================
     */
    var FileUpload = {
        defaults: {
            uploadAreaSelector: "#upload-area",
            fileInputSelector: null,
            placeholderSelector: "#upload-placeholder",
            previewSelector: "#upload-preview",
            previewIconSelector: "#preview-icon",
            previewNameSelector: "#preview-name",
            previewSizeSelector: "#preview-size",
            removeBtnSelector: "#remove-file",
            validTypes: ["image/png", "image/jpeg", "image/jpg", "application/pdf"],
            maxSize: 12 * 1024 * 1024, // 12MB
            dragoverClass: "dragover",
        },

        /**
         * Initialize file upload handler
         * @param {object} options - Configuration options
         */
        init: function (options) {
            var self = this;
            var settings = $.extend({}, this.defaults, options);

            var $uploadArea = $(settings.uploadAreaSelector);
            var $fileInput = $(settings.fileInputSelector);
            var $placeholder = $(settings.placeholderSelector);
            var $preview = $(settings.previewSelector);
            var $previewIcon = $(settings.previewIconSelector);
            var $previewName = $(settings.previewNameSelector);
            var $previewSize = $(settings.previewSizeSelector);
            var $removeBtn = $(settings.removeBtnSelector);

            if (!$uploadArea.length || !$fileInput.length) {
                Utils.log("File upload elements not found", "warn");
                return;
            }

            // Prevent double initialization
            if ($uploadArea.data("file-upload-initialized")) {
                return;
            }
            $uploadArea.data("file-upload-initialized", true);

            // Click to upload - FIXED: Stop propagation properly
            $uploadArea.on("click", function (e) {
                // Don't trigger if clicking on remove button or file input itself
                if ($(e.target).closest(settings.removeBtnSelector).length) {
                    return;
                }
                if ($(e.target).is($fileInput) || $(e.target).closest($fileInput).length) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();
                $fileInput[0].click(); // Use native click instead of jQuery trigger
            });

            // Prevent file input click from bubbling up
            $fileInput.on("click", function (e) {
                e.stopPropagation();
            });

            // Drag and drop
            $uploadArea
                .on("dragenter dragover", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).addClass(settings.dragoverClass);
                })
                .on("dragleave drop", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).removeClass(settings.dragoverClass);
                })
                .on("drop", function (e) {
                    var files = e.originalEvent.dataTransfer.files;
                    if (files.length) {
                        $fileInput[0].files = files;
                        self.showPreview(files[0], settings, $placeholder, $preview, $previewIcon, $previewName, $previewSize);
                    }
                });

            // File input change
            $fileInput.on("change", function () {
                if (this.files.length) {
                    self.showPreview(this.files[0], settings, $placeholder, $preview, $previewIcon, $previewName, $previewSize);
                }
            });

            // Remove file
            $removeBtn.on("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                $fileInput.val("");
                $placeholder.removeClass("d-none");
                $preview.addClass("d-none");
            });

            Utils.log("File upload handler initialized", "log");
        },

        /**
         * Show file preview
         */
        showPreview: function (file, settings, $placeholder, $preview, $previewIcon, $previewName, $previewSize) {
            // Validate file type
            if (!settings.validTypes.includes(file.type)) {
                Toast.error("Invalid file type. Please upload PNG, JPG, or PDF.");
                return;
            }

            // Validate file size
            if (file.size > settings.maxSize) {
                Toast.error("File is too large. Maximum size is " + this.formatFileSize(settings.maxSize) + ".");
                return;
            }

            var ext = file.name.split(".").pop().toLowerCase();
            var iconClass = "bi-file-earmark text-secondary";

            if (["png", "jpg", "jpeg"].includes(ext)) {
                iconClass = "bi-file-image text-primary";
            } else if (ext === "pdf") {
                iconClass = "bi-file-pdf text-danger";
            }

            $previewIcon.attr("class", "bi " + iconClass + " me-3").css("font-size", "2rem");
            $previewName.text(file.name);
            $previewSize.text(this.formatFileSize(file.size));

            $placeholder.addClass("d-none");
            $preview.removeClass("d-none");

            Utils.log("File preview shown: " + file.name, "log");
        },

        /**
         * Format file size
         * @param {number} bytes - File size in bytes
         * @returns {string}
         */
        formatFileSize: function (bytes) {
            if (bytes < 1024) return bytes + " B";
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + " KB";
            return (bytes / 1048576).toFixed(1) + " MB";
        },
    };

    /**
     * =========================================================================
     * SEARCHABLE SELECT (Choices.js)
     * =========================================================================
     */
    var Select = {
        /**
         * Enhance <select> elements carrying the `.js-choices` marker class into
         * searchable dropdowns. Safe to call repeatedly: each element is only
         * initialized once. Works on page load, PJAX refresh, and AJAX modals.
         *
         * @param {jQuery} $container - Scope to search within (defaults to document)
         */
        init: function ($container) {
            if (typeof Choices === "undefined") {
                Utils.log("Choices.js not loaded; skipping searchable selects", "warn");
                return;
            }

            $container = $container || $(document);

            $container.find("select.js-choices").each(function () {
                var el = this;

                // Skip elements that are already enhanced.
                if (el.choicesInstance) return;

                // Only show the search box when the list is long enough to need it.
                var optionCount = el.querySelectorAll("option").length;

                el.choicesInstance = new Choices(el, {
                    searchEnabled: optionCount > 8,
                    searchResultLimit: 50,
                    shouldSort: false, // preserve category tree / defined order
                    allowHTML: false,
                    itemSelectText: "",
                    searchPlaceholderValue:
                        el.getAttribute("data-search-placeholder") || "Search...",
                });

                Utils.log("Searchable select initialized: " + (el.id || el.name || "unnamed"), "log");
            });
        },

        /**
         * Set a select's value programmatically and keep its Choices UI in sync.
         *
         * @param {Element} el - The native <select> element
         * @param {string} value - Option value to select
         * @returns {boolean}
         */
        setValue: function (el, value) {
            if (!el) return false;
            if (el.choicesInstance) {
                el.choicesInstance.setChoiceByValue(String(value));
            } else {
                el.value = value;
            }
            return true;
        },
    };

    /**
     * =========================================================================
     * AMOUNT INPUT FORMATTER
     * =========================================================================
     */
    var AmountFormatter = {
        /**
         * Initialize amount input formatting
         * @param {string} selector - Input selector
         * @param {object} options - Configuration options
         */
        init: function (selector, options) {
            var defaults = {
                locale: "en-US",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            };

            var settings = $.extend({}, defaults, options);

            $(document).on("blur", selector, function () {
                var value = $(this).val().replace(/,/g, "");
                if (value && !isNaN(value)) {
                    $(this).val(
                        parseFloat(value).toLocaleString(settings.locale, {
                            minimumFractionDigits: settings.minimumFractionDigits,
                            maximumFractionDigits: settings.maximumFractionDigits,
                        }),
                    );
                }
            });

            $(document).on("focus", selector, function () {
                $(this).val($(this).val().replace(/,/g, ""));
            });

            Utils.log("Amount formatter initialized for: " + selector, "log");
        },
    };

    /**
     * =========================================================================
     * GRID CHECKBOX HANDLER
     * =========================================================================
     */
    var GridCheckbox = {
        /**
         * Initialize grid checkbox selection
         * @param {object} options - Configuration options
         */
        init: function (options) {
            var defaults = {
                tableSelector: null, // Required
                bulkActionBtnSelector: null, // Button to show/hide
                onSelectionChange: null, // Callback function
            };

            var settings = $.extend({}, defaults, options);

            if (!settings.tableSelector) {
                Utils.log("GridCheckbox: tableSelector is required", "warn");
                return;
            }

            $(document).on("change", settings.tableSelector + ' input[type="checkbox"]', function () {
                var $table = $(settings.tableSelector);
                var checkedCount = $table.find('tbody input[type="checkbox"]:checked').length;

                // Show/hide bulk action button
                if (settings.bulkActionBtnSelector) {
                    if (checkedCount > 0) {
                        $(settings.bulkActionBtnSelector).removeClass("d-none");
                    } else {
                        $(settings.bulkActionBtnSelector).addClass("d-none");
                    }
                }

                // Callback
                if (typeof settings.onSelectionChange === "function") {
                    var selectedKeys = $table.yiiGridView("getSelectedRows");
                    settings.onSelectionChange(selectedKeys, checkedCount);
                }

                Utils.log("Grid selection changed: " + checkedCount + " items", "log");
            });

            Utils.log("Grid checkbox handler initialized for: " + settings.tableSelector, "log");
        },

        /**
         * Get selected rows from grid
         * @param {string} tableSelector - Table selector
         * @returns {array}
         */
        getSelected: function (tableSelector) {
            return $(tableSelector).yiiGridView("getSelectedRows");
        },

        /**
         * Clear all selections
         * @param {string} tableSelector - Table selector
         */
        clearSelection: function (tableSelector) {
            $(tableSelector).find('input[type="checkbox"]').prop("checked", false).trigger("change");
        },
    };

    /**
     * =========================================================================
     * BULK DELETE HANDLER
     * =========================================================================
     */
    var BulkDelete = {
        /**
         * Initialize bulk delete
         * @param {object} options - Configuration options
         */
        init: function (options) {
            var defaults = {
                btnSelector: null, // Required
                tableSelector: null, // Required
                url: null, // Required - bulk delete URL
                pjaxContainer: null,
                confirmMessage: "Are you sure you want to delete {count} item(s)?",
            };

            var settings = $.extend({}, defaults, options);

            if (!settings.btnSelector || !settings.tableSelector) {
                Utils.log("BulkDelete: btnSelector and tableSelector are required", "warn");
                return;
            }

            $(document).on("click", settings.btnSelector, function (e) {
                e.preventDefault();

                var selectedKeys = GridCheckbox.getSelected(settings.tableSelector);

                if (selectedKeys.length === 0) {
                    Toast.warning("Please select at least one item to delete.");
                    return;
                }

                var message = settings.confirmMessage.replace("{count}", selectedKeys.length);

                if (confirm(message)) {
                    if (settings.url) {
                        var csrf = Utils.getCsrf();
                        var data = {};
                        data[csrf.param] = csrf.token;
                        data["ids"] = selectedKeys;

                        Ajax.post(settings.url, $.param(data), {
                            contentType: "application/x-www-form-urlencoded",
                            processData: true,
                            pjaxContainer: settings.pjaxContainer,
                            onSuccess: function () {
                                GridCheckbox.clearSelection(settings.tableSelector);
                            },
                        });
                    } else {
                        Utils.log("Bulk delete - selected IDs:", "log", selectedKeys);
                    }
                }
            });

            Utils.log("Bulk delete handler initialized", "log");
        },
    };

    /**
     * =========================================================================
     * FORM UTILITIES
     * =========================================================================
     */
    var FormUtils = {
        /**
         * Trim whitespace from text inputs on form submit
         * @param {string} formSelector - Form selector
         */
        trimOnSubmit: function (formSelector) {
            $(document).on("submit", formSelector, function () {
                $(this)
                    .find('input[type="text"], textarea')
                    .each(function () {
                        $(this).val($.trim($(this).val()));
                    });
            });

            Utils.log("Form trim initialized for: " + formSelector, "log");
        },
    };

    /**
     * =========================================================================
     * CURRENCY PREVIEW HANDLER
     * =========================================================================
     * Handles live currency preview on the settings page.
     * Only initializes when the currency settings form exists.
     */
    var CurrencyPreview = {
        /**
         * Currency symbols mapping
         * Matches the symbols defined in CurrencyFormatter component
         */
        symbols: {
            // Major World Currencies
            USD: "$",
            EUR: "€",
            GBP: "£",
            JPY: "¥",
            CNY: "¥",
            CHF: "CHF",

            // North America
            CAD: "C$",
            MXN: "MX$",

            // Asia Pacific
            AUD: "A$",
            NZD: "NZ$",
            HKD: "HK$",
            SGD: "S$",
            INR: "₹",
            PKR: "₨",
            BDT: "৳",
            LKR: "₨",
            NPR: "₨",
            KRW: "₩",
            TWD: "NT$",
            THB: "฿",
            MYR: "RM",
            IDR: "Rp",
            PHP: "₱",
            VND: "₫",

            // Europe
            RUB: "₽",
            PLN: "zł",
            CZK: "Kč",
            HUF: "Ft",
            RON: "lei",
            BGN: "лв",
            UAH: "₴",
            TRY: "₺",
            SEK: "kr",
            NOK: "kr",
            DKK: "kr",
            ISK: "kr",

            // Middle East
            SAR: "﷼",
            AED: "د.إ",
            QAR: "﷼",
            KWD: "د.ك",
            BHD: ".د.ب",
            OMR: "﷼",
            JOD: "د.ا",
            ILS: "₪",
            EGP: "E£",
            LBP: "ل.ل",

            // Africa
            ZAR: "R",
            NGN: "₦",
            KES: "KSh",
            GHS: "₵",
            MAD: "د.م.",
            TZS: "TSh",
            UGX: "USh",

            // South America
            BRL: "R$",
            ARS: "$",
            CLP: "$",
            COP: "$",
            PEN: "S/",

            // Cryptocurrency
            BTC: "₿",
            ETH: "Ξ",
        },

        /**
         * Default configuration
         */
        defaults: {
            formSelector: "#currency-settings-form",
            previewSelector: "#currency-preview",
            sampleAmount: 12345.67,
        },

        /**
         * Initialize currency preview
         * @param {object} options - Configuration options
         */
        init: function (options) {
            var self = this;
            var settings = $.extend({}, this.defaults, options);

            var $form = $(settings.formSelector);
            var $preview = $(settings.previewSelector);

            // Only initialize if form and preview elements exist (i.e., on settings page)
            if (!$form.length || !$preview.length) {
                return;
            }

            // Prevent double initialization
            if ($form.data("currency-preview-initialized")) {
                return;
            }
            $form.data("currency-preview-initialized", true);

            /**
             * Update preview based on current form values
             */
            var updatePreview = function () {
                var currencyCode = $form.find('[name*="currency"]').not('[name*="currency_position"]').val() || "USD";
                var position = $form.find('[name*="currency_position"]').val() || "left";
                var thousandSep = $form.find('[name*="thousand_separator"]').val() || ",";
                var decimalSep = $form.find('[name*="decimal_separator"]').val() || ".";
                var decimalPlaces = parseInt($form.find('[name*="decimal_places"]').val()) || 2;

                // Clamp decimal places between 0 and 4
                decimalPlaces = Math.max(0, Math.min(4, decimalPlaces));

                // Get symbol (fallback to currency code if not found)
                var symbol = self.symbols[currencyCode] || currencyCode;

                // Format number
                var parts = settings.sampleAmount.toFixed(decimalPlaces).split(".");
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep);
                var formatted = parts.length > 1 ? parts.join(decimalSep) : parts[0];

                // Apply position
                var result;
                switch (position) {
                    case "right":
                        result = formatted + symbol;
                        break;
                    case "left_space":
                        result = symbol + " " + formatted;
                        break;
                    case "right_space":
                        result = formatted + " " + symbol;
                        break;
                    case "left":
                    default:
                        result = symbol + formatted;
                }

                $preview.text(result);
            };

            // Attach listeners to all form inputs
            $form.find("input, select").on("change input", updatePreview);

            Utils.log("Currency preview initialized", "log");
        },

        /**
         * Get symbol for a currency code
         * @param {string} code - ISO 4217 currency code
         * @returns {string} Currency symbol or code if not found
         */
        getSymbol: function (code) {
            return this.symbols[code] || code;
        },
    };

    /**
     * =========================================================================
     * SETTINGS TAB HANDLER
     * =========================================================================
     * Handles URL hash updates when switching settings tabs.
     * Only initializes when settings tabs exist.
     */
    var SettingsTabs = {
        /**
         * Default configuration
         */
        defaults: {
            tabSelector: 'a[data-bs-toggle="pill"]',
        },

        /**
         * Initialize settings tabs
         * @param {object} options - Configuration options
         */
        init: function (options) {
            var settings = $.extend({}, this.defaults, options);

            var $tabs = $(settings.tabSelector);

            // Only initialize if tabs exist
            if (!$tabs.length) {
                return;
            }

            // Update URL hash when tab changes
            $tabs.on("shown.bs.tab", function (e) {
                var hash = $(e.target).attr("href");
                if (hash) {
                    history.pushState(null, null, hash);
                }
            });

            Utils.log("Settings tabs initialized", "log");
        },
    };

    /**
     * =========================================================================
     * INITIALIZATION
     * =========================================================================
     */
    var init = function (options) {
        // Merge custom config
        if (options) {
            Config = $.extend({}, Config, options);
        }

        Utils.log("NEM Initializing...", "log");

        // Initialize core modules
        Modal.init();
        Form.init();
        Form.initSubmitHandler();
        Delete.init();
        UI.initPageSize();
        UI.initTooltips();

        // Generic status toggle (e.g. budgets): POST with CSRF, then reload PJAX
        $(document).on("click", ".nemToggleStatus", function (e) {
            e.preventDefault();
            var $btn = $(this);
            var url = $btn.data("url");
            if (!url) return;

            var csrf = Utils.getCsrf();
            Ajax.request({
                url: url,
                type: "POST",
                data: csrf.param + "=" + csrf.token,
                contentType: "application/x-www-form-urlencoded",
                processData: true,
                pjaxContainer: $btn.data("container"),
            });
        });

        // Initialize utility modules
        QuickDateFilter.init();
        AmountFormatter.init(".amount-input, #expense-amount, #income-amount");
        FormUtils.trimOnSubmit(".expense-search form, .income-search form");

        // Enhance marked <select> elements into searchable dropdowns, and
        // re-run after PJAX swaps content (e.g. filtered list reloads).
        Select.init();
        $(document).on("pjax:end", function (e) {
            Select.init($(e.target));
        });

        // Initialize settings page modules (only runs if elements exist)
        CurrencyPreview.init();
        SettingsTabs.init();

        Utils.log("NEM Initialized successfully", "log");
    };

    /**
     * =========================================================================
     * PUBLIC API
     * =========================================================================
     */
    return {
        // Configuration
        config: Config,

        // Initialize
        init: init,

        // Core Modules
        Utils: Utils,
        Toast: Toast,
        Ajax: Ajax,
        Pjax: Pjax,
        Modal: Modal,
        Form: Form,
        Delete: Delete,
        UI: UI,

        // Utility Modules
        DateUtils: DateUtils,
        QuickDateFilter: QuickDateFilter,
        FileUpload: FileUpload,
        Select: Select,
        AmountFormatter: AmountFormatter,
        GridCheckbox: GridCheckbox,
        BulkDelete: BulkDelete,
        FormUtils: FormUtils,
        CurrencyPreview: CurrencyPreview,
        SettingsTabs: SettingsTabs,

        // Shortcuts
        toast: Toast.show.bind(Toast),
        ajax: Ajax.request.bind(Ajax),
        openModal: Modal.open.bind(Modal),
        reload: Pjax.reload.bind(Pjax),
    };
})(jQuery);

/**
 * =========================================================================
 * AUTO-INITIALIZATION
 * =========================================================================
 */
$(document).ready(function () {
    NEM.init({
        debug: true, // Set to true for development
    });
});
