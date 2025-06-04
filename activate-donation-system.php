<?php
/**
 * Activate Environmental Donation System Plugin
 */

// Load WordPress environment
define('WP_USE_THEMES', false);
require_once __DIR__ . '/wp-load.php';

echo "=== ACTIVATING DONATION SYSTEM PLUGIN ===\n\n";

// Check if plugin files exist
$plugin_file = 'environmental-donation-system/environmental-donation-system.php';
$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;

if (!file_exists($plugin_path)) {
    echo "❌ Plugin file not found: $plugin_path\n";
    exit(1);
}

echo "✅ Plugin file found\n";

// Check if plugin is already active
$active_plugins = get_option('active_plugins', array());

if (in_array($plugin_file, $active_plugins)) {
    echo "✅ Plugin is already active\n";
} else {
    echo "Activating plugin...\n";
    
    // Activate the plugin
    $result = activate_plugin($plugin_file);
    
    if (is_wp_error($result)) {
        echo "❌ Error activating plugin: " . $result->get_error_message() . "\n";
        exit(1);
    } else {
        echo "✅ Plugin activated successfully!\n";
    }
}

// Verify activation
$active_plugins = get_option('active_plugins', array());
if (in_array($plugin_file, $active_plugins)) {
    echo "✅ Plugin activation verified\n";
} else {
    echo "❌ Plugin activation failed\n";
    exit(1);
}

// Run database setup
if (class_exists('EDS_Database_Setup')) {
    echo "\nRunning database setup...\n";
    EDS_Database_Setup::create_tables();
    echo "✅ Database setup completed\n";
} else {
    echo "❌ Database setup class not found\n";
}

// Test core functionality
echo "\nTesting core functionality...\n";

if (class_exists('EDS_Impact_Tracker')) {
    $impact_tracker = EDS_Impact_Tracker::get_instance();
    echo "✅ Impact Tracker initialized\n";
} else {
    echo "❌ Impact Tracker class not found\n";
}

if (class_exists('EDS_Receipt_Generator')) {
    echo "✅ Receipt Generator class loaded\n";
} else {
    echo "❌ Receipt Generator class not found\n";
}

if (class_exists('EDS_Recurring_Donations')) {
    $recurring = EDS_Recurring_Donations::get_instance();
    echo "✅ Recurring Donations initialized\n";
} else {
    echo "❌ Recurring Donations class not found\n";
}

if (class_exists('EDS_Notification_System')) {
    $notifications = EDS_Notification_System::get_instance();
    echo "✅ Notification System initialized\n";
} else {
    echo "❌ Notification System class not found\n";
}

// Check post types
if (post_type_exists('donation_campaign')) {
    echo "✅ Donation Campaign post type registered\n";
} else {
    echo "❌ Donation Campaign post type not registered\n";
}

if (post_type_exists('donation_org')) {
    echo "✅ Donation Organization post type registered\n";
} else {
    echo "❌ Donation Organization post type not registered\n";
}

echo "\n=== ACTIVATION COMPLETE ===\n";
echo "Environmental Donation System is now ready for use!\n";
?>
