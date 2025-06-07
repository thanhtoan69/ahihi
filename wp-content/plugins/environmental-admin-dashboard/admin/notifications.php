<?php
/**
 * Environmental Admin Dashboard - Notification System
 *
 * @package Environmental_Admin_Dashboard
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'environmental-admin-dashboard'));
}

// Handle notification actions
if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'environmental_notifications')) {
    $action = sanitize_text_field($_POST['action']);
    
    switch ($action) {
        case 'send_notification':
            $result = environmental_send_notification($_POST);
            break;
        case 'delete_notification':
            $result = environmental_delete_notification(intval($_POST['notification_id']));
            break;
        case 'mark_read':
            $result = environmental_mark_notification_read(intval($_POST['notification_id']));
            break;
        case 'bulk_delete':
            $ids = array_map('intval', $_POST['notification_ids']);
            $result = environmental_bulk_delete_notifications($ids);
            break;
    }
    
    if (isset($result)) {
        if ($result['success']) {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
            });
        }
    }
}

// Get notifications data
$notifications = environmental_get_notifications();
$notification_stats = environmental_get_notification_stats();
$users = environmental_get_platform_users();
?>

<div class="wrap environmental-notifications">
    <h1><?php _e('Notification System', 'environmental-admin-dashboard'); ?></h1>
    
    <div class="notification-tabs">
        <button class="tab-button active" data-tab="send"><?php _e('Send Notification', 'environmental-admin-dashboard'); ?></button>
        <button class="tab-button" data-tab="manage"><?php _e('Manage Notifications', 'environmental-admin-dashboard'); ?></button>
        <button class="tab-button" data-tab="templates"><?php _e('Templates', 'environmental-admin-dashboard'); ?></button>
        <button class="tab-button" data-tab="settings"><?php _e('Settings', 'environmental-admin-dashboard'); ?></button>
    </div>
    
    <!-- Send Notification Tab -->
    <div class="tab-content" id="send-tab">
        <div class="notification-stats">
            <div class="stat-card">
                <h3><?php echo number_format($notification_stats['total_sent']); ?></h3>
                <p><?php _e('Total Sent', 'environmental-admin-dashboard'); ?></p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($notification_stats['unread']); ?></h3>
                <p><?php _e('Unread', 'environmental-admin-dashboard'); ?></p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($notification_stats['today']); ?></h3>
                <p><?php _e('Sent Today', 'environmental-admin-dashboard'); ?></p>
            </div>
            <div class="stat-card">
                <h3><?php echo $notification_stats['engagement_rate']; ?>%</h3>
                <p><?php _e('Engagement Rate', 'environmental-admin-dashboard'); ?></p>
            </div>
        </div>
        
        <form method="post" action="" class="notification-form">
            <?php wp_nonce_field('environmental_notifications'); ?>
            <input type="hidden" name="action" value="send_notification">
            
            <div class="form-grid">
                <div class="form-section">
                    <h3><?php _e('Recipients', 'environmental-admin-dashboard'); ?></h3>
                    
                    <div class="recipient-options">
                        <label class="recipient-option">
                            <input type="radio" name="recipient_type" value="all" checked>
                            <?php _e('All Users', 'environmental-admin-dashboard'); ?>
                        </label>
                        <label class="recipient-option">
                            <input type="radio" name="recipient_type" value="role">
                            <?php _e('By Role', 'environmental-admin-dashboard'); ?>
                        </label>
                        <label class="recipient-option">
                            <input type="radio" name="recipient_type" value="specific">
                            <?php _e('Specific Users', 'environmental-admin-dashboard'); ?>
                        </label>
                        <label class="recipient-option">
                            <input type="radio" name="recipient_type" value="active">
                            <?php _e('Active Users Only', 'environmental-admin-dashboard'); ?>
                        </label>
                    </div>
                    
                    <div class="recipient-details" id="role-details" style="display: none;">
                        <select name="user_role" class="full-width">
                            <option value=""><?php _e('Select Role', 'environmental-admin-dashboard'); ?></option>
                            <option value="subscriber"><?php _e('Subscribers', 'environmental-admin-dashboard'); ?></option>
                            <option value="contributor"><?php _e('Contributors', 'environmental-admin-dashboard'); ?></option>
                            <option value="author"><?php _e('Authors', 'environmental-admin-dashboard'); ?></option>
                            <option value="editor"><?php _e('Editors', 'environmental-admin-dashboard'); ?></option>
                        </select>
                    </div>
                    
                    <div class="recipient-details" id="specific-details" style="display: none;">
                        <select name="specific_users[]" multiple class="full-width user-select">
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo esc_attr($user->ID); ?>">
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small><?php _e('Hold Ctrl/Cmd to select multiple users', 'environmental-admin-dashboard'); ?></small>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><?php _e('Notification Content', 'environmental-admin-dashboard'); ?></h3>
                    
                    <div class="form-field">
                        <label for="notification_type"><?php _e('Type', 'environmental-admin-dashboard'); ?></label>
                        <select name="notification_type" id="notification_type" class="full-width">
                            <option value="info"><?php _e('Information', 'environmental-admin-dashboard'); ?></option>
                            <option value="success"><?php _e('Success', 'environmental-admin-dashboard'); ?></option>
                            <option value="warning"><?php _e('Warning', 'environmental-admin-dashboard'); ?></option>
                            <option value="error"><?php _e('Error', 'environmental-admin-dashboard'); ?></option>
                            <option value="reminder"><?php _e('Reminder', 'environmental-admin-dashboard'); ?></option>
                            <option value="achievement"><?php _e('Achievement', 'environmental-admin-dashboard'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-field">
                        <label for="notification_title"><?php _e('Title', 'environmental-admin-dashboard'); ?></label>
                        <input type="text" name="notification_title" id="notification_title" class="full-width" required>
                    </div>
                    
                    <div class="form-field">
                        <label for="notification_message"><?php _e('Message', 'environmental-admin-dashboard'); ?></label>
                        <textarea name="notification_message" id="notification_message" rows="5" class="full-width" required></textarea>
                    </div>
                    
                    <div class="form-field">
                        <label for="action_url"><?php _e('Action URL (Optional)', 'environmental-admin-dashboard'); ?></label>
                        <input type="url" name="action_url" id="action_url" class="full-width">
                        <small><?php _e('URL to redirect users when they click the notification', 'environmental-admin-dashboard'); ?></small>
                    </div>
                    
                    <div class="form-field">
                        <label for="action_text"><?php _e('Action Text (Optional)', 'environmental-admin-dashboard'); ?></label>
                        <input type="text" name="action_text" id="action_text" class="full-width" placeholder="<?php _e('e.g., View Details, Take Action', 'environmental-admin-dashboard'); ?>">
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><?php _e('Delivery Options', 'environmental-admin-dashboard'); ?></h3>
                    
                    <div class="form-field">
                        <label>
                            <input type="checkbox" name="send_email" value="1">
                            <?php _e('Send Email Notification', 'environmental-admin-dashboard'); ?>
                        </label>
                    </div>
                    
                    <div class="form-field">
                        <label>
                            <input type="checkbox" name="send_push" value="1">
                            <?php _e('Send Push Notification', 'environmental-admin-dashboard'); ?>
                        </label>
                    </div>
                    
                    <div class="form-field">
                        <label for="priority"><?php _e('Priority', 'environmental-admin-dashboard'); ?></label>
                        <select name="priority" id="priority" class="full-width">
                            <option value="low"><?php _e('Low', 'environmental-admin-dashboard'); ?></option>
                            <option value="normal" selected><?php _e('Normal', 'environmental-admin-dashboard'); ?></option>
                            <option value="high"><?php _e('High', 'environmental-admin-dashboard'); ?></option>
                            <option value="urgent"><?php _e('Urgent', 'environmental-admin-dashboard'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-field">
                        <label for="schedule_date"><?php _e('Schedule Date (Optional)', 'environmental-admin-dashboard'); ?></label>
                        <input type="datetime-local" name="schedule_date" id="schedule_date" class="full-width">
                        <small><?php _e('Leave empty to send immediately', 'environmental-admin-dashboard'); ?></small>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="button button-primary button-large">
                    <?php _e('Send Notification', 'environmental-admin-dashboard'); ?>
                </button>
                <button type="button" class="button button-secondary preview-notification">
                    <?php _e('Preview', 'environmental-admin-dashboard'); ?>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Manage Notifications Tab -->
    <div class="tab-content" id="manage-tab" style="display: none;">
        <div class="notifications-toolbar">
            <div class="search-notifications">
                <input type="text" placeholder="<?php _e('Search notifications...', 'environmental-admin-dashboard'); ?>" class="notification-search">
                <select class="notification-filter">
                    <option value=""><?php _e('All Types', 'environmental-admin-dashboard'); ?></option>
                    <option value="info"><?php _e('Information', 'environmental-admin-dashboard'); ?></option>
                    <option value="success"><?php _e('Success', 'environmental-admin-dashboard'); ?></option>
                    <option value="warning"><?php _e('Warning', 'environmental-admin-dashboard'); ?></option>
                    <option value="error"><?php _e('Error', 'environmental-admin-dashboard'); ?></option>
                    <option value="reminder"><?php _e('Reminder', 'environmental-admin-dashboard'); ?></option>
                    <option value="achievement"><?php _e('Achievement', 'environmental-admin-dashboard'); ?></option>
                </select>
            </div>
            
            <div class="bulk-actions">
                <select class="bulk-action-select">
                    <option value=""><?php _e('Bulk Actions', 'environmental-admin-dashboard'); ?></option>
                    <option value="delete"><?php _e('Delete', 'environmental-admin-dashboard'); ?></option>
                    <option value="mark_read"><?php _e('Mark as Read', 'environmental-admin-dashboard'); ?></option>
                </select>
                <button type="button" class="button apply-bulk-action"><?php _e('Apply', 'environmental-admin-dashboard'); ?></button>
            </div>
        </div>
        
        <div class="notifications-list">
            <?php if (!empty($notifications)): ?>
                <form id="notifications-form" method="post">
                    <?php wp_nonce_field('environmental_notifications'); ?>
                    <input type="hidden" name="action" value="bulk_delete">
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column">
                                    <input type="checkbox" class="select-all-notifications">
                                </td>
                                <th class="manage-column"><?php _e('Title', 'environmental-admin-dashboard'); ?></th>
                                <th class="manage-column"><?php _e('Type', 'environmental-admin-dashboard'); ?></th>
                                <th class="manage-column"><?php _e('Recipients', 'environmental-admin-dashboard'); ?></th>
                                <th class="manage-column"><?php _e('Sent', 'environmental-admin-dashboard'); ?></th>
                                <th class="manage-column"><?php _e('Status', 'environmental-admin-dashboard'); ?></th>
                                <th class="manage-column"><?php _e('Actions', 'environmental-admin-dashboard'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $notification): ?>
                                <tr class="notification-row" data-type="<?php echo esc_attr($notification->type); ?>">
                                    <th class="check-column">
                                        <input type="checkbox" name="notification_ids[]" value="<?php echo esc_attr($notification->id); ?>" class="notification-checkbox">
                                    </th>
                                    <td class="notification-title">
                                        <strong><?php echo esc_html($notification->title); ?></strong>
                                        <div class="notification-message"><?php echo esc_html(wp_trim_words($notification->message, 15)); ?></div>
                                    </td>
                                    <td>
                                        <span class="notification-type type-<?php echo esc_attr($notification->type); ?>">
                                            <?php echo esc_html(ucfirst($notification->type)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($notification->recipient_count); ?></td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($notification->created_at))); ?></td>
                                    <td>
                                        <span class="notification-status status-<?php echo esc_attr($notification->status); ?>">
                                            <?php echo esc_html(ucfirst($notification->status)); ?>
                                        </span>
                                    </td>
                                    <td class="notification-actions">
                                        <button type="button" class="button button-small view-notification" data-id="<?php echo esc_attr($notification->id); ?>">
                                            <?php _e('View', 'environmental-admin-dashboard'); ?>
                                        </button>
                                        <button type="button" class="button button-small button-link-delete delete-notification" data-id="<?php echo esc_attr($notification->id); ?>">
                                            <?php _e('Delete', 'environmental-admin-dashboard'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            <?php else: ?>
                <div class="no-notifications">
                    <p><?php _e('No notifications found.', 'environmental-admin-dashboard'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Templates Tab -->
    <div class="tab-content" id="templates-tab" style="display: none;">
        <div class="templates-section">
            <h3><?php _e('Notification Templates', 'environmental-admin-dashboard'); ?></h3>
            <p><?php _e('Create and manage reusable notification templates.', 'environmental-admin-dashboard'); ?></p>
            
            <div class="template-grid">
                <div class="template-card">
                    <h4><?php _e('Activity Reminder', 'environmental-admin-dashboard'); ?></h4>
                    <p><?php _e('Remind users about pending activities', 'environmental-admin-dashboard'); ?></p>
                    <button type="button" class="button use-template" data-template="activity_reminder">
                        <?php _e('Use Template', 'environmental-admin-dashboard'); ?>
                    </button>
                </div>
                
                <div class="template-card">
                    <h4><?php _e('Goal Achievement', 'environmental-admin-dashboard'); ?></h4>
                    <p><?php _e('Congratulate users on goal completion', 'environmental-admin-dashboard'); ?></p>
                    <button type="button" class="button use-template" data-template="goal_achievement">
                        <?php _e('Use Template', 'environmental-admin-dashboard'); ?>
                    </button>
                </div>
                
                <div class="template-card">
                    <h4><?php _e('Weekly Summary', 'environmental-admin-dashboard'); ?></h4>
                    <p><?php _e('Weekly environmental impact summary', 'environmental-admin-dashboard'); ?></p>
                    <button type="button" class="button use-template" data-template="weekly_summary">
                        <?php _e('Use Template', 'environmental-admin-dashboard'); ?>
                    </button>
                </div>
                
                <div class="template-card">
                    <h4><?php _e('New Feature', 'environmental-admin-dashboard'); ?></h4>
                    <p><?php _e('Announce new platform features', 'environmental-admin-dashboard'); ?></p>
                    <button type="button" class="button use-template" data-template="new_feature">
                        <?php _e('Use Template', 'environmental-admin-dashboard'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Settings Tab -->
    <div class="tab-content" id="settings-tab" style="display: none;">
        <form method="post" action="" class="notification-settings-form">
            <?php wp_nonce_field('environmental_notifications'); ?>
            <input type="hidden" name="action" value="save_settings">
            
            <h3><?php _e('Email Settings', 'environmental-admin-dashboard'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Enable Email Notifications', 'environmental-admin-dashboard'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="email_enabled" value="1" <?php checked(get_option('environmental_email_enabled', 1)); ?>>
                            <?php _e('Send email notifications to users', 'environmental-admin-dashboard'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('From Email', 'environmental-admin-dashboard'); ?></th>
                    <td>
                        <input type="email" name="from_email" value="<?php echo esc_attr(get_option('environmental_from_email', get_option('admin_email'))); ?>" class="regular-text">
                        <p class="description"><?php _e('Email address notifications are sent from', 'environmental-admin-dashboard'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('From Name', 'environmental-admin-dashboard'); ?></th>
                    <td>
                        <input type="text" name="from_name" value="<?php echo esc_attr(get_option('environmental_from_name', get_bloginfo('name'))); ?>" class="regular-text">
                        <p class="description"><?php _e('Name notifications are sent from', 'environmental-admin-dashboard'); ?></p>
                    </td>
                </tr>
            </table>
            
            <h3><?php _e('Push Notification Settings', 'environmental-admin-dashboard'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Enable Push Notifications', 'environmental-admin-dashboard'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="push_enabled" value="1" <?php checked(get_option('environmental_push_enabled', 0)); ?>>
                            <?php _e('Send push notifications to browsers', 'environmental-admin-dashboard'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <h3><?php _e('Automatic Notifications', 'environmental-admin-dashboard'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Activity Reminders', 'environmental-admin-dashboard'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_activity_reminders" value="1" <?php checked(get_option('environmental_auto_activity_reminders', 1)); ?>>
                            <?php _e('Send automatic reminders for pending activities', 'environmental-admin-dashboard'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Goal Achievements', 'environmental-admin-dashboard'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_goal_achievements" value="1" <?php checked(get_option('environmental_auto_goal_achievements', 1)); ?>>
                            <?php _e('Send automatic notifications for goal completions', 'environmental-admin-dashboard'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Weekly Summaries', 'environmental-admin-dashboard'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_weekly_summaries" value="1" <?php checked(get_option('environmental_auto_weekly_summaries', 1)); ?>>
                            <?php _e('Send weekly environmental impact summaries', 'environmental-admin-dashboard'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Save Settings', 'environmental-admin-dashboard'); ?></button>
            </p>
        </form>
    </div>
    
    <!-- Preview Modal -->
    <div id="notification-preview-modal" class="notification-modal" style="display: none;">
        <div class="notification-modal-content">
            <div class="notification-modal-header">
                <h3><?php _e('Notification Preview', 'environmental-admin-dashboard'); ?></h3>
                <button type="button" class="notification-modal-close">&times;</button>
            </div>
            
            <div class="notification-modal-body">
                <div class="notification-preview">
                    <div class="preview-notification">
                        <div class="notification-icon"></div>
                        <div class="notification-content">
                            <div class="notification-title-preview"></div>
                            <div class="notification-message-preview"></div>
                            <div class="notification-action-preview" style="display: none;">
                                <button type="button" class="notification-action-btn"></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.environmental-notifications {
    max-width: 1200px;
}

.notification-tabs {
    margin: 20px 0;
    border-bottom: 1px solid #ccd0d4;
}

.tab-button {
    background: none;
    border: none;
    padding: 12px 20px;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    font-size: 14px;
    font-weight: 500;
}

.tab-button.active {
    border-bottom-color: #0073aa;
    color: #0073aa;
}

.tab-content {
    padding: 20px 0;
}

.notification-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 6px;
    text-align: center;
}

.stat-card h3 {
    font-size: 32px;
    font-weight: 600;
    margin: 0 0 10px 0;
    color: #23282d;
}

.stat-card p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 300px;
    gap: 30px;
    margin-bottom: 30px;
}

.form-section {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 6px;
}

.form-section h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #e1e1e1;
}

.recipient-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 15px;
}

.recipient-option {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    cursor: pointer;
}

.recipient-details {
    margin-top: 15px;
}

.form-field {
    margin-bottom: 15px;
}

.form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.full-width {
    width: 100%;
}

.user-select {
    height: 150px;
}

.form-actions {
    text-align: center;
}

.button-large {
    padding: 12px 24px;
    font-size: 16px;
}

.notifications-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #e1e1e1;
    border-radius: 6px;
}

.search-notifications {
    display: flex;
    gap: 10px;
}

.notification-search {
    min-width: 250px;
}

.bulk-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.notification-type {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    text-transform: uppercase;
    font-weight: 500;
}

.type-info { background: #d1ecf1; color: #0c5460; }
.type-success { background: #d4edda; color: #155724; }
.type-warning { background: #fff3cd; color: #856404; }
.type-error { background: #f8d7da; color: #721c24; }
.type-reminder { background: #e2e3e5; color: #383d41; }
.type-achievement { background: #d1ecf1; color: #0c5460; }

.notification-status {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    text-transform: uppercase;
}

.status-sent { background: #d4edda; color: #155724; }
.status-pending { background: #fff3cd; color: #856404; }
.status-failed { background: #f8d7da; color: #721c24; }

.notification-message {
    color: #666;
    font-size: 12px;
    margin-top: 4px;
}

.notification-actions {
    white-space: nowrap;
}

.template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.template-card {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 6px;
    text-align: center;
}

.template-card h4 {
    margin-top: 0;
}

.notification-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-modal-content {
    background: #fff;
    border-radius: 6px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow: hidden;
}

.notification-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e1e1e1;
}

.notification-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
}

.notification-modal-body {
    padding: 20px;
}

.preview-notification {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #e1e1e1;
    border-radius: 6px;
    border-left: 4px solid #0073aa;
}

.notification-icon {
    width: 24px;
    height: 24px;
    background: #0073aa;
    border-radius: 50%;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
}

.notification-title-preview {
    font-weight: 600;
    margin-bottom: 5px;
}

.notification-message-preview {
    color: #666;
    margin-bottom: 10px;
}

.notification-action-btn {
    background: #0073aa;
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .notifications-toolbar {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .search-notifications,
    .bulk-actions {
        justify-content: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab functionality
    $('.tab-button').on('click', function() {
        const tabId = $(this).data('tab');
        
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        
        $('.tab-content').hide();
        $(`#${tabId}-tab`).show();
    });
    
    // Recipient type change
    $('input[name="recipient_type"]').on('change', function() {
        const value = $(this).val();
        $('.recipient-details').hide();
        
        if (value === 'role') {
            $('#role-details').show();
        } else if (value === 'specific') {
            $('#specific-details').show();
        }
    });
    
    // Preview notification
    $('.preview-notification').on('click', function() {
        const title = $('#notification_title').val();
        const message = $('#notification_message').val();
        const actionText = $('#action_text').val();
        const type = $('#notification_type').val();
        
        if (!title || !message) {
            alert('<?php _e('Please fill in title and message', 'environmental-admin-dashboard'); ?>');
            return;
        }
        
        // Update preview content
        $('.notification-title-preview').text(title);
        $('.notification-message-preview').text(message);
        
        if (actionText) {
            $('.notification-action-preview').show();
            $('.notification-action-btn').text(actionText);
        } else {
            $('.notification-action-preview').hide();
        }
        
        // Update icon color based on type
        const colors = {
            info: '#17a2b8',
            success: '#28a745',
            warning: '#ffc107',
            error: '#dc3545',
            reminder: '#6c757d',
            achievement: '#007bff'
        };
        
        $('.notification-icon').css('background-color', colors[type] || '#0073aa');
        $('.preview-notification').css('border-left-color', colors[type] || '#0073aa');
        
        $('#notification-preview-modal').show();
    });
    
    // Close modal
    $('.notification-modal-close').on('click', function() {
        $('.notification-modal').hide();
    });
    
    // Select all notifications
    $('.select-all-notifications').on('change', function() {
        $('.notification-checkbox').prop('checked', $(this).is(':checked'));
    });
    
    // Search and filter notifications
    $('.notification-search').on('input', function() {
        const search = $(this).val().toLowerCase();
        filterNotifications();
    });
    
    $('.notification-filter').on('change', function() {
        filterNotifications();
    });
    
    function filterNotifications() {
        const search = $('.notification-search').val().toLowerCase();
        const filter = $('.notification-filter').val();
        
        $('.notification-row').each(function() {
            const row = $(this);
            const title = row.find('.notification-title').text().toLowerCase();
            const type = row.data('type');
            
            let show = true;
            
            if (search && !title.includes(search)) {
                show = false;
            }
            
            if (filter && type !== filter) {
                show = false;
            }
            
            row.toggle(show);
        });
    }
    
    // Bulk actions
    $('.apply-bulk-action').on('click', function() {
        const action = $('.bulk-action-select').val();
        const selected = $('.notification-checkbox:checked');
        
        if (!action) {
            alert('<?php _e('Please select an action', 'environmental-admin-dashboard'); ?>');
            return;
        }
        
        if (selected.length === 0) {
            alert('<?php _e('Please select notifications', 'environmental-admin-dashboard'); ?>');
            return;
        }
        
        if (confirm(`<?php _e('Are you sure you want to perform this action on', 'environmental-admin-dashboard'); ?> ${selected.length} <?php _e('notifications?', 'environmental-admin-dashboard'); ?>`)) {
            $('#notifications-form').submit();
        }
    });
    
    // Use template
    $('.use-template').on('click', function() {
        const template = $(this).data('template');
        
        // Switch to send tab
        $('.tab-button[data-tab="send"]').click();
        
        // Load template data
        loadTemplate(template);
    });
    
    function loadTemplate(templateName) {
        const templates = {
            activity_reminder: {
                title: '<?php _e('Activity Reminder', 'environmental-admin-dashboard'); ?>',
                message: '<?php _e('Don\'t forget to complete your environmental activities! Your contribution makes a difference.', 'environmental-admin-dashboard'); ?>',
                type: 'reminder',
                action_text: '<?php _e('View Activities', 'environmental-admin-dashboard'); ?>'
            },
            goal_achievement: {
                title: '<?php _e('Congratulations!', 'environmental-admin-dashboard'); ?>',
                message: '<?php _e('You\'ve successfully completed your environmental goal! Keep up the great work.', 'environmental-admin-dashboard'); ?>',
                type: 'achievement',
                action_text: '<?php _e('View Achievement', 'environmental-admin-dashboard'); ?>'
            },
            weekly_summary: {
                title: '<?php _e('Weekly Environmental Impact Summary', 'environmental-admin-dashboard'); ?>',
                message: '<?php _e('Here\'s your weekly environmental impact summary. See how you\'re making a difference!', 'environmental-admin-dashboard'); ?>',
                type: 'info',
                action_text: '<?php _e('View Report', 'environmental-admin-dashboard'); ?>'
            },
            new_feature: {
                title: '<?php _e('New Feature Available!', 'environmental-admin-dashboard'); ?>',
                message: '<?php _e('We\'ve added new features to help you track your environmental impact better. Check them out!', 'environmental-admin-dashboard'); ?>',
                type: 'info',
                action_text: '<?php _e('Explore Features', 'environmental-admin-dashboard'); ?>'
            }
        };
        
        const template = templates[templateName];
        if (template) {
            $('#notification_title').val(template.title);
            $('#notification_message').val(template.message);
            $('#notification_type').val(template.type);
            $('#action_text').val(template.action_text);
        }
    }
    
    // Delete single notification
    $('.delete-notification').on('click', function() {
        const id = $(this).data('id');
        
        if (confirm('<?php _e('Are you sure you want to delete this notification?', 'environmental-admin-dashboard'); ?>')) {
            const form = $('<form method="post">')
                .append('<?php wp_nonce_field('environmental_notifications', '_wpnonce', true, false); ?>')
                .append('<input type="hidden" name="action" value="delete_notification">')
                .append(`<input type="hidden" name="notification_id" value="${id}">`);
            
            $('body').append(form);
            form.submit();
        }
    });
});
</script>
