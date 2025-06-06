/**
 * Environmental Platform Admin Performance Scripts
 */

(function($) {
    'use strict';
    
    class AdminPerformanceManager {
        constructor() {
            this.init();
        }
        
        init() {
            this.setupRealTimeUpdates();
            this.setupPerformanceCharts();
            this.bindEvents();
        }
        
        setupRealTimeUpdates() {
            // Update performance metrics every 30 seconds
            setInterval(() => {
                this.fetchLatestMetrics();
            }, 30000);
        }
        
        fetchLatestMetrics() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_get_latest_metrics',
                    nonce: $('#env_performance_nonce').val()
                },
                success: (response) => {
                    if (response.success) {
                        this.updateMetricsDisplay(response.data);
                    }
                }
            });
        }
        
        updateMetricsDisplay(metrics) {
            $('.stat-box').each(function() {
                const $box = $(this);
                const title = $box.find('h3').text();
                
                switch(title) {
                    case 'Average Load Time':
                        $box.find('p').text(metrics.avg_load_time + 's');
                        break;
                    case 'Memory Usage':
                        $box.find('p').text(metrics.avg_memory_usage + 'MB');
                        break;
                    case 'Average Queries':
                        $box.find('p').text(metrics.avg_query_count);
                        break;
                    case 'Cache Hit Ratio':
                        $box.find('p').text(metrics.avg_cache_hit_ratio + '%');
                        break;
                }
            });
        }
        
        setupPerformanceCharts() {
            // Create performance charts if Chart.js is available
            if (typeof Chart !== 'undefined') {
                this.createLoadTimeChart();
                this.createMemoryUsageChart();
                this.createCacheHitRatioChart();
            }
        }
        
        createLoadTimeChart() {
            const ctx = document.getElementById('loadTimeChart');
            if (!ctx) return;
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Load Time (seconds)',
                        data: [],
                        borderColor: '#2e7d32',
                        backgroundColor: 'rgba(46, 125, 50, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Seconds'
                            }
                        }
                    }
                }
            });
        }
        
        createMemoryUsageChart() {
            const ctx = document.getElementById('memoryUsageChart');
            if (!ctx) return;
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Memory Usage (MB)',
                        data: [],
                        backgroundColor: '#4caf50',
                        borderColor: '#2e7d32',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Megabytes'
                            }
                        }
                    }
                }
            });
        }
        
        createCacheHitRatioChart() {
            const ctx = document.getElementById('cacheHitRatioChart');
            if (!ctx) return;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Cache Hits', 'Cache Misses'],
                    datasets: [{
                        data: [75, 25], // Example data
                        backgroundColor: ['#4caf50', '#ff9800'],
                        borderColor: ['#2e7d32', '#f57c00'],
                        borderWidth: 2
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
        
        bindEvents() {
            // Performance test modal
            $('#run-performance-test').on('click', (e) => {
                e.preventDefault();
                this.showPerformanceTestModal();
            });
            
            // Clear cache confirmation
            $('#clear-all-caches').on('click', (e) => {
                e.preventDefault();
                this.confirmClearCaches();
            });
            
            // Database optimization
            $('#optimize-database').on('click', (e) => {
                e.preventDefault();
                this.optimizeDatabase();
            });
            
            // Export performance report
            $('#export-performance-report').on('click', (e) => {
                e.preventDefault();
                this.exportPerformanceReport();
            });
        }
        
        showPerformanceTestModal() {
            const modal = `
                <div id="performance-test-modal" class="performance-modal">
                    <div class="modal-content">
                        <h2>Performance Test Configuration</h2>
                        <form id="performance-test-form">
                            <label>
                                Test Duration (minutes):
                                <input type="number" name="duration" value="5" min="1" max="60">
                            </label>
                            <label>
                                <input type="checkbox" name="test_mobile" checked>
                                Include mobile performance test
                            </label>
                            <label>
                                <input type="checkbox" name="test_desktop" checked>
                                Include desktop performance test
                            </label>
                            <label>
                                <input type="checkbox" name="test_api" checked>
                                Include API performance test
                            </label>
                            <div class="modal-actions">
                                <button type="submit" class="button button-primary">Start Test</button>
                                <button type="button" class="button" onclick="closePerformanceTestModal()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            
            $('body').append(modal);
            
            $('#performance-test-form').on('submit', (e) => {
                e.preventDefault();
                this.runPerformanceTest($(e.target).serialize());
            });
        }
        
        runPerformanceTest(formData) {
            $('#performance-test-modal').remove();
            
            // Show progress indicator
            this.showProgressIndicator('Running performance test...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_run_comprehensive_performance_test',
                    ...formData,
                    nonce: $('#env_performance_nonce').val()
                },
                success: (response) => {
                    this.hideProgressIndicator();
                    if (response.success) {
                        this.showPerformanceTestResults(response.data);
                    } else {
                        alert('Performance test failed: ' + response.data);
                    }
                },
                error: () => {
                    this.hideProgressIndicator();
                    alert('Performance test failed due to network error');
                }
            });
        }
        
        showPerformanceTestResults(results) {
            const modal = `
                <div id="performance-results-modal" class="performance-modal">
                    <div class="modal-content large">
                        <h2>Performance Test Results</h2>
                        <div class="results-grid">
                            <div class="result-item">
                                <h3>Overall Score</h3>
                                <div class="score ${this.getScoreClass(results.overall_score)}">${results.overall_score}/100</div>
                            </div>
                            <div class="result-item">
                                <h3>Load Time</h3>
                                <div class="metric">${results.load_time}s</div>
                            </div>
                            <div class="result-item">
                                <h3>First Contentful Paint</h3>
                                <div class="metric">${results.fcp}s</div>
                            </div>
                            <div class="result-item">
                                <h3>Largest Contentful Paint</h3>
                                <div class="metric">${results.lcp}s</div>
                            </div>
                        </div>
                        <div class="recommendations">
                            <h3>Recommendations</h3>
                            <ul>
                                ${results.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                            </ul>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="button button-primary" onclick="exportTestResults()">Export Results</button>
                            <button type="button" class="button" onclick="closePerformanceResultsModal()">Close</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modal);
        }
        
        getScoreClass(score) {
            if (score >= 90) return 'excellent';
            if (score >= 70) return 'good';
            if (score >= 50) return 'average';
            return 'poor';
        }
        
        confirmClearCaches() {
            if (confirm('Are you sure you want to clear all caches? This may temporarily slow down your site.')) {
                this.clearAllCaches();
            }
        }
        
        clearAllCaches() {
            this.showProgressIndicator('Clearing caches...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_clear_all_caches',
                    nonce: $('#env_performance_nonce').val()
                },
                success: (response) => {
                    this.hideProgressIndicator();
                    if (response.success) {
                        this.showNotification('All caches cleared successfully!', 'success');
                        location.reload();
                    } else {
                        this.showNotification('Failed to clear caches: ' + response.data, 'error');
                    }
                }
            });
        }
        
        optimizeDatabase() {
            if (confirm('Are you sure you want to optimize the database? This may take a few minutes.')) {
                this.showProgressIndicator('Optimizing database...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'env_optimize_database_advanced',
                        nonce: $('#env_performance_nonce').val()
                    },
                    success: (response) => {
                        this.hideProgressIndicator();
                        if (response.success) {
                            this.showNotification('Database optimized successfully!', 'success');
                            this.showDatabaseOptimizationResults(response.data);
                        } else {
                            this.showNotification('Database optimization failed: ' + response.data, 'error');
                        }
                    }
                });
            }
        }
        
        showDatabaseOptimizationResults(results) {
            const modal = `
                <div id="db-optimization-results" class="performance-modal">
                    <div class="modal-content">
                        <h2>Database Optimization Results</h2>
                        <div class="optimization-stats">
                            <p><strong>Tables Optimized:</strong> ${results.tables_optimized}</p>
                            <p><strong>Space Freed:</strong> ${results.space_freed} MB</p>
                            <p><strong>Queries Optimized:</strong> ${results.queries_optimized}</p>
                            <p><strong>Indexes Added:</strong> ${results.indexes_added}</p>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="button" onclick="closeDatabaseOptimizationResults()">Close</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modal);
        }
        
        exportPerformanceReport() {
            this.showProgressIndicator('Generating performance report...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_export_performance_report',
                    nonce: $('#env_performance_nonce').val()
                },
                success: (response) => {
                    this.hideProgressIndicator();
                    if (response.success) {
                        // Trigger download
                        const link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename;
                        link.click();
                    } else {
                        this.showNotification('Failed to generate report: ' + response.data, 'error');
                    }
                }
            });
        }
        
        showProgressIndicator(message) {
            const indicator = `
                <div id="progress-indicator" class="progress-overlay">
                    <div class="progress-content">
                        <div class="spinner"></div>
                        <p>${message}</p>
                    </div>
                </div>
            `;
            $('body').append(indicator);
        }
        
        hideProgressIndicator() {
            $('#progress-indicator').remove();
        }
        
        showNotification(message, type = 'info') {
            const notification = `
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                </div>
            `;
            $('.wrap h1').after(notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $('.notice').fadeOut();
            }, 5000);
        }
    }
    
    // Global functions for modal handling
    window.closePerformanceTestModal = function() {
        $('#performance-test-modal').remove();
    };
    
    window.closePerformanceResultsModal = function() {
        $('#performance-results-modal').remove();
    };
    
    window.closeDatabaseOptimizationResults = function() {
        $('#db-optimization-results').remove();
    };
    
    window.exportTestResults = function() {
        // Implementation for exporting test results
        alert('Test results will be downloaded shortly.');
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#toplevel_page_environmental-performance').length > 0) {
            new AdminPerformanceManager();
        }
    });
    
})(jQuery);
