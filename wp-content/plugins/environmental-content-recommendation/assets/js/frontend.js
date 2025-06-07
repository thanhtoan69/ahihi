/**
 * Frontend JavaScript for Environmental Content Recommendation Plugin
 * Handles user interactions, AJAX requests, and dynamic content loading
 */

(function($) {
    'use strict';

    // Main ECR Frontend Object
    window.ECRFrontend = {
        
        // Configuration
        config: {
            ajaxUrl: ecr_frontend_ajax.ajax_url,
            nonce: ecr_frontend_ajax.nonce,
            strings: ecr_frontend_ajax.strings || {},
            options: ecr_frontend_ajax.options || {}
        },
        
        // Cached jQuery objects
        $document: null,
        $window: null,
        
        // Initialize the frontend functionality
        init: function() {
            this.$document = $(document);
            this.$window = $(window);
            
            this.bindEvents();
            this.initializeRecommendations();
            this.initializeTracking();
            this.initializeRating();
            this.initializeLazyLoading();
        },
        
        // Bind event handlers
        bindEvents: function() {
            var self = this;
            
            // Recommendation interactions
            this.$document.on('click', '.ecr-recommendation-item a', this.trackClick.bind(this));
            this.$document.on('click', '.ecr-action-btn.like', this.handleLike.bind(this));
            this.$document.on('click', '.ecr-action-btn.share', this.handleShare.bind(this));
            this.$document.on('click', '.ecr-action-btn.dismiss', this.handleDismiss.bind(this));
            
            // Load more functionality
            this.$document.on('click', '.ecr-load-more-btn', this.loadMoreRecommendations.bind(this));
            
            // Rating system
            this.$document.on('click', '.ecr-rating-star', this.handleRating.bind(this));
            
            // Search integration
            this.$document.on('input', '.ecr-search-input', this.debounce(this.handleSearch.bind(this), 300));
            
            // Preference updates
            this.$document.on('change', '.ecr-preference-input', this.updatePreferences.bind(this));
            
            // Scroll tracking
            this.$window.on('scroll', this.debounce(this.trackScroll.bind(this), 250));
            
            // Page visibility for session tracking
            document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
            
            // Before page unload
            this.$window.on('beforeunload', this.trackSessionEnd.bind(this));
        },
        
        // Initialize recommendations on page load
        initializeRecommendations: function() {
            var self = this;
            
            $('.ecr-recommendations-container[data-auto-load="true"]').each(function() {
                var $container = $(this);
                var type = $container.data('type') || 'personalized';
                var count = $container.data('count') || 5;
                var contentId = $container.data('content-id') || 0;
                
                self.loadRecommendations($container, type, count, contentId);
            });
        },
        
        // Initialize user behavior tracking
        initializeTracking: function() {
            this.startSession();
            this.trackPageView();
        },
        
        // Initialize rating system
        initializeRating: function() {
            this.setupRatingStars();
        },
        
        // Initialize lazy loading for recommendations
        initializeLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                this.setupIntersectionObserver();
            }
        },
        
        // Load recommendations via AJAX
        loadRecommendations: function($container, type, count, contentId, exclude) {
            var self = this;
            exclude = exclude || [];
            
            $container.html('<div class="ecr-loading"><div class="ecr-loading-spinner"></div>' + 
                          this.config.strings.loading + '</div>');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ecr_get_recommendations',
                    nonce: this.config.nonce,
                    type: type,
                    count: count,
                    content_id: contentId,
                    exclude: exclude,
                    context: 'frontend_widget'
                },
                success: function(response) {
                    if (response.success && response.data.recommendations) {
                        self.renderRecommendations($container, response.data.recommendations, type);
                        self.trackImpressions(response.data.recommendations);
                    } else {
                        self.showError($container, response.data.message || self.config.strings.error);
                    }
                },
                error: function() {
                    self.showError($container, self.config.strings.error);
                }
            });
        },
        
        // Render recommendations HTML
        renderRecommendations: function($container, recommendations, type) {
            var layout = $container.data('layout') || 'grid';
            var showBadges = $container.data('show-badges') !== false;
            var html = '';
            
            if (recommendations.length === 0) {
                html = '<div class="ecr-no-recommendations">' + 
                       this.config.strings.no_recommendations + '</div>';
            } else {
                var listClass = layout === 'list' ? 'ecr-recommendations-list' : 'ecr-recommendations-grid';
                html = '<ul class="' + listClass + '">';
                
                recommendations.forEach(function(rec, index) {
                    html += this.buildRecommendationHTML(rec, index, showBadges);
                }, this);
                
                html += '</ul>';
                
                // Add load more button if applicable
                if (recommendations.length >= ($container.data('count') || 5)) {
                    html += '<div class="ecr-load-more">' +
                           '<button class="ecr-load-more-btn" data-type="' + type + '" data-page="2">' +
                           this.config.strings.load_more + '</button></div>';
                }
            }
            
            $container.html(html);
            $container.find('.ecr-recommendation-item').addClass('ecr-fade-in');
        },
        
        // Build HTML for individual recommendation
        buildRecommendationHTML: function(rec, index, showBadges) {
            var badgeHTML = '';
            if (showBadges && rec.environmental_score) {
                var scoreClass = this.getEnvironmentalScoreClass(rec.environmental_score);
                badgeHTML = '<div class="ecr-environmental-score ' + scoreClass + '">' + 
                           rec.environmental_score + '</div>';
            }
            
            var thumbnailHTML = '';
            if (rec.thumbnail) {
                thumbnailHTML = '<div class="ecr-recommendation-thumbnail">' +
                               '<img src="' + rec.thumbnail + '" alt="' + rec.title + '" loading="lazy">' +
                               (showBadges ? '<div class="ecr-environmental-badge">Eco-Friendly</div>' : '') +
                               '</div>';
            }
            
            return '<li class="ecr-recommendation-item" data-id="' + rec.id + '" data-position="' + index + '">' +
                   thumbnailHTML +
                   '<div class="ecr-recommendation-content">' +
                   '<h4 class="ecr-recommendation-title">' +
                   '<a href="' + rec.url + '" data-track="click">' + rec.title + '</a></h4>' +
                   '<div class="ecr-recommendation-excerpt">' + rec.excerpt + '</div>' +
                   '<div class="ecr-recommendation-meta">' +
                   '<span class="ecr-meta-date">' + rec.date + '</span>' +
                   '<span class="ecr-meta-author">' + rec.author + '</span>' +
                   badgeHTML +
                   '</div>' +
                   '</div>' +
                   '<div class="ecr-recommendation-actions">' +
                   '<div class="ecr-action-buttons">' +
                   '<button class="ecr-action-btn like" data-id="' + rec.id + '">' +
                   '<span class="dashicons dashicons-heart"></span> ' + this.config.strings.like + '</button>' +
                   '<button class="ecr-action-btn share" data-id="' + rec.id + '">' +
                   '<span class="dashicons dashicons-share"></span> ' + this.config.strings.share + '</button>' +
                   '<button class="ecr-action-btn dismiss" data-id="' + rec.id + '">' +
                   '<span class="dashicons dashicons-dismiss"></span> ' + this.config.strings.dismiss + '</button>' +
                   '</div>' +
                   '<div class="ecr-rating-container" data-id="' + rec.id + '"></div>' +
                   '</div>' +
                   '</li>';
        },
        
        // Get CSS class for environmental score
        getEnvironmentalScoreClass: function(score) {
            if (score >= 8) return 'high';
            if (score >= 5) return 'medium';
            return 'low';
        },
        
        // Track recommendation clicks
        trackClick: function(e) {
            var $link = $(e.currentTarget);
            var $item = $link.closest('.ecr-recommendation-item');
            var contentId = $item.data('id');
            var position = $item.data('position');
            
            this.trackInteraction('click', contentId, 1.0, {
                position: position,
                url: $link.attr('href')
            });
        },
        
        // Track user interactions
        trackInteraction: function(action, contentId, value, metadata) {
            if (!contentId) return;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ecr_track_interaction',
                    nonce: this.config.nonce,
                    action_type: action,
                    content_id: contentId,
                    value: value || 1.0,
                    metadata: metadata || {},
                    context: 'frontend'
                }
            });
        },
        
        // Track impressions when recommendations are shown
        trackImpressions: function(recommendations) {
            recommendations.forEach(function(rec, index) {
                this.trackInteraction('impression', rec.id, 1.0, {
                    position: index,
                    recommendation_score: rec.recommendation_score
                });
            }, this);
        },
        
        // Handle like button clicks
        handleLike: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var contentId = $btn.data('id');
            
            $btn.toggleClass('liked');
            var isLiked = $btn.hasClass('liked');
            
            this.trackInteraction(isLiked ? 'like' : 'unlike', contentId, isLiked ? 1.0 : -1.0);
            
            // Visual feedback
            if (isLiked) {
                $btn.find('.dashicons').addClass('dashicons-heart-filled');
                this.showNotification(this.config.strings.liked, 'success');
            } else {
                $btn.find('.dashicons').removeClass('dashicons-heart-filled');
            }
        },
        
        // Handle share button clicks
        handleShare: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var contentId = $btn.data('id');
            var $item = $btn.closest('.ecr-recommendation-item');
            var title = $item.find('.ecr-recommendation-title a').text();
            var url = $item.find('.ecr-recommendation-title a').attr('href');
            
            // Use Web Share API if available
            if (navigator.share) {
                navigator.share({
                    title: title,
                    url: url
                }).then(function() {
                    this.trackInteraction('share', contentId, 1.0, {platform: 'native'});
                }.bind(this));
            } else {
                // Fallback: copy to clipboard
                this.copyToClipboard(url);
                this.showNotification(this.config.strings.copied, 'success');
                this.trackInteraction('share', contentId, 1.0, {platform: 'clipboard'});
            }
        },
        
        // Handle dismiss button clicks
        handleDismiss: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var contentId = $btn.data('id');
            var $item = $btn.closest('.ecr-recommendation-item');
            
            // Ask for dismissal reason
            var reason = prompt(this.config.strings.dismiss_reason);
            if (!reason) return;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ecr_dismiss_recommendation',
                    nonce: this.config.nonce,
                    content_id: contentId,
                    reason: reason
                },
                success: function(response) {
                    if (response.success) {
                        $item.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                }
            });
            
            this.trackInteraction('dismiss', contentId, -1.0, {reason: reason});
        },
        
        // Load more recommendations
        loadMoreRecommendations: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var type = $btn.data('type');
            var page = $btn.data('page') || 2;
            var $container = $btn.closest('.ecr-recommendations-container');
            var $list = $container.find('.ecr-recommendations-grid, .ecr-recommendations-list');
            
            $btn.prop('disabled', true).text(this.config.strings.loading);
            
            var excludeIds = [];
            $list.find('.ecr-recommendation-item').each(function() {
                excludeIds.push($(this).data('id'));
            });
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ecr_load_more_recommendations',
                    nonce: this.config.nonce,
                    type: type,
                    page: page,
                    per_page: 5,
                    exclude: excludeIds
                },
                success: function(response) {
                    if (response.success && response.data.recommendations.length > 0) {
                        response.data.recommendations.forEach(function(rec, index) {
                            var html = this.buildRecommendationHTML(rec, excludeIds.length + index, true);
                            $list.append(html);
                        }, this);
                        
                        $btn.data('page', page + 1);
                        
                        if (!response.data.has_more) {
                            $btn.hide();
                        } else {
                            $btn.prop('disabled', false).text(this.config.strings.load_more);
                        }
                        
                        this.trackImpressions(response.data.recommendations);
                    } else {
                        $btn.hide();
                    }
                }.bind(this),
                error: function() {
                    $btn.prop('disabled', false).text(this.config.strings.load_more);
                }.bind(this)
            });
        },
        
        // Handle rating interactions
        handleRating: function(e) {
            e.preventDefault();
            var $star = $(e.currentTarget);
            var $container = $star.closest('.ecr-rating-container');
            var contentId = $container.data('id');
            var rating = $star.data('rating');
            
            // Update star display
            $container.find('.ecr-rating-star').removeClass('active');
            $star.prevAll('.ecr-rating-star').addBack().addClass('active');
            
            // Submit rating
            this.submitRating(contentId, rating);
        },
        
        // Submit rating via AJAX
        submitRating: function(contentId, rating) {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ecr_rate_recommendation',
                    nonce: this.config.nonce,
                    content_id: contentId,
                    rating: rating
                },
                success: function(response) {
                    if (response.success) {
                        this.showNotification(this.config.strings.rating_saved, 'success');
                    }
                }.bind(this)
            });
        },
        
        // Setup rating stars
        setupRatingStars: function() {
            $('.ecr-rating-container').each(function() {
                var $container = $(this);
                if ($container.find('.ecr-rating-star').length === 0) {
                    var starsHTML = '<div class="ecr-rating-stars">';
                    for (var i = 1; i <= 5; i++) {
                        starsHTML += '<span class="ecr-rating-star" data-rating="' + i + '">★</span>';
                    }
                    starsHTML += '</div>';
                    $container.html(starsHTML);
                }
            });
        },
        
        // Track page view
        trackPageView: function() {
            var currentPost = this.getCurrentPostId();
            if (currentPost > 0) {
                this.trackInteraction('view', currentPost, 1.0, {
                    url: window.location.href,
                    title: document.title
                });
            }
        },
        
        // Track scroll depth
        trackScroll: function() {
            var scrollTop = this.$window.scrollTop();
            var docHeight = this.$document.height();
            var winHeight = this.$window.height();
            var scrollPercent = Math.round((scrollTop / (docHeight - winHeight)) * 100);
            
            if (scrollPercent > 0 && scrollPercent % 25 === 0) {
                var currentPost = this.getCurrentPostId();
                if (currentPost > 0) {
                    this.trackInteraction('scroll', currentPost, scrollPercent / 100, {
                        scroll_depth: scrollPercent
                    });
                }
            }
        },
        
        // Start user session
        startSession: function() {
            this.sessionStartTime = Date.now();
            this.trackInteraction('session_start', 0, 1.0, {
                timestamp: this.sessionStartTime,
                user_agent: navigator.userAgent,
                screen_resolution: screen.width + 'x' + screen.height
            });
        },
        
        // Track session end
        trackSessionEnd: function() {
            if (this.sessionStartTime) {
                var sessionDuration = (Date.now() - this.sessionStartTime) / 1000;
                this.trackInteraction('session_end', 0, sessionDuration, {
                    duration: sessionDuration
                });
            }
        },
        
        // Handle page visibility changes
        handleVisibilityChange: function() {
            if (document.hidden) {
                this.trackInteraction('page_hidden', this.getCurrentPostId(), 1.0);
            } else {
                this.trackInteraction('page_visible', this.getCurrentPostId(), 1.0);
            }
        },
        
        // Search functionality
        handleSearch: function(e) {
            var query = $(e.target).val().trim();
            if (query.length < 3) return;
            
            var $results = $('.ecr-search-results');
            if ($results.length === 0) {
                $results = $('<div class="ecr-search-results"></div>').insertAfter(e.target);
            }
            
            $results.html('<div class="ecr-loading">' + this.config.strings.searching + '</div>');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ecr_search_content',
                    nonce: this.config.nonce,
                    query: query,
                    count: 5
                },
                success: function(response) {
                    if (response.success) {
                        this.renderSearchResults($results, response.data);
                    }
                }.bind(this)
            });
        },
        
        // Render search results
        renderSearchResults: function($container, data) {
            var html = '';
            if (data.results && data.results.length > 0) {
                html = '<ul class="ecr-search-results-list">';
                data.results.forEach(function(result) {
                    html += '<li><a href="' + result.url + '">' + result.title + '</a></li>';
                });
                html += '</ul>';
            } else {
                html = '<p>' + this.config.strings.no_results + '</p>';
            }
            $container.html(html);
        },
        
        // Update user preferences
        updatePreferences: function(e) {
            var $input = $(e.target);
            var preference = $input.data('preference');
            var value = $input.val();
            
            if (!preference) return;
            
            var preferences = {};
            preferences[preference] = value;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ecr_update_preferences',
                    nonce: this.config.nonce,
                    preferences: preferences
                }
            });
        },
        
        // Setup intersection observer for lazy loading
        setupIntersectionObserver: function() {
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var $container = $(entry.target);
                        if ($container.data('lazy-loaded')) return;
                        
                        $container.data('lazy-loaded', true);
                        this.loadRecommendations(
                            $container,
                            $container.data('type'),
                            $container.data('count'),
                            $container.data('content-id')
                        );
                        
                        observer.unobserve(entry.target);
                    }
                }.bind(this));
            }.bind(this), {
                threshold: 0.1
            });
            
            $('.ecr-recommendations-container[data-lazy="true"]').each(function() {
                observer.observe(this);
            });
        },
        
        // Utility functions
        getCurrentPostId: function() {
            if (typeof ecrFrontendData !== 'undefined' && ecrFrontendData.post_id) {
                return parseInt(ecrFrontendData.post_id);
            }
            
            var bodyClasses = document.body.className.split(' ');
            for (var i = 0; i < bodyClasses.length; i++) {
                if (bodyClasses[i].indexOf('postid-') === 0) {
                    return parseInt(bodyClasses[i].replace('postid-', ''));
                }
            }
            return 0;
        },
        
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text);
            } else {
                var textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
            }
        },
        
        showNotification: function(message, type) {
            var $notification = $('<div class="ecr-notification ecr-notification-' + type + '">' + message + '</div>');
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.fadeIn();
            }, 100);
            
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },
        
        showError: function($container, message) {
            $container.html('<div class="ecr-error">' + message + '</div>');
        },
        
        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        ECRFrontend.init();
    });
    
})(jQuery);
