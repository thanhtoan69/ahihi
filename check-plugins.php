<?php
/**
 * Plugin Status Check
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "=== PLUGIN STATUS CHECK ===\n";

// Check if environmental-platform-core exists and is active
$core_plugin = 'environmental-platform-core/environmental-platform-core.php';
if (is_plugin_active($core_plugin)) {
    echo "✅ Environmental Platform Core is active\n";
} else {
    echo "❌ Environmental Platform Core is not active\n";
    
    // Try to activate it
    $result = activate_plugin($core_plugin);
    if (is_wp_error($result)) {
        echo "❌ Failed to activate core plugin: " . $result->get_error_message() . "\n";
    } else {
        echo "✅ Core plugin activated\n";
    }
}

// Now try to activate petitions plugin
$petitions_plugin = 'environmental-platform-petitions/environmental-platform-petitions.php';
echo "\n--- Activating Petitions Plugin ---\n";

$result = activate_plugin($petitions_plugin, '', false, true);
if (is_wp_error($result)) {
    echo "❌ Error activating petitions plugin: " . $result->get_error_message() . "\n";
} else {
    echo "✅ Petitions plugin activated successfully!\n";
    
    // Check if it's really active
    if (is_plugin_active($petitions_plugin)) {
        echo "✅ Petitions plugin is now active\n";
    } else {
        echo "❌ Petitions plugin activation failed\n";
    }
}

// List all active plugins
echo "\n--- Active Plugins ---\n";
$active = get_option('active_plugins', array());
foreach ($active as $plugin) {
    echo "✓ " . $plugin . "\n";
}
?>
