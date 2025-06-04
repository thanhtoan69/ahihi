/**
 * Environmental Platform Petitions - Admin JavaScript
 * Handles all admin dashboard functionality and interactions
 */

(function($) {
    'use strict';

    // Main admin object
    window.EPPAdmin = {
        init: function() {
            this.initCharts();
            this.initDashboardActions();
            this.initBulkActions();
            this.initFilters();
            this.initSettings();
            this.initAnalytics();
            this.initVerification();
            this.setupEventHandlers();
            this.startRealTimeUpdates();
        },

        // Initialize Chart.js charts
        initCharts: function() {
            // Signature trends chart
            if ($('#signature-trends-chart').length) {
                this.createSignatureTrendsChart();
            }

            // Conversion funnel chart
            if ($('#conversion-funnel-chart').length) {
                this.createConversionFunnelChart();
            }

            // Demographics pie chart
            if ($('#demographics-chart').length) {
                this.createDemographicsChart();
            }

            // Performance comparison chart
            if ($('#performance-chart').length) {
                this.createPerformanceChart();
            }

            // Geographic distribution chart
            if ($('#geographic-chart').length) {
                this.createGeographicChart();
            }
        },

        createSignatureTrendsChart: function() {
            const ctx = document.getElementById('signature-trends-chart').getContext('2d');
            const data = JSON.parse($('#signature-trends-data').text() || '[]');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        label: 'Daily Signatures',
                        data: data.signatures || [],
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Verified Signatures',
                        data: data.verified || [],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Signature Trends (Last 30 Days)'
                        },
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        },

        createConversionFunnelChart: function() {
            const ctx = document.getElementById('conversion-funnel-chart').getContext('2d');
            const data = JSON.parse($('#conversion-funnel-data').text() || '{}');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Page Views', 'Form Starts', 'Signatures', 'Verified', 'Shared'],
                    datasets: [{
                        label: 'Conversion Funnel',
                        data: [
                            data.page_views || 0,
                            data.form_starts || 0,
                            data.signatures || 0,
                            data.verified || 0,
                            data.shares || 0
                        ],
                        backgroundColor: [
                            '#e74c3c',
                            '#f39c12',
                            '#f1c40f',
                            '#2ecc71',
                            '#3498db'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Conversion Funnel Analysis'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        },

        createDemographicsChart: function() {
            const ctx = document.getElementById('demographics-chart').getContext('2d');
            const data = JSON.parse($('#demographics-data').text() || '{}');
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(data),
                    datasets: [{
                        data: Object.values(data),
                        backgroundColor: [
                            '#e74c3c',
                            '#3498db',
                            '#2ecc71',
                            '#f39c12',
                            '#9b59b6',
                            '#1abc9c'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Demographics Breakdown'
                        },
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        },

        createPerformanceChart: function() {
            const ctx = document.getElementById('performance-chart').getContext('2d');
            const data = JSON.parse($('#performance-data').text() || '[]');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(item => item.title || 'Untitled'),
                    datasets: [{
                        label: 'Signatures',
                        data: data.map(item => item.signatures || 0),
                        backgroundColor: '#2ecc71'
                    }, {
                        label: 'Goal',
                        data: data.map(item => item.goal || 0),
                        backgroundColor: '#ecf0f1',
                        type: 'line'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Petition Performance Comparison'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        },

        createGeographicChart: function() {
            const ctx = document.getElementById('geographic-chart').getContext('2d');
            const data = JSON.parse($('#geographic-data').text() || '{}');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(data),
                    datasets: [{
                        label: 'Signatures by Location',
                        data: Object.values(data),
                        backgroundColor: '#3498db'
                    }]
                },
                options: {
                    responsive: true,
                    indexAxis: 'y',
                    plugins: {
                        title: {
                            display: true,
                            text: 'Geographic Distribution'
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        },

        // Initialize dashboard actions
        initDashboardActions: function() {
            // Quick actions
            $('.epp-quick-action').on('click', function(e) {
                e.preventDefault();
                const action = $(this).data('action');
                EPPAdmin.handleQuickAction(action);
            });

            // Refresh dashboard
            $('#refresh-dashboard').on('click', function(e) {
                e.preventDefault();
                EPPAdmin.refreshDashboard();
            });

            // Export data
            $('.epp-export-btn').on('click', function(e) {
                e.preventDefault();
                const format = $(this).data('format');
                const type = $(this).data('type');
                EPPAdmin.exportData(type, format);
            });
        },

        handleQuickAction: function(action) {
            const data = {
                action: 'epp_quick_action',
                quick_action: action,
                nonce: petitionAdmin.nonce
            };

            $.post(petitionAdmin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        EPPAdmin.showNotification(response.data.message, 'success');
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                    } else {
                        EPPAdmin.showNotification(response.data.message || 'Action failed', 'error');
                    }
                })
                .fail(function() {
                    EPPAdmin.showNotification('Network error occurred', 'error');
                });
        },

        refreshDashboard: function() {
            $('#refresh-dashboard').addClass('loading');
            
            const data = {
                action: 'epp_refresh_dashboard',
                nonce: petitionAdmin.nonce
            };

            $.post(petitionAdmin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        EPPAdmin.showNotification('Failed to refresh dashboard', 'error');
                    }
                })
                .fail(function() {
                    EPPAdmin.showNotification('Network error occurred', 'error');
                })
                .always(function() {
                    $('#refresh-dashboard').removeClass('loading');
                });
        },

        // Initialize bulk actions
        initBulkActions: function() {
            // Bulk signature actions
            $('#bulk-signature-action').on('click', function(e) {
                e.preventDefault();
                const action = $('#signature-bulk-action').val();
                const selected = $('.signature-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();

                if (!action || selected.length === 0) {
                    EPPAdmin.showNotification('Please select an action and signatures', 'warning');
                    return;
                }

                if (confirm('Are you sure you want to perform this bulk action?')) {
                    EPPAdmin.performBulkAction('signatures', action, selected);
                }
            });

            // Select all checkboxes
            $('#select-all-signatures').on('change', function() {
                $('.signature-checkbox').prop('checked', $(this).prop('checked'));
            });

            // Bulk verification actions
            $('#bulk-verification-action').on('click', function(e) {
                e.preventDefault();
                const action = $('#verification-bulk-action').val();
                const selected = $('.verification-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();

                if (!action || selected.length === 0) {
                    EPPAdmin.showNotification('Please select an action and items', 'warning');
                    return;
                }

                if (confirm('Are you sure you want to perform this bulk action?')) {
                    EPPAdmin.performBulkAction('verification', action, selected);
                }
            });
        },

        performBulkAction: function(type, action, items) {
            const data = {
                action: 'epp_bulk_action',
                bulk_type: type,
                bulk_action: action,
                items: items,
                nonce: petitionAdmin.nonce
            };

            $.post(petitionAdmin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        EPPAdmin.showNotification(response.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        EPPAdmin.showNotification(response.data.message || 'Bulk action failed', 'error');
                    }
                })
                .fail(function() {
                    EPPAdmin.showNotification('Network error occurred', 'error');
                });
        },

        // Initialize filters
        initFilters: function() {
            // Date range filter
            $('.epp-date-filter').on('change', function() {
                EPPAdmin.applyFilters();
            });

            // Status filter
            $('.epp-status-filter').on('change', function() {
                EPPAdmin.applyFilters();
            });

            // Search filter
            $('.epp-search-filter').on('keyup', debounce(function() {
                EPPAdmin.applyFilters();
            }, 500));

            // Clear filters
            $('#clear-filters').on('click', function(e) {
                e.preventDefault();
                $('.epp-date-filter, .epp-status-filter, .epp-search-filter').val('');
                EPPAdmin.applyFilters();
            });
        },

        applyFilters: function() {
            const filters = {
                date_from: $('#date-from').val(),
                date_to: $('#date-to').val(),
                status: $('#status-filter').val(),
                search: $('#search-filter').val()
            };

            const queryString = $.param(filters);
            const url = new URL(window.location);
            
            Object.keys(filters).forEach(key => {
                if (filters[key]) {
                    url.searchParams.set(key, filters[key]);
                } else {
                    url.searchParams.delete(key);
                }
            });

            window.location.href = url.toString();
        },

        // Initialize settings
        initSettings: function() {
            // Settings form
            $('#epp-settings-form').on('submit', function(e) {
                e.preventDefault();
                EPPAdmin.saveSettings($(this));
            });

            // Test email settings
            $('#test-email-settings').on('click', function(e) {
                e.preventDefault();
                EPPAdmin.testEmailSettings();
            });

            // Reset settings
            $('#reset-settings').on('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to reset all settings to defaults?')) {
                    EPPAdmin.resetSettings();
                }
            });
        },

        saveSettings: function($form) {
            const $submitBtn = $form.find('[type="submit"]');
            $submitBtn.prop('disabled', true).text('Saving...');

            const data = $form.serialize() + '&action=epp_save_settings&nonce=' + petitionAdmin.nonce;

            $.post(petitionAdmin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        EPPAdmin.showNotification('Settings saved successfully', 'success');
                    } else {
                        EPPAdmin.showNotification(response.data.message || 'Failed to save settings', 'error');
                    }
                })
                .fail(function() {
                    EPPAdmin.showNotification('Network error occurred', 'error');
                })
                .always(function() {
                    $submitBtn.prop('disabled', false).text('Save Settings');
                });
        },

        testEmailSettings: function() {
            const $btn = $('#test-email-settings');
            $btn.prop('disabled', true).text('Testing...');

            const data = {
                action: 'epp_test_email',
                nonce: petitionAdmin.nonce
            };

            $.post(petitionAdmin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        EPPAdmin.showNotification('Test email sent successfully', 'success');
                    } else {
                        EPPAdmin.showNotification(response.data.message || 'Failed to send test email', 'error');
                    }
                })
                .fail(function() {
                    EPPAdmin.showNotification('Network error occurred', 'error');
                })
                .always(function() {
                    $btn.prop('disabled', false).text('Test Email Settings');
                });
        },

        resetSettings: function() {
            const data = {
                action: 'epp_reset_settings',
                nonce: petitionAdmin.nonce
            };

            $.post(petitionAdmin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        EPPAdmin.showNotification('Settings reset successfully', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        EPPAdmin.showNotification('Failed to reset settings', 'error');
                    }
                })
                .fail(function() {
                    EPPAdmin.showNotification('Network error occurred', 'error');
                });
        },

        // Initialize analytics
        initAnalytics: function() {
            // Date range picker for analytics
            if ($.fn.daterangepicker) {
                $('#analytics-date-range').daterangepicker({
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                    },
                    startDate: moment().subtract(29, 'days'),
                    endDate: moment()
                }, function(start, end) {
                    EPPAdmin.updateAnalytics(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
                });
            }

            // Real-time toggle
            $('#real-time-toggle').on('change', function() {
                if ($(this).prop('checked')) {
                    EPPAdmin.startRealTimeUpdates();
                } else {
                    EPPAdmin.stopRealTimeUpdates();
                }
            });
        },

        updateAnalytics: function(startDate, endDate) {
            const data = {
                action: 'epp_update_analytics',
                start_date: startDate,
                end_date: endDate,
                nonce: petitionAdmin.nonce
            };

            $.post(petitionAdmin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        // Update charts and statistics
                        location.reload();
                    } else {
                        EPPAdmin.showNotification('Failed to update analytics', 'error');
                    }
                })
                .fail(function() {
                    EPPAdmin.showNotification('Network error occurred', 'error');
                });
        },

        // Initialize verification management
        initVerification: function() {
            // Manual verification
            $('.verify-signature').on('click', function(e) {
                e.preventDefault();
                const signatureId = $(this).data('signature-id');
                EPPAdmin.verifySignature(signatureId);
            });

            // Reject verification
            $('.reject-verification').on('click', function(e) {
                e.preventDefault();
                const signatureId = $(this).data('signature-id');
                EPPAdmin.rejectVerification(signatureId);
            });

            // Resend verification
            $('.resend-verification').on('click', function(e) {
                e.preventDefault();
                const signatureId = $(this).data('signature-id');
                EPPAdmin.resendVerification(signatureId);
            });
        },

        verifySignature: function(signatureId) {
            const data = {
                action: 'epp_verify_signature',
                signature_id: signatureId,
                nonce: petitionAdmin.nonce
            };

            $.post(petitionAdmin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        EPPAdmin.showNotification('Signature verified successfully', 'success');
                        $(`[data-signature-id="${signatureId}"]`).closest('tr').find('.status').text('Verified');
                    } else {
                        EPPAdmin.showNotification(response.data.message || 'Failed to verify signature', 'error');
                    }
                })
                .fail(function() {
                    EPPAdmin.showNotification('Network error occurred', 'error');
                });
        },

        rejectVerification: function(signatureId) {
            if (!confirm('Are you sure you want to reject this verification?')) {
                return;
            }

            const data = {
                action: 'epp_reject_verification',
                signature_id: signatureId,
                nonce: petitionAdmin.nonce
            };

            $.post(petitionAdmin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        EPPAdmin.showNotification('Verification rejected', 'success');
                        $(`[data-signature-id="${signatureId}"]`).closest('tr').find('.status').text('Rejected');
                    } else {
                        EPPAdmin.showNotification('Failed to reject verification', 'error');
                    }
                })
                .fail(function() {
                    EPPAdmin.showNotification('Network error occurred', 'error');
                });
        },

        resendVerification: function(signatureId) {
            const data = {
                action: 'epp_resend_verification',
                signature_id: signatureId,
                nonce: petitionAdmin.nonce
            };

            $.post(petitionAdmin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        EPPAdmin.showNotification('Verification email resent', 'success');
                    } else {
                        EPPAdmin.showNotification('Failed to resend verification', 'error');
                    }
                })
                .fail(function() {
                    EPPAdmin.showNotification('Network error occurred', 'error');
                });
        },

        // Export data functionality
        exportData: function(type, format) {
            const data = {
                action: 'epp_export_data',
                export_type: type,
                export_format: format,
                nonce: petitionAdmin.nonce
            };

            // Add current filters
            data.filters = {
                date_from: $('#date-from').val(),
                date_to: $('#date-to').val(),
                status: $('#status-filter').val(),
                search: $('#search-filter').val()
            };

            const form = $('<form>', {
                method: 'POST',
                action: petitionAdmin.ajax_url
            });

            Object.keys(data).forEach(key => {
                if (typeof data[key] === 'object') {
                    Object.keys(data[key]).forEach(subKey => {
                        form.append($('<input>', {
                            type: 'hidden',
                            name: `${key}[${subKey}]`,
                            value: data[key][subKey]
                        }));
                    });
                } else {
                    form.append($('<input>', {
                        type: 'hidden',
                        name: key,
                        value: data[key]
                    }));
                }
            });

            form.appendTo('body').submit().remove();
        },

        // Real-time updates
        startRealTimeUpdates: function() {
            if (this.realTimeInterval) {
                clearInterval(this.realTimeInterval);
            }

            this.realTimeInterval = setInterval(function() {
                EPPAdmin.updateRealTimeData();
            }, 30000); // Update every 30 seconds
        },

        stopRealTimeUpdates: function() {
            if (this.realTimeInterval) {
                clearInterval(this.realTimeInterval);
                this.realTimeInterval = null;
            }
        },

        updateRealTimeData: function() {
            const data = {
                action: 'epp_get_real_time_data',
                nonce: petitionAdmin.nonce
            };

            $.post(petitionAdmin.ajax_url, data)
                .done(function(response) {
                    if (response.success && response.data) {
                        EPPAdmin.updateDashboardStats(response.data);
                    }
                });
        },

        updateDashboardStats: function(data) {
            // Update statistics cards
            $('.total-signatures .stat-number').text(data.total_signatures || 0);
            $('.total-petitions .stat-number').text(data.total_petitions || 0);
            $('.pending-verification .stat-number').text(data.pending_verification || 0);
            $('.active-campaigns .stat-number').text(data.active_campaigns || 0);

            // Update recent activity
            if (data.recent_activity) {
                $('#recent-activity-list').html(data.recent_activity);
            }
        },

        // Setup event handlers
        setupEventHandlers: function() {
            // Modal handlers
            $('.epp-modal-trigger').on('click', function(e) {
                e.preventDefault();
                const target = $(this).data('target');
                $(target).addClass('active');
            });

            $('.epp-modal-close, .epp-modal-overlay').on('click', function() {
                $('.epp-modal').removeClass('active');
            });

            // Tooltip initialization
            if ($.fn.tooltip) {
                $('[data-tooltip]').tooltip();
            }

            // Tab functionality
            $('.epp-tabs .tab-button').on('click', function(e) {
                e.preventDefault();
                const target = $(this).data('tab');
                
                $(this).addClass('active').siblings().removeClass('active');
                $(`.tab-content[data-tab="${target}"]`).addClass('active').siblings('.tab-content').removeClass('active');
            });

            // Accordion functionality
            $('.epp-accordion-header').on('click', function() {
                const $content = $(this).next('.epp-accordion-content');
                const $accordion = $(this).closest('.epp-accordion');
                
                if ($content.is(':visible')) {
                    $content.slideUp();
                    $(this).removeClass('active');
                } else {
                    $accordion.find('.epp-accordion-content').slideUp();
                    $accordion.find('.epp-accordion-header').removeClass('active');
                    $content.slideDown();
                    $(this).addClass('active');
                }
            });
        },

        // Utility functions
        showNotification: function(message, type) {
            type = type || 'info';
            
            const notification = $(`
                <div class="epp-notification epp-notification-${type}">
                    <span class="message">${message}</span>
                    <button class="close">&times;</button>
                </div>
            `);

            $('#epp-notifications').append(notification);

            notification.find('.close').on('click', function() {
                notification.fadeOut(function() {
                    $(this).remove();
                });
            });

            setTimeout(function() {
                notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Utility function for debouncing
    function debounce(func, wait, immediate) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize on petition admin pages
        if ($('body').hasClass('epp-admin-page')) {
            EPPAdmin.init();
        }
    });

})(jQuery);
