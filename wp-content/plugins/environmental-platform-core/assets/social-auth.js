/**
 * Environmental Platform - Social Authentication JavaScript
 * Handles social media login integration and UI interactions
 * 
 * @package Environmental_Platform_Core
 * @version 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Social Authentication Object
     */
    window.EP_SocialAuth = {
        
        // Configuration
        config: {
            ajaxUrl: ep_social_ajax.ajax_url,
            nonce: ep_social_ajax.nonce,
            providers: ep_social_ajax.providers || {},
            messages: ep_social_ajax.messages || {}
        },

        // OAuth popup reference
        oauthPopup: null,

        /**
         * Initialize social authentication
         */
        init: function() {
            this.bindEvents();
            this.initProviderButtons();
            this.setupPopupHandling();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Social login buttons
            $(document).on('click', '.ep-social-login-btn', this.handleSocialLogin.bind(this));
            
            // Social connection management
            $(document).on('click', '.ep-social-connect-btn', this.handleSocialConnect.bind(this));
            $(document).on('click', '.ep-social-disconnect-btn', this.handleSocialDisconnect.bind(this));
            
            // Provider settings
            $(document).on('change', '.ep-provider-enabled', this.handleProviderToggle.bind(this));
            $(document).on('click', '.ep-test-provider-btn', this.testProviderConfig.bind(this));
            
            // Account linking
            $(document).on('click', '.ep-link-account-btn', this.handleAccountLinking.bind(this));
            
            // Social sharing
            $(document).on('click', '.ep-share-btn', this.handleSocialShare.bind(this));
        },

        /**
         * Initialize provider buttons
         */
        initProviderButtons: function() {
            $('.ep-social-login-btn').each(function() {
                const button = $(this);
                const provider = button.data('provider');
                
                if (this.config.providers[provider]) {
                    button.removeClass('ep-disabled');
                } else {
                    button.addClass('ep-disabled').prop('disabled', true);
                }
            }.bind(this));
        },

        /**
         * Setup popup handling for OAuth
         */
        setupPopupHandling: function() {
            // Listen for messages from OAuth popup
            window.addEventListener('message', this.handleOAuthMessage.bind(this), false);
            
            // Check for OAuth callback parameters
            this.checkOAuthCallback();
        },

        /**
         * Handle social login button click
         */
        handleSocialLogin: function(e) {
            e.preventDefault();
            
            const button = $(e.currentTarget);
            const provider = button.data('provider');
            const action = button.data('action') || 'login';
            
            if (!provider || button.hasClass('ep-disabled')) {
                return false;
            }
            
            this.startSocialAuth(provider, action, button);
        },

        /**
         * Handle social account connection
         */
        handleSocialConnect: function(e) {
            e.preventDefault();
            
            const button = $(e.currentTarget);
            const provider = button.data('provider');
            
            if (!provider) return;
            
            this.startSocialAuth(provider, 'connect', button);
        },

        /**
         * Handle social account disconnection
         */
        handleSocialDisconnect: function(e) {
            e.preventDefault();
            
            const button = $(e.currentTarget);
            const provider = button.data('provider');
            
            if (!provider) return;
            
            if (!confirm(this.config.messages.confirm_disconnect || 'Are you sure you want to disconnect this account?')) {
                return;
            }
            
            this.showLoading(button);
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ep_social_disconnect',
                    provider: provider,
                    nonce: this.config.nonce
                },
                success: this.handleDisconnectSuccess.bind(this, button),
                error: this.handleDisconnectError.bind(this, button),
                complete: () => this.hideLoading(button)
            });
        },

        /**
         * Start social authentication process
         */
        startSocialAuth: function(provider, action, button) {
            this.showLoading(button);
            
            // Get OAuth URL
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ep_get_oauth_url',
                    provider: provider,
                    auth_action: action,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.openOAuthPopup(response.data.oauth_url, provider, action, button);
                    } else {
                        this.showError(response.data.message || 'Failed to get OAuth URL');
                        this.hideLoading(button);
                    }
                },
                error: () => {
                    this.showError('Connection error. Please try again.');
                    this.hideLoading(button);
                }
            });
        },

        /**
         * Open OAuth popup window
         */
        openOAuthPopup: function(url, provider, action, button) {
            const width = 600;
            const height = 700;
            const left = (screen.width / 2) - (width / 2);
            const top = (screen.height / 2) - (height / 2);
            
            const features = [
                `width=${width}`,
                `height=${height}`,
                `left=${left}`,
                `top=${top}`,
                'scrollbars=yes',
                'resizable=yes',
                'status=no',
                'menubar=no',
                'toolbar=no'
            ].join(',');
            
            this.oauthPopup = window.open(url, `oauth_${provider}`, features);
            
            if (!this.oauthPopup) {
                this.showError('Popup blocked. Please allow popups for this site.');
                this.hideLoading(button);
                return;
            }
            
            // Monitor popup
            this.monitorPopup(provider, action, button);
        },

        /**
         * Monitor OAuth popup
         */
        monitorPopup: function(provider, action, button) {
            const checkInterval = setInterval(() => {
                if (!this.oauthPopup || this.oauthPopup.closed) {
                    clearInterval(checkInterval);
                    this.hideLoading(button);
                    
                    // Check if authentication was successful
                    this.checkAuthResult(provider, action);
                }
            }, 1000);
            
            // Timeout after 5 minutes
            setTimeout(() => {
                if (this.oauthPopup && !this.oauthPopup.closed) {
                    this.oauthPopup.close();
                    clearInterval(checkInterval);
                    this.hideLoading(button);
                    this.showError('Authentication timed out. Please try again.');
                }
            }, 300000);
        },

        /**
         * Handle OAuth message from popup
         */
        handleOAuthMessage: function(event) {
            // Verify origin for security
            if (event.origin !== window.location.origin) {
                return;
            }
            
            if (event.data.type === 'oauth_success') {
                this.handleOAuthSuccess(event.data);
            } else if (event.data.type === 'oauth_error') {
                this.handleOAuthError(event.data);
            }
        },

        /**
         * Check OAuth callback parameters
         */
        checkOAuthCallback: function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.get('oauth_callback')) {
                const provider = urlParams.get('provider');
                const success = urlParams.get('success') === 'true';
                const message = urlParams.get('message');
                
                if (success) {
                    this.showSuccess(message || 'Authentication successful!');
                    
                    // Redirect to appropriate page
                    const redirectUrl = urlParams.get('redirect_url');
                    if (redirectUrl) {
                        setTimeout(() => {
                            window.location.href = decodeURIComponent(redirectUrl);
                        }, 2000);
                    }
                } else {
                    this.showError(message || 'Authentication failed');
                }
                
                // Clean up URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        },

        /**
         * Check authentication result
         */
        checkAuthResult: function(provider, action) {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ep_check_auth_result',
                    provider: provider,
                    auth_action: action,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.handleAuthSuccess(response.data, action);
                    } else if (response.data && response.data.pending) {
                        // Authentication still in progress, check again
                        setTimeout(() => this.checkAuthResult(provider, action), 1000);
                    }
                }
            });
        },

        /**
         * Handle OAuth success
         */
        handleOAuthSuccess: function(data) {
            if (this.oauthPopup) {
                this.oauthPopup.close();
            }
            
            this.showSuccess(data.message || 'Authentication successful!');
            
            // Handle different auth actions
            if (data.action === 'login') {
                // Redirect to dashboard or specified URL
                setTimeout(() => {
                    window.location.href = data.redirect_url || '/dashboard';
                }, 2000);
            } else if (data.action === 'register') {
                // Redirect to welcome page or dashboard
                setTimeout(() => {
                    window.location.href = data.redirect_url || '/welcome';
                }, 2000);
            } else if (data.action === 'connect') {
                // Refresh social connections display
                this.refreshSocialConnections();
            }
        },

        /**
         * Handle OAuth error
         */
        handleOAuthError: function(data) {
            if (this.oauthPopup) {
                this.oauthPopup.close();
            }
            
            this.showError(data.message || 'Authentication failed');
        },

        /**
         * Handle authentication success
         */
        handleAuthSuccess: function(data, action) {
            this.showSuccess(data.message);
            
            if (action === 'login' || action === 'register') {
                setTimeout(() => {
                    window.location.href = data.redirect_url || '/dashboard';
                }, 2000);
            } else if (action === 'connect') {
                this.refreshSocialConnections();
            }
        },

        /**
         * Handle disconnect success
         */
        handleDisconnectSuccess: function(button, response) {
            if (response.success) {
                this.showSuccess(response.data.message);
                
                // Update UI
                const provider = button.data('provider');
                const connectionCard = button.closest('.ep-social-connection');
                
                if (connectionCard.length) {
                    connectionCard.fadeOut(() => {
                        connectionCard.remove();
                        this.updateConnectionsDisplay();
                    });
                }
            } else {
                this.showError(response.data.message);
            }
        },

        /**
         * Handle disconnect error
         */
        handleDisconnectError: function(button, xhr) {
            this.showError('Failed to disconnect account. Please try again.');
        },

        /**
         * Handle provider toggle
         */
        handleProviderToggle: function(e) {
            const checkbox = $(e.target);
            const provider = checkbox.data('provider');
            const enabled = checkbox.is(':checked');
            
            // Update provider status
            this.updateProviderStatus(provider, enabled);
        },

        /**
         * Test provider configuration
         */
        testProviderConfig: function(e) {
            e.preventDefault();
            
            const button = $(e.target);
            const provider = button.data('provider');
            
            this.showLoading(button);
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ep_test_provider_config',
                    provider: provider,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess(`${provider} configuration is valid`);
                    } else {
                        this.showError(response.data.message || `${provider} configuration failed`);
                    }
                },
                error: () => {
                    this.showError('Failed to test configuration');
                },
                complete: () => this.hideLoading(button)
            });
        },

        /**
         * Handle account linking
         */
        handleAccountLinking: function(e) {
            e.preventDefault();
            
            const button = $(e.target);
            const provider = button.data('provider');
            const targetUserId = button.data('target-user-id');
            
            this.showLoading(button);
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ep_link_social_account',
                    provider: provider,
                    target_user_id: targetUserId,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess(response.data.message);
                        this.refreshSocialConnections();
                    } else {
                        this.showError(response.data.message);
                    }
                },
                error: () => {
                    this.showError('Failed to link account');
                },
                complete: () => this.hideLoading(button)
            });
        },

        /**
         * Handle social sharing
         */
        handleSocialShare: function(e) {
            e.preventDefault();
            
            const button = $(e.target);
            const platform = button.data('share');
            const shareData = this.getShareData();
            
            this.shareToSocial(platform, shareData);
        },

        /**
         * Get share data
         */
        getShareData: function() {
            return {
                url: window.location.href,
                title: document.title,
                description: $('meta[name="description"]').attr('content') || '',
                image: $('meta[property="og:image"]').attr('content') || ''
            };
        },

        /**
         * Share to social platform
         */
        shareToSocial: function(platform, data) {
            let shareUrl = '';
            
            switch (platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(data.url)}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(data.url)}&text=${encodeURIComponent(data.title)}`;
                    break;
                case 'linkedin':
                    shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(data.url)}`;
                    break;
                default:
                    return;
            }
            
            // Open share popup
            const width = 600;
            const height = 400;
            const left = (screen.width / 2) - (width / 2);
            const top = (screen.height / 2) - (height / 2);
            
            window.open(
                shareUrl,
                `share_${platform}`,
                `width=${width},height=${height},left=${left},top=${top},scrollbars=yes,resizable=yes`
            );
        },

        /**
         * Refresh social connections display
         */
        refreshSocialConnections: function() {
            const container = $('.ep-social-connections');
            
            if (container.length) {
                container.load(window.location.href + ' .ep-social-connections > *', () => {
                    this.initProviderButtons();
                });
            }
        },

        /**
         * Update connections display
         */
        updateConnectionsDisplay: function() {
            const connectionCount = $('.ep-social-connection').length;
            
            if (connectionCount === 0) {
                const noConnectionsMsg = $('<div class="ep-no-connections"><p>No social accounts connected yet.</p></div>');
                $('.ep-social-connections').append(noConnectionsMsg);
            }
        },

        /**
         * Update provider status
         */
        updateProviderStatus: function(provider, enabled) {
            const buttons = $(`.ep-social-login-btn[data-provider="${provider}"]`);
            
            if (enabled) {
                buttons.removeClass('ep-disabled').prop('disabled', false);
            } else {
                buttons.addClass('ep-disabled').prop('disabled', true);
            }
        },

        /**
         * Utility methods
         */
        showLoading: function(element) {
            element.addClass('ep-loading').prop('disabled', true);
            const icon = element.find('i');
            if (icon.length) {
                icon.data('original-class', icon.attr('class'));
                icon.attr('class', 'ep-icon-spinner ep-spin');
            }
        },

        hideLoading: function(element) {
            element.removeClass('ep-loading').prop('disabled', false);
            const icon = element.find('i');
            if (icon.length && icon.data('original-class')) {
                icon.attr('class', icon.data('original-class'));
            }
        },

        showSuccess: function(message) {
            this.showMessage('success', message);
        },

        showError: function(message) {
            this.showMessage('error', message);
        },

        showMessage: function(type, message) {
            // Try to use existing message container
            let container = $('.ep-messages').first();
            
            if (!container.length) {
                // Create message container if it doesn't exist
                container = $('<div class="ep-messages"></div>');
                $('body').prepend(container);
            }
            
            const messageEl = $(`
                <div class="ep-message ep-${type}">
                    <i class="ep-icon-${type === 'success' ? 'check' : 'warning'}"></i>
                    <span class="ep-message-text">${message}</span>
                    <button class="ep-message-close">&times;</button>
                </div>
            `);
            
            container.append(messageEl);
            messageEl.slideDown();
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                messageEl.slideUp(() => messageEl.remove());
            }, 5000);
            
            // Handle close button
            messageEl.find('.ep-message-close').on('click', () => {
                messageEl.slideUp(() => messageEl.remove());
            });
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        EP_SocialAuth.init();
    });

})(jQuery);
