/**
 * Environmental Payment Gateway - Admin JavaScript
 * 
 * @package EnvironmentalPaymentGateway
 * @version 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Main admin functionality
     */
    const EPGAdmin = {
        
        /**
         * Initialize admin interface
         */
        init: function() {
            this.initToggleSwitches();
            this.initGatewayTesting();
            this.initEnvironmentalDashboard();
            this.initSettingsValidation();
            this.initBulkActions();
            console.log('EPG Admin initialized');
        },
        
        /**
         * Initialize toggle switches
         */
        initToggleSwitches: function() {
            $('.epg-toggle-switch input').on('change', function() {
                const $switch = $(this);
                const gatewayId = $switch.data('gateway');
                const isEnabled = $switch.is(':checked');
                
                EPGAdmin.updateGatewayStatus(gatewayId, isEnabled);
            });
        },
        
        /**
         * Update gateway status
         */
        updateGatewayStatus: function(gatewayId, enabled) {
            const data = {
                action: 'epg_update_gateway_status',
                gateway_id: gatewayId,
                enabled: enabled ? 1 : 0,
                nonce: epg_admin_ajax.nonce
            };
            
            $.post(epg_admin_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        EPGAdmin.showNotice('success', 'Gateway status updated successfully');
                        EPGAdmin.updateGatewayCard(gatewayId, enabled);
                    } else {
                        EPGAdmin.showNotice('error', response.data || 'Failed to update gateway status');
                        // Revert toggle
                        $(`.epg-toggle-switch input[data-gateway="${gatewayId}"]`).prop('checked', !enabled);
                    }
                })
                .fail(function() {
                    EPGAdmin.showNotice('error', 'Network error occurred');
                    // Revert toggle
                    $(`.epg-toggle-switch input[data-gateway="${gatewayId}"]`).prop('checked', !enabled);
                });
        },
        
        /**
         * Update gateway card appearance
         */
        updateGatewayCard: function(gatewayId, enabled) {
            const $card = $(`.epg-gateway-card[data-gateway="${gatewayId}"]`);
            const $indicator = $card.find('.epg-status-indicator');
            const $text = $card.find('.epg-status-text');
            
            if (enabled) {
                $indicator.removeClass('inactive').addClass('active');
                $text.removeClass('inactive').addClass('active').text('Active');
            } else {
                $indicator.removeClass('active').addClass('inactive');
                $text.removeClass('active').addClass('inactive').text('Inactive');
            }
        },
        
        /**
         * Initialize gateway testing
         */
        initGatewayTesting: function() {
            $('.epg-test-gateway').on('click', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const gatewayId = $button.data('gateway');
                
                EPGAdmin.testGateway(gatewayId, $button);
            });
        },
        
        /**
         * Test gateway connection
         */
        testGateway: function(gatewayId, $button) {
            const originalText = $button.text();
            $button.prop('disabled', true).html('<span class="epg-loading"></span> Testing...');
            
            const data = {
                action: 'epg_test_gateway',
                gateway_id: gatewayId,
                nonce: epg_admin_ajax.nonce
            };
            
            $.post(epg_admin_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        EPGAdmin.showNotice('success', `${gatewayId} gateway test successful`);
                        EPGAdmin.updateTestResults(gatewayId, response.data);
                    } else {
                        EPGAdmin.showNotice('error', response.data || 'Gateway test failed');
                    }
                })
                .fail(function() {
                    EPGAdmin.showNotice('error', 'Network error during gateway test');
                })
                .always(function() {
                    $button.prop('disabled', false).text(originalText);
                });
        },
        
        /**
         * Update test results display
         */
        updateTestResults: function(gatewayId, testData) {
            const $card = $(`.epg-gateway-card[data-gateway="${gatewayId}"]`);
            const $testResults = $card.find('.epg-test-results');
            
            if ($testResults.length === 0) {
                $card.append('<div class="epg-test-results"></div>');
            }
            
            const resultsHtml = `
                <div class="epg-notice epg-notice-success">
                    <span class="epg-notice-icon">✓</span>
                    <div>
                        <strong>Test Successful</strong><br>
                        Response time: ${testData.response_time}ms<br>
                        Last tested: ${new Date().toLocaleString()}
                    </div>
                </div>
            `;
            
            $card.find('.epg-test-results').html(resultsHtml);
        },
        
        /**
         * Initialize environmental dashboard
         */
        initEnvironmentalDashboard: function() {
            this.loadEnvironmentalStats();
            
            // Refresh stats every 5 minutes
            setInterval(() => {
                this.loadEnvironmentalStats();
            }, 300000);
        },
        
        /**
         * Load environmental statistics
         */
        loadEnvironmentalStats: function() {
            const data = {
                action: 'epg_get_environmental_stats',
                nonce: epg_admin_ajax.nonce
            };
            
            $.post(epg_admin_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        EPGAdmin.updateEnvironmentalStats(response.data);
                    }
                })
                .fail(function() {
                    console.error('Failed to load environmental stats');
                });
        },
        
        /**
         * Update environmental statistics display
         */
        updateEnvironmentalStats: function(stats) {
            $('.epg-env-stat[data-stat="carbon_offset"] .epg-env-stat-value').text(stats.carbon_offset + ' kg');
            $('.epg-env-stat[data-stat="eco_payments"] .epg-env-stat-value').text(stats.eco_payments);
            $('.epg-env-stat[data-stat="green_revenue"] .epg-env-stat-value').text('$' + stats.green_revenue);
            $('.epg-env-stat[data-stat="environmental_score"] .epg-env-stat-value').text(stats.environmental_score);
        },
        
        /**
         * Initialize settings validation
         */
        initSettingsValidation: function() {
            $('.epg-settings-form').on('submit', function(e) {
                if (!EPGAdmin.validateSettings()) {
                    e.preventDefault();
                    EPGAdmin.showNotice('error', 'Please correct the validation errors');
                }
            });
            
            // Real-time validation
            $('.epg-input, .epg-select').on('blur', function() {
                EPGAdmin.validateField($(this));
            });
        },
        
        /**
         * Validate form settings
         */
        validateSettings: function() {
            let isValid = true;
            
            $('.epg-input[required], .epg-select[required]').each(function() {
                if (!EPGAdmin.validateField($(this))) {
                    isValid = false;
                }
            });
            
            return isValid;
        },
        
        /**
         * Validate individual field
         */
        validateField: function($field) {
            const value = $field.val().trim();
            const fieldType = $field.data('type') || 'text';
            let isValid = true;
            let errorMessage = '';
            
            // Required field validation
            if ($field.prop('required') && !value) {
                isValid = false;
                errorMessage = 'This field is required';
            }
            
            // Type-specific validation
            if (value && isValid) {
                switch (fieldType) {
                    case 'email':
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(value)) {
                            isValid = false;
                            errorMessage = 'Please enter a valid email address';
                        }
                        break;
                        
                    case 'url':
                        try {
                            new URL(value);
                        } catch {
                            isValid = false;
                            errorMessage = 'Please enter a valid URL';
                        }
                        break;
                        
                    case 'api_key':
                        if (value.length < 10) {
                            isValid = false;
                            errorMessage = 'API key seems too short';
                        }
                        break;
                }
            }
            
            // Update field appearance
            if (isValid) {
                $field.removeClass('epg-field-error');
                $field.siblings('.epg-field-error-message').remove();
            } else {
                $field.addClass('epg-field-error');
                $field.siblings('.epg-field-error-message').remove();
                $field.after(`<div class="epg-field-error-message">${errorMessage}</div>`);
            }
            
            return isValid;
        },
        
        /**
         * Initialize bulk actions
         */
        initBulkActions: function() {
            $('.epg-bulk-action-select').on('change', function() {
                const action = $(this).val();
                const $button = $('.epg-bulk-action-apply');
                
                if (action) {
                    $button.prop('disabled', false);
                } else {
                    $button.prop('disabled', true);
                }
            });
            
            $('.epg-bulk-action-apply').on('click', function(e) {
                e.preventDefault();
                EPGAdmin.applyBulkAction();
            });
            
            $('.epg-select-all-gateways').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.epg-gateway-checkbox').prop('checked', isChecked);
            });
        },
        
        /**
         * Apply bulk action to selected gateways
         */
        applyBulkAction: function() {
            const action = $('.epg-bulk-action-select').val();
            const selectedGateways = $('.epg-gateway-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (!action || selectedGateways.length === 0) {
                EPGAdmin.showNotice('warning', 'Please select an action and at least one gateway');
                return;
            }
            
            const confirmMessage = `Are you sure you want to ${action} ${selectedGateways.length} gateway(s)?`;
            if (!confirm(confirmMessage)) {
                return;
            }
            
            const data = {
                action: 'epg_bulk_gateway_action',
                bulk_action: action,
                gateways: selectedGateways,
                nonce: epg_admin_ajax.nonce
            };
            
            $('.epg-bulk-action-apply').prop('disabled', true).text('Processing...');
            
            $.post(epg_admin_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        EPGAdmin.showNotice('success', `Bulk action completed successfully`);
                        location.reload(); // Refresh to show changes
                    } else {
                        EPGAdmin.showNotice('error', response.data || 'Bulk action failed');
                    }
                })
                .fail(function() {
                    EPGAdmin.showNotice('error', 'Network error during bulk action');
                })
                .always(function() {
                    $('.epg-bulk-action-apply').prop('disabled', false).text('Apply');
                });
        },
        
        /**
         * Show admin notice
         */
        showNotice: function(type, message) {
            const iconMap = {
                success: '✓',
                error: '✗',
                warning: '⚠',
                info: 'ℹ'
            };
            
            const notice = `
                <div class="epg-notice epg-notice-${type}">
                    <span class="epg-notice-icon">${iconMap[type]}</span>
                    ${message}
                </div>
            `;
            
            // Remove existing notices
            $('.epg-notice').fadeOut(300, function() {
                $(this).remove();
            });
            
            // Add new notice
            const $notice = $(notice).hide();
            $('.epg-admin-container').prepend($notice);
            $notice.fadeIn(300);
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(() => {
                    $notice.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        },
        
        /**
         * Export gateway settings
         */
        exportSettings: function() {
            const data = {
                action: 'epg_export_settings',
                nonce: epg_admin_ajax.nonce
            };
            
            $.post(epg_admin_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        const blob = new Blob([JSON.stringify(response.data, null, 2)], {
                            type: 'application/json'
                        });
                        
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `epg-settings-${new Date().toISOString().split('T')[0]}.json`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);
                        
                        EPGAdmin.showNotice('success', 'Settings exported successfully');
                    } else {
                        EPGAdmin.showNotice('error', 'Failed to export settings');
                    }
                })
                .fail(function() {
                    EPGAdmin.showNotice('error', 'Network error during export');
                });
        },
        
        /**
         * Import gateway settings
         */
        importSettings: function(file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                try {
                    const settings = JSON.parse(e.target.result);
                    
                    const data = {
                        action: 'epg_import_settings',
                        settings: settings,
                        nonce: epg_admin_ajax.nonce
                    };
                    
                    $.post(epg_admin_ajax.ajax_url, data)
                        .done(function(response) {
                            if (response.success) {
                                EPGAdmin.showNotice('success', 'Settings imported successfully');
                                setTimeout(() => location.reload(), 2000);
                            } else {
                                EPGAdmin.showNotice('error', response.data || 'Failed to import settings');
                            }
                        })
                        .fail(function() {
                            EPGAdmin.showNotice('error', 'Network error during import');
                        });
                        
                } catch (error) {
                    EPGAdmin.showNotice('error', 'Invalid settings file format');
                }
            };
            
            reader.readAsText(file);
        }
    };
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        EPGAdmin.init();
        
        // Expose EPGAdmin globally for external access
        window.EPGAdmin = EPGAdmin;
    });
    
    /**
     * Handle file import
     */
    $(document).on('change', '.epg-import-file', function() {
        const file = this.files[0];
        if (file) {
            EPGAdmin.importSettings(file);
        }
    });
    
    /**
     * Handle export button
     */
    $(document).on('click', '.epg-export-settings', function(e) {
        e.preventDefault();
        EPGAdmin.exportSettings();
    });
    
})(jQuery);
