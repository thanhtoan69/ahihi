/**
 * Admin JavaScript for Environmental Content Recommendation Plugin
 * Handles admin dashboard interactions, charts, and analytics
 */

(function($) {
    'use strict';

    // Main ECR Admin Object
    window.ECRAdmin = {
        
        // Configuration
        config: {
            ajaxUrl: ecr_admin_ajax.ajax_url,
            nonce: ecr_admin_ajax.nonce,
            strings: ecr_admin_ajax.strings || {}
        },
        
        // Chart instances
        charts: {},
        
        // Initialize admin functionality
        init: function() {
            this.initializeTabs();
            this.initializeCharts();
            this.initializeRangeSliders();
            this.bindEvents();
            this.loadDashboardData();
        },
        
        // Initialize tab functionality
        initializeTabs: function() {
            $('.ecr-tab-link').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                // Update active states
                $('.ecr-tab-link').removeClass('active');
                $('.ecr-tab-content').removeClass('active');
                
                $(this).addClass('active');
                $(target).addClass('active');
                
                // Save active tab in localStorage
                localStorage.setItem('ecr_active_tab', target);
            });
            
            // Restore active tab
            var activeTab = localStorage.getItem('ecr_active_tab');
            if (activeTab && $(activeTab).length) {
                $('.ecr-tab-link[href="' + activeTab + '"]').click();
            }
        },
        
        // Initialize Chart.js charts
        initializeCharts: function() {
            this.initializePerformanceChart();
            this.initializeTypesChart();
            this.initializeTrendsChart();
            this.initializeDeviceChart();
            this.initializePositionChart();
            this.initializeActivityHeatmap();
            this.initializeCategoryEngagement();
        },
        
        // Performance chart for dashboard
        initializePerformanceChart: function() {
            var ctx = document.getElementById('ecr-performance-chart');
            if (!ctx) return;
            
            this.charts.performance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Impressions',
                        data: [],
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Clicks',
                        data: [],
                        borderColor: '#2196F3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
        },
        
        // Recommendation types distribution chart
        initializeTypesChart: function() {
            var ctx = document.getElementById('ecr-types-chart');
            if (!ctx) return;
            
            this.charts.types = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Personalized', 'Similar Content', 'Trending', 'Environmental'],
                    datasets: [{
                        data: [30, 25, 20, 25],
                        backgroundColor: [
                            '#4CAF50',
                            '#2196F3',
                            '#FF9800',
                            '#8BC34A'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
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
        },
        
        // Analytics trends chart
        initializeTrendsChart: function() {
            var ctx = document.getElementById('ecr-trends-chart');
            if (!ctx) return;
            
            this.charts.trends = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Performance Metric',
                        data: [],
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
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
        
        // Device types chart
        initializeDeviceChart: function() {
            var ctx = document.getElementById('ecr-device-chart');
            if (!ctx) return;
            
            this.charts.device = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Desktop', 'Mobile', 'Tablet'],
                    datasets: [{
                        data: [50, 35, 15],
                        backgroundColor: ['#4CAF50', '#2196F3', '#FF9800'],
                        borderWidth: 2,
                        borderColor: '#fff'
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
        },
        
        // Position performance chart
        initializePositionChart: function() {
            var ctx = document.getElementById('ecr-position-chart');
            if (!ctx) return;
            
            this.charts.position = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Position 1', 'Position 2', 'Position 3', 'Position 4', 'Position 5'],
                    datasets: [{
                        label: 'Click Rate',
                        data: [25, 20, 15, 10, 8],
                        backgroundColor: '#4CAF50',
                        borderColor: '#45a049',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
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
        
        // Activity heatmap chart
        initializeActivityHeatmap: function() {
            var ctx = document.getElementById('ecr-activity-heatmap');
            if (!ctx) return;
            
            // This would typically use a heatmap library or custom implementation
            // For now, we'll use a simple bar chart
            this.charts.heatmap = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'User Activity',
                        data: [120, 150, 180, 200, 160, 90, 80],
                        backgroundColor: '#4CAF50'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },
        
        // Category engagement chart
        initializeCategoryEngagement: function() {
            var ctx = document.getElementById('ecr-category-engagement');
            if (!ctx) return;
            
            this.charts.categoryEngagement = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: ['Environment', 'Technology', 'Health', 'Education', 'News'],
                    datasets: [{
                        label: 'Engagement Score',
                        data: [8, 6, 7, 5, 4],
                        backgroundColor: 'rgba(76, 175, 80, 0.2)',
                        borderColor: '#4CAF50',
                        pointBackgroundColor: '#4CAF50'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 10
                        }
                    }
                }
            });
        },
        
        // Initialize range sliders
        initializeRangeSliders: function() {
            $('.ecr-range-slider').on('input', function() {
                var $slider = $(this);
                var $value = $slider.siblings('.ecr-range-value');
                $value.text($slider.val());
            });
        },
        
        // Bind event handlers
        bindEvents: function() {
            // Quick action buttons
            $('#ecr-force-recommendations').on('click', this.forceRecommendations.bind(this));
            $('#ecr-clear-cache').on('click', this.clearCache.bind(this));
            $('#ecr-export-data').on('click', this.exportData.bind(this));
            
            // Analytics filters
            $('#ecr-date-range, #ecr-metric-type').on('change', this.updateAnalytics.bind(this));
            $('#ecr-refresh-analytics').on('click', this.refreshAnalytics.bind(this));
            
            // Settings form
            $('#ecr-settings-form').on('submit', this.saveSettings.bind(this));
            
            // Real-time updates
            this.startRealTimeUpdates();
        },
        
        // Load dashboard data
        loadDashboardData: function() {
            this.updateDashboardStats();
            this.updateChartData();
        },
        
        // Update dashboard statistics
        updateDashboardStats: function() {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ecr_get_analytics_data',
                    nonce: this.config.nonce,
                    type: 'dashboard_stats'
                },
                success: function(response) {
                    if (response.success && response.data) {
                        this.updateStatCards(response.data);
                    }
                }.bind(this)
            });
        },
        
        // Update stat cards
        updateStatCards: function(data) {
            if (data.total_recommendations) {
                $('#total-recommendations').text(this.formatNumber(data.total_recommendations));
            }
            if (data.avg_session_duration) {
                $('#avg-session-duration').text(this.formatDuration(data.avg_session_duration));
            }
            if (data.active_users) {
                $('#active-users').text(this.formatNumber(data.active_users));
            }
        },
        
        // Update chart data
        updateChartData: function() {
            var dateRange = $('#ecr-date-range').val() || 30;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ecr_get_analytics_data',
                    nonce: this.config.nonce,
                    date_range: dateRange,
                    type: 'chart_data'
                },
                success: function(response) {
                    if (response.success && response.data) {
                        this.updateCharts(response.data);
                    }
                }.bind(this)
            });
        },
        
        // Update charts with new data
        updateCharts: function(data) {
            // Update performance chart
            if (this.charts.performance && data.performance) {
                this.charts.performance.data.labels = data.performance.labels;
                this.charts.performance.data.datasets[0].data = data.performance.impressions;
                this.charts.performance.data.datasets[1].data = data.performance.clicks;
                this.charts.performance.update();
            }
            
            // Update types chart
            if (this.charts.types && data.types) {
                this.charts.types.data.datasets[0].data = data.types.values;
                this.charts.types.update();
            }
            
            // Update trends chart
            if (this.charts.trends && data.trends) {
                this.charts.trends.data.labels = data.trends.labels;
                this.charts.trends.data.datasets[0].data = data.trends.values;
                this.charts.trends.update();
            }
        },
        
        // Force regenerate recommendations
        forceRecommendations: function(e) {
            e.preventDefault();
            
            if (!confirm(this.config.strings.confirm_force_recommendations)) {
                return;
            }
            
            var $btn = $(e.target);
            var originalText = $btn.text();
            
            $btn.prop('disabled', true).text(this.config.strings.loading);
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ecr_force_recommendations',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                    } else {
                        this.showNotification(response.data.message || this.config.strings.error, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotification(this.config.strings.error, 'error');
                }.bind(this),
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },
        
        // Clear cache
        clearCache: function(e) {
            e.preventDefault();
            
            if (!confirm(this.config.strings.confirm_clear_cache)) {
                return;
            }
            
            var $btn = $(e.target);
            var originalText = $btn.text();
            
            $btn.prop('disabled', true).text(this.config.strings.loading);
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ecr_clear_cache',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                    } else {
                        this.showNotification(response.data.message || this.config.strings.error, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotification(this.config.strings.error, 'error');
                }.bind(this),
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },
        
        // Export analytics data
        exportData: function(e) {
            e.preventDefault();
            
            var exportType = prompt('Export type (performance, behavior, recommendations):');
            if (!exportType) return;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ecr_export_data',
                    nonce: this.config.nonce,
                    export_type: exportType
                },
                success: function(response) {
                    if (response.success && response.data.data) {
                        this.downloadCSV(response.data.data, response.data.filename);
                        this.showNotification('Data exported successfully', 'success');
                    } else {
                        this.showNotification('Export failed', 'error');
                    }
                }.bind(this)
            });
        },
        
        // Update analytics based on filters
        updateAnalytics: function() {
            this.updateChartData();
            this.updateAnalyticsTables();
        },
        
        // Refresh analytics
        refreshAnalytics: function(e) {
            e.preventDefault();
            this.updateAnalytics();
            this.showNotification('Analytics refreshed', 'success');
        },
        
        // Update analytics tables
        updateAnalyticsTables: function() {
            var dateRange = $('#ecr-date-range').val() || 30;
            var metricType = $('#ecr-metric-type').val() || 'impressions';
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ecr_get_analytics_data',
                    nonce: this.config.nonce,
                    date_range: dateRange,
                    metric_type: metricType,
                    type: 'table_data'
                },
                success: function(response) {
                    if (response.success && response.data) {
                        this.updateTableData(response.data);
                    }
                }.bind(this)
            });
        },
        
        // Update table data
        updateTableData: function(data) {
            // Update top content table
            if (data.top_content) {
                var html = this.buildTableHTML(data.top_content, ['title', 'impressions', 'clicks', 'ctr']);
                $('#ecr-top-content-table').html(html);
            }
            
            // Update segments table
            if (data.segments) {
                var html = this.buildTableHTML(data.segments, ['segment', 'users', 'engagement', 'conversion']);
                $('#ecr-segments-table').html(html);
            }
            
            // Update behavior table
            if (data.behavior) {
                var html = this.buildTableHTML(data.behavior, ['action', 'count', 'percentage']);
                $('#ecr-behavior-table').html(html);
            }
        },
        
        // Build HTML table
        buildTableHTML: function(data, columns) {
            if (!data || data.length === 0) {
                return '<p>No data available</p>';
            }
            
            var html = '<table class="ecr-data-table"><thead><tr>';
            columns.forEach(function(col) {
                html += '<th>' + this.formatColumnName(col) + '</th>';
            }, this);
            html += '</tr></thead><tbody>';
            
            data.forEach(function(row) {
                html += '<tr>';
                columns.forEach(function(col) {
                    var value = row[col] || '-';
                    if (typeof value === 'number') {
                        value = this.formatNumber(value);
                    }
                    html += '<td>' + value + '</td>';
                }, this);
                html += '</tr>';
            }, this);
            
            html += '</tbody></table>';
            return html;
        },
        
        // Save settings
        saveSettings: function(e) {
            e.preventDefault();
            
            var formData = $(e.target).serialize();
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData + '&action=ecr_update_settings&nonce=' + this.config.nonce,
                success: function(response) {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                    } else {
                        this.showNotification(response.data.message || 'Save failed', 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotification('Save failed', 'error');
                }.bind(this)
            });
        },
        
        // Start real-time updates
        startRealTimeUpdates: function() {
            // Update dashboard every 30 seconds
            setInterval(function() {
                if ($('#ecr-recent-activity-list').length) {
                    this.updateRecentActivity();
                }
            }.bind(this), 30000);
            
            // Update stats every 60 seconds
            setInterval(function() {
                this.updateDashboardStats();
            }.bind(this), 60000);
        },
        
        // Update recent activity
        updateRecentActivity: function() {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ecr_get_analytics_data',
                    nonce: this.config.nonce,
                    type: 'recent_activity'
                },
                success: function(response) {
                    if (response.success && response.data.activity) {
                        $('#ecr-recent-activity-list').html(response.data.activity);
                    }
                }
            });
        },
        
        // Utility functions
        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        },
        
        formatDuration: function(seconds) {
            var minutes = Math.floor(seconds / 60);
            var hours = Math.floor(minutes / 60);
            
            if (hours > 0) {
                return hours + 'h ' + (minutes % 60) + 'm';
            } else {
                return minutes + 'm ' + (seconds % 60) + 's';
            }
        },
        
        formatColumnName: function(name) {
            return name.charAt(0).toUpperCase() + name.slice(1).replace(/_/g, ' ');
        },
        
        downloadCSV: function(data, filename) {
            var csvContent = data;
            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            
            if (link.download !== undefined) {
                var url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        },
        
        showNotification: function(message, type) {
            var $notification = $('<div class="ecr-status-message ecr-status-' + type + '">' + message + '</div>');
            $('.ecr-admin-page h1').after($notification);
            
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        showLoadingOverlay: function() {
            var $overlay = $('<div class="ecr-loading-overlay"><div class="ecr-loading-spinner"></div></div>');
            $('body').append($overlay);
            return $overlay;
        },
        
        hideLoadingOverlay: function($overlay) {
            if ($overlay) {
                $overlay.fadeOut(function() {
                    $(this).remove();
                });
            }
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        ECRAdmin.init();
    });
    
})(jQuery);
