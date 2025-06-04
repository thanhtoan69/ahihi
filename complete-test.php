<?php
/**
 * Phase 31 Complete Integration Test and Plugin Activation
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('wp-blog-header.php');

// Function to activate plugin if not active
function activate_environmental_plugin() {
    $plugin_file = 'environmental-platform-core/environmental-platform-core.php';
    
    if (!is_plugin_active($plugin_file)) {
        $result = activate_plugin($plugin_file);
        if (is_wp_error($result)) {
            return "Error activating plugin: " . $result->get_error_message();
        } else {
            return "Plugin activated successfully!";
        }
    }
    return "Plugin is already active";
}

echo "<h1>Phase 31 Complete Integration Test</h1>";

// Test 1: WordPress Environment
echo "<h2>1. WordPress Environment</h2>";
if (function_exists('wp_head')) {
    echo "<p>✅ WordPress loaded successfully</p>";
    echo "<p>WordPress Version: " . get_bloginfo('version') . "</p>";
} else {
    echo "<p>❌ WordPress failed to load</p>";
    exit;
}

// Test 2: Plugin Activation
echo "<h2>2. Plugin Activation</h2>";
$activation_result = activate_environmental_plugin();
echo "<p>{$activation_result}</p>";

// Check if plugin is now active
if (is_plugin_active('environmental-platform-core/environmental-platform-core.php')) {
    echo "<p>✅ Environmental Platform Core plugin is active</p>";
} else {
    echo "<p>❌ Environmental Platform Core plugin is not active</p>";
}

// Test 3: Database Tables
echo "<h2>3. Database Tables</h2>";
global $wpdb;

$required_tables = [
    'ep_user_profiles' => 'User Profiles',
    'ep_user_levels' => 'User Levels',
    'ep_user_points' => 'User Points',
    'ep_achievements' => 'Achievements',
    'ep_user_achievements' => 'User Achievements'
];

foreach ($required_tables as $table => $description) {
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . $table));
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}{$table}");
        echo "<p>✅ {$description} table exists ({$count} records)</p>";
    } else {
        echo "<p>❌ {$description} table missing</p>";
    }
}

// Test 4: Plugin Classes
echo "<h2>4. Plugin Classes</h2>";
$required_classes = [
    'Environmental_Platform_Core' => 'Main Plugin Class',
    'Environmental_Platform_User_Management' => 'User Management',
    'Environmental_Platform_Social_Auth' => 'Social Authentication'
];

foreach ($required_classes as $class => $description) {
    if (class_exists($class)) {
        echo "<p>✅ {$description} class exists</p>";
    } else {
        echo "<p>❌ {$description} class not found</p>";
    }
}

// Test 5: Shortcodes
echo "<h2>5. Shortcodes</h2>";
$required_shortcodes = [
    'ep_login_form' => 'Login Form',
    'ep_registration_form' => 'Registration Form',
    'ep_user_profile' => 'User Profile',
    'ep_social_login' => 'Social Login'
];

foreach ($required_shortcodes as $shortcode => $description) {
    if (shortcode_exists($shortcode)) {
        echo "<p>✅ {$description} shortcode registered</p>";
    } else {
        echo "<p>❌ {$description} shortcode not registered</p>";
    }
}

// Test 6: Template Files
echo "<h2>6. Template Files</h2>";
$plugin_dir = WP_PLUGIN_DIR . '/environmental-platform-core';
$required_templates = [
    'templates/login-form.php' => 'Login Form Template',
    'templates/registration-form.php' => 'Registration Form Template',
    'templates/user-profile.php' => 'User Profile Template',
    'templates/social-login.php' => 'Social Login Template'
];

foreach ($required_templates as $template => $description) {
    if (file_exists($plugin_dir . '/' . $template)) {
        echo "<p>✅ {$description} exists</p>";
    } else {
        echo "<p>❌ {$description} missing</p>";
    }
}

// Test 7: Admin Pages
echo "<h2>7. Admin Integration</h2>";
if (function_exists('add_action')) {
    // Check if admin menu is registered
    if (is_admin()) {
        echo "<p>✅ WordPress admin environment detected</p>";
    } else {
        echo "<p>ℹ️ Not in admin environment (normal for frontend test)</p>";
    }
}

// Test 8: Shortcode Output Test
echo "<h2>8. Shortcode Output Test</h2>";
try {
    $login_output = do_shortcode('[ep_login_form]');
    if (!empty($login_output) && strpos($login_output, 'form') !== false) {
        echo "<p>✅ Login form shortcode produces output</p>";
    } else {
        echo "<p>⚠️ Login form shortcode output: " . substr($login_output, 0, 100) . "...</p>";
    }
    
    $profile_output = do_shortcode('[ep_user_profile]');
    if (!empty($profile_output)) {
        echo "<p>✅ User profile shortcode produces output</p>";
    } else {
        echo "<p>⚠️ User profile shortcode produces no output (normal if not logged in)</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error testing shortcodes: " . $e->getMessage() . "</p>";
}

// Test 9: Navigation Links
echo "<h2>9. Quick Navigation</h2>";
echo "<p><a href='/moitruong/wp-admin/' target='_blank'>🔧 WordPress Admin</a></p>";
echo "<p><a href='/moitruong/wp-admin/plugins.php' target='_blank'>🔌 Plugins Page</a></p>";
echo "<p><a href='/moitruong/wp-admin/admin.php?page=environmental-users' target='_blank'>👥 Environmental Users Admin</a></p>";

// Test 10: Sample Shortcode Page
echo "<h2>10. Live Shortcode Demo</h2>";
echo "<div style='border: 1px solid #ccc; padding: 20px; margin: 10px 0;'>";
echo "<h3>Login Form:</h3>";
echo do_shortcode('[ep_login_form]');
echo "</div>";

echo "<div style='border: 1px solid #ccc; padding: 20px; margin: 10px 0;'>";
echo "<h3>Registration Form:</h3>";
echo do_shortcode('[ep_registration_form]');
echo "</div>";

if (is_user_logged_in()) {
    echo "<div style='border: 1px solid #ccc; padding: 20px; margin: 10px 0;'>";
    echo "<h3>User Profile:</h3>";
    echo do_shortcode('[ep_user_profile]');
    echo "</div>";
}

echo "<h2>Test Complete!</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>Visit WordPress Admin to configure plugin settings</li>";
echo "<li>Test user registration and login functionality</li>";
echo "<li>Configure social authentication providers</li>";
echo "<li>Test admin user management features</li>";
echo "</ul>";
?>
