<?php
/**
 * Environmental Notifications & Messaging System - Uninstall Script
 * 
 * This file is executed when the plugin is deleted via WordPress admin
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin data on uninstall
 */
function environmental_notifications_uninstall() {
    global $wpdb;

    // Remove database tables
    $tables = array(
        $wpdb->prefix . 'en_notifications',
        $wpdb->prefix . 'en_messages',
        $wpdb->prefix . 'en_push_subscriptions',
        $wpdb->prefix . 'en_notification_analytics',
        $wpdb->prefix . 'en_email_preferences'
    );

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }

    // Remove plugin options
    $options = array(
        'en_real_time_enabled',
        'en_push_notifications_enabled',
        'en_email_notifications_enabled',
        'en_in_app_notifications_enabled',
        'en_notification_retention_days',
        'en_max_notifications_per_user',
        'en_batch_size',
        'en_vapid_public_key',
        'en_vapid_private_key',
        'en_notification_sound_enabled',
        'en_auto_cleanup_enabled',
        'en_analytics_enabled',
        'en_rate_limiting_enabled',
        'en_rate_limit_per_minute'
    );

    foreach ($options as $option) {
        delete_option($option);
    }

    // Remove user meta data
    $wpdb->delete(
        $wpdb->usermeta,
        array('meta_key' => 'en_email_preferences')
    );

    $wpdb->delete(
        $wpdb->usermeta,
        array('meta_key' => 'en_notification_settings')
    );

    // Clear scheduled events
    wp_clear_scheduled_hook('en_cleanup_notifications');

    // Remove transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_en_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_en_%'");

    // Flush rewrite rules
    flush_rewrite_rules();
}

// Execute uninstall
environmental_notifications_uninstall();
