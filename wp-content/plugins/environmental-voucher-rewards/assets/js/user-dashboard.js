/**
 * Environmental Voucher & Rewards - User Dashboard JavaScript
 */

(function($) {
    'use strict';

    // Dashboard object
    var EVR_Dashboard = {
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.loadDashboardData();
            this.initTooltips();
        },

        bindEvents: function() {
            // Share achievement button
            $(document).on('click', '.evr-share-achievement', this.shareAchievement);
            
            // Update preferences
            $(document).on('change', '.evr-preference-input', this.updatePreferences);
            
            // Refresh dashboard
            $(document).on('click', '.evr-refresh-dashboard', this.refreshDashboard);
            
            // View achievement details
            $(document).on('click', '.evr-achievement-item', this.viewAchievementDetails);
            
            // Copy statistics
            $(document).on('click', '.evr-copy-stats', this.copyStatistics);
        },

        loadDashboardData: function() {
            if (typeof evr_dashboard === 'undefined') {
                return;
            }

            $.ajax({
                url: evr_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'evr_get_dashboard_data',
                    nonce: evr_dashboard.nonce
                },
                beforeSend: function() {
                    $('.evr-dashboard-widget').addClass('loading');
                },
                success: function(response) {
                    $('.evr-dashboard-widget').removeClass('loading');
                    
                    if (response.success) {
                        EVR_Dashboard.updateDashboardWidgets(response.data);
                    } else {
                        EVR_Dashboard.showMessage(response.message || evr_dashboard.messages.error, 'error');
                    }
                },
                error: function() {
                    $('.evr-dashboard-widget').removeClass('loading');
                    EVR_Dashboard.showMessage(evr_dashboard.messages.error, 'error');
                }
            });
        },

        updateDashboardWidgets: function(data) {
            // Update statistics
            if (data.voucher_stats) {
                $('.evr-stat-item').each(function() {
                    var $this = $(this);
                    var statType = $this.data('stat-type');
                    if (data.voucher_stats[statType]) {
                        $this.find('.evr-stat-number').text(data.voucher_stats[statType]);
                    }
                });
            }

            // Update progress bars
            if (data.loyalty_data) {
                this.updateProgressBars(data.loyalty_data);
            }

            // Update recent activity
            if (data.recent_activity) {
                this.updateActivityFeed(data.recent_activity);
            }
        },

        updateProgressBars: function(loyaltyData) {
            var progressPercentage = loyaltyData.progress_percentage || 0;
            
            $('.progress-fill').css('width', progressPercentage + '%');
            $('.current-points').text(this.numberFormat(loyaltyData.current_points || 0));
            $('.tier-name').text(loyaltyData.current_tier || 'Bronze');
            
            // Update circular progress
            var circle = $('.progress-ring-progress');
            if (circle.length) {
                var radius = parseFloat(circle.attr('r'));
                var circumference = 2 * Math.PI * radius;
                var offset = circumference - (progressPercentage / 100) * circumference;
                
                circle.css({
                    'stroke-dasharray': circumference,
                    'stroke-dashoffset': offset
                });
            }
        },

        updateActivityFeed: function(activities) {
            var $feed = $('.evr-activity-feed');
            if (!$feed.length || !activities.length) {
                return;
            }

            $feed.empty();
            
            activities.forEach(function(activity) {
                var icon = activity.type === 'voucher_earned' ? '🎫' : '⭐';
                var description = '';
                
                if (activity.type === 'voucher_earned') {
                    description = 'Earned voucher ' + activity.item + ' worth $' + activity.value;
                } else {
                    description = 'Earned ' + activity.value + ' points for ' + activity.item;
                }
                
                var timeAgo = EVR_Dashboard.timeAgo(activity.date);
                
                var html = '<div class="evr-activity-item">' +
                          '<span class="activity-icon">' + icon + '</span>' +
                          '<div class="activity-content">' +
                          '<p>' + description + '</p>' +
                          '<span class="activity-date">' + timeAgo + ' ago</span>' +
                          '</div>' +
                          '</div>';
                
                $feed.append(html);
            });
        },

        shareAchievement: function(e) {
            e.preventDefault();
            
            var achievementId = $(this).data('achievement');
            var achievementTitle = $(this).data('title');
            
            $.ajax({
                url: evr_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'evr_share_achievement',
                    nonce: evr_dashboard.nonce,
                    achievement_id: achievementId,
                    achievement_title: achievementTitle
                },
                beforeSend: function() {
                    $(e.target).prop('disabled', true).text(evr_dashboard.messages.loading);
                },
                success: function(response) {
                    $(e.target).prop('disabled', false).text('Share');
                    
                    if (response.success) {
                        EVR_Dashboard.showShareModal(response.share_content, response.share_url);
                        EVR_Dashboard.showMessage(evr_dashboard.messages.shared, 'success');
                    } else {
                        EVR_Dashboard.showMessage(response.message || evr_dashboard.messages.error, 'error');
                    }
                },
                error: function() {
                    $(e.target).prop('disabled', false).text('Share');
                    EVR_Dashboard.showMessage(evr_dashboard.messages.error, 'error');
                }
            });
        },

        showShareModal: function(content, url) {
            var modal = '<div class="evr-share-modal">' +
                       '<div class="evr-share-modal-content">' +
                       '<span class="evr-close">&times;</span>' +
                       '<h3>Share Your Achievement</h3>' +
                       '<textarea readonly class="evr-share-text">' + content + '</textarea>' +
                       '<div class="evr-share-buttons">' +
                       '<a href="https://twitter.com/intent/tweet?text=' + encodeURIComponent(content) + '&url=' + encodeURIComponent(url) + '" target="_blank" class="evr-share-twitter">Twitter</a>' +
                       '<a href="https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url) + '&quote=' + encodeURIComponent(content) + '" target="_blank" class="evr-share-facebook">Facebook</a>' +
                       '<button class="evr-copy-text">Copy Text</button>' +
                       '</div>' +
                       '</div>' +
                       '</div>';
            
            $('body').append(modal);
            
            // Bind modal events
            $('.evr-close, .evr-share-modal').on('click', function(e) {
                if (e.target === this) {
                    $('.evr-share-modal').remove();
                }
            });
            
            $('.evr-copy-text').on('click', function() {
                $('.evr-share-text').select();
                document.execCommand('copy');
                $(this).text('Copied!').prop('disabled', true);
                setTimeout(function() {
                    $('.evr-copy-text').text('Copy Text').prop('disabled', false);
                }, 2000);
            });
        },

        updatePreferences: function() {
            var preferences = {};
            
            $('.evr-preference-input').each(function() {
                var $this = $(this);
                var key = $this.data('preference');
                var value = $this.is(':checkbox') ? ($this.is(':checked') ? 1 : 0) : $this.val();
                preferences[key] = value;
            });
            
            $.ajax({
                url: evr_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'evr_update_profile_preferences',
                    nonce: evr_dashboard.nonce,
                    preferences: preferences
                },
                success: function(response) {
                    if (response.success) {
                        EVR_Dashboard.showMessage(evr_dashboard.messages.saved, 'success');
                    } else {
                        EVR_Dashboard.showMessage(response.message || evr_dashboard.messages.error, 'error');
                    }
                }
            });
        },

        refreshDashboard: function(e) {
            e.preventDefault();
            EVR_Dashboard.loadDashboardData();
        },

        viewAchievementDetails: function() {
            var $this = $(this);
            if (!$this.hasClass('unlocked')) {
                return;
            }
            
            var achievementId = $this.data('achievement-id');
            // Implementation for achievement details modal
        },

        copyStatistics: function(e) {
            e.preventDefault();
            
            var stats = '';
            $('.evr-stat-item').each(function() {
                var label = $(this).find('.evr-stat-label').text();
                var number = $(this).find('.evr-stat-number').text();
                stats += label + ': ' + number + '\n';
            });
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(stats).then(function() {
                    EVR_Dashboard.showMessage('Statistics copied to clipboard!', 'success');
                });
            }
        },

        initCharts: function() {
            if (typeof Chart === 'undefined') {
                return;
            }

            // Initialize progress charts
            this.initProgressChart();
            this.initImpactChart();
        },

        initProgressChart: function() {
            var ctx = document.getElementById('evr-progress-chart');
            if (!ctx) return;

            var progressData = {
                labels: ['Current Progress', 'Remaining'],
                datasets: [{
                    data: [65, 35], // This would come from actual data
                    backgroundColor: ['#4CAF50', '#E0E0E0'],
                    borderWidth: 0
                }]
            };

            new Chart(ctx, {
                type: 'doughnut',
                data: progressData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        },

        initImpactChart: function() {
            var ctx = document.getElementById('evr-impact-chart');
            if (!ctx) return;

            var impactData = {
                labels: ['CO2 Saved', 'Waste Classified', 'Vouchers Used'],
                datasets: [{
                    label: 'Environmental Impact',
                    data: [45, 78, 23], // This would come from actual data
                    backgroundColor: ['#4CAF50', '#2196F3', '#FF9800'],
                    borderWidth: 0
                }]
            };

            new Chart(ctx, {
                type: 'bar',
                data: impactData,
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

        initTooltips: function() {
            // Initialize tooltips for various elements
            $('[data-tooltip]').each(function() {
                var $this = $(this);
                var tooltip = $this.data('tooltip');
                
                $this.on('mouseenter', function(e) {
                    var tooltipEl = $('<div class="evr-tooltip">' + tooltip + '</div>');
                    $('body').append(tooltipEl);
                    
                    tooltipEl.css({
                        position: 'absolute',
                        top: e.pageY - tooltipEl.outerHeight() - 10,
                        left: e.pageX - tooltipEl.outerWidth() / 2,
                        zIndex: 9999
                    });
                }).on('mouseleave', function() {
                    $('.evr-tooltip').remove();
                });
            });
        },

        showMessage: function(message, type) {
            var messageEl = $('<div class="evr-message ' + type + '">' + message + '</div>');
            $('.evr-user-dashboard').prepend(messageEl);
            
            setTimeout(function() {
                messageEl.fadeOut(function() {
                    messageEl.remove();
                });
            }, 5000);
        },

        numberFormat: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },

        timeAgo: function(dateString) {
            var now = new Date();
            var date = new Date(dateString);
            var diffMs = now - date;
            var diffMins = Math.floor(diffMs / 60000);
            var diffHours = Math.floor(diffMins / 60);
            var diffDays = Math.floor(diffHours / 24);
            
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return diffMins + ' minutes';
            if (diffHours < 24) return diffHours + ' hours';
            if (diffDays < 7) return diffDays + ' days';
            return Math.floor(diffDays / 7) + ' weeks';
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        EVR_Dashboard.init();
    });

    // Expose globally for external access
    window.EVR_Dashboard = EVR_Dashboard;

})(jQuery);
