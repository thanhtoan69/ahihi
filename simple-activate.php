<?php
/**
 * Simple Plugin Activation
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('wp-load.php');

// Activate plugin
$result = activate_plugin('environmental-platform-petitions/environmental-platform-petitions.php');

if (is_wp_error($result)) {
    echo "❌ Error: " . $result->get_error_message() . "\n";
} else {
    echo "✅ Plugin activated successfully!\n";
}

// Check if active
if (is_plugin_active('environmental-platform-petitions/environmental-platform-petitions.php')) {
    echo "✅ Plugin is now active\n";
} else {
    echo "❌ Plugin is not active\n";
}
?>
