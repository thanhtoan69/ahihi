/**
 * Admin JavaScript for AI Waste Classification Management
 * 
 * @package Environmental_Data_Dashboard
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Admin object
    var WasteClassificationAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.loadDashboardStats();
            this.loadRecentClassifications();
            this.initCharts();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Configuration form
            $('#ai-config-form').on('submit', this.saveConfiguration);
            $('#test_ai_connection').on('click', this.testAIConnection);
            
            // Gamification settings
            $('#gamification-settings-form').on('submit', this.saveGamificationSettings);
            
            // Classification management
            $('#search-classifications').on('input', this.filterClassifications);
            $('#filter-category').on('change', this.filterClassifications);
            $('#export-classifications').on('click', this.exportClassifications);
            
            // Confidence threshold slider
            $('#confidence_threshold').on('input', function() {
                $('#confidence_value').text($(this).val());
            });
            
            // Classification action buttons
            $(document).on('click', '.approve-classification', this.approveClassification);
            $(document).on('click', '.reject-classification', this.rejectClassification);
            $(document).on('click', '.view-classification-details', this.viewClassificationDetails);
        },
        
        /**
         * Load dashboard statistics
         */
        loadDashboardStats: function() {
            $.ajax({
                url: waste_classification_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_ai_classification_stats',
                    nonce: waste_classification_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#total-classifications').text(response.data.total_classifications || '0');
                        $('#active-users').text(response.data.active_users || '0');
                        $('#average-accuracy').text((response.data.average_accuracy || '0') + '%');
                        $('#total-carbon').text((response.data.total_carbon || '0') + ' kg');
                    }
                },
                error: function() {
                    console.error('Failed to load dashboard stats');
                }
            });
        },
        
        /**
         * Load recent classifications
         */
        loadRecentClassifications: function() {
            $.ajax({
                url: waste_classification_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_recent_classifications',
                    nonce: waste_classification_admin_ajax.nonce,
                    limit: 20
                },
                success: function(response) {
                    if (response.success) {
                        WasteClassificationAdmin.populateClassificationsTable(response.data);
                    }
                },
                error: function() {
                    console.error('Failed to load recent classifications');
                }
            });
        },
        
        /**
         * Populate classifications table
         */
        populateClassificationsTable: function(classifications) {
            var tbody = $('#classifications-data-table');
            tbody.empty();
            
            if (classifications.length === 0) {
                tbody.append('<tr><td colspan="7">' + waste_classification_admin_ajax.strings.no_data + '</td></tr>');
                return;
            }
            
            classifications.forEach(function(classification) {
                var row = $('<tr>');
                
                // Image
                var imageCell = $('<td>');
                if (classification.image_url) {
                    imageCell.html('<img src="' + classification.image_url + '" alt="Classification" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">');
                } else {
                    imageCell.html('<span class="dashicons dashicons-format-image"></span>');
                }
                row.append(imageCell);
                
                // User
                row.append('<td>' + (classification.user_name || 'Unknown') + '</td>');
                
                // Category
                var categoryBadge = '<span class="category-badge category-' + classification.category + '">' + classification.category + '</span>';
                row.append('<td>' + categoryBadge + '</td>');
                
                // Confidence
                var confidenceClass = classification.confidence_score >= 0.8 ? 'high' : 
                                    classification.confidence_score >= 0.6 ? 'medium' : 'low';
                var confidenceBadge = '<span class="confidence-badge confidence-' + confidenceClass + '">' + 
                                    (classification.confidence_score * 100).toFixed(1) + '%</span>';
                row.append('<td>' + confidenceBadge + '</td>');
                
                // Feedback
                var feedbackText = classification.feedback_count > 0 ? 
                    classification.feedback_count + ' feedback(s)' : 'No feedback';
                row.append('<td>' + feedbackText + '</td>');
                
                // Date
                row.append('<td>' + WasteClassificationAdmin.formatDate(classification.created_at) + '</td>');
                
                // Actions
                var actions = $('<td>');
                var actionButtons = $('<div class="action-buttons">');
                
                actionButtons.append('<button class="button-secondary view-classification-details" data-id="' + 
                    classification.id + '">View</button>');
                
                if (classification.status === 'pending') {
                    actionButtons.append('<button class="button-primary approve-classification" data-id="' + 
                        classification.id + '">Approve</button>');
                    actionButtons.append('<button class="button-secondary reject-classification" data-id="' + 
                        classification.id + '">Reject</button>');
                }
                
                actions.append(actionButtons);
                row.append(actions);
                
                tbody.append(row);
            });
        },
        
        /**
         * Save AI configuration
         */
        saveConfiguration: function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitButton = form.find('#save_ai_config');
            var originalText = submitButton.val();
            
            submitButton.val(waste_classification_admin_ajax.strings.saving).prop('disabled', true);
            
            $.ajax({
                url: waste_classification_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_ai_configuration',
                    nonce: waste_classification_admin_ajax.nonce,
                    ai_service_provider: $('#ai_service_provider').val(),
                    api_key: $('#api_key').val(),
                    max_requests_per_day: $('#max_requests_per_day').val(),
                    confidence_threshold: $('#confidence_threshold').val()
                },
                success: function(response) {
                    if (response.success) {
                        WasteClassificationAdmin.showNotice('Configuration saved successfully!', 'success');
                        $('#api_key').val(''); // Clear API key field for security
                    } else {
                        WasteClassificationAdmin.showNotice('Failed to save configuration: ' + response.data, 'error');
                    }
                },
                error: function() {
                    WasteClassificationAdmin.showNotice('Failed to save configuration', 'error');
                },
                complete: function() {
                    submitButton.val(originalText).prop('disabled', false);
                }
            });
        },
        
        /**
         * Test AI connection
         */
        testAIConnection: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var originalText = button.text();
            
            button.text(waste_classification_admin_ajax.strings.testing_connection).prop('disabled', true);
            
            $.ajax({
                url: waste_classification_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'test_ai_connection',
                    nonce: waste_classification_admin_ajax.nonce,
                    ai_service_provider: $('#ai_service_provider').val(),
                    api_key: $('#api_key').val()
                },
                success: function(response) {
                    if (response.success) {
                        WasteClassificationAdmin.showNotice(waste_classification_admin_ajax.strings.connection_successful, 'success');
                    } else {
                        WasteClassificationAdmin.showNotice(waste_classification_admin_ajax.strings.connection_failed + ' ' + response.data, 'error');
                    }
                },
                error: function() {
                    WasteClassificationAdmin.showNotice(waste_classification_admin_ajax.strings.connection_failed, 'error');
                },
                complete: function() {
                    button.text(originalText).prop('disabled', false);
                }
            });
        },
        
        /**
         * Save gamification settings
         */
        saveGamificationSettings: function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitButton = form.find('#save_gamification_settings');
            var originalText = submitButton.val();
            
            submitButton.val(waste_classification_admin_ajax.strings.saving).prop('disabled', true);
            
            $.ajax({
                url: waste_classification_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_gamification_settings',
                    nonce: waste_classification_admin_ajax.nonce,
                    points_per_classification: $('#points_per_classification').val(),
                    accuracy_bonus_multiplier: $('#accuracy_bonus_multiplier').val(),
                    enable_leaderboard: $('#enable_leaderboard').is(':checked') ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        WasteClassificationAdmin.showNotice('Gamification settings saved successfully!', 'success');
                    } else {
                        WasteClassificationAdmin.showNotice('Failed to save settings: ' + response.data, 'error');
                    }
                },
                error: function() {
                    WasteClassificationAdmin.showNotice('Failed to save settings', 'error');
                },
                complete: function() {
                    submitButton.val(originalText).prop('disabled', false);
                }
            });
        },
        
        /**
         * Filter classifications
         */
        filterClassifications: function() {
            var searchTerm = $('#search-classifications').val().toLowerCase();
            var categoryFilter = $('#filter-category').val();
            
            $('#classifications-data-table tr').each(function() {
                var row = $(this);
                var userText = row.find('td:nth-child(2)').text().toLowerCase();
                var categoryText = row.find('td:nth-child(3)').text().toLowerCase();
                
                var matchesSearch = searchTerm === '' || userText.includes(searchTerm);
                var matchesCategory = categoryFilter === '' || categoryText.includes(categoryFilter);
                
                if (matchesSearch && matchesCategory) {
                    row.show();
                } else {
                    row.hide();
                }
            });
        },
        
        /**
         * Export classifications data
         */
        exportClassifications: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var originalText = button.text();
            
            button.text(waste_classification_admin_ajax.strings.exporting_data).prop('disabled', true);
            
            $.ajax({
                url: waste_classification_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'export_classifications_data',
                    nonce: waste_classification_admin_ajax.nonce,
                    format: 'csv'
                },
                success: function(response) {
                    if (response.success) {
                        // Create download link
                        var link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename;
                        link.click();
                        
                        WasteClassificationAdmin.showNotice(waste_classification_admin_ajax.strings.export_complete, 'success');
                    } else {
                        WasteClassificationAdmin.showNotice('Export failed: ' + response.data, 'error');
                    }
                },
                error: function() {
                    WasteClassificationAdmin.showNotice('Export failed', 'error');
                },
                complete: function() {
                    button.text(originalText).prop('disabled', false);
                }
            });
        },
        
        /**
         * Initialize charts
         */
        initCharts: function() {
            // Classifications trend chart
            var classificationsCtx = document.getElementById('classifications-chart');
            if (classificationsCtx) {
                this.loadClassificationsTrendChart(classificationsCtx);
            }
            
            // User engagement chart
            var engagementCtx = document.getElementById('user-engagement-chart');
            if (engagementCtx) {
                this.loadUserEngagementChart(engagementCtx);
            }
        },
        
        /**
         * Load classifications trend chart
         */
        loadClassificationsTrendChart: function(ctx) {
            $.ajax({
                url: waste_classification_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_classifications_trend_data',
                    nonce: waste_classification_admin_ajax.nonce,
                    period: '30_days'
                },
                success: function(response) {
                    if (response.success) {
                        new Chart(ctx, {
                            type: 'line',
                            data: response.data,
                            options: {
                                responsive: true,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Number of Classifications'
                                        }
                                    },
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Date'
                                        }
                                    }
                                },
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Classifications Trend (Last 30 Days)'
                                    }
                                }
                            }
                        });
                    }
                }
            });
        },
        
        /**
         * Load user engagement chart
         */
        loadUserEngagementChart: function(ctx) {
            $.ajax({
                url: waste_classification_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_user_engagement_data',
                    nonce: waste_classification_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: response.data,
                            options: {
                                responsive: true,
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'User Engagement Levels'
                                    },
                                    legend: {
                                        position: 'bottom'
                                    }
                                }
                            }
                        });
                    }
                }
            });
        },
        
        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap h1').after(notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            }, 5000);
        },
        
        /**
         * Format date for display
         */
        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        },
        
        /**
         * Approve classification
         */
        approveClassification: function(e) {
            e.preventDefault();
            var classificationId = $(this).data('id');
            
            WasteClassificationAdmin.updateClassificationStatus(classificationId, 'completed');
        },
        
        /**
         * Reject classification
         */
        rejectClassification: function(e) {
            e.preventDefault();
            var classificationId = $(this).data('id');
            
            if (confirm('Are you sure you want to reject this classification?')) {
                WasteClassificationAdmin.updateClassificationStatus(classificationId, 'failed');
            }
        },
        
        /**
         * Update classification status
         */
        updateClassificationStatus: function(classificationId, status) {
            $.ajax({
                url: waste_classification_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'update_classification_status',
                    nonce: waste_classification_admin_ajax.nonce,
                    classification_id: classificationId,
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        WasteClassificationAdmin.showNotice('Classification status updated successfully!', 'success');
                        WasteClassificationAdmin.loadRecentClassifications(); // Reload table
                    } else {
                        WasteClassificationAdmin.showNotice('Failed to update status: ' + response.data, 'error');
                    }
                },
                error: function() {
                    WasteClassificationAdmin.showNotice('Failed to update status', 'error');
                }
            });
        },
        
        /**
         * View classification details
         */
        viewClassificationDetails: function(e) {
            e.preventDefault();
            var classificationId = $(this).data('id');
            
            // Create and show modal with classification details
            WasteClassificationAdmin.showClassificationModal(classificationId);
        },
        
        /**
         * Show classification details modal
         */
        showClassificationModal: function(classificationId) {
            $.ajax({
                url: waste_classification_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_classification_details',
                    nonce: waste_classification_admin_ajax.nonce,
                    classification_id: classificationId
                },
                success: function(response) {
                    if (response.success) {
                        var details = response.data;
                        var modalHtml = WasteClassificationAdmin.buildClassificationModal(details);
                        
                        $('body').append(modalHtml);
                        $('#classification-details-modal').show();
                    }
                }
            });
        },
        
        /**
         * Build classification details modal HTML
         */
        buildClassificationModal: function(details) {
            return `
                <div id="classification-details-modal" class="classification-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Classification Details</h3>
                            <span class="modal-close">&times;</span>
                        </div>
                        <div class="modal-body">
                            <div class="classification-details">
                                <div class="detail-row">
                                    <strong>ID:</strong> ${details.id}
                                </div>
                                <div class="detail-row">
                                    <strong>User:</strong> ${details.user_name || 'Unknown'}
                                </div>
                                <div class="detail-row">
                                    <strong>Category:</strong> ${details.category}
                                </div>
                                <div class="detail-row">
                                    <strong>Confidence Score:</strong> ${(details.confidence_score * 100).toFixed(1)}%
                                </div>
                                <div class="detail-row">
                                    <strong>Status:</strong> ${details.status}
                                </div>
                                <div class="detail-row">
                                    <strong>Created:</strong> ${this.formatDate(details.created_at)}
                                </div>
                                ${details.image_url ? `
                                <div class="detail-row">
                                    <strong>Image:</strong><br>
                                    <img src="${details.image_url}" alt="Classification" style="max-width: 300px; max-height: 200px; object-fit: contain; border-radius: 4px;">
                                </div>
                                ` : ''}
                                ${details.ai_response ? `
                                <div class="detail-row">
                                    <strong>AI Response:</strong><br>
                                    <pre style="white-space: pre-wrap; background: #f5f5f5; padding: 10px; border-radius: 4px;">${details.ai_response}</pre>
                                </div>
                                ` : ''}
                                ${details.disposal_recommendations ? `
                                <div class="detail-row">
                                    <strong>Disposal Recommendations:</strong><br>
                                    <div style="background: #f5f5f5; padding: 10px; border-radius: 4px;">${details.disposal_recommendations}</div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        WasteClassificationAdmin.init();
        
        // Modal close handler
        $(document).on('click', '.modal-close, .classification-modal', function(e) {
            if (e.target === this) {
                $('.classification-modal').remove();
            }
        });
    });

})(jQuery);
