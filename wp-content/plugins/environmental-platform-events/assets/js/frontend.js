/**
 * Environmental Platform Events - Frontend JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    // Main Events object
    window.EP_Events = {
        // Configuration
        config: {
            ajaxUrl: ep_events_ajax.ajax_url,
            nonce: ep_events_ajax.nonce,
            currentView: 'month',
            currentDate: new Date(),
            eventCache: {},
            isLoading: false
        },

        // Initialize the events system
        init: function() {
            this.bindEvents();
            this.initCalendar();
            this.initRegistrationForms();
            this.initFilters();
            this.loadGoogleMaps();
        },

        // Bind event handlers
        bindEvents: function() {
            // Calendar navigation
            $(document).on('click', '.ep-calendar-nav button', this.handleCalendarNav.bind(this));
            $(document).on('click', '.ep-view-btn', this.handleViewChange.bind(this));
            
            // Event registration
            $(document).on('submit', '.ep-registration-form', this.handleRegistration.bind(this));
            $(document).on('click', '.ep-cancel-registration', this.handleCancellation.bind(this));
            
            // Event interactions
            $(document).on('click', '.ep-event-item', this.showEventPopup.bind(this));
            $(document).on('click', '.ep-close-popup', this.closeEventPopup.bind(this));
            
            // Filters
            $(document).on('change', '.ep-events-filters select', this.handleFilterChange.bind(this));
            $(document).on('click', '.ep-filter-reset', this.resetFilters.bind(this));
            
            // QR Code generation
            $(document).on('click', '.ep-generate-qr', this.generateQRCode.bind(this));
        },

        // Initialize calendar functionality
        initCalendar: function() {
            if ($('.ep-events-calendar').length === 0) return;
            
            this.renderCalendar();
            this.loadCalendarEvents();
        },

        // Render calendar structure
        renderCalendar: function() {
            const calendar = $('.ep-events-calendar');
            const currentDate = this.config.currentDate;
            const currentView = this.config.currentView;

            if (currentView === 'month') {
                this.renderMonthView(calendar, currentDate);
            } else if (currentView === 'week') {
                this.renderWeekView(calendar, currentDate);
            } else if (currentView === 'list') {
                this.renderListView(calendar, currentDate);
            }
        },

        // Render month view
        renderMonthView: function(calendar, date) {
            const year = date.getFullYear();
            const month = date.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());

            let html = '<div class="ep-calendar-month">';
            
            // Day headers
            const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            dayNames.forEach(day => {
                html += `<div class="ep-calendar-day-header">${day}</div>`;
            });

            // Calendar days
            const currentDate = new Date(startDate);
            for (let week = 0; week < 6; week++) {
                for (let day = 0; day < 7; day++) {
                    const isCurrentMonth = currentDate.getMonth() === month;
                    const isToday = this.isToday(currentDate);
                    const dayClass = `ep-calendar-day ${!isCurrentMonth ? 'other-month' : ''} ${isToday ? 'today' : ''}`;
                    
                    html += `<div class="${dayClass}" data-date="${this.formatDate(currentDate)}">`;
                    html += `<div class="ep-day-number">${currentDate.getDate()}</div>`;
                    html += `<div class="ep-day-events" data-date="${this.formatDate(currentDate)}"></div>`;
                    html += '</div>';
                    
                    currentDate.setDate(currentDate.getDate() + 1);
                }
            }

            html += '</div>';
            calendar.find('.ep-calendar-content').html(html);
        },

        // Load events for calendar
        loadCalendarEvents: function() {
            if (this.config.isLoading) return;
            
            this.config.isLoading = true;
            this.showLoading();

            const data = {
                action: 'ep_get_calendar_events',
                nonce: this.config.nonce,
                year: this.config.currentDate.getFullYear(),
                month: this.config.currentDate.getMonth() + 1,
                view: this.config.currentView
            };

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.renderCalendarEvents(response.data.events);
                        this.config.eventCache[this.getCacheKey()] = response.data.events;
                    } else {
                        this.showError('Failed to load events: ' + response.data.message);
                    }
                },
                error: () => {
                    this.showError('Failed to load events. Please try again.');
                },
                complete: () => {
                    this.config.isLoading = false;
                    this.hideLoading();
                }
            });
        },

        // Render events on calendar
        renderCalendarEvents: function(events) {
            $('.ep-day-events').empty();

            events.forEach(event => {
                const eventDate = new Date(event.date);
                const dateStr = this.formatDate(eventDate);
                const dayContainer = $(`.ep-day-events[data-date="${dateStr}"]`);
                
                if (dayContainer.length > 0) {
                    const eventHtml = `
                        <div class="ep-event-item ${event.type}" data-event-id="${event.id}" title="${event.title}">
                            ${event.title}
                        </div>
                    `;
                    dayContainer.append(eventHtml);
                }
            });
        },

        // Handle calendar navigation
        handleCalendarNav: function(e) {
            e.preventDefault();
            const button = $(e.currentTarget);
            const action = button.data('action');
            
            if (action === 'prev') {
                this.config.currentDate.setMonth(this.config.currentDate.getMonth() - 1);
            } else if (action === 'next') {
                this.config.currentDate.setMonth(this.config.currentDate.getMonth() + 1);
            } else if (action === 'today') {
                this.config.currentDate = new Date();
            }

            this.updateCalendarTitle();
            this.renderCalendar();
            this.loadCalendarEvents();
        },

        // Handle view change
        handleViewChange: function(e) {
            e.preventDefault();
            const button = $(e.currentTarget);
            const view = button.data('view');
            
            $('.ep-view-btn').removeClass('active');
            button.addClass('active');
            
            this.config.currentView = view;
            this.renderCalendar();
            this.loadCalendarEvents();
        },

        // Initialize registration forms
        initRegistrationForms: function() {
            this.setupFormValidation();
            this.loadRegistrationStatus();
        },

        // Handle event registration
        handleRegistration: function(e) {
            e.preventDefault();
            
            const form = $(e.target);
            const eventId = form.data('event-id');
            const submitButton = form.find('.ep-register-btn');
            
            if (!this.validateRegistrationForm(form)) {
                return;
            }

            submitButton.prop('disabled', true).html('<span class="ep-loading"></span> Registering...');

            const formData = {
                action: 'ep_register_for_event',
                nonce: this.config.nonce,
                event_id: eventId,
                participant_name: form.find('[name="participant_name"]').val(),
                participant_email: form.find('[name="participant_email"]').val(),
                participant_phone: form.find('[name="participant_phone"]').val(),
                dietary_requirements: form.find('[name="dietary_requirements"]').val(),
                special_needs: form.find('[name="special_needs"]').val()
            };

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.showSuccess('Registration successful! Check your email for confirmation.');
                        this.updateRegistrationUI(eventId, response.data);
                        form[0].reset();
                    } else {
                        this.showError('Registration failed: ' + response.data.message);
                    }
                },
                error: () => {
                    this.showError('Registration failed. Please try again.');
                },
                complete: () => {
                    submitButton.prop('disabled', false).html('Register for Event');
                }
            });
        },

        // Handle registration cancellation
        handleCancellation: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to cancel your registration?')) {
                return;
            }

            const button = $(e.currentTarget);
            const eventId = button.data('event-id');
            const registrationId = button.data('registration-id');

            button.prop('disabled', true).html('<span class="ep-loading"></span> Cancelling...');

            const data = {
                action: 'ep_cancel_registration',
                nonce: this.config.nonce,
                event_id: eventId,
                registration_id: registrationId
            };

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.showSuccess('Registration cancelled successfully.');
                        this.updateCancellationUI(eventId);
                    } else {
                        this.showError('Cancellation failed: ' + response.data.message);
                    }
                },
                error: () => {
                    this.showError('Cancellation failed. Please try again.');
                },
                complete: () => {
                    button.prop('disabled', false).html('Cancel Registration');
                }
            });
        },

        // Validate registration form
        validateRegistrationForm: function(form) {
            let isValid = true;
            const requiredFields = ['participant_name', 'participant_email'];

            requiredFields.forEach(field => {
                const input = form.find(`[name="${field}"]`);
                const value = input.val().trim();
                
                if (!value) {
                    this.showFieldError(input, 'This field is required');
                    isValid = false;
                } else {
                    this.clearFieldError(input);
                }
            });

            // Email validation
            const emailField = form.find('[name="participant_email"]');
            const email = emailField.val().trim();
            if (email && !this.isValidEmail(email)) {
                this.showFieldError(emailField, 'Please enter a valid email address');
                isValid = false;
            }

            return isValid;
        },

        // Show field error
        showFieldError: function(input, message) {
            input.addClass('error');
            let errorEl = input.siblings('.ep-field-error');
            if (errorEl.length === 0) {
                errorEl = $(`<div class="ep-field-error">${message}</div>`);
                input.after(errorEl);
            } else {
                errorEl.text(message);
            }
        },

        // Clear field error
        clearFieldError: function(input) {
            input.removeClass('error');
            input.siblings('.ep-field-error').remove();
        },

        // Initialize filters
        initFilters: function() {
            this.loadFilterOptions();
        },

        // Handle filter change
        handleFilterChange: function(e) {
            this.applyFilters();
        },

        // Apply filters
        applyFilters: function() {
            const filters = this.getActiveFilters();
            this.loadFilteredEvents(filters);
        },

        // Get active filters
        getActiveFilters: function() {
            const filters = {};
            
            $('.ep-events-filters select').each(function() {
                const name = $(this).attr('name');
                const value = $(this).val();
                if (value) {
                    filters[name] = value;
                }
            });

            return filters;
        },

        // Load filtered events
        loadFilteredEvents: function(filters) {
            if (this.config.isLoading) return;
            
            this.config.isLoading = true;
            this.showLoading();

            const data = {
                action: 'ep_filter_events',
                nonce: this.config.nonce,
                filters: filters
            };

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.renderFilteredEvents(response.data.events);
                    } else {
                        this.showError('Failed to load filtered events: ' + response.data.message);
                    }
                },
                error: () => {
                    this.showError('Failed to load events. Please try again.');
                },
                complete: () => {
                    this.config.isLoading = false;
                    this.hideLoading();
                }
            });
        },

        // Show event popup
        showEventPopup: function(e) {
            e.preventDefault();
            const eventItem = $(e.currentTarget);
            const eventId = eventItem.data('event-id');
            
            this.loadEventDetails(eventId);
        },

        // Load event details
        loadEventDetails: function(eventId) {
            const data = {
                action: 'ep_get_event_details',
                nonce: this.config.nonce,
                event_id: eventId
            };

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.displayEventPopup(response.data.event);
                    } else {
                        this.showError('Failed to load event details');
                    }
                },
                error: () => {
                    this.showError('Failed to load event details');
                }
            });
        },

        // Display event popup
        displayEventPopup: function(event) {
            const popupHtml = `
                <div class="ep-event-popup-overlay">
                    <div class="ep-event-popup">
                        <div class="ep-popup-header">
                            <h3>${event.title}</h3>
                            <button class="ep-close-popup">&times;</button>
                        </div>
                        <div class="ep-popup-content">
                            <div class="ep-popup-meta">
                                <p><strong>Date:</strong> ${event.date}</p>
                                <p><strong>Time:</strong> ${event.time}</p>
                                <p><strong>Location:</strong> ${event.location}</p>
                                <p><strong>Capacity:</strong> ${event.registered}/${event.capacity}</p>
                            </div>
                            <div class="ep-popup-description">
                                ${event.description}
                            </div>
                            <div class="ep-popup-actions">
                                <a href="${event.url}" class="ep-view-event-btn">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(popupHtml);
            $('.ep-event-popup-overlay').fadeIn(300);
        },

        // Close event popup
        closeEventPopup: function(e) {
            e.preventDefault();
            $('.ep-event-popup-overlay').fadeOut(300, function() {
                $(this).remove();
            });
        },

        // Generate QR code
        generateQRCode: function(e) {
            e.preventDefault();
            
            const button = $(e.currentTarget);
            const eventId = button.data('event-id');
            const container = button.siblings('.ep-qr-container');

            if (container.find('canvas').length > 0) {
                container.toggle();
                return;
            }

            const qrData = `${window.location.origin}/events/checkin/${eventId}`;
            
            // Generate QR code using a library (you'll need to include QR.js)
            if (typeof QRCode !== 'undefined') {
                const qr = new QRCode(container[0], {
                    text: qrData,
                    width: 200,
                    height: 200,
                    colorDark: "#000000",
                    colorLight: "#ffffff"
                });
                container.show();
            } else {
                this.showError('QR code library not loaded');
            }
        },

        // Load Google Maps
        loadGoogleMaps: function() {
            if ($('.ep-event-map').length === 0) return;
            
            $('.ep-event-map').each(function() {
                const mapEl = $(this);
                const lat = parseFloat(mapEl.data('lat'));
                const lng = parseFloat(mapEl.data('lng'));
                const title = mapEl.data('title');

                if (lat && lng && typeof google !== 'undefined') {
                    const map = new google.maps.Map(mapEl[0], {
                        zoom: 15,
                        center: { lat: lat, lng: lng }
                    });

                    new google.maps.Marker({
                        position: { lat: lat, lng: lng },
                        map: map,
                        title: title
                    });
                }
            });
        },

        // Utility functions
        formatDate: function(date) {
            return date.getFullYear() + '-' + 
                   String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                   String(date.getDate()).padStart(2, '0');
        },

        isToday: function(date) {
            const today = new Date();
            return date.toDateString() === today.toDateString();
        },

        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        getCacheKey: function() {
            return `${this.config.currentDate.getFullYear()}-${this.config.currentDate.getMonth()}`;
        },

        updateCalendarTitle: function() {
            const monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            const title = `${monthNames[this.config.currentDate.getMonth()]} ${this.config.currentDate.getFullYear()}`;
            $('.ep-calendar-title').text(title);
        },

        showLoading: function() {
            if ($('.ep-loading-overlay').length === 0) {
                $('body').append('<div class="ep-loading-overlay"><div class="ep-loading"></div></div>');
            }
        },

        hideLoading: function() {
            $('.ep-loading-overlay').remove();
        },

        showSuccess: function(message) {
            this.showMessage(message, 'success');
        },

        showError: function(message) {
            this.showMessage(message, 'error');
        },

        showInfo: function(message) {
            this.showMessage(message, 'info');
        },

        showMessage: function(message, type) {
            const messageHtml = `<div class="ep-${type}-message ep-fade-in">${message}</div>`;
            $('.ep-messages').html(messageHtml);
            
            setTimeout(() => {
                $('.ep-messages').empty();
            }, 5000);
        },

        updateRegistrationUI: function(eventId, data) {
            // Update registration count
            $(`.ep-registration-count[data-event-id="${eventId}"]`).text(data.registered_count);
            
            // Update registration status
            if (data.is_full) {
                $('.ep-registration-form').hide();
                $('.ep-registration-full').show();
            }
        },

        updateCancellationUI: function(eventId) {
            $('.ep-registration-status').hide();
            $('.ep-registration-form').show();
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        EP_Events.init();
    });

    // Handle window resize for responsive calendar
    $(window).resize(function() {
        clearTimeout(this.resizeTimer);
        this.resizeTimer = setTimeout(function() {
            if (EP_Events.config.currentView === 'month') {
                EP_Events.renderCalendar();
            }
        }, 250);
    });

})(jQuery);
