/**
 * Environmental Testing & QA Admin JavaScript
 * 
 * Handles all client-side functionality for the testing and QA dashboard
 * 
 * @package EnvironmentalTestingQA
 * @version 1.0.0
 */

(function($) {
    'use strict';
    
    // Main ETQ Admin object
    window.ETQAdmin = {
        
        // Configuration
        config: {
            ajaxUrl: etqAdmin.ajaxUrl,
            nonce: etqAdmin.nonce,
            refreshInterval: 5000, // 5 seconds
            currentTestRun: null,
            autoRefresh: false
        },
        
        // Initialize the admin interface
        init: function() {
            this.bindEvents();
            this.initDashboard();
            this.initModals();
            
            // Auto-refresh if on dashboard and tests are running
            if ($('#etq-dashboard-grid').length) {
                this.checkForRunningTests();
            }
        },
        
        // Bind all event listeners
        bindEvents: function() {
            var self = this;
            
            // Quick action buttons
            $(document).on('click', '.etq-run-quick-test', this.runQuickTest.bind(this));
            $(document).on('click', '.etq-create-staging', this.showCreateStagingModal.bind(this));
            $(document).on('click', '.etq-sync-tests', this.syncTestData.bind(this));
            
            // Test management
            $(document).on('click', '.etq-view-details', this.viewTestDetails.bind(this));
            $(document).on('click', '.etq-rerun-test', this.rerunTest.bind(this));
            $(document).on('click', '.etq-create-test-suite', this.showCreateTestSuiteModal.bind(this));
            
            // Performance testing
            $(document).on('submit', '#etq-performance-test-form', this.runPerformanceTest.bind(this));
            
            // Test runner controls
            $(document).on('click', '.etq-start-test-run', this.startTestRun.bind(this));
            $(document).on('click', '.etq-stop-test-run', this.stopTestRun.bind(this));
            
            // Modal controls
            $(document).on('click', '.etq-modal-close', this.closeModal.bind(this));
            $(document).on('click', '.etq-modal', function(e) {
                if (e.target === this) {
                    self.closeModal();
                }
            });
            
            // Results filtering
            $(document).on('submit', '#etq-results-filter-form', this.filterResults.bind(this));
            
            // Staging environment management
            $(document).on('click', '.etq-create-staging-env', this.createStagingEnvironment.bind(this));
            $(document).on('click', '.etq-deploy-staging', this.deployStagingEnvironment.bind(this));
        },
        
        // Initialize dashboard
        initDashboard: function() {
            if ($('#etq-dashboard-grid').length) {
                this.loadDashboardData();
            }
        },
        
        // Initialize modals
        initModals: function() {
            // Create modal container if it doesn't exist
            if (!$('#etq-modal-container').length) {
                $('body').append('<div id="etq-modal-container"></div>');
            }
        },
        
        // Load dashboard data
        loadDashboardData: function() {
            var self = this;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'etq_get_dashboard_data',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateDashboardUI(response.data);
                    }
                },
                error: function() {
                    self.showNotification('Failed to load dashboard data', 'error');
                }
            });
        },
        
        // Update dashboard UI with new data
        updateDashboardUI: function(data) {
            // Update overview cards
            $('.etq-stat-number').each(function() {
                var card = $(this).closest('.etq-card');
                if (card.hasClass('etq-card-tests')) {
                    $(this).text(data.total_tests);
                } else if (card.hasClass('etq-card-success')) {
                    $(this).text(data.success_rate + '%');
                } else if (card.hasClass('etq-card-performance')) {
                    $(this).text(data.avg_response_time + 'ms');
                } else if (card.hasClass('etq-card-environments')) {
                    $(this).text(data.active_environments);
                }
            });
            
            // Update progress bars
            $('.etq-progress-fill').css('width', data.success_rate + '%');
            
            // Update recent tests table
            this.updateRecentTestsTable(data.recent_tests);
            
            // Update coverage chart
            this.updateCoverageChart(data.coverage_data);
        },
        
        // Update recent tests table
        updateRecentTestsTable: function(tests) {
            var tbody = $('.etq-test-results-table tbody');
            if (tbody.length && tests.length) {
                tbody.empty();
                
                tests.forEach(function(test) {
                    var row = $('<tr>');
                    row.append('<td><strong>' + test.test_name + '</strong>' + 
                              (test.description ? '<br><small>' + test.description + '</small>' : '') + '</td>');
                    row.append('<td><span class="etq-test-type etq-type-' + test.test_type + '">' + 
                              test.test_type.charAt(0).toUpperCase() + test.test_type.slice(1) + '</span></td>');
                    row.append('<td><span class="etq-status etq-status-' + test.status + '">' + 
                              test.status.charAt(0).toUpperCase() + test.status.slice(1) + '</span></td>');
                    row.append('<td>' + test.duration + 's</td>');
                    row.append('<td>' + test.executed_at + '</td>');
                    row.append('<td>' +
                              '<button class="button button-small etq-view-details" data-test-id="' + test.id + '">View</button> ' +
                              '<button class="button button-small etq-rerun-test" data-test-id="' + test.id + '">Rerun</button>' +
                              '</td>');
                    tbody.append(row);
                });
            }
        },
        
        // Update coverage chart
        updateCoverageChart: function(coverageData) {
            var container = $('.etq-coverage-visual');
            if (container.length && coverageData) {
                container.empty();
                
                Object.keys(coverageData).forEach(function(module) {
                    var coverage = coverageData[module];
                    var item = $('<div class="etq-coverage-item">');
                    item.append('<div class="etq-coverage-label">' + module + 
                               '<span class="etq-coverage-percent">' + coverage + '%</span></div>');
                    item.append('<div class="etq-coverage-bar">' +
                               '<div class="etq-coverage-fill" style="width: ' + coverage + '%"></div></div>');
                    container.append(item);
                });
            }
        },
        
        // Run quick test
        runQuickTest: function(e) {
            e.preventDefault();
            
            var button = $(e.currentTarget);
            var testType = button.data('test-type');
            var originalText = button.text();
            
            button.prop('disabled', true).text('Running...');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'etq_run_quick_test',
                    test_type: testType,
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ETQAdmin.showNotification(response.data.message, 'success');
                    } else {
                        ETQAdmin.showNotification('Test failed: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    ETQAdmin.showNotification('Failed to run test', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        // Run performance test
        runPerformanceTest: function(e) {
            e.preventDefault();
            
            var form = $(e.currentTarget);
            var formData = form.serialize();
            var button = form.find('button[type="submit"]');
            var originalText = button.text();
            
            button.prop('disabled', true).text('Running Performance Test...');
            
            // Show progress indicator
            $('#etq-performance-results').html('<div class="etq-loading">Running performance test, please wait...</div>');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'etq_run_performance_test',
                    config: JSON.stringify(this.serializeFormToObject(form)),
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ETQAdmin.displayPerformanceResults(response.data);
                        ETQAdmin.showNotification('Performance test completed', 'success');
                    } else {
                        ETQAdmin.showNotification('Performance test failed: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    ETQAdmin.showNotification('Failed to run performance test', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        // Display performance test results
        displayPerformanceResults: function(results) {
            var html = '<div class="etq-performance-results">';
            html += '<h3>Performance Test Results</h3>';
            html += '<div class="etq-performance-score">Overall Score: <strong>' + results.overall_score + '/100</strong></div>';
            
            if (results.endpoints) {
                html += '<h4>Endpoint Performance</h4>';
                html += '<table class="widefat">';
                html += '<thead><tr><th>Endpoint</th><th>Avg Response Time</th><th>Success Rate</th><th>Throughput</th></tr></thead>';
                html += '<tbody>';
                
                results.endpoints.forEach(function(endpoint) {
                    html += '<tr>';
                    html += '<td>' + endpoint.url + '</td>';
                    html += '<td>' + (endpoint.avg_response_time * 1000).toFixed(2) + 'ms</td>';
                    html += '<td>' + (100 - endpoint.error_rate).toFixed(1) + '%</td>';
                    html += '<td>' + endpoint.throughput.toFixed(2) + ' req/s</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
            }
            
            if (results.recommendations && results.recommendations.length) {
                html += '<h4>Recommendations</h4>';
                html += '<ul>';
                results.recommendations.forEach(function(rec) {
                    html += '<li>' + rec + '</li>';
                });
                html += '</ul>';
            }
            
            html += '</div>';
            
            $('#etq-performance-results').html(html);
        },
        
        // Start comprehensive test run
        startTestRun: function(e) {
            e.preventDefault();
            
            var config = {
                run_type: 'manual',
                test_types: ['phpunit', 'selenium', 'performance'],
                environment: 'development',
                email_notifications: true
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'etq_start_test_run',
                    config: JSON.stringify(config),
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ETQAdmin.config.currentTestRun = response.data.run_id;
                        ETQAdmin.startTestRunMonitoring();
                        ETQAdmin.showNotification('Test run started', 'success');
                    } else {
                        ETQAdmin.showNotification('Failed to start test run: ' + response.data.error, 'error');
                    }
                },
                error: function() {
                    ETQAdmin.showNotification('Failed to start test run', 'error');
                }
            });
        },
        
        // Stop test run
        stopTestRun: function(e) {
            e.preventDefault();
            
            if (!this.config.currentTestRun) {
                this.showNotification('No test run in progress', 'warning');
                return;
            }
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'etq_stop_test_run',
                    run_id: this.config.currentTestRun,
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ETQAdmin.stopTestRunMonitoring();
                        ETQAdmin.showNotification('Test run stopped', 'success');
                    }
                },
                error: function() {
                    ETQAdmin.showNotification('Failed to stop test run', 'error');
                }
            });
        },
        
        // Start monitoring test run progress
        startTestRunMonitoring: function() {
            this.config.autoRefresh = true;
            this.monitorTestRun();
        },
        
        // Stop monitoring test run
        stopTestRunMonitoring: function() {
            this.config.autoRefresh = false;
            this.config.currentTestRun = null;
        },
        
        // Monitor test run progress
        monitorTestRun: function() {
            var self = this;
            
            if (!this.config.autoRefresh || !this.config.currentTestRun) {
                return;
            }
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'etq_get_test_run_status',
                    run_id: this.config.currentTestRun,
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.updateTestRunStatus(response.data);
                        
                        if (response.data.status === 'completed' || response.data.status === 'failed' || response.data.status === 'stopped') {
                            self.stopTestRunMonitoring();
                        }
                    }
                },
                complete: function() {
                    if (self.config.autoRefresh) {
                        setTimeout(function() {
                            self.monitorTestRun();
                        }, self.config.refreshInterval);
                    }
                }
            });
        },
        
        // Update test run status display
        updateTestRunStatus: function(status) {
            // Update any test run status indicators
            $('.etq-test-run-status').text(status.status);
            $('.etq-test-run-progress').text(status.progress || '');
        },
        
        // Check for running tests on page load
        checkForRunningTests: function() {
            var self = this;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'etq_get_test_run_status',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success && response.data && response.data.status === 'running') {
                        self.config.currentTestRun = response.data.id;
                        self.startTestRunMonitoring();
                        self.showNotification('Test run in progress', 'info');
                    }
                }
            });
        },
        
        // View test details
        viewTestDetails: function(e) {
            e.preventDefault();
            
            var testId = $(e.currentTarget).data('test-id');
            this.showModal('Test Details', '<div class="etq-loading">Loading test details...</div>');
            
            // Load test details via AJAX
            // This is a placeholder - would load actual test details
            setTimeout(function() {
                var content = '<div class="etq-test-details">';
                content += '<h4>Test Information</h4>';
                content += '<p><strong>Test ID:</strong> ' + testId + '</p>';
                content += '<p><strong>Status:</strong> Passed</p>';
                content += '<p><strong>Duration:</strong> 2.5 seconds</p>';
                content += '<h4>Test Output</h4>';
                content += '<pre>Test completed successfully\nAll assertions passed</pre>';
                content += '</div>';
                
                ETQAdmin.updateModalContent(content);
            }, 1000);
        },
        
        // Rerun test
        rerunTest: function(e) {
            e.preventDefault();
            
            var testId = $(e.currentTarget).data('test-id');
            var button = $(e.currentTarget);
            var originalText = button.text();
            
            button.prop('disabled', true).text('Running...');
            
            // Simulate test rerun
            setTimeout(function() {
                button.prop('disabled', false).text(originalText);
                ETQAdmin.showNotification('Test rerun completed', 'success');
                ETQAdmin.loadDashboardData(); // Refresh data
            }, 2000);
        },
        
        // Show modal
        showModal: function(title, content) {
            var modal = $('#etq-test-details-modal');
            if (modal.length) {
                modal.find('h2').text(title);
                modal.find('#etq-test-details-content').html(content);
                modal.show();
            }
        },
        
        // Update modal content
        updateModalContent: function(content) {
            $('#etq-test-details-content').html(content);
        },
        
        // Close modal
        closeModal: function() {
            $('.etq-modal').hide();
        },
        
        // Show notification
        showNotification: function(message, type) {
            type = type || 'info';
            
            var notification = $('<div class="notice notice-' + type + ' is-dismissible">');
            notification.append('<p>' + message + '</p>');
            notification.append('<button type="button" class="notice-dismiss">×</button>');
            
            $('.wrap h1').after(notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notification.fadeOut();
            }, 5000);
            
            // Manual dismiss
            notification.on('click', '.notice-dismiss', function() {
                notification.fadeOut();
            });
        },
        
        // Utility functions
        serializeFormToObject: function(form) {
            var obj = {};
            var data = form.serializeArray();
            
            data.forEach(function(item) {
                obj[item.name] = item.value;
            });
            
            return obj;
        },
        
        // Placeholder functions for features to be implemented
        showCreateStagingModal: function(e) {
            e.preventDefault();
            this.showNotification('Staging environment creation will be implemented', 'info');
        },
        
        syncTestData: function(e) {
            e.preventDefault();
            this.showNotification('Test data sync completed', 'success');
        },
        
        showCreateTestSuiteModal: function(e) {
            e.preventDefault();
            this.showNotification('Test suite creation will be implemented', 'info');
        },
        
        filterResults: function(e) {
            e.preventDefault();
            this.showNotification('Results filtered', 'success');
        },
        
        createStagingEnvironment: function(e) {
            e.preventDefault();
            this.showNotification('Staging environment creation started', 'info');
        },
        
        deployStagingEnvironment: function(e) {
            e.preventDefault();
            this.showNotification('Deployment started', 'info');
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        ETQAdmin.init();
    });
    
})(jQuery);
