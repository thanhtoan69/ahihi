# Environmental Advanced Search & Filtering - Phase 53

## Overview

The Environmental Advanced Search & Filtering plugin is a comprehensive WordPress plugin that provides enterprise-level search capabilities for the Environmental Platform. This plugin implements Phase 53 of the development roadmap, featuring advanced search algorithms, Elasticsearch integration, faceted search, geolocation-based filtering, and detailed analytics.

## Features

### 🔍 Advanced Search Engine
- **Weighted Search Scoring**: Configurable weights for different content types (title, content, excerpt, meta fields, taxonomies)
- **Multi-field Search**: Search across posts, pages, custom post types, and metadata
- **Real-time Suggestions**: Autocomplete functionality with search history
- **Relevance Calculation**: Sophisticated scoring algorithm for better result ranking

### ⚡ Elasticsearch Integration
- **Custom HTTP Client**: Direct integration with Elasticsearch clusters
- **Bulk Operations**: Efficient indexing and updating of large content sets
- **Real-time Sync**: Automatic synchronization of content changes
- **Advanced Queries**: Support for complex search queries and filters
- **Performance Monitoring**: Built-in performance metrics and optimization

### 🎯 Faceted Search System
- **Multiple Filter Types**: 
  - Select dropdowns
  - Checkbox groups
  - Radio buttons
  - Range sliders
  - Hierarchical taxonomies
  - Location-based filters
- **Dynamic Counting**: Real-time facet counts based on current search results
- **Filter Persistence**: Maintains filter state across search sessions
- **Custom Facet Configuration**: Admin interface for creating custom facets

### 📍 Geolocation Search
- **Address Geocoding**: Convert addresses to coordinates
- **Distance Calculations**: Search within specified radius
- **Location Meta Boxes**: Easy location assignment for content
- **Multiple Geocoding Providers**: Support for Google Maps, OpenStreetMap
- **Interactive Maps**: Visual location selection and display

### 📊 Search Analytics
- **Query Tracking**: Monitor search terms and frequency
- **Performance Metrics**: Response times, click-through rates, success rates
- **Popular Searches**: Trending search terms and suggestions
- **User Behavior**: Track user search patterns and preferences
- **Data Export**: Export analytics data in CSV, JSON formats
- **Visual Dashboard**: Charts and graphs for data visualization

### 🎨 Frontend Features
- **AJAX-Powered Interface**: Seamless search experience without page reloads
- **Responsive Design**: Mobile-optimized search interface
- **Customizable Widgets**: WordPress widget for search functionality
- **Flexible Shortcodes**: Multiple shortcodes for different search layouts
- **Progressive Enhancement**: Works with JavaScript disabled

### 🛠 Admin Interface
- **Comprehensive Dashboard**: Complete management interface
- **Settings Configuration**: Easy setup and customization
- **Elasticsearch Management**: Index management and monitoring
- **Analytics Reporting**: Detailed reports and insights
- **Tool Suite**: Maintenance and optimization tools

## Installation

1. **Prerequisites**: Ensure Environmental Platform Core plugin is installed and activated
2. **Upload**: Upload the plugin files to `/wp-content/plugins/environmental-advanced-search/`
3. **Activate**: Activate the plugin through the WordPress admin interface
4. **Configure**: Visit the plugin settings to configure search options

## Configuration

### Basic Setup
1. Navigate to **Environmental Platform → Advanced Search** in WordPress admin
2. Configure basic search settings in the **Settings** tab
3. Set up search weights and relevance factors
4. Configure frontend display options

### Elasticsearch Setup (Optional)
1. Install and configure Elasticsearch server
2. Enter Elasticsearch connection details in the **Elasticsearch** tab
3. Test the connection using the built-in test tool
4. Reindex existing content for Elasticsearch

### Faceted Search Configuration
1. Go to the **Facets** section in settings
2. Configure which taxonomies and meta fields to use as facets
3. Set display options and filter types
4. Save and test faceted search functionality

### Geolocation Setup
1. Obtain API keys for geocoding services (Google Maps recommended)
2. Enter API credentials in the **Geolocation** settings
3. Configure default location and radius settings
4. Test geocoding functionality

## Usage

### Search Widget
Add the Advanced Search widget to any widget area:
```php
// In widgets admin or Customizer
Add Widget → Environmental Advanced Search
```

### Shortcodes
Use shortcodes to embed search functionality:

```php
// Basic search form
[eas_search]

// Search form with facets
[eas_search_with_facets]

// Search results display
[eas_search_results]

// Faceted search interface
[eas_faceted_search]
```

### Template Integration
For theme developers:
```php
// Display advanced search form
if (function_exists('eas_search_form')) {
    eas_search_form();
}

// Get search results with advanced features
$results = eas_get_search_results($query, $filters);
```

## API Reference

### AJAX Endpoints
- `eas_perform_search` - Execute search query
- `eas_get_suggestions` - Get autocomplete suggestions
- `eas_get_facet_counts` - Get facet counts
- `eas_track_search` - Track search analytics
- `eas_geocode_address` - Geocode address to coordinates

### REST API Endpoints
- `GET /wp-json/eas/v1/search` - Search content
- `GET /wp-json/eas/v1/facets` - Get available facets
- `GET /wp-json/eas/v1/analytics` - Get search analytics
- `POST /wp-json/eas/v1/track` - Track search event

### Filter Hooks
```php
// Modify search query
add_filter('eas_search_query', 'custom_search_query');

// Customize search results
add_filter('eas_search_results', 'custom_search_results');

// Add custom facets
add_filter('eas_available_facets', 'custom_facets');

// Modify search weights
add_filter('eas_search_weights', 'custom_search_weights');
```

### Action Hooks
```php
// Before search execution
add_action('eas_before_search', 'custom_before_search');

// After search execution
add_action('eas_after_search', 'custom_after_search');

// On search result click
add_action('eas_result_clicked', 'track_result_click');
```

## Performance Optimization

### Caching
- Built-in result caching with configurable TTL
- Facet count caching for improved performance
- Suggestion caching for autocomplete

### Database Optimization
- Efficient query structures
- Proper indexing for analytics tables
- Automated cleanup of old analytics data

### Elasticsearch Optimization
- Bulk indexing for large content sets
- Configurable refresh intervals
- Index optimization tools

## Troubleshooting

### Common Issues

**Search not working**
- Check that the plugin is activated
- Verify Environmental Platform Core is installed
- Test with default WordPress search first

**Elasticsearch connection failed**
- Verify Elasticsearch server is running
- Check connection credentials
- Test network connectivity

**Geolocation not working**
- Verify API keys are correct
- Check API usage limits
- Test with different addresses

**Performance issues**
- Enable caching options
- Optimize database tables
- Check Elasticsearch performance

### Debug Mode
Enable debug mode by adding to wp-config.php:
```php
define('EAS_DEBUG', true);
```

## Database Schema

### Analytics Tables
- `eas_search_analytics` - Search query tracking
- `eas_popular_searches` - Popular search terms
- `eas_click_tracking` - Result click tracking

### Options
- `eas_settings` - Plugin configuration
- `eas_elasticsearch_config` - Elasticsearch settings
- `eas_facet_config` - Facet configuration

## Security

### Data Protection
- All AJAX requests are nonce-protected
- User input is sanitized and validated
- SQL injection protection through prepared statements

### Privacy Compliance
- Optional analytics data collection
- User consent mechanisms
- Data anonymization options
- GDPR compliance features

## Compatibility

### WordPress Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Plugin Dependencies
- Environmental Platform Core (required)
- Compatible with major caching plugins
- Works with popular SEO plugins

### Browser Support
- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+

## Development

### File Structure
```
environmental-advanced-search/
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       ├── admin.js
│       └── frontend.js
├── includes/
│   ├── class-admin.php
│   ├── class-ajax-handlers.php
│   ├── class-elasticsearch-manager.php
│   ├── class-faceted-search.php
│   ├── class-geolocation-search.php
│   ├── class-search-analytics.php
│   ├── class-search-engine.php
│   ├── class-search-widget.php
│   └── class-shortcodes.php
├── languages/
├── templates/
└── environmental-advanced-search.php
```

### Contributing
1. Fork the repository
2. Create feature branch
3. Follow WordPress coding standards
4. Add tests for new functionality
5. Submit pull request

## Changelog

### Version 1.0.0
- Initial release
- Advanced search engine with weighted scoring
- Elasticsearch integration
- Faceted search system
- Geolocation-based search
- Comprehensive analytics system
- AJAX-powered frontend
- Admin dashboard and management tools

## Support

For support and documentation:
- Visit the Environmental Platform documentation
- Submit issues through the plugin repository
- Contact the development team

## License

This plugin is licensed under GPL v2 or later. See LICENSE file for details.

---

**Environmental Platform Team**  
Phase 53 - Advanced Search & Filtering  
Version 1.0.0
