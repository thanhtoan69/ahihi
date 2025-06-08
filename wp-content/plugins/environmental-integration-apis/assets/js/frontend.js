/**
 * Environmental Integration APIs - Frontend JavaScript
 * Handles all frontend widget functionality, interactive maps, weather displays, and user interactions
 */

(function($) {
    'use strict';

    // Global frontend object
    window.EIA_Frontend = {
        initialized: false,
        maps: {},
        weatherWidgets: {},
        airQualityWidgets: {},
        socialWidgets: {},
        refreshIntervals: {},
        
        // Initialize frontend functionality
        init: function() {
            if (this.initialized) return;
            
            console.log('Initializing Environmental Integration APIs Frontend');
            
            this.initializeMaps();
            this.initializeWeatherWidgets();
            this.initializeAirQualityWidgets();
            this.initializeSocialWidgets();
            this.initializeLocationPickers();
            this.bindEvents();
            
            this.initialized = true;
        },
        
        // Initialize Google Maps
        initializeMaps: function() {
            var self = this;
            
            $('.env-google-map').each(function() {
                var mapContainer = $(this);
                var mapId = mapContainer.attr('id');
                var config = mapContainer.data('config') || {};
                
                self.createMap(mapId, config);
            });
        },
        
        createMap: function(mapId, config) {
            var self = this;
            var mapContainer = $('#' + mapId);
            
            if (!mapContainer.length || !window.google || !window.google.maps) {
                console.warn('Google Maps not available for map:', mapId);
                return;
            }
            
            var defaultConfig = {
                center: { lat: 21.0285, lng: 105.8542 }, // Hanoi, Vietnam
                zoom: 10,
                mapTypeId: 'roadmap',
                markers: [],
                infoWindows: true,
                clustering: false
            };
            
            config = $.extend(defaultConfig, config);
            
            // Create map
            var map = new google.maps.Map(mapContainer[0], {
                center: config.center,
                zoom: config.zoom,
                mapTypeId: google.maps.MapTypeId[config.mapTypeId.toUpperCase()],
                styles: config.styles || []
            });
            
            // Add markers
            var markers = [];
            var infoWindows = [];
            
            if (config.markers && config.markers.length > 0) {
                $.each(config.markers, function(index, markerData) {
                    var marker = new google.maps.Marker({
                        position: { lat: markerData.lat, lng: markerData.lng },
                        map: map,
                        title: markerData.title || '',
                        icon: markerData.icon || null
                    });
                    
                    markers.push(marker);
                    
                    // Add info window if content provided
                    if (config.infoWindows && markerData.content) {
                        var infoWindow = new google.maps.InfoWindow({
                            content: markerData.content
                        });
                        
                        marker.addListener('click', function() {
                            // Close all other info windows
                            $.each(infoWindows, function(i, iw) {
                                iw.close();
                            });
                            infoWindow.open(map, marker);
                        });
                        
                        infoWindows.push(infoWindow);
                    }
                });
                
                // Auto-fit bounds if multiple markers
                if (markers.length > 1) {
                    var bounds = new google.maps.LatLngBounds();
                    $.each(markers, function(index, marker) {
                        bounds.extend(marker.getPosition());
                    });
                    map.fitBounds(bounds);
                }
            }
            
            // Marker clustering
            if (config.clustering && markers.length > 0 && window.MarkerClusterer) {
                new MarkerClusterer(map, markers);
            }
            
            // Store map reference
            this.maps[mapId] = {
                map: map,
                markers: markers,
                infoWindows: infoWindows,
                config: config
            };
            
            // Trigger map loaded event
            mapContainer.trigger('mapLoaded', [map]);
        },
        
        // Initialize location pickers
        initializeLocationPickers: function() {
            var self = this;
            
            $('.env-location-picker').each(function() {
                var pickerId = $(this).attr('id');
                var config = $(this).data('config') || {};
                
                self.createLocationPicker(pickerId, config);
            });
        },
        
        createLocationPicker: function(pickerId, config) {
            var self = this;
            var pickerContainer = $('#' + pickerId);
            
            if (!pickerContainer.length || !window.google || !window.google.maps) {
                console.warn('Google Maps not available for location picker:', pickerId);
                return;
            }
            
            var mapDiv = pickerContainer.find('.location-picker-map');
            var searchInput = pickerContainer.find('.location-search');
            var coordsDisplay = pickerContainer.find('.coordinates-display');
            var hiddenLat = pickerContainer.find('input[name$="[lat]"]');
            var hiddenLng = pickerContainer.find('input[name$="[lng]"]');
            
            var defaultConfig = {
                center: { lat: 21.0285, lng: 105.8542 },
                zoom: 10,
                draggableMarker: true,
                showCoordinates: true
            };
            
            config = $.extend(defaultConfig, config);
            
            // Create map
            var map = new google.maps.Map(mapDiv[0], {
                center: config.center,
                zoom: config.zoom,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            });
            
            // Create draggable marker
            var marker = new google.maps.Marker({
                position: config.center,
                map: map,
                draggable: config.draggableMarker,
                title: 'Drag to select location'
            });
            
            // Update coordinates display and hidden inputs
            function updateLocation(position) {
                var lat = position.lat();
                var lng = position.lng();
                
                if (config.showCoordinates && coordsDisplay.length) {
                    coordsDisplay.text('Lat: ' + lat.toFixed(6) + ', Lng: ' + lng.toFixed(6));
                }
                
                if (hiddenLat.length) hiddenLat.val(lat);
                if (hiddenLng.length) hiddenLng.val(lng);
                
                // Trigger location change event
                pickerContainer.trigger('locationChanged', [lat, lng]);
            }
            
            // Handle marker drag
            if (config.draggableMarker) {
                marker.addListener('dragend', function() {
                    updateLocation(marker.getPosition());
                });
            }
            
            // Handle map click
            map.addListener('click', function(event) {
                marker.setPosition(event.latLng);
                updateLocation(event.latLng);
            });
            
            // Setup search functionality
            if (searchInput.length) {
                var autocomplete = new google.maps.places.Autocomplete(searchInput[0]);
                autocomplete.bindTo('bounds', map);
                
                autocomplete.addListener('place_changed', function() {
                    var place = autocomplete.getPlace();
                    
                    if (!place.geometry) {
                        console.warn('No geometry found for place:', place.name);
                        return;
                    }
                    
                    // Update map and marker
                    if (place.geometry.viewport) {
                        map.fitBounds(place.geometry.viewport);
                    } else {
                        map.setCenter(place.geometry.location);
                        map.setZoom(17);
                    }
                    
                    marker.setPosition(place.geometry.location);
                    updateLocation(place.geometry.location);
                });
            }
            
            // Initialize with existing values
            if (hiddenLat.val() && hiddenLng.val()) {
                var existingPos = {
                    lat: parseFloat(hiddenLat.val()),
                    lng: parseFloat(hiddenLng.val())
                };
                marker.setPosition(existingPos);
                map.setCenter(existingPos);
                updateLocation(marker.getPosition());
            } else {
                updateLocation(marker.getPosition());
            }
        },
        
        // Initialize weather widgets
        initializeWeatherWidgets: function() {
            var self = this;
            
            $('.env-weather-widget').each(function() {
                var widget = $(this);
                var widgetId = widget.attr('id') || 'weather-' + Math.random().toString(36).substr(2, 9);
                var config = widget.data('config') || {};
                
                widget.attr('id', widgetId);
                self.createWeatherWidget(widgetId, config);
            });
        },
        
        createWeatherWidget: function(widgetId, config) {
            var self = this;
            var widget = $('#' + widgetId);
            
            var defaultConfig = {
                location: '',
                showForecast: false,
                units: 'metric',
                refreshInterval: 300000, // 5 minutes
                showAlerts: true
            };
            
            config = $.extend(defaultConfig, config);
            
            // Store widget reference
            this.weatherWidgets[widgetId] = {
                element: widget,
                config: config,
                data: null
            };
            
            // Load initial data
            this.loadWeatherData(widgetId);
            
            // Setup auto-refresh
            if (config.refreshInterval > 0) {
                this.refreshIntervals[widgetId] = setInterval(function() {
                    self.loadWeatherData(widgetId);
                }, config.refreshInterval);
            }
            
            // Bind refresh button
            widget.find('.weather-refresh').on('click', function() {
                self.loadWeatherData(widgetId);
            });
        },
        
        loadWeatherData: function(widgetId) {
            var self = this;
            var widgetData = this.weatherWidgets[widgetId];
            
            if (!widgetData) return;
            
            var widget = widgetData.element;
            var config = widgetData.config;
            
            // Show loading state
            widget.addClass('loading');
            
            $.ajax({
                url: eia_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'eia_get_weather_data',
                    location: config.location,
                    show_forecast: config.showForecast,
                    units: config.units,
                    nonce: eia_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateWeatherWidget(widgetId, response.data);
                    } else {
                        self.showWidgetError(widget, 'Failed to load weather data: ' + response.data);
                    }
                },
                error: function() {
                    self.showWidgetError(widget, 'Failed to load weather data');
                },
                complete: function() {
                    widget.removeClass('loading');
                }
            });
        },
        
        updateWeatherWidget: function(widgetId, data) {
            var widgetData = this.weatherWidgets[widgetId];
            if (!widgetData) return;
            
            var widget = widgetData.element;
            widgetData.data = data;
            
            // Update current weather
            if (data.current) {
                var current = data.current;
                widget.find('.current-temp').text(Math.round(current.temperature) + '°');
                widget.find('.current-condition').text(current.condition);
                widget.find('.current-humidity').text(current.humidity + '%');
                widget.find('.current-wind').text(current.wind_speed + ' km/h');
                widget.find('.weather-icon').attr('class', 'weather-icon icon-' + current.icon);
            }
            
            // Update forecast
            if (data.forecast && widgetData.config.showForecast) {
                var forecastContainer = widget.find('.weather-forecast');
                forecastContainer.empty();
                
                $.each(data.forecast, function(index, day) {
                    var dayDiv = $('<div class="forecast-day">');
                    dayDiv.append('<div class="forecast-date">' + day.date + '</div>');
                    dayDiv.append('<div class="forecast-icon icon-' + day.icon + '"></div>');
                    dayDiv.append('<div class="forecast-temps">' + 
                                 Math.round(day.temp_max) + '° / ' + Math.round(day.temp_min) + '°</div>');
                    dayDiv.append('<div class="forecast-condition">' + day.condition + '</div>');
                    
                    forecastContainer.append(dayDiv);
                });
            }
            
            // Update alerts
            if (data.alerts && widgetData.config.showAlerts) {
                var alertsContainer = widget.find('.weather-alerts');
                alertsContainer.empty();
                
                if (data.alerts.length > 0) {
                    $.each(data.alerts, function(index, alert) {
                        var alertDiv = $('<div class="weather-alert alert-' + alert.severity + '">');
                        alertDiv.append('<div class="alert-title">' + alert.title + '</div>');
                        alertDiv.append('<div class="alert-description">' + alert.description + '</div>');
                        alertsContainer.append(alertDiv);
                    });
                    alertsContainer.show();
                } else {
                    alertsContainer.hide();
                }
            }
            
            // Update last updated time
            widget.find('.last-updated').text('Updated: ' + new Date().toLocaleTimeString());
            
            // Trigger widget updated event
            widget.trigger('weatherUpdated', [data]);
        },
        
        // Initialize air quality widgets
        initializeAirQualityWidgets: function() {
            var self = this;
            
            $('.env-air-quality-widget').each(function() {
                var widget = $(this);
                var widgetId = widget.attr('id') || 'airquality-' + Math.random().toString(36).substr(2, 9);
                var config = widget.data('config') || {};
                
                widget.attr('id', widgetId);
                self.createAirQualityWidget(widgetId, config);
            });
        },
        
        createAirQualityWidget: function(widgetId, config) {
            var self = this;
            var widget = $('#' + widgetId);
            
            var defaultConfig = {
                location: '',
                showForecast: false,
                refreshInterval: 600000, // 10 minutes
                showPollutants: true
            };
            
            config = $.extend(defaultConfig, config);
            
            // Store widget reference
            this.airQualityWidgets[widgetId] = {
                element: widget,
                config: config,
                data: null
            };
            
            // Load initial data
            this.loadAirQualityData(widgetId);
            
            // Setup auto-refresh
            if (config.refreshInterval > 0) {
                this.refreshIntervals[widgetId] = setInterval(function() {
                    self.loadAirQualityData(widgetId);
                }, config.refreshInterval);
            }
            
            // Bind refresh button
            widget.find('.airquality-refresh').on('click', function() {
                self.loadAirQualityData(widgetId);
            });
        },
        
        loadAirQualityData: function(widgetId) {
            var self = this;
            var widgetData = this.airQualityWidgets[widgetId];
            
            if (!widgetData) return;
            
            var widget = widgetData.element;
            var config = widgetData.config;
            
            // Show loading state
            widget.addClass('loading');
            
            $.ajax({
                url: eia_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'eia_get_air_quality_data',
                    location: config.location,
                    show_forecast: config.showForecast,
                    nonce: eia_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateAirQualityWidget(widgetId, response.data);
                    } else {
                        self.showWidgetError(widget, 'Failed to load air quality data: ' + response.data);
                    }
                },
                error: function() {
                    self.showWidgetError(widget, 'Failed to load air quality data');
                },
                complete: function() {
                    widget.removeClass('loading');
                }
            });
        },
        
        updateAirQualityWidget: function(widgetId, data) {
            var widgetData = this.airQualityWidgets[widgetId];
            if (!widgetData) return;
            
            var widget = widgetData.element;
            widgetData.data = data;
            
            // Update current AQI
            if (data.current) {
                var current = data.current;
                widget.find('.current-aqi').text(current.aqi);
                widget.find('.aqi-category').text(current.category)
                      .attr('class', 'aqi-category category-' + current.category.toLowerCase().replace(/\s+/g, '-'));
                widget.find('.aqi-description').text(current.description);
                
                // Update AQI color indicator
                widget.find('.aqi-indicator').attr('class', 'aqi-indicator level-' + current.level);
            }
            
            // Update pollutants
            if (data.pollutants && widgetData.config.showPollutants) {
                var pollutantsContainer = widget.find('.pollutants-list');
                pollutantsContainer.empty();
                
                $.each(data.pollutants, function(name, pollutant) {
                    var pollutantDiv = $('<div class="pollutant-item">');
                    pollutantDiv.append('<span class="pollutant-name">' + name + '</span>');
                    pollutantDiv.append('<span class="pollutant-value">' + pollutant.value + ' ' + pollutant.unit + '</span>');
                    pollutantDiv.append('<span class="pollutant-level level-' + pollutant.level + '">' + pollutant.level + '</span>');
                    
                    pollutantsContainer.append(pollutantDiv);
                });
            }
            
            // Update forecast
            if (data.forecast && widgetData.config.showForecast) {
                var forecastContainer = widget.find('.airquality-forecast');
                forecastContainer.empty();
                
                $.each(data.forecast, function(index, day) {
                    var dayDiv = $('<div class="forecast-day">');
                    dayDiv.append('<div class="forecast-date">' + day.date + '</div>');
                    dayDiv.append('<div class="forecast-aqi">' + day.aqi + '</div>');
                    dayDiv.append('<div class="forecast-category category-' + 
                                 day.category.toLowerCase().replace(/\s+/g, '-') + '">' + day.category + '</div>');
                    
                    forecastContainer.append(dayDiv);
                });
            }
            
            // Update last updated time
            widget.find('.last-updated').text('Updated: ' + new Date().toLocaleTimeString());
            
            // Trigger widget updated event
            widget.trigger('airQualityUpdated', [data]);
        },
        
        // Initialize social media widgets
        initializeSocialWidgets: function() {
            var self = this;
            
            $('.env-social-widget').each(function() {
                var widget = $(this);
                var widgetId = widget.attr('id') || 'social-' + Math.random().toString(36).substr(2, 9);
                var config = widget.data('config') || {};
                
                widget.attr('id', widgetId);
                self.createSocialWidget(widgetId, config);
            });
            
            // Initialize social sharing buttons
            $('.env-social-share').each(function() {
                self.initializeSocialShare($(this));
            });
        },
        
        createSocialWidget: function(widgetId, config) {
            var self = this;
            var widget = $('#' + widgetId);
            
            var defaultConfig = {
                platform: 'facebook',
                count: 5,
                refreshInterval: 900000, // 15 minutes
                showImages: true,
                showEngagement: true
            };
            
            config = $.extend(defaultConfig, config);
            
            // Store widget reference
            this.socialWidgets[widgetId] = {
                element: widget,
                config: config,
                data: null
            };
            
            // Load initial data
            this.loadSocialData(widgetId);
            
            // Setup auto-refresh
            if (config.refreshInterval > 0) {
                this.refreshIntervals[widgetId] = setInterval(function() {
                    self.loadSocialData(widgetId);
                }, config.refreshInterval);
            }
            
            // Bind refresh button
            widget.find('.social-refresh').on('click', function() {
                self.loadSocialData(widgetId);
            });
        },
        
        loadSocialData: function(widgetId) {
            var self = this;
            var widgetData = this.socialWidgets[widgetId];
            
            if (!widgetData) return;
            
            var widget = widgetData.element;
            var config = widgetData.config;
            
            // Show loading state
            widget.addClass('loading');
            
            $.ajax({
                url: eia_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'eia_get_social_feed',
                    platform: config.platform,
                    count: config.count,
                    nonce: eia_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateSocialWidget(widgetId, response.data);
                    } else {
                        self.showWidgetError(widget, 'Failed to load social media data: ' + response.data);
                    }
                },
                error: function() {
                    self.showWidgetError(widget, 'Failed to load social media data');
                },
                complete: function() {
                    widget.removeClass('loading');
                }
            });
        },
        
        updateSocialWidget: function(widgetId, data) {
            var widgetData = this.socialWidgets[widgetId];
            if (!widgetData) return;
            
            var widget = widgetData.element;
            var config = widgetData.config;
            widgetData.data = data;
            
            var feedContainer = widget.find('.social-feed');
            feedContainer.empty();
            
            if (!data.posts || data.posts.length === 0) {
                feedContainer.append('<div class="no-posts">No posts available</div>');
                return;
            }
            
            $.each(data.posts, function(index, post) {
                var postDiv = $('<div class="social-post">');
                
                // Post header
                var header = $('<div class="post-header">');
                header.append('<span class="post-author">' + post.author + '</span>');
                header.append('<span class="post-date">' + post.date + '</span>');
                postDiv.append(header);
                
                // Post content
                if (post.content) {
                    postDiv.append('<div class="post-content">' + post.content + '</div>');
                }
                
                // Post image
                if (config.showImages && post.image) {
                    postDiv.append('<div class="post-image"><img src="' + post.image + '" alt="Post image"></div>');
                }
                
                // Engagement metrics
                if (config.showEngagement && post.engagement) {
                    var engagement = $('<div class="post-engagement">');
                    if (post.engagement.likes) {
                        engagement.append('<span class="likes">' + post.engagement.likes + ' likes</span>');
                    }
                    if (post.engagement.comments) {
                        engagement.append('<span class="comments">' + post.engagement.comments + ' comments</span>');
                    }
                    if (post.engagement.shares) {
                        engagement.append('<span class="shares">' + post.engagement.shares + ' shares</span>');
                    }
                    postDiv.append(engagement);
                }
                
                // Post link
                if (post.link) {
                    postDiv.append('<div class="post-link"><a href="' + post.link + '" target="_blank">View Post</a></div>');
                }
                
                feedContainer.append(postDiv);
            });
            
            // Update last updated time
            widget.find('.last-updated').text('Updated: ' + new Date().toLocaleTimeString());
            
            // Trigger widget updated event
            widget.trigger('socialUpdated', [data]);
        },
        
        initializeSocialShare: function(shareWidget) {
            var self = this;
            
            shareWidget.find('.share-button').on('click', function(e) {
                e.preventDefault();
                
                var button = $(this);
                var platform = button.data('platform');
                var url = button.data('url') || window.location.href;
                var title = button.data('title') || document.title;
                var description = button.data('description') || '';
                
                self.shareToSocial(platform, url, title, description);
            });
        },
        
        shareToSocial: function(platform, url, title, description) {
            var shareUrl = '';
            var windowOptions = 'width=600,height=400,scrollbars=yes,resizable=yes';
            
            switch(platform) {
                case 'facebook':
                    shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
                    break;
                case 'twitter':
                    shareUrl = 'https://twitter.com/intent/tweet?url=' + encodeURIComponent(url) + 
                              '&text=' + encodeURIComponent(title);
                    break;
                case 'linkedin':
                    shareUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(url);
                    break;
                case 'whatsapp':
                    shareUrl = 'https://wa.me/?text=' + encodeURIComponent(title + ' ' + url);
                    break;
                case 'telegram':
                    shareUrl = 'https://t.me/share/url?url=' + encodeURIComponent(url) + 
                              '&text=' + encodeURIComponent(title);
                    break;
                case 'email':
                    shareUrl = 'mailto:?subject=' + encodeURIComponent(title) + 
                              '&body=' + encodeURIComponent(description + '\n\n' + url);
                    window.location.href = shareUrl;
                    return;
            }
            
            if (shareUrl) {
                window.open(shareUrl, 'social-share', windowOptions);
            }
        },
        
        // Bind global events
        bindEvents: function() {
            var self = this;
            
            // Window resize handler for responsive maps
            $(window).on('resize', function() {
                self.resizeMaps();
            });
            
            // Visibility change handler for pausing/resuming intervals
            $(document).on('visibilitychange', function() {
                if (document.hidden) {
                    self.pauseRefreshIntervals();
                } else {
                    self.resumeRefreshIntervals();
                }
            });
            
            // Custom event handlers
            $(document).on('locationChanged', '.env-location-picker', function(e, lat, lng) {
                console.log('Location changed:', lat, lng);
            });
            
            $(document).on('weatherUpdated', '.env-weather-widget', function(e, data) {
                console.log('Weather updated:', data);
            });
            
            $(document).on('airQualityUpdated', '.env-air-quality-widget', function(e, data) {
                console.log('Air quality updated:', data);
            });
            
            $(document).on('socialUpdated', '.env-social-widget', function(e, data) {
                console.log('Social media updated:', data);
            });
        },
        
        // Utility functions
        resizeMaps: function() {
            var self = this;
            
            // Trigger resize event for all maps
            $.each(this.maps, function(mapId, mapData) {
                if (mapData.map) {
                    google.maps.event.trigger(mapData.map, 'resize');
                }
            });
        },
        
        pauseRefreshIntervals: function() {
            this.intervalsPaused = true;
            
            $.each(this.refreshIntervals, function(key, interval) {
                clearInterval(interval);
            });
        },
        
        resumeRefreshIntervals: function() {
            if (!this.intervalsPaused) return;
            
            var self = this;
            this.intervalsPaused = false;
            
            // Restart weather widget intervals
            $.each(this.weatherWidgets, function(widgetId, widgetData) {
                if (widgetData.config.refreshInterval > 0) {
                    self.refreshIntervals[widgetId] = setInterval(function() {
                        self.loadWeatherData(widgetId);
                    }, widgetData.config.refreshInterval);
                }
            });
            
            // Restart air quality widget intervals
            $.each(this.airQualityWidgets, function(widgetId, widgetData) {
                if (widgetData.config.refreshInterval > 0) {
                    self.refreshIntervals[widgetId] = setInterval(function() {
                        self.loadAirQualityData(widgetId);
                    }, widgetData.config.refreshInterval);
                }
            });
            
            // Restart social media widget intervals
            $.each(this.socialWidgets, function(widgetId, widgetData) {
                if (widgetData.config.refreshInterval > 0) {
                    self.refreshIntervals[widgetId] = setInterval(function() {
                        self.loadSocialData(widgetId);
                    }, widgetData.config.refreshInterval);
                }
            });
        },
        
        showWidgetError: function(widget, message) {
            var errorDiv = widget.find('.widget-error');
            
            if (errorDiv.length === 0) {
                errorDiv = $('<div class="widget-error"></div>');
                widget.prepend(errorDiv);
            }
            
            errorDiv.text(message).show();
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                errorDiv.fadeOut();
            }, 5000);
        },
        
        hideWidgetError: function(widget) {
            widget.find('.widget-error').hide();
        },
        
        // Public API methods
        refreshWidget: function(widgetId) {
            if (this.weatherWidgets[widgetId]) {
                this.loadWeatherData(widgetId);
            }
            if (this.airQualityWidgets[widgetId]) {
                this.loadAirQualityData(widgetId);
            }
            if (this.socialWidgets[widgetId]) {
                this.loadSocialData(widgetId);
            }
        },
        
        getWidgetData: function(widgetId) {
            if (this.weatherWidgets[widgetId]) {
                return this.weatherWidgets[widgetId].data;
            }
            if (this.airQualityWidgets[widgetId]) {
                return this.airQualityWidgets[widgetId].data;
            }
            if (this.socialWidgets[widgetId]) {
                return this.socialWidgets[widgetId].data;
            }
            return null;
        },
        
        updateWidgetConfig: function(widgetId, newConfig) {
            if (this.weatherWidgets[widgetId]) {
                this.weatherWidgets[widgetId].config = $.extend(this.weatherWidgets[widgetId].config, newConfig);
            }
            if (this.airQualityWidgets[widgetId]) {
                this.airQualityWidgets[widgetId].config = $.extend(this.airQualityWidgets[widgetId].config, newConfig);
            }
            if (this.socialWidgets[widgetId]) {
                this.socialWidgets[widgetId].config = $.extend(this.socialWidgets[widgetId].config, newConfig);
            }
        },
        
        // Cleanup
        destroy: function() {
            // Clear all intervals
            $.each(this.refreshIntervals, function(key, interval) {
                clearInterval(interval);
            });
            
            // Clear maps
            $.each(this.maps, function(mapId, mapData) {
                if (mapData.infoWindows) {
                    $.each(mapData.infoWindows, function(i, infoWindow) {
                        infoWindow.close();
                    });
                }
            });
            
            this.initialized = false;
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        EIA_Frontend.init();
    });
    
    // Initialize when Google Maps is loaded (if using async loading)
    window.initGoogleMaps = function() {
        EIA_Frontend.initializeMaps();
        EIA_Frontend.initializeLocationPickers();
    };
    
    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        EIA_Frontend.destroy();
    });

})(jQuery);
