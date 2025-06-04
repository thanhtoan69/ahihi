# Environmental Data Dashboard WordPress Plugin

A comprehensive WordPress plugin for environmental data visualization, air quality monitoring, weather tracking, and carbon footprint management.

## 📋 Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Shortcodes](#shortcodes)
- [Admin Features](#admin-features)
- [API Integration](#api-integration)
- [Database Structure](#database-structure)
- [Development](#development)
- [Support](#support)

## 🌟 Features

### Core Functionality
- **Real-time Air Quality Monitoring** - Track AQI, PM2.5, PM10, O₃, and other pollutants
- **Comprehensive Weather Data** - Temperature, humidity, wind speed, UV index, and more
- **Carbon Footprint Tracking** - Personal and community carbon footprint calculation
- **Interactive Data Visualization** - Beautiful charts powered by Chart.js
- **Personal Environmental Dashboard** - Individual user dashboards with goals and achievements
- **Community Statistics** - Community-wide environmental impact tracking

### Technical Features
- **Responsive Design** - Mobile-friendly interface
- **AJAX-Powered** - Real-time updates without page refresh
- **Database Management** - Robust data storage and management
- **API Integration** - Seamless third-party API integration
- **Admin Dashboard** - Comprehensive administration interface
- **Shortcode Support** - Easy widget embedding
- **Cron Jobs** - Automated data updates
- **Caching System** - Optimized performance

## 🚀 Installation

1. **Upload Plugin Files**
   ```
   /wp-content/plugins/environmental-data-dashboard/
   ```

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Environmental Data Dashboard"
   - Click "Activate"

3. **Database Setup**
   - The plugin automatically creates required database tables on activation
   - Sample data is inserted for testing purposes

## ⚙️ Configuration

### API Keys Setup
1. Go to **WordPress Admin → Environmental Dashboard → Settings**
2. Configure API keys:
   - **Air Quality API Key** - From IQair or similar service
   - **Weather API Key** - From OpenWeatherMap or similar service
3. Set default location coordinates
4. Choose update frequency

### Basic Settings
- **Default Location**: Primary location for data collection
- **Update Frequency**: How often to fetch new data (hourly, twice daily, daily)
- **Enable Notifications**: Air quality alert notifications
- **Dashboard Theme**: Visual theme selection

## 📖 Usage

### For End Users

#### Viewing Environmental Data
- Air quality and weather widgets display automatically on configured pages
- Personal dashboard accessible via user account
- Community statistics available to all users

#### Carbon Footprint Tracking
1. Navigate to personal dashboard
2. Add daily activities (transportation, energy use, consumption)
3. View progress towards environmental goals
4. Compare with community averages

### For Administrators

#### Data Management
- Monitor all environmental data from admin dashboard
- Generate reports and export data
- Manage user goals and achievements
- Configure API settings and test connections

## 📝 Shortcodes

### Air Quality Widget
```php
[env_air_quality_widget location="Ho Chi Minh City"]
```

### Weather Widget
```php
[env_weather_widget show_forecast="true"]
```

### Carbon Footprint Tracker
```php
[env_carbon_tracker show_form="true" show_chart="true" period="month"]
```

### Personal Dashboard
```php
[env_personal_dashboard show_goals="true" show_achievements="true"]
```

### Community Statistics
```php
[env_community_stats show_chart="true" show_leaderboard="true" period="week"]
```

## 👑 Admin Features

### Dashboard Overview
- Total users and active participants
- Environmental data statistics
- System status monitoring
- Quick access to all features

### Settings Management
- API configuration
- Default location settings
- Notification preferences
- Data retention policies

### Data Administration
- View and manage all environmental data
- Generate comprehensive reports
- Export data in multiple formats
- Database optimization tools

### User Management
- Monitor user activity
- Manage user goals and achievements
- Community leaderboards
- User engagement metrics

## 🔌 API Integration

### Supported APIs
- **Air Quality APIs**: IQair, AirVisual, WAQI
- **Weather APIs**: OpenWeatherMap, WeatherAPI, AccuWeather
- **Geocoding APIs**: Google Maps, Mapbox, OpenStreet

### Configuration
1. Obtain API keys from supported providers
2. Configure in WordPress Admin → Environmental Dashboard → API Settings
3. Test connections using built-in testing tools

## 🗄️ Database Structure

### Core Tables
- `env_air_quality_data` - Air quality measurements
- `env_weather_data` - Weather information
- `env_carbon_footprint` - User carbon footprint data
- `env_user_goals` - Environmental goals tracking
- `env_community_data` - Community statistics

### Data Relationships
- User data linked to WordPress users table
- Location-based data indexing
- Time-series data optimization
- Foreign key constraints for data integrity

## 🛠️ Development

### File Structure
```
environmental-data-dashboard/
├── environmental-data-dashboard.php (Main plugin file)
├── includes/
│   ├── class-air-quality-api.php
│   ├── class-weather-api.php
│   ├── class-carbon-footprint-tracker.php
│   ├── class-environmental-widgets.php
│   ├── class-data-visualization.php
│   ├── class-community-stats.php
│   ├── class-personal-dashboard.php
│   └── class-database-manager.php
├── assets/
│   ├── css/environmental-dashboard.css
│   └── js/environmental-dashboard.js
├── admin/
│   ├── css/admin-styles.css
│   └── js/admin-dashboard.js
└── test-dashboard.html (Demo page)
```

### Key Classes
- `Environmental_Data_Dashboard` - Main plugin class
- `Air_Quality_API` - Air quality data handling
- `Weather_API` - Weather data management
- `Carbon_Footprint_Tracker` - Carbon footprint calculations
- `Environmental_Widgets` - Widget rendering system
- `Environmental_Data_Visualization` - Chart and graph generation
- `Community_Environmental_Stats` - Community statistics
- `Personal_Environmental_Dashboard` - User dashboard
- `Environmental_Database_Manager` - Database operations

### Hooks and Filters
- `env_dashboard_air_quality_data` - Filter air quality data
- `env_dashboard_weather_data` - Filter weather data
- `env_dashboard_carbon_calculation` - Filter carbon calculations
- Actions for data updates and notifications

## 📊 Performance

### Optimization Features
- Database query optimization
- Transient caching system
- AJAX loading for heavy data
- Responsive image handling
- Minified CSS and JavaScript

### Caching Strategy
- API responses cached for configured intervals
- Chart data cached per user session
- Community statistics cached daily
- Automatic cache cleanup

## 🔧 Troubleshooting

### Common Issues
1. **API Connection Errors**
   - Verify API keys are correct
   - Check API rate limits
   - Test connection in admin panel

2. **Data Not Updating**
   - Check cron job status
   - Verify API configurations
   - Review error logs

3. **Display Issues**
   - Clear browser cache
   - Check for JavaScript errors
   - Verify theme compatibility

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📈 Version History

### Version 1.0.0 (Current)
- Initial release
- Complete environmental monitoring suite
- All core features implemented
- Admin interface complete
- Mobile-responsive design

## 🤝 Support

### Getting Help
- Check documentation thoroughly
- Review troubleshooting section
- Enable debug mode for error details
- Check WordPress and PHP compatibility

### Contributing
- Report bugs via GitHub issues
- Submit feature requests
- Contribute code improvements
- Help with documentation

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🌍 Environmental Impact

This plugin helps users:
- Track and reduce their carbon footprint
- Make informed decisions about air quality
- Participate in community environmental goals
- Contribute to environmental awareness

---

**Developed with 💚 for a sustainable future**

Plugin Version: 1.0.0  
WordPress Compatibility: 5.0+  
PHP Requirements: 7.4+  
Last Updated: 2024
