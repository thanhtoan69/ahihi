<?php
/**
 * Test SEO Plugin Activation
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load WordPress
require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';
require_once __DIR__ . '/wp-admin/includes/plugin.php';

echo "<h1>SEO Plugin Activation Test</h1>";

// Check if Environmental SEO plugin exists
$plugin_file = 'environmental-platform-seo/environmental-platform-seo.php';

if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
    echo "<p>✓ Environmental SEO Plugin file exists</p>";
    
    // Check if plugin is active
    if (is_plugin_active($plugin_file)) {
        echo "<p>✓ Environmental SEO Plugin is already active</p>";
    } else {
        echo "<p>⚠ Environmental SEO Plugin is not active - activating now...</p>";
        
        // Activate the plugin
        $result = activate_plugin($plugin_file);
        
        if (is_wp_error($result)) {
            echo "<p>❌ Failed to activate plugin: " . $result->get_error_message() . "</p>";
        } else {
            echo "<p>✓ Environmental SEO Plugin activated successfully!</p>";
        }
    }
} else {
    echo "<p>❌ Environmental SEO Plugin file not found</p>";
}

// Check for other SEO plugins
echo "<h2>Other SEO Plugins Status:</h2>";

$seo_plugins = [
    'wordpress-seo/wp-seo.php' => 'Yoast SEO',
    'seo-by-rank-math/rank-math.php' => 'Rank Math',
    'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO'
];

foreach ($seo_plugins as $plugin => $name) {
    if (is_plugin_active($plugin)) {
        echo "<p>✓ $name is active</p>";
    } else {
        echo "<p>⚪ $name is not active</p>";
    }
}

// Test Environmental SEO functionality
if (class_exists('EnvironmentalPlatformSEO')) {
    echo "<p>✓ EnvironmentalPlatformSEO class is available</p>";
} else {
    echo "<p>❌ EnvironmentalPlatformSEO class not found</p>";
}

echo "<h2>WordPress Environment:</h2>";
echo "<p>WordPress Version: " . get_bloginfo('version') . "</p>";
echo "<p>Site URL: " . get_site_url() . "</p>";
echo "<p>Plugin Directory: " . WP_PLUGIN_DIR . "</p>";

?>
