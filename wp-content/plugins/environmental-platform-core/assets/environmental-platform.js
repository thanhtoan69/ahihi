/**
 * Environmental Platform Core JavaScript
 * Handles frontend and admin functionality for the Environmental Platform
 */

(function($) {
    'use strict';

    // Environmental Platform Core object
    window.EnvironmentalPlatform = {
        
        // Initialize the platform
        init: function() {
            this.bindEvents();
            this.initDashboard();
            this.initAjaxHandlers();
            this.initCharts();
            console.log('Environmental Platform Core initialized');
        },
        
        // Bind event handlers
        bindEvents: function() {
            // Dashboard refresh button
            $(document).on('click', '.ep-refresh-stats', this.refreshStats);
            
            // Quick action buttons
            $(document).on('click', '.ep-quick-action', this.handleQuickAction);
            
            // Form submissions
            $(document).on('submit', '.ep-form', this.handleFormSubmit);
            
            // Real-time search
            $(document).on('keyup', '.ep-search-input', this.handleSearch);
        },
        
        // Initialize dashboard functionality
        initDashboard: function() {
            if ($('.ep-dashboard-grid').length) {
                this.loadDashboardStats();
                this.setupRealTimeUpdates();
            }
        },
        
        // Initialize AJAX handlers
        initAjaxHandlers: function() {
            // Set up AJAX defaults
            $.ajaxSetup({
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', ep_ajax.nonce);
                }
            });
        },
        
        // Initialize charts and visualizations
        initCharts: function() {
            if (typeof Chart !== 'undefined' && $('.ep-chart').length) {
                this.createStatsChart();
                this.createUserGrowthChart();
            }
        },
        
        // Load dashboard statistics
        loadDashboardStats: function() {
            const self = this;
            
            $.ajax({
                url: ep_ajax.ajax_url,
                method: 'GET',
                data: {
                    action: 'ep_get_dashboard_stats',
                    nonce: ep_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateDashboardStats(response.data);
                    }
                },
                error: function() {
                    console.error('Failed to load dashboard statistics');
                }
            });
        },
        
        // Update dashboard statistics
        updateDashboardStats: function(stats) {
            $('.ep-stat-number').each(function() {
                const $this = $(this);
                const statType = $this.data('stat');
                if (stats[statType]) {
                    $this.text(stats[statType]);
                }
            });
        },
        
        // Setup real-time updates
        setupRealTimeUpdates: function() {
            // Update dashboard every 30 seconds
            setInterval(() => {
                this.loadDashboardStats();
            }, 30000);
        },
        
        // Refresh statistics
        refreshStats: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const originalText = $button.text();
            
            $button.html('<span class="ep-loading"></span> Refreshing...');
            $button.prop('disabled', true);
            
            EnvironmentalPlatform.loadDashboardStats();
            
            setTimeout(() => {
                $button.text(originalText);
                $button.prop('disabled', false);
            }, 2000);
        },
        
        // Handle quick actions
        handleQuickAction: function(e) {
            e.preventDefault();
            
            const action = $(this).data('action');
            const $button = $(this);
            
            $button.addClass('loading');
            
            switch(action) {
                case 'create_post':
                    window.location.href = 'post-new.php?post_type=environmental_post';
                    break;
                case 'create_event':
                    window.location.href = 'post-new.php?post_type=environmental_event';
                    break;
                case 'view_analytics':
                    window.location.href = 'admin.php?page=ep-analytics';
                    break;
                default:
                    console.log('Unknown action:', action);
            }
            
            $button.removeClass('loading');
        },
        
        // Handle form submissions
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const formData = new FormData(this);
            formData.append('action', $form.data('action'));
            formData.append('nonce', ep_ajax.nonce);
            
            $.ajax({
                url: ep_ajax.ajax_url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $form.find('input[type="submit"]').prop('disabled', true);
                    EnvironmentalPlatform.showMessage('Processing...', 'info');
                },
                success: function(response) {
                    if (response.success) {
                        EnvironmentalPlatform.showMessage(response.data.message, 'success');
                        $form[0].reset();
                    } else {
                        EnvironmentalPlatform.showMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    EnvironmentalPlatform.showMessage('An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $form.find('input[type="submit"]').prop('disabled', false);
                }
            });
        },
        
        // Handle search functionality
        handleSearch: function() {
            const query = $(this).val();
            const searchType = $(this).data('search-type');
            
            if (query.length < 3) {
                $('.ep-search-results').empty();
                return;
            }
            
            $.ajax({
                url: ep_ajax.ajax_url,
                method: 'GET',
                data: {
                    action: 'ep_search',
                    query: query,
                    type: searchType,
                    nonce: ep_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        EnvironmentalPlatform.displaySearchResults(response.data);
                    }
                }
            });
        },
        
        // Display search results
        displaySearchResults: function(results) {
            const $container = $('.ep-search-results');
            $container.empty();
            
            if (results.length === 0) {
                $container.html('<p>No results found.</p>');
                return;
            }
            
            results.forEach(function(item) {
                const $result = $('<div class="ep-search-result">');
                $result.html(`
                    <h4>${item.title}</h4>
                    <p>${item.excerpt}</p>
                    <small>${item.date}</small>
                `);
                $container.append($result);
            });
        },
        
        // Create statistics chart
        createStatsChart: function() {
            const ctx = document.getElementById('ep-stats-chart');
            if (!ctx) return;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Users', 'Posts', 'Events', 'Achievements'],
                    datasets: [{
                        data: [
                            $('.ep-stat-number[data-stat="users"]').text() || 0,
                            $('.ep-stat-number[data-stat="posts"]').text() || 0,
                            $('.ep-stat-number[data-stat="events"]').text() || 0,
                            $('.ep-stat-number[data-stat="achievements"]').text() || 0
                        ],
                        backgroundColor: [
                            '#667eea',
                            '#764ba2',
                            '#f093fb',
                            '#f5576c'
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
        },
        
        // Create user growth chart
        createUserGrowthChart: function() {
            const ctx = document.getElementById('ep-growth-chart');
            if (!ctx) return;
            
            // This would typically load real data from the server
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'User Growth',
                        data: [10, 25, 45, 78, 120, 150],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
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
                    }
                }
            });
        },
        
        // Show messages to user
        showMessage: function(message, type) {
            const $message = $(`
                <div class="ep-message ${type}">
                    ${message}
                    <button type="button" class="ep-message-close">&times;</button>
                </div>
            `);
            
            $('.ep-messages').append($message);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $message.fadeOut(() => {
                    $message.remove();
                });
            }, 5000);
        },
        
        // Utility functions
        utils: {
            // Format numbers with commas
            formatNumber: function(num) {
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            },
            
            // Format dates
            formatDate: function(date) {
                return new Date(date).toLocaleDateString();
            },
            
            // Animate number counting
            animateNumber: function($element, targetNumber) {
                const startNumber = parseInt($element.text()) || 0;
                const duration = 1000;
                const increment = (targetNumber - startNumber) / (duration / 16);
                
                function updateNumber() {
                    const currentNumber = parseInt($element.text()) || 0;
                    if (currentNumber < targetNumber) {
                        $element.text(Math.ceil(currentNumber + increment));
                        requestAnimationFrame(updateNumber);
                    } else {
                        $element.text(targetNumber);
                    }
                }
                
                updateNumber();
            }
        }
    };
    
    // Event handlers for message close buttons
    $(document).on('click', '.ep-message-close', function() {
        $(this).parent().fadeOut(() => {
            $(this).parent().remove();
        });
    });
    
    // Initialize when document is ready
    $(document).ready(function() {
        EnvironmentalPlatform.init();
    });
    
    // Also initialize on window load for any dynamic content
    $(window).on('load', function() {
        // Additional initialization if needed
    });

})(jQuery);
