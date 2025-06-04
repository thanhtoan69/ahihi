<?php
/**
 * Quick Petition System Test
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PETITION SYSTEM QUICK TEST ===\n";

// Load WordPress
define('WP_USE_THEMES', false);
require_once('wp-load.php');

// Test 1: Plugin Active
echo "1. Plugin Status:\n";
if (is_plugin_active('environmental-platform-petitions/environmental-platform-petitions.php')) {
    echo "   ✅ Environmental Platform Petitions is active\n";
} else {
    echo "   ❌ Environmental Platform Petitions is not active\n";
}

// Test 2: Classes Available
echo "\n2. Core Classes:\n";
$classes = [
    'EPP_Database',
    'EPP_Signature_Manager', 
    'EPP_Verification_System',
    'EPP_Campaign_Manager',
    'EPP_Analytics',
    'EPP_Admin_Dashboard'
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "   ✅ {$class} class loaded\n";
    } else {
        echo "   ❌ {$class} class not found\n";
    }
}

// Test 3: Database Tables
echo "\n3. Database Tables:\n";
global $wpdb;
$tables = [
    'petition_signatures',
    'petition_analytics', 
    'petition_milestones',
    'petition_shares',
    'petition_campaigns',
    'petition_campaign_updates'
];

foreach ($tables as $table) {
    $table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
    if ($exists) {
        echo "   ✅ {$table} table exists\n";
    } else {
        echo "   ❌ {$table} table missing\n";
    }
}

// Test 4: Post Types
echo "\n4. Post Types:\n";
if (post_type_exists('env_petition')) {
    echo "   ✅ env_petition post type registered\n";
} else {
    echo "   ❌ env_petition post type not found\n";
}

// Test 5: Shortcodes
echo "\n5. Shortcodes:\n";
$shortcodes = ['petition_signature_form', 'petition_progress', 'petition_share'];
foreach ($shortcodes as $shortcode) {
    if (shortcode_exists($shortcode)) {
        echo "   ✅ [{$shortcode}] shortcode registered\n";
    } else {
        echo "   ❌ [{$shortcode}] shortcode missing\n";
    }
}

// Test 6: File Structure
echo "\n6. Key Files:\n";
$files = [
    'wp-content/plugins/environmental-platform-petitions/environmental-platform-petitions.php',
    'wp-content/plugins/environmental-platform-petitions/includes/class-database.php',
    'wp-content/plugins/environmental-platform-petitions/assets/js/frontend.js',
    'wp-content/plugins/environmental-platform-petitions/assets/css/frontend.css'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "   ✅ {$file} exists\n";
    } else {
        echo "   ❌ {$file} missing\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
?>
