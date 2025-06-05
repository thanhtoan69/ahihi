/**
 * Environmental Social Viral Admin JavaScript
 * 
 * Handles all admin interface interactions including analytics charts,
 * bulk actions, real-time updates, and modal dialogs.
 * 
 * @package Environmental_Social_Viral
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    var EnvSocialViralAdmin = {
        
        /**
         * Initialize the admin interface
         */
        init: function() {
            this.bindEvents();
            this.initializeCharts();
            this.initializeDataTables();
            this.setupRealTimeUpdates();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Bulk actions
            $(document).on('change', '#select-all-referrers', this.handleSelectAll);
            $(document).on('change', '.bulk-actions select', this.handleBulkActionChange);
            $(document).on('click', '.bulk-actions .button', this.handleBulkAction);
            
            // Individual actions
            $(document).on('click', '.process-reward', this.handleProcessReward);
            $(document).on('click', '.view-details', this.handleViewDetails);
            
            // Chart controls
            $(document).on('change', '#coefficient-period', this.updateCoefficientChart);
            $(document).on('change', '#trending-period', this.updateTrendingContent);
            $(document).on('click', '#refresh-trending', this.refreshTrendingContent);
            
            // Settings form
            $(document).on('submit', '.env-settings-form', this.handleSettingsSubmit);
            $(document).on('change', '.env-form-group input, .env-form-group select', this.handleSettingChange);
            
            // Export actions
            $(document).on('click', '.export-referral-data', this.exportReferralData);
            $(document).on('click', '.export-viral-metrics', this.exportViralMetrics);
            
            // Modal controls
            $(document).on('click', '.env-modal-close, .env-modal-overlay', this.closeModal);
            $(document).on('click', '.env-modal', function(e) { e.stopPropagation(); });
            
            // Tooltips
            this.initTooltips();
        },
        
        /**
         * Handle select all checkbox
         */
        handleSelectAll: function(e) {
            var isChecked = $(this).prop('checked');
            $('input[name="referrer[]"]').prop('checked', isChecked);
        },
        
        /**
         * Handle bulk action dropdown change
         */
        handleBulkActionChange: function(e) {
            var action = $(this).val();
            var $button = $(this).siblings('.button');
            
            if (action === '') {
                $button.prop('disabled', true);
            } else {
                $button.prop('disabled', false);
                $button.text(EnvSocialViralAdmin.getBulkActionLabel(action));
            }
        },
        
        /**
         * Handle bulk action execution
         */
        handleBulkAction: function(e) {
            e.preventDefault();
            
            var action = $(this).siblings('select').val();
            var selectedItems = $('input[name="referrer[]"]:checked').map(function() {
                return this.value;
            }).get();
            
            if (selectedItems.length === 0) {
                EnvSocialViralAdmin.showNotice(__('Please select at least one item.', 'environmental-social-viral'), 'warning');
                return;
            }
            
            if (!confirm(__('Are you sure you want to perform this action?', 'environmental-social-viral'))) {
                return;
            }
            
            EnvSocialViralAdmin.showSpinner($(this));
            
            $.post(ajaxurl, {
                action: 'env_referral_bulk_action',
                bulk_action: action,
                items: selectedItems,
                nonce: envSocialViralAdmin.nonce
            }).done(function(response) {
                if (response.success) {
                    EnvSocialViralAdmin.showNotice(response.data.message, 'success');
                    location.reload();
                } else {
                    EnvSocialViralAdmin.showNotice(response.data || __('Action failed.', 'environmental-social-viral'), 'error');
                }
            }).fail(function() {
                EnvSocialViralAdmin.showNotice(__('Network error occurred.', 'environmental-social-viral'), 'error');
            }).always(function() {
                EnvSocialViralAdmin.hideSpinner($(this));
            });
        },
        
        /**
         * Handle process reward button
         */
        handleProcessReward: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var userId = $button.data('user-id');
            var pendingAmount = $button.data('pending');
            
            if (pendingAmount <= 0) {
                EnvSocialViralAdmin.showNotice(__('No pending rewards to process.', 'environmental-social-viral'), 'info');
                return;
            }
            
            var message = __('Process reward of $%s for this user?', 'environmental-social-viral').replace('%s', pendingAmount);
            if (!confirm(message)) {
                return;
            }
            
            EnvSocialViralAdmin.showSpinner($button);
            
            $.post(ajaxurl, {
                action: 'env_referral_process_reward',
                user_id: userId,
                nonce: envSocialViralAdmin.nonce
            }).done(function(response) {
                if (response.success) {
                    EnvSocialViralAdmin.showNotice(response.data.message, 'success');
                    $button.text(__('Processed', 'environmental-social-viral')).prop('disabled', true);
                    
                    // Update the UI
                    var $row = $button.closest('tr');
                    $row.find('.rewards-earned').text('$' + response.data.total_rewards);
                } else {
                    EnvSocialViralAdmin.showNotice(response.data || __('Failed to process reward.', 'environmental-social-viral'), 'error');
                }
            }).fail(function() {
                EnvSocialViralAdmin.showNotice(__('Network error occurred.', 'environmental-social-viral'), 'error');
            }).always(function() {
                EnvSocialViralAdmin.hideSpinner($button);
            });
        },
        
        /**
         * Handle view details button
         */
        handleViewDetails: function(e) {
            e.preventDefault();
            
            var userId = $(this).data('user-id');
            EnvSocialViralAdmin.showUserDetailsModal(userId);
        },
        
        /**
         * Show user details modal
         */
        showUserDetailsModal: function(userId) {
            var $modal = EnvSocialViralAdmin.createModal('user-details', __('User Details', 'environmental-social-viral'));
            var $body = $modal.find('.env-modal-body');
            
            $body.html('<div class="env-loading">' + __('Loading user details...', 'environmental-social-viral') + '</div>');
            
            $.post(ajaxurl, {
                action: 'env_get_user_details',
                user_id: userId,
                nonce: envSocialViralAdmin.nonce
            }).done(function(response) {
                if (response.success) {
                    $body.html(EnvSocialViralAdmin.renderUserDetails(response.data));
                } else {
                    $body.html('<p>' + __('Failed to load user details.', 'environmental-social-viral') + '</p>');
                }
            }).fail(function() {
                $body.html('<p>' + __('Network error occurred.', 'environmental-social-viral') + '</p>');
            });
        },
        
        /**
         * Render user details HTML
         */
        renderUserDetails: function(data) {
            var html = '<div class="user-details-content">';
            html += '<div class="user-info">';
            html += '<h4>' + data.display_name + '</h4>';
            html += '<p><strong>' + __('Email:', 'environmental-social-viral') + '</strong> ' + data.user_email + '</p>';
            html += '<p><strong>' + __('Referral Code:', 'environmental-social-viral') + '</strong> <code>' + data.referral_code + '</code></p>';
            html += '<p><strong>' + __('Member Since:', 'environmental-social-viral') + '</strong> ' + data.user_registered + '</p>';
            html += '</div>';
            
            html += '<div class="referral-stats">';
            html += '<h5>' + __('Referral Statistics', 'environmental-social-viral') + '</h5>';
            html += '<div class="stats-grid">';
            html += '<div class="stat-item"><span class="label">' + __('Total Referrals:', 'environmental-social-viral') + '</span><span class="value">' + data.stats.total_referrals + '</span></div>';
            html += '<div class="stat-item"><span class="label">' + __('Successful Conversions:', 'environmental-social-viral') + '</span><span class="value">' + data.stats.conversions + '</span></div>';
            html += '<div class="stat-item"><span class="label">' + __('Conversion Rate:', 'environmental-social-viral') + '</span><span class="value">' + data.stats.conversion_rate + '%</span></div>';
            html += '<div class="stat-item"><span class="label">' + __('Total Earnings:', 'environmental-social-viral') + '</span><span class="value">$' + data.stats.total_earnings + '</span></div>';
            html += '</div>';
            html += '</div>';
            
            if (data.recent_referrals && data.recent_referrals.length > 0) {
                html += '<div class="recent-activity">';
                html += '<h5>' + __('Recent Activity', 'environmental-social-viral') + '</h5>';
                html += '<ul>';
                $.each(data.recent_referrals, function(index, referral) {
                    html += '<li>' + referral.date + ' - ' + referral.description + '</li>';
                });
                html += '</ul>';
                html += '</div>';
            }
            
            html += '</div>';
            return html;
        },
        
        /**
         * Update coefficient chart based on selected period
         */
        updateCoefficientChart: function(e) {
            var period = $(this).val();
            var $chart = $('#coefficientChart');
            
            if ($chart.length === 0) return;
            
            EnvSocialViralAdmin.showChartLoading($chart);
            
            $.post(ajaxurl, {
                action: 'env_get_coefficient_chart_data',
                period: period,
                nonce: envSocialViralAdmin.nonce
            }).done(function(response) {
                if (response.success) {
                    EnvSocialViralAdmin.renderCoefficientChart(response.data);
                }
            }).always(function() {
                EnvSocialViralAdmin.hideChartLoading($chart);
            });
        },
        
        /**
         * Update trending content
         */
        updateTrendingContent: function(e) {
            var period = $(this).val();
            EnvSocialViralAdmin.loadTrendingContent(period);
        },
        
        /**
         * Refresh trending content
         */
        refreshTrendingContent: function(e) {
            e.preventDefault();
            var period = $('#trending-period').val();
            EnvSocialViralAdmin.loadTrendingContent(period, true);
        },
        
        /**
         * Load trending content
         */
        loadTrendingContent: function(period, refresh) {
            var $grid = $('.trending-content-grid');
            var $button = $('#refresh-trending');
            
            if (refresh) {
                EnvSocialViralAdmin.showSpinner($button);
            } else {
                $grid.addClass('env-loading');
            }
            
            $.post(ajaxurl, {
                action: 'env_viral_get_trending',
                period: period,
                refresh: refresh ? 1 : 0,
                nonce: envSocialViralAdmin.nonce
            }).done(function(response) {
                if (response.success) {
                    $grid.html(EnvSocialViralAdmin.renderTrendingContent(response.data));
                }
            }).always(function() {
                $grid.removeClass('env-loading');
                if (refresh) {
                    EnvSocialViralAdmin.hideSpinner($button);
                }
            });
        },
        
        /**
         * Render trending content HTML
         */
        renderTrendingContent: function(content) {
            var html = '';
            
            $.each(content, function(index, item) {
                html += '<div class="trending-item" data-content-id="' + item.content_id + '">';
                html += '<div class="trending-header">';
                html += '<h4><a href="' + item.edit_link + '" target="_blank">' + item.title + '</a></h4>';
                html += '<span class="trending-badge trending-level-' + item.trending_level + '">';
                html += item.trending_label + '</span>';
                html += '</div>';
                
                html += '<div class="trending-metrics">';
                html += '<div class="metric">';
                html += '<span class="metric-label">' + __('Viral Coefficient:', 'environmental-social-viral') + '</span>';
                html += '<span class="metric-value viral-coefficient-' + item.viral_level + '">';
                html += item.viral_coefficient + '</span>';
                html += '</div>';
                html += '<div class="metric">';
                html += '<span class="metric-label">' + __('Shares:', 'environmental-social-viral') + '</span>';
                html += '<span class="metric-value">' + item.shares + '</span>';
                html += '</div>';
                html += '<div class="metric">';
                html += '<span class="metric-label">' + __('Reach:', 'environmental-social-viral') + '</span>';
                html += '<span class="metric-value">' + item.reach + '</span>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            });
            
            return html;
        },
        
        /**
         * Handle settings form submission
         */
        handleSettingsSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var formData = $form.serialize();
            
            EnvSocialViralAdmin.showSpinner($form.find('input[type="submit"]'));
            
            $.post(ajaxurl, formData + '&action=env_referral_update_settings&nonce=' + envSocialViralAdmin.nonce)
            .done(function(response) {
                if (response.success) {
                    EnvSocialViralAdmin.showNotice(response.data.message, 'success');
                } else {
                    EnvSocialViralAdmin.showNotice(response.data || __('Failed to save settings.', 'environmental-social-viral'), 'error');
                }
            }).fail(function() {
                EnvSocialViralAdmin.showNotice(__('Network error occurred.', 'environmental-social-viral'), 'error');
            }).always(function() {
                EnvSocialViralAdmin.hideSpinner($form.find('input[type="submit"]'));
            });
        },
        
        /**
         * Handle individual setting changes
         */
        handleSettingChange: function(e) {
            var $input = $(this);
            var settingName = $input.attr('name');
            var settingValue = $input.val();
            
            // Debounce the auto-save
            clearTimeout($input.data('saveTimeout'));
            $input.data('saveTimeout', setTimeout(function() {
                EnvSocialViralAdmin.autoSaveSetting(settingName, settingValue);
            }, 1000));
        },
        
        /**
         * Auto-save individual setting
         */
        autoSaveSetting: function(name, value) {
            $.post(ajaxurl, {
                action: 'env_auto_save_setting',
                setting_name: name,
                setting_value: value,
                nonce: envSocialViralAdmin.nonce
            }).done(function(response) {
                if (response.success) {
                    EnvSocialViralAdmin.showQuickNotice(__('Saved', 'environmental-social-viral'), 'success');
                }
            });
        },
        
        /**
         * Export referral data
         */
        exportReferralData: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            EnvSocialViralAdmin.showSpinner($button);
            
            $.post(ajaxurl, {
                action: 'env_referral_export_data',
                format: 'csv',
                nonce: envSocialViralAdmin.nonce
            }).done(function(response) {
                if (response.success) {
                    EnvSocialViralAdmin.downloadFile(response.data.url, response.data.filename);
                    EnvSocialViralAdmin.showNotice(__('Export completed successfully.', 'environmental-social-viral'), 'success');
                } else {
                    EnvSocialViralAdmin.showNotice(__('Export failed.', 'environmental-social-viral'), 'error');
                }
            }).always(function() {
                EnvSocialViralAdmin.hideSpinner($button);
            });
        },
        
        /**
         * Export viral metrics
         */
        exportViralMetrics: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            EnvSocialViralAdmin.showSpinner($button);
            
            $.post(ajaxurl, {
                action: 'env_viral_export_metrics',
                format: 'csv',
                nonce: envSocialViralAdmin.nonce
            }).done(function(response) {
                if (response.success) {
                    EnvSocialViralAdmin.downloadFile(response.data.url, response.data.filename);
                    EnvSocialViralAdmin.showNotice(__('Export completed successfully.', 'environmental-social-viral'), 'success');
                } else {
                    EnvSocialViralAdmin.showNotice(__('Export failed.', 'environmental-social-viral'), 'error');
                }
            }).always(function() {
                EnvSocialViralAdmin.hideSpinner($button);
            });
        },
        
        /**
         * Initialize charts
         */
        initializeCharts: function() {
            if (typeof Chart === 'undefined') return;
            
            // Initialize coefficient chart
            if ($('#coefficientChart').length) {
                EnvSocialViralAdmin.initCoefficientChart();
            }
            
            // Initialize virality distribution chart
            if ($('#viralityDistributionChart').length) {
                EnvSocialViralAdmin.initViralityDistributionChart();
            }
        },
        
        /**
         * Initialize coefficient chart
         */
        initCoefficientChart: function() {
            var ctx = document.getElementById('coefficientChart').getContext('2d');
            
            $.post(ajaxurl, {
                action: 'env_get_coefficient_chart_data',
                period: '30days',
                nonce: envSocialViralAdmin.nonce
            }).done(function(response) {
                if (response.success) {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: response.data.labels,
                            datasets: [{
                                label: __('Viral Coefficient', 'environmental-social-viral'),
                                data: response.data.coefficients,
                                borderColor: '#00A32A',
                                backgroundColor: 'rgba(0, 163, 42, 0.1)',
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: __('Viral Coefficient Over Time', 'environmental-social-viral')
                                },
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: __('Coefficient', 'environmental-social-viral')
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: __('Date', 'environmental-social-viral')
                                    }
                                }
                            }
                        }
                    });
                }
            });
        },
        
        /**
         * Initialize virality distribution chart
         */
        initViralityDistributionChart: function() {
            var ctx = document.getElementById('viralityDistributionChart').getContext('2d');
            
            $.post(ajaxurl, {
                action: 'env_get_virality_distribution',
                nonce: envSocialViralAdmin.nonce
            }).done(function(response) {
                if (response.success) {
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: response.data.labels,
                            datasets: [{
                                data: response.data.values,
                                backgroundColor: [
                                    '#00A32A',
                                    '#FFBA00',
                                    '#D63638',
                                    '#72AAEA'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: __('Content Virality Distribution', 'environmental-social-viral')
                                }
                            }
                        }
                    });
                }
            });
        },
        
        /**
         * Initialize data tables
         */
        initializeDataTables: function() {
            if (typeof $.fn.DataTable === 'undefined') return;
            
            $('.env-referrers-table').DataTable({
                pageLength: 25,
                order: [[4, 'desc']], // Sort by conversion rate
                columnDefs: [
                    { orderable: false, targets: [0, 7] } // Checkbox and actions columns
                ]
            });
        },
        
        /**
         * Setup real-time updates
         */
        setupRealTimeUpdates: function() {
            // Update stats every 30 seconds
            setInterval(function() {
                EnvSocialViralAdmin.updateRealtimeStats();
            }, 30000);
        },
        
        /**
         * Update real-time statistics
         */
        updateRealtimeStats: function() {
            $.post(ajaxurl, {
                action: 'env_get_realtime_stats',
                nonce: envSocialViralAdmin.nonce
            }).done(function(response) {
                if (response.success) {
                    EnvSocialViralAdmin.updateStatsDisplay(response.data);
                }
            });
        },
        
        /**
         * Update stats display
         */
        updateStatsDisplay: function(stats) {
            $('.env-stat-number').each(function() {
                var $stat = $(this);
                var statType = $stat.closest('.env-stat-card').data('stat-type');
                
                if (stats[statType] !== undefined) {
                    var currentValue = parseInt($stat.text().replace(/[,\$]/g, ''));
                    var newValue = stats[statType];
                    
                    if (currentValue !== newValue) {
                        EnvSocialViralAdmin.animateNumber($stat, currentValue, newValue);
                    }
                }
            });
        },
        
        /**
         * Animate number change
         */
        animateNumber: function($element, from, to) {
            var steps = 20;
            var stepValue = (to - from) / steps;
            var currentStep = 0;
            
            var timer = setInterval(function() {
                currentStep++;
                var value = Math.round(from + (stepValue * currentStep));
                $element.text(value.toLocaleString());
                
                if (currentStep >= steps) {
                    clearInterval(timer);
                    $element.text(to.toLocaleString());
                }
            }, 50);
        },
        
        /**
         * Utility functions
         */
        
        /**
         * Create modal dialog
         */
        createModal: function(id, title) {
            var modalHtml = '<div class="env-modal-overlay" data-modal="' + id + '">';
            modalHtml += '<div class="env-modal">';
            modalHtml += '<div class="env-modal-header">';
            modalHtml += '<h3 class="env-modal-title">' + title + '</h3>';
            modalHtml += '<button class="env-modal-close">&times;</button>';
            modalHtml += '</div>';
            modalHtml += '<div class="env-modal-body"></div>';
            modalHtml += '<div class="env-modal-footer">';
            modalHtml += '<button class="button env-modal-close">' + __('Close', 'environmental-social-viral') + '</button>';
            modalHtml += '</div>';
            modalHtml += '</div>';
            modalHtml += '</div>';
            
            var $modal = $(modalHtml);
            $('body').append($modal);
            
            return $modal;
        },
        
        /**
         * Close modal
         */
        closeModal: function(e) {
            if ($(e.target).hasClass('env-modal-overlay') || $(e.target).hasClass('env-modal-close')) {
                $(e.target).closest('.env-modal-overlay').remove();
            }
        },
        
        /**
         * Show notification
         */
        showNotice: function(message, type) {
            var $notice = $('<div class="env-admin-notice ' + type + '"><p>' + message + '</p></div>');
            $('.wrap').prepend($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, 5000);
        },
        
        /**
         * Show quick notification
         */
        showQuickNotice: function(message, type) {
            var $notice = $('<div class="env-quick-notice ' + type + '">' + message + '</div>');
            $notice.css({
                position: 'fixed',
                top: '32px',
                right: '20px',
                background: type === 'success' ? '#00A32A' : '#D63638',
                color: 'white',
                padding: '10px 15px',
                borderRadius: '4px',
                zIndex: 100000
            });
            
            $('body').append($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, 2000);
        },
        
        /**
         * Show spinner
         */
        showSpinner: function($element) {
            $element.prop('disabled', true).addClass('env-loading');
            if (!$element.find('.env-spinner').length) {
                $element.append('<span class="env-spinner"></span>');
            }
        },
        
        /**
         * Hide spinner
         */
        hideSpinner: function($element) {
            $element.prop('disabled', false).removeClass('env-loading');
            $element.find('.env-spinner').remove();
        },
        
        /**
         * Show chart loading
         */
        showChartLoading: function($chart) {
            var $container = $chart.closest('.env-chart-wrapper');
            $container.addClass('env-loading');
        },
        
        /**
         * Hide chart loading
         */
        hideChartLoading: function($chart) {
            var $container = $chart.closest('.env-chart-wrapper');
            $container.removeClass('env-loading');
        },
        
        /**
         * Download file
         */
        downloadFile: function(url, filename) {
            var link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        
        /**
         * Get bulk action label
         */
        getBulkActionLabel: function(action) {
            var labels = {
                'approve': __('Approve Selected', 'environmental-social-viral'),
                'reject': __('Reject Selected', 'environmental-social-viral'),
                'process_rewards': __('Process Rewards', 'environmental-social-viral'),
                'delete': __('Delete Selected', 'environmental-social-viral')
            };
            return labels[action] || __('Apply', 'environmental-social-viral');
        },
        
        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $(document).on('mouseenter', '[data-tooltip]', function() {
                var $element = $(this);
                var tooltipText = $element.data('tooltip');
                
                var $tooltip = $('<div class="env-admin-tooltip">' + tooltipText + '</div>');
                $('body').append($tooltip);
                
                var elementOffset = $element.offset();
                var elementWidth = $element.outerWidth();
                var elementHeight = $element.outerHeight();
                var tooltipWidth = $tooltip.outerWidth();
                var tooltipHeight = $tooltip.outerHeight();
                
                var left = elementOffset.left + (elementWidth / 2) - (tooltipWidth / 2);
                var top = elementOffset.top - tooltipHeight - 10;
                
                $tooltip.css({
                    position: 'absolute',
                    left: left + 'px',
                    top: top + 'px',
                    background: '#333',
                    color: 'white',
                    padding: '8px 12px',
                    borderRadius: '4px',
                    fontSize: '12px',
                    zIndex: 100000,
                    opacity: 0
                }).animate({ opacity: 1 }, 200);
                
                $element.data('tooltip-element', $tooltip);
            }).on('mouseleave', '[data-tooltip]', function() {
                var $tooltip = $(this).data('tooltip-element');
                if ($tooltip) {
                    $tooltip.remove();
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        EnvSocialViralAdmin.init();
    });
    
    // Expose to global scope
    window.EnvSocialViralAdmin = EnvSocialViralAdmin;
    
})(jQuery);
