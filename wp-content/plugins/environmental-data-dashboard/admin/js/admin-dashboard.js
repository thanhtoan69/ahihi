/**
 * Environmental Data Dashboard - Admin JavaScript
 * 
 * JavaScript functionality for the admin interface of the Environmental Data Dashboard plugin.
 */

(function($) {
    'use strict';

    // Global variables
    let adminCharts = {};
    let refreshInterval = null;

    // Initialize admin on document ready
    $(document).ready(function() {
        initializeAdmin();
        bindAdminEvents();
        loadAdminData();
        startPeriodicUpdates();
    });

    /**
     * Initialize admin interface
     */
    function initializeAdmin() {
        // Initialize admin navigation
        initializeNavigation();
        
        // Initialize admin charts
        initializeAdminCharts();
        
        // Initialize form handlers
        initializeFormHandlers();
        
        // Initialize data tables
        initializeDataTables();
        
        // Initialize tooltips and help texts
        initializeHelpers();
    }

    /**
     * Initialize navigation
     */
    function initializeNavigation() {
        $('.environmental-admin-nav a').on('click', function(e) {
            e.preventDefault();
            const target = $(this).attr('href');
            
            // Update active nav item
            $('.environmental-admin-nav a').removeClass('active');
            $(this).addClass('active');
            
            // Show/hide content sections
            $('.admin-section').hide();
            $(target).show();
            
            // Resize charts when section becomes visible
            setTimeout(function() {
                resizeAdminCharts();
            }, 100);
        });
    }

    /**
     * Initialize admin charts
     */
    function initializeAdminCharts() {
        // Overview charts
        if ($('#admin-users-chart').length) {
            initializeUsersChart();
        }
        
        if ($('#admin-data-chart').length) {
            initializeDataChart();
        }
        
        if ($('#admin-api-chart').length) {
            initializeApiChart();
        }
        
        if ($('#admin-performance-chart').length) {
            initializePerformanceChart();
        }
    }

    /**
     * Initialize Users Chart
     */
    function initializeUsersChart() {
        const ctx = document.getElementById('admin-users-chart').getContext('2d');
        adminCharts.users = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'New Users',
                    data: [],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    fill: true
                }, {
                    label: 'Active Users',
                    data: [],
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    /**
     * Initialize Data Chart
     */
    function initializeDataChart() {
        const ctx = document.getElementById('admin-data-chart').getContext('2d');
        adminCharts.data = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Air Quality', 'Weather', 'Carbon Footprint', 'User Actions'],
                datasets: [{
                    label: 'Data Points',
                    data: [],
                    backgroundColor: [
                        'rgba(231, 76, 60, 0.8)',
                        'rgba(52, 152, 219, 0.8)',
                        'rgba(46, 204, 113, 0.8)',
                        'rgba(243, 156, 18, 0.8)'
                    ],
                    borderColor: [
                        '#e74c3c',
                        '#3498db',
                        '#2ecc71',
                        '#f39c12'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    /**
     * Initialize API Chart
     */
    function initializeApiChart() {
        const ctx = document.getElementById('admin-api-chart').getContext('2d');
        adminCharts.api = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Successful', 'Failed', 'Cached'],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        '#2ecc71',
                        '#e74c3c',
                        '#f39c12'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    /**
     * Initialize Performance Chart
     */
    function initializePerformanceChart() {
        const ctx = document.getElementById('admin-performance-chart').getContext('2d');
        adminCharts.performance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Response Time (ms)',
                    data: [],
                    borderColor: '#9b59b6',
                    backgroundColor: 'rgba(155, 89, 182, 0.1)',
                    fill: true
                }, {
                    label: 'Memory Usage (MB)',
                    data: [],
                    borderColor: '#e67e22',
                    backgroundColor: 'rgba(230, 126, 34, 0.1)',
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Response Time (ms)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Memory Usage (MB)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    }

    /**
     * Initialize form handlers
     */
    function initializeFormHandlers() {
        // Settings form
        $('#environmental-settings-form').on('submit', function(e) {
            e.preventDefault();
            saveSettings($(this));
        });

        // API configuration forms
        $('.api-config-form').on('submit', function(e) {
            e.preventDefault();
            saveApiConfig($(this));
        });

        // Test API buttons
        $('.api-test-button').on('click', function(e) {
            e.preventDefault();
            testApiConnection($(this));
        });

        // Import/Export forms
        $('#import-data-form').on('submit', function(e) {
            e.preventDefault();
            importData($(this));
        });

        $('#export-data-form').on('submit', function(e) {
            e.preventDefault();
            exportData($(this));
        });

        // User management forms
        $('.user-form').on('submit', function(e) {
            e.preventDefault();
            saveUserSettings($(this));
        });

        // Bulk actions
        $('#bulk-action-form').on('submit', function(e) {
            e.preventDefault();
            performBulkAction($(this));
        });
    }

    /**
     * Initialize data tables
     */
    function initializeDataTables() {
        if ($.fn.DataTable) {
            $('.admin-data-table').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']],
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        }
    }

    /**
     * Initialize helpers
     */
    function initializeHelpers() {
        // Tooltips
        $('[data-tooltip]').each(function() {
            $(this).attr('title', $(this).data('tooltip'));
        });

        // Help toggles
        $('.help-toggle').on('click', function(e) {
            e.preventDefault();
            $(this).next('.help-content').slideToggle();
        });

        // Collapsible sections
        $('.collapsible-header').on('click', function() {
            $(this).next('.collapsible-content').slideToggle();
            $(this).find('.collapse-icon').toggleClass('rotated');
        });
    }

    /**
     * Bind admin events
     */
    function bindAdminEvents() {
        // Refresh data button
        $('.refresh-admin-data').on('click', function(e) {
            e.preventDefault();
            refreshAdminData();
        });

        // Clear cache button
        $('.clear-cache').on('click', function(e) {
            e.preventDefault();
            clearCache();
        });

        // Reset settings button
        $('.reset-settings').on('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to reset all settings to default values?')) {
                resetSettings();
            }
        });

        // Delete data buttons
        $('.delete-data').on('click', function(e) {
            e.preventDefault();
            const dataType = $(this).data('type');
            if (confirm(`Are you sure you want to delete all ${dataType} data? This action cannot be undone.`)) {
                deleteData(dataType);
            }
        });

        // Generate report button
        $('.generate-report').on('click', function(e) {
            e.preventDefault();
            generateReport();
        });

        // Export report button
        $('.export-report').on('click', function(e) {
            e.preventDefault();
            exportReport();
        });
    }

    /**
     * Load admin data
     */
    function loadAdminData() {
        showAdminLoading();
        
        $.ajax({
            url: environmental_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'get_admin_dashboard_data',
                nonce: environmental_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateAdminDashboard(response.data);
                } else {
                    showAdminError('Failed to load admin data');
                }
            },
            error: function() {
                showAdminError('Failed to connect to server');
            },
            complete: function() {
                hideAdminLoading();
            }
        });
    }

    /**
     * Update admin dashboard
     */
    function updateAdminDashboard(data) {
        // Update statistics cards
        if (data.stats) {
            $('.total-users-count').text(data.stats.total_users);
            $('.active-users-count').text(data.stats.active_users);
            $('.total-data-points').text(data.stats.total_data_points);
            $('.api-calls-today').text(data.stats.api_calls_today);
            $('.cache-hit-rate').text(data.stats.cache_hit_rate + '%');
            $('.avg-response-time').text(data.stats.avg_response_time + 'ms');
        }

        // Update charts
        if (data.charts) {
            updateAdminCharts(data.charts);
        }

        // Update recent activity
        if (data.recent_activity) {
            updateRecentActivity(data.recent_activity);
        }

        // Update system status
        if (data.system_status) {
            updateSystemStatus(data.system_status);
        }
    }

    /**
     * Update admin charts
     */
    function updateAdminCharts(chartsData) {
        // Users chart
        if (adminCharts.users && chartsData.users) {
            adminCharts.users.data.labels = chartsData.users.labels;
            adminCharts.users.data.datasets[0].data = chartsData.users.new_users;
            adminCharts.users.data.datasets[1].data = chartsData.users.active_users;
            adminCharts.users.update();
        }

        // Data chart
        if (adminCharts.data && chartsData.data_points) {
            adminCharts.data.data.datasets[0].data = chartsData.data_points;
            adminCharts.data.update();
        }

        // API chart
        if (adminCharts.api && chartsData.api_calls) {
            adminCharts.api.data.datasets[0].data = [
                chartsData.api_calls.successful,
                chartsData.api_calls.failed,
                chartsData.api_calls.cached
            ];
            adminCharts.api.update();
        }

        // Performance chart
        if (adminCharts.performance && chartsData.performance) {
            adminCharts.performance.data.labels = chartsData.performance.labels;
            adminCharts.performance.data.datasets[0].data = chartsData.performance.response_time;
            adminCharts.performance.data.datasets[1].data = chartsData.performance.memory_usage;
            adminCharts.performance.update();
        }
    }

    /**
     * Update recent activity
     */
    function updateRecentActivity(activities) {
        const activityList = $('.recent-activity-list');
        activityList.empty();
        
        activities.forEach(activity => {
            activityList.append(`
                <div class="activity-item">
                    <div class="activity-icon ${activity.type}">
                        <i class="fas fa-${activity.icon}"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">${activity.title}</div>
                        <div class="activity-description">${activity.description}</div>
                        <div class="activity-time">${activity.time}</div>
                    </div>
                </div>
            `);
        });
    }

    /**
     * Update system status
     */
    function updateSystemStatus(status) {
        // Database status
        $('.db-status').removeClass('connected disconnected')
                        .addClass(status.database ? 'connected' : 'disconnected')
                        .text(status.database ? 'Connected' : 'Disconnected');

        // API statuses
        $('.air-quality-api-status').removeClass('connected disconnected')
                                   .addClass(status.air_quality_api ? 'connected' : 'disconnected')
                                   .text(status.air_quality_api ? 'Connected' : 'Disconnected');

        $('.weather-api-status').removeClass('connected disconnected')
                                .addClass(status.weather_api ? 'connected' : 'disconnected')
                                .text(status.weather_api ? 'Connected' : 'Disconnected');

        // Cache status
        $('.cache-status').text(status.cache_enabled ? 'Enabled' : 'Disabled');
        
        // Plugin version
        $('.plugin-version').text(status.plugin_version);
        
        // WordPress version
        $('.wp-version').text(status.wp_version);
    }

    /**
     * Save settings
     */
    function saveSettings(form) {
        const formData = form.serialize();
        
        showFormLoading(form);
        
        $.ajax({
            url: environmental_admin_ajax.ajax_url,
            method: 'POST',
            data: formData + '&action=save_environmental_settings&nonce=' + environmental_admin_ajax.nonce,
            success: function(response) {
                if (response.success) {
                    showAdminSuccess('Settings saved successfully');
                } else {
                    showAdminError(response.data || 'Failed to save settings');
                }
            },
            error: function() {
                showAdminError('Failed to save settings');
            },
            complete: function() {
                hideFormLoading(form);
            }
        });
    }

    /**
     * Save API configuration
     */
    function saveApiConfig(form) {
        const formData = form.serialize();
        
        showFormLoading(form);
        
        $.ajax({
            url: environmental_admin_ajax.ajax_url,
            method: 'POST',
            data: formData + '&action=save_api_config&nonce=' + environmental_admin_ajax.nonce,
            success: function(response) {
                if (response.success) {
                    showAdminSuccess('API configuration saved successfully');
                    updateApiStatus(form, response.data.status);
                } else {
                    showAdminError(response.data || 'Failed to save API configuration');
                }
            },
            error: function() {
                showAdminError('Failed to save API configuration');
            },
            complete: function() {
                hideFormLoading(form);
            }
        });
    }

    /**
     * Test API connection
     */
    function testApiConnection(button) {
        const apiType = button.data('api');
        
        button.prop('disabled', true).html('<span class="loading-spinner"></span> Testing...');
        
        $.ajax({
            url: environmental_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'test_api_connection',
                nonce: environmental_admin_ajax.nonce,
                api_type: apiType
            },
            success: function(response) {
                if (response.success) {
                    showAdminSuccess(`${apiType} API connection successful`);
                    updateApiStatus(button.closest('.api-config-section'), 'connected');
                } else {
                    showAdminError(`${apiType} API connection failed: ${response.data}`);
                    updateApiStatus(button.closest('.api-config-section'), 'disconnected');
                }
            },
            error: function() {
                showAdminError(`Failed to test ${apiType} API connection`);
                updateApiStatus(button.closest('.api-config-section'), 'disconnected');
            },
            complete: function() {
                button.prop('disabled', false).text('Test Connection');
            }
        });
    }

    /**
     * Update API status
     */
    function updateApiStatus(container, status) {
        const statusElement = container.find('.api-status');
        statusElement.removeClass('connected disconnected').addClass(status);
        statusElement.text(status === 'connected' ? 'Connected' : 'Disconnected');
    }

    /**
     * Clear cache
     */
    function clearCache() {
        $.ajax({
            url: environmental_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'clear_environmental_cache',
                nonce: environmental_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAdminSuccess('Cache cleared successfully');
                } else {
                    showAdminError('Failed to clear cache');
                }
            },
            error: function() {
                showAdminError('Failed to clear cache');
            }
        });
    }

    /**
     * Reset settings
     */
    function resetSettings() {
        $.ajax({
            url: environmental_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'reset_environmental_settings',
                nonce: environmental_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAdminSuccess('Settings reset successfully');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAdminError('Failed to reset settings');
                }
            },
            error: function() {
                showAdminError('Failed to reset settings');
            }
        });
    }

    /**
     * Delete data
     */
    function deleteData(dataType) {
        $.ajax({
            url: environmental_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'delete_environmental_data',
                nonce: environmental_admin_ajax.nonce,
                data_type: dataType
            },
            success: function(response) {
                if (response.success) {
                    showAdminSuccess(`${dataType} data deleted successfully`);
                    refreshAdminData();
                } else {
                    showAdminError(`Failed to delete ${dataType} data`);
                }
            },
            error: function() {
                showAdminError(`Failed to delete ${dataType} data`);
            }
        });
    }

    /**
     * Generate report
     */
    function generateReport() {
        const formData = $('#report-form').serialize();
        
        showAdminLoading();
        
        $.ajax({
            url: environmental_admin_ajax.ajax_url,
            method: 'POST',
            data: formData + '&action=generate_environmental_report&nonce=' + environmental_admin_ajax.nonce,
            success: function(response) {
                if (response.success) {
                    displayReport(response.data);
                    showAdminSuccess('Report generated successfully');
                } else {
                    showAdminError('Failed to generate report');
                }
            },
            error: function() {
                showAdminError('Failed to generate report');
            },
            complete: function() {
                hideAdminLoading();
            }
        });
    }

    /**
     * Display report
     */
    function displayReport(reportData) {
        const reportContainer = $('#report-results');
        reportContainer.empty();
        
        if (reportData.charts) {
            reportData.charts.forEach(chart => {
                reportContainer.append(`
                    <div class="report-chart">
                        <h4>${chart.title}</h4>
                        <canvas id="${chart.id}" width="400" height="300"></canvas>
                    </div>
                `);
                
                // Initialize chart
                const ctx = document.getElementById(chart.id).getContext('2d');
                new Chart(ctx, chart.config);
            });
        }
        
        if (reportData.tables) {
            reportData.tables.forEach(table => {
                let tableHtml = `
                    <div class="report-table">
                        <h4>${table.title}</h4>
                        <table class="data-table">
                            <thead><tr>
                `;
                
                table.headers.forEach(header => {
                    tableHtml += `<th>${header}</th>`;
                });
                
                tableHtml += '</tr></thead><tbody>';
                
                table.data.forEach(row => {
                    tableHtml += '<tr>';
                    row.forEach(cell => {
                        tableHtml += `<td>${cell}</td>`;
                    });
                    tableHtml += '</tr>';
                });
                
                tableHtml += '</tbody></table></div>';
                
                reportContainer.append(tableHtml);
            });
        }
    }

    /**
     * Export report
     */
    function exportReport() {
        const format = $('#export-format').val();
        
        $.ajax({
            url: environmental_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'export_environmental_report',
                nonce: environmental_admin_ajax.nonce,
                format: format
            },
            success: function(response) {
                if (response.success) {
                    // Trigger download
                    const link = document.createElement('a');
                    link.href = response.data.download_url;
                    link.download = response.data.filename;
                    link.click();
                    
                    showAdminSuccess('Report exported successfully');
                } else {
                    showAdminError('Failed to export report');
                }
            },
            error: function() {
                showAdminError('Failed to export report');
            }
        });
    }

    /**
     * Refresh admin data
     */
    function refreshAdminData() {
        loadAdminData();
    }

    /**
     * Resize admin charts
     */
    function resizeAdminCharts() {
        Object.keys(adminCharts).forEach(function(key) {
            if (adminCharts[key]) {
                adminCharts[key].resize();
            }
        });
    }

    /**
     * Start periodic updates
     */
    function startPeriodicUpdates() {
        // Update every 5 minutes
        refreshInterval = setInterval(function() {
            loadAdminData();
        }, 5 * 60 * 1000);
    }

    /**
     * Show admin loading
     */
    function showAdminLoading() {
        $('.admin-loading').show();
    }

    /**
     * Hide admin loading
     */
    function hideAdminLoading() {
        $('.admin-loading').hide();
    }

    /**
     * Show form loading
     */
    function showFormLoading(form) {
        form.find('.submit-button').prop('disabled', true)
            .html('<span class="loading-spinner"></span> Saving...');
    }

    /**
     * Hide form loading
     */
    function hideFormLoading(form) {
        form.find('.submit-button').prop('disabled', false)
            .text('Save Settings');
    }

    /**
     * Show admin success message
     */
    function showAdminSuccess(message) {
        showAdminMessage(message, 'success');
    }

    /**
     * Show admin error message
     */
    function showAdminError(message) {
        showAdminMessage(message, 'error');
    }

    /**
     * Show admin message
     */
    function showAdminMessage(message, type) {
        const messageDiv = $(`<div class="alert alert-${type}">${message}</div>`);
        $('.environmental-admin-content').prepend(messageDiv);
        
        setTimeout(function() {
            messageDiv.fadeOut(function() {
                messageDiv.remove();
            });
        }, type === 'success' ? 3000 : 5000);
    }

    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });

    // Expose functions for external use
    window.EnvironmentalAdmin = {
        refreshData: refreshAdminData,
        showSuccess: showAdminSuccess,
        showError: showAdminError,
        testApi: testApiConnection,
        clearCache: clearCache
    };

})(jQuery);
