<?php
/**
 * Simple Donation System Check
 */

// Load WordPress environment
define('WP_USE_THEMES', false);
require_once __DIR__ . '/wp-load.php';

echo "=== DONATION SYSTEM SIMPLE CHECK ===\n\n";

// Check if plugin is active
$active_plugins = get_option('active_plugins', array());
$plugin_file = 'environmental-donation-system/environmental-donation-system.php';

if (in_array($plugin_file, $active_plugins)) {
    echo "✅ Environmental Donation System plugin is ACTIVE\n";
} else {
    echo "❌ Environmental Donation System plugin is NOT ACTIVE\n";
    echo "Available plugins:\n";
    foreach ($active_plugins as $plugin) {
        echo "  - $plugin\n";
    }
}

// Check if main class exists
if (class_exists('EnvironmentalDonationSystem')) {
    echo "✅ Main plugin class loaded\n";
} else {
    echo "❌ Main plugin class not loaded\n";
}

// Check core classes
$classes = array(
    'EDS_Impact_Tracker',
    'EDS_Receipt_Generator', 
    'EDS_Recurring_Donations',
    'EDS_Notification_System'
);

echo "\nCore Classes:\n";
foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "✅ $class\n";
    } else {
        echo "❌ $class\n";
    }
}

// Check database tables
global $wpdb;
$tables = array(
    'donations',
    'donation_campaigns',
    'donation_organizations', 
    'donation_subscriptions',
    'donation_tax_receipts'
);

echo "\nDatabase Tables:\n";
foreach ($tables as $table) {
    $table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if ($exists) {
        echo "✅ $table\n";
    } else {
        echo "❌ $table\n";
    }
}

echo "\n=== CHECK COMPLETE ===\n";
?>
