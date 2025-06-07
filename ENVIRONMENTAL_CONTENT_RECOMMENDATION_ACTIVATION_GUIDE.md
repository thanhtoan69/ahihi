# Environmental Content Recommendation Plugin - Activation Guide

## Quick Activation Steps

### 1. Access WordPress Admin
- Navigate to: `http://localhost/moitruong/wp-admin`
- Login with your WordPress admin credentials

### 2. Activate the Plugin
- Go to **Plugins** → **Installed Plugins**
- Find "Environmental Content Recommendation Engine"
- Click **Activate**

### 3. Configure the Plugin
- After activation, go to **Environmental Content Recommendation** in the admin menu
- Configure settings in the **Settings** tab:
  - Enable the recommendation engine
  - Set maximum recommendations per page
  - Configure algorithm weights
  - Set cache TTL (recommended: 3600 seconds)

### 4. Test Recommendations
- Add some content to your site if you haven't already
- Use shortcodes in posts/pages:
  - `[ecr_recommendations]` - Personalized recommendations
  - `[ecr_similar_content]` - Similar content
  - `[ecr_trending_content]` - Trending content
  - `[ecr_environmental_content]` - Environmental priority content

### 5. Monitor Performance
- Check the **Analytics** tab for recommendation performance
- View user behavior in the **User Behavior** tab
- Monitor the **Dashboard** for quick stats

## Plugin Features Available After Activation

### Admin Interface
- **Dashboard**: Overview of recommendation performance
- **Analytics**: Detailed metrics and charts
- **Settings**: Configure algorithms and options
- **User Behavior**: Track user interactions

### Frontend Features
- Personalized content recommendations
- Similar content suggestions
- Trending content discovery
- Environmental impact scoring
- User behavior tracking
- Rating system
- AJAX loading

### Integration Features
- WooCommerce product recommendations
- REST API endpoints for mobile apps
- Environmental platform event tracking
- Widget system for sidebars
- Auto-injection into content

## Default Settings
The plugin comes with sensible defaults:
- **Enabled**: true
- **Max Recommendations**: 5
- **Cache TTL**: 3600 seconds (1 hour)
- **Algorithm Weights**: Balanced across all types
- **Display**: Grid layout
- **Environmental Focus**: Enabled

## Troubleshooting
If you encounter issues:
1. Check that all plugin files are present in `/wp-content/plugins/environmental-content-recommendation/`
2. Verify database tables were created (check under **Dashboard** tab)
3. Clear any caching plugins you may have
4. Check WordPress error logs for any PHP errors
5. Ensure your server meets WordPress requirements

## Support
The plugin is fully self-contained with comprehensive error handling and logging. All features have been tested and are ready for production use.

---
**Plugin Status**: ✅ Ready for Activation  
**Last Updated**: June 7, 2025
