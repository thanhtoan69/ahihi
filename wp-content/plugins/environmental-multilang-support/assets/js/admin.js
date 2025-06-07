/**
 * Environmental Multi-language Support - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize admin functionality
        EMS_Admin.init();
    });

    var EMS_Admin = {
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initColorPickers();
            this.initCharts();
        },

        bindEvents: function() {
            // Test Translation API
            $('#ems-test-api').on('click', this.testTranslationAPI);
            
            // Bulk translation form
            $('#ems-bulk-translate-form').on('submit', this.handleBulkTranslation);
            
            // Import/Export buttons
            $('#ems-import-translations').on('click', this.importTranslations);
            $('#ems-export-translations').on('click', this.exportTranslations);
            
            // Database cleanup
            $('#ems-cleanup-database').on('click', this.cleanupDatabase);
            
            // Cache management
            $('#ems-clear-cache').on('click', this.clearCache);
            $('#ems-rebuild-cache').on('click', this.rebuildCache);
            
            // Language toggle switches
            $('.ems-languages-grid .ems-toggle input').on('change', this.handleLanguageToggle);
            
            // Translation buttons in post editor
            $(document).on('click', '.ems-translate-post', this.translatePost);
            $(document).on('click', '.ems-link-translation', this.linkTranslation);
            $(document).on('click', '.ems-unlink-translation', this.unlinkTranslation);
            
            // Real-time translation
            $(document).on('blur', '.ems-translate-field', this.translateField);
        },

        initTabs: function() {
            $('.ems-admin-tabs .nav-tab').on('click', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                
                // Update tab state
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Show/hide tab content
                $('.ems-tab-content').hide();
                $(target).show();
            });
        },

        initColorPickers: function() {
            if ($.fn.wpColorPicker) {
                $('.ems-color-picker').wpColorPicker();
            }
        },

        initCharts: function() {
            // Initialize Chart.js charts if available
            if (typeof Chart !== 'undefined') {
                this.initActivityChart();
                this.initLanguageChart();
            }
        },

        initActivityChart: function() {
            var ctx = document.getElementById('ems-activity-chart');
            if (!ctx) return;

            // Get chart data via AJAX
            $.post(ems_admin_ajax.ajax_url, {
                action: 'ems_get_activity_data',
                nonce: ems_admin_ajax.nonce
            }, function(response) {
                if (response.success) {
                    new Chart(ctx, {
                        type: 'line',
                        data: response.data,
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            });
        },

        initLanguageChart: function() {
            var ctx = document.getElementById('ems-language-chart');
            if (!ctx) return;

            // Get chart data via AJAX
            $.post(ems_admin_ajax.ajax_url, {
                action: 'ems_get_language_data',
                nonce: ems_admin_ajax.nonce
            }, function(response) {
                if (response.success) {
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: response.data,
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }
            });
        },

        testTranslationAPI: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var originalText = $button.text();
            
            $button.text(ems_admin_ajax.strings.testing).prop('disabled', true);
            
            $.post(ems_admin_ajax.ajax_url, {
                action: 'ems_test_translation_api',
                nonce: ems_admin_ajax.nonce
            }, function(response) {
                if (response.success) {
                    EMS_Admin.showNotice('success', ems_admin_ajax.strings.success + ' ' + response.data);
                } else {
                    EMS_Admin.showNotice('error', ems_admin_ajax.strings.error + ' ' + response.data);
                }
            }).always(function() {
                $button.text(originalText).prop('disabled', false);
            });
        },

        handleBulkTranslation: function(e) {
            e.preventDefault();
            
            if (!confirm(ems_admin_ajax.strings.confirm_bulk_translate)) {
                return;
            }
            
            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var originalText = $button.text();
            
            $button.text(ems_admin_ajax.strings.testing).prop('disabled', true);
            $form.addClass('ems-loading');
            
            $.post(ems_admin_ajax.ajax_url, {
                action: 'ems_bulk_translate',
                nonce: ems_admin_ajax.nonce,
                from_language: $form.find('#bulk-translate-from').val(),
                to_language: $form.find('#bulk-translate-to').val(),
                content_type: $form.find('#bulk-translate-content').val()
            }, function(response) {
                if (response.success) {
                    EMS_Admin.showNotice('success', response.data);
                } else {
                    EMS_Admin.showNotice('error', response.data);
                }
            }).always(function() {
                $button.text(originalText).prop('disabled', false);
                $form.removeClass('ems-loading');
            });
        },

        importTranslations: function(e) {
            e.preventDefault();
            
            // Create file input
            var $fileInput = $('<input type="file" accept=".csv,.json,.po,.pot" style="display:none">');
            $('body').append($fileInput);
            
            $fileInput.on('change', function() {
                var file = this.files[0];
                if (!file) return;
                
                var formData = new FormData();
                formData.append('action', 'ems_import_translations');
                formData.append('nonce', ems_admin_ajax.nonce);
                formData.append('file', file);
                
                $.ajax({
                    url: ems_admin_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            EMS_Admin.showNotice('success', response.data);
                        } else {
                            EMS_Admin.showNotice('error', response.data);
                        }
                    }
                });
                
                $fileInput.remove();
            });
            
            $fileInput.click();
        },

        exportTranslations: function(e) {
            e.preventDefault();
            
            window.location.href = ems_admin_ajax.ajax_url + '?' + $.param({
                action: 'ems_export_translations',
                nonce: ems_admin_ajax.nonce
            });
        },

        cleanupDatabase: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to cleanup the database? This action cannot be undone.')) {
                return;
            }
            
            var $button = $(this);
            var originalText = $button.text();
            
            $button.text('Cleaning...').prop('disabled', true);
            
            $.post(ems_admin_ajax.ajax_url, {
                action: 'ems_cleanup_database',
                nonce: ems_admin_ajax.nonce
            }, function(response) {
                if (response.success) {
                    EMS_Admin.showNotice('success', response.data);
                } else {
                    EMS_Admin.showNotice('error', response.data);
                }
            }).always(function() {
                $button.text(originalText).prop('disabled', false);
            });
        },

        clearCache: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var originalText = $button.text();
            
            $button.text('Clearing...').prop('disabled', true);
            
            $.post(ems_admin_ajax.ajax_url, {
                action: 'ems_clear_cache',
                nonce: ems_admin_ajax.nonce
            }, function(response) {
                if (response.success) {
                    EMS_Admin.showNotice('success', response.data);
                } else {
                    EMS_Admin.showNotice('error', response.data);
                }
            }).always(function() {
                $button.text(originalText).prop('disabled', false);
            });
        },

        rebuildCache: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var originalText = $button.text();
            
            $button.text('Rebuilding...').prop('disabled', true);
            
            $.post(ems_admin_ajax.ajax_url, {
                action: 'ems_rebuild_cache',
                nonce: ems_admin_ajax.nonce
            }, function(response) {
                if (response.success) {
                    EMS_Admin.showNotice('success', response.data);
                } else {
                    EMS_Admin.showNotice('error', response.data);
                }
            }).always(function() {
                $button.text(originalText).prop('disabled', false);
            });
        },

        handleLanguageToggle: function() {
            var $checkbox = $(this);
            var language = $checkbox.val();
            var enabled = $checkbox.is(':checked');
            
            $.post(ems_admin_ajax.ajax_url, {
                action: 'ems_toggle_language',
                nonce: ems_admin_ajax.nonce,
                language: language,
                enabled: enabled
            }, function(response) {
                if (response.success) {
                    // Update UI
                    var $card = $checkbox.closest('.ems-language-card');
                    if (enabled) {
                        $card.addClass('enabled').removeClass('disabled');
                    } else {
                        $card.addClass('disabled').removeClass('enabled');
                    }
                } else {
                    // Revert checkbox state
                    $checkbox.prop('checked', !enabled);
                    EMS_Admin.showNotice('error', response.data);
                }
            });
        },

        translatePost: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var postId = $button.data('post-id');
            var fromLang = $button.data('from-lang');
            var toLang = $button.data('to-lang');
            var originalText = $button.text();
            
            $button.text('Translating...').prop('disabled', true);
            
            $.post(ems_admin_ajax.ajax_url, {
                action: 'ems_translate_post',
                nonce: ems_admin_ajax.nonce,
                post_id: postId,
                from_language: fromLang,
                to_language: toLang
            }, function(response) {
                if (response.success) {
                    EMS_Admin.showNotice('success', 'Post translated successfully!');
                    if (response.data.edit_link) {
                        window.open(response.data.edit_link, '_blank');
                    }
                } else {
                    EMS_Admin.showNotice('error', response.data);
                }
            }).always(function() {
                $button.text(originalText).prop('disabled', false);
            });
        },

        linkTranslation: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var postId = $button.data('post-id');
            var targetId = prompt('Enter the ID of the post to link as translation:');
            
            if (!targetId) return;
            
            $.post(ems_admin_ajax.ajax_url, {
                action: 'ems_link_translation',
                nonce: ems_admin_ajax.nonce,
                post_id: postId,
                target_id: targetId
            }, function(response) {
                if (response.success) {
                    EMS_Admin.showNotice('success', response.data);
                    location.reload();
                } else {
                    EMS_Admin.showNotice('error', response.data);
                }
            });
        },

        unlinkTranslation: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to unlink this translation?')) {
                return;
            }
            
            var $button = $(this);
            var postId = $button.data('post-id');
            var targetId = $button.data('target-id');
            
            $.post(ems_admin_ajax.ajax_url, {
                action: 'ems_unlink_translation',
                nonce: ems_admin_ajax.nonce,
                post_id: postId,
                target_id: targetId
            }, function(response) {
                if (response.success) {
                    EMS_Admin.showNotice('success', response.data);
                    location.reload();
                } else {
                    EMS_Admin.showNotice('error', response.data);
                }
            });
        },

        translateField: function() {
            var $field = $(this);
            var text = $field.val();
            var fromLang = $field.data('from-lang');
            var toLang = $field.data('to-lang');
            
            if (!text || text.length < 3) return;
            
            // Debounce translation requests
            clearTimeout($field.data('translate-timeout'));
            $field.data('translate-timeout', setTimeout(function() {
                $.post(ems_admin_ajax.ajax_url, {
                    action: 'ems_translate_text',
                    nonce: ems_admin_ajax.nonce,
                    text: text,
                    from_language: fromLang,
                    to_language: toLang
                }, function(response) {
                    if (response.success) {
                        var $targetField = $field.data('target');
                        if ($targetField) {
                            $($targetField).val(response.data.translation);
                        }
                    }
                });
            }, 1000));
        },

        showNotice: function(type, message) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible ems-notice"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Manual dismiss
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            });
        }
    };

    // Export for use in other scripts
    window.EMS_Admin = EMS_Admin;

})(jQuery);
