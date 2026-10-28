/**
 * Temple Management System — Centralized UI Utilities (Phase 2.1)
 * Standardized DataTables, Toastr, Button Loading, and Select2
 */
(function(window, $) {
    'use strict';

    window.TempleUI = window.TempleUI || {};

    // ------------------------------------------------------------------------
    // 1. Toastr Centralized Notifications
    // ------------------------------------------------------------------------
    if (typeof toastr !== 'undefined') {
        toastr.options = {
            closeButton: true,
            debug: false,
            newestOnTop: true,
            progressBar: true,
            positionClass: "toast-top-right",
            preventDuplicates: true,
            showDuration: "250",
            hideDuration: "800",
            timeOut: "4500",
            extendedTimeOut: "1500",
            showEasing: "swing",
            hideEasing: "linear",
            showMethod: "fadeIn",
            hideMethod: "fadeOut"
        };
    }

    TempleUI.toast = {
        success: function(message, title) {
            if (typeof toastr !== 'undefined' && message) {
                toastr.success(message, title || '');
            }
        },
        error: function(message, title) {
            if (typeof toastr !== 'undefined' && message) {
                toastr.error(message, title || '');
            }
        },
        warning: function(message, title) {
            if (typeof toastr !== 'undefined' && message) {
                toastr.warning(message, title || '');
            }
        },
        info: function(message, title) {
            if (typeof toastr !== 'undefined' && message) {
                toastr.info(message, title || '');
            }
        }
    };

    // ------------------------------------------------------------------------
    // 2. Button Loading Indicators & Double-Submission Prevention
    // ------------------------------------------------------------------------
    TempleUI.button = {
        loading: function($btn, customText) {
            if (!$btn || !$btn.length) return;
            if ($btn.data('is-loading')) return;

            var currentHtml = $btn.html();
            var currentWidth = $btn.outerWidth();
            var loadingText = customText || $btn.data('loading-text') || 
                (window.TempleConfig && window.TempleConfig.i18n ? window.TempleConfig.i18n.loading : 'Loading...');

            $btn.data('original-html', currentHtml);
            $btn.data('original-width', currentWidth);
            $btn.data('is-loading', true);

            $btn.css('min-width', currentWidth + 'px');
            $btn.prop('disabled', true);
            $btn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + loadingText);
        },

        reset: function($btn) {
            if (!$btn || !$btn.length) return;
            if (!$btn.data('is-loading')) return;

            var originalHtml = $btn.data('original-html');
            if (originalHtml !== undefined) {
                $btn.html(originalHtml);
            }
            $btn.prop('disabled', false);
            $btn.css('min-width', '');
            $btn.data('is-loading', false);
        }
    };

    // Auto-attach form submit loading
    $(document).on('submit', 'form:not([data-no-loading])', function(e) {
        var form = this;
        // If native HTML5 validation failed, don't show loading
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }

        // If event default was prevented by confirmation or custom validation, skip
        if (e.isDefaultPrevented()) {
            return;
        }

        var $submitBtn = $(form).find('button[type="submit"]:not([data-no-loading]), input[type="submit"]:not([data-no-loading])').first();
        if ($submitBtn.length) {
            // Small timeout to allow form submit event to dispatch before disabling
            setTimeout(function() {
                TempleUI.button.loading($submitBtn);
            }, 10);
        }
    });

    // ------------------------------------------------------------------------
    // 3. Select2 Reusable Initialization
    // ------------------------------------------------------------------------
    TempleUI.initSelect2 = function(context) {
        if (!$.fn.select2) return;

        var $scope = context ? $(context) : $(document);

        $scope.find('select.select2, select[data-toggle="select2"]').each(function() {
            var $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) {
                return; // Already initialized
            }

            var placeholder = $select.data('placeholder') || 
                (window.TempleConfig && window.TempleConfig.select2i18n ? window.TempleConfig.select2i18n.placeholder : 'Select an option');
            var allowClear = $select.data('allow-clear') !== undefined ? $select.data('allow-clear') : true;

            var config = {
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: placeholder,
                allowClear: allowClear
            };

            // If inside a Bootstrap modal, set dropdownParent so it renders correctly
            var $modal = $select.closest('.modal');
            if ($modal.length) {
                config.dropdownParent = $modal;
            }

            $select.select2(config);
        });
    };

    // ------------------------------------------------------------------------
    // 4. DataTables Global Defaults & Reusable Initialization
    // ------------------------------------------------------------------------
    var getDtLanguage = function() {
        return (window.TempleConfig && window.TempleConfig.datatablesLanguage) 
            ? window.TempleConfig.datatablesLanguage 
            : {};
    };

    if (typeof $.fn.dataTable !== 'undefined') {
        var dtGlobalLang = getDtLanguage();
        $.extend(true, $.fn.dataTable.defaults, {
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: dtGlobalLang,
            oLanguage: dtGlobalLang
        });
    }

    TempleUI.initDataTable = function(selector, userOptions) {
        if (!$.fn.DataTable) return null;

        var $table = $(selector);
        if (!$table.length) return null;

        // Prevent duplicate initialization
        if ($.fn.DataTable.isDataTable(selector)) {
            return $table.DataTable();
        }

        // Clean single colspan empty row if present to prevent DataTables column count mismatch
        var $rows = $table.find('tbody tr');
        if ($rows.length === 1 && $rows.first().find('td[colspan]').length) {
            $table.find('tbody').empty();
        }

        var dtLang = getDtLanguage();
        var baseOptions = {
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [], // Default preserve server order
            language: dtLang,
            oLanguage: dtLang
        };

        var mergedOptions = $.extend(true, {}, baseOptions, userOptions || {});
        return $table.DataTable(mergedOptions);
    };

    // ------------------------------------------------------------------------
    // 5. Sidebar Active Item Auto-Scroll & Scroll Persistence
    // ------------------------------------------------------------------------
    TempleUI.initSidebarScroll = function() {
        var $sidebar = $('.app-sidebar');
        var $menu = $sidebar.find('.sidebar-menu');
        if (!$menu.length) return;

        var $activeLink = $menu.find('.sidebar-link.active').first();
        if ($activeLink.length) {
            // Calculate active link's top position relative to scrollable .sidebar-menu
            var menuOffsetTop = $menu.offset().top;
            var linkOffsetTop = $activeLink.offset().top;
            var currentScroll = $menu.scrollTop();
            var itemTopInContent = linkOffsetTop - menuOffsetTop + currentScroll;

            var menuHeight = $menu.innerHeight();
            var linkHeight = $activeLink.outerHeight();

            // Center the active menu item in the sidebar viewport
            var targetScroll = Math.max(0, itemTopInContent - (menuHeight / 2) + (linkHeight / 2));
            $menu.scrollTop(targetScroll);
            sessionStorage.setItem('temple_sidebar_scroll', targetScroll);
        } else {
            var savedPos = sessionStorage.getItem('temple_sidebar_scroll');
            if (savedPos !== null) {
                $menu.scrollTop(parseInt(savedPos, 10));
            }
        }

        // Keep saved position in sync when user manually scrolls
        $menu.off('scroll.templeSidebar').on('scroll.templeSidebar', function() {
            sessionStorage.setItem('temple_sidebar_scroll', $(this).scrollTop());
        });
    };

    // Auto-initialize standard elements on DOM ready
    $(document).ready(function() {
        // Auto-scroll sidebar so active menu item is prominently visible
        TempleUI.initSidebarScroll();
        setTimeout(TempleUI.initSidebarScroll, 80);
        // Display flash toasts passed from Laravel session
        if (window.TempleConfig && window.TempleConfig.flashes) {
            var flashes = window.TempleConfig.flashes;
            if (flashes.success) TempleUI.toast.success(flashes.success);
            if (flashes.error) TempleUI.toast.error(flashes.error);
            if (flashes.warning) TempleUI.toast.warning(flashes.warning);
            if (flashes.info) TempleUI.toast.info(flashes.info);
        }

        // Display validation errors toast if present
        if (window.TempleConfig && window.TempleConfig.validationErrors && window.TempleConfig.validationErrors.length) {
            var errorList = window.TempleConfig.validationErrors;
            var summary = errorList.length === 1 ? errorList[0] : errorList.join('<br>');
            TempleUI.toast.error(summary, window.TempleConfig.i18n ? window.TempleConfig.i18n.validationError : 'Validation Error');
        }

        // Initialize Select2 across current document
        TempleUI.initSelect2(document);

        // Re-initialize Select2 when Bootstrap modals are opened
        $(document).on('shown.bs.modal', '.modal', function() {
            TempleUI.initSelect2(this);
        });

        // Initialize DataTables on auto-target tables
        $('table.data-table, table.datatable').each(function() {
            TempleUI.initDataTable(this);
        });

        // Global AJAX setup: inject CSRF token & handle errors
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ajaxError(function(event, jqXHR, settings, thrownError) {
            // Restore loading buttons on AJAX error
            $('button[data-is-loading="true"]').each(function() {
                TempleUI.button.reset($(this));
            });

            if (jqXHR.status === 422 && jqXHR.responseJSON) {
                var message = jqXHR.responseJSON.message || 'Validation error.';
                if (jqXHR.responseJSON.errors) {
                    var msgs = [];
                    $.each(jqXHR.responseJSON.errors, function(key, val) {
                        msgs.push(Array.isArray(val) ? val.join(' ') : val);
                    });
                    if (msgs.length) message = msgs.join('<br>');
                }
                TempleUI.toast.error(message);
            } else if (jqXHR.status === 403) {
                TempleUI.toast.error(jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : 'You do not have permission to perform this action.');
            } else if (jqXHR.status === 419) {
                TempleUI.toast.warning('Session expired. Please reload the page.');
            } else if (jqXHR.status >= 500) {
                TempleUI.toast.error('An unexpected server error occurred. Please try again.');
            }
        });
    });

})(window, jQuery);
