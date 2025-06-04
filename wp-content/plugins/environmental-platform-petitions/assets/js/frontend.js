/**
 * Frontend JavaScript for Environmental Platform Petitions
 * 
 * Handles petition signature form, verification, sharing, and progress tracking
 * 
 * @package Environmental_Platform_Petitions
 * @since 1.0.0 - Phase 35
 */

(function($) {
    'use strict';
    
    // Main Petition Handler
    window.EPP = {
        
        /**
         * Initialize petition functionality
         */
        init: function() {
            this.bindEvents();
            this.initProgressBars();
            this.initShareButtons();
            this.initSignatureForm();
            this.checkVerificationStatus();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Signature form submission
            $(document).on('submit', '.epp-signature-form', this.handleSignature);
            
            // Share button clicks
            $(document).on('click', '.epp-share-btn', this.handleShare);
            
            // Verification form
            $(document).on('submit', '.epp-verification-form', this.handleVerification);
            
            // Resend verification
            $(document).on('click', '.epp-resend-verification', this.resendVerification);
            
            // Modal controls
            $(document).on('click', '.epp-modal-close, .epp-modal-overlay', this.closeModal);
            
            // Phone verification
            $(document).on('click', '.epp-verify-phone', this.verifyPhone);
            
            // Copy link functionality
            $(document).on('click', '.epp-copy-link', this.copyLink);
            
            // Load more signatures
            $(document).on('click', '.epp-load-more-signatures', this.loadMoreSignatures);
        },
        
        /**
         * Initialize progress bars with animation
         */
        initProgressBars: function() {
            $('.epp-progress-fill').each(function() {
                var $this = $(this);
                var width = $this.data('width') || $this.css('width');
                
                $this.css('width', '0%');
                
                setTimeout(function() {
                    $this.animate({ width: width }, 1500, 'easeOutCubic');
                }, 500);
            });
        },
        
        /**
         * Initialize share buttons
         */
        initShareButtons: function() {
            // Track share clicks
            $('.epp-share-btn').on('click', function(e) {
                e.preventDefault();
                var platform = $(this).data('platform');
                var petitionId = $(this).data('petition-id') || $('.epp-signature-form').data('petition-id');
                
                EPP.trackShare(petitionId, platform);
            });
        },
        
        /**
         * Initialize signature form
         */
        initSignatureForm: function() {
            // Auto-fill location if geolocation is available
            if (navigator.geolocation && $('.epp-signature-form #signer_location').length) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    // In a real implementation, you'd reverse geocode the coordinates
                    // For now, we'll just indicate that location was detected
                    $('#signer_location').attr('placeholder', 'Location detected...');
                });
            }
            
            // Form validation
            $('.epp-signature-form input, .epp-signature-form textarea').on('blur', this.validateField);
        },
        
        /**
         * Handle signature form submission
         */
        handleSignature: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $form.find('.epp-sign-button');
            var $message = $form.find('.epp-form-message');
            
            // Validate form
            if (!EPP.validateSignatureForm($form)) {
                return false;
            }
            
            // Disable button and show loading
            $button.prop('disabled', true);
            $button.find('.epp-button-text').hide();
            $button.find('.epp-button-loading').show();
            
            // Prepare form data
            var formData = {
                action: 'sign_petition',
                nonce: epp_ajax.nonce,
                petition_id: $form.data('petition-id'),
                name: $form.find('input[name="name"]').val(),
                email: $form.find('input[name="email"]').val(),
                phone: $form.find('input[name="phone"]').val(),
                location: $form.find('input[name="location"]').val(),
                comment: $form.find('textarea[name="comment"]').val(),
                anonymous: $form.find('input[name="anonymous"]').is(':checked') ? 1 : 0,
                email_updates: $form.find('input[name="email_updates"]').is(':checked') ? 1 : 0
            };
            
            // Submit signature
            $.ajax({
                url: epp_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        EPP.showMessage($message, response.data.message, 'success');
                        
                        if (response.data.requires_verification) {
                            EPP.showVerificationModal(response.data.signature_id);
                        } else {
                            // Update signature count
                            EPP.updateSignatureCount();
                            // Reset form
                            $form[0].reset();
                        }
                        
                        // Track successful signature
                        EPP.trackEvent(formData.petition_id, 'signature_completed');
                        
                    } else {
                        EPP.showMessage($message, response.data || epp_ajax.messages.error, 'error');
                    }
                },
                error: function() {
                    EPP.showMessage($message, epp_ajax.messages.error, 'error');
                },
                complete: function() {
                    // Re-enable button
                    $button.prop('disabled', false);
                    $button.find('.epp-button-text').show();
                    $button.find('.epp-button-loading').hide();
                }
            });
        },
        
        /**
         * Validate signature form
         */
        validateSignatureForm: function($form) {
            var isValid = true;
            var $message = $form.find('.epp-form-message');
            
            // Clear previous messages
            $message.hide();
            
            // Required fields
            var requiredFields = ['name', 'email'];
            
            requiredFields.forEach(function(fieldName) {
                var $field = $form.find('input[name="' + fieldName + '"]');
                var value = $field.val().trim();
                
                if (!value) {
                    EPP.showFieldError($field, epp_ajax.messages[fieldName + '_required']);
                    isValid = false;
                }
            });
            
            // Email validation
            var email = $form.find('input[name="email"]').val();
            if (email && !EPP.isValidEmail(email)) {
                EPP.showFieldError($form.find('input[name="email"]'), 'Please enter a valid email address.');
                isValid = false;
            }
            
            return isValid;
        },
        
        /**
         * Validate individual field
         */
        validateField: function() {
            var $field = $(this);
            var value = $field.val().trim();
            var fieldName = $field.attr('name');
            
            // Clear previous error
            $field.removeClass('error');
            $field.next('.field-error').remove();
            
            // Check required fields
            if ($field.prop('required') && !value) {
                EPP.showFieldError($field, 'This field is required.');
                return false;
            }
            
            // Email validation
            if (fieldName === 'email' && value && !EPP.isValidEmail(value)) {
                EPP.showFieldError($field, 'Please enter a valid email address.');
                return false;
            }
            
            return true;
        },
        
        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            $field.addClass('error');
            $field.after('<div class="field-error">' + message + '</div>');
        },
        
        /**
         * Validate email format
         */
        isValidEmail: function(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },
        
        /**
         * Show message in form
         */
        showMessage: function($messageEl, message, type) {
            $messageEl.removeClass('success error').addClass(type);
            $messageEl.html(message).show();
            
            // Auto hide after 5 seconds
            setTimeout(function() {
                $messageEl.fadeOut();
            }, 5000);
        },
        
        /**
         * Show verification modal
         */
        showVerificationModal: function(signatureId) {
            var modalHtml = `
                <div class="epp-verification-modal">
                    <h3>Verify Your Signature</h3>
                    <p>We've sent a verification link to your email address. Please check your email and click the link to complete your signature.</p>
                    <div class="epp-verification-actions">
                        <button class="epp-resend-verification" data-signature-id="${signatureId}">
                            Resend Verification Email
                        </button>
                        <button class="epp-close-modal">Close</button>
                    </div>
                </div>
            `;
            
            EPP.showModal(modalHtml);
        },
        
        /**
         * Show modal
         */
        showModal: function(content) {
            var $modal = $('#epp-signature-modal');
            if (!$modal.length) {
                $('body').append('<div id="epp-signature-modal" class="epp-modal"><div class="epp-modal-content"><span class="epp-modal-close">&times;</span><div id="epp-modal-body"></div></div></div>');
                $modal = $('#epp-signature-modal');
            }
            
            $modal.find('#epp-modal-body').html(content);
            $modal.show();
        },
        
        /**
         * Close modal
         */
        closeModal: function() {
            $('.epp-modal').hide();
        },
        
        /**
         * Resend verification email
         */
        resendVerification: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var signatureId = $button.data('signature-id');
            
            $button.prop('disabled', true).text('Sending...');
            
            $.ajax({
                url: epp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'resend_verification',
                    nonce: epp_ajax.nonce,
                    signature_id: signatureId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Verification email sent successfully!');
                    } else {
                        alert('Error sending verification email. Please try again.');
                    }
                },
                error: function() {
                    alert('Error sending verification email. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Resend Verification Email');
                }
            });
        },
        
        /**
         * Handle social sharing
         */
        handleShare: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var platform = $button.data('platform');
            var petitionId = $button.data('petition-id') || $('.epp-signature-form').data('petition-id');
            var url = window.location.href;
            var title = document.title;
            
            var shareUrls = {
                facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
                twitter: `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`,
                linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`,
                whatsapp: `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`,
                email: `mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent('Check out this important petition: ' + url)}`
            };
            
            if (shareUrls[platform]) {
                if (platform === 'email') {
                    window.location.href = shareUrls[platform];
                } else {
                    window.open(shareUrls[platform], 'share', 'width=600,height=400');
                }
                
                // Track share
                EPP.trackShare(petitionId, platform);
            }
        },
        
        /**
         * Track share event
         */
        trackShare: function(petitionId, platform) {
            $.ajax({
                url: epp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'petition_share',
                    nonce: epp_ajax.nonce,
                    petition_id: petitionId,
                    platform: platform
                }
            });
        },
        
        /**
         * Copy petition link to clipboard
         */
        copyLink: function(e) {
            e.preventDefault();
            
            var url = window.location.href;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    alert('Link copied to clipboard!');
                });
            } else {
                // Fallback for older browsers
                var textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('Link copied to clipboard!');
            }
        },
        
        /**
         * Update signature count dynamically
         */
        updateSignatureCount: function() {
            var petitionId = $('.epp-signature-form').data('petition-id');
            
            $.ajax({
                url: epp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_signature_count',
                    petition_id: petitionId
                },
                success: function(response) {
                    if (response.success) {
                        $('.epp-signature-count strong').text(response.data.count.toLocaleString());
                        
                        // Update progress bar
                        var percentage = (response.data.count / response.data.target) * 100;
                        $('.epp-progress-fill').animate({ width: percentage + '%' }, 500);
                    }
                }
            });
        },
        
        /**
         * Load more signatures
         */
        loadMoreSignatures: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var petitionId = $button.data('petition-id');
            var offset = $button.data('offset') || 0;
            
            $button.text('Loading...').prop('disabled', true);
            
            $.ajax({
                url: epp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_more_signatures',
                    petition_id: petitionId,
                    offset: offset
                },
                success: function(response) {
                    if (response.success && response.data.signatures.length > 0) {
                        $('.epp-signatures-list').append(response.data.html);
                        $button.data('offset', offset + response.data.signatures.length);
                        
                        if (response.data.signatures.length < 20) {
                            $button.hide(); // No more signatures to load
                        }
                    } else {
                        $button.hide();
                    }
                },
                complete: function() {
                    $button.text('Load More Signatures').prop('disabled', false);
                }
            });
        },
        
        /**
         * Check verification status from URL parameters
         */
        checkVerificationStatus: function() {
            var urlParams = new URLSearchParams(window.location.search);
            var verification = urlParams.get('verification');
            
            if (verification) {
                var message = '';
                var type = '';
                
                if (verification === 'success') {
                    message = 'Your signature has been verified successfully! Thank you for your support.';
                    type = 'success';
                } else if (verification === 'failed') {
                    message = 'Signature verification failed. Please check your verification link or contact support.';
                    type = 'error';
                }
                
                if (message) {
                    EPP.showNotification(message, type);
                    
                    // Clean URL
                    var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                    window.history.replaceState({path: newUrl}, '', newUrl);
                }
            }
        },
        
        /**
         * Show notification banner
         */
        showNotification: function(message, type) {
            var $notification = $(`
                <div class="epp-notification epp-${type}">
                    <div class="epp-notification-content">
                        ${message}
                        <button class="epp-notification-close">&times;</button>
                    </div>
                </div>
            `);
            
            $('body').prepend($notification);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Manual close
            $notification.on('click', '.epp-notification-close', function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            });
        },
        
        /**
         * Track analytics events
         */
        trackEvent: function(petitionId, eventType, eventData) {
            $.ajax({
                url: epp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'track_petition_event',
                    petition_id: petitionId,
                    event_type: eventType,
                    event_data: JSON.stringify(eventData || {})
                }
            });
        },
        
        /**
         * Phone verification
         */
        verifyPhone: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var phoneNumber = $('#phone_verification').val();
            var signatureId = $button.data('signature-id');
            
            if (!phoneNumber) {
                alert('Please enter your phone number.');
                return;
            }
            
            $button.prop('disabled', true).text('Sending...');
            
            $.ajax({
                url: epp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'verify_phone',
                    nonce: epp_ajax.nonce,
                    phone_number: phoneNumber,
                    signature_id: signatureId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Verification code sent to your phone!');
                        EPP.showPhoneVerificationForm(signatureId);
                    } else {
                        alert(response.data || 'Error sending verification code.');
                    }
                },
                error: function() {
                    alert('Error sending verification code. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Send Verification Code');
                }
            });
        },
        
        /**
         * Show phone verification form
         */
        showPhoneVerificationForm: function(signatureId) {
            var formHtml = `
                <div class="epp-phone-verification">
                    <h3>Enter Verification Code</h3>
                    <p>Enter the 6-digit code sent to your phone:</p>
                    <input type="text" id="phone_code" maxlength="6" placeholder="123456">
                    <button class="epp-verify-phone-code" data-signature-id="${signatureId}">
                        Verify Code
                    </button>
                </div>
            `;
            
            EPP.showModal(formHtml);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        EPP.init();
    });
    
    // Add custom easing function for smooth animations
    $.easing.easeOutCubic = function(x, t, b, c, d) {
        return c*((t=t/d-1)*t*t + 1) + b;
    };
    
})(jQuery);

// Global functions for backward compatibility
function showSignForm() {
    jQuery('#petition-sign-form').get(0).scrollIntoView({ behavior: 'smooth' });
}

function sharePetition(platform) {
    jQuery('.epp-share-btn[data-platform="' + platform + '"]').trigger('click');
}

function copyPetitionLink() {
    jQuery('.epp-copy-link').trigger('click');
}

function viewAllSignatures() {
    jQuery('.epp-signatures-section').get(0).scrollIntoView({ behavior: 'smooth' });
}
