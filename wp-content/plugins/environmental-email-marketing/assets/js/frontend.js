/**
 * Environmental Email Marketing - Frontend JavaScript
 * Handles subscription forms, newsletter popups, preference centers, and user interactions
 */

(function($) {
    'use strict';

    // Global variables
    let eemFrontend = {
        ajaxUrl: eem_frontend_vars.ajax_url,
        nonce: eem_frontend_vars.nonce,
        isProcessing: false,
        popupShown: false,
        popupSettings: eem_frontend_vars.popup_settings || {},
        environmentalTheme: eem_frontend_vars.environmental_theme || 'nature'
    };

    /**
     * Initialize frontend functionality
     */
    function init() {
        initSubscriptionForms();
        initNewsletterPopup();
        initPreferenceCenter();
        initUnsubscribeHandling();
        initEnvironmentalFeatures();
        initValidation();
        initAnimations();
        
        console.log('EEM Frontend initialized successfully');
    }

    /**
     * Initialize subscription forms
     */
    function initSubscriptionForms() {
        // Handle form submissions
        $('.eem-subscription-form').on('submit', function(e) {
            e.preventDefault();
            handleSubscriptionSubmission($(this));
        });
        
        // Real-time validation
        $('.eem-subscription-form input[type="email"]').on('blur', function() {
            validateEmail($(this));
        });
        
        // Environmental preferences toggle
        $('.eem-environmental-interests input').on('change', function() {
            updateEnvironmentalInterests();
        });
        
        // GDPR consent handling
        $('.eem-gdpr-consent').on('change', function() {
            toggleSubmitButton($(this).closest('form'));
        });
        
        // Privacy policy modal
        $('.eem-privacy-link').on('click', function(e) {
            e.preventDefault();
            openPrivacyModal();
        });
    }

    /**
     * Handle subscription form submission
     */
    function handleSubscriptionSubmission(form) {
        if (eemFrontend.isProcessing) return;
        
        // Validate form
        if (!validateSubscriptionForm(form)) {
            return;
        }
        
        eemFrontend.isProcessing = true;
        
        // Show loading state
        const submitBtn = form.find('.eem-submit-btn');
        const originalText = submitBtn.text();
        submitBtn.text('Subscribing...').prop('disabled', true);
        
        // Collect form data
        const formData = {
            action: 'eem_subscribe',
            nonce: eemFrontend.nonce,
            email: form.find('input[name="email"]').val(),
            name: form.find('input[name="name"]').val() || '',
            interests: collectInterests(form),
            source: form.data('source') || 'website',
            environmental_preferences: collectEnvironmentalPreferences(form),
            gdpr_consent: form.find('.eem-gdpr-consent').is(':checked')
        };
        
        $.ajax({
            url: eemFrontend.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    handleSubscriptionSuccess(form, response.data);
                } else {
                    handleSubscriptionError(form, response.data);
                }
            },
            error: function() {
                handleSubscriptionError(form, 'Network error occurred. Please try again.');
            },
            complete: function() {
                eemFrontend.isProcessing = false;
                submitBtn.text(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Handle successful subscription
     */
    function handleSubscriptionSuccess(form, data) {
        // Show success message
        const successMessage = $(`
            <div class="eem-success-message eem-environmental-success">
                <div class="eem-success-icon">🌱</div>
                <h3>Welcome to our environmental community!</h3>
                <p>${data.message || 'Thank you for subscribing to our newsletter.'}</p>
                ${data.requires_confirmation ? '<p><small>Please check your email to confirm your subscription.</small></p>' : ''}
                <div class="eem-environmental-impact">
                    <span class="eem-impact-text">Your environmental impact score: <strong>${data.environmental_score || 0}</strong></span>
                </div>
            </div>
        `);
        
        // Replace form with success message
        form.fadeOut(300, function() {
            form.replaceWith(successMessage);
            successMessage.fadeIn(300);
        });
        
        // Track conversion
        trackSubscriptionConversion(data);
        
        // Close popup if it's in a popup
        if (form.closest('.eem-newsletter-popup').length > 0) {
            setTimeout(() => {
                closeNewsletterPopup();
            }, 3000);
        }
    }

    /**
     * Handle subscription error
     */
    function handleSubscriptionError(form, errorMessage) {
        const errorDiv = form.find('.eem-error-message');
        
        if (errorDiv.length > 0) {
            errorDiv.text(errorMessage).fadeIn();
        } else {
            const error = $(`<div class="eem-error-message">${errorMessage}</div>`);
            form.prepend(error);
            error.fadeIn();
        }
        
        // Hide error after 5 seconds
        setTimeout(() => {
            form.find('.eem-error-message').fadeOut();
        }, 5000);
    }

    /**
     * Initialize newsletter popup
     */
    function initNewsletterPopup() {
        if (!eemFrontend.popupSettings.enabled) return;
        
        // Check if popup should be shown
        if (shouldShowPopup()) {
            schedulePopup();
        }
        
        // Close button
        $(document).on('click', '.eem-popup-close', function() {
            closeNewsletterPopup();
        });
        
        // Overlay click to close
        $(document).on('click', '.eem-popup-overlay', function(e) {
            if (e.target === this) {
                closeNewsletterPopup();
            }
        });
        
        // ESC key to close
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27 && $('.eem-newsletter-popup').is(':visible')) {
                closeNewsletterPopup();
            }
        });
    }

    /**
     * Check if popup should be shown
     */
    function shouldShowPopup() {
        // Check localStorage for popup dismissal
        const popupDismissed = localStorage.getItem('eem_popup_dismissed');
        if (popupDismissed) {
            const dismissedTime = parseInt(popupDismissed);
            const daysSince = (Date.now() - dismissedTime) / (1000 * 60 * 60 * 24);
            if (daysSince < (eemFrontend.popupSettings.delay_days || 7)) {
                return false;
            }
        }
        
        // Check if already subscribed
        const isSubscribed = localStorage.getItem('eem_subscribed');
        if (isSubscribed) return false;
        
        // Check page conditions
        const currentPage = window.location.pathname;
        const excludedPages = eemFrontend.popupSettings.excluded_pages || [];
        if (excludedPages.some(page => currentPage.includes(page))) {
            return false;
        }
        
        return true;
    }

    /**
     * Schedule popup display
     */
    function schedulePopup() {
        const triggerType = eemFrontend.popupSettings.trigger || 'time_delay';
        const delay = parseInt(eemFrontend.popupSettings.time_delay || 5) * 1000;
        
        switch (triggerType) {
            case 'time_delay':
                setTimeout(showNewsletterPopup, delay);
                break;
            case 'scroll_percentage':
                initScrollTrigger();
                break;
            case 'exit_intent':
                initExitIntentTrigger();
                break;
            case 'page_views':
                checkPageViewTrigger();
                break;
        }
    }

    /**
     * Show newsletter popup
     */
    function showNewsletterPopup() {
        if (eemFrontend.popupShown) return;
        
        eemFrontend.popupShown = true;
        
        const popup = createNewsletterPopup();
        $('body').append(popup);
        
        // Animate popup
        setTimeout(() => {
            popup.addClass('eem-popup-visible');
        }, 100);
        
        // Track popup impression
        trackPopupImpression();
    }

    /**
     * Create newsletter popup HTML
     */
    function createNewsletterPopup() {
        const settings = eemFrontend.popupSettings;
        const theme = eemFrontend.environmentalTheme;
        
        return $(`
            <div class="eem-newsletter-popup eem-theme-${theme}">
                <div class="eem-popup-overlay"></div>
                <div class="eem-popup-content">
                    <button class="eem-popup-close">&times;</button>
                    <div class="eem-popup-header">
                        <div class="eem-environmental-icon">🌍</div>
                        <h2>${settings.title || 'Join Our Environmental Newsletter'}</h2>
                        <p>${settings.description || 'Stay updated on environmental news, tips, and actions you can take to make a difference.'}</p>
                    </div>
                    <form class="eem-subscription-form eem-popup-form" data-source="popup">
                        <div class="eem-form-group">
                            <input type="email" name="email" placeholder="Enter your email address" required>
                        </div>
                        <div class="eem-form-group">
                            <input type="text" name="name" placeholder="Your name (optional)">
                        </div>
                        <div class="eem-environmental-interests">
                            <label>Environmental interests (optional):</label>
                            <div class="eem-interest-options">
                                <label><input type="checkbox" name="interests[]" value="climate_change"> Climate Change</label>
                                <label><input type="checkbox" name="interests[]" value="renewable_energy"> Renewable Energy</label>
                                <label><input type="checkbox" name="interests[]" value="conservation"> Conservation</label>
                                <label><input type="checkbox" name="interests[]" value="sustainable_living"> Sustainable Living</label>
                            </div>
                        </div>
                        <div class="eem-gdpr-consent-group">
                            <label>
                                <input type="checkbox" class="eem-gdpr-consent" required>
                                I agree to receive environmental newsletters and updates. You can unsubscribe at any time.
                            </label>
                        </div>
                        <button type="submit" class="eem-submit-btn eem-btn-environmental">Subscribe Now</button>
                        <div class="eem-environmental-promise">
                            <small>🌱 We're committed to reducing our digital carbon footprint. Emails are optimized for minimal environmental impact.</small>
                        </div>
                    </form>
                </div>
            </div>
        `);
    }

    /**
     * Close newsletter popup
     */
    function closeNewsletterPopup() {
        const popup = $('.eem-newsletter-popup');
        popup.removeClass('eem-popup-visible');
        
        setTimeout(() => {
            popup.remove();
        }, 300);
        
        // Remember dismissal
        localStorage.setItem('eem_popup_dismissed', Date.now().toString());
    }

    /**
     * Initialize scroll trigger
     */
    function initScrollTrigger() {
        const scrollPercentage = parseInt(eemFrontend.popupSettings.scroll_percentage || 50);
        
        $(window).on('scroll', function() {
            const scrolled = ($(window).scrollTop() / ($(document).height() - $(window).height())) * 100;
            
            if (scrolled >= scrollPercentage && !eemFrontend.popupShown) {
                showNewsletterPopup();
                $(window).off('scroll'); // Remove event listener
            }
        });
    }

    /**
     * Initialize exit intent trigger
     */
    function initExitIntentTrigger() {
        let hasTriggered = false;
        
        $(document).on('mouseleave', function(e) {
            if (e.clientY <= 0 && !hasTriggered && !eemFrontend.popupShown) {
                hasTriggered = true;
                showNewsletterPopup();
            }
        });
    }

    /**
     * Initialize preference center
     */
    function initPreferenceCenter() {
        // Preference form submission
        $('.eem-preference-form').on('submit', function(e) {
            e.preventDefault();
            handlePreferenceUpdate($(this));
        });
        
        // Interest category toggles
        $('.eem-interest-category').on('change', function() {
            updateInterestCategory($(this));
        });
        
        // Frequency selection
        $('.eem-frequency-option').on('change', function() {
            updateEmailFrequency($(this));
        });
        
        // Environmental scoring display
        initEnvironmentalScoring();
    }

    /**
     * Handle preference updates
     */
    function handlePreferenceUpdate(form) {
        if (eemFrontend.isProcessing) return;
        
        eemFrontend.isProcessing = true;
        
        const submitBtn = form.find('.eem-update-preferences');
        const originalText = submitBtn.text();
        submitBtn.text('Updating...').prop('disabled', true);
        
        const formData = {
            action: 'eem_update_preferences',
            nonce: eemFrontend.nonce,
            subscriber_id: form.find('input[name="subscriber_id"]').val(),
            interests: collectInterests(form),
            frequency: form.find('input[name="frequency"]:checked').val(),
            environmental_preferences: collectEnvironmentalPreferences(form)
        };
        
        $.ajax({
            url: eemFrontend.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showPreferenceSuccess();
                } else {
                    showPreferenceError(response.data);
                }
            },
            error: function() {
                showPreferenceError('Network error occurred. Please try again.');
            },
            complete: function() {
                eemFrontend.isProcessing = false;
                submitBtn.text(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Initialize unsubscribe handling
     */
    function initUnsubscribeHandling() {
        $('.eem-unsubscribe-form').on('submit', function(e) {
            e.preventDefault();
            handleUnsubscribe($(this));
        });
        
        // Unsubscribe feedback
        $('.eem-unsubscribe-reason').on('change', function() {
            const reason = $(this).val();
            toggleFeedbackInput(reason);
        });
    }

    /**
     * Handle unsubscribe
     */
    function handleUnsubscribe(form) {
        if (eemFrontend.isProcessing) return;
        
        // Confirmation
        if (!confirm('Are you sure you want to unsubscribe from our environmental newsletter?')) {
            return;
        }
        
        eemFrontend.isProcessing = true;
        
        const formData = {
            action: 'eem_unsubscribe',
            nonce: eemFrontend.nonce,
            subscriber_id: form.find('input[name="subscriber_id"]').val(),
            reason: form.find('select[name="reason"]').val(),
            feedback: form.find('textarea[name="feedback"]').val()
        };
        
        $.ajax({
            url: eemFrontend.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showUnsubscribeSuccess();
                } else {
                    showUnsubscribeError(response.data);
                }
            },
            error: function() {
                showUnsubscribeError('Network error occurred. Please try again.');
            },
            complete: function() {
                eemFrontend.isProcessing = false;
            }
        });
    }

    /**
     * Initialize environmental features
     */
    function initEnvironmentalFeatures() {
        // Carbon footprint calculator
        $('.eem-carbon-calculator').on('click', function() {
            openCarbonCalculator();
        });
        
        // Environmental tip display
        displayEnvironmentalTip();
        
        // Green actions tracking
        $('.eem-green-action').on('click', function() {
            trackGreenAction($(this).data('action'));
        });
        
        // Eco-friendly email reminder
        showEcoFriendlyReminder();
    }

    /**
     * Display environmental tip
     */
    function displayEnvironmentalTip() {
        const tips = [
            "Digital emails have a lower carbon footprint than printed newsletters. Thank you for going digital! 🌱",
            "Unsubscribing from unwanted emails reduces digital clutter and energy consumption. 💚",
            "Reading emails on devices with longer battery life reduces energy consumption. 🔋",
            "Deleting old emails regularly helps reduce server storage energy needs. 🗂️",
            "Sharing environmental articles digitally amplifies positive impact! 📢"
        ];
        
        const randomTip = tips[Math.floor(Math.random() * tips.length)];
        const tipElement = $('.eem-environmental-tip');
        
        if (tipElement.length > 0) {
            tipElement.html(`<span class="eem-tip-icon">💡</span> ${randomTip}`);
        }
    }

    /**
     * Track green action
     */
    function trackGreenAction(action) {
        $.ajax({
            url: eemFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'eem_track_green_action',
                nonce: eemFrontend.nonce,
                green_action: action
            }
        });
    }

    /**
     * Initialize form validation
     */
    function initValidation() {
        // Email validation
        $('input[type="email"]').on('input', function() {
            validateEmailField($(this));
        });
        
        // Required field validation
        $('input[required], textarea[required], select[required]').on('blur', function() {
            validateRequiredField($(this));
        });
    }

    /**
     * Validate email field
     */
    function validateEmailField(field) {
        const email = field.val();
        const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        
        field.toggleClass('eem-invalid', !isValid && email.length > 0);
        field.toggleClass('eem-valid', isValid);
        
        return isValid;
    }

    /**
     * Validate subscription form
     */
    function validateSubscriptionForm(form) {
        let isValid = true;
        
        // Email validation
        const emailField = form.find('input[name="email"]');
        if (!validateEmailField(emailField)) {
            isValid = false;
        }
        
        // GDPR consent
        const gdprField = form.find('.eem-gdpr-consent');
        if (gdprField.length > 0 && !gdprField.is(':checked')) {
            gdprField.addClass('eem-invalid');
            isValid = false;
        }
        
        return isValid;
    }

    /**
     * Collect interests from form
     */
    function collectInterests(form) {
        const interests = [];
        form.find('input[name="interests[]"]:checked').each(function() {
            interests.push($(this).val());
        });
        return interests;
    }

    /**
     * Collect environmental preferences
     */
    function collectEnvironmentalPreferences(form) {
        return {
            carbon_offset_interest: form.find('input[name="carbon_offset"]').is(':checked'),
            renewable_energy_updates: form.find('input[name="renewable_energy"]').is(':checked'),
            sustainable_living_tips: form.find('input[name="sustainable_living"]').is(':checked'),
            local_environmental_events: form.find('input[name="local_events"]').is(':checked')
        };
    }

    /**
     * Initialize animations
     */
    function initAnimations() {
        // Fade in elements on scroll
        $(window).on('scroll', function() {
            $('.eem-fade-in').each(function() {
                const elementTop = $(this).offset().top;
                const elementBottom = elementTop + $(this).outerHeight();
                const viewportTop = $(window).scrollTop();
                const viewportBottom = viewportTop + $(window).height();
                
                if (elementBottom > viewportTop && elementTop < viewportBottom) {
                    $(this).addClass('eem-visible');
                }
            });
        });
        
        // Environmental theme animations
        $('.eem-environmental-icon').hover(function() {
            $(this).addClass('eem-pulse');
        }, function() {
            $(this).removeClass('eem-pulse');
        });
    }

    /**
     * Track subscription conversion
     */
    function trackSubscriptionConversion(data) {
        // Google Analytics tracking
        if (typeof gtag !== 'undefined') {
            gtag('event', 'subscribe', {
                event_category: 'Email Marketing',
                event_label: 'Environmental Newsletter',
                value: 1
            });
        }
        
        // Facebook Pixel tracking
        if (typeof fbq !== 'undefined') {
            fbq('track', 'Subscribe', {
                content_name: 'Environmental Newsletter'
            });
        }
        
        // Custom tracking
        $.ajax({
            url: eemFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'eem_track_conversion',
                nonce: eemFrontend.nonce,
                event: 'subscription',
                source: data.source || 'website'
            }
        });
    }

    /**
     * Track popup impression
     */
    function trackPopupImpression() {
        $.ajax({
            url: eemFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'eem_track_popup_impression',
                nonce: eemFrontend.nonce
            }
        });
    }

    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        const notification = $(`
            <div class="eem-notification eem-notification-${type}">
                <div class="eem-notification-content">
                    ${message}
                    <button class="eem-notification-close">&times;</button>
                </div>
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(() => {
            notification.addClass('eem-show');
        }, 100);
        
        setTimeout(() => {
            notification.removeClass('eem-show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
        
        notification.find('.eem-notification-close').on('click', function() {
            notification.removeClass('eem-show');
            setTimeout(() => notification.remove(), 300);
        });
    }

    /**
     * Initialize environmental scoring display
     */
    function initEnvironmentalScoring() {
        $('.eem-environmental-score-display').each(function() {
            const score = parseInt($(this).data('score'));
            const maxScore = parseInt($(this).data('max-score') || 100);
            const percentage = Math.min((score / maxScore) * 100, 100);
            
            // Animate score bar
            const scoreBar = $(this).find('.eem-score-bar');
            scoreBar.css('width', '0%');
            
            setTimeout(() => {
                scoreBar.css({
                    'width': percentage + '%',
                    'transition': 'width 1s ease-in-out'
                });
            }, 500);
            
            // Update score text
            $(this).find('.eem-score-text').text(score + ' / ' + maxScore);
        });
    }

    // Initialize when document is ready
    $(document).ready(function() {
        init();
    });

    // Expose public methods
    window.eemFrontend = {
        showNotification: showNotification,
        showNewsletterPopup: showNewsletterPopup,
        closeNewsletterPopup: closeNewsletterPopup,
        trackGreenAction: trackGreenAction
    };

})(jQuery);

/**
 * Utility functions for external usage
 */
function eemSubscribe(email, options = {}) {
    if (!email || !validateEmail(email)) {
        console.error('Valid email address required');
        return;
    }
    
    jQuery.ajax({
        url: eem_frontend_vars.ajax_url,
        type: 'POST',
        data: {
            action: 'eem_subscribe',
            nonce: eem_frontend_vars.nonce,
            email: email,
            name: options.name || '',
            interests: options.interests || [],
            source: options.source || 'api',
            environmental_preferences: options.environmental_preferences || {},
            gdpr_consent: options.gdpr_consent || false
        },
        success: function(response) {
            if (options.callback) {
                options.callback(response);
            }
        }
    });
}

function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function eemTrackEnvironmentalAction(action) {
    if (typeof window.eemFrontend !== 'undefined') {
        window.eemFrontend.trackGreenAction(action);
    }
}
