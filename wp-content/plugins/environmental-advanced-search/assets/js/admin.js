/**
 * Environmental Advanced Search - Admin JavaScript
 * Phase 53 - Advanced Search & Filtering
 */

(function($) {
    'use strict';

    /**
     * Admin Application Class
     */
    class EASAdmin {
        constructor() {
            this.currentTab = 'settings';
            this.charts = {};
            this.intervals = {};
            
            this.init();
        }

        /**
         * Initialize admin functionality
         */
        init() {
            this.bindEvents();
            this.initializeTabs();
            this.loadDashboardData();
            this.initializeCharts();
            this.startAutoRefresh();
        }

        /**
         * Bind event handlers
         */
        bindEvents() {
            // Tab switching
            $('.eas-nav-tab a').on('click', (e) => {
                e.preventDefault();
                this.switchTab($(e.target).data('tab'));
            });

            // Settings form
            $('.eas-settings-form').on('submit', (e) => {
                e.preventDefault();
                this.saveSettings($(e.target));
            });

            // Elasticsearch actions
            $('.eas-elasticsearch-test').on('click', () => {
                this.testElasticsearchConnection();
            });

            $('.eas-elasticsearch-reindex').on('click', () => {
                this.reindexElasticsearch();
            });

            $('.eas-elasticsearch-clear').on('click', () => {
                this.clearElasticsearchIndex();
            });

            // Analytics export
            $('.eas-export-analytics').on('click', () => {
                this.exportAnalytics();
            });

            // Tools
            $('.eas-clear-cache').on('click', () => {
                this.clearSearchCache();
            });

            $('.eas-reset-analytics').on('click', () => {
                this.resetAnalytics();
            });

            $('.eas-optimize-database').on('click', () => {
                this.optimizeDatabase();
            });

            // Auto-refresh toggle
            $('.eas-auto-refresh').on('change', (e) => {
                this.toggleAutoRefresh($(e.target).is(':checked'));
            });

            // Date range picker
            $('.eas-date-range').on('change', () => {
                this.updateAnalyticsPeriod();
            });

            // Search weight sliders
            $('.eas-weight-slider').on('input', (e) => {
                this.updateWeightDisplay($(e.target));
            });

            // Facet configuration
            $('.eas-add-facet').on('click', () => {
                this.addFacetRow();
            });

            $(document).on('click', '.eas-remove-facet', (e) => {
                this.removeFacetRow($(e.target));
            });

            // Geolocation settings
            $('.eas-geocoding-provider').on('change', (e) => {
                this.toggleGeocodingSettings($(e.target).val());
            });
        }

        /**
         * Initialize tabs
         */
        initializeTabs() {
            const hash = window.location.hash.replace('#', '');
            if (hash && $('.eas-tab-pane[data-tab="' + hash + '"]').length) {
                this.switchTab(hash);
            } else {
                this.switchTab('settings');
            }
        }

        /**
         * Switch to specific tab
         */
        switchTab(tabId) {
            // Update navigation
            $('.eas-nav-tab').removeClass('active');
            $('.eas-nav-tab a[data-tab="' + tabId + '"]').parent().addClass('active');

            // Update content
            $('.eas-tab-pane').removeClass('active');
            $('.eas-tab-pane[data-tab="' + tabId + '"]').addClass('active');

            // Update URL
            window.location.hash = tabId;
            this.currentTab = tabId;

            // Load tab-specific data
            this.loadTabData(tabId);
        }

        /**
         * Load tab-specific data
         */
        loadTabData(tabId) {
            switch(tabId) {
                case 'analytics':
                    this.loadAnalyticsData();
                    break;
                case 'elasticsearch':
                    this.loadElasticsearchStatus();
                    break;
                case 'tools':
                    this.loadToolsStatus();
                    break;
            }
        }

        /**
         * Load dashboard data
         */
        loadDashboardData() {
            const data = {
                action: 'eas_get_dashboard_data',
                nonce: eas_admin_ajax.nonce
            };

            $.ajax({
                url: eas_admin_ajax.url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.updateDashboardCards(response.data);
                    }
                },
                error: () => {
                    this.showMessage('Failed to load dashboard data', 'error');
                }
            });
        }

        /**
         * Update dashboard cards
         */
        updateDashboardCards(data) {
            Object.keys(data).forEach(key => {
                const $card = $(`.eas-analytics-card[data-metric="${key}"]`);
                if ($card.length) {
                    $card.find('.eas-analytics-card-value').text(data[key].value);
                    
                    const $change = $card.find('.eas-analytics-card-change');
                    if (data[key].change !== undefined) {
                        $change.text(data[key].change + '%');
                        $change.removeClass('positive negative neutral');
                        
                        if (data[key].change > 0) {
                            $change.addClass('positive').prepend('↑ ');
                        } else if (data[key].change < 0) {
                            $change.addClass('negative').prepend('↓ ');
                        } else {
                            $change.addClass('neutral').prepend('- ');
                        }
                    }
                }
            });
        }

        /**
         * Initialize charts
         */
        initializeCharts() {
            // Search trends chart
            this.initSearchTrendsChart();
            
            // Popular searches chart
            this.initPopularSearchesChart();
            
            // Performance metrics chart
            this.initPerformanceChart();
        }

        /**
         * Initialize search trends chart
         */
        initSearchTrendsChart() {
            const ctx = document.getElementById('easSearchTrendsChart');
            if (!ctx) return;

            this.charts.searchTrends = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Search Queries',
                        data: [],
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
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

            this.loadSearchTrendsData();
        }

        /**
         * Initialize popular searches chart
         */
        initPopularSearchesChart() {
            const ctx = document.getElementById('easPopularSearchesChart');
            if (!ctx) return;

            this.charts.popularSearches = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#0073aa', '#00a32a', '#d63638', '#dba617',
                            '#826eb4', '#e65100', '#00695c', '#5d4e75'
                        ]
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

            this.loadPopularSearchesData();
        }

        /**
         * Initialize performance chart
         */
        initPerformanceChart() {
            const ctx = document.getElementById('easPerformanceChart');
            if (!ctx) return;

            this.charts.performance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Average Response Time', 'Cache Hit Rate', 'Success Rate'],
                    datasets: [{
                        label: 'Performance Metrics',
                        data: [0, 0, 0],
                        backgroundColor: ['#0073aa', '#00a32a', '#d63638']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
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

            this.loadPerformanceData();
        }

        /**
         * Load search trends data
         */
        loadSearchTrendsData() {
            const data = {
                action: 'eas_get_search_trends',
                nonce: eas_admin_ajax.nonce,
                period: $('.eas-date-range').val() || '7days'
            };

            $.ajax({
                url: eas_admin_ajax.url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success && this.charts.searchTrends) {
                        this.charts.searchTrends.data.labels = response.data.labels;
                        this.charts.searchTrends.data.datasets[0].data = response.data.values;
                        this.charts.searchTrends.update();
                    }
                }
            });
        }

        /**
         * Load popular searches data
         */
        loadPopularSearchesData() {
            const data = {
                action: 'eas_get_popular_searches',
                nonce: eas_admin_ajax.nonce,
                period: $('.eas-date-range').val() || '7days'
            };

            $.ajax({
                url: eas_admin_ajax.url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success && this.charts.popularSearches) {
                        this.charts.popularSearches.data.labels = response.data.labels;
                        this.charts.popularSearches.data.datasets[0].data = response.data.values;
                        this.charts.popularSearches.update();
                    }
                }
            });
        }

        /**
         * Load performance data
         */
        loadPerformanceData() {
            const data = {
                action: 'eas_get_performance_data',
                nonce: eas_admin_ajax.nonce
            };

            $.ajax({
                url: eas_admin_ajax.url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success && this.charts.performance) {
                        this.charts.performance.data.datasets[0].data = [
                            response.data.avg_response_time,
                            response.data.cache_hit_rate,
                            response.data.success_rate
                        ];
                        this.charts.performance.update();
                    }
                }
            });
        }

        /**
         * Load analytics data
         */
        loadAnalyticsData() {
            this.loadSearchTrendsData();
            this.loadPopularSearchesData();
            this.loadPerformanceData();
            this.loadAnalyticsTable();
        }

        /**
         * Load analytics table
         */
        loadAnalyticsTable() {
            const data = {
                action: 'eas_get_analytics_table',
                nonce: eas_admin_ajax.nonce,
                period: $('.eas-date-range').val() || '7days'
            };

            $.ajax({
                url: eas_admin_ajax.url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.updateAnalyticsTable(response.data);
                    }
                }
            });
        }

        /**
         * Update analytics table
         */
        updateAnalyticsTable(data) {
            const $tbody = $('.eas-analytics-table tbody');
            $tbody.empty();

            data.forEach(row => {
                const $tr = $(`
                    <tr>
                        <td>${row.query}</td>
                        <td>${row.count}</td>
                        <td>${row.results}</td>
                        <td>${row.ctr}%</td>
                        <td>${row.avg_time}ms</td>
                        <td>${row.last_searched}</td>
                    </tr>
                `);
                $tbody.append($tr);
            });
        }

        /**
         * Save settings
         */
        saveSettings($form) {
            const formData = new FormData($form[0]);
            formData.append('action', 'eas_save_settings');
            formData.append('nonce', eas_admin_ajax.nonce);

            $form.addClass('eas-loading');

            $.ajax({
                url: eas_admin_ajax.url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showMessage('Settings saved successfully', 'success');
                    } else {
                        this.showMessage('Failed to save settings: ' + response.data, 'error');
                    }
                },
                error: () => {
                    this.showMessage('Failed to save settings', 'error');
                },
                complete: () => {
                    $form.removeClass('eas-loading');
                }
            });
        }

        /**
         * Test Elasticsearch connection
         */
        testElasticsearchConnection() {
            const $button = $('.eas-elasticsearch-test');
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('Testing...');

            const data = {
                action: 'eas_test_elasticsearch',
                nonce: eas_admin_ajax.nonce
            };

            $.ajax({
                url: eas_admin_ajax.url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.showMessage('Elasticsearch connection successful', 'success');
                        this.updateElasticsearchStatus(response.data);
                    } else {
                        this.showMessage('Elasticsearch connection failed: ' + response.data, 'error');
                    }
                },
                error: () => {
                    this.showMessage('Failed to test Elasticsearch connection', 'error');
                },
                complete: () => {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }

        /**
         * Reindex Elasticsearch
         */
        reindexElasticsearch() {
            if (!confirm('This will reindex all content. Continue?')) {
                return;
            }

            const $button = $('.eas-elasticsearch-reindex');
            const originalText = $button.text();
            
            $button.prop('disabled', true).html('Reindexing... <span class="eas-spinner-inline"></span>');

            const data = {
                action: 'eas_reindex_elasticsearch',
                nonce: eas_admin_ajax.nonce
            };

            $.ajax({
                url: eas_admin_ajax.url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.showMessage('Reindexing completed successfully', 'success');
                        this.startReindexProgress(response.data.job_id);
                    } else {
                        this.showMessage('Reindexing failed: ' + response.data, 'error');
                    }
                },
                error: () => {
                    this.showMessage('Failed to start reindexing', 'error');
                },
                complete: () => {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }

        /**
         * Start reindex progress monitoring
         */
        startReindexProgress(jobId) {
            const $progress = $('.eas-reindex-progress');
            $progress.show();

            const checkProgress = () => {
                $.ajax({
                    url: eas_admin_ajax.url,
                    type: 'POST',
                    data: {
                        action: 'eas_get_reindex_progress',
                        nonce: eas_admin_ajax.nonce,
                        job_id: jobId
                    },
                    success: (response) => {
                        if (response.success) {
                            const progress = response.data.progress;
                            $progress.find('.eas-progress-bar').css('width', progress + '%');
                            $progress.find('.eas-progress-text').text(progress + '%');

                            if (progress < 100) {
                                setTimeout(checkProgress, 2000);
                            } else {
                                $progress.hide();
                                this.loadElasticsearchStatus();
                            }
                        }
                    }
                });
            };

            checkProgress();
        }

        /**
         * Clear Elasticsearch index
         */
        clearElasticsearchIndex() {
            if (!confirm('This will delete all indexed data. Continue?')) {
                return;
            }

            const data = {
                action: 'eas_clear_elasticsearch_index',
                nonce: eas_admin_ajax.nonce
            };

            $.ajax({
                url: eas_admin_ajax.url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.showMessage('Index cleared successfully', 'success');
                        this.loadElasticsearchStatus();
                    } else {
                        this.showMessage('Failed to clear index: ' + response.data, 'error');
                    }
                },
                error: () => {
                    this.showMessage('Failed to clear index', 'error');
                }
            });
        }

        /**
         * Load Elasticsearch status
         */
        loadElasticsearchStatus() {
            const data = {
                action: 'eas_get_elasticsearch_status',
                nonce: eas_admin_ajax.nonce
            };

            $.ajax({
                url: eas_admin_ajax.url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.updateElasticsearchStatus(response.data);
                    }
                }
            });
        }

        /**
         * Update Elasticsearch status display
         */
        updateElasticsearchStatus(data) {
            // Update status indicator
            const $status = $('.eas-elasticsearch-status-indicator');
            $status.removeClass('connected disconnected syncing');
            $status.addClass(data.status).text(data.status.charAt(0).toUpperCase() + data.status.slice(1));

            // Update info items
            Object.keys(data.info || {}).forEach(key => {
                const $item = $(`.eas-elasticsearch-info-item[data-key="${key}"]`);
                if ($item.length) {
                    $item.find('.eas-elasticsearch-info-value').text(data.info[key]);
                }
            });
        }

        /**
         * Export analytics
         */
        exportAnalytics() {
            const format = $('input[name="export_format"]:checked').val() || 'csv';
            const period = $('.eas-date-range').val() || '7days';

            const data = {
                action: 'eas_export_analytics',
                nonce: eas_admin_ajax.nonce,
                format: format,
                period: period
            };

            // Create hidden form for file download
            const $form = $('<form>', {
                method: 'POST',
                action: eas_admin_ajax.url,
                style: 'display: none;'
            });

            Object.keys(data).forEach(key => {
                $form.append($('<input>', {
                    type: 'hidden',
                    name: key,
                    value: data[key]
                }));
            });

            $('body').append($form);
            $form.submit();
            $form.remove();
        }

        /**
         * Clear search cache
         */
        clearSearchCache() {
            const data = {
                action: 'eas_clear_search_cache',
                nonce: eas_admin_ajax.nonce
            };

            $.ajax({
                url: eas_admin_ajax.url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.showMessage('Search cache cleared successfully', 'success');
                    } else {
                        this.showMessage('Failed to clear cache: ' + response.data, 'error');
                    }
                }
            });
        }

        /**
         * Reset analytics
         */
        resetAnalytics() {
            if (!confirm('This will delete all analytics data. Continue?')) {
                return;
            }

            const data = {
                action: 'eas_reset_analytics',
                nonce: eas_admin_ajax.nonce
            };

            $.ajax({
                url: eas_admin_ajax.url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.showMessage('Analytics data reset successfully', 'success');
                        this.loadDashboardData();
                        this.loadAnalyticsData();
                    } else {
                        this.showMessage('Failed to reset analytics: ' + response.data, 'error');
                    }
                }
            });
        }

        /**
         * Optimize database
         */
        optimizeDatabase() {
            const data = {
                action: 'eas_optimize_database',
                nonce: eas_admin_ajax.nonce
            };

            $.ajax({
                url: eas_admin_ajax.url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.showMessage('Database optimized successfully', 'success');
                    } else {
                        this.showMessage('Failed to optimize database: ' + response.data, 'error');
                    }
                }
            });
        }

        /**
         * Start auto-refresh
         */
        startAutoRefresh() {
            this.intervals.dashboard = setInterval(() => {
                if (this.currentTab === 'analytics') {
                    this.loadDashboardData();
                }
            }, 30000); // 30 seconds
        }

        /**
         * Toggle auto-refresh
         */
        toggleAutoRefresh(enabled) {
            if (enabled) {
                this.startAutoRefresh();
            } else {
                Object.values(this.intervals).forEach(interval => {
                    clearInterval(interval);
                });
                this.intervals = {};
            }
        }

        /**
         * Update analytics period
         */
        updateAnalyticsPeriod() {
            this.loadAnalyticsData();
        }

        /**
         * Update weight display
         */
        updateWeightDisplay($slider) {
            const value = $slider.val();
            const $display = $slider.siblings('.eas-weight-display');
            $display.text(value);
        }

        /**
         * Add facet configuration row
         */
        addFacetRow() {
            const $container = $('.eas-facet-rows');
            const $template = $('.eas-facet-row-template');
            const $newRow = $template.clone().removeClass('eas-facet-row-template').show();
            $container.append($newRow);
        }

        /**
         * Remove facet configuration row
         */
        removeFacetRow($button) {
            $button.closest('.eas-facet-row').remove();
        }

        /**
         * Toggle geocoding settings
         */
        toggleGeocodingSettings(provider) {
            $('.eas-geocoding-settings').hide();
            $(`.eas-geocoding-settings[data-provider="${provider}"]`).show();
        }

        /**
         * Load tools status
         */
        loadToolsStatus() {
            const data = {
                action: 'eas_get_tools_status',
                nonce: eas_admin_ajax.nonce
            };

            $.ajax({
                url: eas_admin_ajax.url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.updateToolsStatus(response.data);
                    }
                }
            });
        }

        /**
         * Update tools status
         */
        updateToolsStatus(data) {
            Object.keys(data).forEach(key => {
                const $tool = $(`.eas-tool-card[data-tool="${key}"]`);
                if ($tool.length && data[key].status) {
                    $tool.find('.eas-tool-status').text(data[key].status);
                }
            });
        }

        /**
         * Show admin message
         */
        showMessage(message, type = 'info') {
            const $message = $(`
                <div class="eas-message ${type}">
                    ${message}
                </div>
            `);

            $('.eas-tab-content').prepend($message);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                $message.fadeOut(() => $message.remove());
            }, 5000);
        }
    }

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        if ($('.eas-admin-container').length) {
            window.easAdmin = new EASAdmin();
        }
    });

})(jQuery);
