/**
 * Environmental Data Dashboard - Frontend JavaScript
 * 
 * Handles interactive charts, real-time data updates, and user interactions
 * for the Environmental Data Dashboard plugin.
 */

(function($) {
    'use strict';

    // Global variables
    let charts = {};
    let updateInterval = null;
    let currentLocation = null;

    // Initialize dashboard on document ready
    $(document).ready(function() {
        initializeDashboard();
        bindEvents();
        startRealTimeUpdates();
    });

    /**
     * Initialize the dashboard
     */
    function initializeDashboard() {
        // Get user location
        getUserLocation();
        
        // Initialize charts
        initializeCharts();
        
        // Load initial data
        loadDashboardData();
        
        // Initialize tooltips
        initializeTooltips();
        
        // Initialize interactive elements
        initializeInteractiveElements();
    }

    /**
     * Get user's location for localized data
     */
    function getUserLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    currentLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    updateLocationBasedData();
                },
                function(error) {
                    console.log('Location access denied or unavailable');
                    // Use default location or IP-based location
                    getCurrentLocationByIP();
                }
            );
        } else {
            getCurrentLocationByIP();
        }
    }

    /**
     * Get location by IP address
     */
    function getCurrentLocationByIP() {
        $.ajax({
            url: 'https://ipapi.co/json/',
            method: 'GET',
            success: function(data) {
                currentLocation = {
                    lat: data.latitude,
                    lng: data.longitude,
                    city: data.city,
                    country: data.country_name
                };
                updateLocationBasedData();
            },
            error: function() {
                // Use default location (can be configured in admin)
                currentLocation = {
                    lat: 21.0285,
                    lng: 105.8542,
                    city: 'Hanoi',
                    country: 'Vietnam'
                };
                updateLocationBasedData();
            }
        });
    }

    /**
     * Update data based on user location
     */
    function updateLocationBasedData() {
        if (currentLocation) {
            loadAirQualityData();
            loadWeatherData();
            updateLocationDisplay();
        }
    }

    /**
     * Initialize all charts
     */
    function initializeCharts() {
        // Air Quality Chart
        if ($('#air-quality-chart').length) {
            initializeAirQualityChart();
        }

        // Weather Chart
        if ($('#weather-chart').length) {
            initializeWeatherChart();
        }

        // Carbon Footprint Chart
        if ($('#carbon-footprint-chart').length) {
            initializeCarbonFootprintChart();
        }

        // Environmental Trends Chart
        if ($('#environmental-trends-chart').length) {
            initializeEnvironmentalTrendsChart();
        }

        // Personal Progress Chart
        if ($('#personal-progress-chart').length) {
            initializePersonalProgressChart();
        }

        // Community Stats Chart
        if ($('#community-stats-chart').length) {
            initializeCommunityStatsChart();
        }
    }

    /**
     * Initialize Air Quality Chart
     */
    function initializeAirQualityChart() {
        const ctx = document.getElementById('air-quality-chart').getContext('2d');
        charts.airQuality = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'PM2.5',
                    data: [],
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    fill: true
                }, {
                    label: 'PM10',
                    data: [],
                    borderColor: '#f39c12',
                    backgroundColor: 'rgba(243, 156, 18, 0.1)',
                    fill: true
                }, {
                    label: 'AQI',
                    data: [],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Time'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Value'
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    /**
     * Initialize Weather Chart
     */
    function initializeWeatherChart() {
        const ctx = document.getElementById('weather-chart').getContext('2d');
        charts.weather = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Temperature (°C)',
                    data: [],
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    yAxisID: 'y'
                }, {
                    label: 'Humidity (%)',
                    data: [],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Time'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Temperature (°C)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Humidity (%)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    }

    /**
     * Initialize Carbon Footprint Chart
     */
    function initializeCarbonFootprintChart() {
        const ctx = document.getElementById('carbon-footprint-chart').getContext('2d');
        charts.carbonFootprint = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Transportation', 'Energy', 'Food', 'Waste', 'Other'],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        '#e74c3c',
                        '#f39c12',
                        '#2ecc71',
                        '#3498db',
                        '#9b59b6'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + ' kg CO2';
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Initialize Environmental Trends Chart
     */
    function initializeEnvironmentalTrendsChart() {
        const ctx = document.getElementById('environmental-trends-chart').getContext('2d');
        charts.environmentalTrends = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Air Quality Index',
                    data: [],
                    backgroundColor: 'rgba(231, 76, 60, 0.8)',
                    borderColor: '#e74c3c',
                    borderWidth: 1
                }, {
                    label: 'Carbon Footprint (kg)',
                    data: [],
                    backgroundColor: 'rgba(52, 152, 219, 0.8)',
                    borderColor: '#3498db',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    /**
     * Initialize Personal Progress Chart
     */
    function initializePersonalProgressChart() {
        const ctx = document.getElementById('personal-progress-chart').getContext('2d');
        charts.personalProgress = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Air Quality Awareness', 'Carbon Reduction', 'Energy Saving', 'Waste Reduction', 'Water Conservation', 'Sustainable Transport'],
                datasets: [{
                    label: 'Current Progress',
                    data: [],
                    fill: true,
                    backgroundColor: 'rgba(46, 204, 113, 0.2)',
                    borderColor: '#2ecc71',
                    pointBackgroundColor: '#2ecc71',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#2ecc71'
                }, {
                    label: 'Target Goals',
                    data: [],
                    fill: true,
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    borderColor: '#3498db',
                    pointBackgroundColor: '#3498db',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#3498db'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                elements: {
                    line: {
                        borderWidth: 3
                    }
                },
                scales: {
                    r: {
                        angleLines: {
                            display: false
                        },
                        suggestedMin: 0,
                        suggestedMax: 100
                    }
                }
            }
        });
    }

    /**
     * Initialize Community Stats Chart
     */
    function initializeCommunityStatsChart() {
        const ctx = document.getElementById('community-stats-chart').getContext('2d');
        charts.communityStats = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Active Users',
                    data: [],
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    fill: true
                }, {
                    label: 'CO2 Saved (kg)',
                    data: [],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Active Users'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'CO2 Saved (kg)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    }

    /**
     * Load dashboard data
     */
    function loadDashboardData() {
        // Load all data sections
        loadAirQualityData();
        loadWeatherData();
        loadCarbonFootprintData();
        loadPersonalDashboardData();
        loadCommunityStats();
    }

    /**
     * Load Air Quality Data
     */
    function loadAirQualityData() {
        if (!currentLocation) return;

        $.ajax({
            url: environmental_dashboard_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'get_air_quality_data',
                nonce: environmental_dashboard_ajax.nonce,
                lat: currentLocation.lat,
                lng: currentLocation.lng
            },
            success: function(response) {
                if (response.success) {
                    updateAirQualityDisplay(response.data);
                    updateAirQualityChart(response.data);
                }
            },
            error: function() {
                showError('Failed to load air quality data');
            }
        });
    }

    /**
     * Load Weather Data
     */
    function loadWeatherData() {
        if (!currentLocation) return;

        $.ajax({
            url: environmental_dashboard_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'get_weather_data',
                nonce: environmental_dashboard_ajax.nonce,
                lat: currentLocation.lat,
                lng: currentLocation.lng
            },
            success: function(response) {
                if (response.success) {
                    updateWeatherDisplay(response.data);
                    updateWeatherChart(response.data);
                }
            },
            error: function() {
                showError('Failed to load weather data');
            }
        });
    }

    /**
     * Load Carbon Footprint Data
     */
    function loadCarbonFootprintData() {
        $.ajax({
            url: environmental_dashboard_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'get_carbon_footprint_data',
                nonce: environmental_dashboard_ajax.nonce,
                user_id: environmental_dashboard_ajax.user_id
            },
            success: function(response) {
                if (response.success) {
                    updateCarbonFootprintDisplay(response.data);
                    updateCarbonFootprintChart(response.data);
                }
            },
            error: function() {
                showError('Failed to load carbon footprint data');
            }
        });
    }

    /**
     * Load Personal Dashboard Data
     */
    function loadPersonalDashboardData() {
        $.ajax({
            url: environmental_dashboard_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'get_personal_dashboard_data',
                nonce: environmental_dashboard_ajax.nonce,
                user_id: environmental_dashboard_ajax.user_id
            },
            success: function(response) {
                if (response.success) {
                    updatePersonalDashboardDisplay(response.data);
                    updatePersonalProgressChart(response.data);
                }
            },
            error: function() {
                showError('Failed to load personal dashboard data');
            }
        });
    }

    /**
     * Load Community Stats
     */
    function loadCommunityStats() {
        $.ajax({
            url: environmental_dashboard_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'get_community_stats',
                nonce: environmental_dashboard_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateCommunityStatsDisplay(response.data);
                    updateCommunityStatsChart(response.data);
                }
            },
            error: function() {
                showError('Failed to load community statistics');
            }
        });
    }

    /**
     * Update Air Quality Display
     */
    function updateAirQualityDisplay(data) {
        if (data.current) {
            $('.air-quality-value').text(data.current.aqi);
            $('.air-quality-status').text(data.current.status);
            $('.air-quality-status').removeClass().addClass('air-quality-status status-' + data.current.level);
            $('.pm25-value').text(data.current.pm25);
            $('.pm10-value').text(data.current.pm10);
            $('.last-updated').text('Last updated: ' + data.current.timestamp);
        }
    }

    /**
     * Update Air Quality Chart
     */
    function updateAirQualityChart(data) {
        if (charts.airQuality && data.historical) {
            charts.airQuality.data.labels = data.historical.map(item => item.time);
            charts.airQuality.data.datasets[0].data = data.historical.map(item => item.pm25);
            charts.airQuality.data.datasets[1].data = data.historical.map(item => item.pm10);
            charts.airQuality.data.datasets[2].data = data.historical.map(item => item.aqi);
            charts.airQuality.update();
        }
    }

    /**
     * Update Weather Display
     */
    function updateWeatherDisplay(data) {
        if (data.current) {
            $('.temperature-value').text(data.current.temperature + '°C');
            $('.humidity-value').text(data.current.humidity + '%');
            $('.weather-condition').text(data.current.condition);
            $('.weather-icon').attr('src', data.current.icon);
            $('.feels-like-value').text(data.current.feels_like + '°C');
            $('.wind-speed-value').text(data.current.wind_speed + ' km/h');
        }
    }

    /**
     * Update Weather Chart
     */
    function updateWeatherChart(data) {
        if (charts.weather && data.forecast) {
            charts.weather.data.labels = data.forecast.map(item => item.time);
            charts.weather.data.datasets[0].data = data.forecast.map(item => item.temperature);
            charts.weather.data.datasets[1].data = data.forecast.map(item => item.humidity);
            charts.weather.update();
        }
    }

    /**
     * Update Carbon Footprint Display
     */
    function updateCarbonFootprintDisplay(data) {
        if (data.current) {
            $('.total-carbon-footprint').text(data.current.total + ' kg CO2');
            $('.daily-carbon-footprint').text(data.current.daily + ' kg CO2');
            $('.monthly-carbon-footprint').text(data.current.monthly + ' kg CO2');
            $('.carbon-reduction-percentage').text(data.current.reduction + '%');
        }
    }

    /**
     * Update Carbon Footprint Chart
     */
    function updateCarbonFootprintChart(data) {
        if (charts.carbonFootprint && data.breakdown) {
            charts.carbonFootprint.data.datasets[0].data = [
                data.breakdown.transportation,
                data.breakdown.energy,
                data.breakdown.food,
                data.breakdown.waste,
                data.breakdown.other
            ];
            charts.carbonFootprint.update();
        }
    }

    /**
     * Update Personal Dashboard Display
     */
    function updatePersonalDashboardDisplay(data) {
        if (data.overview) {
            $('.personal-score').text(data.overview.score);
            $('.achievements-count').text(data.overview.achievements);
            $('.goals-completed').text(data.overview.completed_goals);
            $('.streak-days').text(data.overview.streak);
        }

        // Update recommendations
        if (data.recommendations) {
            const recommendationsList = $('.recommendations-list');
            recommendationsList.empty();
            data.recommendations.forEach(rec => {
                recommendationsList.append(`
                    <div class="recommendation-item">
                        <h4>${rec.title}</h4>
                        <p>${rec.description}</p>
                        <span class="recommendation-impact">Impact: ${rec.impact}</span>
                    </div>
                `);
            });
        }
    }

    /**
     * Update Personal Progress Chart
     */
    function updatePersonalProgressChart(data) {
        if (charts.personalProgress && data.progress) {
            charts.personalProgress.data.datasets[0].data = [
                data.progress.air_quality,
                data.progress.carbon_reduction,
                data.progress.energy_saving,
                data.progress.waste_reduction,
                data.progress.water_conservation,
                data.progress.sustainable_transport
            ];
            charts.personalProgress.data.datasets[1].data = [
                data.goals.air_quality,
                data.goals.carbon_reduction,
                data.goals.energy_saving,
                data.goals.waste_reduction,
                data.goals.water_conservation,
                data.goals.sustainable_transport
            ];
            charts.personalProgress.update();
        }
    }

    /**
     * Update Community Stats Display
     */
    function updateCommunityStatsDisplay(data) {
        if (data.overview) {
            $('.community-users-count').text(data.overview.total_users);
            $('.community-co2-saved').text(data.overview.co2_saved + ' kg');
            $('.community-goals-achieved').text(data.overview.goals_achieved);
            $('.community-participation-rate').text(data.overview.participation_rate + '%');
        }

        // Update leaderboard
        if (data.leaderboard) {
            const leaderboardList = $('.leaderboard-list');
            leaderboardList.empty();
            data.leaderboard.forEach((user, index) => {
                leaderboardList.append(`
                    <div class="leaderboard-item">
                        <span class="rank">${index + 1}</span>
                        <span class="username">${user.name}</span>
                        <span class="score">${user.score}</span>
                    </div>
                `);
            });
        }
    }

    /**
     * Update Community Stats Chart
     */
    function updateCommunityStatsChart(data) {
        if (charts.communityStats && data.trends) {
            charts.communityStats.data.labels = data.trends.map(item => item.date);
            charts.communityStats.data.datasets[0].data = data.trends.map(item => item.users);
            charts.communityStats.data.datasets[1].data = data.trends.map(item => item.co2_saved);
            charts.communityStats.update();
        }
    }

    /**
     * Update location display
     */
    function updateLocationDisplay() {
        if (currentLocation && currentLocation.city) {
            $('.current-location').text(currentLocation.city + ', ' + currentLocation.country);
        }
    }

    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Refresh button
        $('.refresh-data').on('click', function(e) {
            e.preventDefault();
            refreshAllData();
        });

        // Time range selectors
        $('.time-range-selector').on('change', function() {
            const range = $(this).val();
            updateChartsTimeRange(range);
        });

        // Goal setting
        $('.set-goal-btn').on('click', function(e) {
            e.preventDefault();
            showGoalSettingModal();
        });

        // Carbon footprint entry
        $('.add-carbon-entry').on('click', function(e) {
            e.preventDefault();
            showCarbonEntryModal();
        });

        // Tab switching
        $('.dashboard-tab').on('click', function(e) {
            e.preventDefault();
            const tabId = $(this).data('tab');
            switchTab(tabId);
        });

        // Settings toggle
        $('.settings-toggle').on('click', function(e) {
            e.preventDefault();
            toggleSettings();
        });

        // Location change
        $('.change-location').on('click', function(e) {
            e.preventDefault();
            showLocationModal();
        });
    }

    /**
     * Start real-time updates
     */
    function startRealTimeUpdates() {
        // Update every 5 minutes
        updateInterval = setInterval(function() {
            refreshAllData();
        }, 5 * 60 * 1000);
    }

    /**
     * Refresh all data
     */
    function refreshAllData() {
        showLoadingIndicator();
        loadDashboardData();
        setTimeout(hideLoadingIndicator, 2000);
    }

    /**
     * Update charts time range
     */
    function updateChartsTimeRange(range) {
        // Reload data with new time range
        $.ajax({
            url: environmental_dashboard_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'get_charts_data',
                nonce: environmental_dashboard_ajax.nonce,
                time_range: range,
                user_id: environmental_dashboard_ajax.user_id
            },
            success: function(response) {
                if (response.success) {
                    updateAllCharts(response.data);
                }
            }
        });
    }

    /**
     * Update all charts with new data
     */
    function updateAllCharts(data) {
        if (data.air_quality) updateAirQualityChart(data.air_quality);
        if (data.weather) updateWeatherChart(data.weather);
        if (data.carbon_footprint) updateCarbonFootprintChart(data.carbon_footprint);
        if (data.personal_progress) updatePersonalProgressChart(data.personal_progress);
        if (data.community_stats) updateCommunityStatsChart(data.community_stats);
    }

    /**
     * Switch tabs
     */
    function switchTab(tabId) {
        $('.dashboard-tab').removeClass('active');
        $('.dashboard-tab[data-tab="' + tabId + '"]').addClass('active');
        
        $('.dashboard-panel').removeClass('active');
        $('#' + tabId + '-panel').addClass('active');
        
        // Resize charts when tab becomes visible
        setTimeout(function() {
            Object.keys(charts).forEach(function(key) {
                if (charts[key]) {
                    charts[key].resize();
                }
            });
        }, 100);
    }

    /**
     * Initialize tooltips
     */
    function initializeTooltips() {
        $('[data-tooltip]').each(function() {
            $(this).attr('title', $(this).data('tooltip'));
        });
    }

    /**
     * Initialize interactive elements
     */
    function initializeInteractiveElements() {
        // Smooth scrolling for internal links
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            const target = $($(this).attr('href'));
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 20
                }, 500);
            }
        });

        // Collapsible sections
        $('.collapsible-header').on('click', function() {
            $(this).next('.collapsible-content').slideToggle();
            $(this).find('.collapse-icon').toggleClass('rotated');
        });
    }

    /**
     * Show loading indicator
     */
    function showLoadingIndicator() {
        $('.loading-indicator').show();
        $('.refresh-data').prop('disabled', true);
    }

    /**
     * Hide loading indicator
     */
    function hideLoadingIndicator() {
        $('.loading-indicator').hide();
        $('.refresh-data').prop('disabled', false);
    }

    /**
     * Show error message
     */
    function showError(message) {
        const errorDiv = $('<div class="error-message">' + message + '</div>');
        $('.dashboard-header').after(errorDiv);
        setTimeout(function() {
            errorDiv.fadeOut(function() {
                errorDiv.remove();
            });
        }, 5000);
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        const successDiv = $('<div class="success-message">' + message + '</div>');
        $('.dashboard-header').after(successDiv);
        setTimeout(function() {
            successDiv.fadeOut(function() {
                successDiv.remove();
            });
        }, 3000);
    }

    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        if (updateInterval) {
            clearInterval(updateInterval);
        }
    });

    // Expose functions for external use
    window.EnvironmentalDashboard = {
        refreshData: refreshAllData,
        updateLocation: updateLocationBasedData,
        switchTab: switchTab,
        showError: showError,
        showSuccess: showSuccess
    };

})(jQuery);
