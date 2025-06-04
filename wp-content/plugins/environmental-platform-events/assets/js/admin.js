/**
 * Environmental Platform Events - Admin JavaScript
 * Phase 34: Event Management System
 */

(function($) {
    'use strict';

    // Wait for document ready
    $(document).ready(function() {
        EPEventsAdmin.init();
    });

    // Main admin object
    var EPEventsAdmin = {
        
        // Initialize admin functionality
        init: function() {
            this.initDateTimePickers();
            this.initLocationToggles();
            this.initRegistrationToggles();
            this.initPricingToggles();
            this.initMapFunctionality();
            this.initAnalyticsCharts();
            this.initRegistrationManagement();
            this.initQRCodeGeneration();
            this.initFormValidation();
            this.initBulkActions();
        },

        // Initialize date and time pickers
        initDateTimePickers: function() {
            // Date pickers
            $('.ep-events-date-field').each(function() {
                $(this).datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0, // Prevent past dates
                    changeMonth: true,
                    changeYear: true
                });
            });

            // Time pickers
            $('.ep-events-time-field').each(function() {
                $(this).timepicker({
                    timeFormat: 'HH:mm',
                    interval: 15,
                    minTime: '00:00',
                    maxTime: '23:59',
                    defaultTime: '09:00',
                    startTime: '00:00',
                    dynamic: false,
                    dropdown: true,
                    scrollbar: true
                });
            });

            // Auto-set end time when start time changes
            $('#ep_event_start_time').on('change', function() {
                var startTime = $(this).val();
                var endTimeField = $('#ep_event_end_time');
                
                if (startTime && !endTimeField.val()) {
                    var startDate = new Date('1970-01-01 ' + startTime);
                    startDate.setHours(startDate.getHours() + 2); // Default 2-hour duration
                    var endTime = startDate.toTimeString().slice(0, 5);
                    endTimeField.val(endTime);
                }
            });
        },

        // Initialize location toggles
        initLocationToggles: function() {
            var self = this;
            
            $('.ep-events-location-toggle').on('click', function(e) {
                e.preventDefault();
                var target = $(this).data('target');
                var container = $(target);
                
                if (container.is(':visible')) {
                    container.slideUp();
                    $(this).text('Show Location Details');
                } else {
                    container.slideDown();
                    $(this).text('Hide Location Details');
                    self.initMap();
                }
            });

            // Toggle online/offline event settings
            $('#ep_event_is_online').on('change', function() {
                var isOnline = $(this).is(':checked');
                var physicalLocation = $('.ep-events-physical-location');
                var onlineLocation = $('.ep-events-online-location');
                
                if (isOnline) {
                    physicalLocation.hide();
                    onlineLocation.show();
                } else {
                    physicalLocation.show();
                    onlineLocation.hide();
                }
            });
        },

        // Initialize registration toggles
        initRegistrationToggles: function() {
            $('#ep_event_registration_required').on('change', function() {
                var isRequired = $(this).is(':checked');
                var registrationSettings = $('.ep-events-registration-settings');
                
                if (isRequired) {
                    registrationSettings.slideDown();
                } else {
                    registrationSettings.slideUp();
                }
            });

            // Toggle capacity settings
            $('#ep_event_has_capacity_limit').on('change', function() {
                var hasLimit = $(this).is(':checked');
                var capacityFields = $('.ep-events-capacity-fields');
                
                if (hasLimit) {
                    capacityFields.slideDown();
                } else {
                    capacityFields.slideUp();
                }
            });
        },

        // Initialize pricing toggles
        initPricingToggles: function() {
            $('#ep_event_is_paid').on('change', function() {
                var isPaid = $(this).is(':checked');
                var pricingGroup = $('.ep-events-pricing-group');
                
                if (isPaid) {
                    pricingGroup.slideDown();
                } else {
                    pricingGroup.slideUp();
                }
            });
        },

        // Initialize map functionality
        initMapFunctionality: function() {
            var self = this;
            
            // Address lookup
            $('#ep_event_address').on('blur', function() {
                var address = $(this).val();
                if (address) {
                    self.geocodeAddress(address);
                }
            });
        },

        // Geocode address to get coordinates
        geocodeAddress: function(address) {
            if (typeof google !== 'undefined' && google.maps) {
                var geocoder = new google.maps.Geocoder();
                
                geocoder.geocode({'address': address}, function(results, status) {
                    if (status === 'OK') {
                        var location = results[0].geometry.location;
                        $('#ep_event_latitude').val(location.lat());
                        $('#ep_event_longitude').val(location.lng());
                        
                        // Update map if visible
                        EPEventsAdmin.updateMap(location.lat(), location.lng());
                    }
                });
            }
        },

        // Initialize or update map
        initMap: function() {
            var mapContainer = $('#ep-events-map');
            if (mapContainer.length && typeof google !== 'undefined' && google.maps) {
                var lat = parseFloat($('#ep_event_latitude').val()) || 0;
                var lng = parseFloat($('#ep_event_longitude').val()) || 0;
                
                var map = new google.maps.Map(mapContainer[0], {
                    zoom: lat && lng ? 15 : 2,
                    center: {lat: lat, lng: lng}
                });

                if (lat && lng) {
                    var marker = new google.maps.Marker({
                        position: {lat: lat, lng: lng},
                        map: map,
                        draggable: true
                    });

                    marker.addListener('dragend', function(event) {
                        $('#ep_event_latitude').val(event.latLng.lat());
                        $('#ep_event_longitude').val(event.latLng.lng());
                    });
                }

                this.map = map;
            }
        },

        // Update map with new coordinates
        updateMap: function(lat, lng) {
            if (this.map) {
                var location = new google.maps.LatLng(lat, lng);
                this.map.setCenter(location);
                this.map.setZoom(15);
                
                if (this.marker) {
                    this.marker.setPosition(location);
                } else {
                    this.marker = new google.maps.Marker({
                        position: location,
                        map: this.map,
                        draggable: true
                    });
                }
            }
        },

        // Initialize analytics charts
        initAnalyticsCharts: function() {
            if (typeof Chart !== 'undefined') {
                this.initRegistrationChart();
                this.initCategoryChart();
                this.initMonthlyChart();
            }
        },

        // Registration analytics chart
        initRegistrationChart: function() {
            var canvas = $('#ep-events-registration-chart');
            if (canvas.length) {
                var ctx = canvas[0].getContext('2d');
                var data = canvas.data('chart-data') || {
                    labels: ['Confirmed', 'Pending', 'Cancelled'],
                    datasets: [{
                        data: [0, 0, 0],
                        backgroundColor: ['#27ae60', '#f39c12', '#e74c3c']
                    }]
                };

                new Chart(ctx, {
                    type: 'doughnut',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        },

        // Category analytics chart
        initCategoryChart: function() {
            var canvas = $('#ep-events-category-chart');
            if (canvas.length) {
                var ctx = canvas[0].getContext('2d');
                var data = canvas.data('chart-data') || {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#3498db', '#9b59b6', '#1abc9c', '#f1c40f', '#e67e22']
                    }]
                };

                new Chart(ctx, {
                    type: 'pie',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        },

        // Monthly events chart
        initMonthlyChart: function() {
            var canvas = $('#ep-events-monthly-chart');
            if (canvas.length) {
                var ctx = canvas[0].getContext('2d');
                var data = canvas.data('chart-data') || {
                    labels: [],
                    datasets: [{
                        label: 'Events Created',
                        data: [],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4
                    }]
                };

                new Chart(ctx, {
                    type: 'line',
                    data: data,
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
        },

        // Initialize registration management
        initRegistrationManagement: function() {
            var self = this;

            // Approve registration
            $(document).on('click', '.ep-events-approve-registration', function(e) {
                e.preventDefault();
                var registrationId = $(this).data('registration-id');
                self.updateRegistrationStatus(registrationId, 'confirmed');
            });

            // Cancel registration
            $(document).on('click', '.ep-events-cancel-registration', function(e) {
                e.preventDefault();
                var registrationId = $(this).data('registration-id');
                if (confirm('Are you sure you want to cancel this registration?')) {
                    self.updateRegistrationStatus(registrationId, 'cancelled');
                }
            });

            // Send confirmation email
            $(document).on('click', '.ep-events-send-email', function(e) {
                e.preventDefault();
                var registrationId = $(this).data('registration-id');
                self.sendConfirmationEmail(registrationId);
            });
        },

        // Update registration status
        updateRegistrationStatus: function(registrationId, status) {
            var data = {
                action: 'ep_events_update_registration_status',
                registration_id: registrationId,
                status: status,
                nonce: ep_events_admin.nonce
            };

            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error updating registration status: ' + response.data);
                }
            });
        },

        // Send confirmation email
        sendConfirmationEmail: function(registrationId) {
            var data = {
                action: 'ep_events_send_confirmation_email',
                registration_id: registrationId,
                nonce: ep_events_admin.nonce
            };

            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    alert('Confirmation email sent successfully!');
                } else {
                    alert('Error sending email: ' + response.data);
                }
            });
        },

        // Initialize QR code generation
        initQRCodeGeneration: function() {
            $('.ep-events-generate-qr').on('click', function(e) {
                e.preventDefault();
                var eventId = $(this).data('event-id');
                EPEventsAdmin.generateQRCode(eventId);
            });
        },

        // Generate QR code for event
        generateQRCode: function(eventId) {
            var data = {
                action: 'ep_events_generate_qr_code',
                event_id: eventId,
                nonce: ep_events_admin.nonce
            };

            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    $('#ep-events-qr-display').html(response.data);
                } else {
                    alert('Error generating QR code: ' + response.data);
                }
            });
        },

        // Initialize form validation
        initFormValidation: function() {
            $('#post').on('submit', function(e) {
                var errors = [];
                
                // Validate event title
                var title = $('#title').val().trim();
                if (!title) {
                    errors.push('Event title is required.');
                }

                // Validate dates
                var startDate = $('#ep_event_start_date').val();
                var endDate = $('#ep_event_end_date').val();
                
                if (!startDate) {
                    errors.push('Start date is required.');
                }
                
                if (endDate && startDate && new Date(endDate) < new Date(startDate)) {
                    errors.push('End date cannot be before start date.');
                }

                // Validate capacity if enabled
                if ($('#ep_event_has_capacity_limit').is(':checked')) {
                    var capacity = parseInt($('#ep_event_capacity').val());
                    if (!capacity || capacity <= 0) {
                        errors.push('Valid capacity number is required when capacity limit is enabled.');
                    }
                }

                // Validate price if paid event
                if ($('#ep_event_is_paid').is(':checked')) {
                    var price = parseFloat($('#ep_event_price').val());
                    if (isNaN(price) || price < 0) {
                        errors.push('Valid price is required for paid events.');
                    }
                }

                if (errors.length > 0) {
                    e.preventDefault();
                    alert('Please fix the following errors:\n\n' + errors.join('\n'));
                    return false;
                }
            });
        },

        // Initialize bulk actions
        initBulkActions: function() {
            $('#doaction, #doaction2').on('click', function(e) {
                var action = $(this).siblings('select').val();
                if (action === 'delete') {
                    if (!confirm('Are you sure you want to delete the selected items?')) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        }
    };

    // Make EPEventsAdmin globally available
    window.EPEventsAdmin = EPEventsAdmin;

})(jQuery);

// Additional utility functions
function epEventsFormatDate(date) {
    if (!date) return '';
    var d = new Date(date);
    return d.toLocaleDateString();
}

function epEventsFormatTime(time) {
    if (!time) return '';
    var parts = time.split(':');
    var hours = parseInt(parts[0]);
    var minutes = parts[1];
    var ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    return hours + ':' + minutes + ' ' + ampm;
}

function epEventsFormatDateTime(date, time) {
    return epEventsFormatDate(date) + ' at ' + epEventsFormatTime(time);
}
