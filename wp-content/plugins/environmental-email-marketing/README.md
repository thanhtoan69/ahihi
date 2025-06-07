# Environmental Email Marketing Plugin Documentation

## Overview

The Environmental Email Marketing plugin is a comprehensive email marketing solution designed specifically for environmental organizations, activists, and eco-conscious businesses. It provides advanced email marketing capabilities with a focus on environmental impact tracking and sustainability metrics.

## Features

### Core Features
- **Multi-Provider Support**: Integration with Mailchimp, SendGrid, Mailgun, Amazon SES, and native WordPress email
- **Advanced Automation**: Trigger-based email sequences with environmental action responses
- **Template Engine**: Professional email templates with environmental themes
- **Analytics Dashboard**: Comprehensive tracking with environmental impact metrics
- **A/B Testing**: Built-in split testing for subject lines and content
- **Subscriber Segmentation**: Advanced segmentation based on environmental preferences and actions
- **GDPR Compliance**: Full data protection compliance with consent tracking

### Environmental Features
- **Carbon Footprint Tracking**: Monitor the environmental impact of your email campaigns
- **Eco-Scoring System**: Score subscribers based on their environmental engagement
- **Sustainability Metrics**: Track environmental actions and their impact
- **Green Campaign Templates**: Pre-designed templates for environmental campaigns
- **Action-Based Automation**: Trigger emails based on environmental actions (petitions, donations, etc.)

## Installation

1. Upload the plugin files to the `/wp-content/plugins/environmental-email-marketing` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to 'Email Marketing' in your WordPress admin menu
4. Follow the setup wizard to configure your email provider and basic settings

## Configuration

### Email Provider Setup

#### Mailchimp
1. Go to Email Marketing → Settings → Providers
2. Select Mailchimp and enter your API key
3. Choose your default list and configure sync settings

#### SendGrid
1. Go to Email Marketing → Settings → Providers
2. Select SendGrid and enter your API key
3. Configure your sender authentication settings

#### Other Providers
Similar configuration process for Mailgun, Amazon SES, and other supported providers.

### Basic Settings

#### General Settings
- **Company Information**: Set your organization's name and contact details
- **Default Sender**: Configure default from name and email address
- **Timezone**: Set your preferred timezone for scheduling
- **Language**: Choose your preferred language (affects email templates)

#### Environmental Settings
- **Carbon Tracking**: Enable/disable carbon footprint calculations
- **Eco-Scoring**: Configure the environmental scoring system
- **Impact Metrics**: Set up tracking for environmental actions
- **Sustainability Goals**: Define your organization's environmental targets

## Usage Guide

### Managing Subscribers

#### Adding Subscribers
1. Navigate to Email Marketing → Subscribers
2. Click "Add New Subscriber"
3. Fill in subscriber details and preferences
4. Set environmental preferences and interests
5. Choose lists and segments to assign

#### Importing Subscribers
1. Go to Email Marketing → Subscribers → Import
2. Upload a CSV file with subscriber data
3. Map fields to match your subscriber attributes
4. Review and confirm the import
5. Monitor the import progress

#### Segmentation
- **Environmental Preferences**: Climate change, renewable energy, sustainability
- **Engagement Level**: Highly engaged, moderately engaged, low engagement
- **Actions Taken**: Petition signers, donors, event attendees
- **Geographic**: Location-based segmentation
- **Custom Segments**: Create custom segments based on any criteria

### Creating Campaigns

#### Campaign Types
- **Regular Campaigns**: One-time email sends
- **Newsletter Campaigns**: Recurring newsletter sends
- **Automated Campaigns**: Trigger-based email sequences
- **A/B Test Campaigns**: Split test different versions

#### Campaign Builder
1. Navigate to Email Marketing → Campaigns → Add New
2. Choose campaign type and template
3. Design your email content using the drag-and-drop editor
4. Set up personalization and dynamic content
5. Configure environmental tracking and impact metrics
6. Preview and test your campaign
7. Schedule or send immediately

#### Template System
- **Default Template**: Clean, professional design
- **Newsletter Template**: Multi-section newsletter layout
- **Promotional Template**: Marketing-focused design with CTAs
- **Custom Templates**: Create your own templates
- **Environmental Themes**: Green, eco-friendly color schemes

### Automation

#### Welcome Series
Automatically send a series of welcome emails to new subscribers:
1. Immediate welcome email
2. Organization introduction (2 days later)
3. Environmental impact story (1 week later)
4. Call-to-action email (2 weeks later)

#### Action-Based Automation
Trigger emails based on subscriber actions:
- **Petition Signed**: Thank you email with impact metrics
- **Donation Made**: Appreciation email with carbon offset information
- **Event Attended**: Follow-up email with related content
- **Quiz Completed**: Results email with personalized recommendations

#### Seasonal Campaigns
Automatically send campaigns for environmental events:
- Earth Day campaigns
- World Environment Day
- Climate Action Week
- Sustainability Month

### Analytics and Reporting

#### Campaign Analytics
- **Open Rates**: Track email open rates by campaign and segment
- **Click Rates**: Monitor link clicks and engagement
- **Conversion Rates**: Track goal completions and actions taken
- **Unsubscribe Rates**: Monitor list health and content performance
- **Environmental Impact**: Carbon footprint and sustainability metrics

#### Subscriber Analytics
- **Engagement Scores**: Individual subscriber engagement levels
- **Environmental Scores**: Eco-engagement and action history
- **Preference Tracking**: Monitor preference changes over time
- **Lifetime Value**: Track long-term subscriber value

#### A/B Testing
- **Subject Line Testing**: Test different subject lines
- **Content Testing**: Test different email content versions
- **Send Time Testing**: Find optimal send times
- **Template Testing**: Compare different email designs

## Advanced Features

### REST API
The plugin provides a comprehensive REST API for external integrations:

#### Endpoints
- `GET /wp-json/eem/v1/subscribers` - List subscribers
- `POST /wp-json/eem/v1/subscribers` - Add subscriber
- `GET /wp-json/eem/v1/campaigns` - List campaigns
- `POST /wp-json/eem/v1/campaigns` - Create campaign
- `GET /wp-json/eem/v1/analytics` - Get analytics data

#### Authentication
API requests require authentication using:
- WordPress application passwords
- JWT tokens (if JWT plugin is installed)
- API keys (configured in settings)

### Webhooks
Set up webhooks to receive real-time notifications:
- Subscriber added/updated
- Campaign sent
- Email opened/clicked
- Unsubscribe events
- Environmental actions taken

### Integrations

#### WooCommerce
- Sync customers as subscribers
- Trigger emails based on purchase behavior
- Track environmental impact of purchases
- Send eco-friendly product recommendations

#### Contact Form 7
- Add subscribers from contact forms
- Trigger automation sequences
- Capture environmental preferences

#### Event Plugins
- Sync event attendees
- Send event-related campaigns
- Track environmental impact of events

## Troubleshooting

### Common Issues

#### Email Delivery Problems
1. Check your email provider settings
2. Verify SPF and DKIM records
3. Monitor sender reputation
4. Review bounce and spam reports

#### Database Issues
1. Run the system status check
2. Verify table creation and permissions
3. Check database connection
4. Review error logs

#### Performance Issues
1. Monitor memory usage
2. Optimize database queries
3. Enable object caching
4. Review server resources

### Debug Mode
Enable debug mode to troubleshoot issues:
1. Add `define('WP_DEBUG', true);` to wp-config.php
2. Navigate to Email Marketing → System Status
3. Run diagnostic tests
4. Review error logs and system status

### Support
- **Documentation**: Complete guides and tutorials
- **Knowledge Base**: Common questions and solutions
- **Support Forums**: Community support and discussions
- **Professional Support**: Premium support options available

## Best Practices

### Email Deliverability
- Maintain clean subscriber lists
- Use double opt-in for new subscribers
- Monitor bounce rates and remove invalid addresses
- Authenticate your sending domain
- Follow email marketing regulations (CAN-SPAM, GDPR)

### Environmental Impact
- Track and report on environmental metrics
- Use carbon offset calculations
- Promote sustainable actions
- Measure and communicate impact
- Set and track environmental goals

### Content Strategy
- Personalize email content based on preferences
- Use environmental storytelling
- Include clear calls-to-action
- Test different content approaches
- Monitor engagement and adjust strategy

### List Management
- Segment subscribers based on interests and actions
- Regularly clean and update subscriber data
- Respect unsubscribe requests
- Maintain preference centers
- Monitor list health metrics

## API Reference

### Subscriber Management
```php
// Add subscriber
$subscriber_id = eem_add_subscriber(array(
    'email' => 'user@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'preferences' => array('climate_change' => 1)
));

// Update preferences
eem_update_subscriber_preferences($subscriber_id, array(
    'renewable_energy' => 1,
    'frequency' => 'weekly'
));

// Get subscriber
$subscriber = eem_get_subscriber($subscriber_id);
```

### Campaign Management
```php
// Create campaign
$campaign_id = eem_create_campaign(array(
    'name' => 'Earth Day Campaign',
    'subject' => 'Join us for Earth Day!',
    'content' => '<p>Celebrate Earth Day with us!</p>',
    'template_id' => 1
));

// Send campaign
eem_send_campaign($campaign_id, array(
    'segment_id' => 123,
    'send_time' => '2024-04-22 09:00:00'
));
```

### Analytics
```php
// Get campaign analytics
$analytics = eem_get_campaign_analytics($campaign_id);

// Track custom event
eem_track_event('petition_signed', array(
    'subscriber_id' => $subscriber_id,
    'petition_id' => 456,
    'carbon_impact' => 2.5 // kg CO2 saved
));
```

## Changelog

### Version 1.0.0
- Initial release
- Multi-provider email integration
- Environmental tracking system
- Automation engine
- Analytics dashboard
- REST API
- Testing framework

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by the Environmental Platform Team for organizations working to protect our planet.
