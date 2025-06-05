/**
 * Environmental Social Viral Frontend JavaScript
 * 
 * Handles all frontend interactions including sharing, referral tracking,
 * viral content loading, and user interface enhancements.
 * 
 * @package Environmental_Social_Viral
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    var EnvSocialViral = {
        
        /**
         * Initialize the plugin
         */
        init: function() {
            this.bindEvents();
            this.initializeComponents();
            this.trackPageView();
            this.processReferral();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Sharing button clicks
            $(document).on('click', '.env-share-button', this.handleShare);
            
            // Copy link buttons
            $(document).on('click', '.env-copy-button, .env-share-copy', this.handleCopyLink);
            
            // Referral share buttons
            $(document).on('click', '.env-referral-share-button', this.handleReferralShare);
            
            // Viral dashboard interactions
            $(document).on('click', '.env-load-more-viral', this.loadMoreViralContent);
            
            // User preference changes
            $(document).on('change', '.env-user-preference', this.saveUserPreference);
            
            // Real-time stats updates
            if ($('.env-sharing-stats').length) {
                setInterval(this.updateStats, 30000); // Update every 30 seconds
            }
        },
        
        /**
         * Initialize components
         */
        initializeComponents: function() {
            this.initViralCharts();
            this.initTooltips();
            this.initLazyLoading();
            this.initAnimations();
        },
        
        /**
         * Handle share button clicks
         */
        handleShare: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var platform = $button.data('platform');
            var url = $button.data('url');
            var title = $button.data('title');
            var postId = $button.closest('.env-sharing-buttons').data('post-id');
            
            // Show loading state
            $button.addClass('env-sharing').prop('disabled', true);
            var originalText = $button.find('.env-share-label').text();
            $button.find('.env-share-label').text(envSocialViral.strings.loading);
            
            // Track the share
            EnvSocialViral.trackShare(postId, platform).then(function(response) {
                // Open sharing window
                EnvSocialViral.openSharingWindow(platform, url, title);
                
                // Update button state
                if (response.success) {
                    $button.find('.env-share-count').text(response.data.new_count);
                    EnvSocialViral.showNotification(envSocialViral.strings.sharing_success, 'success');
                }
            }).catch(function(error) {
                EnvSocialViral.showNotification(envSocialViral.strings.sharing_error, 'error');
            }).finally(function() {
                // Reset button state
                $button.removeClass('env-sharing').prop('disabled', false);
                $button.find('.env-share-label').text(originalText);
            });
        },
        
        /**
         * Handle copy link functionality
         */
        handleCopyLink: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var url = $button.data('url');
            var targetId = $button.data('target');
            
            if (targetId) {
                // Copy from input field
                var $input = $('#' + targetId);
                url = $input.val();
                $input.select();
            }
            
            // Try to copy to clipboard
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    EnvSocialViral.showNotification(envSocialViral.strings.copy_success, 'success');
                }).catch(function() {
                    EnvSocialViral.fallbackCopyTextToClipboard(url);
                });
            } else {
                EnvSocialViral.fallbackCopyTextToClipboard(url);
            }
        },
        
        /**
         * Handle referral share button clicks
         */
        handleReferralShare: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var platform = $button.data('platform');
            var url = $button.data('url');
            var text = $button.data('text');
            
            // Open sharing window for referral
            EnvSocialViral.openSharingWindow(platform, url, text);
        },
        
        /**
         * Load more viral content
         */
        loadMoreViralContent: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $container = $button.closest('.env-viral-content-widget');
            var offset = $container.find('.env-viral-content-item').length;
            
            $button.addClass('loading').text(envSocialViral.strings.loading);
            
            $.post(envSocialViral.ajax_url, {
                action: 'env_get_viral_content',
                offset: offset,
                limit: 5,
                period: $container.data('period') || 7,
                post_type: $container.data('post-type') || 'post',
                nonce: envSocialViral.nonce
            }).done(function(response) {
                if (response.success && response.data.length > 0) {
                    var html = EnvSocialViral.renderViralContent(response.data);
                    $container.find('.env-viral-content-list').append(html);
                    
                    if (response.data.length < 5) {
                        $button.hide(); // No more content
                    }
                } else {
                    $button.hide();
                }
            }).fail(function() {
                EnvSocialViral.showNotification('Error loading content', 'error');
            }).always(function() {
                $button.removeClass('loading').text('Load More');
            });
        },
        
        /**
         * Save user preference
         */
        saveUserPreference: function(e) {
            var $input = $(this);
            var preference = $input.data('preference');
            var value = $input.val();
            
            $.post(envSocialViral.ajax_url, {
                action: 'env_save_user_preference',
                preference: preference,
                value: value,
                nonce: envSocialViral.nonce
            });
        },
        
        /**
         * Track share action
         */
        trackShare: function(postId, platform) {
            return $.post(envSocialViral.ajax_url, {
                action: 'env_track_share',
                post_id: postId,
                platform: platform,
                nonce: envSocialViral.nonce
            });
        },
        
        /**
         * Track page view
         */
        trackPageView: function() {
            if (envSocialViral.current_post_id) {
                $.post(envSocialViral.ajax_url, {
                    action: 'env_track_view',
                    post_id: envSocialViral.current_post_id,
                    nonce: envSocialViral.nonce
                });
            }
        },
        
        /**
         * Process referral if present in URL
         */
        processReferral: function() {
            var urlParams = new URLSearchParams(window.location.search);
            var refCode = urlParams.get('ref') || urlParams.get('referral');
            
            if (refCode) {
                $.post(envSocialViral.ajax_url, {
                    action: 'env_process_referral',
                    referral_code: refCode,
                    post_id: envSocialViral.current_post_id,
                    nonce: envSocialViral.nonce
                }).done(function(response) {
                    if (response.success) {
                        EnvSocialViral.showNotification(envSocialViral.strings.referral_applied, 'success');
                        
                        // Remove referral parameter from URL
                        var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                        window.history.replaceState({path: newUrl}, '', newUrl);
                    }
                });
            }
        },
        
        /**
         * Open sharing window for different platforms
         */
        openSharingWindow: function(platform, url, title) {
            var shareUrl = '';
            var encodedUrl = encodeURIComponent(url);
            var encodedTitle = encodeURIComponent(title);
            
            switch (platform) {
                case 'facebook':
                    shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodedUrl;
                    break;
                case 'twitter':
                    shareUrl = 'https://twitter.com/intent/tweet?url=' + encodedUrl + '&text=' + encodedTitle;
                    break;
                case 'linkedin':
                    shareUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodedUrl;
                    break;
                case 'pinterest':
                    shareUrl = 'https://pinterest.com/pin/create/button/?url=' + encodedUrl + '&description=' + encodedTitle;
                    break;
                case 'whatsapp':
                    shareUrl = 'https://wa.me/?text=' + encodedTitle + ' ' + encodedUrl;
                    break;
                case 'telegram':
                    shareUrl = 'https://t.me/share/url?url=' + encodedUrl + '&text=' + encodedTitle;
                    break;
                case 'email':
                    shareUrl = 'mailto:?subject=' + encodedTitle + '&body=' + encodedUrl;
                    break;
                default:
                    return;
            }
            
            if (platform === 'email') {
                window.location.href = shareUrl;
            } else {
                window.open(shareUrl, 'share', 'width=550,height=400,resizable=yes,scrollbars=yes');
            }
        },
        
        /**
         * Fallback copy to clipboard
         */
        fallbackCopyTextToClipboard: function(text) {
            var textArea = document.createElement("textarea");
            textArea.value = text;
            
            // Avoid scrolling to bottom
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    EnvSocialViral.showNotification(envSocialViral.strings.copy_success, 'success');
                } else {
                    EnvSocialViral.showNotification(envSocialViral.strings.copy_error, 'error');
                }
            } catch (err) {
                EnvSocialViral.showNotification(envSocialViral.strings.copy_error, 'error');
            }
            
            document.body.removeChild(textArea);
        },
        
        /**
         * Show notification to user
         */
        showNotification: function(message, type) {
            var $notification = $('<div class="env-notification env-notification-' + type + '">' + message + '</div>');
            
            // Add to page
            if ($('.env-notifications').length === 0) {
                $('body').append('<div class="env-notifications"></div>');
            }
            
            $('.env-notifications').append($notification);
            
            // Show with animation
            setTimeout(function() {
                $notification.addClass('env-notification-show');
            }, 100);
            
            // Auto-hide after 3 seconds
            setTimeout(function() {
                $notification.removeClass('env-notification-show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        },
        
        /**
         * Update sharing statistics in real-time
         */
        updateStats: function() {
            $('.env-sharing-stats').each(function() {
                var $stats = $(this);
                var postId = $stats.data('post-id') || envSocialViral.current_post_id;
                
                if (postId) {
                    $.post(envSocialViral.ajax_url, {
                        action: 'env_get_sharing_stats',
                        post_id: postId,
                        nonce: envSocialViral.nonce
                    }).done(function(response) {
                        if (response.success) {
                            EnvSocialViral.updateStatsDisplay($stats, response.data);
                        }
                    });
                }
            });
        },
        
        /**
         * Update stats display
         */
        updateStatsDisplay: function($stats, data) {
            $stats.find('.env-shares-count').text(data.total_shares);
            $stats.find('.env-total-shares').text(data.total_shares + ' shares');
            
            // Update individual platform counts
            $.each(data.platforms, function(platform, count) {
                $stats.find('.env-share-' + platform + ' .env-share-count').text(count);
            });
            
            // Update viral indicator
            if (data.viral_coefficient >= envSocialViral.viral_threshold) {
                if ($stats.find('.env-viral-badge').length === 0) {
                    $stats.append('<span class="env-viral-badge">Viral!</span>');
                }
            }
        },
        
        /**
         * Render viral content HTML
         */
        renderViralContent: function(content) {
            var html = '';
            
            $.each(content, function(index, item) {
                html += '<div class="env-viral-content-item">';
                html += '<div class="env-content-rank">' + (index + 1) + '</div>';
                html += '<div class="env-content-info">';
                html += '<h5><a href="' + item.permalink + '">' + item.title + '</a></h5>';
                html += '<div class="env-content-stats">';
                html += '<span class="env-shares">' + item.total_shares + ' shares</span>';
                html += '<span class="env-coefficient">Viral Score: ' + item.viral_coefficient + '</span>';
                html += '</div>';
                html += '</div>';
                
                if (item.viral_coefficient >= envSocialViral.viral_threshold) {
                    html += '<div class="env-viral-indicator"><span class="env-viral-badge">🔥</span></div>';
                }
                
                html += '</div>';
            });
            
            return html;
        },
        
        /**
         * Initialize viral performance charts
         */
        initViralCharts: function() {
            if (typeof Chart === 'undefined' || !$('#viral-performance-chart').length) {
                return;
            }
            
            var ctx = document.getElementById('viral-performance-chart').getContext('2d');
            
            // Get chart data via AJAX
            $.post(envSocialViral.ajax_url, {
                action: 'env_get_viral_chart_data',
                user_id: envSocialViral.user_id,
                period: 30,
                nonce: envSocialViral.nonce
            }).done(function(response) {
                if (response.success) {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: response.data.labels,
                            datasets: [{
                                label: 'Viral Coefficient',
                                data: response.data.coefficients,
                                borderColor: '#4CAF50',
                                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                                tension: 0.4
                            }, {
                                label: 'Total Shares',
                                data: response.data.shares,
                                borderColor: '#2196F3',
                                backgroundColor: 'rgba(33, 150, 243, 0.1)',
                                tension: 0.4,
                                yAxisID: 'y1'
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Viral Performance Over Time'
                                }
                            },
                            scales: {
                                y: {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    title: {
                                        display: true,
                                        text: 'Viral Coefficient'
                                    }
                                },
                                y1: {
                                    type: 'linear',
                                    display: true,
                                    position: 'right',
                                    title: {
                                        display: true,
                                        text: 'Total Shares'
                                    },
                                    grid: {
                                        drawOnChartArea: false,
                                    },
                                }
                            }
                        }
                    });
                }
            });
        },
        
        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Simple tooltip implementation
            $(document).on('mouseenter', '[data-tooltip]', function() {
                var $element = $(this);
                var tooltipText = $element.data('tooltip');
                
                var $tooltip = $('<div class="env-tooltip">' + tooltipText + '</div>');
                $('body').append($tooltip);
                
                var elementOffset = $element.offset();
                $tooltip.css({
                    top: elementOffset.top - $tooltip.outerHeight() - 5,
                    left: elementOffset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                });
                
                $element.data('tooltip-element', $tooltip);
            }).on('mouseleave', '[data-tooltip]', function() {
                var $tooltip = $(this).data('tooltip-element');
                if ($tooltip) {
                    $tooltip.remove();
                }
            });
        },
        
        /**
         * Initialize lazy loading for viral content
         */
        initLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                var lazyContentObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var $element = $(entry.target);
                            EnvSocialViral.loadViralContentSection($element);
                            lazyContentObserver.unobserve(entry.target);
                        }
                    });
                });
                
                $('.env-viral-content-lazy').each(function() {
                    lazyContentObserver.observe(this);
                });
            }
        },
        
        /**
         * Initialize animations
         */
        initAnimations: function() {
            // Animate sharing buttons on hover
            $('.env-share-button').hover(function() {
                $(this).addClass('env-share-hover');
            }, function() {
                $(this).removeClass('env-share-hover');
            });
            
            // Animate stats counters
            $('.env-stat-number').each(function() {
                var $counter = $(this);
                var target = parseInt($counter.text().replace(/,/g, ''));
                
                if (target > 0) {
                    $counter.text('0');
                    EnvSocialViral.animateCounter($counter, target);
                }
            });
        },
        
        /**
         * Animate counter
         */
        animateCounter: function($element, target) {
            var current = 0;
            var increment = target / 50;
            var timer = setInterval(function() {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                $element.text(Math.floor(current).toLocaleString());
            }, 20);
        },
        
        /**
         * Load viral content section
         */
        loadViralContentSection: function($element) {
            var postType = $element.data('post-type') || 'post';
            var limit = $element.data('limit') || 5;
            var period = $element.data('period') || 7;
            
            $.post(envSocialViral.ajax_url, {
                action: 'env_get_viral_content',
                post_type: postType,
                limit: limit,
                period: period,
                nonce: envSocialViral.nonce
            }).done(function(response) {
                if (response.success) {
                    var html = EnvSocialViral.renderViralContent(response.data);
                    $element.html(html);
                    $element.removeClass('env-viral-content-lazy');
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if (typeof envSocialViral !== 'undefined') {
            EnvSocialViral.init();
        }
    });
    
    // Make available globally
    window.EnvSocialViral = EnvSocialViral;
    
})(jQuery);
