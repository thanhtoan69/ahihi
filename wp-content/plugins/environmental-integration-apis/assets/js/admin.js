/**
 * Environmental Integration APIs - Admin JavaScript
 * Handles all admin dashboard functionality, API testing, and AJAX interactions
 */

(function($) {
    'use strict';

    // Global admin object
    window.EIA_Admin = {
        initialized: false,
        currentTab: 'dashboard',
        charts: {},
        refreshIntervals: {},
        
        // Initialize admin functionality
        init: function() {
            if (this.initialized) return;
            
            console.log('Initializing Environmental Integration APIs Admin');
            
            this.bindEvents();
            this.initializeDashboard();
            this.initializeApiConfig();
            this.initializeWebhooks();
            this.initializeMonitoring();
            this.initializeLogs();
            this.initializeSettings();
            this.setupAjaxHandlers();
            
            this.initialized = true;
        },
        
        // Bind global events
        bindEvents: function() {
            var self = this;
            
            // Tab navigation
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                self.switchTab($(this).data('tab'));
            });
            
            // Form submissions
            $('form[data-ajax-form]').on('submit', function(e) {
                e.preventDefault();
                self.submitAjaxForm($(this));
            });
            
            // API test buttons
            $('.test-api-btn').on('click', function() {
                self.testApiConnection($(this).data('api'));
            });
            
            // Refresh buttons
            $('.refresh-btn').on('click', function() {
                self.refreshData($(this).data('target'));
            });
            
            // Export buttons
            $('.export-btn').on('click', function() {
                self.exportData($(this).data('type'));
            });
        },
        
        // Switch between admin tabs
        switchTab: function(tab) {
            $('.nav-tab').removeClass('nav-tab-active');
            $('.nav-tab[data-tab="' + tab + '"]').addClass('nav-tab-active');
            
            $('.tab-content').hide();
            $('#tab-' + tab).show();
            
            this.currentTab = tab;
            this.onTabSwitch(tab);
        },
        
        // Handle tab-specific initialization
        onTabSwitch: function(tab) {
            switch(tab) {
                case 'dashboard':
                    this.refreshDashboard();
                    break;
                case 'monitoring':
                    this.refreshMonitoring();
                    break;
                case 'logs':
                    this.refreshLogs();
                    break;
            }
        },
        
        // Dashboard functionality
        initializeDashboard: function() {
            this.setupDashboardCharts();
            this.setupDashboardRefresh();
            this.loadRecentActivity();
        },
        
        setupDashboardCharts: function() {
            var self = this;
            
            // API Usage Chart
            if ($('#api-usage-chart').length) {
                this.charts.apiUsage = new Chart($('#api-usage-chart')[0].getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'API Calls',
                            data: [],
                            borderColor: '#007cba',
                            backgroundColor: 'rgba(0, 124, 186, 0.1)',
                            fill: true
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
            }
            
            // Error Rate Chart
            if ($('#error-rate-chart').length) {
                this.charts.errorRate = new Chart($('#error-rate-chart')[0].getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Success', 'Errors'],
                        datasets: [{
                            data: [0, 0],
                            backgroundColor: ['#46b450', '#dc3232']
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
            }
        },
        
        setupDashboardRefresh: function() {
            var self = this;
            
            // Auto-refresh dashboard every 30 seconds
            this.refreshIntervals.dashboard = setInterval(function() {
                if (self.currentTab === 'dashboard') {
                    self.refreshDashboard();
                }
            }, 30000);
        },
        
        refreshDashboard: function() {
            var self = this;
            
            $.ajax({
                url: eia_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eia_get_dashboard_data',
                    nonce: eia_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateDashboardData(response.data);
                    }
                },
                error: function() {
                    self.showNotice('Failed to refresh dashboard data', 'error');
                }
            });
        },
        
        updateDashboardData: function(data) {
            // Update statistics
            $('#total-api-calls').text(data.stats.total_calls || 0);
            $('#total-errors').text(data.stats.total_errors || 0);
            $('#active-webhooks').text(data.stats.active_webhooks || 0);
            $('#avg-response-time').text((data.stats.avg_response_time || 0) + 'ms');
            
            // Update API status indicators
            $.each(data.api_status, function(api, status) {
                var indicator = $('.api-status[data-api="' + api + '"]');
                indicator.removeClass('status-healthy status-warning status-error')
                        .addClass('status-' + status.status)
                        .find('.status-text').text(status.message);
            });
            
            // Update charts
            if (this.charts.apiUsage && data.usage_chart) {
                this.charts.apiUsage.data.labels = data.usage_chart.labels;
                this.charts.apiUsage.data.datasets[0].data = data.usage_chart.data;
                this.charts.apiUsage.update();
            }
            
            if (this.charts.errorRate && data.error_chart) {
                this.charts.errorRate.data.datasets[0].data = [
                    data.error_chart.success,
                    data.error_chart.errors
                ];
                this.charts.errorRate.update();
            }
            
            // Update recent activity
            if (data.recent_activity) {
                this.updateRecentActivity(data.recent_activity);
            }
        },
        
        loadRecentActivity: function() {
            var self = this;
            
            $.ajax({
                url: eia_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eia_get_recent_activity',
                    nonce: eia_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateRecentActivity(response.data);
                    }
                }
            });
        },
        
        updateRecentActivity: function(activities) {
            var activityList = $('#recent-activity-list');
            activityList.empty();
            
            if (activities.length === 0) {
                activityList.append('<li class="no-activity">No recent activity</li>');
                return;
            }
            
            $.each(activities, function(index, activity) {
                var item = $('<li class="activity-item">');
                item.append('<span class="activity-time">' + activity.time + '</span>');
                item.append('<span class="activity-message">' + activity.message + '</span>');
                item.append('<span class="activity-status status-' + activity.status + '">' + activity.status + '</span>');
                activityList.append(item);
            });
        },
        
        // API Configuration functionality
        initializeApiConfig: function() {
            this.setupApiTesting();
            this.setupConfigSaving();
        },
        
        setupApiTesting: function() {
            var self = this;
            
            $('.test-connection-btn').on('click', function() {
                var apiType = $(this).data('api');
                self.testApiConnection(apiType);
            });
        },
        
        testApiConnection: function(apiType) {
            var self = this;
            var button = $('.test-connection-btn[data-api="' + apiType + '"]');
            var originalText = button.text();
            
            button.prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: eia_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eia_test_api_connection',
                    api_type: apiType,
                    nonce: eia_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('API connection successful: ' + response.data.message, 'success');
                    } else {
                        self.showNotice('API connection failed: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showNotice('Failed to test API connection', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        setupConfigSaving: function() {
            var self = this;
            
            $('#api-config-form').on('submit', function(e) {
                e.preventDefault();
                self.saveApiConfig();
            });
        },
        
        saveApiConfig: function() {
            var self = this;
            var form = $('#api-config-form');
            var submitButton = form.find('input[type="submit"]');
            var originalText = submitButton.val();
            
            submitButton.prop('disabled', true).val('Saving...');
            
            $.ajax({
                url: eia_admin.ajax_url,
                type: 'POST',
                data: form.serialize() + '&action=eia_save_api_config&nonce=' + eia_admin.nonce,
                success: function(response) {
                    if (response.success) {
                        self.showNotice('API configuration saved successfully', 'success');
                    } else {
                        self.showNotice('Failed to save configuration: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showNotice('Failed to save API configuration', 'error');
                },
                complete: function() {
                    submitButton.prop('disabled', false).val(originalText);
                }
            });
        },
        
        // Webhooks functionality
        initializeWebhooks: function() {
            this.setupWebhookModals();
            this.setupWebhookActions();
            this.loadWebhooks();
        },
        
        setupWebhookModals: function() {
            var self = this;
            
            // Create webhook modal
            $('#create-webhook-btn').on('click', function() {
                self.openWebhookModal();
            });
            
            // Edit webhook buttons
            $(document).on('click', '.edit-webhook-btn', function() {
                var webhookId = $(this).data('webhook-id');
                self.openWebhookModal(webhookId);
            });
            
            // Save webhook
            $('#save-webhook-btn').on('click', function() {
                self.saveWebhook();
            });
            
            // Close modal
            $('.modal-close, .modal-backdrop').on('click', function() {
                self.closeWebhookModal();
            });
        },
        
        openWebhookModal: function(webhookId) {
            var modal = $('#webhook-modal');
            var form = $('#webhook-form');
            
            if (webhookId) {
                // Edit mode - load webhook data
                this.loadWebhookData(webhookId);
                modal.find('.modal-title').text('Edit Webhook');
                form.find('input[name="webhook_id"]').val(webhookId);
            } else {
                // Create mode - reset form
                form[0].reset();
                modal.find('.modal-title').text('Create Webhook');
                form.find('input[name="webhook_id"]').val('');
            }
            
            modal.show();
        },
        
        closeWebhookModal: function() {
            $('#webhook-modal').hide();
        },
        
        loadWebhookData: function(webhookId) {
            var self = this;
            
            $.ajax({
                url: eia_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eia_get_webhook',
                    webhook_id: webhookId,
                    nonce: eia_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.populateWebhookForm(response.data);
                    }
                }
            });
        },
        
        populateWebhookForm: function(webhook) {
            var form = $('#webhook-form');
            
            form.find('input[name="name"]').val(webhook.name);
            form.find('input[name="url"]').val(webhook.url);
            form.find('select[name="events"]').val(webhook.events);
            form.find('select[name="status"]').val(webhook.status);
            form.find('textarea[name="description"]').val(webhook.description);
        },
        
        saveWebhook: function() {
            var self = this;
            var form = $('#webhook-form');
            var button = $('#save-webhook-btn');
            var originalText = button.text();
            
            button.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: eia_admin.ajax_url,
                type: 'POST',
                data: form.serialize() + '&action=eia_save_webhook&nonce=' + eia_admin.nonce,
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Webhook saved successfully', 'success');
                        self.closeWebhookModal();
                        self.loadWebhooks();
                    } else {
                        self.showNotice('Failed to save webhook: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showNotice('Failed to save webhook', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        setupWebhookActions: function() {
            var self = this;
            
            // Delete webhook
            $(document).on('click', '.delete-webhook-btn', function() {
                var webhookId = $(this).data('webhook-id');
                if (confirm('Are you sure you want to delete this webhook?')) {
                    self.deleteWebhook(webhookId);
                }
            });
            
            // Test webhook
            $(document).on('click', '.test-webhook-btn', function() {
                var webhookId = $(this).data('webhook-id');
                self.testWebhook(webhookId);
            });
        },
        
        deleteWebhook: function(webhookId) {
            var self = this;
            
            $.ajax({
                url: eia_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eia_delete_webhook',
                    webhook_id: webhookId,
                    nonce: eia_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Webhook deleted successfully', 'success');
                        self.loadWebhooks();
                    } else {
                        self.showNotice('Failed to delete webhook: ' + response.data, 'error');
                    }
                }
            });
        },
        
        testWebhook: function(webhookId) {
            var self = this;
            var button = $('.test-webhook-btn[data-webhook-id="' + webhookId + '"]');
            var originalText = button.text();
            
            button.prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: eia_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eia_test_webhook',
                    webhook_id: webhookId,
                    nonce: eia_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Webhook test successful', 'success');
                    } else {
                        self.showNotice('Webhook test failed: ' + response.data, 'error');
                    }
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        loadWebhooks: function() {
            var self = this;
            
            $.ajax({
                url: eia_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eia_get_webhooks',
                    nonce: eia_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateWebhooksTable(response.data);
                    }
                }
            });
        },
        
        updateWebhooksTable: function(webhooks) {
            var tbody = $('#webhooks-table tbody');
            tbody.empty();
            
            if (webhooks.length === 0) {
                tbody.append('<tr><td colspan="6">No webhooks found</td></tr>');
                return;
            }
            
            $.each(webhooks, function(index, webhook) {
                var row = $('<tr>');
                row.append('<td>' + webhook.name + '</td>');
                row.append('<td>' + webhook.url + '</td>');
                row.append('<td>' + webhook.events.join(', ') + '</td>');
                row.append('<td><span class="status-' + webhook.status + '">' + webhook.status + '</span></td>');
                row.append('<td>' + webhook.created_at + '</td>');
                
                var actions = $('<td class="actions">');
                actions.append('<button class="button button-small edit-webhook-btn" data-webhook-id="' + webhook.id + '">Edit</button>');
                actions.append('<button class="button button-small test-webhook-btn" data-webhook-id="' + webhook.id + '">Test</button>');
                actions.append('<button class="button button-small delete-webhook-btn" data-webhook-id="' + webhook.id + '">Delete</button>');
                row.append(actions);
                
                tbody.append(row);
            });
        },
        
        // Monitoring functionality
        initializeMonitoring: function() {
            this.setupMonitoringCharts();
            this.setupMonitoringRefresh();
        },
        
        setupMonitoringCharts: function() {
            var self = this;
            
            // Response Time Chart
            if ($('#response-time-chart').length) {
                this.charts.responseTime = new Chart($('#response-time-chart')[0].getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Response Time (ms)',
                            data: [],
                            borderColor: '#007cba',
                            backgroundColor: 'rgba(0, 124, 186, 0.1)',
                            fill: true
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
            }
            
            // API Health Chart
            if ($('#api-health-chart').length) {
                this.charts.apiHealth = new Chart($('#api-health-chart')[0].getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['Google Maps', 'Weather', 'Air Quality', 'Social Media'],
                        datasets: [{
                            label: 'Uptime %',
                            data: [0, 0, 0, 0],
                            backgroundColor: ['#46b450', '#46b450', '#46b450', '#46b450']
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }
        },
        
        setupMonitoringRefresh: function() {
            var self = this;
            
            // Auto-refresh monitoring every 60 seconds
            this.refreshIntervals.monitoring = setInterval(function() {
                if (self.currentTab === 'monitoring') {
                    self.refreshMonitoring();
                }
            }, 60000);
        },
        
        refreshMonitoring: function() {
            var self = this;
            
            $.ajax({
                url: eia_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eia_get_monitoring_data',
                    nonce: eia_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateMonitoringData(response.data);
                    }
                }
            });
        },
        
        updateMonitoringData: function(data) {
            // Update API health indicators
            $.each(data.api_health, function(api, health) {
                var indicator = $('.api-health[data-api="' + api + '"]');
                indicator.removeClass('health-good health-warning health-critical')
                        .addClass('health-' + health.status);
                indicator.find('.health-uptime').text(health.uptime + '%');
                indicator.find('.health-response-time').text(health.avg_response_time + 'ms');
                indicator.find('.health-error-rate').text(health.error_rate + '%');
            });
            
            // Update rate limiting info
            if (data.rate_limits) {
                $.each(data.rate_limits, function(api, limits) {
                    var limitsDiv = $('.rate-limits[data-api="' + api + '"]');
                    limitsDiv.find('.current-usage').text(limits.current + '/' + limits.limit);
                    limitsDiv.find('.usage-percentage').text(Math.round((limits.current / limits.limit) * 100) + '%');
                });
            }
            
            // Update charts
            if (this.charts.responseTime && data.response_time_chart) {
                this.charts.responseTime.data.labels = data.response_time_chart.labels;
                this.charts.responseTime.data.datasets[0].data = data.response_time_chart.data;
                this.charts.responseTime.update();
            }
            
            if (this.charts.apiHealth && data.api_health_chart) {
                this.charts.apiHealth.data.datasets[0].data = data.api_health_chart.data;
                this.charts.apiHealth.update();
            }
        },
        
        // Logs functionality
        initializeLogs: function() {
            this.setupLogFilters();
            this.setupLogPagination();
            this.setupLogExport();
        },
        
        setupLogFilters: function() {
            var self = this;
            
            $('#log-filters').on('change', 'select, input', function() {
                self.filterLogs();
            });
            
            $('#search-logs').on('keyup', function() {
                clearTimeout(self.searchTimeout);
                self.searchTimeout = setTimeout(function() {
                    self.filterLogs();
                }, 500);
            });
        },
        
        filterLogs: function() {
            var self = this;
            var filters = {
                api_type: $('#filter-api-type').val(),
                status: $('#filter-status').val(),
                date_from: $('#filter-date-from').val(),
                date_to: $('#filter-date-to').val(),
                search: $('#search-logs').val()
            };
            
            $.ajax({
                url: eia_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eia_filter_logs',
                    filters: filters,
                    nonce: eia_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateLogsTable(response.data.logs);
                        self.updateLogsPagination(response.data.pagination);
                    }
                }
            });
        },
        
        updateLogsTable: function(logs) {
            var tbody = $('#logs-table tbody');
            tbody.empty();
            
            if (logs.length === 0) {
                tbody.append('<tr><td colspan="6">No logs found</td></tr>');
                return;
            }
            
            $.each(logs, function(index, log) {
                var row = $('<tr>');
                row.append('<td>' + log.timestamp + '</td>');
                row.append('<td>' + log.api_type + '</td>');
                row.append('<td>' + log.endpoint + '</td>');
                row.append('<td><span class="status-' + log.status + '">' + log.status + '</span></td>');
                row.append('<td>' + log.response_time + 'ms</td>');
                
                var actions = $('<td>');
                actions.append('<button class="button button-small view-log-btn" data-log-id="' + log.id + '">View</button>');
                row.append(actions);
                
                tbody.append(row);
            });
        },
        
        setupLogPagination: function() {
            var self = this;
            
            $(document).on('click', '.log-pagination a', function(e) {
                e.preventDefault();
                var page = $(this).data('page');
                self.loadLogsPage(page);
            });
        },
        
        loadLogsPage: function(page) {
            var self = this;
            
            $.ajax({
                url: eia_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eia_get_logs_page',
                    page: page,
                    nonce: eia_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateLogsTable(response.data.logs);
                        self.updateLogsPagination(response.data.pagination);
                    }
                }
            });
        },
        
        updateLogsPagination: function(pagination) {
            var paginationDiv = $('.log-pagination');
            paginationDiv.empty();
            
            if (pagination.total_pages <= 1) return;
            
            for (var i = 1; i <= pagination.total_pages; i++) {
                var link = $('<a href="#" data-page="' + i + '">' + i + '</a>');
                if (i === pagination.current_page) {
                    link.addClass('current');
                }
                paginationDiv.append(link);
            }
        },
        
        setupLogExport: function() {
            var self = this;
            
            $('#export-logs-btn').on('click', function() {
                self.exportLogs();
            });
        },
        
        exportLogs: function() {
            var filters = {
                api_type: $('#filter-api-type').val(),
                status: $('#filter-status').val(),
                date_from: $('#filter-date-from').val(),
                date_to: $('#filter-date-to').val(),
                search: $('#search-logs').val()
            };
            
            var form = $('<form method="post">');
            form.append('<input type="hidden" name="action" value="eia_export_logs">');
            form.append('<input type="hidden" name="nonce" value="' + eia_admin.nonce + '">');
            
            $.each(filters, function(key, value) {
                form.append('<input type="hidden" name="filters[' + key + ']" value="' + value + '">');
            });
            
            $('body').append(form);
            form.submit();
            form.remove();
        },
        
        // Settings functionality
        initializeSettings: function() {
            this.setupSettingsForm();
            this.setupCacheClear();
        },
        
        setupSettingsForm: function() {
            var self = this;
            
            $('#settings-form').on('submit', function(e) {
                e.preventDefault();
                self.saveSettings();
            });
        },
        
        saveSettings: function() {
            var self = this;
            var form = $('#settings-form');
            var submitButton = form.find('input[type="submit"]');
            var originalText = submitButton.val();
            
            submitButton.prop('disabled', true).val('Saving...');
            
            $.ajax({
                url: eia_admin.ajax_url,
                type: 'POST',
                data: form.serialize() + '&action=eia_save_settings&nonce=' + eia_admin.nonce,
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Settings saved successfully', 'success');
                    } else {
                        self.showNotice('Failed to save settings: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showNotice('Failed to save settings', 'error');
                },
                complete: function() {
                    submitButton.prop('disabled', false).val(originalText);
                }
            });
        },
        
        setupCacheClear: function() {
            var self = this;
            
            $('#clear-cache-btn').on('click', function() {
                self.clearCache();
            });
        },
        
        clearCache: function() {
            var self = this;
            var button = $('#clear-cache-btn');
            var originalText = button.text();
            
            button.prop('disabled', true).text('Clearing...');
            
            $.ajax({
                url: eia_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eia_clear_cache',
                    nonce: eia_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Cache cleared successfully', 'success');
                    } else {
                        self.showNotice('Failed to clear cache: ' + response.data, 'error');
                    }
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        // AJAX helpers
        setupAjaxHandlers: function() {
            var self = this;
            
            // Global AJAX error handler
            $(document).ajaxError(function(event, xhr, settings, error) {
                if (xhr.status === 0) return; // Aborted requests
                
                console.error('AJAX Error:', error);
                self.showNotice('An error occurred while processing your request', 'error');
            });
            
            // Loading indicator
            $(document).ajaxStart(function() {
                $('.spinner').addClass('is-active');
            }).ajaxStop(function() {
                $('.spinner').removeClass('is-active');
            });
        },
        
        submitAjaxForm: function(form) {
            var self = this;
            var action = form.data('action');
            var submitButton = form.find('input[type="submit"], button[type="submit"]');
            var originalText = submitButton.val() || submitButton.text();
            
            submitButton.prop('disabled', true);
            if (submitButton.is('input')) {
                submitButton.val('Processing...');
            } else {
                submitButton.text('Processing...');
            }
            
            $.ajax({
                url: eia_admin.ajax_url,
                type: 'POST',
                data: form.serialize() + '&action=' + action + '&nonce=' + eia_admin.nonce,
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Operation completed successfully', 'success');
                        if (form.data('reset-on-success')) {
                            form[0].reset();
                        }
                    } else {
                        self.showNotice('Operation failed: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showNotice('Failed to process request', 'error');
                },
                complete: function() {
                    submitButton.prop('disabled', false);
                    if (submitButton.is('input')) {
                        submitButton.val(originalText);
                    } else {
                        submitButton.text(originalText);
                    }
                }
            });
        },
        
        // Utility functions
        refreshData: function(target) {
            switch(target) {
                case 'dashboard':
                    this.refreshDashboard();
                    break;
                case 'monitoring':
                    this.refreshMonitoring();
                    break;
                case 'logs':
                    this.filterLogs();
                    break;
                case 'webhooks':
                    this.loadWebhooks();
                    break;
            }
        },
        
        exportData: function(type) {
            switch(type) {
                case 'logs':
                    this.exportLogs();
                    break;
            }
        },
        
        showNotice: function(message, type) {
            type = type || 'info';
            
            var notice = $('<div class="notice notice-' + type + ' is-dismissible">');
            notice.append('<p>' + message + '</p>');
            notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
            
            $('.wrap h1').after(notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            }, 5000);
            
            // Manual dismiss
            notice.find('.notice-dismiss').on('click', function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            });
        },
        
        // Cleanup
        destroy: function() {
            // Clear intervals
            $.each(this.refreshIntervals, function(key, interval) {
                clearInterval(interval);
            });
            
            // Destroy charts
            $.each(this.charts, function(key, chart) {
                chart.destroy();
            });
            
            this.initialized = false;
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        EIA_Admin.init();
    });
    
    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        EIA_Admin.destroy();
    });

})(jQuery);
