# Environmental Platform WordPress Theme

## Overview

The Environmental Platform WordPress theme is a comprehensive, modern, and fully responsive theme designed specifically for environmental organizations, eco-friendly businesses, and sustainability-focused websites. The theme features a complete component-based architecture with dark/light mode support, accessibility compliance, and extensive environmental data integration.

## Features

### 🌟 Core Features
- **Responsive Design**: Optimized for all devices (desktop, tablet, mobile)
- **Dark/Light Mode**: Automatic system preference detection with manual toggle
- **Accessibility Compliant**: WCAG 2.1 AA compliant with screen reader support
- **Component-Based Architecture**: Modular design for easy customization
- **Performance Optimized**: Fast loading with optimized assets
- **SEO Ready**: Schema.org structured data and semantic HTML

### 🌱 Environmental Features
- **Environmental Scoring System**: Rate content based on environmental impact
- **Carbon Footprint Tracking**: Display carbon impact data for posts and activities
- **User Progress Tracking**: Environmental level system with achievements
- **Green Points System**: Gamification for user engagement
- **Environmental Categories**: Organize content by environmental topics
- **Impact Statistics**: Real-time environmental data widgets

### 📱 Templates & Components
- **Custom Page Templates**: Archive, search, 404, and single post layouts
- **Template Parts**: Reusable header, content, and footer components
- **Widget System**: Environmental statistics, tips, and progress widgets
- **Custom Navigation**: Environmental-themed menu walker with icons
- **Social Sharing**: Built-in social media integration

## Installation

1. **Upload Theme Files**
   ```
   wp-content/themes/environmental-platform/
   ```

2. **Activate Theme**
   - Go to Appearance → Themes in WordPress admin
   - Click "Activate" on Environmental Platform theme

3. **Configure Theme**
   - Go to Appearance → Customize to configure theme options
   - Visit Appearance → Theme Options for advanced settings

## Theme Structure

```
environmental-platform/
├── assets/
│   ├── css/
│   │   └── components.css          # Main component styles
│   ├── js/
│   │   └── theme.js               # Main theme JavaScript
│   └── images/                    # Theme images
├── inc/
│   ├── customizer.php             # WordPress Customizer settings
│   ├── template-tags.php          # Custom template functions
│   ├── widgets.php                # Custom widgets
│   ├── theme-options.php          # Admin theme options
│   └── class-environmental-walker-nav-menu.php  # Navigation walker
├── js/
│   ├── customizer.js              # Customizer preview scripts
│   └── admin-options.js           # Admin interface scripts
├── template-parts/
│   ├── hero.php                   # Hero section
│   ├── page-header.php            # Page headers
│   ├── content.php                # Post content (grid view)
│   ├── content-list.php           # Post content (list view)
│   ├── content-search.php         # Search result content
│   └── content-single.php         # Single post content
├── 404.php                        # 404 error page
├── archive.php                    # Archive pages
├── footer.php                     # Site footer
├── functions.php                  # Theme functions
├── header.php                     # Site header
├── index.php                      # Main template
├── page.php                       # Static pages
├── search.php                     # Search results
├── single.php                     # Single posts
└── style.css                      # Main stylesheet
```

## Customization

### Theme Customizer Options

Access via **Appearance → Customize**:

1. **Environmental Platform Settings**
   - Hero section configuration
   - Environmental alerts
   - Statistics display
   - Color schemes

2. **Social Media Links**
   - Facebook, Twitter, Instagram, YouTube URLs
   - Social sharing configuration

3. **Colors & Typography**
   - Primary and secondary colors
   - Font selections
   - Custom CSS

### Theme Options Panel

Access via **Appearance → Theme Options**:

1. **Environmental Impact Settings**
   - Default environmental scores
   - Carbon footprint calculations
   - User level thresholds

2. **Performance Settings**
   - Caching options
   - Asset optimization
   - Database settings

3. **Social Media Integration**
   - API keys and tokens
   - Sharing behavior
   - Analytics tracking

### Dark/Light Mode

The theme automatically detects user system preferences and provides a manual toggle:

- **Automatic Detection**: Respects `prefers-color-scheme` CSS media query
- **Manual Toggle**: Click the sun/moon icon to switch themes
- **Persistent Storage**: Saves user preference in localStorage and user meta
- **Accessibility**: Proper ARIA labels and screen reader announcements

## Environmental Data Integration

### Post Meta Fields

Each post/page can include:

- **Environmental Score** (0-100): Overall environmental impact rating
- **Carbon Impact** (kg): Carbon footprint measurement
- **Environmental Category**: Classification (climate_change, waste_reduction, etc.)

### User Progress System

- **Green Points**: Earned through environmental actions
- **User Level**: Calculated based on total environmental score
- **Activity Tracking**: Records user environmental activities
- **Achievements**: Milestone rewards and badges

### Database Integration

The theme integrates with custom database tables:

- `users`: Enhanced user profiles with environmental data
- `user_activities_comprehensive`: Activity tracking
- Environmental scoring and progress calculations

## Widgets

### Environmental Statistics Widget
Displays real-time environmental impact data:
- Total carbon saved
- Waste reduction metrics
- User engagement statistics
- Progress indicators

### Environmental Tip Widget
Shows daily environmental tips:
- Rotating tip content
- Category-based tips
- User-specific recommendations
- Action buttons

### User Progress Widget
Personal environmental dashboard:
- Current user level
- Points and achievements
- Progress towards next level
- Recent activities

## Navigation Features

### Custom Menu Walker
- **Automatic Icons**: Environmental-themed icons for menu items
- **Dropdown Support**: Multi-level navigation with hover effects
- **Mobile Optimization**: Responsive hamburger menu
- **Accessibility**: ARIA labels and keyboard navigation

### Menu Locations
- **Primary Menu**: Main site navigation
- **Footer Menu**: Footer links
- **Environmental Menu**: Environmental action items
- **Quick Links**: Secondary navigation

## Performance Optimization

### Built-in Optimizations
- **Asset Minification**: Compressed CSS and JavaScript
- **Lazy Loading**: Images load on scroll
- **Preload Hints**: Critical resource preloading
- **Clean Code**: Removed unnecessary WordPress features

### Caching Support
- Compatible with popular caching plugins
- Optimized database queries
- Efficient asset loading

## Accessibility Features

### WCAG 2.1 AA Compliance
- **Keyboard Navigation**: Full keyboard accessibility
- **Screen Reader Support**: Proper ARIA labels and landmarks
- **Color Contrast**: Meets AA contrast requirements
- **Focus Management**: Visible focus indicators

### Additional Features
- **Skip Links**: Jump to main content
- **Alt Text**: Image descriptions
- **Form Labels**: Proper form accessibility
- **Live Regions**: Dynamic content announcements

## Browser Support

- **Modern Browsers**: Chrome 70+, Firefox 65+, Safari 12+, Edge 79+
- **Progressive Enhancement**: Graceful degradation for older browsers
- **Mobile Support**: iOS Safari, Chrome Mobile, Samsung Internet

## Development

### Customizing Styles

Edit `assets/css/components.css` for component styles:

```css
/* Custom environmental card styling */
.environmental-widget {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 1.5rem;
    /* Add your custom styles */
}
```

### JavaScript Customization

Extend theme functionality in `assets/js/theme.js`:

```javascript
// Add custom environmental tracking
class CustomEnvironmentalTracking {
    constructor() {
        this.initTracking();
    }
    
    initTracking() {
        // Your custom tracking code
    }
}
```

### Adding Custom Widgets

Create new widgets in `inc/widgets.php`:

```php
class Custom_Environmental_Widget extends WP_Widget {
    // Widget implementation
}
```

## Troubleshooting

### Common Issues

1. **Dark Mode Not Working**
   - Clear browser cache
   - Check localStorage permissions
   - Verify JavaScript is enabled

2. **Environmental Data Not Displaying**
   - Ensure database tables exist
   - Check user permissions
   - Verify AJAX endpoints

3. **Theme Customizer Issues**
   - Clear WordPress cache
   - Check for plugin conflicts
   - Verify theme file permissions

### Debug Mode

Enable WordPress debug mode to troubleshoot:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support & Updates

### Getting Help
- Check theme documentation
- Review WordPress error logs
- Test with default WordPress themes

### Theme Updates
- Backup your site before updating
- Test in staging environment
- Review changelog for breaking changes

## Credits

- Icons: Environmental theme icons and system icons
- Fonts: System fonts and web-safe alternatives
- Libraries: jQuery, WordPress core functions
- Inspiration: Modern environmental design principles

## License

This theme is licensed under GPL v2 or later. You are free to modify and distribute according to the license terms.

---

**Environmental Platform Theme v1.0.0**  
Making the web more sustainable, one website at a time. 🌱
