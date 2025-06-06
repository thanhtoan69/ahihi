/**
 * Environmental Analytics Frontend JavaScript
 * 
 * Handles client-side tracking, user interactions, and AJAX requests
 * 
 * @package Environmental_Analytics_Reporting
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // Environmental Analytics object
    window.EnvAnalytics = {
        config: {
            ajaxUrl: env_analytics_vars.ajax_url,
            nonce: env_analytics_vars.nonce,
            trackingEnabled: env_analytics_vars.tracking_enabled,
            sessionId: env_analytics_vars.session_id,
            userId: env_analytics_vars.user_id,
            ga4MeasurementId: env_analytics_vars.ga4_measurement_id
        },
        
        // Initialize analytics
        init: function() {
            if (!this.config.trackingEnabled) {
                return;
            }
            
            this.initializeSession();
            this.bindEvents();
            this.trackPageView();
            this.startEngagementTracking();
            this.initializeGA4();
        },
        
        // Initialize user session
        initializeSession: function() {
            if (!this.config.sessionId) {
                this.createSession();
            }
            
            // Update last activity
            this.updateLastActivity();
            
            // Set session refresh interval
            setInterval(() => {
                this.updateLastActivity();
            }, 30000); // Update every 30 seconds
        },
        
        // Create new session
        createSession: function() {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'env_start_session',
                    nonce: this.config.nonce,
                    referrer: document.referrer,
                    utm_source: this.getUrlParameter('utm_source'),
                    utm_medium: this.getUrlParameter('utm_medium'),
                    utm_campaign: this.getUrlParameter('utm_campaign')
                },
                success: (response) => {
                    if (response.success) {
                        this.config.sessionId = response.data.session_id;
                    }
                }
            });
        },
        
        // Update last activity
        updateLastActivity: function() {
            if (this.config.sessionId) {
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'env_update_activity',
                        nonce: this.config.nonce,
                        session_id: this.config.sessionId
                    }
                });
            }
        },
        
        // Bind tracking events
        bindEvents: function() {
            // Track clicks on important elements
            $('a[href*="donation"], button[data-action="donate"]').on('click', (e) => {
                this.trackEvent('donation_click', {
                    element: e.target.tagName,
                    text: $(e.target).text().trim(),
                    url: $(e.target).attr('href') || 'button'
                });
            });
            
            // Track petition signatures
            $('form[data-form-type="petition"], .petition-form').on('submit', (e) => {
                this.trackEvent('petition_sign', {
                    petition_id: $(e.target).data('petition-id') || 'unknown',
                    form_type: 'petition'
                });
            });
            
            // Track item exchange interactions
            $('a[href*="exchange"], button[data-action="exchange"]').on('click', (e) => {
                this.trackEvent('item_exchange_click', {
                    element: e.target.tagName,
                    text: $(e.target).text().trim()
                });
            });
            
            // Track forum interactions
            $('.forum-post-form, form[data-form-type="forum"]').on('submit', (e) => {
                this.trackEvent('forum_post', {
                    topic_id: $(e.target).data('topic-id') || 'unknown'
                });
            });
            
            // Track social sharing
            $('a[href*="facebook.com/sharer"], a[href*="twitter.com/intent"], a[href*="linkedin.com/sharing"]').on('click', (e) => {
                const platform = this.getSocialPlatform($(e.target).attr('href'));
                this.trackEvent('social_share', {
                    platform: platform,
                    url: window.location.href,
                    title: document.title
                });
            });
            
            // Track downloads
            $('a[href$=".pdf"], a[href$=".doc"], a[href$=".docx"], a[href$=".zip"]').on('click', (e) => {
                const url = $(e.target).attr('href');
                const filename = url.split('/').pop();
                this.trackEvent('file_download', {
                    filename: filename,
                    url: url,
                    file_type: filename.split('.').pop()
                });
            });
            
            // Track form submissions
            $('form:not(.search-form)').on('submit', (e) => {
                const formType = $(e.target).data('form-type') || 'general';
                this.trackEvent('form_submission', {
                    form_type: formType,
                    form_id: $(e.target).attr('id') || 'unknown'
                });
            });
            
            // Track search queries
            $('.search-form, form[role="search"]').on('submit', (e) => {
                const query = $(e.target).find('input[type="search"], input[name="s"]').val();
                if (query) {
                    this.trackEvent('search', {
                        query: query.trim(),
                        results_page: window.location.href
                    });
                }
            });
            
            // Track scroll depth
            this.initScrollTracking();
            
            // Track time on page
            this.initTimeTracking();
        },
        
        // Track page views
        trackPageView: function() {
            this.trackEvent('page_view', {
                page_url: window.location.href,
                page_title: document.title,
                referrer: document.referrer
            });
        },
        
        // Track custom events
        trackEvent: function(eventType, eventData = {}) {
            const trackingData = {
                action: 'env_track_event',
                nonce: this.config.nonce,
                event_type: eventType,
                event_data: JSON.stringify(eventData),
                session_id: this.config.sessionId,
                user_id: this.config.userId,
                page_url: window.location.href,
                page_title: document.title,
                timestamp: new Date().toISOString()
            };
            
            // Send to WordPress backend
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: trackingData
            });
            
            // Send to Google Analytics 4 if configured
            if (this.config.ga4MeasurementId && window.gtag) {
                this.sendToGA4(eventType, eventData);
            }
        },
        
        // Send event to Google Analytics 4
        sendToGA4: function(eventType, eventData) {
            const ga4EventName = this.mapEventToGA4(eventType);
            window.gtag('event', ga4EventName, {
                ...eventData,
                custom_parameter_1: eventType,
                page_location: window.location.href,
                page_title: document.title
            });
        },
        
        // Map custom events to GA4 event names
        mapEventToGA4: function(eventType) {
            const eventMap = {
                'page_view': 'page_view',
                'donation': 'purchase',
                'donation_click': 'select_item',
                'petition_sign': 'sign_up',
                'item_exchange': 'add_to_cart',
                'forum_post': 'post_score',
                'social_share': 'share',
                'file_download': 'file_download',
                'form_submission': 'generate_lead',
                'search': 'search',
                'achievement_unlocked': 'unlock_achievement'
            };
            
            return eventMap[eventType] || 'custom_event';
        },
        
        // Initialize scroll tracking
        initScrollTracking: function() {
            let scrollDepths = [25, 50, 75, 100];
            let trackedDepths = [];
            
            $(window).on('scroll', () => {
                const scrollTop = $(window).scrollTop();
                const documentHeight = $(document).height();
                const windowHeight = $(window).height();
                const scrollPercent = Math.round((scrollTop / (documentHeight - windowHeight)) * 100);
                
                scrollDepths.forEach(depth => {
                    if (scrollPercent >= depth && !trackedDepths.includes(depth)) {
                        trackedDepths.push(depth);
                        this.trackEvent('scroll_depth', {
                            depth_percentage: depth,
                            page_url: window.location.href
                        });
                    }
                });
            });
        },
        
        // Initialize time tracking
        initTimeTracking: function() {
            let timeSpent = 0;
            let isActive = true;
            let timeIntervals = [30, 60, 120, 300]; // Track at 30s, 1m, 2m, 5m
            let trackedIntervals = [];
            
            // Track time spent
            setInterval(() => {
                if (isActive) {
                    timeSpent += 5;
                    
                    timeIntervals.forEach(interval => {
                        if (timeSpent >= interval && !trackedIntervals.includes(interval)) {
                            trackedIntervals.push(interval);
                            this.trackEvent('time_on_page', {
                                seconds: interval,
                                page_url: window.location.href
                            });
                        }
                    });
                }
            }, 5000);
            
            // Track user activity
            $(document).on('mousemove keypress scroll click', () => {
                isActive = true;
            });
            
            // Mark as inactive after 30 seconds of no activity
            setInterval(() => {
                isActive = false;
            }, 30000);
        },
        
        // Start engagement tracking
        startEngagementTracking: function() {
            let engagementScore = 0;
            let engagementEvents = 0;
            
            // Track various engagement signals
            $(document).on('click', 'a, button', () => {
                engagementScore += 1;
                engagementEvents++;
            });
            
            $(document).on('submit', 'form', () => {
                engagementScore += 5;
                engagementEvents++;
            });
            
            $(window).on('scroll', () => {
                engagementScore += 0.1;
            });
            
            // Send engagement data every 2 minutes
            setInterval(() => {
                if (engagementEvents > 0) {
                    this.trackEvent('engagement_update', {
                        engagement_score: Math.round(engagementScore),
                        events_count: engagementEvents,
                        session_duration: Math.round(timeSpent)
                    });
                    
                    engagementEvents = 0; // Reset counter
                }
            }, 120000);
        },
        
        // Initialize Google Analytics 4
        initializeGA4: function() {
            if (!this.config.ga4MeasurementId) {
                return;
            }
            
            // Load GA4 script if not already loaded
            if (!window.gtag) {
                const script = document.createElement('script');
                script.async = true;
                script.src = `https://www.googletagmanager.com/gtag/js?id=${this.config.ga4MeasurementId}`;
                document.head.appendChild(script);
                
                window.dataLayer = window.dataLayer || [];
                window.gtag = function() {
                    dataLayer.push(arguments);
                };
                
                gtag('js', new Date());
                gtag('config', this.config.ga4MeasurementId, {
                    page_title: document.title,
                    page_location: window.location.href,
                    user_id: this.config.userId
                });
            }
        },
        
        // Utility functions
        getUrlParameter: function(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        },
        
        getSocialPlatform: function(url) {
            if (url.includes('facebook.com')) return 'facebook';
            if (url.includes('twitter.com')) return 'twitter';
            if (url.includes('linkedin.com')) return 'linkedin';
            if (url.includes('instagram.com')) return 'instagram';
            return 'other';
        },
        
        // Public API for custom tracking
        track: function(eventType, eventData) {
            this.trackEvent(eventType, eventData);
        },
        
        // Track achievement unlocks
        trackAchievement: function(achievementName, achievementData = {}) {
            this.trackEvent('achievement_unlocked', {
                achievement_name: achievementName,
                ...achievementData
            });
        },
        
        // Track conversions
        trackConversion: function(goalName, value = 0, transactionId = null) {
            this.trackEvent('conversion', {
                goal_name: goalName,
                conversion_value: value,
                transaction_id: transactionId
            });
        }
    };
    
    // Admin Dashboard JavaScript
    window.EnvAnalyticsAdmin = {
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.loadDashboardData();
        },
        
        bindEvents: function() {
            // Conversion goal management
            $('#add-conversion-goal').on('click', this.showAddGoalModal);
            $('#save-conversion-goal').on('click', this.saveConversionGoal);
            $('.edit-goal').on('click', this.editConversionGoal);
            $('.delete-goal').on('click', this.deleteConversionGoal);
            
            // Report generation
            $('#generate-custom-report').on('click', this.generateCustomReport);
            $('#schedule-reports').on('click', this.scheduleReports);
            $('.download-report').on('click', this.downloadReport);
            
            // Date range picker
            $('#analytics-date-range').on('change', this.updateDateRange);
            
            // Refresh buttons
            $('.refresh-data').on('click', this.refreshData);
        },
        
        showAddGoalModal: function() {
            $('#conversion-goal-modal').show();
            $('#conversion-goal-form')[0].reset();
            $('#goal-id').val('');
        },
        
        saveConversionGoal: function() {
            const formData = {
                action: 'env_save_conversion_goal',
                nonce: env_analytics_vars.nonce,
                goal_id: $('#goal-id').val(),
                goal_name: $('#goal-name').val(),
                goal_type: $('#goal-type').val(),
                target_value: $('#target-value').val(),
                funnel_steps: $('#funnel-steps').val()
            };
            
            $.ajax({
                url: env_analytics_vars.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#conversion-goal-modal').hide();
                        location.reload();
                    } else {
                        alert('Error saving goal: ' + response.data.message);
                    }
                }
            });
        },
        
        editConversionGoal: function(e) {
            const goalId = $(e.target).data('goal-id');
            
            $.ajax({
                url: env_analytics_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'env_get_conversion_goal',
                    nonce: env_analytics_vars.nonce,
                    goal_id: goalId
                },
                success: function(response) {
                    if (response.success) {
                        const goal = response.data;
                        $('#goal-id').val(goal.id);
                        $('#goal-name').val(goal.goal_name);
                        $('#goal-type').val(goal.goal_type);
                        $('#target-value').val(goal.target_value);
                        $('#funnel-steps').val(goal.funnel_steps);
                        $('#conversion-goal-modal').show();
                    }
                }
            });
        },
        
        deleteConversionGoal: function(e) {
            if (!confirm('Are you sure you want to delete this conversion goal?')) {
                return;
            }
            
            const goalId = $(e.target).data('goal-id');
            
            $.ajax({
                url: env_analytics_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'env_delete_conversion_goal',
                    nonce: env_analytics_vars.nonce,
                    goal_id: goalId
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error deleting goal: ' + response.data.message);
                    }
                }
            });
        },
        
        generateCustomReport: function() {
            const startDate = $('#report-start-date').val();
            const endDate = $('#report-end-date').val();
            const reportType = $('#report-type').val();
            
            if (!startDate || !endDate) {
                alert('Please select start and end dates');
                return;
            }
            
            $('#generate-custom-report').prop('disabled', true).text('Generating...');
            
            $.ajax({
                url: env_analytics_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'generate_custom_report',
                    nonce: env_analytics_vars.nonce,
                    start_date: startDate,
                    end_date: endDate,
                    report_type: reportType
                },
                success: function(response) {
                    $('#generate-custom-report').prop('disabled', false).text('Generate Report');
                    
                    if (response.success) {
                        alert('Report generated successfully!');
                        location.reload();
                    } else {
                        alert('Error generating report: ' + response.data.message);
                    }
                }
            });
        },
        
        scheduleReports: function() {
            const reportType = $('#schedule-report-type').val();
            const schedule = $('#schedule-frequency').val();
            const emailEnabled = $('#email-notifications').is(':checked');
            
            $.ajax({
                url: env_analytics_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'schedule_report',
                    nonce: env_analytics_vars.nonce,
                    report_type: reportType,
                    schedule: schedule,
                    email_enabled: emailEnabled
                },
                success: function(response) {
                    if (response.success) {
                        alert('Report schedule updated successfully!');
                    } else {
                        alert('Error updating schedule: ' + response.data.message);
                    }
                }
            });
        },
        
        downloadReport: function(e) {
            const reportId = $(e.target).data('report-id');
            const format = $(e.target).data('format');
            
            $.ajax({
                url: env_analytics_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'download_report',
                    nonce: env_analytics_vars.nonce,
                    report_id: reportId,
                    format: format
                },
                success: function(response) {
                    if (response.success) {
                        // Create download link
                        const blob = new Blob([response.data.html], {type: 'text/html'});
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `env-analytics-report-${reportId}.html`;
                        a.click();
                        window.URL.revokeObjectURL(url);
                    }
                }
            });
        },
        
        updateDateRange: function() {
            const dateRange = $('#analytics-date-range').val();
            const [startDate, endDate] = dateRange.split(' to ');
            
            this.loadDashboardData(startDate, endDate);
        },
        
        refreshData: function() {
            location.reload();
        },
        
        loadDashboardData: function(startDate = null, endDate = null) {
            // Load analytics data for dashboard
            $.ajax({
                url: env_analytics_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'env_get_dashboard_data',
                    nonce: env_analytics_vars.nonce,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    if (response.success) {
                        // Update dashboard metrics
                        $('.metric-sessions').text(response.data.sessions);
                        $('.metric-users').text(response.data.users);
                        $('.metric-pageviews').text(response.data.pageviews);
                        $('.metric-duration').text(response.data.avg_duration);
                        $('.metric-bounce').text(response.data.bounce_rate + '%');
                        
                        // Update charts
                        EnvAnalyticsAdmin.updateCharts(response.data);
                    }
                }
            });
        },
        
        initCharts: function() {
            // Initialize Chart.js charts
            if (typeof Chart === 'undefined') {
                return;
            }
            
            // Sessions over time chart
            const sessionsCtx = document.getElementById('sessions-chart');
            if (sessionsCtx) {
                this.sessionsChart = new Chart(sessionsCtx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Sessions',
                            data: [],
                            borderColor: '#4CAF50',
                            backgroundColor: 'rgba(76, 175, 80, 0.1)',
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
            }
            
            // User segments chart
            const segmentsCtx = document.getElementById('segments-chart');
            if (segmentsCtx) {
                this.segmentsChart = new Chart(segmentsCtx, {
                    type: 'doughnut',
                    data: {
                        labels: [],
                        datasets: [{
                            data: [],
                            backgroundColor: [
                                '#4CAF50',
                                '#2196F3',
                                '#FF9800',
                                '#F44336',
                                '#9C27B0'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        },
        
        updateCharts: function(data) {
            // Update sessions chart
            if (this.sessionsChart && data.sessions_timeline) {
                this.sessionsChart.data.labels = data.sessions_timeline.map(item => item.date);
                this.sessionsChart.data.datasets[0].data = data.sessions_timeline.map(item => item.sessions);
                this.sessionsChart.update();
            }
            
            // Update segments chart
            if (this.segmentsChart && data.user_segments) {
                this.segmentsChart.data.labels = Object.keys(data.user_segments);
                this.segmentsChart.data.datasets[0].data = Object.values(data.user_segments);
                this.segmentsChart.update();
            }
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize frontend tracking
        if (typeof env_analytics_vars !== 'undefined') {
            EnvAnalytics.init();
        }
        
        // Initialize admin dashboard if on admin page
        if ($('.env-analytics-admin').length > 0) {
            EnvAnalyticsAdmin.init();
        }
    });
    
})(jQuery);
