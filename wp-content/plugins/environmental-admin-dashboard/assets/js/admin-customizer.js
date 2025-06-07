/**
 * Environmental Admin Customizer JavaScript
 * 
 * Handles admin interface customization functionality, AJAX requests,
 * form interactions, and dynamic UI updates.
 */

(function($) {
    'use strict';
    
    var EnvAdminCustomizer = {
        
        /**
         * Initialize the customizer
         */
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initSortable();
            this.initColorPickers();
            this.initMediaUploaders();
            this.initPreviewHandlers();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Form submission
            $('#env-customizer-form').on('submit', this.handleFormSubmit.bind(this));
            
            // Reset settings
            $('#reset-settings-btn').on('click', this.handleReset.bind(this));
            
            // Import/Export
            $('#export-settings-btn').on('click', this.handleExport.bind(this));
            $('#import-settings-btn').on('click', this.handleImport.bind(this));
            $('#import-file').on('change', this.handleFileSelect.bind(this));
            
            // Tab navigation
            $('.nav-tab').on('click', this.handleTabClick.bind(this));
            
            // Theme and color scheme previews
            $('.theme-preview').on('click', this.handleThemeSelect.bind(this));
            $('.color-scheme-preview').on('click', this.handleColorSchemeSelect.bind(this));
            
            // Real-time preview updates
            $('input, select, textarea').on('change input', this.handlePreviewUpdate.bind(this));
            
            // Logo upload/remove buttons
            $('.logo-upload-btn').on('click', this.handleLogoUpload.bind(this));
            $('.logo-remove-btn').on('click', this.handleLogoRemove.bind(this));
        },
        
        /**
         * Initialize tabs
         */
        initTabs: function() {
            $('.nav-tab').first().addClass('nav-tab-active');
            $('.tab-content').first().addClass('active');
        },
        
        /**
         * Initialize sortable lists
         */
        initSortable: function() {
            $('#menu-order-list').sortable({
                placeholder: 'sortable-placeholder',
                handle: '.menu-item-handle',
                axis: 'y',
                opacity: 0.8,
                update: function(event, ui) {
                    EnvAdminCustomizer.updateMenuOrder();
                }
            });
        },
        
        /**
         * Initialize color pickers
         */
        initColorPickers: function() {
            $('.color-picker').wpColorPicker({
                change: function(event, ui) {
                    EnvAdminCustomizer.updatePreview();
                }
            });
        },
        
        /**
         * Initialize media uploaders
         */
        initMediaUploaders: function() {
            // Admin logo uploader
            $('#upload_admin_logo_button').on('click', function(e) {
                e.preventDefault();
                EnvAdminCustomizer.openMediaUploader('admin_logo');
            });
            
            // Login logo uploader
            $('#upload_login_logo_button').on('click', function(e) {
                e.preventDefault();
                EnvAdminCustomizer.openMediaUploader('login_logo');
            });
            
            // Remove logo buttons
            $('#remove_admin_logo_button').on('click', function(e) {
                e.preventDefault();
                EnvAdminCustomizer.removeLogo('admin_logo');
            });
            
            $('#remove_login_logo_button').on('click', function(e) {
                e.preventDefault();
                EnvAdminCustomizer.removeLogo('login_logo');
            });
        },
        
        /**
         * Initialize preview handlers
         */
        initPreviewHandlers: function() {
            // Create live preview iframe if needed
            if ($('#live-preview-frame').length === 0) {
                $('<iframe id="live-preview-frame" style="display:none;"></iframe>')
                    .appendTo('body');
            }
        },
        
        /**
         * Handle tab clicks
         */
        handleTabClick: function(e) {
            e.preventDefault();
            
            var $tab = $(e.target);
            var targetId = $tab.attr('href').substring(1);
            
            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            $tab.addClass('nav-tab-active');
            
            // Show corresponding content
            $('.tab-content').removeClass('active');
            $('#' + targetId).addClass('active');
            
            // Trigger resize for sortable elements
            if (targetId === 'menu') {
                $('#menu-order-list').sortable('refresh');
            }
        },
        
        /**
         * Handle form submission
         */
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(e.target);
            var $submitBtn = $form.find('button[type="submit"]');
            var $spinner = $form.find('.spinner');
            
            // Show loading state
            $submitBtn.prop('disabled', true);
            $spinner.addClass('is-active');
            
            // Collect form data
            var formData = this.collectFormData($form);
            
            // Add nonce and action
            formData.action = 'env_save_customizer_settings';
            formData.nonce = envAdminCustomizer.nonce;
            
            // Submit via AJAX
            $.ajax({
                url: envAdminCustomizer.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        EnvAdminCustomizer.showMessage(response.data, 'success');
                        EnvAdminCustomizer.updatePreview();
                    } else {
                        EnvAdminCustomizer.showMessage(response.data || envAdminCustomizer.strings.error, 'error');
                    }
                },
                error: function() {
                    EnvAdminCustomizer.showMessage(envAdminCustomizer.strings.error, 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        },
        
        /**
         * Handle reset settings
         */
        handleReset: function(e) {
            e.preventDefault();
            
            if (!confirm(envAdminCustomizer.strings.confirmReset)) {
                return;
            }
            
            var $btn = $(e.target);
            $btn.prop('disabled', true);
            
            $.ajax({
                url: envAdminCustomizer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'env_reset_customizer_settings',
                    nonce: envAdminCustomizer.nonce
                },
                success: function(response) {
                    if (response.success) {
                        EnvAdminCustomizer.showMessage(response.data, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        EnvAdminCustomizer.showMessage(response.data || envAdminCustomizer.strings.error, 'error');
                    }
                },
                error: function() {
                    EnvAdminCustomizer.showMessage(envAdminCustomizer.strings.error, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },
        
        /**
         * Handle export settings
         */
        handleExport: function(e) {
            e.preventDefault();
            
            var $btn = $(e.target);
            $btn.prop('disabled', true);
            
            $.ajax({
                url: envAdminCustomizer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'env_export_customizer_settings',
                    nonce: envAdminCustomizer.nonce
                },
                success: function(response) {
                    if (response.success) {
                        EnvAdminCustomizer.downloadJSON(response.data, 'env-admin-customizer-settings.json');
                        EnvAdminCustomizer.showMessage('Settings exported successfully!', 'success');
                    } else {
                        EnvAdminCustomizer.showMessage(response.data || envAdminCustomizer.strings.error, 'error');
                    }
                },
                error: function() {
                    EnvAdminCustomizer.showMessage(envAdminCustomizer.strings.error, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },
        
        /**
         * Handle import settings
         */
        handleImport: function(e) {
            e.preventDefault();
            
            if (!confirm(envAdminCustomizer.strings.confirmImport)) {
                return;
            }
            
            var fileInput = document.getElementById('import-file');
            var file = fileInput.files[0];
            
            if (!file) {
                EnvAdminCustomizer.showMessage('Please select a file to import.', 'error');
                return;
            }
            
            var reader = new FileReader();
            reader.onload = function(e) {
                try {
                    var settings = JSON.parse(e.target.result);
                    EnvAdminCustomizer.importSettings(settings);
                } catch (error) {
                    EnvAdminCustomizer.showMessage('Invalid JSON file.', 'error');
                }
            };
            reader.readAsText(file);
        },
        
        /**
         * Handle file selection for import
         */
        handleFileSelect: function(e) {
            var file = e.target.files[0];
            var $importBtn = $('#import-settings-btn');
            
            if (file && file.type === 'application/json') {
                $importBtn.prop('disabled', false);
            } else {
                $importBtn.prop('disabled', true);
                if (file) {
                    EnvAdminCustomizer.showMessage('Please select a valid JSON file.', 'error');
                }
            }
        },
        
        /**
         * Handle theme selection
         */
        handleThemeSelect: function(e) {
            var $preview = $(e.target);
            var theme = $preview.data('theme');
            
            $('.theme-preview').removeClass('selected');
            $preview.addClass('selected');
            
            $('select[name="admin_theme"]').val(theme).trigger('change');
        },
        
        /**
         * Handle color scheme selection
         */
        handleColorSchemeSelect: function(e) {
            var $preview = $(e.target);
            var scheme = $preview.data('scheme');
            
            $('.color-scheme-preview').removeClass('selected');
            $preview.addClass('selected');
            
            $('select[name="color_scheme"]').val(scheme).trigger('change');
        },
        
        /**
         * Handle preview updates
         */
        handlePreviewUpdate: function(e) {
            // Debounce preview updates
            clearTimeout(this.previewTimeout);
            this.previewTimeout = setTimeout(function() {
                EnvAdminCustomizer.updatePreview();
            }, 500);
        },
        
        /**
         * Open media uploader
         */
        openMediaUploader: function(type) {
            var uploader = wp.media({
                title: 'Select Logo',
                button: { text: 'Use This Logo' },
                multiple: false,
                library: { type: 'image' }
            });
            
            uploader.on('select', function() {
                var attachment = uploader.state().get('selection').first().toJSON();
                EnvAdminCustomizer.setLogo(type, attachment.url);
            });
            
            uploader.open();
        },
        
        /**
         * Set logo
         */
        setLogo: function(type, url) {
            var inputId = 'custom_' + type;
            var previewId = type + '_preview';
            var removeButtonId = 'remove_' + type + '_button';
            
            $('#' + inputId).val(url);
            $('#' + previewId).html('<img src="' + url + '" style="max-width: 200px; height: auto;" />');
            $('#' + removeButtonId).show();
        },
        
        /**
         * Remove logo
         */
        removeLogo: function(type) {
            var inputId = 'custom_' + type;
            var previewId = type + '_preview';
            var removeButtonId = 'remove_' + type + '_button';
            
            $('#' + inputId).val('');
            $('#' + previewId).empty();
            $('#' + removeButtonId).hide();
        },
        
        /**
         * Update menu order
         */
        updateMenuOrder: function() {
            var order = [];
            $('#menu-order-list li').each(function() {
                order.push($(this).data('menu-item'));
            });
            
            // Store order in hidden field or update preview
            $('<input type="hidden" name="menu_order" />').val(JSON.stringify(order)).appendTo('#env-customizer-form');
        },
        
        /**
         * Collect form data
         */
        collectFormData: function($form) {
            var data = {};
            
            $form.find('input, select, textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();
                var type = $field.attr('type');
                
                if (!name) return;
                
                if (type === 'checkbox') {
                    if ($field.is(':checked')) {
                        if (name.indexOf('[]') !== -1) {
                            var baseName = name.replace('[]', '');
                            if (!data[baseName]) data[baseName] = [];
                            data[baseName].push(value);
                        } else {
                            data[name] = 1;
                        }
                    }
                } else if (type === 'radio') {
                    if ($field.is(':checked')) {
                        data[name] = value;
                    }
                } else {
                    data[name] = value;
                }
            });
            
            // Handle menu order
            var menuOrder = [];
            $('#menu-order-list li').each(function() {
                menuOrder.push($(this).data('menu-item'));
            });
            data.menu_order = menuOrder;
            
            return data;
        },
        
        /**
         * Import settings
         */
        importSettings: function(settings) {
            var $btn = $('#import-settings-btn');
            $btn.prop('disabled', true);
            
            $.ajax({
                url: envAdminCustomizer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'env_import_customizer_settings',
                    nonce: envAdminCustomizer.nonce,
                    settings: JSON.stringify(settings)
                },
                success: function(response) {
                    if (response.success) {
                        EnvAdminCustomizer.showMessage(response.data, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        EnvAdminCustomizer.showMessage(response.data || envAdminCustomizer.strings.error, 'error');
                    }
                },
                error: function() {
                    EnvAdminCustomizer.showMessage(envAdminCustomizer.strings.error, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },
        
        /**
         * Download JSON data
         */
        downloadJSON: function(data, filename) {
            var blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            var url = URL.createObjectURL(blob);
            
            var a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        },
        
        /**
         * Update live preview
         */
        updatePreview: function() {
            // Apply changes to current page for immediate preview
            var adminTheme = $('select[name="admin_theme"]').val();
            var colorScheme = $('select[name="color_scheme"]').val();
            var customCSS = $('textarea[name="custom_css"]').val();
            
            // Remove existing preview styles
            $('#env-preview-styles').remove();
            
            // Create new preview styles
            var previewCSS = '';
            
            if (adminTheme === 'environmental') {
                previewCSS += this.getEnvironmentalThemeCSS();
            } else if (adminTheme === 'dark') {
                previewCSS += this.getDarkThemeCSS();
            }
            
            if (colorScheme !== 'default') {
                previewCSS += this.getColorSchemeCSS(colorScheme);
            }
            
            if (customCSS) {
                previewCSS += customCSS;
            }
            
            if (previewCSS) {
                $('<style id="env-preview-styles">' + previewCSS + '</style>').appendTo('head');
            }
        },
        
        /**
         * Get environmental theme CSS
         */
        getEnvironmentalThemeCSS: function() {
            return `
                #adminmenu, #adminmenuback, #adminmenuwrap {
                    background: linear-gradient(135deg, #2E7D32 0%, #388E3C 100%) !important;
                }
                #adminmenu a { color: #E8F5E8 !important; }
                #adminmenu .wp-has-current-submenu .wp-submenu-head,
                #adminmenu .wp-menu-arrow,
                #adminmenu li.current a.menu-top {
                    background: #1B5E20 !important;
                    color: #fff !important;
                }
            `;
        },
        
        /**
         * Get dark theme CSS
         */
        getDarkThemeCSS: function() {
            return `
                body { background: #1a1a1a !important; color: #e0e0e0 !important; }
                #wpcontent, #wpfooter { background: #1a1a1a !important; }
                .wrap { color: #e0e0e0 !important; }
                input[type="text"], input[type="email"], textarea, select {
                    background: #2a2a2a !important;
                    border-color: #444 !important;
                    color: #e0e0e0 !important;
                }
            `;
        },
        
        /**
         * Get color scheme CSS
         */
        getColorSchemeCSS: function(scheme) {
            var colors = {
                green: { primary: '#4CAF50', secondary: '#2E7D32' },
                blue: { primary: '#2196F3', secondary: '#1976D2' },
                earth: { primary: '#8D6E63', secondary: '#5D4037' }
            };
            
            if (!colors[scheme]) return '';
            
            var primary = colors[scheme].primary;
            var secondary = colors[scheme].secondary;
            
            return `
                .wp-core-ui .button-primary {
                    background: ${primary} !important;
                    border-color: ${secondary} !important;
                }
                .wp-core-ui .button-primary:hover {
                    background: ${secondary} !important;
                }
            `;
        },
        
        /**
         * Show message
         */
        showMessage: function(message, type) {
            // Remove existing messages
            $('.customizer-message').remove();
            
            // Create new message
            var $message = $('<div class="customizer-message ' + type + '">' + message + '</div>');
            $message.insertAfter('.nav-tab-wrapper');
            $message.fadeIn();
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $message.remove();
                });
            }, 5000);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        EnvAdminCustomizer.init();
    });
    
})(jQuery);
