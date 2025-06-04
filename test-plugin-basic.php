<?php
/**
 * Basic Plugin Functionality Test
 * Tests core WordPress and plugin integration
 */

// Include WordPress
require_once('wp-config.php');
require_once('wp-blog-header.php');

// Test basic WordPress functionality
echo "<h1>Phase 31 Plugin Basic Test</h1>";

// Check if WordPress is loaded
if (function_exists('wp_head')) {
    echo "<p>✅ WordPress is loaded successfully</p>";
} else {
    echo "<p>❌ WordPress failed to load</p>";
    exit;
}

// Check if our plugin is active
if (function_exists('environmental_platform_init')) {
    echo "<p>✅ Environmental Platform Core plugin is loaded</p>";
} else {
    echo "<p>❌ Environmental Platform Core plugin is not loaded</p>";
}

// Check if plugin classes exist
if (class_exists('Environmental_Platform_User_Management')) {
    echo "<p>✅ User Management class exists</p>";
} else {
    echo "<p>❌ User Management class not found</p>";
}

if (class_exists('Environmental_Platform_Social_Auth')) {
    echo "<p>✅ Social Auth class exists</p>";
} else {
    echo "<p>❌ Social Auth class not found</p>";
}

// Test database connection
global $wpdb;
$test_query = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}users");
if ($test_query !== null) {
    echo "<p>✅ Database connection working - Found {$test_query} users</p>";
} else {
    echo "<p>❌ Database connection failed</p>";
}

// Test custom tables
$custom_tables = [
    'ep_user_profiles',
    'ep_user_levels',
    'ep_user_points',
    'ep_achievements',
    'ep_user_achievements'
];

foreach ($custom_tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'");
    if ($exists) {
        echo "<p>✅ Table {$table} exists</p>";
    } else {
        echo "<p>❌ Table {$table} missing</p>";
    }
}

// Test shortcodes
if (shortcode_exists('ep_login_form')) {
    echo "<p>✅ Login form shortcode registered</p>";
} else {
    echo "<p>❌ Login form shortcode not registered</p>";
}

if (shortcode_exists('ep_registration_form')) {
    echo "<p>✅ Registration form shortcode registered</p>";
} else {
    echo "<p>❌ Registration form shortcode not registered</p>";
}

if (shortcode_exists('ep_user_profile')) {
    echo "<p>✅ User profile shortcode registered</p>";
} else {
    echo "<p>❌ User profile shortcode not registered</p>";
}

// Test template files
$template_files = [
    'templates/login-form.php',
    'templates/registration-form.php',
    'templates/user-profile.php',
    'templates/social-login.php'
];

foreach ($template_files as $template) {
    $path = WP_PLUGIN_DIR . '/environmental-platform-core/' . $template;
    if (file_exists($path)) {
        echo "<p>✅ Template {$template} exists</p>";
    } else {
        echo "<p>❌ Template {$template} missing</p>";
    }
}

echo "<h2>Test Complete</h2>";
echo "<p>If all items show ✅, Phase 31 is properly configured.</p>";
?>
