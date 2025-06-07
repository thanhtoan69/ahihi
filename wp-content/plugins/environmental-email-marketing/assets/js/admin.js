/**
 * Environmental Email Marketing - Admin JavaScript
 * Handles all admin interface interactions, campaign builder, analytics charts, and dashboard functionality
 */

jQuery(document).ready(function($) {
    'use strict';

    // Global variables
    let eemAdmin = {
        ajaxUrl: eem_admin_vars.ajax_url,
        nonce: eem_admin_vars.nonce,
        isProcessing: false,
        currentChart: null,
        campaignBuilder: {
            currentStep: 1,
            totalSteps: 5,
            campaignData: {}
        }
    };

    /**
     * Initialize admin functionality
     */
    function init() {
        initDashboard();
        initCampaignBuilder();
        initSubscriberManagement();
        initAnalytics();
        initSettings();
        initUtilities();
        
        // Initialize tooltips and popovers
        initTooltips();
        
        // Initialize modals
        initModals();
        
        console.log('EEM Admin initialized successfully');
    }

    /**
     * Dashboard functionality
     */
    function initDashboard() {
        // Real-time statistics updates
        setInterval(updateDashboardStats, 30000); // Every 30 seconds
        
        // Quick actions
        $('.eem-quick-action').on('click', function(e) {
            e.preventDefault();
            const action = $(this).data('action');
            handleQuickAction(action);
        });
        
        // Widget refresh
        $('.eem-widget-refresh').on('click', function(e) {
            e.preventDefault();
            const widget = $(this).closest('.eem-widget');
            refreshWidget(widget);
        });
        
        // Dashboard charts
        initDashboardCharts();
    }

    /**
     * Campaign Builder functionality
     */
    function initCampaignBuilder() {
        // Step navigation
        $('.eem-step-nav').on('click', function(e) {
            e.preventDefault();
            const step = parseInt($(this).data('step'));
            navigateToStep(step);
        });
        
        // Next/Previous buttons
        $('.eem-btn-next').on('click', function(e) {
            e.preventDefault();
            nextStep();
        });
        
        $('.eem-btn-prev').on('click', function(e) {
            e.preventDefault();
            prevStep();
        });
        
        // Campaign type selection
        $('input[name="campaign_type"]').on('change', function() {
            const type = $(this).val();
            showCampaignTypeOptions(type);
        });
        
        // Template selection
        $('.eem-template-option').on('click', function() {
            $('.eem-template-option').removeClass('selected');
            $(this).addClass('selected');
            const templateId = $(this).data('template-id');
            loadTemplate(templateId);
        });
        
        // Email content editor
        if (typeof tinymce !== 'undefined') {
            initTinyMCE();
        }
        
        // A/B testing toggle
        $('#enable_ab_testing').on('change', function() {
            const isEnabled = $(this).is(':checked');
            toggleABTesting(isEnabled);
        });
        
        // Environmental settings
        $('.eem-environmental-option').on('change', function() {
            updateEnvironmentalSettings();
        });
        
        // Preview functionality
        $('.eem-preview-btn').on('click', function(e) {
            e.preventDefault();
            openCampaignPreview();
        });
        
        // Save draft
        $('.eem-save-draft').on('click', function(e) {
            e.preventDefault();
            saveCampaignDraft();
        });
        
        // Send test email
        $('.eem-send-test').on('click', function(e) {
            e.preventDefault();
            openTestEmailModal();
        });
        
        // Schedule campaign
        $('.eem-schedule-btn').on('click', function(e) {
            e.preventDefault();
            scheduleCampaign();
        });
        
        // Send now button
        $('.eem-send-now').on('click', function(e) {
            e.preventDefault();
            sendCampaignNow();
        });
    }

    /**
     * Subscriber Management functionality
     */
    function initSubscriberManagement() {
        // Bulk actions
        $('#eem-bulk-action-selector').on('change', function() {
            const action = $(this).val();
            toggleBulkActionOptions(action);
        });
        
        $('.eem-apply-bulk-action').on('click', function(e) {
            e.preventDefault();
            applyBulkAction();
        });
        
        // Individual subscriber actions
        $('.eem-subscriber-action').on('click', function(e) {
            e.preventDefault();
            const action = $(this).data('action');
            const subscriberId = $(this).data('subscriber-id');
            handleSubscriberAction(action, subscriberId);
        });
        
        // Search and filters
        $('#eem-subscriber-search').on('input', debounce(function() {
            filterSubscribers();
        }, 300));
        
        $('.eem-filter-option').on('change', function() {
            filterSubscribers();
        });
        
        // Import/Export
        $('.eem-import-btn').on('click', function(e) {
            e.preventDefault();
            openImportModal();
        });
        
        $('.eem-export-btn').on('click', function(e) {
            e.preventDefault();
            exportSubscribers();
        });
        
        // Segmentation
        $('.eem-create-segment').on('click', function(e) {
            e.preventDefault();
            openSegmentModal();
        });
        
        // Environmental scoring visualization
        initEnvironmentalScoring();
    }

    /**
     * Analytics functionality
     */
    function initAnalytics() {
        // Date range picker
        $('#eem-date-range').on('change', function() {
            const range = $(this).val();
            updateAnalyticsData(range);
        });
        
        // Chart type toggles
        $('.eem-chart-toggle').on('click', function(e) {
            e.preventDefault();
            const chartType = $(this).data('chart-type');
            switchChartType(chartType);
        });
        
        // Metric cards refresh
        $('.eem-metric-refresh').on('click', function(e) {
            e.preventDefault();
            refreshMetrics();
        });
        
        // Export analytics
        $('.eem-export-analytics').on('click', function(e) {
            e.preventDefault();
            exportAnalytics();
        });
        
        // Initialize charts
        initAnalyticsCharts();
        
        // Real-time updates for active campaigns
        setInterval(updateRealTimeMetrics, 60000); // Every minute
    }

    /**
     * Settings functionality
     */
    function initSettings() {
        // Tab navigation
        $('.eem-settings-tab').on('click', function(e) {
            e.preventDefault();
            const tab = $(this).data('tab');
            switchSettingsTab(tab);
        });
        
        // Provider selection
        $('#email_provider').on('change', function() {
            const provider = $(this).val();
            showProviderSettings(provider);
        });
        
        // Test connection
        $('.eem-test-connection').on('click', function(e) {
            e.preventDefault();
            testProviderConnection();
        });
        
        // Automation settings
        $('.eem-automation-toggle').on('change', function() {
            const automationId = $(this).data('automation-id');
            const isEnabled = $(this).is(':checked');
            toggleAutomation(automationId, isEnabled);
        });
        
        // Environmental settings
        $('.eem-environmental-setting').on('change', function() {
            updateEnvironmentalSettings();
        });
        
        // Advanced settings toggles
        $('.eem-advanced-toggle').on('change', function() {
            const setting = $(this).data('setting');
            const value = $(this).is(':checked');
            updateAdvancedSetting(setting, value);
        });
        
        // Settings form submission
        $('#eem-settings-form').on('submit', function(e) {
            e.preventDefault();
            saveSettings();
        });
        
        // Reset settings
        $('.eem-reset-settings').on('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to reset all settings to defaults?')) {
                resetSettings();
            }
        });
    }

    /**
     * Utility functions
     */
    function initUtilities() {
        // Color picker
        $('.eem-color-picker').wpColorPicker({
            change: function(event, ui) {
                updateColorPreview($(this), ui.color.toString());
            }
        });
        
        // File upload handling
        $('.eem-file-upload').on('change', function() {
            handleFileUpload($(this));
        });
        
        // Drag and drop functionality
        initDragAndDrop();
        
        // Keyboard shortcuts
        initKeyboardShortcuts();
        
        // Auto-save functionality
        initAutoSave();
    }

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        $('[data-toggle="tooltip"]').tooltip({
            container: 'body',
            trigger: 'hover focus'
        });
    }

    /**
     * Initialize modals
     */
    function initModals() {
        // Campaign preview modal
        $('#eem-campaign-preview-modal').on('show.bs.modal', function() {
            loadCampaignPreview();
        });
        
        // Test email modal
        $('#eem-test-email-modal').on('show.bs.modal', function() {
            populateTestEmailForm();
        });
        
        // Import modal
        $('#eem-import-modal').on('show.bs.modal', function() {
            resetImportForm();
        });
        
        // Segment creation modal
        $('#eem-segment-modal').on('show.bs.modal', function() {
            initSegmentBuilder();
        });
    }

    /**
     * Dashboard statistics update
     */
    function updateDashboardStats() {
        if (eemAdmin.isProcessing) return;
        
        $.ajax({
            url: eemAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'eem_get_dashboard_stats',
                nonce: eemAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStatsDisplay(response.data);
                }
            }
        });
    }

    /**
     * Update statistics display
     */
    function updateStatsDisplay(stats) {
        // Update metric cards
        $('.eem-stat-subscribers .stat-value').text(stats.subscribers || 0);
        $('.eem-stat-campaigns .stat-value').text(stats.campaigns || 0);
        $('.eem-stat-sent .stat-value').text(stats.emails_sent || 0);
        $('.eem-stat-opens .stat-value').text(stats.open_rate || '0%');
        $('.eem-stat-clicks .stat-value').text(stats.click_rate || '0%');
        $('.eem-stat-environmental .stat-value').text(stats.environmental_score || 0);
        
        // Update trend indicators
        updateTrendIndicators(stats.trends || {});
        
        // Update recent activity
        if (stats.recent_activity) {
            updateRecentActivity(stats.recent_activity);
        }
    }

    /**
     * Handle quick actions
     */
    function handleQuickAction(action) {
        switch (action) {
            case 'new_campaign':
                window.location.href = eem_admin_vars.new_campaign_url;
                break;
            case 'import_subscribers':
                openImportModal();
                break;
            case 'view_analytics':
                window.location.href = eem_admin_vars.analytics_url;
                break;
            case 'sync_providers':
                syncProviders();
                break;
            default:
                console.log('Unknown quick action:', action);
        }
    }

    /**
     * Campaign builder step navigation
     */
    function navigateToStep(step) {
        if (step < 1 || step > eemAdmin.campaignBuilder.totalSteps) return;
        
        // Validate current step before proceeding
        if (step > eemAdmin.campaignBuilder.currentStep && !validateCurrentStep()) {
            return;
        }
        
        // Hide current step
        $('.eem-step').removeClass('active');
        $('.eem-step-nav').removeClass('active');
        
        // Show target step
        $('#eem-step-' + step).addClass('active');
        $('.eem-step-nav[data-step="' + step + '"]').addClass('active');
        
        // Update progress bar
        const progress = (step / eemAdmin.campaignBuilder.totalSteps) * 100;
        $('.eem-progress-bar').css('width', progress + '%');
        
        eemAdmin.campaignBuilder.currentStep = step;
        
        // Update button states
        updateStepButtons();
    }

    /**
     * Validate current step
     */
    function validateCurrentStep() {
        const step = eemAdmin.campaignBuilder.currentStep;
        let isValid = true;
        
        switch (step) {
            case 1: // Campaign details
                isValid = validateCampaignDetails();
                break;
            case 2: // Recipients
                isValid = validateRecipients();
                break;
            case 3: // Content
                isValid = validateContent();
                break;
            case 4: // Settings
                isValid = validateSettings();
                break;
            case 5: // Review
                isValid = true; // Review step doesn't need validation
                break;
        }
        
        if (!isValid) {
            showValidationErrors();
        }
        
        return isValid;
    }

    /**
     * Initialize TinyMCE editor
     */
    function initTinyMCE() {
        tinymce.init({
            selector: '#campaign_content',
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste code help wordcount',
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            menubar: false,
            height: 400,
            setup: function(editor) {
                editor.on('change', function() {
                    updateCampaignContent(editor.getContent());
                });
            }
        });
    }

    /**
     * Initialize analytics charts
     */
    function initAnalyticsCharts() {
        // Main engagement chart
        initEngagementChart();
        
        // Environmental impact chart
        initEnvironmentalChart();
        
        // Campaign performance chart
        initPerformanceChart();
        
        // Subscriber growth chart
        initGrowthChart();
    }

    /**
     * Initialize engagement chart
     */
    function initEngagementChart() {
        const ctx = document.getElementById('eem-engagement-chart');
        if (!ctx) return;
        
        eemAdmin.currentChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Opens',
                    data: [],
                    borderColor: '#2E7D32',
                    backgroundColor: 'rgba(46, 125, 50, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Clicks',
                    data: [],
                    borderColor: '#1976D2',
                    backgroundColor: 'rgba(25, 118, 210, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Email Engagement Over Time'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Load initial data
        loadEngagementData();
    }

    /**
     * Environmental scoring visualization
     */
    function initEnvironmentalScoring() {
        $('.eem-environmental-score').each(function() {
            const score = parseInt($(this).data('score'));
            const maxScore = parseInt($(this).data('max-score') || 100);
            const percentage = (score / maxScore) * 100;
            
            // Create progress bar
            const progressBar = $('<div class="eem-score-progress"></div>');
            const progressFill = $('<div class="eem-score-fill"></div>');
            
            progressFill.css({
                'width': percentage + '%',
                'background-color': getScoreColor(percentage)
            });
            
            progressBar.append(progressFill);
            $(this).append(progressBar);
        });
    }

    /**
     * Get color based on environmental score
     */
    function getScoreColor(percentage) {
        if (percentage >= 80) return '#4CAF50'; // Green
        if (percentage >= 60) return '#8BC34A'; // Light Green
        if (percentage >= 40) return '#FFC107'; // Yellow
        if (percentage >= 20) return '#FF9800'; // Orange
        return '#F44336'; // Red
    }

    /**
     * Debounce function for search inputs
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = function() {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Show notification
     */
    function showNotification(message, type = 'info', duration = 5000) {
        const notification = $(`
            <div class="eem-notification eem-notification-${type}">
                <div class="eem-notification-content">
                    <span class="eem-notification-message">${message}</span>
                    <button class="eem-notification-close">&times;</button>
                </div>
            </div>
        `);
        
        $('body').append(notification);
        
        // Show notification
        setTimeout(() => {
            notification.addClass('show');
        }, 100);
        
        // Auto-hide
        setTimeout(() => {
            hideNotification(notification);
        }, duration);
        
        // Close button
        notification.find('.eem-notification-close').on('click', function() {
            hideNotification(notification);
        });
    }

    /**
     * Hide notification
     */
    function hideNotification(notification) {
        notification.removeClass('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }

    /**
     * Initialize drag and drop
     */
    function initDragAndDrop() {
        // Template elements dragging
        $('.eem-template-element').draggable({
            revert: 'invalid',
            helper: 'clone',
            cursor: 'move'
        });
        
        // Drop zones
        $('.eem-drop-zone').droppable({
            accept: '.eem-template-element',
            hoverClass: 'eem-drop-hover',
            drop: function(event, ui) {
                const element = ui.draggable.data('element-type');
                addTemplateElement($(this), element);
            }
        });
    }

    /**
     * Initialize keyboard shortcuts
     */
    function initKeyboardShortcuts() {
        $(document).on('keydown', function(e) {
            // Ctrl+S - Save
            if (e.ctrlKey && e.which === 83) {
                e.preventDefault();
                saveCampaignDraft();
                return false;
            }
            
            // Ctrl+Enter - Send test email
            if (e.ctrlKey && e.which === 13) {
                e.preventDefault();
                openTestEmailModal();
                return false;
            }
        });
    }

    /**
     * Initialize auto-save
     */
    function initAutoSave() {
        let autoSaveTimer;
        
        // Auto-save every 30 seconds when editing campaign
        if ($('#campaign_content').length > 0) {
            autoSaveTimer = setInterval(() => {
                saveCampaignDraft(true); // Silent save
            }, 30000);
        }
        
        // Clear timer on page unload
        $(window).on('beforeunload', function() {
            if (autoSaveTimer) {
                clearInterval(autoSaveTimer);
            }
        });
    }

    /**
     * Save campaign draft
     */
    function saveCampaignDraft(silent = false) {
        if (eemAdmin.isProcessing) return;
        
        eemAdmin.isProcessing = true;
        
        const campaignData = collectCampaignData();
        
        $.ajax({
            url: eemAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'eem_save_campaign_draft',
                nonce: eemAdmin.nonce,
                campaign_data: campaignData
            },
            success: function(response) {
                if (response.success) {
                    if (!silent) {
                        showNotification('Draft saved successfully', 'success');
                    }
                    // Update last saved time
                    $('.eem-last-saved').text('Last saved: ' + new Date().toLocaleTimeString());
                } else {
                    if (!silent) {
                        showNotification('Failed to save draft: ' + response.data, 'error');
                    }
                }
            },
            error: function() {
                if (!silent) {
                    showNotification('Error saving draft', 'error');
                }
            },
            complete: function() {
                eemAdmin.isProcessing = false;
            }
        });
    }

    /**
     * Collect campaign data from form
     */
    function collectCampaignData() {
        const data = {
            name: $('#campaign_name').val(),
            subject: $('#campaign_subject').val(),
            content: $('#campaign_content').val() || (typeof tinymce !== 'undefined' ? tinymce.get('campaign_content')?.getContent() : ''),
            type: $('input[name="campaign_type"]:checked').val(),
            recipients: collectRecipientData(),
            settings: collectCampaignSettings(),
            environmental: collectEnvironmentalSettings()
        };
        
        return data;
    }

    /**
     * AJAX error handler
     */
    $(document).ajaxError(function(event, xhr, settings, error) {
        console.error('AJAX Error:', error);
        if (xhr.status === 403) {
            showNotification('Session expired. Please refresh the page.', 'error');
        } else if (xhr.status === 500) {
            showNotification('Server error occurred. Please try again.', 'error');
        }
    });

    /**
     * Initialize loading states
     */
    function initLoadingStates() {
        // Add loading state to buttons
        $(document).on('click', '.eem-btn-loading', function() {
            const btn = $(this);
            if (btn.hasClass('loading')) return false;
            
            btn.addClass('loading');
            btn.data('original-text', btn.text());
            btn.text('Loading...');
            
            // Remove loading state after 10 seconds max
            setTimeout(() => {
                removeLoadingState(btn);
            }, 10000);
        });
    }

    /**
     * Remove loading state from button
     */
    function removeLoadingState(btn) {
        btn.removeClass('loading');
        if (btn.data('original-text')) {
            btn.text(btn.data('original-text'));
        }
    }

    /**
     * Initialize responsive behavior
     */
    function initResponsive() {
        // Mobile menu toggle
        $('.eem-mobile-menu-toggle').on('click', function() {
            $('.eem-admin-sidebar').toggleClass('mobile-open');
        });
        
        // Close mobile menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.eem-admin-sidebar, .eem-mobile-menu-toggle').length) {
                $('.eem-admin-sidebar').removeClass('mobile-open');
            }
        });
        
        // Responsive tables
        $('.eem-table-responsive').each(function() {
            makeTableResponsive($(this));
        });
    }

    /**
     * Make table responsive
     */
    function makeTableResponsive(table) {
        const headers = table.find('thead th').map(function() {
            return $(this).text();
        }).get();
        
        table.find('tbody tr').each(function() {
            $(this).find('td').each(function(index) {
                $(this).attr('data-label', headers[index]);
            });
        });
    }

    // Initialize everything when document is ready
    init();
    initLoadingStates();
    initResponsive();

    // Expose public methods
    window.eemAdmin = {
        showNotification: showNotification,
        updateDashboardStats: updateDashboardStats,
        saveCampaignDraft: saveCampaignDraft,
        navigateToStep: navigateToStep
    };
});

/**
 * Campaign builder specific functions (global scope for inline usage)
 */
function eemOpenTemplateLibrary() {
    jQuery('#eem-template-library-modal').modal('show');
}

function eemSelectTemplate(templateId) {
    jQuery.ajax({
        url: eem_admin_vars.ajax_url,
        type: 'POST',
        data: {
            action: 'eem_load_template',
            nonce: eem_admin_vars.nonce,
            template_id: templateId
        },
        success: function(response) {
            if (response.success) {
                // Load template content into editor
                if (typeof tinymce !== 'undefined' && tinymce.get('campaign_content')) {
                    tinymce.get('campaign_content').setContent(response.data.content);
                } else {
                    jQuery('#campaign_content').val(response.data.content);
                }
                
                // Close modal
                jQuery('#eem-template-library-modal').modal('hide');
                
                // Show success message
                window.eemAdmin.showNotification('Template loaded successfully', 'success');
            } else {
                window.eemAdmin.showNotification('Failed to load template: ' + response.data, 'error');
            }
        }
    });
}

function eemPreviewCampaign() {
    const previewWindow = window.open('', 'campaign_preview', 'width=800,height=600,scrollbars=yes');
    const content = typeof tinymce !== 'undefined' && tinymce.get('campaign_content') 
        ? tinymce.get('campaign_content').getContent() 
        : jQuery('#campaign_content').val();
    
    previewWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Campaign Preview</title>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
                .preview-header { background: #f0f0f0; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
                .preview-content { max-width: 600px; margin: 0 auto; }
            </style>
        </head>
        <body>
            <div class="preview-header">
                <strong>Campaign Preview</strong>
                <button onclick="window.close()" style="float: right;">Close</button>
            </div>
            <div class="preview-content">
                ${content}
            </div>
        </body>
        </html>
    `);
}
