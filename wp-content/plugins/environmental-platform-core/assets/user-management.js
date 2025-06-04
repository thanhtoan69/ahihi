/**
 * Environmental Platform - User Management JavaScript
 * Handles user registration, login, profile management, and social authentication
 * 
 * @package Environmental_Platform_Core
 * @version 1.0.0
 */

(function($) {
    'use strict';

    /**
     * User Management Object
     */
    window.EP_UserManagement = {
        
        // Configuration
        config: {
            ajaxUrl: ep_user_ajax.ajax_url,
            nonce: ep_user_ajax.nonce,
            redirectUrl: ep_user_ajax.redirect_url || '/',
            messages: ep_user_ajax.messages || {}
        },

        /**
         * Initialize user management functionality
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initProgressBars();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Form submissions
            $(document).on('submit', '#ep-registration-form', this.handleRegistration.bind(this));
            $(document).on('submit', '#ep-login-form', this.handleLogin.bind(this));
            $(document).on('submit', '#ep-profile-settings-form', this.handleProfileUpdate.bind(this));
            
            // Social authentication
            $(document).on('click', '.ep-social-login-btn', this.handleSocialLogin.bind(this));
            $(document).on('click', '.ep-social-connect-btn', this.handleSocialConnect.bind(this));
            $(document).on('click', '.ep-social-disconnect-btn', this.handleSocialDisconnect.bind(this));
            
            // Profile functionality
            $(document).on('click', '.ep-nav-link', this.handleTabNavigation.bind(this));
            $(document).on('click', '.ep-change-avatar-btn', this.handleAvatarChange.bind(this));
            $(document).on('change', '#ep-activity-type-filter', this.filterActivities.bind(this));
            
            // Password validation
            $(document).on('input', '#ep_password, #ep_confirm_password', this.validatePasswords.bind(this));
            
            // Form validation
            $(document).on('blur', 'input[type="email"]', this.validateEmail.bind(this));
            $(document).on('blur', 'input[name="username"]', this.validateUsername.bind(this));
        },

        /**
         * Initialize registration form
         */
        initRegistrationForm: function() {
            this.setupFormValidation('#ep-registration-form');
            this.initPasswordStrengthMeter();
            this.initInterestSelection();
        },

        /**
         * Initialize login form
         */
        initLoginForm: function() {
            this.setupFormValidation('#ep-login-form');
            this.initRememberMe();
        },

        /**
         * Initialize profile functionality
         */
        initProfile: function() {
            this.loadUserActivities();
            this.initProfileTabs();
            this.initSocialSharing();
            this.initProgressAnimations();
        },

        /**
         * Initialize dashboard functionality
         */
        initDashboard: function() {
            this.loadDashboardData();
            this.initQuickActions();
            this.initDailyChallenges();
            this.animateStats();
        },

        /**
         * Initialize leaderboard functionality
         */
        initLeaderboard: function() {
            this.loadLeaderboardData();
            this.initFilters();
            this.highlightCurrentUser();
        },

        /**
         * Handle user registration
         */
        handleRegistration: function(e) {
            e.preventDefault();
            
            const form = $(e.target);
            const submitBtn = form.find('#ep-register-btn');
            
            if (!this.validateRegistrationForm(form)) {
                return false;
            }
            
            this.showLoading(submitBtn);
            
            const formData = {
                action: 'ep_user_register',
                nonce: form.find('#ep_registration_nonce').val(),
                first_name: form.find('#ep_first_name').val(),
                last_name: form.find('#ep_last_name').val(),
                username: form.find('#ep_username').val(),
                email: form.find('#ep_email').val(),
                password: form.find('#ep_password').val(),
                user_type: form.find('#ep_user_type').val(),
                location: form.find('#ep_location').val(),
                interests: form.find('input[name="interests[]"]:checked').map(function() {
                    return $(this).val();
                }).get(),
                accept_terms: form.find('#ep_terms').is(':checked') ? 1 : 0,
                subscribe_newsletter: form.find('#ep_newsletter').is(':checked') ? 1 : 0
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                success: this.handleRegistrationSuccess.bind(this),
                error: this.handleRegistrationError.bind(this),
                complete: function() {
                    this.hideLoading(submitBtn);
                }.bind(this)
            });
        },

        /**
         * Handle user login
         */
        handleLogin: function(e) {
            e.preventDefault();
            
            const form = $(e.target);
            const submitBtn = form.find('#ep-login-btn');
            
            this.showLoading(submitBtn);
            
            const formData = {
                action: 'ep_user_login',
                nonce: form.find('#ep_login_nonce').val(),
                login: form.find('#ep_login_email').val(),
                password: form.find('#ep_login_password').val(),
                remember_me: form.find('#ep_remember_me').is(':checked') ? 1 : 0
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                success: this.handleLoginSuccess.bind(this),
                error: this.handleLoginError.bind(this),
                complete: function() {
                    this.hideLoading(submitBtn);
                }.bind(this)
            });
        },

        /**
         * Handle profile update
         */
        handleProfileUpdate: function(e) {
            e.preventDefault();
            
            const form = $(e.target);
            const submitBtn = form.find('button[type="submit"]');
            
            this.showLoading(submitBtn);
            
            const formData = {
                action: 'ep_update_profile',
                nonce: form.find('#ep_profile_settings_nonce').val(),
                first_name: form.find('#ep_settings_first_name').val(),
                last_name: form.find('#ep_settings_last_name').val(),
                bio: form.find('#ep_settings_bio').val(),
                location: form.find('#ep_settings_location').val(),
                profile_public: form.find('input[name="profile_public"]').is(':checked') ? 1 : 0,
                show_achievements: form.find('input[name="show_achievements"]').is(':checked') ? 1 : 0,
                show_leaderboard: form.find('input[name="show_leaderboard"]').is(':checked') ? 1 : 0,
                email_notifications: form.find('input[name="email_notifications"]').is(':checked') ? 1 : 0,
                achievement_notifications: form.find('input[name="achievement_notifications"]').is(':checked') ? 1 : 0,
                newsletter_subscription: form.find('input[name="newsletter_subscription"]').is(':checked') ? 1 : 0
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                success: this.handleProfileUpdateSuccess.bind(this),
                error: this.handleProfileUpdateError.bind(this),
                complete: function() {
                    this.hideLoading(submitBtn);
                }.bind(this)
            });
        },

        /**
         * Handle social login
         */
        handleSocialLogin: function(e) {
            e.preventDefault();
            
            const button = $(e.target);
            const provider = button.data('provider');
            const action = button.data('action') || 'login';
            
            if (!provider) return;
            
            // Show loading state
            this.showLoading(button);
            
            // Open social login popup
            const width = 600;
            const height = 700;
            const left = (screen.width / 2) - (width / 2);
            const top = (screen.height / 2) - (height / 2);
            
            const popup = window.open(
                this.config.ajaxUrl + '?action=ep_social_auth&provider=' + provider + '&auth_action=' + action + '&nonce=' + this.config.nonce,
                'social_login',
                `width=${width},height=${height},left=${left},top=${top},scrollbars=yes,resizable=yes`
            );
            
            // Listen for popup close/completion
            const checkClosed = setInterval(() => {
                if (popup.closed) {
                    clearInterval(checkClosed);
                    this.hideLoading(button);
                    this.handleSocialAuthComplete();
                }
            }, 1000);
        },

        /**
         * Handle social account connection
         */
        handleSocialConnect: function(e) {
            e.preventDefault();
            
            const button = $(e.target);
            const provider = button.data('provider');
            
            this.showLoading(button);
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ep_social_connect',
                    provider: provider,
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.showMessage('success', response.data.message);
                        this.refreshSocialConnections();
                    } else {
                        this.showMessage('error', response.data.message);
                    }
                }.bind(this),
                error: function() {
                    this.showMessage('error', this.config.messages.connection_error);
                }.bind(this),
                complete: function() {
                    this.hideLoading(button);
                }.bind(this)
            });
        },

        /**
         * Handle social account disconnection
         */
        handleSocialDisconnect: function(e) {
            e.preventDefault();
            
            const button = $(e.target);
            const provider = button.data('provider');
            
            if (!confirm(this.config.messages.confirm_disconnect)) {
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
                success: function(response) {
                    if (response.success) {
                        this.showMessage('success', response.data.message);
                        this.refreshSocialConnections();
                    } else {
                        this.showMessage('error', response.data.message);
                    }
                }.bind(this),
                error: function() {
                    this.showMessage('error', this.config.messages.connection_error);
                }.bind(this),
                complete: function() {
                    this.hideLoading(button);
                }.bind(this)
            });
        },

        /**
         * Handle tab navigation
         */
        handleTabNavigation: function(e) {
            e.preventDefault();
            
            const link = $(e.target);
            const target = link.attr('href');
            
            // Update active states
            $('.ep-nav-item').removeClass('active');
            link.closest('.ep-nav-item').addClass('active');
            
            $('.ep-profile-panel').removeClass('active');
            $(target).addClass('active');
            
            // Load panel content if needed
            this.loadPanelContent(target);
            
            // Update URL hash
            if (history.pushState) {
                history.pushState(null, null, target);
            }
        },

        /**
         * Handle avatar change
         */
        handleAvatarChange: function(e) {
            e.preventDefault();
            
            // Create file input
            const fileInput = $('<input type="file" accept="image/*" style="display:none;">');
            $('body').append(fileInput);
            
            fileInput.on('change', function() {
                const file = this.files[0];
                if (file) {
                    this.uploadAvatar(file);
                }
                fileInput.remove();
            }.bind(this));
            
            fileInput.click();
        },

        /**
         * Filter activities
         */
        filterActivities: function(e) {
            const filter = $(e.target).val();
            this.loadUserActivities(filter);
        },

        /**
         * Validate registration form
         */
        validateRegistrationForm: function(form) {
            let isValid = true;
            
            // Clear previous errors
            form.find('.ep-field-error').remove();
            form.find('.ep-error').removeClass('ep-error');
            
            // Required fields
            const requiredFields = ['first_name', 'last_name', 'username', 'email', 'password'];
            requiredFields.forEach(field => {
                const input = form.find(`#ep_${field}`);
                if (!input.val().trim()) {
                    this.showFieldError(input, this.config.messages.field_required);
                    isValid = false;
                }
            });
            
            // Email validation
            const email = form.find('#ep_email').val();
            if (email && !this.isValidEmail(email)) {
                this.showFieldError(form.find('#ep_email'), this.config.messages.invalid_email);
                isValid = false;
            }
            
            // Password validation
            const password = form.find('#ep_password').val();
            const confirmPassword = form.find('#ep_confirm_password').val();
            
            if (password.length < 8) {
                this.showFieldError(form.find('#ep_password'), this.config.messages.password_too_short);
                isValid = false;
            }
            
            if (password !== confirmPassword) {
                this.showFieldError(form.find('#ep_confirm_password'), this.config.messages.passwords_dont_match);
                isValid = false;
            }
            
            // Terms acceptance
            if (!form.find('#ep_terms').is(':checked')) {
                this.showFieldError(form.find('#ep_terms'), this.config.messages.accept_terms);
                isValid = false;
            }
            
            return isValid;
        },

        /**
         * Validate passwords match
         */
        validatePasswords: function() {
            const password = $('#ep_password').val();
            const confirmPassword = $('#ep_confirm_password').val();
            const confirmField = $('#ep_confirm_password');
            
            confirmField.removeClass('ep-error ep-success');
            confirmField.siblings('.ep-field-error').remove();
            
            if (confirmPassword && password !== confirmPassword) {
                confirmField.addClass('ep-error');
                this.showFieldError(confirmField, this.config.messages.passwords_dont_match);
            } else if (confirmPassword && password === confirmPassword) {
                confirmField.addClass('ep-success');
            }
        },

        /**
         * Validate email format
         */
        validateEmail: function(e) {
            const input = $(e.target);
            const email = input.val();
            
            input.removeClass('ep-error ep-success');
            input.siblings('.ep-field-error').remove();
            
            if (email && !this.isValidEmail(email)) {
                input.addClass('ep-error');
                this.showFieldError(input, this.config.messages.invalid_email);
            } else if (email) {
                input.addClass('ep-success');
            }
        },

        /**
         * Validate username availability
         */
        validateUsername: function(e) {
            const input = $(e.target);
            const username = input.val();
            
            if (username.length < 3) return;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ep_check_username',
                    username: username,
                    nonce: this.config.nonce
                },
                success: function(response) {
                    input.removeClass('ep-error ep-success');
                    input.siblings('.ep-field-error').remove();
                    
                    if (response.success) {
                        if (response.data.available) {
                            input.addClass('ep-success');
                        } else {
                            input.addClass('ep-error');
                            this.showFieldError(input, this.config.messages.username_taken);
                        }
                    }
                }.bind(this)
            });
        },

        /**
         * Load user activities
         */
        loadUserActivities: function(filter = '') {
            const container = $('#ep-activities-list');
            
            container.html('<div class="ep-loading">' + this.config.messages.loading + '</div>');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ep_get_user_activities',
                    filter: filter,
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        container.html(response.data.html);
                        this.initActivityPagination();
                    } else {
                        container.html('<div class="ep-error">' + response.data.message + '</div>');
                    }
                }.bind(this),
                error: function() {
                    container.html('<div class="ep-error">' + this.config.messages.loading_error + '</div>');
                }.bind(this)
            });
        },

        /**
         * Load dashboard data
         */
        loadDashboardData: function() {
            // Load quick stats
            this.refreshQuickStats();
            
            // Load recent activities
            this.loadRecentActivities();
            
            // Load daily challenges
            this.loadDailyChallenges();
        },

        /**
         * Refresh dashboard data
         */
        refreshDashboardData: function() {
            this.loadDashboardData();
        },

        /**
         * Load leaderboard data
         */
        loadLeaderboardData: function() {
            // Implementation for loading leaderboard data
        },

        /**
         * Load more leaderboard entries
         */
        loadMoreLeaderboard: function(offset) {
            const button = $('#ep-load-more-leaderboard');
            const period = $('#ep-period-filter').val();
            const category = $('#ep-category-filter').val();
            
            this.showLoading(button);
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ep_load_more_leaderboard',
                    offset: offset,
                    period: period,
                    category: category,
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.ep-leaderboard-table tbody').append(response.data.html);
                        button.data('offset', offset + response.data.count);
                        
                        if (!response.data.has_more) {
                            button.hide();
                        }
                    }
                }.bind(this),
                complete: function() {
                    this.hideLoading(button);
                }.bind(this)
            });
        },

        /**
         * Success handlers
         */
        handleRegistrationSuccess: function(response) {
            if (response.success) {
                this.showMessage('success', response.data.message);
                
                // Clear form
                $('#ep-registration-form')[0].reset();
                
                // Redirect after delay
                setTimeout(() => {
                    window.location.href = response.data.redirect_url || this.config.redirectUrl;
                }, 2000);
            } else {
                this.handleRegistrationError(response);
            }
        },

        handleLoginSuccess: function(response) {
            if (response.success) {
                this.showMessage('success', response.data.message);
                
                // Redirect after delay
                setTimeout(() => {
                    window.location.href = response.data.redirect_url || this.config.redirectUrl;
                }, 1000);
            } else {
                this.handleLoginError(response);
            }
        },

        handleProfileUpdateSuccess: function(response) {
            if (response.success) {
                this.showMessage('success', response.data.message);
                
                // Update profile display if needed
                this.updateProfileDisplay(response.data.profile);
            } else {
                this.handleProfileUpdateError(response);
            }
        },

        /**
         * Error handlers
         */
        handleRegistrationError: function(response) {
            const message = response.data?.message || this.config.messages.registration_error;
            this.showMessage('error', message);
        },

        handleLoginError: function(response) {
            const message = response.data?.message || this.config.messages.login_error;
            this.showMessage('error', message);
        },

        handleProfileUpdateError: function(response) {
            const message = response.data?.message || this.config.messages.update_error;
            this.showMessage('error', message);
        },

        /**
         * Utility methods
         */
        showLoading: function(element) {
            element.addClass('ep-loading').prop('disabled', true);
            element.find('.ep-btn-text').hide();
            element.find('.ep-btn-loading').show();
        },

        hideLoading: function(element) {
            element.removeClass('ep-loading').prop('disabled', false);
            element.find('.ep-btn-text').show();
            element.find('.ep-btn-loading').hide();
        },

        showMessage: function(type, message) {
            const container = $('.ep-messages').first();
            const messageEl = container.find('.ep-message.ep-' + type);
            
            messageEl.find('.ep-message-text').text(message);
            container.show();
            messageEl.show();
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(() => {
                    messageEl.fadeOut();
                }, 5000);
            }
        },

        showFieldError: function(field, message) {
            field.addClass('ep-error');
            field.after('<div class="ep-field-error">' + message + '</div>');
        },

        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        initTooltips: function() {
            // Initialize tooltips if library is available
            if ($.fn.tooltip) {
                $('[title]').tooltip();
            }
        },

        initProgressBars: function() {
            // Animate progress bars
            $('.ep-progress-fill').each(function() {
                const progress = $(this);
                const width = progress.css('width');
                progress.css('width', '0').animate({ width: width }, 1000);
            });
        },

        animateStats: function() {
            // Animate stat numbers
            $('.ep-stat-number, .ep-score-number, .ep-impact-value').each(function() {
                const element = $(this);
                const target = parseInt(element.text().replace(/,/g, ''));
                
                if (!isNaN(target)) {
                    element.text('0');
                    $({ count: 0 }).animate({ count: target }, {
                        duration: 2000,
                        step: function() {
                            element.text(Math.floor(this.count).toLocaleString());
                        },
                        complete: function() {
                            element.text(target.toLocaleString());
                        }
                    });
                }
            });
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        EP_UserManagement.init();
    });

})(jQuery);
