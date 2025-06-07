/**
 * Environmental Notifications - Admin JavaScript
 * Admin dashboard functionality and chart management
 */

(function($) {
    'use strict';

    // Global admin configuration
    const EN_ADMIN = {
        ajax_url: env_notifications_admin.ajax_url,
        nonce: env_notifications_admin.nonce,
        charts: {},
        currentTemplate: null,
        currentConversation: null
    };

    /**
     * Initialize admin functionality
     */
    function init() {
        setupTabs();
        setupCharts();
        setupTemplateEditor();
        setupMessaging();
        setupSettings();
        setupModals();
        setupNotices();
        loadDashboardData();
    }

    /**
     * Setup tab navigation
     */
    function setupTabs() {
        $(document).on('click', '.en-tab-button', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const target = $button.data('tab');
            const $container = $button.closest('.en-settings-tabs');
            
            // Update active button
            $container.find('.en-tab-button').removeClass('active');
            $button.addClass('active');
            
            // Update active panel
            $container.find('.en-tab-panel').removeClass('active');
            $container.find(`[data-panel="${target}"]`).addClass('active');
        });
    }

    /**
     * Setup charts and analytics
     */
    function setupCharts() {
        // Initialize Chart.js if available
        if (typeof Chart !== 'undefined') {
            setupNotificationChart();
            setupEngagementChart();
            setupDeviceChart();
        }

        // Setup chart filters
        $(document).on('change', '.en-chart-filter', function() {
            const chartType = $(this).data('chart');
            const period = $(this).val();
            updateChart(chartType, period);
        });

        // Setup analytics export
        $(document).on('click', '.en-export-analytics', function() {
            const format = $(this).data('format');
            exportAnalytics(format);
        });
    }

    /**
     * Setup notification delivery chart
     */
    function setupNotificationChart() {
        const ctx = document.getElementById('en-notifications-chart');
        if (!ctx) return;

        EN_ADMIN.charts.notifications = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Notifications Sent',
                    data: [],
                    borderColor: '#0073aa',
                    backgroundColor: 'rgba(0, 115, 170, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Notifications Read',
                    data: [],
                    borderColor: '#46b450',
                    backgroundColor: 'rgba(70, 180, 80, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Count'
                        },
                        beginAtZero: true
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    /**
     * Setup engagement metrics chart
     */
    function setupEngagementChart() {
        const ctx = document.getElementById('en-engagement-chart');
        if (!ctx) return;

        EN_ADMIN.charts.engagement = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Read', 'Unread', 'Dismissed'],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        '#46b450',
                        '#ffb900',
                        '#dc3232'
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
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Setup device analytics chart
     */
    function setupDeviceChart() {
        const ctx = document.getElementById('en-device-chart');
        if (!ctx) return;

        EN_ADMIN.charts.device = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Desktop', 'Mobile', 'Tablet'],
                datasets: [{
                    label: 'Notifications',
                    data: [],
                    backgroundColor: [
                        '#0073aa',
                        '#46b450',
                        '#ffb900'
                    ],
                    borderRadius: 4
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
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    /**
     * Update chart data
     */
    function updateChart(chartType, period) {
        $.ajax({
            url: EN_ADMIN.ajax_url,
            type: 'POST',
            data: {
                action: 'en_get_chart_data',
                nonce: EN_ADMIN.nonce,
                chart_type: chartType,
                period: period
            },
            success: function(response) {
                if (response.success && EN_ADMIN.charts[chartType]) {
                    const chart = EN_ADMIN.charts[chartType];
                    const data = response.data;
                    
                    if (chartType === 'notifications') {
                        chart.data.labels = data.labels;
                        chart.data.datasets[0].data = data.sent;
                        chart.data.datasets[1].data = data.read;
                    } else if (chartType === 'engagement') {
                        chart.data.datasets[0].data = [data.read, data.unread, data.dismissed];
                    } else if (chartType === 'device') {
                        chart.data.datasets[0].data = [data.desktop, data.mobile, data.tablet];
                    }
                    
                    chart.update();
                }
            }
        });
    }

    /**
     * Export analytics data
     */
    function exportAnalytics(format) {
        const params = new URLSearchParams({
            action: 'en_export_analytics',
            nonce: EN_ADMIN.nonce,
            format: format
        });
        
        window.open(`${EN_ADMIN.ajax_url}?${params.toString()}`, '_blank');
    }

    /**
     * Setup template editor
     */
    function setupTemplateEditor() {
        // Template selection
        $(document).on('click', '.en-template-item', function() {
            const templateId = $(this).data('id');
            selectTemplate(templateId);
        });

        // Save template
        $(document).on('click', '.en-save-template', function() {
            saveTemplate();
        });

        // Preview template
        $(document).on('click', '.en-preview-template', function() {
            previewTemplate();
        });

        // Reset template
        $(document).on('click', '.en-reset-template', function() {
            resetTemplate();
        });

        // Template variables helper
        setupTemplateVariables();
    }

    /**
     * Select and load template
     */
    function selectTemplate(templateId) {
        $('.en-template-item').removeClass('active');
        $(`.en-template-item[data-id="${templateId}"]`).addClass('active');
        EN_ADMIN.currentTemplate = templateId;
        
        $.ajax({
            url: EN_ADMIN.ajax_url,
            type: 'POST',
            data: {
                action: 'en_get_template',
                nonce: EN_ADMIN.nonce,
                template_id: templateId
            },
            success: function(response) {
                if (response.success) {
                    const template = response.data.template;
                    $('#en-template-subject').val(template.subject);
                    $('#en-template-content').val(template.content);
                    $('#en-template-type').val(template.type);
                    updateTemplatePreview();
                }
            }
        });
    }

    /**
     * Save template changes
     */
    function saveTemplate() {
        if (!EN_ADMIN.currentTemplate) {
            showNotice('Please select a template first', 'error');
            return;
        }

        const templateData = {
            action: 'en_save_template',
            nonce: EN_ADMIN.nonce,
            template_id: EN_ADMIN.currentTemplate,
            subject: $('#en-template-subject').val(),
            content: $('#en-template-content').val(),
            type: $('#en-template-type').val()
        };

        $.ajax({
            url: EN_ADMIN.ajax_url,
            type: 'POST',
            data: templateData,
            success: function(response) {
                if (response.success) {
                    showNotice('Template saved successfully', 'success');
                } else {
                    showNotice('Failed to save template', 'error');
                }
            }
        });
    }

    /**
     * Preview template
     */
    function previewTemplate() {
        const content = $('#en-template-content').val();
        const subject = $('#en-template-subject').val();
        
        const previewHtml = `
            <div class="en-template-preview">
                <h3>Subject: ${escapeHtml(subject)}</h3>
                <div class="en-template-preview-content">
                    ${content.replace(/\n/g, '<br>')}
                </div>
            </div>
        `;
        
        showModal('Template Preview', previewHtml);
    }

    /**
     * Reset template to default
     */
    function resetTemplate() {
        if (!EN_ADMIN.currentTemplate) return;
        
        if (confirm('Are you sure you want to reset this template to default? This action cannot be undone.')) {
            $.ajax({
                url: EN_ADMIN.ajax_url,
                type: 'POST',
                data: {
                    action: 'en_reset_template',
                    nonce: EN_ADMIN.nonce,
                    template_id: EN_ADMIN.currentTemplate
                },
                success: function(response) {
                    if (response.success) {
                        selectTemplate(EN_ADMIN.currentTemplate);
                        showNotice('Template reset to default', 'success');
                    }
                }
            });
        }
    }

    /**
     * Setup template variables helper
     */
    function setupTemplateVariables() {
        const variables = [
            '{{user_name}}', '{{user_email}}', '{{site_name}}', '{{site_url}}',
            '{{notification_title}}', '{{notification_message}}', '{{notification_url}}',
            '{{unsubscribe_url}}', '{{current_date}}', '{{current_time}}'
        ];

        let variablesHtml = '<div class="en-template-variables"><h4>Available Variables:</h4>';
        variables.forEach(function(variable) {
            variablesHtml += `<span class="en-variable-tag" data-variable="${variable}">${variable}</span>`;
        });
        variablesHtml += '</div>';

        $('.en-template-editor-panel').append(variablesHtml);

        // Insert variable on click
        $(document).on('click', '.en-variable-tag', function() {
            const variable = $(this).data('variable');
            const textarea = $('#en-template-content')[0];
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            
            textarea.value = text.substring(0, start) + variable + text.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + variable.length, start + variable.length);
        });
    }

    /**
     * Update template preview
     */
    function updateTemplatePreview() {
        // Real-time preview could be implemented here
        $(document).on('input', '#en-template-content, #en-template-subject', function() {
            // Debounced preview update
            clearTimeout(this.previewTimeout);
            this.previewTimeout = setTimeout(updateTemplatePreview, 500);
        });
    }

    /**
     * Setup messaging management
     */
    function setupMessaging() {
        // Load conversations
        loadConversations();

        // Conversation selection
        $(document).on('click', '.en-conversation-item', function() {
            const conversationId = $(this).data('id');
            selectConversation(conversationId);
        });

        // Send admin message
        $(document).on('click', '.en-send-admin-message', function() {
            sendAdminMessage();
        });

        // Message search
        $(document).on('input', '.en-message-search', function() {
            const query = $(this).val();
            searchMessages(query);
        });
    }

    /**
     * Load conversations list
     */
    function loadConversations() {
        $.ajax({
            url: EN_ADMIN.ajax_url,
            type: 'POST',
            data: {
                action: 'en_get_conversations',
                nonce: EN_ADMIN.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderConversations(response.data.conversations);
                }
            }
        });
    }

    /**
     * Render conversations in sidebar
     */
    function renderConversations(conversations) {
        const $list = $('.en-conversations-list');
        let html = '';

        conversations.forEach(function(conversation) {
            html += `
                <div class="en-conversation-item" data-id="${conversation.id}">
                    <div class="en-conversation-user">${escapeHtml(conversation.user_name)}</div>
                    <div class="en-conversation-preview">${escapeHtml(conversation.last_message)}</div>
                    <div class="en-conversation-time">${timeAgo(conversation.last_activity)}</div>
                </div>
            `;
        });

        $list.html(html);
    }

    /**
     * Select and load conversation
     */
    function selectConversation(conversationId) {
        $('.en-conversation-item').removeClass('active');
        $(`.en-conversation-item[data-id="${conversationId}"]`).addClass('active');
        EN_ADMIN.currentConversation = conversationId;

        $.ajax({
            url: EN_ADMIN.ajax_url,
            type: 'POST',
            data: {
                action: 'en_get_conversation_messages',
                nonce: EN_ADMIN.nonce,
                conversation_id: conversationId
            },
            success: function(response) {
                if (response.success) {
                    renderConversationMessages(response.data.messages);
                }
            }
        });
    }

    /**
     * Render conversation messages
     */
    function renderConversationMessages(messages) {
        const $thread = $('.en-thread-messages');
        let html = '';

        messages.forEach(function(message) {
            const isAdmin = message.sender_type === 'admin';
            html += `
                <div class="en-admin-message ${isAdmin ? 'sent' : 'received'}">
                    <div class="en-message-content">${escapeHtml(message.content)}</div>
                    <div class="en-admin-message-time">${timeAgo(message.created_at)}</div>
                </div>
            `;
        });

        $thread.html(html);
        $thread.scrollTop($thread[0].scrollHeight);
    }

    /**
     * Send admin message
     */
    function sendAdminMessage() {
        const $input = $('.en-admin-message-input');
        const content = $input.val().trim();

        if (!content || !EN_ADMIN.currentConversation) return;

        $.ajax({
            url: EN_ADMIN.ajax_url,
            type: 'POST',
            data: {
                action: 'en_send_admin_message',
                nonce: EN_ADMIN.nonce,
                conversation_id: EN_ADMIN.currentConversation,
                content: content
            },
            success: function(response) {
                if (response.success) {
                    $input.val('');
                    selectConversation(EN_ADMIN.currentConversation);
                }
            }
        });
    }

    /**
     * Search messages
     */
    function searchMessages(query) {
        // Implement message search functionality
        $.ajax({
            url: EN_ADMIN.ajax_url,
            type: 'POST',
            data: {
                action: 'en_search_messages',
                nonce: EN_ADMIN.nonce,
                query: query
            },
            success: function(response) {
                if (response.success) {
                    renderConversations(response.data.conversations);
                }
            }
        });
    }

    /**
     * Setup settings management
     */
    function setupSettings() {
        // Save settings
        $(document).on('click', '.en-save-settings', function() {
            const form = $(this).closest('form');
            saveSettings(form);
        });

        // Test email settings
        $(document).on('click', '.en-test-email', function() {
            testEmailSettings();
        });

        // Test push notifications
        $(document).on('click', '.en-test-push', function() {
            testPushNotifications();
        });

        // Import/Export settings
        $(document).on('click', '.en-export-settings', function() {
            exportSettings();
        });

        $(document).on('change', '.en-import-settings', function() {
            importSettings(this.files[0]);
        });
    }

    /**
     * Save settings form
     */
    function saveSettings(form) {
        const formData = new FormData(form[0]);
        formData.append('action', 'en_save_settings');
        formData.append('nonce', EN_ADMIN.nonce);

        $.ajax({
            url: EN_ADMIN.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotice('Settings saved successfully', 'success');
                } else {
                    showNotice('Failed to save settings', 'error');
                }
            }
        });
    }

    /**
     * Test email settings
     */
    function testEmailSettings() {
        $.ajax({
            url: EN_ADMIN.ajax_url,
            type: 'POST',
            data: {
                action: 'en_test_email',
                nonce: EN_ADMIN.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Test email sent successfully', 'success');
                } else {
                    showNotice('Failed to send test email: ' + response.data.message, 'error');
                }
            }
        });
    }

    /**
     * Test push notifications
     */
    function testPushNotifications() {
        $.ajax({
            url: EN_ADMIN.ajax_url,
            type: 'POST',
            data: {
                action: 'en_test_push',
                nonce: EN_ADMIN.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Test push notification sent', 'success');
                } else {
                    showNotice('Failed to send test push notification', 'error');
                }
            }
        });
    }

    /**
     * Setup modals
     */
    function setupModals() {
        // Close modal on background click
        $(document).on('click', '.en-admin-modal', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal on close button click
        $(document).on('click', '.en-modal-close', function() {
            closeModal();
        });

        // ESC key to close modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    }

    /**
     * Show modal
     */
    function showModal(title, content, footer = '') {
        const modalHtml = `
            <div class="en-admin-modal active">
                <div class="en-modal-content">
                    <div class="en-modal-header">
                        <h3 class="en-modal-title">${escapeHtml(title)}</h3>
                        <button class="en-modal-close">&times;</button>
                    </div>
                    <div class="en-modal-body">
                        ${content}
                    </div>
                    ${footer ? `<div class="en-modal-footer">${footer}</div>` : ''}
                </div>
            </div>
        `;

        $('body').append(modalHtml);
    }

    /**
     * Close modal
     */
    function closeModal() {
        $('.en-admin-modal').removeClass('active');
        setTimeout(function() {
            $('.en-admin-modal').remove();
        }, 300);
    }

    /**
     * Setup notices
     */
    function setupNotices() {
        // Auto-dismiss notices
        setTimeout(function() {
            $('.en-admin-notice').fadeOut();
        }, 5000);

        // Manual dismiss
        $(document).on('click', '.en-notice-dismiss', function() {
            $(this).closest('.en-admin-notice').fadeOut();
        });
    }

    /**
     * Show admin notice
     */
    function showNotice(message, type = 'info') {
        const noticeHtml = `
            <div class="en-admin-notice ${type}">
                <span>${escapeHtml(message)}</span>
                <button class="en-notice-dismiss">&times;</button>
            </div>
        `;

        $('.en-admin-dashboard').prepend(noticeHtml);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.en-admin-notice').first().fadeOut();
        }, 5000);
    }

    /**
     * Load dashboard data
     */
    function loadDashboardData() {
        $.ajax({
            url: EN_ADMIN.ajax_url,
            type: 'POST',
            data: {
                action: 'en_get_dashboard_data',
                nonce: EN_ADMIN.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboardStats(response.data.stats);
                    updateChartData(response.data.charts);
                    updateRecentActivity(response.data.activity);
                }
            }
        });
    }

    /**
     * Update dashboard statistics
     */
    function updateDashboardStats(stats) {
        Object.keys(stats).forEach(function(key) {
            const $card = $(`.en-stat-card[data-stat="${key}"]`);
            if ($card.length) {
                $card.find('.en-stat-value').text(stats[key].value);
                
                const $change = $card.find('.en-stat-change');
                if (stats[key].change !== undefined) {
                    const changeClass = stats[key].change > 0 ? 'positive' : 
                                       stats[key].change < 0 ? 'negative' : 'neutral';
                    const changeSymbol = stats[key].change > 0 ? '+' : '';
                    
                    $change.removeClass('positive negative neutral')
                           .addClass(changeClass)
                           .text(`${changeSymbol}${stats[key].change}% from last period`);
                }
            }
        });
    }

    /**
     * Update chart data
     */
    function updateChartData(chartData) {
        Object.keys(chartData).forEach(function(chartType) {
            if (EN_ADMIN.charts[chartType]) {
                const chart = EN_ADMIN.charts[chartType];
                const data = chartData[chartType];
                
                if (chartType === 'notifications') {
                    chart.data.labels = data.labels;
                    chart.data.datasets[0].data = data.sent;
                    chart.data.datasets[1].data = data.read;
                } else if (chartType === 'engagement') {
                    chart.data.datasets[0].data = [data.read, data.unread, data.dismissed];
                } else if (chartType === 'device') {
                    chart.data.datasets[0].data = [data.desktop, data.mobile, data.tablet];
                }
                
                chart.update();
            }
        });
    }

    /**
     * Update recent activity feed
     */
    function updateRecentActivity(activities) {
        const $feed = $('.en-activity-feed');
        let html = '';

        activities.forEach(function(activity) {
            html += `
                <div class="en-activity-item">
                    <div class="en-activity-icon ${activity.type}">
                        <span class="dashicons dashicons-${getActivityIcon(activity.type)}"></span>
                    </div>
                    <div class="en-activity-content">
                        <div class="en-activity-title">${escapeHtml(activity.title)}</div>
                        <div class="en-activity-description">${escapeHtml(activity.description)}</div>
                        <div class="en-activity-time">${timeAgo(activity.created_at)}</div>
                    </div>
                </div>
            `;
        });

        $feed.html(html);
    }

    /**
     * Get activity icon based on type
     */
    function getActivityIcon(type) {
        const icons = {
            notification: 'bell',
            message: 'email-alt',
            alert: 'warning',
            user: 'admin-users',
            settings: 'admin-settings'
        };
        return icons[type] || 'marker';
    }

    /**
     * Utility functions
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60
        };

        for (const [unit, secondsInUnit] of Object.entries(intervals)) {
            const interval = Math.floor(seconds / secondsInUnit);
            if (interval >= 1) {
                return `${interval} ${unit}${interval === 1 ? '' : 's'} ago`;
            }
        }

        return 'Just now';
    }

    // Initialize when document is ready
    $(document).ready(init);

})(jQuery);
