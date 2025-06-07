/**
 * Environmental Admin Dashboard - JavaScript Functions
 *
 * @package Environmental_Admin_Dashboard
 * @version 1.0.0
 */

(function($) {
    'use strict';
    
    // Dashboard object to hold all functionality
    const EnvironmentalDashboard = {
        
        // Configuration
        config: {
            refreshInterval: 300000, // 5 minutes
            chartColors: {
                primary: '#10b981',
                secondary: '#059669',
                warning: '#f59e0b',
                danger: '#ef4444',
                info: '#3b82f6'
            },
            apiEndpoint: environmental_dashboard_ajax.ajax_url,
            nonce: environmental_dashboard_ajax.nonce
        },
        
        // Initialize dashboard
        init: function() {
            this.bindEvents();
            this.initializeCharts();
            this.startAutoRefresh();
            this.initializeSortable();
            this.loadUserPreferences();
        },
        
        // Bind event handlers
        bindEvents: function() {
            // Refresh button
            $(document).on('click', '.refresh-dashboard', this.refreshDashboard.bind(this));
            
            // Export buttons
            $(document).on('click', '.export-data', this.exportData.bind(this));
            
            // Customize dashboard
            $(document).on('click', '.customize-dashboard', this.openCustomizeModal.bind(this));
            
            // Modal close
            $(document).on('click', '.dashboard-modal-close, .modal-cancel', this.closeModal.bind(this));
            
            // Save customization
            $(document).on('click', '.save-customization', this.saveCustomization.bind(this));
            
            // Widget actions
            $(document).on('click', '.widget-refresh', this.refreshWidget.bind(this));
            $(document).on('click', '.widget-minimize', this.toggleWidget.bind(this));
            
            // Auto-refresh toggle
            $(document).on('change', '#auto-refresh', this.toggleAutoRefresh.bind(this));
            
            // Layout change
            $(document).on('change', 'input[name="layout"]', this.changeLayout.bind(this));
            
            // Widget visibility toggles
            $(document).on('change', '.widget-toggle input', this.toggleWidgetVisibility.bind(this));
            
            // Quick actions
            $(document).on('click', '.quick-action', this.handleQuickAction.bind(this));
            
            // Chart interactions
            $(document).on('click', '.chart-period', this.changeChartPeriod.bind(this));
        },
        
        // Initialize charts
        initializeCharts: function() {
            this.initActivitiesChart();
            this.initGoalsChart();
            this.initPerformanceChart();
            this.initHealthChart();
        },
        
        // Initialize activities progress chart
        initActivitiesChart: function() {
            const ctx = document.getElementById('activitiesChart');
            if (!ctx) return;
            
            this.activitiesChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'In Progress', 'Pending'],
                    datasets: [{
                        data: [0, 0, 0],
                        backgroundColor: [
                            this.config.chartColors.primary,
                            this.config.chartColors.warning,
                            '#e5e7eb'
                        ],
                        borderWidth: 0,
                        cutout: '70%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            });
        },
        
        // Initialize goals progress chart
        initGoalsChart: function() {
            const ctx = document.getElementById('goalsChart');
            if (!ctx) return;
            
            this.goalsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Progress %',
                        data: [],
                        backgroundColor: this.config.chartColors.primary,
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        },
        
        // Initialize performance analytics chart
        initPerformanceChart: function() {
            const ctx = document.getElementById('performanceChart');
            if (!ctx) return;
            
            this.performanceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Activity Score',
                        data: [],
                        borderColor: this.config.chartColors.primary,
                        backgroundColor: this.config.chartColors.primary + '20',
                        fill: true,
                        tension: 0.4
                    }, {
                        label: 'Environmental Impact',
                        data: [],
                        borderColor: this.config.chartColors.secondary,
                        backgroundColor: this.config.chartColors.secondary + '20',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                    },
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
        },
        
        // Initialize platform health chart
        initHealthChart: function() {
            const ctx = document.getElementById('healthChart');
            if (!ctx) return;
            
            this.healthChart = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: ['Performance', 'Stability', 'Security', 'Usage', 'Growth'],
                    datasets: [{
                        label: 'Platform Health',
                        data: [0, 0, 0, 0, 0],
                        backgroundColor: this.config.chartColors.primary + '30',
                        borderColor: this.config.chartColors.primary,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        },
        
        // Refresh entire dashboard
        refreshDashboard: function() {
            this.showLoading();
            
            $.ajax({
                url: this.config.apiEndpoint,
                type: 'POST',
                data: {
                    action: 'refresh_dashboard',
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateDashboardData(response.data);
                        this.showNotification('Dashboard refreshed successfully', 'success');
                    } else {
                        this.showNotification('Failed to refresh dashboard', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Connection error', 'error');
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        },
        
        // Refresh individual widget
        refreshWidget: function(e) {
            e.preventDefault();
            const widgetId = $(e.currentTarget).closest('.widget-container').data('widget-id');
            const widget = $(e.currentTarget).closest('.widget-container');
            
            this.showWidgetLoading(widget);
            
            $.ajax({
                url: this.config.apiEndpoint,
                type: 'POST',
                data: {
                    action: 'refresh_widget',
                    widget_id: widgetId,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateWidgetData(widgetId, response.data);
                    }
                },
                complete: () => {
                    this.hideWidgetLoading(widget);
                }
            });
        },
        
        // Update dashboard data
        updateDashboardData: function(data) {
            // Update statistics
            if (data.stats) {
                this.updateStatistics(data.stats);
            }
            
            // Update charts
            if (data.charts) {
                this.updateCharts(data.charts);
            }
            
            // Update widgets
            if (data.widgets) {
                this.updateWidgets(data.widgets);
            }
            
            // Update alerts
            if (data.alerts) {
                this.updateAlerts(data.alerts);
            }
        },
        
        // Update statistics cards
        updateStatistics: function(stats) {
            Object.keys(stats).forEach(key => {
                const stat = stats[key];
                const card = $(`.stat-card[data-stat="${key}"]`);
                
                if (card.length) {
                    card.find('.stat-value').text(stat.value);
                    card.find('.stat-change').removeClass('positive negative')
                        .addClass(stat.change >= 0 ? 'positive' : 'negative')
                        .text((stat.change >= 0 ? '+' : '') + stat.change + '%');
                }
            });
        },
        
        // Update charts with new data
        updateCharts: function(chartData) {
            // Update activities chart
            if (chartData.activities && this.activitiesChart) {
                this.activitiesChart.data.datasets[0].data = chartData.activities.data;
                this.activitiesChart.update('active');
            }
            
            // Update goals chart
            if (chartData.goals && this.goalsChart) {
                this.goalsChart.data.labels = chartData.goals.labels;
                this.goalsChart.data.datasets[0].data = chartData.goals.data;
                this.goalsChart.update('active');
            }
            
            // Update performance chart
            if (chartData.performance && this.performanceChart) {
                this.performanceChart.data.labels = chartData.performance.labels;
                this.performanceChart.data.datasets[0].data = chartData.performance.activity_scores;
                this.performanceChart.data.datasets[1].data = chartData.performance.impact_scores;
                this.performanceChart.update('active');
            }
            
            // Update health chart
            if (chartData.health && this.healthChart) {
                this.healthChart.data.datasets[0].data = chartData.health.data;
                this.healthChart.update('active');
            }
        },
        
        // Export dashboard data
        exportData: function(e) {
            e.preventDefault();
            const format = $(e.currentTarget).data('format') || 'csv';
            
            const exportForm = $('<form>', {
                method: 'POST',
                action: this.config.apiEndpoint
            });
            
            exportForm.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'export_dashboard_data'
            }));
            
            exportForm.append($('<input>', {
                type: 'hidden',
                name: 'format',
                value: format
            }));
            
            exportForm.append($('<input>', {
                type: 'hidden',
                name: 'nonce',
                value: this.config.nonce
            }));
            
            $('body').append(exportForm);
            exportForm.submit();
            exportForm.remove();
            
            this.showNotification('Export started...', 'info');
        },
        
        // Open customization modal
        openCustomizeModal: function(e) {
            e.preventDefault();
            $('#dashboard-customize-modal').show();
        },
        
        // Close modal
        closeModal: function(e) {
            if (e.target === e.currentTarget || $(e.target).hasClass('dashboard-modal-close') || $(e.target).hasClass('modal-cancel')) {
                $('.dashboard-modal').hide();
            }
        },
        
        // Save customization settings
        saveCustomization: function(e) {
            e.preventDefault();
            
            const settings = {
                visible_widgets: [],
                layout: $('input[name="layout"]:checked').val(),
                auto_refresh: $('#auto-refresh').is(':checked'),
                refresh_interval: $('#refresh-interval').val()
            };
            
            // Get visible widgets
            $('.widget-toggle input:checked').each(function() {
                settings.visible_widgets.push($(this).val());
            });
            
            $.ajax({
                url: this.config.apiEndpoint,
                type: 'POST',
                data: {
                    action: 'save_dashboard_settings',
                    settings: settings,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.applyCustomization(settings);
                        this.showNotification('Settings saved successfully', 'success');
                        $('.dashboard-modal').hide();
                    } else {
                        this.showNotification('Failed to save settings', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Connection error', 'error');
                }
            });
        },
        
        // Apply customization to dashboard
        applyCustomization: function(settings) {
            // Update layout
            if (settings.layout) {
                this.changeLayout({target: {value: settings.layout}}, false);
            }
            
            // Update widget visibility
            $('.widget-container').each(function() {
                const widgetId = $(this).data('widget-id');
                if (settings.visible_widgets.includes(widgetId)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            
            // Update auto-refresh
            if (settings.auto_refresh) {
                this.startAutoRefresh(settings.refresh_interval);
            } else {
                this.stopAutoRefresh();
            }
        },
        
        // Change dashboard layout
        changeLayout: function(e, save = true) {
            const layout = e.target ? e.target.value : e;
            const container = $('.dashboard-widgets');
            
            container.removeClass('layout-grid layout-list layout-compact');
            container.addClass(`layout-${layout}`);
            
            if (save) {
                this.saveLayoutPreference(layout);
            }
        },
        
        // Toggle widget visibility
        toggleWidgetVisibility: function(e) {
            const widgetId = $(e.target).val();
            const widget = $(`.widget-container[data-widget-id="${widgetId}"]`);
            
            if ($(e.target).is(':checked')) {
                widget.fadeIn();
            } else {
                widget.fadeOut();
            }
        },
        
        // Toggle widget minimize/maximize
        toggleWidget: function(e) {
            e.preventDefault();
            const widget = $(e.currentTarget).closest('.widget-container');
            const content = widget.find('.widget-content');
            const button = $(e.currentTarget);
            
            content.slideToggle('fast', function() {
                button.find('.dashicons')
                    .toggleClass('dashicons-arrow-up-alt2 dashicons-arrow-down-alt2');
            });
        },
        
        // Handle quick actions
        handleQuickAction: function(e) {
            e.preventDefault();
            const action = $(e.currentTarget).data('action');
            
            switch (action) {
                case 'add_activity':
                    window.location.href = 'admin.php?page=environmental-activities&action=add';
                    break;
                case 'add_goal':
                    window.location.href = 'admin.php?page=environmental-goals&action=add';
                    break;
                case 'view_reports':
                    window.location.href = 'admin.php?page=environmental-reports';
                    break;
                case 'manage_users':
                    window.location.href = 'users.php';
                    break;
                case 'settings':
                    window.location.href = 'admin.php?page=environmental-settings';
                    break;
                case 'bulk_operations':
                    window.location.href = 'admin.php?page=environmental-bulk-operations';
                    break;
                default:
                    console.log('Unknown action:', action);
            }
        },
        
        // Change chart time period
        changeChartPeriod: function(e) {
            e.preventDefault();
            const period = $(e.currentTarget).data('period');
            const chartType = $(e.currentTarget).data('chart');
            
            // Update button states
            $(e.currentTarget).siblings().removeClass('active');
            $(e.currentTarget).addClass('active');
            
            // Refresh chart data
            this.refreshChartData(chartType, period);
        },
        
        // Refresh specific chart data
        refreshChartData: function(chartType, period) {
            $.ajax({
                url: this.config.apiEndpoint,
                type: 'POST',
                data: {
                    action: 'get_chart_data',
                    chart_type: chartType,
                    period: period,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const chartData = {};
                        chartData[chartType] = response.data;
                        this.updateCharts(chartData);
                    }
                }
            });
        },
        
        // Initialize sortable widgets
        initializeSortable: function() {
            if (typeof $.fn.sortable === 'function') {
                $('.dashboard-widgets').sortable({
                    items: '.widget-container',
                    handle: '.widget-header',
                    placeholder: 'widget-placeholder',
                    tolerance: 'pointer',
                    update: (event, ui) => {
                        this.saveWidgetOrder();
                    }
                });
            }
        },
        
        // Save widget order
        saveWidgetOrder: function() {
            const order = [];
            $('.widget-container').each(function() {
                order.push($(this).data('widget-id'));
            });
            
            $.ajax({
                url: this.config.apiEndpoint,
                type: 'POST',
                data: {
                    action: 'save_widget_order',
                    order: order,
                    nonce: this.config.nonce
                }
            });
        },
        
        // Start auto-refresh
        startAutoRefresh: function(interval) {
            this.stopAutoRefresh();
            const refreshInterval = interval || this.config.refreshInterval;
            
            this.autoRefreshTimer = setInterval(() => {
                this.refreshDashboard();
            }, refreshInterval);
        },
        
        // Stop auto-refresh
        stopAutoRefresh: function() {
            if (this.autoRefreshTimer) {
                clearInterval(this.autoRefreshTimer);
                this.autoRefreshTimer = null;
            }
        },
        
        // Toggle auto-refresh
        toggleAutoRefresh: function(e) {
            if ($(e.target).is(':checked')) {
                this.startAutoRefresh();
            } else {
                this.stopAutoRefresh();
            }
        },
        
        // Load user preferences
        loadUserPreferences: function() {
            $.ajax({
                url: this.config.apiEndpoint,
                type: 'POST',
                data: {
                    action: 'get_dashboard_preferences',
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.applyCustomization(response.data);
                    }
                }
            });
        },
        
        // Show loading overlay
        showLoading: function() {
            $('.dashboard-widgets').addClass('loading');
            
            if (!$('.loading-overlay').length) {
                $('.dashboard-widgets').append(
                    '<div class="loading-overlay"><div class="loading-spinner"></div></div>'
                );
            }
        },
        
        // Hide loading overlay
        hideLoading: function() {
            $('.dashboard-widgets').removeClass('loading');
            $('.loading-overlay').remove();
        },
        
        // Show widget loading
        showWidgetLoading: function(widget) {
            widget.addClass('loading');
            
            if (!widget.find('.loading-overlay').length) {
                widget.append(
                    '<div class="loading-overlay"><div class="loading-spinner"></div></div>'
                );
            }
        },
        
        // Hide widget loading
        hideWidgetLoading: function(widget) {
            widget.removeClass('loading');
            widget.find('.loading-overlay').remove();
        },
        
        // Show notification
        showNotification: function(message, type = 'info') {
            const notification = $(`
                <div class="dashboard-notification ${type}">
                    <span class="notification-message">${message}</span>
                    <button type="button" class="notification-close">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 5000);
            
            // Manual close
            notification.find('.notification-close').on('click', () => {
                notification.fadeOut(() => notification.remove());
            });
        },
        
        // Update alerts
        updateAlerts: function(alerts) {
            const container = $('.dashboard-alerts');
            container.empty();
            
            alerts.forEach(alert => {
                const alertElement = $(`
                    <div class="alert-card ${alert.type}">
                        <div class="alert-icon">
                            <span class="dashicons dashicons-${this.getAlertIcon(alert.type)}"></span>
                        </div>
                        <div class="alert-content">
                            <div class="alert-title">${alert.title}</div>
                            <div class="alert-message">${alert.message}</div>
                        </div>
                    </div>
                `);
                
                container.append(alertElement);
            });
        },
        
        // Get alert icon
        getAlertIcon: function(type) {
            const icons = {
                success: 'yes-alt',
                warning: 'warning',
                error: 'dismiss',
                info: 'info'
            };
            
            return icons[type] || 'info';
        },
        
        // Save layout preference
        saveLayoutPreference: function(layout) {
            $.ajax({
                url: this.config.apiEndpoint,
                type: 'POST',
                data: {
                    action: 'save_layout_preference',
                    layout: layout,
                    nonce: this.config.nonce
                }
            });
        },
        
        // Update widget data
        updateWidgetData: function(widgetId, data) {
            const widget = $(`.widget-container[data-widget-id="${widgetId}"]`);
            const content = widget.find('.widget-content');
            
            // Update widget content based on widget type
            switch (widgetId) {
                case 'platform-overview':
                    this.updateOverviewWidget(content, data);
                    break;
                case 'activities-progress':
                    this.updateActivitiesWidget(content, data);
                    break;
                case 'environmental-goals':
                    this.updateGoalsWidget(content, data);
                    break;
                case 'performance-analytics':
                    this.updatePerformanceWidget(content, data);
                    break;
                case 'platform-health':
                    this.updateHealthWidget(content, data);
                    break;
                case 'quick-actions':
                    this.updateQuickActionsWidget(content, data);
                    break;
            }
        },
        
        // Update overview widget
        updateOverviewWidget: function(content, data) {
            content.find('.stat-value').each(function() {
                const statType = $(this).data('stat');
                if (data[statType]) {
                    $(this).text(data[statType]);
                }
            });
        },
        
        // Update activities widget
        updateActivitiesWidget: function(content, data) {
            if (data.chart_data) {
                const chartData = {activities: data.chart_data};
                this.updateCharts(chartData);
            }
        },
        
        // Update goals widget
        updateGoalsWidget: function(content, data) {
            if (data.chart_data) {
                const chartData = {goals: data.chart_data};
                this.updateCharts(chartData);
            }
        },
        
        // Update performance widget
        updatePerformanceWidget: function(content, data) {
            if (data.chart_data) {
                const chartData = {performance: data.chart_data};
                this.updateCharts(chartData);
            }
        },
        
        // Update health widget
        updateHealthWidget: function(content, data) {
            if (data.chart_data) {
                const chartData = {health: data.chart_data};
                this.updateCharts(chartData);
            }
        },
        
        // Update quick actions widget
        updateQuickActionsWidget: function(content, data) {
            // Quick actions don't usually need data updates
            // but we can update counts or statuses if needed
            if (data.counts) {
                content.find('.quick-action').each(function() {
                    const action = $(this).data('action');
                    if (data.counts[action]) {
                        $(this).find('.action-count').text(data.counts[action]);
                    }
                });
            }
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        EnvironmentalDashboard.init();
    });
    
    // Make dashboard object globally available
    window.EnvironmentalDashboard = EnvironmentalDashboard;
    
})(jQuery);
