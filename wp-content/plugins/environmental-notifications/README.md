# Environmental Notifications & Messaging System

**Phase 54** - A comprehensive real-time notification system with push notifications, email preferences, in-app messaging, and notification analytics for the Environmental Platform.

## Features

### 🔔 Real-time Notifications
- **Live notification bell** with unread count badges
- **Server-Sent Events (SSE)** for real-time updates
- **Toast notifications** for immediate feedback
- **Sound notifications** (configurable)
- **Offline support** with background sync

### 📱 Push Notifications
- **Web Push API** integration
- **Service Worker** for background processing
- **VAPID protocol** support
- **Cross-browser compatibility**
- **Notification customization** with icons and actions

### 💬 In-App Messaging
- **Real-time chat interface**
- **Floating message widget**
- **Conversation threading**
- **File attachment support**
- **Read receipts and timestamps**

### 📧 Email Preferences
- **Granular preference controls**
- **Frequency settings** (immediate, daily, weekly)
- **Template customization**
- **Batch processing** for performance
- **User profile integration**

### 📊 Analytics & Tracking
- **Delivery tracking** across all channels
- **Open/click rate monitoring**
- **Performance metrics**
- **Interactive dashboards** with Chart.js
- **Export functionality**

### ⚙️ Admin Interface
- **Comprehensive dashboard** with statistics
- **Template editor** with variable insertion
- **Message management** system
- **Settings configuration**
- **Analytics visualization**

## Installation

1. **Upload** the plugin files to `/wp-content/plugins/environmental-notifications/`
2. **Activate** the plugin through the WordPress admin
3. **Configure** settings under "Notifications" menu
4. **Set up VAPID keys** for push notifications (optional)

## Requirements

- **WordPress** 5.0 or higher
- **PHP** 7.4 or higher
- **Environmental Platform Core** plugin (dependency)
- **MySQL** 5.7 or higher
- **Modern browser** with JavaScript enabled

## Database Tables

The plugin creates the following database tables:

- `wp_en_notifications` - Notification storage
- `wp_en_messages` - In-app messaging
- `wp_en_push_subscriptions` - Push notification subscriptions
- `wp_en_notification_analytics` - Analytics tracking
- `wp_en_email_preferences` - User email preferences

## Configuration

### VAPID Keys Setup (for Push Notifications)

1. Generate VAPID keys using online generators or libraries
2. Add keys in **Settings > Push Notifications**
3. Configure service worker registration

### Email Settings

1. Configure SMTP settings in WordPress
2. Set up email templates in **Templates** section
3. Configure frequency and batch processing

### Real-time Configuration

1. Enable SSE in **Settings > Real-time**
2. Configure connection timeout and retry settings
3. Set up fallback polling if needed

## Usage

### Frontend (Users)

- **Notification Bell**: Click to view recent notifications
- **Message Widget**: Access in-app messaging
- **Email Preferences**: Configure in user profile
- **Push Permissions**: Allow when prompted

### Admin (Administrators)

- **Dashboard**: Overview of notification statistics
- **All Notifications**: Manage all system notifications
- **Messages**: Monitor in-app conversations
- **Analytics**: View detailed performance metrics
- **Settings**: Configure system-wide options
- **Templates**: Create and edit notification templates

## Hooks & Filters

### Action Hooks

```php
// Environmental Platform integration
do_action('environmental_waste_reported', $data);
do_action('environmental_event_created', $data);
do_action('environmental_achievement_earned', $data);
do_action('environmental_forum_post_created', $data);
do_action('environmental_petition_signed', $data);

// Custom notification creation
do_action('en_notification_created', $notification_id, $user_id);
do_action('en_notification_sent', $notification_id, $channel);
do_action('en_message_sent', $message_id, $sender_id, $recipient_id);
```

### Filter Hooks

```php
// Notification filtering
$notification = apply_filters('en_notification_data', $notification, $user_id);
$template = apply_filters('en_notification_template', $template, $type);
$channels = apply_filters('en_notification_channels', $channels, $user_id);

// Message filtering
$message = apply_filters('en_message_data', $message, $sender_id);
$attachments = apply_filters('en_message_attachments', $attachments, $message_id);
```

## REST API Endpoints

### Notifications
- `GET /wp-json/environmental-notifications/v1/notifications` - Get user notifications
- `POST /wp-json/environmental-notifications/v1/notifications/{id}/read` - Mark as read

### Messages
- `GET /wp-json/environmental-notifications/v1/messages` - Get user messages
- `POST /wp-json/environmental-notifications/v1/messages` - Send message

### Push Notifications
- `POST /wp-json/environmental-notifications/v1/push/subscribe` - Subscribe to push

## Customization

### CSS Customization

```css
/* Notification bell styling */
.en-notification-bell {
    /* Custom styles */
}

/* Message widget styling */
.en-message-widget {
    /* Custom styles */
}

/* Toast notification styling */
.en-toast {
    /* Custom styles */
}
```

### JavaScript Events

```javascript
// Listen for new notifications
document.addEventListener('en:notification:received', function(event) {
    console.log('New notification:', event.detail);
});

// Listen for message events
document.addEventListener('en:message:received', function(event) {
    console.log('New message:', event.detail);
});
```

## Troubleshooting

### Common Issues

1. **Notifications not appearing**
   - Check if user is logged in
   - Verify JavaScript is enabled
   - Check browser console for errors

2. **Push notifications not working**
   - Verify VAPID keys are set correctly
   - Check browser permissions
   - Ensure HTTPS is enabled

3. **Real-time updates failing**
   - Check server SSE support
   - Verify network connectivity
   - Check for ad blockers

### Debug Mode

Enable debug mode by adding to `wp-config.php`:

```php
define('EN_DEBUG', true);
```

## Performance

### Optimization Features

- **Database indexing** for fast queries
- **Caching** for frequently accessed data
- **Batch processing** for bulk operations
- **Connection pooling** for real-time updates
- **Lazy loading** for admin interfaces

### Recommended Settings

- **Notification retention**: 30 days
- **Batch size**: 50 notifications
- **Rate limiting**: 10 requests/minute
- **Auto cleanup**: Daily

## Security

### Features

- **Nonce verification** for all AJAX requests
- **User capability checks** for admin functions
- **SQL injection protection** with prepared statements
- **XSS prevention** with proper escaping
- **CSRF protection** for forms

### Best Practices

- Regularly update VAPID keys
- Monitor for suspicious activity
- Use HTTPS for all communications
- Validate all user inputs

## Support

For support and documentation:

- **Plugin Repository**: Environmental Platform
- **Documentation**: Available in admin area
- **Issues**: Report via admin interface
- **Updates**: Automatic through WordPress

## Changelog

### Version 1.0.0
- Initial release with complete Phase 54 functionality
- Real-time notifications with SSE
- Push notification support
- In-app messaging system
- Email preference management
- Analytics and tracking
- Comprehensive admin interface

## License

GPL v2 or later - Compatible with WordPress licensing

---

**Environmental Notifications & Messaging System** - Enhancing user engagement through intelligent, real-time communication.
