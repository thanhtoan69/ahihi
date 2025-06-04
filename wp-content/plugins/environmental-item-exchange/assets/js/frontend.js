/**
 * Environmental Item Exchange - Frontend JavaScript
 * 
 * Handles user interactions for the exchange platform
 */

(function($) {
    'use strict';

    // Main plugin object
    window.EIE_Frontend = {
        
        // Initialize the frontend
        init: function() {
            this.bindEvents();
            this.initSearch();
            this.initForms();
            this.initMaps();
            this.initNotifications();
            console.log('EIE Frontend initialized');
        },
        
        // Bind event handlers
        bindEvents: function() {
            // Search functionality
            $(document).on('click', '#ep-search-btn', this.handleSearch);
            $(document).on('keypress', '#ep-search-input', this.handleSearchKeypress);
            $(document).on('click', '#ep-apply-filters', this.applyFilters);
            $(document).on('click', '#ep-load-more', this.loadMoreExchanges);
            
            // Exchange actions
            $(document).on('click', '.ep-save-exchange', this.saveExchange);
            $(document).on('click', '.ep-contact-owner', this.openContactModal);
            $(document).on('click', '.ep-rate-exchange', this.openRatingModal);
            
            // Form submissions
            $(document).on('submit', '#ep-exchange-form', this.submitExchangeForm);
            $(document).on('submit', '#ep-contact-form', this.submitContactForm);
            $(document).on('submit', '#ep-rating-form', this.submitRatingForm);
            
            // Modal controls
            $(document).on('click', '.ep-modal-close', this.closeModal);
            $(document).on('click', '.ep-modal-overlay', this.closeModal);
            
            // Image upload
            $(document).on('change', '#ep-exchange-images', this.handleImageUpload);
            
            // Location detection
            $(document).on('click', '#ep-detect-location', this.detectLocation);
            
            // Dashboard functionality
            $(document).on('click', '.ep-refresh-dashboard', this.refreshDashboard);
            $(document).on('click', '.ep-view-more', this.viewMoreItems);
        },
        
        // Initialize search functionality
        initSearch: function() {
            // Auto-complete for location search
            if ($('#ep-filter-location').length) {
                this.initLocationAutocomplete();
            }
            
            // Search suggestions
            if ($('#ep-search-input').length) {
                this.initSearchSuggestions();
            }
        },
        
        // Initialize forms
        initForms: function() {
            // Form validation
            this.initFormValidation();
            
            // Image preview
            this.initImagePreview();
            
            // Rich text editor for descriptions
            this.initRichTextEditor();
        },
        
        // Initialize maps
        initMaps: function() {
            if (typeof google !== 'undefined' && $('.ep-map').length) {
                this.initGoogleMaps();
            }
        },
        
        // Initialize notifications
        initNotifications: function() {
            // Check for new notifications periodically
            setInterval(this.checkNotifications.bind(this), 30000); // Every 30 seconds
            
            // Mark notifications as read when clicked
            $(document).on('click', '.ep-notification', this.markNotificationRead);
        },
        
        // Handle search
        handleSearch: function(e) {
            e.preventDefault();
            EIE_Frontend.performSearch(1);
        },
        
        // Handle search keypress
        handleSearchKeypress: function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                EIE_Frontend.performSearch(1);
            }
        },
        
        // Perform search
        performSearch: function(page) {
            const searchTerm = $('#ep-search-input').val();
            const filters = this.getActiveFilters();
            
            this.showLoading('#ep-exchange-list');
            
            $.ajax({
                url: eie_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'ep_search_exchanges',
                    nonce: eie_ajax.nonce,
                    search_term: searchTerm,
                    type: filters.type,
                    category: filters.category,
                    location: filters.location,
                    radius: filters.radius,
                    page: page,
                    limit: 12
                },
                success: function(response) {
                    if (response.success) {
                        if (page === 1) {
                            $('#ep-exchange-list').html(EIE_Frontend.renderExchanges(response.data.exchanges));
                        } else {
                            $('#ep-exchange-list').append(EIE_Frontend.renderExchanges(response.data.exchanges));
                        }
                        
                        // Update load more button
                        if (response.data.has_more) {
                            $('#ep-load-more').show().data('page', page + 1);
                        } else {
                            $('#ep-load-more').hide();
                        }
                        
                        // Update results count
                        $('.ep-results-count').text(response.data.total + ' results found');
                    } else {
                        EIE_Frontend.showError(response.data);
                    }
                },
                error: function() {
                    EIE_Frontend.showError('Search failed. Please try again.');
                },
                complete: function() {
                    EIE_Frontend.hideLoading('#ep-exchange-list');
                }
            });
        },
        
        // Apply filters
        applyFilters: function(e) {
            e.preventDefault();
            EIE_Frontend.performSearch(1);
        },
        
        // Load more exchanges
        loadMoreExchanges: function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            EIE_Frontend.performSearch(page);
        },
        
        // Get active filters
        getActiveFilters: function() {
            return {
                type: $('#ep-filter-type').val(),
                category: $('#ep-filter-category').val(),
                location: $('#ep-filter-location').val(),
                radius: $('#ep-filter-radius').val() || 10
            };
        },
        
        // Save/unsave exchange
        saveExchange: function(e) {
            e.preventDefault();
            const $button = $(this);
            const exchangeId = $button.data('exchange-id');
            
            $.ajax({
                url: eie_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'ep_save_exchange',
                    nonce: eie_ajax.nonce,
                    exchange_id: exchangeId
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.action === 'saved') {
                            $button.addClass('saved').find('.ep-btn-text').text('Saved');
                        } else {
                            $button.removeClass('saved').find('.ep-btn-text').text('Save');
                        }
                        EIE_Frontend.showSuccess(response.data.message);
                    } else {
                        EIE_Frontend.showError(response.data);
                    }
                },
                error: function() {
                    EIE_Frontend.showError('Action failed. Please try again.');
                }
            });
        },
        
        // Open contact modal
        openContactModal: function(e) {
            e.preventDefault();
            const exchangeId = $(this).data('exchange-id');
            const ownerName = $(this).data('owner-name');
            
            $('#ep-contact-modal').find('[name="exchange_id"]').val(exchangeId);
            $('#ep-contact-modal').find('.ep-owner-name').text(ownerName);
            $('#ep-contact-modal').show();
        },
        
        // Open rating modal
        openRatingModal: function(e) {
            e.preventDefault();
            const exchangeId = $(this).data('exchange-id');
            
            $('#ep-rating-modal').find('[name="exchange_id"]').val(exchangeId);
            $('#ep-rating-modal').show();
        },
        
        // Close modal
        closeModal: function(e) {
            if (e.target === this || $(e.target).hasClass('ep-modal-close')) {
                $(this).closest('.ep-modal').hide();
                // Reset forms
                $(this).closest('.ep-modal').find('form')[0]?.reset();
            }
        },
        
        // Submit contact form
        submitContactForm: function(e) {
            e.preventDefault();
            const $form = $(this);
            const formData = new FormData(this);
            formData.append('action', 'ep_contact_exchange_owner');
            formData.append('nonce', eie_ajax.nonce);
            
            EIE_Frontend.showLoading($form.find('.ep-submit-btn'));
            
            $.ajax({
                url: eie_ajax.ajax_url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        EIE_Frontend.showSuccess(response.data);
                        $form.closest('.ep-modal').hide();
                        $form[0].reset();
                    } else {
                        EIE_Frontend.showError(response.data);
                    }
                },
                error: function() {
                    EIE_Frontend.showError('Message failed to send. Please try again.');
                },
                complete: function() {
                    EIE_Frontend.hideLoading($form.find('.ep-submit-btn'));
                }
            });
        },
        
        // Submit rating form
        submitRatingForm: function(e) {
            e.preventDefault();
            const $form = $(this);
            const formData = new FormData(this);
            formData.append('action', 'ep_rate_exchange');
            formData.append('nonce', eie_ajax.nonce);
            
            EIE_Frontend.showLoading($form.find('.ep-submit-btn'));
            
            $.ajax({
                url: eie_ajax.ajax_url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        EIE_Frontend.showSuccess(response.data);
                        $form.closest('.ep-modal').hide();
                        $form[0].reset();
                    } else {
                        EIE_Frontend.showError(response.data);
                    }
                },
                error: function() {
                    EIE_Frontend.showError('Rating failed to submit. Please try again.');
                },
                complete: function() {
                    EIE_Frontend.hideLoading($form.find('.ep-submit-btn'));
                }
            });
        },
        
        // Handle image upload
        handleImageUpload: function(e) {
            const files = e.target.files;
            const $preview = $('#ep-image-preview');
            
            $preview.empty();
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const $img = $('<div class="ep-image-item">')
                        .append('<img src="' + e.target.result + '" alt="Preview">')
                        .append('<button type="button" class="ep-remove-image">×</button>');
                    $preview.append($img);
                };
                
                reader.readAsDataURL(file);
            }
        },
        
        // Detect user location
        detectLocation: function(e) {
            e.preventDefault();
            
            if (!navigator.geolocation) {
                EIE_Frontend.showError('Geolocation is not supported by this browser.');
                return;
            }
            
            const $button = $(this);
            $button.prop('disabled', true).text('Detecting...');
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    // Reverse geocoding to get address
                    EIE_Frontend.reverseGeocode(lat, lng, function(address) {
                        $('#ep-exchange-location').val(address);
                        $button.prop('disabled', false).text('Detect Location');
                    });
                },
                function(error) {
                    EIE_Frontend.showError('Unable to detect location: ' + error.message);
                    $button.prop('disabled', false).text('Detect Location');
                }
            );
        },
        
        // Reverse geocoding
        reverseGeocode: function(lat, lng, callback) {
            if (typeof google !== 'undefined') {
                const geocoder = new google.maps.Geocoder();
                const latlng = new google.maps.LatLng(lat, lng);
                
                geocoder.geocode({ location: latlng }, function(results, status) {
                    if (status === 'OK' && results[0]) {
                        callback(results[0].formatted_address);
                    } else {
                        callback(lat + ', ' + lng);
                    }
                });
            } else {
                callback(lat + ', ' + lng);
            }
        },
        
        // Initialize location autocomplete
        initLocationAutocomplete: function() {
            if (typeof google !== 'undefined') {
                const autocomplete = new google.maps.places.Autocomplete(
                    document.getElementById('ep-filter-location')
                );
            }
        },
        
        // Initialize search suggestions
        initSearchSuggestions: function() {
            let searchTimeout;
            
            $('#ep-search-input').on('input', function() {
                clearTimeout(searchTimeout);
                const query = $(this).val();
                
                if (query.length < 3) {
                    $('#ep-search-suggestions').hide();
                    return;
                }
                
                searchTimeout = setTimeout(function() {
                    EIE_Frontend.loadSearchSuggestions(query);
                }, 300);
            });
        },
        
        // Load search suggestions
        loadSearchSuggestions: function(query) {
            // Implementation for search suggestions
            // This would typically call an AJAX endpoint to get suggestions
        },
        
        // Check for new notifications
        checkNotifications: function() {
            if (!$('body').hasClass('logged-in')) return;
            
            $.ajax({
                url: eie_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'ep_check_notifications',
                    nonce: eie_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.count > 0) {
                        $('.ep-notification-count').text(response.data.count).show();
                    }
                }
            });
        },
        
        // Mark notification as read
        markNotificationRead: function(e) {
            const notificationId = $(this).data('notification-id');
            
            $.ajax({
                url: eie_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'ep_mark_notification_read',
                    nonce: eie_ajax.nonce,
                    notification_id: notificationId
                }
            });
        },
        
        // Refresh dashboard
        refreshDashboard: function(e) {
            e.preventDefault();
            
            $.ajax({
                url: eie_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'ep_get_user_dashboard_data',
                    nonce: eie_ajax.nonce,
                    data_type: 'stats'
                },
                success: function(response) {
                    if (response.success) {
                        EIE_Frontend.updateDashboardStats(response.data);
                    }
                }
            });
        },
        
        // Update dashboard stats
        updateDashboardStats: function(stats) {
            $('.ep-stat-card').each(function() {
                const statType = $(this).data('stat-type');
                if (stats[statType] !== undefined) {
                    $(this).find('.ep-stat-number').text(stats[statType]);
                }
            });
        },
        
        // Render exchanges for search results
        renderExchanges: function(exchanges) {
            let html = '';
            
            exchanges.forEach(function(exchange) {
                html += `
                    <div class="ep-exchange-card" data-exchange-id="${exchange.id}">
                        <div class="ep-exchange-image">
                            ${exchange.thumbnail ? 
                                `<img src="${exchange.thumbnail}" alt="${exchange.title}">` :
                                '<div class="ep-no-image">No Image</div>'
                            }
                        </div>
                        <div class="ep-exchange-content">
                            <h3 class="ep-exchange-title">
                                <a href="${exchange.permalink}">${exchange.title}</a>
                            </h3>
                            <div class="ep-exchange-meta">
                                <span class="ep-exchange-type ep-type-${exchange.type}">${exchange.type}</span>
                                <span class="ep-exchange-condition">${exchange.condition}</span>
                                <span class="ep-exchange-location">${exchange.location}</span>
                            </div>
                            <div class="ep-exchange-excerpt">${exchange.excerpt}</div>
                            <div class="ep-exchange-actions">
                                <button class="ep-btn ep-btn-primary ep-contact-owner" 
                                        data-exchange-id="${exchange.id}" 
                                        data-owner-name="${exchange.author}">
                                    Contact Owner
                                </button>
                                <button class="ep-btn ep-btn-outline ep-save-exchange" 
                                        data-exchange-id="${exchange.id}">
                                    <span class="ep-btn-text">Save</span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            return html;
        },
        
        // Initialize form validation
        initFormValidation: function() {
            $('.ep-form').each(function() {
                $(this).on('submit', function(e) {
                    const isValid = EIE_Frontend.validateForm(this);
                    if (!isValid) {
                        e.preventDefault();
                    }
                });
            });
        },
        
        // Validate form
        validateForm: function(form) {
            let isValid = true;
            
            $(form).find('[required]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (!value) {
                    EIE_Frontend.showFieldError($field, 'This field is required.');
                    isValid = false;
                } else {
                    EIE_Frontend.clearFieldError($field);
                }
            });
            
            // Email validation
            $(form).find('[type="email"]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (value && !EIE_Frontend.isValidEmail(value)) {
                    EIE_Frontend.showFieldError($field, 'Please enter a valid email address.');
                    isValid = false;
                }
            });
            
            return isValid;
        },
        
        // Show field error
        showFieldError: function($field, message) {
            $field.addClass('ep-field-error');
            $field.siblings('.ep-field-error-message').remove();
            $field.after('<div class="ep-field-error-message">' + message + '</div>');
        },
        
        // Clear field error
        clearFieldError: function($field) {
            $field.removeClass('ep-field-error');
            $field.siblings('.ep-field-error-message').remove();
        },
        
        // Validate email
        isValidEmail: function(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },
        
        // Utility functions
        showLoading: function(element) {
            $(element).addClass('ep-loading').prop('disabled', true);
        },
        
        hideLoading: function(element) {
            $(element).removeClass('ep-loading').prop('disabled', false);
        },
        
        showSuccess: function(message) {
            this.showNotification(message, 'success');
        },
        
        showError: function(message) {
            this.showNotification(message, 'error');
        },
        
        showNotification: function(message, type) {
            const $notification = $('<div class="ep-notification ep-notification-' + type + '">')
                .text(message)
                .appendTo('body');
            
            setTimeout(function() {
                $notification.addClass('show');
            }, 100);
            
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        EIE_Frontend.init();
    });

})(jQuery);
