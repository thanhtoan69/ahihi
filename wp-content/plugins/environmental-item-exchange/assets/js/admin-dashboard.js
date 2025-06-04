/**
 * Admin Dashboard JavaScript for Environmental Item Exchange
 * Handles all admin interface interactions and AJAX calls
 */

(function($) {
    'use strict';

    window.EpAdminDashboard = {
        init: function() {
            this.loadDashboardData();
            this.initCharts();
            this.bindEvents();
        },

        loadDashboardData: function() {
            // Load recent exchanges
            this.ajaxCall('recent_exchanges', function(data) {
                EpAdminDashboard.renderRecentExchanges(data);
            });

            // Load system alerts
            this.ajaxCall('system_alerts', function(data) {
                EpAdminDashboard.renderSystemAlerts(data);
            });

            // Load chart data
            this.ajaxCall('chart_data', function(data) {
                EpAdminDashboard.updateCharts(data);
            });
        },

        initCharts: function() {
            // Initialize empty charts
            this.activityChart = new Chart(document.getElementById('ep-activity-chart'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Daily Exchanges',
                        data: [],
                        borderColor: '#2196F3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            this.categoriesChart = new Chart(document.getElementById('ep-categories-chart'), {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
                            '#4BC0C0', '#FF6384'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        },

        updateCharts: function(data) {
            // Update activity chart
            if (data.activity && data.activity.length > 0) {
                const labels = data.activity.map(item => item.date);
                const values = data.activity.map(item => parseInt(item.count));

                this.activityChart.data.labels = labels;
                this.activityChart.data.datasets[0].data = values;
                this.activityChart.update();
            }

            // Update categories chart
            if (data.categories && data.categories.length > 0) {
                const labels = data.categories.map(item => item.name);
                const values = data.categories.map(item => parseInt(item.count));

                this.categoriesChart.data.labels = labels;
                this.categoriesChart.data.datasets[0].data = values;
                this.categoriesChart.update();
            }
        },

        renderRecentExchanges: function(exchanges) {
            const tbody = $('#ep-recent-exchanges-tbody');
            tbody.empty();

            if (!exchanges || exchanges.length === 0) {
                tbody.append('<tr><td colspan="6">' + epAdmin.strings.loading + '</td></tr>');
                return;
            }

            exchanges.forEach(function(exchange) {
                const row = $(`
                    <tr>
                        <td><a href="post.php?post=${exchange.ID}&action=edit">${exchange.post_title}</a></td>
                        <td>${exchange.display_name}</td>
                        <td><span class="ep-exchange-type ${exchange.exchange_type}">${exchange.exchange_type}</span></td>
                        <td><span class="ep-status ${exchange.exchange_status}">${exchange.exchange_status || 'active'}</span></td>
                        <td>${new Date(exchange.post_date).toLocaleDateString()}</td>
                        <td>
                            <button class="button button-small" onclick="EpAdminDashboard.viewExchange(${exchange.ID})">View</button>
                            <button class="button button-small" onclick="EpAdminDashboard.findMatches(${exchange.ID})">Matches</button>
                        </td>
                    </tr>
                `);
                tbody.append(row);
            });
        },

        renderSystemAlerts: function(alerts) {
            const container = $('#ep-system-alerts');
            container.empty();

            if (!alerts || alerts.length === 0) {
                container.append('<p>' + 'No system alerts' + '</p>');
                return;
            }

            alerts.forEach(function(alert) {
                const alertDiv = $(`
                    <div class="ep-alert ep-alert-${alert.type}">
                        <p>${alert.message}</p>
                    </div>
                `);
                container.append(alertDiv);
            });
        },

        bindEvents: function() {
            // Refresh dashboard button
            $(document).on('click', '#ep-refresh-dashboard', function(e) {
                e.preventDefault();
                EpAdminDashboard.loadDashboardData();
            });

            // System action buttons
            $(document).on('click', '.ep-system-action', function(e) {
                e.preventDefault();
                const action = $(this).data('action');
                if (confirm(epAdmin.strings.confirm)) {
                    EpAdminDashboard.performSystemAction(action);
                }
            });
        },

        viewExchange: function(postId) {
            window.open('post.php?post=' + postId + '&action=edit', '_blank');
        },

        findMatches: function(postId) {
            this.ajaxCall('get_matches', function(data) {
                EpAdminDashboard.showMatchesModal(data);
            }, { post_id: postId });
        },

        showMatchesModal: function(matches) {
            // Create and show matches modal
            const modal = $(`
                <div class="ep-modal-overlay">
                    <div class="ep-modal">
                        <div class="ep-modal-header">
                            <h3>Item Matches</h3>
                            <button class="ep-modal-close">&times;</button>
                        </div>
                        <div class="ep-modal-body">
                            <div class="ep-matches-list"></div>
                        </div>
                    </div>
                </div>
            `);

            const matchesList = modal.find('.ep-matches-list');
            if (matches.matches && matches.matches.length > 0) {
                matches.matches.forEach(function(match) {
                    const matchItem = $(`
                        <div class="ep-match-item">
                            <h4>${match.post.post_title}</h4>
                            <p>Compatibility Score: ${(match.score * 100).toFixed(1)}%</p>
                            <p>Reasons: ${match.reasons.join(', ')}</p>
                        </div>
                    `);
                    matchesList.append(matchItem);
                });
            } else {
                matchesList.append('<p>No matches found</p>');
            }

            $('body').append(modal);

            // Close modal events
            modal.find('.ep-modal-close, .ep-modal-overlay').on('click', function(e) {
                if (e.target === this) {
                    modal.remove();
                }
            });
        },

        performSystemAction: function(action) {
            $.ajax({
                url: epAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ep_admin_system_action',
                    action_type: action,
                    nonce: epAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        if (action === 'regenerate_matches') {
                            EpAdminDashboard.loadDashboardData();
                        }
                    } else {
                        alert(epAdmin.strings.error);
                    }
                },
                error: function() {
                    alert(epAdmin.strings.error);
                }
            });
        },

        ajaxCall: function(dataType, callback, extraData = {}) {
            const data = {
                action: 'ep_admin_get_dashboard_data',
                data_type: dataType,
                nonce: epAdmin.nonce,
                ...extraData
            };

            $.ajax({
                url: epAdmin.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success && callback) {
                        callback(response.data);
                    }
                },
                error: function() {
                    console.error('AJAX error for data type:', dataType);
                }
            });
        }
    };

    // Analytics page functionality
    window.EpAnalytics = {
        init: function() {
            this.bindEvents();
            this.loadDefaultReport();
        },

        bindEvents: function() {
            $('#ep-analytics-filter-form').on('submit', function(e) {
                e.preventDefault();
                EpAnalytics.generateReport();
            });

            $('#ep-export-report').on('click', function(e) {
                e.preventDefault();
                EpAnalytics.exportReport();
            });
        },

        loadDefaultReport: function() {
            this.generateReport();
        },

        generateReport: function() {
            const formData = $('#ep-analytics-filter-form').serialize();
            const contentDiv = $('#ep-analytics-content');
            
            contentDiv.html('<div class="ep-analytics-loading"><p>' + epAdmin.strings.loading + '</p></div>');

            // This would make an AJAX call to generate the report
            // For now, showing a placeholder
            setTimeout(function() {
                contentDiv.html(`
                    <div class="ep-analytics-report">
                        <h3>Analytics Report</h3>
                        <div class="ep-analytics-charts">
                            <canvas id="ep-analytics-chart-1"></canvas>
                            <canvas id="ep-analytics-chart-2"></canvas>
                        </div>
                        <div class="ep-analytics-tables">
                            <table class="widefat striped">
                                <thead>
                                    <tr>
                                        <th>Metric</th>
                                        <th>Value</th>
                                        <th>Change</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Total Exchanges</td>
                                        <td>1,234</td>
                                        <td class="positive">+15%</td>
                                    </tr>
                                    <tr>
                                        <td>Match Success Rate</td>
                                        <td>78.5%</td>
                                        <td class="positive">+2.3%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                `);
            }, 1000);
        },

        exportReport: function() {
            const reportType = $('#ep-report-type').val();
            
            $.ajax({
                url: epAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ep_admin_export_data',
                    export_type: reportType,
                    nonce: epAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        EpAnalytics.downloadCSV(response.data.data, response.data.filename);
                    }
                }
            });
        },

        downloadCSV: function(data, filename) {
            const csv = this.convertToCSV(data);
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            link.click();
            window.URL.revokeObjectURL(url);
        },

        convertToCSV: function(data) {
            if (!data || data.length === 0) return '';
            
            const headers = Object.keys(data[0]);
            const csvHeaders = headers.join(',');
            const csvRows = data.map(row => 
                headers.map(header => `"${row[header] || ''}"`).join(',')
            );
            
            return [csvHeaders, ...csvRows].join('\n');
        }
    };

    // Users management functionality
    window.EpUsersManager = {
        init: function() {
            this.bindEvents();
            this.loadUsers();
        },

        bindEvents: function() {
            $('#ep-users-filter-form').on('submit', function(e) {
                e.preventDefault();
                EpUsersManager.loadUsers();
            });

            $(document).on('click', '.ep-user-action', function(e) {
                e.preventDefault();
                const action = $(this).data('action');
                const userId = $(this).data('user-id');
                EpUsersManager.performUserAction(action, userId);
            });
        },

        loadUsers: function() {
            const tbody = $('#ep-users-tbody');
            tbody.html('<tr><td colspan="7">' + epAdmin.strings.loading + '</td></tr>');

            // Simulate loading users data
            setTimeout(function() {
                tbody.html(`
                    <tr>
                        <td>John Doe</td>
                        <td>4.8/5.0</td>
                        <td>15</td>
                        <td>250</td>
                        <td><span class="ep-status active">Active</span></td>
                        <td>2 days ago</td>
                        <td>
                            <button class="button button-small ep-user-action" data-action="suspend" data-user-id="1">Suspend</button>
                            <button class="button button-small ep-user-action" data-action="reset_rating" data-user-id="1">Reset Rating</button>
                        </td>
                    </tr>
                    <tr>
                        <td>Jane Smith</td>
                        <td>4.2/5.0</td>
                        <td>8</td>
                        <td>180</td>
                        <td><span class="ep-status active">Active</span></td>
                        <td>1 week ago</td>
                        <td>
                            <button class="button button-small ep-user-action" data-action="suspend" data-user-id="2">Suspend</button>
                            <button class="button button-small ep-user-action" data-action="reset_rating" data-user-id="2">Reset Rating</button>
                        </td>
                    </tr>
                `);
            }, 500);
        },

        performUserAction: function(action, userId) {
            if (!confirm(epAdmin.strings.confirm)) {
                return;
            }

            $.ajax({
                url: epAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ep_admin_manage_user',
                    action_type: action,
                    user_id: userId,
                    nonce: epAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        EpUsersManager.loadUsers();
                    } else {
                        alert(epAdmin.strings.error);
                    }
                },
                error: function() {
                    alert(epAdmin.strings.error);
                }
            });
        }
    };

    // Matching engine management
    window.EpMatchingManager = {
        init: function() {
            this.bindEvents();
            this.initWeightSliders();
        },

        bindEvents: function() {
            $('#ep-matching-weights-form').on('submit', function(e) {
                e.preventDefault();
                EpMatchingManager.updateWeights();
            });

            $('#ep-reset-weights').on('click', function(e) {
                e.preventDefault();
                EpMatchingManager.resetWeights();
            });

            $('#ep-optimize-weights').on('click', function(e) {
                e.preventDefault();
                EpMatchingManager.optimizeWeights();
            });
        },

        initWeightSliders: function() {
            $('.ep-weight-control input[type="range"]').on('input', function() {
                const value = parseFloat($(this).val());
                $(this).siblings('.ep-weight-value').text((value * 100).toFixed(1) + '%');
                EpMatchingManager.normalizeWeights();
            });
        },

        normalizeWeights: function() {
            const sliders = $('.ep-weight-control input[type="range"]');
            let total = 0;
            
            sliders.each(function() {
                total += parseFloat($(this).val());
            });

            if (total > 0) {
                sliders.each(function() {
                    const normalizedValue = parseFloat($(this).val()) / total;
                    $(this).val(normalizedValue);
                    $(this).siblings('.ep-weight-value').text((normalizedValue * 100).toFixed(1) + '%');
                });
            }
        },

        updateWeights: function() {
            const formData = $('#ep-matching-weights-form').serialize();
            
            $.ajax({
                url: epAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ep_admin_update_settings',
                    settings: this.getWeightsFromForm(),
                    nonce: epAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert(epAdmin.strings.error);
                    }
                },
                error: function() {
                    alert(epAdmin.strings.error);
                }
            });
        },

        getWeightsFromForm: function() {
            const weights = {};
            $('.ep-weight-control input[type="range"]').each(function() {
                const name = $(this).attr('name').replace('weights[', '').replace(']', '');
                weights['matching_weight_' + name] = $(this).val();
            });
            return weights;
        },

        resetWeights: function() {
            if (!confirm(epAdmin.strings.confirm)) {
                return;
            }

            // Reset to default weights
            const defaultWeights = {
                'weight_category_match': 0.25,
                'weight_location_proximity': 0.20,
                'weight_environmental_impact': 0.15,
                'weight_user_compatibility': 0.15,
                'weight_item_condition': 0.10,
                'weight_value_range': 0.10,
                'weight_urgency': 0.05
            };

            Object.keys(defaultWeights).forEach(function(key) {
                const slider = $('#' + key);
                slider.val(defaultWeights[key]);
                slider.siblings('.ep-weight-value').text((defaultWeights[key] * 100).toFixed(1) + '%');
            });
        },

        optimizeWeights: function() {
            if (!confirm('This will analyze recent matching performance and automatically optimize weights. Continue?')) {
                return;
            }

            $.ajax({
                url: epAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ep_admin_system_action',
                    action_type: 'optimize_weights',
                    nonce: epAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload(); // Reload to show updated weights
                    } else {
                        alert(epAdmin.strings.error);
                    }
                },
                error: function() {
                    alert(epAdmin.strings.error);
                }
            });
        }
    };

    // Settings management
    window.EpSettings = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('#ep-settings-form').on('submit', function(e) {
                e.preventDefault();
                EpSettings.saveSettings();
            });
        },

        saveSettings: function() {
            const formData = {};
            $('#ep-settings-form input, #ep-settings-form select').each(function() {
                const name = $(this).attr('name');
                const value = $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val();
                formData[name] = value;
            });

            $.ajax({
                url: epAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ep_admin_update_settings',
                    settings: formData,
                    nonce: epAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert(epAdmin.strings.error);
                    }
                },
                error: function() {
                    alert(epAdmin.strings.error);
                }
            });
        }
    };

    // System health monitoring
    window.EpSystemHealth = {
        init: function() {
            this.loadSystemLogs();
            this.bindEvents();
        },

        bindEvents: function() {
            $('.ep-health-check button').on('click', function() {
                const action = $(this).attr('onclick');
                if (action) {
                    eval(action);
                }
            });
        },

        loadSystemLogs: function() {
            const logsContainer = $('#ep-system-logs');
            
            // Simulate loading system logs
            setTimeout(function() {
                logsContainer.html(`
                    <div class="ep-log-entry">
                        <span class="ep-log-time">${new Date().toLocaleTimeString()}</span>
                        <span class="ep-log-level info">INFO</span>
                        <span class="ep-log-message">Matching engine updated successfully</span>
                    </div>
                    <div class="ep-log-entry">
                        <span class="ep-log-time">${new Date().toLocaleTimeString()}</span>
                        <span class="ep-log-level warning">WARNING</span>
                        <span class="ep-log-message">High number of pending matches detected</span>
                    </div>
                    <div class="ep-log-entry">
                        <span class="ep-log-time">${new Date().toLocaleTimeString()}</span>
                        <span class="ep-log-level info">INFO</span>
                        <span class="ep-log-message">Database optimization completed</span>
                    </div>
                `);
            }, 500);
        }
    };

})(jQuery);
