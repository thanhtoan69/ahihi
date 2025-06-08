# Environmental Integration APIs Plugin

## Overview

The Environmental Integration APIs plugin provides comprehensive integration with external environmental services including Google Maps, weather APIs, air quality monitoring, social media platforms, and webhook systems. This plugin is designed specifically for environmental platforms and WordPress sites focusing on environmental data and awareness.

## Features

### 🗺️ Google Maps Integration
- **Geocoding & Reverse Geocoding**: Convert addresses to coordinates and vice versa
- **Interactive Maps**: Embed customizable maps with environmental markers
- **Location Picker**: User-friendly location selection with search functionality
- **Places Search**: Find nearby environmental services and facilities
- **Caching System**: Optimized performance with intelligent caching

### 🌤️ Weather Integration
- **Multi-Provider Support**: OpenWeatherMap, AccuWeather, WeatherAPI
- **Current Weather**: Real-time weather conditions with detailed metrics
- **Weather Forecasts**: 5-day forecasts with hourly breakdowns
- **Weather Alerts**: Severe weather warnings with email/webhook notifications
- **Responsive Widgets**: Mobile-friendly weather displays

### 🌬️ Air Quality Monitoring
- **AQI Tracking**: Real-time Air Quality Index monitoring
- **Pollutant Details**: PM2.5, PM10, O3, NO2, SO2, CO measurements
- **Health Categories**: Color-coded health recommendations
- **Forecast Data**: Air quality predictions and trends
- **Alert System**: Threshold-based notifications for poor air quality

### 📱 Social Media Integration
- **Platform Support**: Facebook, Twitter, Instagram
- **Auto-Sharing**: Automatic post sharing for environmental content
- **Social Feeds**: Display environmental social media content
- **Engagement Tracking**: Monitor likes, comments, and shares
- **Scheduling**: Plan and schedule environmental awareness posts

### 🔗 Webhook System
- **Event-Driven**: Trigger webhooks on environmental alerts
- **Secure Communications**: Signature verification for webhook security
- **Retry Mechanisms**: Automatic retry with exponential backoff
- **Comprehensive Logging**: Track all webhook deliveries and responses
- **REST API**: Full webhook management via REST endpoints

### 📊 API Monitoring & Analytics
- **Real-Time Monitoring**: Track API health and performance
- **Rate Limiting**: Intelligent rate limiting with per-API controls
- **Error Tracking**: Monitor and alert on API failures
- **Performance Metrics**: Response time analytics and availability monitoring
- **Dashboard Analytics**: Visual charts and statistics

## Installation

1. **Upload Plugin Files**:
   ```
   wp-content/plugins/environmental-integration-apis/
   ```

2. **Activate Plugin**:
   Navigate to WordPress Admin → Plugins → Activate "Environmental Integration APIs"

3. **Database Setup**:
   The plugin automatically creates required database tables on activation:
   - `eia_api_connections`
   - `eia_api_logs`
   - `eia_webhooks`
   - `eia_webhook_logs`
   - `eia_api_cache`

## Configuration

### API Keys Setup

Navigate to **Admin → Environmental APIs → API Configuration**

#### Google Maps
1. Obtain API key from [Google Cloud Console](https://console.cloud.google.com/)
2. Enable Maps JavaScript API, Geocoding API, and Places API
3. Enter API key in plugin settings

#### Weather APIs

**OpenWeatherMap**:
1. Register at [OpenWeatherMap](https://openweathermap.org/api)
2. Get free API key (1000 calls/day)
3. Enter API key in weather settings

**AccuWeather**:
1. Register at [AccuWeather Developer](https://developer.accuweather.com/)
2. Get API key (50 calls/day free)
3. Enter API key in weather settings

**WeatherAPI**:
1. Register at [WeatherAPI](https://www.weatherapi.com/)
2. Get API key (1M calls/month free)
3. Enter API key in weather settings

#### Air Quality APIs

**IQAir**:
1. Register at [IQAir AirVisual](https://www.iqair.com/air-pollution-data-api)
2. Get API key
3. Enter API key in air quality settings

#### Social Media APIs

**Facebook**:
1. Create app at [Facebook Developers](https://developers.facebook.com/)
2. Get App ID and App Secret
3. Configure OAuth permissions

**Twitter**:
1. Apply for developer account at [Twitter Developer](https://developer.twitter.com/)
2. Create app and get API keys
3. Configure authentication

## Shortcodes

### Google Maps

#### Interactive Map
```php
[env_google_map 
    center_lat="21.0285" 
    center_lng="105.8542" 
    zoom="10" 
    height="400px" 
    markers='[{"lat":21.0285,"lng":105.8542,"title":"Hanoi","content":"Capital of Vietnam"}]'
]
```

#### Location Picker
```php
[env_location_picker 
    name="event_location" 
    default_lat="21.0285" 
    default_lng="105.8542" 
    show_coordinates="true"
]
```

### Weather

#### Current Weather Widget
```php
[env_weather 
    location="Hanoi" 
    units="metric" 
    show_forecast="false"
]
```

#### Weather Forecast
```php
[env_weather_forecast 
    location="Hanoi" 
    days="5" 
    units="metric"
]
```

#### Compact Weather Widget
```php
[env_weather_widget 
    location="Ho Chi Minh City" 
    style="compact" 
    refresh_interval="300"
]
```

### Air Quality

#### Current Air Quality
```php
[env_air_quality 
    location="Hanoi" 
    show_pollutants="true"
]
```

#### Air Quality Widget
```php
[env_air_quality_widget 
    location="Hanoi" 
    style="minimal" 
    show_forecast="true"
]
```

### Social Media

#### Social Sharing Buttons
```php
[env_social_share 
    platforms="facebook,twitter,linkedin" 
    url="current" 
    title="Environmental Alert"
]
```

#### Social Media Feed
```php
[env_social_feed 
    platform="facebook" 
    count="5" 
    show_images="true"
]
```

## REST API Endpoints

### Base URL
```
https://yoursite.com/wp-json/environmental-integration/v1/
```

### Authentication
Include API key in header:
```
X-API-Key: your-api-key
```

### Google Maps Endpoints

#### Geocoding
```http
POST /google-maps/geocode
Content-Type: application/json

{
    "address": "1600 Amphitheatre Parkway, Mountain View, CA"
}
```

#### Reverse Geocoding
```http
POST /google-maps/reverse-geocode
Content-Type: application/json

{
    "lat": 37.4224764,
    "lng": -122.0842499
}
```

#### Nearby Places
```http
POST /google-maps/nearby-places
Content-Type: application/json

{
    "lat": 37.4224764,
    "lng": -122.0842499,
    "radius": 5000,
    "type": "hospital"
}
```

### Weather Endpoints

#### Current Weather
```http
GET /weather/current?location=Hanoi&units=metric
```

#### Weather Forecast
```http
GET /weather/forecast?location=Hanoi&days=5&units=metric
```

#### Weather Alerts
```http
GET /weather/alerts?location=Hanoi
```

### Air Quality Endpoints

#### Current Air Quality
```http
GET /air-quality/current?location=Hanoi
```

#### Air Quality Forecast
```http
GET /air-quality/forecast?location=Hanoi&days=3
```

### Social Media Endpoints

#### Share Content
```http
POST /social/share
Content-Type: application/json

{
    "platform": "facebook",
    "content": "Environmental awareness post",
    "url": "https://example.com/article"
}
```

#### Get Social Feed
```http
GET /social/feed?platform=facebook&count=10
```

### Webhook Endpoints

#### List Webhooks
```http
GET /webhooks
```

#### Create Webhook
```http
POST /webhooks
Content-Type: application/json

{
    "name": "Weather Alert Hook",
    "url": "https://your-site.com/webhook-endpoint",
    "events": ["weather_alert", "air_quality_alert"],
    "status": "active"
}
```

#### Update Webhook
```http
PUT /webhooks/{id}
Content-Type: application/json

{
    "name": "Updated Webhook Name",
    "status": "inactive"
}
```

#### Delete Webhook
```http
DELETE /webhooks/{id}
```

#### Test Webhook
```http
POST /webhooks/{id}/test
```

### Monitoring Endpoints

#### API Status
```http
GET /monitor/status
```

#### API Statistics
```http
GET /monitor/statistics?period=24h
```

## JavaScript API

### Frontend Usage

The plugin provides a global `EIA_Frontend` object for frontend interactions:

```javascript
// Refresh a weather widget
EIA_Frontend.refreshWidget('weather-widget-1');

// Get widget data
var weatherData = EIA_Frontend.getWidgetData('weather-widget-1');

// Update widget configuration
EIA_Frontend.updateWidgetConfig('weather-widget-1', {
    refreshInterval: 600000 // 10 minutes
});

// Listen for widget updates
$(document).on('weatherUpdated', '.env-weather-widget', function(e, data) {
    console.log('Weather updated:', data);
});
```

### Admin Usage

Admin interface provides an `EIA_Admin` object:

```javascript
// Test API connection
EIA_Admin.testApiConnection('openweathermap');

// Refresh dashboard
EIA_Admin.refreshDashboard();

// Show notification
EIA_Admin.showNotice('Operation completed', 'success');
```

## Webhooks

### Event Types

- `weather_alert`: Severe weather warnings
- `air_quality_alert`: Poor air quality notifications
- `api_error`: API service failures
- `api_limit_reached`: Rate limit exceeded
- `system_error`: Plugin system errors

### Webhook Payload

```json
{
    "event": "weather_alert",
    "timestamp": "2024-01-15T10:30:00Z",
    "data": {
        "location": "Hanoi",
        "alert_type": "severe_weather",
        "severity": "moderate",
        "title": "Thunderstorm Warning",
        "description": "Severe thunderstorms expected in the area",
        "start_time": "2024-01-15T12:00:00Z",
        "end_time": "2024-01-15T18:00:00Z"
    },
    "signature": "sha256=webhook_signature"
}
```

### Signature Verification

Verify webhook authenticity using HMAC SHA256:

```php
function verify_webhook_signature($payload, $signature, $secret) {
    $calculated_signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($calculated_signature, $signature);
}
```

## Caching

The plugin implements intelligent caching for optimal performance:

- **API Responses**: Cached for 5-15 minutes depending on data type
- **Geocoding Results**: Cached for 24 hours
- **Weather Data**: Cached for 10 minutes
- **Air Quality Data**: Cached for 15 minutes
- **Social Media Feeds**: Cached for 30 minutes

### Manual Cache Management

```php
// Clear all cache
do_action('eia_clear_cache');

// Clear specific cache
do_action('eia_clear_cache', 'weather');

// Get cache statistics
$stats = apply_filters('eia_get_cache_stats', array());
```

## Database Schema

### API Connections Table
```sql
CREATE TABLE eia_api_connections (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    api_type varchar(50) NOT NULL,
    provider varchar(50) NOT NULL,
    api_key text,
    settings longtext,
    rate_limit_per_minute int DEFAULT 60,
    rate_limit_per_day int DEFAULT 1000,
    status enum('active','inactive','error') DEFAULT 'active',
    last_error text,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY api_type (api_type),
    KEY provider (provider),
    KEY status (status)
);
```

### API Logs Table
```sql
CREATE TABLE eia_api_logs (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    api_type varchar(50) NOT NULL,
    provider varchar(50) NOT NULL,
    endpoint varchar(255) NOT NULL,
    method varchar(10) DEFAULT 'GET',
    request_data longtext,
    response_data longtext,
    response_code int,
    response_time int,
    status enum('success','error','timeout') NOT NULL,
    error_message text,
    user_id bigint(20),
    ip_address varchar(45),
    user_agent text,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY api_type (api_type),
    KEY provider (provider),
    KEY status (status),
    KEY created_at (created_at),
    KEY user_id (user_id)
);
```

## Troubleshooting

### Common Issues

#### API Connection Failed
1. Verify API keys are correct
2. Check API quotas and limits
3. Ensure proper API permissions
4. Review error logs in Admin → Logs

#### Maps Not Loading
1. Verify Google Maps API key
2. Check API restrictions and permissions
3. Ensure Maps JavaScript API is enabled
4. Check browser console for errors

#### Webhook Delivery Failed
1. Verify webhook URL is accessible
2. Check SSL certificate validity
3. Review webhook logs for error details
4. Test webhook endpoint manually

#### Widget Not Updating
1. Check API connection status
2. Verify caching settings
3. Clear plugin cache
4. Check JavaScript console for errors

### Debug Mode

Enable debug mode in plugin settings to:
- Log detailed API requests/responses
- Show additional error information
- Enable development tools
- Track performance metrics

### Support

For technical support and feature requests:
- Plugin Settings → Help & Support
- WordPress.org Plugin Directory
- GitHub Repository Issues

## Performance Optimization

### Caching Strategy
- Use WordPress object cache when available
- Implement page-level caching for widgets
- Cache API responses with appropriate TTL
- Use CDN for static assets

### Rate Limiting
- Configure appropriate API rate limits
- Monitor usage in Analytics dashboard
- Implement queuing for high-volume sites
- Use multiple API keys for scaling

### Database Optimization
- Regular log cleanup (automated)
- Index optimization for large datasets
- Implement log rotation
- Monitor database performance

## Security

### API Security
- Secure API key storage
- Input validation and sanitization
- Rate limiting protection
- Request logging and monitoring

### Webhook Security
- HMAC signature verification
- HTTPS-only webhook URLs
- Request origin validation
- Payload size limits

### Data Privacy
- No personal data stored unnecessarily
- GDPR compliance considerations
- User consent for external API calls
- Data retention policies

## Changelog

### Version 1.0.0
- Initial release
- Google Maps integration
- Weather API support
- Air Quality monitoring
- Social Media integration
- Webhook system
- Admin dashboard
- REST API endpoints
- Comprehensive documentation

## License

This plugin is licensed under the GPL v2 or later.

---

**Environmental Integration APIs Plugin** - Comprehensive environmental data integration for WordPress.

For more information, visit the [plugin documentation](https://your-documentation-site.com) or [support forum](https://your-support-site.com).
